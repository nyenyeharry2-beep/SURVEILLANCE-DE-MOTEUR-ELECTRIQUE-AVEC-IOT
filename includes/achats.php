<?php

declare(strict_types=1);

require_once __DIR__ . '/medicaments_unites.php';
require_once __DIR__ . '/journee.php';

function ensureAchatSchema(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $columns = [
        'unite_entree' => "ENUM('comprime', 'plaquette', 'flacon', 'unite') NOT NULL DEFAULT 'unite' AFTER medicament_id",
        'stock_ajoute' => 'INT NULL AFTER quantite',
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->exec("ALTER TABLE achat_lignes ADD COLUMN {$col} {$def}");
        } catch (Throwable $e) {
            // exists
        }
    }

    $ready = true;
}

function parseAchatLignesInput(array $body): array
{
    if (!empty($body['lignes']) && is_array($body['lignes'])) {
        $lines = [];
        foreach ($body['lignes'] as $line) {
            if (!is_array($line)) {
                continue;
            }
            $medId = (int) ($line['medicament_id'] ?? 0);
            $qty = (int) ($line['quantite'] ?? 0);
            if ($medId > 0 && $qty > 0) {
                $lines[] = [
                    'medicament_id' => $medId,
                    'quantite' => $qty,
                    'prix_unitaire' => (float) ($line['prix_unitaire'] ?? 0),
                    'unite_entree' => $line['unite_entree'] ?? $line['unite'] ?? null,
                    'date_fabrication' => $line['date_fabrication'] ?? null,
                    'date_expiration' => $line['date_expiration'] ?? null,
                ];
            }
        }
        if ($lines !== []) {
            return $lines;
        }
    }

    $medId = (int) ($body['medicament_id'] ?? 0);
    $qty = (int) ($body['quantite'] ?? 0);
    if ($medId > 0 && $qty > 0) {
        return [[
            'medicament_id' => $medId,
            'quantite' => $qty,
            'prix_unitaire' => (float) ($body['prix_unitaire'] ?? 0),
            'unite_entree' => $body['unite_entree'] ?? $body['unite'] ?? null,
            'date_fabrication' => $body['date_fabrication'] ?? null,
            'date_expiration' => $body['date_expiration'] ?? null,
        ]];
    }

    return [];
}

function uniteEntreeLabel(string $unite, int $qty = 1): string
{
    return uniteVenteLabel($unite, $qty);
}

function createAchatTransaction(PDO $db, array $user, array $body): array
{
    require_once __DIR__ . '/journal.php';
    ensureAchatSchema($db);
    ensureMedicamentUnitesSchema($db);

    $lignes = parseAchatLignesInput($body);
    if ($lignes === []) {
        throw new InvalidArgumentException('Ajoutez au moins un produit à l\'entrée de stock.');
    }

    $fournisseurId = !empty($body['fournisseur_id']) ? (int) $body['fournisseur_id'] : null;
    $dateAchat = trim($body['date_achat'] ?? '') ?: getBusinessDate();
    $notes = trim($body['notes'] ?? '');

    $prepared = [];
    $montantTotal = 0.0;

    foreach ($lignes as $idx => $line) {
        $medicamentId = (int) $line['medicament_id'];
        $quantite = (int) $line['quantite'];
        $prixUnitaire = (float) ($line['prix_unitaire'] ?? 0);
        $dateFabrication = $line['date_fabrication'] ?: null;
        $dateExpiration = $line['date_expiration'] ?: null;

        if ($dateFabrication && $dateExpiration && $dateFabrication > $dateExpiration) {
            throw new InvalidArgumentException('Ligne ' . ($idx + 1) . ' : fabrication après expiration.');
        }

        $stmt = $db->prepare('SELECT * FROM medicaments WHERE id = ? AND actif = 1');
        $stmt->execute([$medicamentId]);
        $med = $stmt->fetch();
        if (!$med) {
            throw new InvalidArgumentException('Médicament introuvable (ligne ' . ($idx + 1) . ').');
        }

        $med = enrichMedicamentRow($med);
        $uniteEntree = resolveUniteVenteForMed($med, $line['unite_entree'] ?? null);
        $stockAjoute = calcStockDeduit($med, $uniteEntree, $quantite);
        $sousTotal = $quantite * $prixUnitaire;
        $montantTotal += $sousTotal;

        $prepared[] = [
            'medicament_id' => $medicamentId,
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'unite_entree' => $uniteEntree,
            'stock_ajoute' => $stockAjoute,
            'date_fabrication' => $dateFabrication,
            'date_expiration' => $dateExpiration,
            'nom' => $med['nom'],
        ];
    }

    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO achats (fournisseur_id, utilisateur_id, date_achat, montant_total, notes) VALUES (?,?,?,?,?)')
           ->execute([$fournisseurId, $user['id'], $dateAchat, $montantTotal, $notes ?: null]);
        $achatId = (int) $db->lastInsertId();

        $insertLine = $db->prepare('
            INSERT INTO achat_lignes (achat_id, medicament_id, unite_entree, quantite, stock_ajoute, prix_unitaire, date_fabrication, date_expiration)
            VALUES (?,?,?,?,?,?,?,?)
        ');

        foreach ($prepared as $line) {
            $insertLine->execute([
                $achatId,
                $line['medicament_id'],
                $line['unite_entree'],
                $line['quantite'],
                $line['stock_ajoute'],
                $line['prix_unitaire'],
                $line['date_fabrication'],
                $line['date_expiration'],
            ]);

            $updateSql = 'UPDATE medicaments SET quantite_stock = quantite_stock + ?';
            $params = [$line['stock_ajoute']];
            if ($line['prix_unitaire'] > 0) {
                $updateSql .= ', prix_achat = ?';
                $params[] = $line['prix_unitaire'];
            }
            if ($line['date_fabrication']) {
                $updateSql .= ', date_fabrication = ?';
                $params[] = $line['date_fabrication'];
            }
            if ($line['date_expiration']) {
                $updateSql .= ', date_expiration = ?';
                $params[] = $line['date_expiration'];
            }
            $updateSql .= ' WHERE id = ?';
            $params[] = $line['medicament_id'];
            $db->prepare($updateSql)->execute($params);
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    $journal = getJournal($db, $dateAchat);
    if ($journal && empty($journal['cloture'])) {
        syncJournalDay($db, (int) $journal['id'], $dateAchat);
    }

    $details = array_map(
        static fn(array $l): string => formatLigneVenteDetail($l['nom'], $l['quantite'], $l['unite_entree']),
        $prepared
    );

    return [
        'id' => $achatId,
        'montant_total' => $montantTotal,
        'date_achat' => $dateAchat,
        'nb_lignes' => count($prepared),
        'details' => implode(', ', $details),
    ];
}

/**
 * Import entrées stock depuis Excel/CSV — crée un achat par lot ou une entrée groupée.
 *
 * @return array{imported: int, achat_id: int|null, errors: string[]}
 */
function importStockEntriesFromRows(PDO $db, array $user, array $rows, ?int $fournisseurId = null, ?string $dateAchat = null): array
{
    ensureAchatSchema($db);

    $dateAchat = $dateAchat ?: getBusinessDate();
    $lignes = [];
    $errors = [];

    foreach ($rows as $i => $row) {
        $lineNo = $i + 2;
        $code = trim((string) ($row['code'] ?? ''));
        $nom = trim((string) ($row['nom'] ?? $row['medicament'] ?? $row['produit'] ?? ''));
        $qty = (int) ($row['quantite'] ?? $row['quantite_stock'] ?? $row['stock'] ?? $row['qte'] ?? 0);

        if ($qty <= 0) {
            if ($code === '' && $nom === '') {
                continue;
            }
            $errors[] = "Ligne {$lineNo} : quantité invalide.";
            continue;
        }

        $med = null;
        if ($code !== '') {
            $stmt = $db->prepare('SELECT id FROM medicaments WHERE code = ? AND actif = 1 LIMIT 1');
            $stmt->execute([$code]);
            $medId = $stmt->fetchColumn();
            if ($medId) {
                $med = (int) $medId;
            }
        }
        if (!$med && $nom !== '') {
            $stmt = $db->prepare('SELECT id FROM medicaments WHERE nom = ? AND actif = 1 LIMIT 1');
            $stmt->execute([$nom]);
            $medId = $stmt->fetchColumn();
            if ($medId) {
                $med = (int) $medId;
            }
        }

        if (!$med) {
            $errors[] = "Ligne {$lineNo} : produit « {$nom}{$code} » introuvable — importez d'abord le catalogue.";
            continue;
        }

        $lignes[] = [
            'medicament_id' => $med,
            'quantite' => $qty,
            'prix_unitaire' => (float) ($row['prix_achat'] ?? $row['prix_unitaire'] ?? $row['prix'] ?? 0),
            'unite_entree' => $row['unite_entree'] ?? $row['unite'] ?? $row['type_unite'] ?? null,
            'date_fabrication' => normalizeImportDate($row['date_fabrication'] ?? $row['fabrication'] ?? ''),
            'date_expiration' => normalizeImportDate($row['date_expiration'] ?? $row['expiration'] ?? ''),
        ];
    }

    if ($lignes === []) {
        return ['imported' => 0, 'achat_id' => null, 'errors' => $errors ?: ['Aucune ligne valide.']];
    }

    try {
        $result = createAchatTransaction($db, $user, [
            'lignes' => $lignes,
            'fournisseur_id' => $fournisseurId,
            'date_achat' => $dateAchat,
            'notes' => 'Import Excel entrées stock',
        ]);

        return [
            'imported' => $result['nb_lignes'],
            'achat_id' => $result['id'],
            'errors' => $errors,
        ];
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();

        return ['imported' => 0, 'achat_id' => null, 'errors' => $errors];
    }
}

function retirerStockExpire(PDO $db, int $medicamentId): void
{
    $stmt = $db->prepare('SELECT id, nom, quantite_stock, date_expiration FROM medicaments WHERE id = ? AND actif = 1');
    $stmt->execute([$medicamentId]);
    $med = $stmt->fetch();
    if (!$med) {
        throw new InvalidArgumentException('Médicament introuvable.');
    }
    if (!$med['date_expiration'] || !isExpired($med['date_expiration'])) {
        throw new InvalidArgumentException('Ce produit n\'est pas expiré.');
    }

    $db->prepare('UPDATE medicaments SET quantite_stock = 0 WHERE id = ?')->execute([$medicamentId]);
}
