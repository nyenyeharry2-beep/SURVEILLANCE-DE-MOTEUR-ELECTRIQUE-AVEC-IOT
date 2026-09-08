<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

class PdfParser
{
    private const MOIS_FR = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    private const MOIS_KEYWORDS = [
        'janvier' => 1, 'fevrier' => 2, 'février' => 2, 'mars' => 3, 'avril' => 4,
        'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8, 'août' => 8,
        'septembre' => 9, 'sept' => 9, 'octobre' => 10, 'oct' => 10,
        'novembre' => 11, 'nov' => 11, 'decembre' => 12, 'décembre' => 12, 'dec' => 12,
    ];

    public static function extractText(string $filePath): string
    {
        if (is_file(__DIR__ . '/../vendor/autoload.php')) {
            try {
                require_once __DIR__ . '/../vendor/autoload.php';
                $parser = new Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
                if (trim($text) !== '' && self::isExtractedTextUsable($text)) {
                    return self::normalizeText($text);
                }
            } catch (Throwable $e) {
                // fallback
            }
        }

        if (function_exists('shell_exec')) {
            $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
            if (!in_array('shell_exec', $disabled, true)) {
                $escaped = escapeshellarg($filePath);
                $text = @shell_exec("pdftotext -layout $escaped - 2>/dev/null");
                if ($text !== null && trim($text) !== '' && self::isExtractedTextUsable($text)) {
                    return self::normalizeText($text);
                }
            }
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            throw new RuntimeException('Impossible de lire le fichier PDF');
        }

        preg_match_all('/\(([^)\\\\]*(?:\\\\.[^)\\\\]*)*)\)/s', $raw, $matches);
        $parts = [];
        foreach ($matches[1] ?? [] as $part) {
            $decoded = stripcslashes($part);
            if (strlen(trim($decoded)) > 1) {
                $parts[] = $decoded;
            }
        }
        $text = implode(' ', $parts);
        if (trim($text) === '') {
            throw new RuntimeException(
                'Impossible d\'extraire le texte du PDF. Exportez le PDF depuis Super Genies en format standard.'
            );
        }
        return self::normalizeText($text);
    }

    /** Rejette le bruit binaire (PDF wkhtmltopdf mal lu par Smalot). */
    private static function isExtractedTextUsable(string $text): bool
    {
        $sample = mb_substr($text, 0, 8000);
        if ($sample === '') {
            return false;
        }
        $readable = preg_match_all('/[\p{L}\p{N}\s\-\/\.:,]/u', $sample, $m);
        $len = max(1, mb_strlen($sample));
        if (($readable / $len) < 0.45) {
            return false;
        }
        return (bool) preg_match(
            '/CSLSG|PAIEMENT|INSCRIPTION|Matricule|\bNom\b|Re[cç]u|FRAIS|MINERVAL|LISTE/i',
            $sample
        );
    }

    public static function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    public static function normalizeName(string $raw): string
    {
        $s = mb_strtoupper(trim($raw));
        $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($translit !== false) {
            $s = $translit;
        }
        $s = preg_replace('/[^A-Z\s\']/u', ' ', $s) ?? $s;
        return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    }

    public static function normalizeMatricule(string $raw): ?string
    {
        $raw = strtoupper(trim($raw));
        if (preg_match('/CSLSG[\s\-]*(\d{4})[\s\-]*(\d{4})[\s\-]*(\d{4,5})/', $raw, $m)) {
            return sprintf('CSLSG-%s-%s-%05d', $m[1], $m[2], (int) $m[3]);
        }
        if (preg_match('/(CSLSG-\d{4}-\d{4}-\d{5})/', $raw, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    public static function extractMatricules(string $text): array
    {
        $found = [];
        if (preg_match_all('/CSLSG[\s\-]*\d{4}[\s\-]*\d{4}[\s\-]*\d{4,5}/i', $text, $matches)) {
            foreach ($matches[0] as $raw) {
                $norm = self::normalizeMatricule($raw);
                if ($norm !== null) {
                    $found[$norm] = true;
                }
            }
        }
        return array_keys($found);
    }

    public static function detectImportType(string $text): string
    {
        $upper = mb_strtoupper($text);
        if (str_contains($upper, 'LISTE DES INSCRIPTIONS') || str_contains($upper, 'ADMISSIONS')
            || str_contains($upper, 'MATERNELLE') || str_contains($upper, 'ANNEE MATERNELLE')) {
            return 'inscriptions';
        }
        if (str_contains($upper, 'PAIEMENT') || str_contains($upper, 'FRAIS') || str_contains($upper, 'RECU')
            || str_contains($upper, 'REÇU') || str_contains($upper, 'IMPAY') || str_contains($upper, 'MINERVAL')) {
            return 'paiements';
        }
        return 'inscriptions';
    }

    /** Catégorie du rapport PDF (entête) : connexe, minerval, transport, equipement */
    public static function detectPaymentCategory(string $text): string
    {
        $header = mb_substr(mb_strtoupper($text), 0, 1200);

        if (preg_match('/PAIEMENTS\s*SCOLAIRES/i', $header)) {
            return 'mixte';
        }
        if (preg_match('/FRAIS\s*CONNEX|FRAIS\s*D[\']?INSCRIPTION|INSCRIPTION\s*202/i', $header)) {
            return 'connexe';
        }
        if (preg_match('/TRANSPORT|FRAIS\s*DE\s*BUS|\bBUS\b/i', $header)) {
            return 'transport';
        }
        if (preg_match('/PULL|CRAVATE|KIT|COMBINAISON|TROUSSEAU|TENUE|SAC\s*SCOLAIRE|ÉQUIPEMENT|EQUIPEMENT/i', $header)) {
            return 'equipement';
        }
        if (preg_match('/MINERVAL|FRAIS\s*SCOLAIRE|MENSUALIT|MENSUEL/i', $header)) {
            return 'minerval';
        }
        return 'mixte';
    }

    public static function extractClasse(string $text): ?string
    {
        if (preg_match('/Classe\s*:\s*(.+?)(?:\s*[·•|]|Statut|$)/ui', $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/(\d+(?:È|E|ère|ERE)?\s*ANNEE\s*MATERNELLE)/ui', $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/(MATERNELLE\s*\d)/ui', $text, $m)) {
            return trim($m[0]);
        }
        return null;
    }

    public static function extractAnnee(string $text): string
    {
        if (preg_match('/(\d{4}-\d{4})/', $text, $m)) {
            return $m[1];
        }
        return getAppConfig()['academic_year'];
    }

    public static function moisLabel(?int $mois): ?string
    {
        return $mois !== null ? (self::MOIS_FR[$mois] ?? null) : null;
    }

    public static function parseInscriptions(string $text): array
    {
        $rows = [];
        $classeDefault = self::extractClasse($text);
        $annee = self::extractAnnee($text);

        $pattern = '/(\d+)\s+(CSLSG-\d{4}-\d{4}-\d{5})\s+([A-ZÀ-Ÿ\'\-]+)\s+([A-ZÀ-Ÿ\'\-\s]+?)\s+(Masculin|Féminin|Feminin)\s+(\d{2}\/\d{2}\/\d{4})\s+(.+?)\s+(\d{10}|—|\d{9,12})\s+(Année scolaire\s+\d{4}-\d{4})\s+(Actif|Brouillon|Inactif)\s+(\d{2}\/\d{2}\/\d{4})/ui';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $classe = trim($m[7]);
                $rows[] = self::rowInscription($m[2], trim($m[3]), trim($m[4]), $m[5], $m[6], $classe, $m[8], $m[9], $m[10], $m[11], $classeDefault, $annee);
            }
        }

        if (empty($rows)) {
            $lines = preg_split('/\n/', $text) ?: [];
            foreach ($lines as $line) {
                $mat = self::normalizeMatricule($line);
                if ($mat === null) {
                    continue;
                }
                $rest = trim(preg_replace('/.*?' . preg_quote($mat, '/') . '/i', '', $line) ?? $line);
                $nom = 'À compléter';
                $prenom = '';
                if (preg_match('/^([A-ZÀ-Ÿ\'\-]{2,})\s+([A-ZÀ-Ÿ\'\-\s]{2,})/u', $rest, $nm)) {
                    $nom = trim($nm[1]);
                    $prenom = trim($nm[2]);
                }
                $rows[$mat] = [
                    'matricule' => $mat,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'genre' => preg_match('/F[ée]minin/i', $line) ? 'Féminin' : (preg_match('/Masculin/i', $line) ? 'Masculin' : 'Autre'),
                    'date_naissance' => parseFrenchDate(self::firstDate($line)),
                    'classe' => $classeDefault ?? 'Non classé',
                    'section' => detectSection($classeDefault ?? ''),
                    'telephone' => self::firstPhone($line),
                    'annee_scolaire' => $annee,
                    'statut_inscription' => 'Actif',
                    'date_inscription' => null,
                ];
            }
            $rows = array_values($rows);
        }

        if (empty($rows)) {
            foreach (self::extractMatricules($text) as $mat) {
                $rows[] = [
                    'matricule' => $mat,
                    'nom' => 'À compléter',
                    'prenom' => '',
                    'genre' => 'Autre',
                    'date_naissance' => null,
                    'classe' => $classeDefault ?? 'Non classé',
                    'section' => detectSection($classeDefault ?? ''),
                    'telephone' => null,
                    'annee_scolaire' => $annee,
                    'statut_inscription' => 'Actif',
                    'date_inscription' => null,
                ];
            }
        }

        return $rows;
    }

    private static function rowInscription(
        string $matricule, string $nom, string $prenom, string $genre, string $dateNaiss,
        string $classe, string $tel, string $anneeLabel, string $statut, string $dateInscr,
        ?string $classeDefault, string $annee
    ): array {
        $classe = $classe !== '' ? $classe : ($classeDefault ?? 'Non classé');
        return [
            'matricule' => self::normalizeMatricule($matricule) ?? strtoupper($matricule),
            'nom' => $nom,
            'prenom' => $prenom,
            'genre' => str_contains(mb_strtolower($genre), 'f') ? 'Féminin' : 'Masculin',
            'date_naissance' => parseFrenchDate($dateNaiss),
            'classe' => $classe,
            'section' => detectSection($classe),
            'telephone' => trim($tel) === '—' ? null : trim($tel),
            'annee_scolaire' => preg_replace('/Année scolaire\s+/i', '', $anneeLabel) ?: $annee,
            'statut_inscription' => trim($statut),
            'date_inscription' => parseFrenchDate($dateInscr),
        ];
    }

    private static function firstDate(string $line): ?string
    {
        return preg_match('/(\d{2}\/\d{2}\/\d{4})/', $line, $m) ? $m[1] : null;
    }

    private static function firstPhone(string $line): ?string
    {
        return preg_match('/(\d{9,12})/', $line, $m) ? $m[1] : null;
    }

    /**
     * Parse fiche paiements — matricule OU nom + n° reçu.
     * Classe les frais : connexe (30$), minerval (65$+mois), bus, équipements.
     */
    public static function parsePaiements(string $text): array
    {
        if (self::isSuperGeniesFinanceReport($text)) {
            $rows = self::parseSuperGeniesFinanceReport($text);
            if ($rows !== []) {
                return $rows;
            }
        }

        $annee = self::extractAnnee($text);
        $docCategory = self::detectPaymentCategory($text);
        $docMois = self::extractMois($text);
        $rows = [];
        $seen = [];

        $lines = preg_split('/\n/', $text) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || self::isSkippedLine($line)) {
                continue;
            }

            $parsed = self::parsePaymentLine($line, $docCategory, $docMois, $annee, $text);
            if ($parsed === null) {
                continue;
            }

            $key = self::rowDedupKey($parsed);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[] = $parsed;
        }

        return $rows;
    }

    /** Rapport Super Genies Module FINANCE — PAIEMENTS SCOLAIRES avec Reçu PAY-… */
    private static function isSuperGeniesFinanceReport(string $text): bool
    {
        return str_contains(mb_strtoupper($text), 'PAIEMENTS SCOLAIRES')
            && preg_match('/PAY-\d{8}-[A-F0-9]+/i', $text);
    }

    private static function parseSuperGeniesFinanceReport(string $text): array
    {
        $annee = self::extractAnnee($text);
        $rows = [];
        $seen = [];

        $pattern = '/^(\d{1,3})\s+(\d{2}\/\d{2}\/\d{4})\s+\d{2}:\d{2}(PAY-\d{8}-[A-F0-9]+)\s+(.*?)\s+([\d.]+)\s*USD\s+([\d.]+)\s+(Comptabilis[eé]|Annul[eé]|Partiel|Impay[eé])/ui';

        $lines = preg_split('/\n/', $text) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!preg_match($pattern, $line, $m)) {
                continue;
            }

            $numeroRecu = strtoupper($m[3]);
            $datePaiement = $m[2];
            $middle = trim($m[4]);
            $du = (float) $m[5];
            $paye = (float) $m[6];
            $statutRaw = mb_strtolower($m[7]);

            $nameInfo = self::parseFinanceEleveField($middle);
            $lineCategory = self::inferCategoryFromAmount($du, $line);
            $mois = null;
            if (in_array($lineCategory, ['minerval', 'transport'], true) && preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $datePaiement, $dm)) {
                $mois = (int) $dm[2];
            }

            $statut = 'paye';
            if (str_contains($statutRaw, 'partiel')) {
                $statut = 'partiel';
            } elseif (str_contains($statutRaw, 'impay') || str_contains($statutRaw, 'annul')) {
                $statut = str_contains($statutRaw, 'annul') ? 'impaye' : 'impaye';
            }

            $label = self::buildFeeLabel($lineCategory, $line, $mois);
            $feeTypeCode = self::mapFeeTypeCode($lineCategory, $line, $du);

            $row = [
                'matricule' => null,
                'nom' => $nameInfo['nom'],
                'prenom' => $nameInfo['prenom'],
                'eleve_raw' => $nameInfo['eleve_raw'],
                'classe_ligne' => null,
                'numero_recu' => $numeroRecu,
                'date_paiement' => $datePaiement,
                'source_line' => $line,
                'label' => $label,
                'fee_category' => $lineCategory,
                'fee_type_code' => $feeTypeCode,
                'montant_du' => $du,
                'montant_paye' => $paye,
                'statut' => $statut,
                'mois' => in_array($lineCategory, ['minerval', 'transport'], true) ? $mois : null,
                'annee_scolaire' => $annee,
            ];

            $key = $numeroRecu;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[] = $row;
        }

        return $rows;
    }

    /** Colonne Élève(s) du rapport FINANCE Super Genies */
    private static function parseFinanceEleveField(string $middle): array
    {
        $middle = trim(preg_replace('/\s+/u', ' ', $middle) ?? $middle);
        $eleveRaw = $middle;

        if (preg_match('/^[—\-–]\s*(.+)$/u', $middle, $m)) {
            $eleveRaw = trim($m[1]);
        } elseif (preg_match('/\s{2,}/u', $middle)) {
            $parts = preg_split('/\s{2,}/u', $middle) ?: [];
            $eleveRaw = trim(end($parts));
        }

        $words = array_values(array_filter(explode(' ', self::normalizeName($eleveRaw))));
        $nom = $words[0] ?? '';
        $prenom = count($words) >= 2 ? $words[count($words) - 1] : '';

        return [
            'eleve_raw' => $eleveRaw,
            'nom' => $nom !== '' ? $nom : null,
            'prenom' => $prenom !== '' ? $prenom : null,
        ];
    }

    private static function isSkippedLine(string $line): bool
    {
        if (strlen($line) < 6) {
            return true;
        }
        if (preg_match('/^PAY-\d{8}-/i', $line)) {
            return false;
        }
        $u = mb_strtoupper($line);
        $skipWords = [
            'TOTAL', 'SOUS-TOTAL', 'PAGE ', 'RAPPORT', 'LISTE DES', 'MATRICULE',
            'NOM PRENOM', 'MONTANT', 'MODULE :', 'RÉSULTATS', 'RESULTATS',
            'ÉTABLISSEMENT', 'ETABLISSEMENT', 'ANNÉE SCOLAIRE', 'ANNEE SCOLAIRE',
            'LIGNES', 'ÉQUIVALENT AFFECT', 'SIGNATURE', 'PROJET ÉDUCATIF',
            'N° DATE', 'N°                 DATE', 'CAISSIER', 'PAYEUR',
        ];
        foreach ($skipWords as $w) {
            if (str_contains($u, $w)) {
                return true;
            }
        }
        if (preg_match('/^\d+\s*$/u', $line)) {
            return true;
        }
        if (preg_match('/^[\d\s.]+\s*\$/u', $line) && !preg_match('/USD/i', $line)) {
            return true;
        }
        return false;
    }

    private static function rowDedupKey(array $row): string
    {
        $parts = [
            $row['matricule'] ?? '',
            self::normalizeName(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? '')),
            $row['numero_recu'] ?? '',
            $row['label'],
            (string) ($row['mois'] ?? ''),
            (string) $row['montant_du'],
        ];
        return implode('|', $parts);
    }

    private static function parsePaymentLine(
        string $line,
        string $docCategory,
        ?int $docMois,
        string $annee,
        string $fullText
    ): ?array {
        $matricule = self::normalizeMatricule($line);
        $numeroRecu = null;
        if (preg_match('/(?:Re[cç]u|RECU|N[°o\.])\s*(\d{3,10})/ui', $line, $rm)) {
            $numeroRecu = $rm[1];
        } elseif (preg_match('/^(\d{3,8})\s+[A-ZÀ-Ÿ]/u', $line, $rm)) {
            $numeroRecu = $rm[1];
        }

        $amounts = self::extractAmounts($line);
        if ($amounts === null) {
            return null;
        }

        [$du, $paye, $statut] = $amounts;

        $nameInfo = self::extractNameFromLine($line, $matricule);
        $lineCategory = self::resolveLineCategory($line, $docCategory, $du);
        $mois = self::extractMois($line) ?? $docMois;
        if ($lineCategory === 'minerval' && $mois === null) {
            $mois = self::extractMois($fullText);
        }

        $label = self::buildFeeLabel($lineCategory, $line, $mois);
        $feeTypeCode = self::mapFeeTypeCode($lineCategory, $line, $du);

        return [
            'matricule' => $matricule,
            'nom' => $nameInfo['nom'] ?? null,
            'prenom' => $nameInfo['prenom'] ?? null,
            'classe_ligne' => $nameInfo['classe'] ?? null,
            'numero_recu' => $numeroRecu,
            'source_line' => $line,
            'label' => $label,
            'fee_category' => $lineCategory,
            'fee_type_code' => $feeTypeCode,
            'montant_du' => $du,
            'montant_paye' => $paye,
            'statut' => $statut,
            'mois' => $lineCategory === 'minerval' ? $mois : null,
            'annee_scolaire' => $annee,
        ];
    }

    /** @return array{0: float, 1: float, 2: string}|null */
    private static function extractAmounts(string $line): ?array
    {
        preg_match_all('/(\d+(?:[.,]\d{2})?)/', $line, $amounts);
        $nums = $amounts[1] ?? [];
        if ($nums === []) {
            return null;
        }

        // Ignorer le n° reçu en début de ligne
        if (preg_match('/^(\d{3,8})\s+/u', $line, $rec) && count($nums) >= 2) {
            if ($nums[0] === $rec[1]) {
                array_shift($nums);
            }
        }

        if (count($nums) >= 2) {
            $du = (float) str_replace(',', '.', $nums[count($nums) - 2]);
            $paye = (float) str_replace(',', '.', $nums[count($nums) - 1]);
        } elseif (count($nums) === 1) {
            $du = (float) str_replace(',', '.', $nums[0]);
            $paye = 0.0;
        } else {
            return null;
        }

        if ($du <= 0 && $paye <= 0) {
            return null;
        }
        if ($du <= 0 && $paye > 0) {
            $du = $paye;
        }

        $statut = 'impaye';
        if ($paye >= $du && $du > 0) {
            $statut = 'paye';
        } elseif ($paye > 0) {
            $statut = 'partiel';
        }
        if (preg_match('/pay[eé]/ui', $line)) {
            $statut = 'paye';
        } elseif (preg_match('/partiel/ui', $line)) {
            $statut = 'partiel';
        } elseif (preg_match('/impay[eé]/ui', $line)) {
            $statut = 'impaye';
        } elseif (preg_match('/exempt/ui', $line)) {
            $statut = 'exempt';
        }

        return [$du, $paye, $statut];
    }

    /** @return array{nom?: string, prenom?: string, classe?: string} */
    private static function extractNameFromLine(string $line, ?string $matricule): array
    {
        $work = $line;
        if ($matricule !== null) {
            $work = preg_replace('/' . preg_quote($matricule, '/') . '/i', ' ', $work) ?? $work;
        }
        $work = preg_replace('/(?:Re[cç]u|RECU|N[°o\.])\s*\d{3,10}/ui', ' ', $work) ?? $work;
        $work = preg_replace('/^(\d{3,8})\s+/u', ' ', $work) ?? $work;
        $work = preg_replace('/\s+\d+(?:[.,]\d{2})?(\s+\d+(?:[.,]\d{2})?)?(\s+(pay[eé]|partiel|impay[eé]|exempt))?.*$/ui', ' ', $work) ?? $work;

        $classe = null;
        if (preg_match('/(\d+[eèèrere]?\s*(?:ANNEE|MATERNELLE|PRIMAIRE|EB|GEN|TECH|SECONDE|PRIMAIRE)[^\d]{0,40})/ui', $work, $cm)) {
            $classe = trim($cm[1]);
            $work = str_replace($cm[1], ' ', $work);
        }

        $work = trim(preg_replace('/\s+/u', ' ', $work) ?? $work);
        if (preg_match('/^([A-ZÀ-Ÿ\'\-]{2,})\s+([A-ZÀ-Ÿ\'\-\s]{2,})$/u', $work, $m)) {
            return [
                'nom' => trim($m[1]),
                'prenom' => trim($m[2]),
                'classe' => $classe,
            ];
        }
        if (preg_match('/([A-ZÀ-Ÿ\'\-]{2,})\s+([A-ZÀ-Ÿ\'\-]{2,})/u', $work, $m)) {
            return [
                'nom' => trim($m[1]),
                'prenom' => trim($m[2]),
                'classe' => $classe,
            ];
        }

        return ['classe' => $classe];
    }

    private static function resolveLineCategory(string $line, string $docCategory, float $du): string
    {
        $u = mb_strtoupper($line);

        if (preg_match('/TRANSPORT|\bBUS\b/i', $u)) {
            return 'transport';
        }
        if (preg_match('/CONNEX|INSCRIPTION/i', $u)) {
            return 'connexe';
        }
        if (preg_match('/MINERVAL|MENSUEL|SCOLAIRE/i', $u)) {
            return 'minerval';
        }
        if (preg_match('/PULL|CRAVATE|KIT|COMBINAISON|TROUSSEAU|TENUE|SAC/i', $u)) {
            return 'equipement';
        }

        if ($docCategory !== 'mixte') {
            return $docCategory;
        }

        return self::inferCategoryFromAmount($du, $line);
    }

    private static function inferCategoryFromAmount(float $du, string $line): string
    {
        if (abs($du - 30.0) < 2.0 || abs($du - 50.0) < 2.0) {
            return 'connexe';
        }
        if (abs($du - 65.0) < 3.0 || abs($du - 70.0) < 3.0 || abs($du - 75.0) < 3.0
            || abs($du - 115.0) < 5.0 || abs($du - 120.0) < 5.0) {
            return 'minerval';
        }
        if (abs($du - 20.0) < 2.0 && preg_match('/\bBUS\b|TRANSPORT/i', $line)) {
            return 'transport';
        }
        if (abs($du - 20.0) < 2.0 && preg_match('/\bCONNEX/i', $line)) {
            return 'connexe';
        }
        if (abs($du - 5.0) < 1.5 || abs($du - 10.0) < 1.5 || abs($du - 12.0) < 1.5
            || abs($du - 15.0) < 1.5 || abs($du - 25.0) < 1.5 || abs($du - 40.0) < 1.5) {
            return 'equipement';
        }
        if (abs($du - 20.0) < 2.0) {
            return 'transport';
        }
        if (preg_match('/pull|cravate|kit|combinaison|tenue|sac|journal/i', $line)) {
            return 'equipement';
        }
        return 'minerval';
    }

    private static function extractMois(string $text): ?int
    {
        $lower = mb_strtolower($text);
        foreach (self::MOIS_KEYWORDS as $word => $num) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $lower)) {
                return $num;
            }
        }
        if (preg_match('/MOIS\s*[:\-]?\s*(\d{1,2})/i', $text, $m)) {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 12) {
                return $n;
            }
            // Mois scolaire 1-8 → Sept(9) à Avril(4)
            $schoolMap = [1 => 9, 2 => 10, 3 => 11, 4 => 12, 5 => 1, 6 => 2, 7 => 3, 8 => 4];
            return $schoolMap[$n] ?? null;
        }
        if (preg_match('/(\d{1,2})\/(\d{4})/', $text, $m)) {
            $n = (int) $m[1];
            return ($n >= 1 && $n <= 12) ? $n : null;
        }
        return null;
    }

    private static function buildFeeLabel(string $category, string $line, ?int $mois): string
    {
        switch ($category) {
            case 'connexe':
                return 'Frais connexe';
            case 'transport':
                return 'Frais de bus';
            case 'equipement':
                if (preg_match('/pull/i', $line)) {
                    return 'Pull-over';
                }
                if (preg_match('/cravate/i', $line)) {
                    return 'Cravate';
                }
                if (preg_match('/kit|cagoule/i', $line)) {
                    return preg_match('/cagoule/i', $line) ? 'Kit complet avec cagoule' : 'Kit complet (tenue)';
                }
                if (preg_match('/combinaison/i', $line)) {
                    return 'Combinaison';
                }
                if (preg_match('/tenue|gym/i', $line)) {
                    return 'Tenue de gymnastique';
                }
                if (preg_match('/sac/i', $line)) {
                    return 'Sac scolaire';
                }
                return 'Équipement scolaire';
            case 'minerval':
            default:
                $monthLabel = self::moisLabel($mois);
                return $monthLabel !== null ? "Minerval — $monthLabel" : 'Minerval (frais scolaires)';
        }
    }

    private static function mapFeeTypeCode(string $category, string $line, float $du): ?string
    {
        switch ($category) {
            case 'connexe':
                return abs($du - 50.0) < 2.0 ? 'FRAIS_CONNEXE_SEC_4_6' : 'FRAIS_CONNEXE_PRIMAIRE';
            case 'transport':
                if (abs($du - 25.0) < 2.0) {
                    return 'TRANSPORT_MATER';
                }
                if (abs($du - 30.0) < 2.0) {
                    return 'TRANSPORT_LOIN';
                }
                return 'TRANSPORT_PROCHE';
            case 'equipement':
                if (preg_match('/pull/i', $line)) {
                    return 'PULLOVER';
                }
                if (preg_match('/combinaison/i', $line)) {
                    return 'COMBINAISON';
                }
                if (preg_match('/tenue|gym/i', $line)) {
                    return 'TENUE_GYM';
                }
                if (preg_match('/sac/i', $line)) {
                    return 'SAC_SCOLAIRE';
                }
                if (preg_match('/cagoule/i', $line)) {
                    return 'KIT_CAGOULE';
                }
                return 'KIT_COMPLET';
            case 'minerval':
            default:
                return 'SCOLAIRE_PRIMAIRE';
        }
    }

    /** Index élèves pour rapprochement nom → matricule */
    public static function buildStudentIndex(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, matricule, nom, prenom, classe FROM students')->fetchAll();
        $byName = [];
        $byMatricule = [];
        $nameKeys = [];

        foreach ($rows as $row) {
            $byMatricule[$row['matricule']] = $row;
            $key = self::normalizeName($row['nom'] . ' ' . $row['prenom']);
            if ($key === '') {
                continue;
            }
            $byName[$key][] = $row;
            $nameKeys[] = $key;
        }

        usort($nameKeys, static fn ($a, $b) => strlen($b) <=> strlen($a));

        return [
            'by_name' => $byName,
            'by_matricule' => $byMatricule,
            'name_keys_sorted' => $nameKeys,
        ];
    }

    /** Score de correspondance nom élève PDF ↔ base inscriptions */
    public static function scoreStudentMatch(string $eleveRaw, array $student): int
    {
        $eleve = self::normalizeName($eleveRaw);
        if ($eleve === '') {
            return 0;
        }
        $nom = self::normalizeName($student['nom']);
        $prenom = self::normalizeName($student['prenom']);
        $score = 0;
        $nomMatched = $nom !== '' && str_contains($eleve, $nom);
        $prenomMatched = false;

        if ($nomMatched) {
            $score += 12;
        }
        foreach (array_filter(explode(' ', $prenom)) as $part) {
            if (strlen($part) >= 3 && str_contains($eleve, $part)) {
                $score += 10;
                $prenomMatched = true;
            }
        }
        if ($nomMatched && !$prenomMatched) {
            return 0;
        }
        if (!$nomMatched && !$prenomMatched) {
            return 0;
        }
        $full = trim($nom . ' ' . $prenom);
        if ($full !== '' && str_contains($eleve, $full)) {
            $score += 25;
        }

        return $score;
    }

    public static function resolveStudentFromRow(array $row, array $index): ?array
    {
        if (!empty($row['matricule']) && isset($index['by_matricule'][$row['matricule']])) {
            return $index['by_matricule'][$row['matricule']];
        }

        $eleveRaw = $row['eleve_raw'] ?? trim(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? ''));

        if ($eleveRaw !== '') {
            $best = null;
            $bestScore = 0;
            foreach ($index['by_matricule'] as $student) {
                $score = self::scoreStudentMatch($eleveRaw, $student);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $student;
                }
            }
            if ($best !== null && $bestScore >= 20) {
                return $best;
            }
        }

        if (!empty($row['nom'])) {
            $key = self::normalizeName(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? ''));
            if ($key !== '' && isset($index['by_name'][$key])) {
                $candidates = $index['by_name'][$key];
                if (count($candidates) === 1) {
                    return $candidates[0];
                }
                if (!empty($row['classe_ligne'])) {
                    foreach ($candidates as $c) {
                        if (self::classesMatch($c['classe'], $row['classe_ligne'])) {
                            return $c;
                        }
                    }
                }
                return $candidates[0];
            }
        }

        $searchLine = self::normalizeName($eleveRaw);
        if ($searchLine === '' && !empty($row['source_line'])) {
            $searchLine = self::normalizeName($row['source_line']);
        }

        foreach ($index['name_keys_sorted'] as $nameKey) {
            if ($nameKey !== '' && str_contains($searchLine, $nameKey)) {
                $candidates = $index['by_name'][$nameKey];
                if (count($candidates) === 1) {
                    return $candidates[0];
                }
                if (!empty($row['classe_ligne'])) {
                    foreach ($candidates as $c) {
                        if (self::classesMatch($c['classe'], $row['classe_ligne'])) {
                            return $c;
                        }
                    }
                }
                return $candidates[0];
            }
        }

        return null;
    }

    private static function classesMatch(string $a, string $b): bool
    {
        $na = self::normalizeName($a);
        $nb = self::normalizeName($b);
        if ($na === $nb) {
            return true;
        }
        return str_contains($na, $nb) || str_contains($nb, $na);
    }

    /** Applique le type d'import choisi (connexe, minerval, bus…) et calcule partiels/crédits */
    public static function applyImportContext(array $rows, array $context, ?array $studentIndex = null): array
    {
        require_once __DIR__ . '/../config/fee_catalog.php';
        $feeKindImport = $context['fee_kind'] ?? 'auto';

        foreach ($rows as &$row) {
            $paye = (float) ($row['montant_paye'] ?? 0);
            $kind = $feeKindImport;

            if ($kind === 'auto' || $kind === 'paiements') {
                $kind = match ($row['fee_category'] ?? '') {
                    'connexe' => 'connexe',
                    'minerval' => 'minerval',
                    'transport' => 'bus',
                    'equipement' => guessFeeKindFromAmount($paye > 0 ? $paye : (float) ($row['montant_du'] ?? 0), 'auto'),
                    default => guessFeeKindFromAmount($paye > 0 ? $paye : (float) ($row['montant_du'] ?? 0), 'auto'),
                };
            }

            if (!isset(getFeeCatalog()[$kind])) {
                continue;
            }

            $classe = $row['classe_ligne'] ?? null;
            if (($classe === null || $classe === '') && $studentIndex !== null) {
                $student = self::resolveStudentFromRow($row, $studentIndex);
                $classe = $student['classe'] ?? null;
            }

            $row = resolveImportFeeRow($row, $kind, $context, $classe);
        }
        unset($row);

        return $rows;
    }

    /** @deprecated use guessFeeKindFromAmount in fee_catalog.php */
    private static function guessKindFromAmount(float $amount): string
    {
        require_once __DIR__ . '/../config/fee_catalog.php';
        return guessFeeKindFromAmount($amount, 'auto');
    }
}

class ImportService
{
    public static function importInscriptions(PDO $pdo, array $rows, string $filename): array
    {
        $processed = 0;
        $errors = 0;
        $classes = [];

        $upsert = $pdo->prepare('
            INSERT INTO students (matricule, nom, prenom, genre, date_naissance, classe, section, telephone, annee_scolaire, statut_inscription, date_inscription)
            VALUES (:matricule, :nom, :prenom, :genre, :date_naissance, :classe, :section, :telephone, :annee_scolaire, :statut_inscription, :date_inscription)
            ON DUPLICATE KEY UPDATE
                nom = VALUES(nom),
                prenom = VALUES(prenom),
                genre = VALUES(genre),
                date_naissance = VALUES(date_naissance),
                classe = VALUES(classe),
                section = VALUES(section),
                telephone = VALUES(telephone),
                annee_scolaire = VALUES(annee_scolaire),
                statut_inscription = VALUES(statut_inscription),
                date_inscription = VALUES(date_inscription)
        ');

        foreach ($rows as $row) {
            try {
                $upsert->execute($row);
                $processed++;
                $classes[$row['classe']] = ($classes[$row['classe']] ?? 0) + 1;
            } catch (Throwable $e) {
                $errors++;
            }
        }

        $classeDetectee = array_key_first($classes);
        $sectionDetectee = $classeDetectee ? detectSection($classeDetectee) : null;

        self::logImport($pdo, 'inscriptions', $filename, $classeDetectee, $sectionDetectee, $processed, $errors, [
            'classes' => $classes,
            'inserted' => $processed,
            'updated' => 0,
        ]);

        return [
            'processed' => $processed,
            'errors' => $errors,
            'inserted' => $processed,
            'updated' => 0,
            'classes' => $classes,
            'classe_detectee' => $classeDetectee,
            'section_detectee' => $sectionDetectee,
        ];
    }

    public static function importPaiements(PDO $pdo, array $rows, string $filename, array $context = []): array
    {
        $processed = 0;
        $errors = 0;
        $inserted = 0;
        $updated = 0;
        $unmatched = 0;
        $byClass = [];
        $unmatchedRows = [];

        $studentIndex = PdfParser::buildStudentIndex($pdo);
        if ($rows !== [] && ($context['fee_kind'] ?? 'auto') !== 'inscriptions') {
            $rows = PdfParser::applyImportContext($rows, $context, $studentIndex);
        }
        $feeTypeIds = [];
        foreach ($pdo->query('SELECT id, code FROM fee_types')->fetchAll() as $ft) {
            $feeTypeIds[$ft['code']] = (int) $ft['id'];
        }

        $findStudent = $pdo->prepare('SELECT id, classe, matricule, nom, prenom FROM students WHERE matricule = ?');
        $insertFee = $pdo->prepare('
            INSERT INTO student_fees (student_id, fee_type_id, label, montant_du, montant_paye, statut, mois, annee_scolaire, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $updateFee = $pdo->prepare('
            UPDATE student_fees SET fee_type_id = ?, montant_du = ?, montant_paye = ?, statut = ?, notes = ?, updated_at = NOW()
            WHERE student_id = ? AND label = ? AND annee_scolaire = ? AND (mois <=> ?)
        ');
        $findExisting = $pdo->prepare('
            SELECT id FROM student_fees
            WHERE student_id = ? AND label = ? AND annee_scolaire = ? AND (mois <=> ?)
            LIMIT 1
        ');

        foreach ($rows as $row) {
            try {
                $student = PdfParser::resolveStudentFromRow($row, $studentIndex);

                if ($student === null && !empty($row['matricule'])) {
                    $findStudent->execute([$row['matricule']]);
                    $student = $findStudent->fetch() ?: null;
                }

                if ($student === null) {
                    $unmatched++;
                    $unmatchedRows[] = [
                        'nom' => trim(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? '')),
                        'recu' => $row['numero_recu'] ?? null,
                        'label' => $row['label'],
                        'montant' => $row['montant_paye'],
                    ];
                    continue;
                }

                require_once __DIR__ . '/../config/fee_catalog.php';
                $feeKind = $row['_fee_kind'] ?? ($context['fee_kind'] ?? 'auto');
                if ($feeKind === 'auto' || $feeKind === 'paiements') {
                    $feeKind = guessFeeKindFromAmount((float) ($row['montant_paye'] ?? 0), $feeKind);
                }
                if (isset(getFeeCatalog()[$feeKind])) {
                    $row = resolveImportFeeRow($row, $feeKind, $context, $student['classe']);
                }

                $studentId = (int) $student['id'];
                $classe = $student['classe'];
                $matricule = $student['matricule'];
                $feeTypeId = null;
                if (!empty($row['fee_type_code']) && isset($feeTypeIds[$row['fee_type_code']])) {
                    $feeTypeId = $feeTypeIds[$row['fee_type_code']];
                }

                $notes = $row['notes_extra'] ?? null;
                if ($notes === null && !empty($row['numero_recu'])) {
                    $notes = 'Reçu ' . $row['numero_recu'];
                }

                $findExisting->execute([
                    $studentId,
                    $row['label'],
                    $row['annee_scolaire'],
                    $row['mois'],
                ]);
                $exists = (bool) $findExisting->fetch();

                $updateFee->execute([
                    $feeTypeId,
                    $row['montant_du'],
                    $row['montant_paye'],
                    $row['statut'],
                    $notes,
                    $studentId,
                    $row['label'],
                    $row['annee_scolaire'],
                    $row['mois'],
                ]);

                if ($updateFee->rowCount() > 0) {
                    $updated++;
                } elseif (!$exists) {
                    $insertFee->execute([
                        $studentId,
                        $feeTypeId,
                        $row['label'],
                        $row['montant_du'],
                        $row['montant_paye'],
                        $row['statut'],
                        $row['mois'],
                        $row['annee_scolaire'],
                        $notes,
                    ]);
                    $inserted++;
                } else {
                    $updated++;
                }

                $processed++;
                $byClass[$classe] = ($byClass[$classe] ?? 0) + 1;

                if (empty($row['matricule'])) {
                    $studentIndex['by_matricule'][$matricule] = $student;
                }
            } catch (Throwable $e) {
                $errors++;
            }
        }

        $categories = array_unique(array_column($rows, 'fee_category'));
        $docCategory = count($categories) === 1 ? ($categories[0] ?? 'mixte') : 'mixte';

        self::logImport($pdo, 'paiements', $filename, $context['classe'] ?? null, $context['section'] ?? null, $processed, $errors, [
            'par_classe' => $byClass,
            'inserted' => $inserted,
            'updated' => $updated,
            'unmatched' => $unmatched,
            'unmatched_rows' => array_slice($unmatchedRows, 0, 20),
            'categorie_pdf' => $docCategory,
            'fee_kind' => $context['fee_kind'] ?? 'auto',
            'mois' => $context['mois'] ?? null,
        ]);

        return [
            'processed' => $processed,
            'errors' => $errors,
            'inserted' => $inserted,
            'updated' => $updated,
            'unmatched' => $unmatched,
            'unmatched_rows' => $unmatchedRows,
            'par_classe' => $byClass,
            'categorie_pdf' => $docCategory,
        ];
    }

    private static function logImport(
        PDO $pdo,
        string $type,
        string $filename,
        ?string $classe,
        ?string $section,
        int $processed,
        int $errors,
        array $details
    ): void {
        $log = $pdo->prepare('INSERT INTO import_logs (type_import, fichier, classe_detectee, section_detectee, lignes_traitees, lignes_erreur, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $log->execute([
            $type,
            $filename,
            $classe,
            $section,
            $processed,
            $errors,
            json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
