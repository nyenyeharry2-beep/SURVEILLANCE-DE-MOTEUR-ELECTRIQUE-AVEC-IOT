<?php

declare(strict_types=1);

function ensureMedicamentUnitesSchema(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $medColumns = [
        'type_unite' => "ENUM('comprime_plaquette', 'flacon') NOT NULL DEFAULT 'comprime_plaquette' AFTER prix_vente",
        'prix_comprime' => 'DECIMAL(12, 2) NULL AFTER type_unite',
        'prix_plaquette' => 'DECIMAL(12, 2) NULL AFTER prix_comprime',
        'prix_flacon' => 'DECIMAL(12, 2) NULL AFTER prix_plaquette',
        'comprimes_par_plaquette' => 'INT NOT NULL DEFAULT 10 AFTER prix_flacon',
    ];

    foreach ($medColumns as $col => $def) {
        try {
            $db->exec("ALTER TABLE medicaments ADD COLUMN {$col} {$def}");
        } catch (Throwable $e) {
            // exists
        }
    }

    $lineColumns = [
        'unite_vente' => "ENUM('comprime', 'plaquette', 'flacon', 'unite') NOT NULL DEFAULT 'unite' AFTER medicament_id",
        'stock_deduit' => 'INT NULL AFTER quantite',
    ];

    foreach ($lineColumns as $col => $def) {
        try {
            $db->exec("ALTER TABLE vente_lignes ADD COLUMN {$col} {$def}");
        } catch (Throwable $e) {
            // exists
        }
    }

    $achatColumns = [
        'unite_entree' => "ENUM('comprime', 'plaquette', 'flacon', 'unite') NOT NULL DEFAULT 'unite' AFTER medicament_id",
        'stock_ajoute' => 'INT NULL AFTER quantite',
    ];

    foreach ($achatColumns as $col => $def) {
        try {
            $db->exec("ALTER TABLE achat_lignes ADD COLUMN {$col} {$def}");
        } catch (Throwable $e) {
            // exists
        }
    }

    $ready = true;
}

function normalizeTypeUnite(?string $type): string
{
    $type = strtolower(trim((string) $type));
    if (in_array($type, ['flacon', 'flacons', 'liquide', 'sirop', 'solution'], true)) {
        return 'flacon';
    }

    return 'comprime_plaquette';
}

function normalizeUniteVente(?string $unite): string
{
    $unite = strtolower(trim((string) $unite));
    if (in_array($unite, ['comprime', 'comprimé', 'comprimes', 'comprimés', 'cp'], true)) {
        return 'comprime';
    }
    if (in_array($unite, ['plaquette', 'plaquettes', 'plt'], true)) {
        return 'plaquette';
    }
    if (in_array($unite, ['flacon', 'flacons', 'fl'], true)) {
        return 'flacon';
    }

    return 'unite';
}

function uniteVenteLabel(string $unite, int $qty = 1): string
{
    $labels = [
        'comprime' => ['comprimé', 'comprimés'],
        'plaquette' => ['plaquette', 'plaquettes'],
        'flacon' => ['flacon', 'flacons'],
        'unite' => ['unité', 'unités'],
    ];
    $pair = $labels[normalizeUniteVente($unite)] ?? $labels['unite'];

    return $qty > 1 ? $pair[1] : $pair[0];
}

function getComprimesParPlaquette(array $med): int
{
    $cpp = (int) ($med['comprimes_par_plaquette'] ?? 10);

    return max(1, $cpp);
}

function getPrixComprime(array $med): float
{
    $prix = (float) ($med['prix_comprime'] ?? 0);
    if ($prix > 0) {
        return $prix;
    }

    return (float) ($med['prix_vente'] ?? 0);
}

function getPrixPlaquette(array $med): float
{
    $prix = (float) ($med['prix_plaquette'] ?? 0);
    if ($prix > 0) {
        return $prix;
    }

    return getPrixComprime($med) * getComprimesParPlaquette($med);
}

function getPrixFlacon(array $med): float
{
    $prix = (float) ($med['prix_flacon'] ?? 0);
    if ($prix > 0) {
        return $prix;
    }

    return (float) ($med['prix_vente'] ?? 0);
}

function getUnitesVenteDisponibles(array $med): array
{
    if (normalizeTypeUnite($med['type_unite'] ?? '') === 'flacon') {
        return ['flacon'];
    }

    return ['comprime', 'plaquette'];
}

function getPrixUnitaireVente(array $med, string $uniteVente): float
{
    $unite = normalizeUniteVente($uniteVente);
    if ($unite === 'plaquette') {
        return getPrixPlaquette($med);
    }
    if ($unite === 'flacon') {
        return getPrixFlacon($med);
    }
    if ($unite === 'comprime') {
        return getPrixComprime($med);
    }

    if (normalizeTypeUnite($med['type_unite'] ?? '') === 'flacon') {
        return getPrixFlacon($med);
    }

    return getPrixComprime($med);
}

function calcStockDeduit(array $med, string $uniteVente, int $quantite): int
{
    if ($quantite <= 0) {
        return 0;
    }

    $unite = normalizeUniteVente($uniteVente);
    if ($unite === 'plaquette') {
        return $quantite * getComprimesParPlaquette($med);
    }

    return $quantite;
}

function getStockMaxVente(array $med, string $uniteVente): int
{
    $stock = (int) ($med['quantite_stock'] ?? 0);
    $unite = normalizeUniteVente($uniteVente);

    if ($unite === 'plaquette') {
        return intdiv($stock, getComprimesParPlaquette($med));
    }

    return $stock;
}

function resolveUniteVenteForMed(array $med, ?string $requested): string
{
    $allowed = getUnitesVenteDisponibles($med);
    $unite = normalizeUniteVente($requested ?? '');

    if ($unite === 'unite') {
        return $allowed[0];
    }

    if (!in_array($unite, $allowed, true)) {
        throw new InvalidArgumentException(
            $med['nom'] . ' : unité « ' . $unite . ' » non disponible. Utilisez : ' . implode(', ', $allowed) . '.'
        );
    }

    return $unite;
}

function formatStockLabel(array $med): string
{
    $stock = (int) ($med['quantite_stock'] ?? 0);
    if (normalizeTypeUnite($med['type_unite'] ?? '') === 'flacon') {
        return $stock . ' flacon' . ($stock > 1 ? 's' : '');
    }

    $cpp = getComprimesParPlaquette($med);
    $plaquettes = intdiv($stock, $cpp);

    return $stock . ' cp (' . $plaquettes . ' plt)';
}

function enrichMedicamentRow(array $med): array
{
    $type = normalizeTypeUnite($med['type_unite'] ?? 'comprime_plaquette');
    $unites = getUnitesVenteDisponibles($med);
    $prixComprime = getPrixComprime($med);
    $prixPlaquette = getPrixPlaquette($med);
    $prixFlacon = getPrixFlacon($med);
    $cpp = getComprimesParPlaquette($med);

    $prixVente = $type === 'flacon' ? $prixFlacon : $prixComprime;

    return array_merge($med, [
        'type_unite' => $type,
        'comprimes_par_plaquette' => $cpp,
        'prix_comprime' => $prixComprime,
        'prix_plaquette' => $prixPlaquette,
        'prix_flacon' => $prixFlacon,
        'prix_vente' => $prixVente,
        'unites_vente' => $unites,
        'stock_label' => formatStockLabel($med),
        'stock_max' => [
            'comprime' => getStockMaxVente($med, 'comprime'),
            'plaquette' => getStockMaxVente($med, 'plaquette'),
            'flacon' => getStockMaxVente($med, 'flacon'),
        ],
    ]);
}

function formatLigneVenteDetail(string $nom, int $quantite, string $uniteVente): string
{
    $unite = normalizeUniteVente($uniteVente);
    if ($unite === 'unite') {
        return $nom . ' x' . $quantite;
    }

    return $nom . ' x' . $quantite . ' ' . uniteVenteLabel($unite, $quantite);
}

/**
 * Importe des médicaments depuis un tableau associatif (ligne CSV/Excel).
 *
 * @param array{mode?: string} $options mode: replace|add_stock
 * @return array{imported: int, updated: int, errors: string[]}
 */
function importMedicamentsFromRows(PDO $db, array $rows, array $options = []): array
{
    ensureMedicamentUnitesSchema($db);

    $mode = ($options['mode'] ?? 'replace') === 'add_stock' ? 'add_stock' : 'replace';
    $imported = 0;
    $updated = 0;
    $errors = [];

    $findCat = $db->prepare('SELECT id FROM categories WHERE nom = ? LIMIT 1');
    $insertCat = $db->prepare('INSERT INTO categories (nom) VALUES (?)');
    $findMedByCode = $db->prepare('SELECT id, quantite_stock, prix_achat FROM medicaments WHERE code = ? LIMIT 1');
    $findMedByNom = $db->prepare('SELECT id, quantite_stock, prix_achat FROM medicaments WHERE nom = ? AND actif = 1 LIMIT 1');
    $insertMed = $db->prepare('
        INSERT INTO medicaments (
            code, nom, categorie_id, prix_achat, prix_vente, type_unite,
            prix_comprime, prix_plaquette, prix_flacon, comprimes_par_plaquette,
            quantite_stock, seuil_alerte, date_expiration, description, actif
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)
    ');
    $updateMed = $db->prepare('
        UPDATE medicaments SET
            nom = ?, categorie_id = ?, prix_achat = ?, prix_vente = ?, type_unite = ?,
            prix_comprime = ?, prix_plaquette = ?, prix_flacon = ?, comprimes_par_plaquette = ?,
            quantite_stock = ?, seuil_alerte = ?, date_expiration = ?, description = ?, actif = 1
        WHERE id = ?
    ');

    foreach ($rows as $i => $row) {
        $lineNo = $i + 2;
        $code = trim((string) ($row['code'] ?? ''));
        $nom = trim((string) ($row['nom'] ?? $row['medicament'] ?? $row['produit'] ?? ''));

        if ($code === '' && $nom === '') {
            continue;
        }
        if ($nom === '') {
            $errors[] = "Ligne {$lineNo} : nom du médicament manquant.";
            continue;
        }
        if ($code === '') {
            $code = 'IMP-' . strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $nom), 0, 8) ?: 'MED', 0, 8)) . '-' . ($i + 1);
        }

        $type = normalizeTypeUnite($row['type_unite'] ?? $row['type'] ?? $row['forme'] ?? 'comprime_plaquette');
        $cpp = max(1, (int) ($row['comprimes_par_plaquette'] ?? $row['cp_par_plaquette'] ?? 10));
        $prixComprime = (float) ($row['prix_comprime'] ?? $row['prix cp'] ?? 0);
        $prixPlaquette = (float) ($row['prix_plaquette'] ?? $row['prix plt'] ?? 0);
        $prixFlacon = (float) ($row['prix_flacon'] ?? 0);
        $prixVente = (float) ($row['prix_vente'] ?? $row['prix'] ?? 0);
        $prixAchat = (float) ($row['prix_achat'] ?? $row['prix_achat_unitaire'] ?? 0);
        $stock = (int) ($row['quantite_stock'] ?? $row['stock'] ?? $row['qte'] ?? 0);
        $stockAjout = (int) ($row['stock_a_ajouter'] ?? $row['ajout_stock'] ?? 0);
        $seuil = (int) ($row['seuil_alerte'] ?? 10);
        $expiration = normalizeImportDate($row['date_expiration'] ?? $row['expiration'] ?? '');
        $description = trim((string) ($row['description'] ?? ''));

        $existingId = null;
        $existingStock = 0;
        $existingPrixAchat = 0.0;
        if ($code !== '') {
            $findMedByCode->execute([$code]);
            $found = $findMedByCode->fetch();
            if ($found) {
                $existingId = (int) $found['id'];
                $existingStock = (int) $found['quantite_stock'];
                $existingPrixAchat = (float) $found['prix_achat'];
            }
        }
        if (!$existingId && $nom !== '') {
            $findMedByNom->execute([$nom]);
            $found = $findMedByNom->fetch();
            if ($found) {
                $existingId = (int) $found['id'];
                $existingStock = (int) $found['quantite_stock'];
                $existingPrixAchat = (float) $found['prix_achat'];
            }
        }

        $skipPriceCheck = $existingId && $mode === 'add_stock' && ($stockAjout > 0 || $stock > 0);

        if ($type === 'flacon') {
            if ($prixFlacon <= 0 && $prixVente > 0) {
                $prixFlacon = $prixVente;
            }
            if ($prixFlacon <= 0 && !$skipPriceCheck) {
                $errors[] = "Ligne {$lineNo} ({$nom}) : prix flacon requis.";
                continue;
            }
            if ($prixFlacon > 0) {
                $prixVente = $prixFlacon;
            }
        } else {
            if ($prixComprime <= 0 && $prixVente > 0) {
                $prixComprime = $prixVente;
            }
            if ($prixPlaquette <= 0 && $prixComprime > 0) {
                $prixPlaquette = $prixComprime * $cpp;
            }
            if ($prixComprime <= 0 && !$skipPriceCheck) {
                $errors[] = "Ligne {$lineNo} ({$nom}) : prix comprimé requis.";
                continue;
            }
            if ($prixComprime > 0) {
                $prixVente = $prixComprime;
            }
        }

        $catId = null;
        $catNom = trim((string) ($row['categorie'] ?? $row['category'] ?? ''));
        if ($catNom !== '') {
            $findCat->execute([$catNom]);
            $catId = $findCat->fetchColumn();
            if (!$catId) {
                $insertCat->execute([$catNom]);
                $catId = (int) $db->lastInsertId();
            } else {
                $catId = (int) $catId;
            }
        }

        try {
            $finalStock = $stock;
            if ($existingId && $mode === 'add_stock') {
                $add = $stockAjout > 0 ? $stockAjout : $stock;
                $finalStock = $existingStock + $add;
            }

            $finalPrixAchat = $prixAchat > 0 ? $prixAchat : ($existingId ? $existingPrixAchat : 0);

            if ($existingId) {
                if ($mode === 'add_stock' && $skipPriceCheck) {
                    $db->prepare('
                        UPDATE medicaments SET quantite_stock = ?, seuil_alerte = ?,
                        date_expiration = COALESCE(?, date_expiration),
                        prix_achat = CASE WHEN ? > 0 THEN ? ELSE prix_achat END,
                        actif = 1
                        WHERE id = ?
                    ')->execute([$finalStock, $seuil, $expiration, $prixAchat, $prixAchat, $existingId]);
                } else {
                    $updateMed->execute([
                        $nom, $catId, $finalPrixAchat, $prixVente, $type,
                        $type === 'flacon' ? null : $prixComprime,
                        $type === 'flacon' ? null : $prixPlaquette,
                        $type === 'flacon' ? $prixFlacon : null,
                        $cpp, $finalStock, $seuil, $expiration, $description,
                        $existingId,
                    ]);
                }
                $updated++;
            } else {
                $insertMed->execute([
                    $code, $nom, $catId, $prixAchat, $prixVente, $type,
                    $type === 'flacon' ? null : $prixComprime,
                    $type === 'flacon' ? null : $prixPlaquette,
                    $type === 'flacon' ? $prixFlacon : null,
                    $cpp, $stock, $seuil, $expiration, $description ?: null,
                ]);
                $imported++;
            }
        } catch (Throwable $e) {
            $errors[] = "Ligne {$lineNo} ({$code}) : " . $e->getMessage();
        }
    }

    return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
}

/**
 * Parse CSV/Excel export (UTF-8, séparateur ; ou ,).
 *
 * @return array<int, array<string, string>>
 */
function parseImportSpreadsheet(string $content): array
{
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    $lines = preg_split('/\r\n|\r|\n/', trim($content));
    if ($lines === false || count($lines) < 2) {
        return [];
    }

    $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
    $headers = str_getcsv($lines[0], $delimiter);
    $headers = array_map(static function (string $h): string {
        $h = strtolower(trim($h));
        $h = str_replace(['é', 'è', 'ê'], 'e', $h);
        $h = preg_replace('/\s+/', '_', $h) ?? $h;

        return $h;
    }, $headers);

    $rows = [];
    for ($i = 1, $c = count($lines); $i < $c; $i++) {
        if (trim($lines[$i]) === '') {
            continue;
        }
        $cells = str_getcsv($lines[$i], $delimiter);
        $row = [];
        foreach ($headers as $idx => $key) {
            if ($key === '') {
                continue;
            }
            $row[$key] = trim((string) ($cells[$idx] ?? ''));
        }
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Lecture basique d'un fichier XLSX (première feuille, sans dépendance externe).
 *
 * @return array<int, array<string, string>>
 */
function parseSimpleXlsx(string $filePath): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Extension ZipArchive requise pour lire les fichiers .xlsx.');
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('Fichier Excel invalide.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = @simplexml_load_string($sharedXml);
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        return [];
    }

    $sheet = @simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData->row)) {
        return [];
    }

    $grid = [];
    foreach ($sheet->sheetData->row as $row) {
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                continue;
            }
            $col = columnIndexFromLetters($m[1]);
            $rowNum = (int) $m[2];
            $type = (string) ($cell['t'] ?? '');
            $value = '';
            if ($type === 's') {
                $idx = (int) ($cell->v ?? 0);
                $value = $sharedStrings[$idx] ?? '';
            } elseif (isset($cell->v)) {
                $value = (string) $cell->v;
            } elseif (isset($cell->is->t)) {
                $value = (string) $cell->is->t;
            }
            $grid[$rowNum][$col] = trim($value);
        }
    }

    if ($grid === []) {
        return [];
    }

    ksort($grid);
    $firstRowNum = array_key_first($grid);
    $headerRow = $grid[$firstRowNum] ?? [];
    ksort($headerRow);
    $headers = array_map(static function (string $h): string {
        $h = strtolower(trim($h));
        $h = str_replace(['é', 'è', 'ê'], 'e', $h);
        $h = preg_replace('/\s+/', '_', $h) ?? $h;

        return $h;
    }, array_values($headerRow));

    $rows = [];
    foreach ($grid as $rowNum => $cells) {
        if ($rowNum === $firstRowNum) {
            continue;
        }
        ksort($cells);
        $values = array_values($cells);
        $row = [];
        $hasData = false;
        foreach ($headers as $idx => $key) {
            if ($key === '') {
                continue;
            }
            $val = trim((string) ($values[$idx] ?? ''));
            if ($val !== '') {
                $hasData = true;
            }
            $row[$key] = $val;
        }
        if ($hasData) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function columnIndexFromLetters(string $letters): int
{
    $letters = strtoupper($letters);
    $index = 0;
    $len = strlen($letters);
    for ($i = 0; $i < $len; $i++) {
        $index = $index * 26 + (ord($letters[$i]) - 64);
    }

    return $index - 1;
}

/** Convertit date Excel (numéro série) ou texte en AAAA-MM-JJ */
function normalizeImportDate(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $value = trim($value);

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $value, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }

    if (is_numeric($value)) {
        $serial = (float) $value;
        if ($serial > 30000 && $serial < 60000) {
            $unix = (int) round(($serial - 25569) * 86400);

            return gmdate('Y-m-d', $unix);
        }
    }

    $ts = strtotime($value);

    return $ts ? date('Y-m-d', $ts) : null;
}
