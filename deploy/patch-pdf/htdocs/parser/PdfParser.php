<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

class PdfParser
{
    public static function extractText(string $filePath): string
    {
        // 1. Bibliothèque PHP (fonctionne sur InfinityFree sans pdftotext)
        if (is_file(__DIR__ . '/../vendor/autoload.php')) {
            try {
                require_once __DIR__ . '/../vendor/autoload.php';
                $parser = new Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
                if (trim($text) !== '') {
                    return self::normalizeText($text);
                }
            } catch (Throwable $e) {
                // fallback ci-dessous
            }
        }

        // 2. pdftotext si disponible sur le serveur
        if (function_exists('shell_exec')) {
            $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
            if (!in_array('shell_exec', $disabled, true)) {
                $escaped = escapeshellarg($filePath);
                $text = @shell_exec("pdftotext -layout $escaped - 2>/dev/null");
                if ($text !== null && trim($text) !== '') {
                    return self::normalizeText($text);
                }
            }
        }

        // 3. Extraction basique (PDF simples non compressés)
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

    public static function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /** Normalise CSLSG-2026-2027-00167 et variantes avec espaces */
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
            || str_contains($upper, 'IMPAY')) {
            return 'paiements';
        }
        return 'inscriptions';
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

        // Ligne par ligne : matricule + noms
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

        // Dernier recours : matricules seuls
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

    public static function parsePaiements(string $text): array
    {
        $rows = [];
        $annee = self::extractAnnee($text);

        foreach (self::extractMatricules($text) as $mat) {
            $line = '';
            foreach (preg_split('/\n/', $text) ?: [] as $ln) {
                if (str_contains($ln, $mat) || str_contains(self::normalizeMatricule($ln) ?? '', $mat)) {
                    $line = $ln;
                    break;
                }
            }
            if ($line === '') {
                continue;
            }
            preg_match_all('/(\d+(?:[.,]\d{2})?)/', $line, $amounts);
            $nums = $amounts[1] ?? [];
            if (count($nums) >= 2) {
                $du = (float) str_replace(',', '.', $nums[count($nums) - 2]);
                $paye = (float) str_replace(',', '.', $nums[count($nums) - 1]);
            } elseif (count($nums) === 1) {
                $du = (float) str_replace(',', '.', $nums[0]);
                $paye = 0;
            } else {
                $du = 0;
                $paye = 0;
            }
            $statut = 'impaye';
            if ($paye >= $du && $du > 0) {
                $statut = 'paye';
            } elseif ($paye > 0) {
                $statut = 'partiel';
            }
            if (preg_match('/pay[eé]/ui', $line)) {
                $statut = 'paye';
            }
            $label = 'Frais scolaires';
            if (preg_match('/transport/i', $line)) {
                $label = 'Transport';
            } elseif (preg_match('/inscription|connexe/i', $line)) {
                $label = 'Frais connexe';
            }
            $rows[] = [
                'matricule' => $mat,
                'label' => $label,
                'montant_du' => $du,
                'montant_paye' => $paye,
                'statut' => $statut,
                'annee_scolaire' => $annee,
                'mois' => null,
            ];
        }

        return $rows;
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

        $log = $pdo->prepare('INSERT INTO import_logs (type_import, fichier, classe_detectee, section_detectee, lignes_traitees, lignes_erreur, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $log->execute([
            'inscriptions',
            $filename,
            $classeDetectee,
            $sectionDetectee,
            $processed,
            $errors,
            json_encode(['classes' => $classes], JSON_UNESCAPED_UNICODE),
        ]);

        return [
            'processed' => $processed,
            'errors' => $errors,
            'classes' => $classes,
            'classe_detectee' => $classeDetectee,
            'section_detectee' => $sectionDetectee,
        ];
    }

    public static function importPaiements(PDO $pdo, array $rows, string $filename): array
    {
        $processed = 0;
        $errors = 0;
        $byClass = [];

        $findStudent = $pdo->prepare('SELECT id, classe FROM students WHERE matricule = ?');
        $insertFee = $pdo->prepare('
            INSERT INTO student_fees (student_id, label, montant_du, montant_paye, statut, mois, annee_scolaire)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $updateFee = $pdo->prepare('
            UPDATE student_fees SET montant_du = ?, montant_paye = ?, statut = ?, updated_at = NOW()
            WHERE student_id = ? AND label = ? AND annee_scolaire = ? AND (mois <=> ?)
        ');

        foreach ($rows as $row) {
            try {
                $findStudent->execute([$row['matricule']]);
                $student = $findStudent->fetch();
                if (!$student) {
                    $create = $pdo->prepare('INSERT INTO students (matricule, nom, prenom, classe, section, annee_scolaire) VALUES (?, ?, ?, ?, ?, ?)');
                    $create->execute([
                        $row['matricule'],
                        'Non renseigné',
                        '',
                        'Non classé',
                        'Non classé',
                        $row['annee_scolaire'],
                    ]);
                    $studentId = (int) $pdo->lastInsertId();
                    $classe = 'Non classé';
                } else {
                    $studentId = (int) $student['id'];
                    $classe = $student['classe'];
                }

                $updateFee->execute([
                    $row['montant_du'],
                    $row['montant_paye'],
                    $row['statut'],
                    $studentId,
                    $row['label'],
                    $row['annee_scolaire'],
                    $row['mois'],
                ]);

                if ($updateFee->rowCount() === 0) {
                    $insertFee->execute([
                        $studentId,
                        $row['label'],
                        $row['montant_du'],
                        $row['montant_paye'],
                        $row['statut'],
                        $row['mois'],
                        $row['annee_scolaire'],
                    ]);
                }

                $processed++;
                $byClass[$classe] = ($byClass[$classe] ?? 0) + 1;
            } catch (Throwable $e) {
                $errors++;
            }
        }

        $log = $pdo->prepare('INSERT INTO import_logs (type_import, fichier, classe_detectee, section_detectee, lignes_traitees, lignes_erreur, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $log->execute([
            'paiements',
            $filename,
            null,
            null,
            $processed,
            $errors,
            json_encode(['par_classe' => $byClass], JSON_UNESCAPED_UNICODE),
        ]);

        return [
            'processed' => $processed,
            'errors' => $errors,
            'par_classe' => $byClass,
        ];
    }
}
