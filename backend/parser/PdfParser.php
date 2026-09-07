<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

class PdfParser
{
    /**
     * Extrait le texte d'un PDF via pdftotext (si disponible) ou lecture brute.
     */
    public static function extractText(string $filePath): string
    {
        if (function_exists('shell_exec')) {
            $escaped = escapeshellarg($filePath);
            $text = @shell_exec("pdftotext -layout $escaped - 2>/dev/null");
            if ($text !== null && trim($text) !== '') {
                return $text;
            }
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            throw new RuntimeException('Impossible de lire le fichier PDF');
        }

        // Extraction basique du texte entre parenthèses PDF
        preg_match_all('/\(([^)\\\\]*(?:\\\\.[^)\\\\]*)*)\)/s', $raw, $matches);
        $parts = [];
        foreach ($matches[1] ?? [] as $part) {
            $decoded = stripcslashes($part);
            if (strlen(trim($decoded)) > 1) {
                $parts[] = $decoded;
            }
        }
        return implode(' ', $parts);
    }

    public static function detectImportType(string $text): string
    {
        $upper = mb_strtoupper($text);
        if (str_contains($upper, 'LISTE DES INSCRIPTIONS') || str_contains($upper, 'ADMISSIONS')) {
            return 'inscriptions';
        }
        if (str_contains($upper, 'PAIEMENT') || str_contains($upper, 'FRAIS') || str_contains($upper, 'RECU')) {
            return 'paiements';
        }
        return 'inscriptions';
    }

    public static function extractClasse(string $text): ?string
    {
        if (preg_match('/Classe\s*:\s*(.+?)(?:\s*[·•|]|Statut|$)/ui', $text, $m)) {
            return trim($m[1]);
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

    /**
     * Parse les lignes d'inscription au format Super Genies.
     * Format: N° Matricule Nom Prénom Genre Date Classe Téléphone Année Statut DateInscription
     */
    public static function parseInscriptions(string $text): array
    {
        $rows = [];
        $classeDefault = self::extractClasse($text);
        $annee = self::extractAnnee($text);

        // Pattern matricule CSLSG-YYYY-YYYY-NNNNN
        $pattern = '/(\d+)\s+(CSLSG-\d{4}-\d{4}-\d{5})\s+([A-ZÀ-Ÿ\'\-]+)\s+([A-ZÀ-Ÿ\'\-\s]+?)\s+(Masculin|Féminin|Feminin)\s+(\d{2}\/\d{2}\/\d{4})\s+(.+?)\s+(\d{10}|—|\d{9,12})\s+(Année scolaire\s+\d{4}-\d{4})\s+(Actif|Brouillon|Inactif)\s+(\d{2}\/\d{2}\/\d{4})/ui';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $classe = trim($m[7]);
                $rows[] = [
                    'matricule' => strtoupper(trim($m[2])),
                    'nom' => trim($m[3]),
                    'prenom' => trim($m[4]),
                    'genre' => str_contains(mb_strtolower($m[5]), 'f') ? 'Féminin' : 'Masculin',
                    'date_naissance' => parseFrenchDate($m[6]),
                    'classe' => $classe !== '' ? $classe : ($classeDefault ?? 'Non classé'),
                    'section' => detectSection($classe ?: ($classeDefault ?? '')),
                    'telephone' => trim($m[8]) === '—' ? null : trim($m[8]),
                    'annee_scolaire' => preg_replace('/Année scolaire\s+/i', '', $m[9]),
                    'statut_inscription' => trim($m[10]),
                    'date_inscription' => parseFrenchDate($m[11]),
                ];
            }
        }

        // Fallback: recherche matricules seuls
        if (empty($rows)) {
            preg_match_all('/(CSLSG-\d{4}-\d{4}-\d{5})/', $text, $matricules);
            foreach (array_unique($matricules[1] ?? []) as $mat) {
                $rows[] = [
                    'matricule' => strtoupper($mat),
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

    /**
     * Parse fiche paiements.
     * Formats supportés:
     * - Matricule | Frais | Montant | Payé | Statut
     * - Matricule Nom Prénom | Label frais | Montant dû | Montant payé
     */
    public static function parsePaiements(string $text): array
    {
        $rows = [];
        $annee = self::extractAnnee($text);

        // Format: CSLSG-... LABEL montant montant statut
        $pattern = '/(CSLSG-\d{4}-\d{4}-\d{5})\s+(?:[^\d\n]{3,40}?)\s+(\d+(?:[.,]\d{2})?)\s+(\d+(?:[.,]\d{2})?)\s+(pay[eé]|partiel|impay[eé]|exempt)/ui';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $du = (float) str_replace(',', '.', $m[2]);
                $paye = (float) str_replace(',', '.', $m[3]);
                $statut = mb_strtolower($m[4]);
                if ($statut === 'paye' || $statut === 'payé') {
                    $statut = 'paye';
                } elseif ($statut === 'impaye' || $statut === 'impayé') {
                    $statut = 'impaye';
                }
                $rows[] = [
                    'matricule' => strtoupper(trim($m[1])),
                    'label' => 'Frais scolaires',
                    'montant_du' => $du,
                    'montant_paye' => $paye,
                    'statut' => $statut,
                    'annee_scolaire' => $annee,
                    'mois' => null,
                ];
            }
        }

        // Format alternatif ligne par ligne avec matricule + montants
        if (empty($rows)) {
            $lines = preg_split('/\R/', $text);
            foreach ($lines as $line) {
                if (!preg_match('/(CSLSG-\d{4}-\d{4}-\d{5})/', $line, $mat)) {
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
                    continue;
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

                $label = 'Frais';
                if (preg_match('/transport/i', $line)) {
                    $label = 'Transport';
                } elseif (preg_match('/inscription|connexe/i', $line)) {
                    $label = 'Frais connexe';
                } elseif (preg_match('/scolaire|mensuel/i', $line)) {
                    $label = 'Frais scolaires';
                }

                $rows[] = [
                    'matricule' => strtoupper($mat[1]),
                    'label' => $label,
                    'montant_du' => $du,
                    'montant_paye' => $paye,
                    'statut' => $statut,
                    'annee_scolaire' => $annee,
                    'mois' => null,
                ];
            }
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
                    // Créer élève minimal si paiement avant inscription
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
