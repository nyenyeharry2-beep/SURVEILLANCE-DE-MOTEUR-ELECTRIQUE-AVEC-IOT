<?php
/**
 * Fonctions utilitaires
 */

require_once __DIR__ . '/db.php';

// ─── Sécurité ───────────────────────────────────────────

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

// ─── Paramètres ─────────────────────────────────────────

function getSetting(string $key, ?string $default = null): ?string
{
    static $cache = [];
    if (!isset($cache[$key])) {
        $stmt = getDB()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $cache[$key] = $stmt->fetchColumn() ?: $default;
    }
    return $cache[$key] ?? $default;
}

function setSetting(string $key, string $value): void
{
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
    $stmt->execute([$key, $value, $value]);
}

function getAllSettings(): array
{
    $stmt = getDB()->query('SELECT setting_key, setting_value FROM settings');
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

// ─── Année scolaire ─────────────────────────────────────

function getActiveAcademicYear(): ?array
{
    $stmt = getDB()->query('SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1');
    return $stmt->fetch() ?: null;
}

function getAcademicYearById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM academic_years WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getAllAcademicYears(): array
{
    return getDB()->query('SELECT * FROM academic_years ORDER BY label DESC')->fetchAll();
}

function getSelectedYearId(): int
{
    if (!empty($_SESSION['selected_year_id'])) {
        return (int) $_SESSION['selected_year_id'];
    }
    $year = getActiveAcademicYear();
    return $year ? (int) $year['id'] : 0;
}

// ─── Numéros auto ───────────────────────────────────────

function getNextCounter(string $name): int
{
    $db = getDB();
    $year = (int) date('Y');
    $ownsTransaction = !$db->inTransaction();
    if ($ownsTransaction) {
        $db->beginTransaction();
    }
    try {
        $stmt = $db->prepare('SELECT value FROM counters WHERE name = ? AND year = ? FOR UPDATE');
        $stmt->execute([$name, $year]);
        $row = $stmt->fetch();
        if ($row) {
            $next = $row['value'] + 1;
            $db->prepare('UPDATE counters SET value = ? WHERE name = ? AND year = ?')->execute([$next, $name, $year]);
        } else {
            $next = 1;
            $db->prepare('INSERT INTO counters (name, year, value) VALUES (?, ?, ?)')->execute([$name, $year, $next]);
        }
        if ($ownsTransaction) {
            $db->commit();
        }
        return $next;
    } catch (Exception $ex) {
        if ($ownsTransaction && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $ex;
    }
}

function generateDossierNumber(): string
{
    $prefix = getSetting('dossier_prefix', 'BUS');
    $year = date('Y');
    $num = getNextCounter('dossier');
    return sprintf('%s-%s-%05d', $prefix, $year, $num);
}

function generateReceiptNumber(): string
{
    $prefix = getSetting('receipt_prefix', 'BUS');
    $year = date('Y');
    $num = getNextCounter('receipt');
    return sprintf('%s-%s-%06d', $prefix, $year, $num);
}

// ─── Paiements ──────────────────────────────────────────

function calculatePaymentStatus(float $montantDu, float $montantPaye): array
{
    $reste = max(0, $montantDu - $montantPaye);
    if ($montantPaye <= 0) {
        $statut = 'impaye';
    } elseif ($montantPaye >= $montantDu) {
        $statut = 'paye';
        $reste = 0;
    } else {
        $statut = 'partiel';
    }
    return ['reste' => $reste, 'statut' => $statut];
}

function getPaymentStatusLabel(string $statut): string
{
    return match ($statut) {
        'paye'    => 'Payé',
        'partiel' => 'Partiel',
        'impaye'  => 'Non payé',
        default   => $statut,
    };
}

function getPaymentStatusBadge(string $statut): string
{
    return match ($statut) {
        'paye'    => '<span class="badge bg-success">Payé</span>',
        'partiel' => '<span class="badge bg-warning text-dark">Partiel</span>',
        'impaye'  => '<span class="badge bg-danger">Non payé</span>',
        default   => '<span class="badge bg-secondary">' . e($statut) . '</span>',
    };
}

function getPaymentStatusIcon(string $statut): string
{
    return match ($statut) {
        'paye'    => '✅',
        'partiel' => '🟠',
        'impaye'  => '❌',
        default   => '—',
    };
}

function formatMonthPayment(?array $payment): string
{
    if (!$payment || (float)$payment['montant_paye'] <= 0) {
        return '—';
    }
    $amount = (float) $payment['montant_paye'];
    $formatted = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    return $formatted . '$';
}

// ─── Logo et en-tête école ───────────────────────────────

function getSchoolLogoUrl(): string
{
    return BASE_URL . '/assets/images/logo.jpg';
}

function schoolLogoExists(): bool
{
    static $exists = null;
    if ($exists === null) {
        $exists = file_exists(__DIR__ . '/../assets/images/logo.jpg');
    }
    return $exists;
}

function renderSchoolLogo(string $size = 'medium', string $extraClass = ''): string
{
    if (!schoolLogoExists()) {
        return '';
    }
    $sizes = [
        'small'  => 'height:32px;max-height:32px;max-width:40px;',
        'medium' => 'height:48px;max-height:48px;max-width:60px;',
        'banner' => 'height:50px;max-height:50px;max-width:65px;',
        'large'  => 'height:60px;max-height:60px;max-width:75px;',
        'print'  => 'height:55px;max-height:55px;max-width:70px;',
    ];
    $style = ($sizes[$size] ?? $sizes['medium']) . 'width:auto;object-fit:contain;background:#fff;border-radius:6px;padding:2px;display:inline-block;';
    $class = trim('school-logo school-logo-' . $size . ' ' . $extraClass);
    return '<img src="' . e(getSchoolLogoUrl()) . '" alt="Logo ' . e(getSetting('school_name', 'C.S LES SUPER GENIES')) . '" class="' . e($class) . '" style="' . $style . '">';
}

function renderSchoolBanner(?string $subtitle = null): void
{
    $subtitle = $subtitle ?? '🚌 Inscription au transport scolaire';
    echo '<div class="school-banner text-center">';
    if (schoolLogoExists()) {
        echo '<div class="school-banner-logo">' . renderSchoolLogo('banner') . '</div>';
    }
    echo '<h5 class="mb-0 fw-bold">' . e(getSetting('school_foundation', 'FONDATION EBEN EZER – ORA S.A.R.I')) . '</h5>';
    echo '<p class="mb-0 small">' . e(getSetting('school_project', 'PROJET EDUCATIF')) . '</p>';
    echo '<h4 class="mb-0 fw-bold">' . e(getSetting('school_name', 'C.S LES SUPER GENIES')) . '</h4>';
    if ($subtitle) {
        echo '<p class="mb-0 small school-banner-subtitle">' . e($subtitle) . '</p>';
    }
    echo '</div>';
}

/**
 * En-tête imprimable pour fiches (contrôle, export PDF, etc.)
 * $meta keys: section, classe, title (default Minerval), year, date, right_html
 */
function renderSchoolPrintHeader(array $settings, array $meta = []): void
{
    $title = $meta['title'] ?? 'Minerval';
    $section = $meta['section'] ?? '________';
    $option = $meta['option'] ?? '';
    $classe = $meta['classe'] ?? '________';
    $year = $meta['year'] ?? '';
    $date = $meta['date'] ?? date('d/m/Y');
    $showDate = $meta['show_date'] ?? false;

    echo '<div class="school-print-header">';
    if (schoolLogoExists()) {
        echo '<div class="school-print-logo">' . renderSchoolLogo('print') . '</div>';
    }
    echo '<div class="school-print-body" style="font-size:11px;">';
    echo '<div class="school-print-left">';
    echo '<strong>' . e($settings['school_foundation'] ?? '') . '</strong><br>';
    echo e($settings['school_project'] ?? '') . '<br>';
    echo '<strong class="school-print-name">' . e($settings['school_name'] ?? '') . '</strong><br>';
    echo e($settings['school_address'] ?? '') . '<br>';
    echo e($settings['school_quarter'] ?? '') . '<br>';
    echo '<strong>' . e($settings['school_city'] ?? '') . '</strong><br>';
    echo 'Mail: ' . e($settings['school_email'] ?? '') . '<br>';
    echo 'TEL: ' . e($settings['school_phone'] ?? '');
    echo '</div>';
    echo '<div class="school-print-right">';
    echo '<strong>SERVICE CONTROLE</strong><br>';
    echo 'Section : <strong>' . e($section) . '</strong><br>';
    if ($option && $option !== '—') {
        echo 'Option : <strong>' . e($option) . '</strong><br>';
    }
    echo 'Classe : <strong>' . e($classe) . '</strong><br><br>';
    echo '<strong class="school-print-title">' . e($title) . '</strong><br>';
    if ($year) {
        echo '<small>Année : ' . e($year) . '</small><br>';
    }
    if ($showDate) {
        echo '<small>Date : ' . e($date) . '</small>';
    }
    if (!empty($meta['right_html'])) {
        echo $meta['right_html'];
    }
    echo '</div>';
    echo '</div>';
    echo '<hr class="school-print-divider">';
    echo '</div>';
}

// ─── Fiche de contrôle (colonnes mois + OK) ─────────────

function formatStudentFullAddress(array $student, bool $includePhones = true): string
{
    $parts = [];

    $adresse = trim($student['adresse'] ?? '');
    if ($adresse !== '') {
        $parts[] = $adresse;
    }

    $arretNom = trim($student['arret_nom'] ?? '');
    $arretPrecision = trim($student['arret_precision'] ?? '');
    if ($arretNom !== '' || $arretPrecision !== '') {
        $arretLine = 'Arrêt : ' . $arretNom;
        if ($arretPrecision !== '') {
            $arretLine .= ($arretNom !== '' ? ' — ' : '') . $arretPrecision;
        }
        $parts[] = trim($arretLine, ' :—');
    }

    if ($includePhones) {
        $telephone = trim($student['telephone_parent'] ?? '');
        if ($telephone !== '') {
            $parts[] = 'Tél : ' . $telephone;
        }

        $telephone2 = trim($student['telephone_parent2'] ?? '');
        if ($telephone2 !== '') {
            $parts[] = 'Tél 2 : ' . $telephone2;
        }
    }

    return $parts ? implode("\n", $parts) : '—';
}

function formatStudentPhones(array $student): string
{
    $phones = [];
    $telephone = trim($student['telephone_parent'] ?? '');
    $telephone2 = trim($student['telephone_parent2'] ?? '');

    if ($telephone !== '') {
        $phones[] = $telephone;
    }
    if ($telephone2 !== '') {
        $phones[] = $telephone2;
    }

    return $phones ? implode("\n", $phones) : '—';
}

function getControlSheetColspan(): int
{
    // N° + Nom + Adresse + Tél parents + (Mois + colonne OK) × 10 mois
    return 4 + count(SCHOOL_MONTHS) * 2;
}

function renderControlSheetThead(bool $pdfMode = false): void
{
    $monthClass = $pdfMode ? '' : 'col-month';
    $okClass = $pdfMode ? 'col-ok-pdf' : 'col-ok';
    $addressClass = $pdfMode ? 'col-address-pdf' : 'col-address';
    $telClass = $pdfMode ? 'col-tel-pdf' : 'col-tel';
    echo '<thead><tr>';
    echo '<th class="col-num">N°</th>';
    echo '<th class="col-name">NOM &amp; POST-NOM</th>';
    echo '<th class="' . $addressClass . '">ADRESSE COMPLÈTE</th>';
    echo '<th class="' . $telClass . '">TÉL PARENTS</th>';
    foreach (SCHOOL_MONTHS as $info) {
        echo '<th class="' . $monthClass . '">' . e($info['short']) . '</th>';
        echo '<th class="' . $okClass . '">&nbsp;</th>';
    }
    echo '</tr></thead>';
}

function renderControlSheetStudentRow(array $student, array $studentPayments, int $num, bool $interactive = false, bool $pdfMode = false): void
{
    $monthClass = $pdfMode ? '' : 'col-month';
    $okClassBase = $pdfMode ? 'col-ok-pdf' : 'col-ok';

    $addressClass = $pdfMode ? 'col-address-pdf' : 'col-address';
    $telClass = $pdfMode ? 'col-tel-pdf' : 'col-tel';
    $fullAddress = formatStudentFullAddress($student, false);
    $phones = formatStudentPhones($student);

    echo '<tr>';
    echo '<td class="col-num">' . str_pad((string) $num, 2, '0', STR_PAD_LEFT) . '</td>';
    echo '<td class="col-name">' . e($student['nom_complet']) . '</td>';
    echo '<td class="' . $addressClass . '">' . nl2br(e($fullAddress)) . '</td>';
    echo '<td class="' . $telClass . '">' . nl2br(e($phones)) . '</td>';

    foreach (SCHOOL_MONTHS as $monthNum => $info) {
        $p = $studentPayments[$monthNum] ?? null;
        $cellClass = $monthClass;
        $cellContent = '';

        if ($p) {
            if ((float) $p['montant_paye'] > 0) {
                $cellContent = formatMonthPayment($p);
                $cellClass = trim($cellClass . ' cell-paid');
            } else {
                $cellContent = '—';
                $cellClass = trim($cellClass . ' cell-empty');
            }
        }

        echo '<td class="' . $cellClass . '">' . $cellContent . '</td>';

        // Colonne vide entre chaque mois pour marquer OK après vérification
        $okClass = $okClassBase;
        $okContent = '&nbsp;';
        if ($p && (float) $p['montant_paye'] > 0) {
            if ($p['verified_ok']) {
                $okClass .= ' cell-ok-marked';
                $okContent = '<strong>OK</strong>';
            } elseif ($interactive) {
                $okContent = '<form method="POST" class="d-inline mark-ok-form">'
                    . csrfField()
                    . '<input type="hidden" name="action" value="mark_ok">'
                    . '<input type="hidden" name="payment_id" value="' . (int) $p['id'] . '">'
                    . '<button type="submit" class="btn btn-sm btn-outline-success py-0 px-1" title="Marquer OK">OK</button>'
                    . '</form>';
            }
        }
        echo '<td class="' . $okClass . '">' . $okContent . '</td>';
    }
    echo '</tr>';
}

function renderControlSheetBlankRow(int $num, bool $pdfMode = false): void
{
    echo '<tr>';
    echo '<td class="col-num">' . str_pad((string) $num, 2, '0', STR_PAD_LEFT) . '</td>';
    echo '<td class="col-name">&nbsp;</td>';
    echo '<td class="' . ($pdfMode ? 'col-address-pdf' : 'col-address') . '">&nbsp;</td>';
    echo '<td class="' . ($pdfMode ? 'col-tel-pdf' : 'col-tel') . '">&nbsp;</td>';
    for ($i = 0; $i < count(SCHOOL_MONTHS); $i++) {
        echo '<td class="' . ($pdfMode ? '' : 'col-month') . '">&nbsp;</td>';
        echo '<td class="' . ($pdfMode ? 'col-ok-pdf' : 'col-ok') . '">&nbsp;</td>';
    }
    echo '</tr>';
}

// ─── Élèves ─────────────────────────────────────────────

function findStudentByName(string $nomComplet, int $yearId): ?array
{
    $stmt = getDB()->prepare(
        'SELECT * FROM students WHERE LOWER(TRIM(nom_complet)) = LOWER(TRIM(?)) AND academic_year_id = ? AND statut = "actif" LIMIT 1'
    );
    $stmt->execute([$nomComplet, $yearId]);
    return $stmt->fetch() ?: null;
}

function getStudentById(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT s.*, c.nom AS classe_nom, c.section AS classe_section, bs.nom AS arret_nom, t.montant AS tariff_montant
         FROM students s
         LEFT JOIN classes c ON s.classe_id = c.id
         LEFT JOIN bus_stops bs ON s.arret_id = bs.id
         LEFT JOIN tariffs t ON s.tariff_id = t.id
         WHERE s.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getStudentPayments(int $studentId, int $yearId): array
{
    $stmt = getDB()->prepare(
        'SELECT * FROM payments WHERE student_id = ? AND academic_year_id = ? ORDER BY FIELD(mois, 9,10,11,12,1,2,3,4,5,6)'
    );
    $stmt->execute([$studentId, $yearId]);
    $payments = [];
    foreach ($stmt->fetchAll() as $p) {
        $payments[(int)$p['mois']] = $p;
    }
    return $payments;
}

function getStudentPaymentsMatrix(int $studentId, int $yearId): array
{
    $payments = getStudentPayments($studentId, $yearId);
    $matrix = [];
    foreach (SCHOOL_MONTHS as $num => $info) {
        $matrix[$num] = $payments[$num] ?? null;
    }
    return $matrix;
}

// ─── Journal ────────────────────────────────────────────

function logActivity(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = getDB()->prepare(
            'INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
    } catch (Exception $e) {
        // Ne pas bloquer l'inscription publique si le journal est indisponible
    }
}

// ─── Listes ─────────────────────────────────────────────

function getSectionDisplayOrder(): array
{
    return [
        'Maternelle' => 1,
        'Primaire' => 2,
        'Secondaire' => 3,
    ];
}

function compareClassesByOrder(array $a, array $b): int
{
    $ordreCmp = ((int) ($a['ordre'] ?? 0)) <=> ((int) ($b['ordre'] ?? 0));
    if ($ordreCmp !== 0) {
        return $ordreCmp;
    }
    return strcasecmp((string) ($a['nom'] ?? ''), (string) ($b['nom'] ?? ''));
}

function compareSectionsByOrder(string $a, string $b): int
{
    $order = getSectionDisplayOrder();
    $oa = $order[$a] ?? 100;
    $ob = $order[$b] ?? 100;
    if ($oa !== $ob) {
        return $oa <=> $ob;
    }
    return strcasecmp($a, $b);
}

function getActiveClasses(): array
{
    try {
        $classes = getDB()->query('SELECT * FROM classes WHERE statut = "actif" ORDER BY ordre ASC, nom ASC')->fetchAll();
        if (empty($classes)) {
            $classes = getDB()->query('SELECT * FROM classes ORDER BY ordre ASC, nom ASC')->fetchAll();
        }
        usort($classes, 'compareClassesByOrder');
        return $classes;
    } catch (Exception $e) {
        return [];
    }
}

function getClassesForSelect(): array
{
    return getActiveClasses();
}

function extractYearLevelFromClassName(string $nom): string
{
    if (preg_match('/^(1ère|2ème|3ème|4ème|5ème|6ème|7ème|8ème)/u', $nom, $matches)) {
        return $matches[1];
    }
    return '';
}

function getMainSchoolSections(): array
{
    return ['Maternelle', 'Primaire', 'Secondaire', 'Options'];
}

function getClassPickerStructure(): array
{
    $structure = [
        'Maternelle' => [],
        'Primaire' => [],
        'Secondaire' => [],
        'Options' => [
            'years' => ['1ère', '2ème', '3ème', '4ème'],
            'specialties' => [],
        ],
    ];

    foreach (getActiveClasses() as $classe) {
        $section = $classe['section'] ?? '';
        $item = [
            'id' => (int) $classe['id'],
            'label' => formatClassName($classe),
            'year' => extractYearLevelFromClassName($classe['nom'] ?? ''),
        ];

        if ($section === 'Maternelle') {
            $structure['Maternelle'][] = $item;
        } elseif ($section === 'Primaire') {
            $structure['Primaire'][] = $item;
        } elseif ($section === 'Secondaire') {
            $structure['Secondaire'][] = $item;
        } elseif ($section !== '') {
            if (!isset($structure['Options']['specialties'][$section])) {
                $structure['Options']['specialties'][$section] = [];
            }
            $structure['Options']['specialties'][$section][] = $item;
        }
    }

    ksort($structure['Options']['specialties']);

    return $structure;
}

function getClassesGroupedBySection(): array
{
    $grouped = [];
    foreach (getActiveClasses() as $classe) {
        $sec = $classe['section'] ?: 'Autre';
        $grouped[$sec][] = $classe;
    }
    uksort($grouped, 'compareSectionsByOrder');
    foreach ($grouped as &$sectionClasses) {
        usort($sectionClasses, 'compareClassesByOrder');
    }
    unset($sectionClasses);
    return $grouped;
}

function getSchoolSections(): array
{
    $stmt = getDB()->query('SELECT DISTINCT section FROM classes WHERE statut = "actif" AND section IS NOT NULL AND section != "" ORDER BY MIN(ordre)');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getClassById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getClassesBySection(?string $section): array
{
    if (!$section) return getActiveClasses();
    $stmt = getDB()->prepare('SELECT * FROM classes WHERE statut = "actif" AND section = ? ORDER BY ordre, nom');
    $stmt->execute([$section]);
    return $stmt->fetchAll();
}

function getMainSectionsForFilter(): array
{
    return ['Maternelle', 'Primaire', 'Secondaire', 'Options'];
}

function getCoreSchoolSections(): array
{
    return ['Maternelle', 'Primaire', 'Secondaire'];
}

function getOptionSpecialties(): array
{
    $core = getCoreSchoolSections();
    $stmt = getDB()->query('SELECT DISTINCT section FROM classes WHERE statut = "actif" AND section IS NOT NULL AND section != "" ORDER BY section');
    $all = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_filter($all, static fn($s) => !in_array($s, $core, true)));
}

function parseReportFilters(array $input = []): array
{
    return [
        'section' => trim($input['section'] ?? ''),
        'option' => trim($input['option'] ?? ''),
        'classe' => (int) ($input['classe'] ?? 0),
    ];
}

function getClassesForAdminFilter(?string $mainSection, ?string $optionFiliere = null): array
{
    $classes = getActiveClasses();
    if (!$mainSection) {
        return $classes;
    }

    if (in_array($mainSection, getCoreSchoolSections(), true)) {
        return array_values(array_filter($classes, static fn($c) => ($c['section'] ?? '') === $mainSection));
    }

    if ($mainSection === 'Options') {
        $core = getCoreSchoolSections();
        $filtered = array_values(array_filter($classes, static fn($c) => !in_array($c['section'] ?? '', $core, true)));
        if ($optionFiliere) {
            $filtered = array_values(array_filter($filtered, static fn($c) => ($c['section'] ?? '') === $optionFiliere));
        }
        return $filtered;
    }

    return $classes;
}

function applyStudentListFilters(string &$sql, array &$params, array $filters): void
{
    $filterClasse = (int) ($filters['classe'] ?? 0);
    $filterSection = trim($filters['section'] ?? '');
    $filterOption = trim($filters['option'] ?? '');

    if ($filterClasse) {
        $sql .= ' AND s.classe_id = ?';
        $params[] = $filterClasse;
        return;
    }

    if (in_array($filterSection, getCoreSchoolSections(), true)) {
        $sql .= ' AND c.section = ?';
        $params[] = $filterSection;
        return;
    }

    if ($filterSection === 'Options') {
        if ($filterOption) {
            $sql .= ' AND c.section = ?';
            $params[] = $filterOption;
            return;
        }
        $core = getCoreSchoolSections();
        $placeholders = implode(',', array_fill(0, count($core), '?'));
        $sql .= " AND c.section NOT IN ($placeholders)";
        array_push($params, ...$core);
    }
}

function getReportFilterDisplayMeta(array $filters): array
{
    $section = trim($filters['section'] ?? '');
    $option = trim($filters['option'] ?? '');
    $classeId = (int) ($filters['classe'] ?? 0);
    $selectedClass = $classeId ? getClassById($classeId) : null;

    $displaySection = 'Toutes';
    if ($section === 'Options' && $option) {
        $displaySection = 'Options — ' . $option;
    } elseif ($section) {
        $displaySection = $section;
    }

    $displayClasse = $selectedClass ? formatClassName($selectedClass) : 'Toutes';
    $displayOption = ($section === 'Options' && $option) ? $option : '—';

    return [
        'section' => $displaySection,
        'classe' => $displayClasse,
        'option' => $displayOption,
    ];
}

function buildReportExportQuery(array $filters, string $type, bool $autoDownloadPdf = true): string
{
    $query = parseReportFilters($filters);
    $query['type'] = $type;
    if ($type === 'pdf' && $autoDownloadPdf) {
        $query['download'] = '1';
    }
    return http_build_query($query);
}

function buildReportExportFilename(array $filters, string $yearLabel, string $ext): string
{
    $parts = ['transport', preg_replace('/[^a-zA-Z0-9_-]+/', '_', $yearLabel)];
    $section = trim($filters['section'] ?? '');
    $option = trim($filters['option'] ?? '');
    $classeId = (int) ($filters['classe'] ?? 0);

    if ($section) {
        $parts[] = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $section);
    }
    if ($section === 'Options' && $option) {
        $parts[] = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $option);
    }
    if ($classeId) {
        $cl = getClassById($classeId);
        if ($cl) {
            $parts[] = preg_replace('/[^a-zA-Z0-9_-]+/', '_', formatClassName($cl));
        }
    }

    return implode('_', $parts) . '.' . $ext;
}

function getActiveStops(): array
{
    return getDB()->query('SELECT * FROM bus_stops WHERE statut = "actif" ORDER BY nom')->fetchAll();
}

function getDefaultTariffAmount(): float
{
    $tariff = getDefaultTariff();
    if ($tariff) {
        return (float) $tariff['montant'];
    }
    $fromSettings = getSetting('default_tariff');
    return $fromSettings !== null ? (float) $fromSettings : 15.0;
}

function getActiveTariffs(): array
{
    return getDB()->query('SELECT * FROM tariffs WHERE statut = "actif" ORDER BY montant ASC, nom')->fetchAll();
}

function getDefaultTariff(): ?array
{
    $stmt = getDB()->query('SELECT * FROM tariffs WHERE is_default = 1 AND statut = "actif" LIMIT 1');
    return $stmt->fetch() ?: null;
}

function formatClassName(?array $classe): string
{
    if (!$classe) return '—';
    return trim($classe['nom'] ?? $classe['classe_nom'] ?? '—');
}

function formatClassWithSection(?array $classe): string
{
    if (!$classe) return '—';
    $name = formatClassName($classe);
    $section = $classe['section'] ?? $classe['classe_section'] ?? '';
    if ($section && stripos($name, $section) === false) {
        return $section . ' — ' . $name;
    }
    return $name;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string
{
    $flash = getFlashMessage();
    if (!$flash) return '';
    $class = match ($flash['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">'
        . e($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
