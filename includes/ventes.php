<?php

declare(strict_types=1);

require_once __DIR__ . '/journee.php';

function ensureVenteSchema(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $columns = [
        'date_jour' => 'DATE NULL AFTER date_vente',
        'annulee' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'annulee_at' => 'DATETIME NULL',
        'annulee_par' => 'INT NULL',
        'motif_annulation' => 'VARCHAR(255) NULL',
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->exec("ALTER TABLE ventes ADD COLUMN {$col} {$def}");
        } catch (Throwable $e) {
            // exists
        }
    }

    try {
        $db->exec('UPDATE ventes SET date_jour = DATE(date_vente) WHERE date_jour IS NULL');
    } catch (Throwable $e) {
        // ignore
    }

    $ready = true;
}

function venteActiveCondition(string $alias = 'v'): string
{
    return 'COALESCE(' . $alias . '.annulee, 0) = 0';
}

function createVenteTransaction(PDO $db, array $user, array $body): array
{
    ensureVenteSchema($db);
    $dateJour = getBusinessDate();
    assertJourneeOuverte($db, $dateJour);

    $lignes = parseVenteLignesInput($body);
    if ($lignes === []) {
        throw new InvalidArgumentException('Ajoutez au moins un produit à la facture.');
    }

    $devise = normalizeDevise($body['devise'] ?? 'CDF');
    $clientNom = trim($body['client_nom'] ?? '');
    $notes = trim($body['notes'] ?? '');
    $taux = getTauxUsdCdfForDate($db, $dateJour);

    $prepared = [];
    $montantTotal = 0.0;

    foreach ($lignes as $idx => $line) {
        $medicamentId = (int) $line['medicament_id'];
        $quantite = (int) $line['quantite'];
        $prixUnitaire = (float) ($line['prix_unitaire'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM medicaments WHERE id = ? AND actif = 1');
        $stmt->execute([$medicamentId]);
        $med = $stmt->fetch();

        if (!$med) {
            throw new InvalidArgumentException('Médicament introuvable (ligne ' . ($idx + 1) . ').');
        }

        if ((int) $med['quantite_stock'] < $quantite) {
            throw new InvalidArgumentException($med['nom'] . ' : stock insuffisant. Disponible : ' . $med['quantite_stock']);
        }

        if ($prixUnitaire <= 0) {
            $prixCatalogue = (float) $med['prix_vente'];
            $prixUnitaire = $devise === 'USD' ? $prixCatalogue / $taux : $prixCatalogue;
        }

        $sousTotal = $quantite * $prixUnitaire;
        $montantTotal += $sousTotal;

        $prepared[] = [
            'medicament_id' => $medicamentId,
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'sous_total' => $sousTotal,
            'nom' => $med['nom'],
        ];
    }

    $numero = 'VTE-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

    $db->beginTransaction();

    try {
        $db->prepare('INSERT INTO ventes (numero, utilisateur_id, client_nom, montant_total, devise, notes, date_jour) VALUES (?,?,?,?,?,?,?)')
           ->execute([$numero, $user['id'], $clientNom ?: null, $montantTotal, $devise, $notes ?: null, $dateJour]);
        $venteId = (int) $db->lastInsertId();

        $insertLine = $db->prepare('INSERT INTO vente_lignes (vente_id, medicament_id, quantite, prix_unitaire, sous_total) VALUES (?,?,?,?,?)');
        $updateStock = $db->prepare('UPDATE medicaments SET quantite_stock = quantite_stock - ? WHERE id = ?');

        foreach ($prepared as $line) {
            $insertLine->execute([
                $venteId,
                $line['medicament_id'],
                $line['quantite'],
                $line['prix_unitaire'],
                $line['sous_total'],
            ]);
            $updateStock->execute([$line['quantite'], $line['medicament_id']]);
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    $details = array_map(static fn(array $l): string => $l['nom'] . ' x' . $l['quantite'], $prepared);

    return [
        'id' => $venteId,
        'numero' => $numero,
        'montant_total' => $montantTotal,
        'devise' => $devise,
        'date_jour' => $dateJour,
        'nb_lignes' => count($prepared),
        'details' => implode(', ', $details),
        'lignes' => $prepared,
    ];
}

function cancelVenteTransaction(PDO $db, array $user, int $venteId, string $motif = ''): array
{
    ensureVenteSchema($db);
    $dateJour = getBusinessDate();

    $stmt = $db->prepare('
        SELECT v.*, u.nom AS vendeur_nom
        FROM ventes v
        JOIN utilisateurs u ON u.id = v.utilisateur_id
        WHERE v.id = ?
    ');
    $stmt->execute([$venteId]);
    $vente = $stmt->fetch();

    if (!$vente) {
        throw new InvalidArgumentException('Vente introuvable.');
    }

    if ((int) ($vente['annulee'] ?? 0) === 1) {
        throw new InvalidArgumentException('Cette vente est déjà annulée.');
    }

    $venteDateJour = $vente['date_jour'] ?: date('Y-m-d', strtotime($vente['date_vente']));
    if ($venteDateJour !== $dateJour) {
        throw new InvalidArgumentException('Annulation possible uniquement pour la journée en cours.');
    }

    $isAdmin = in_array($user['role'], ['admin', 'pharmacien'], true);
    if (!$isAdmin && (int) $vente['utilisateur_id'] !== (int) $user['id']) {
        throw new InvalidArgumentException('Vous ne pouvez annuler que vos propres ventes.');
    }

    $motif = trim($motif) ?: 'Annulation vendeur';

    $lignes = $db->prepare('SELECT medicament_id, quantite FROM vente_lignes WHERE vente_id = ?');
    $lignes->execute([$venteId]);
    $rows = $lignes->fetchAll();

    $db->beginTransaction();
    try {
        foreach ($rows as $row) {
            $db->prepare('UPDATE medicaments SET quantite_stock = quantite_stock + ? WHERE id = ?')
               ->execute([(int) $row['quantite'], (int) $row['medicament_id']]);
        }

        $db->prepare('
            UPDATE ventes SET annulee = 1, annulee_at = NOW(), annulee_par = ?, motif_annulation = ?
            WHERE id = ?
        ')->execute([(int) $user['id'], $motif, $venteId]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    return [
        'id' => $venteId,
        'numero' => $vente['numero'],
        'annulee' => true,
        'motif_annulation' => $motif,
        'annulee_par' => $user['nom'] ?? $user['email'],
        'message' => 'Vente annulée — stock restauré.',
    ];
}
