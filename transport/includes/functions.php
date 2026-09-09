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
    $db->beginTransaction();
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
        $db->commit();
        return $next;
    } catch (Exception $ex) {
        $db->rollBack();
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
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = getDB()->prepare(
        'INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
}

// ─── Listes ─────────────────────────────────────────────

function getActiveClasses(): array
{
    return getDB()->query('SELECT * FROM classes WHERE statut = "actif" ORDER BY ordre, nom, section')->fetchAll();
}

function getActiveStops(): array
{
    return getDB()->query('SELECT * FROM bus_stops WHERE statut = "actif" ORDER BY nom')->fetchAll();
}

function getActiveTariffs(): array
{
    return getDB()->query('SELECT * FROM tariffs WHERE statut = "actif" ORDER BY is_default DESC, nom')->fetchAll();
}

function getDefaultTariff(): ?array
{
    $stmt = getDB()->query('SELECT * FROM tariffs WHERE is_default = 1 AND statut = "actif" LIMIT 1');
    return $stmt->fetch() ?: null;
}

function formatClassName(?array $classe): string
{
    if (!$classe) return '—';
    $name = $classe['nom'] ?? $classe['classe_nom'] ?? '';
    $section = $classe['section'] ?? $classe['classe_section'] ?? '';
    return trim($name . ($section ? ' ' . $section : ''));
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
