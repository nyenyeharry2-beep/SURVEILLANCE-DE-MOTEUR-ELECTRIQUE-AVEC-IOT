<?php

declare(strict_types=1);

require_once __DIR__ . '/journee.php';

function createVenteTransaction(PDO $db, array $user, array $body): array
{
    assertJourneeOuverte($db, date('Y-m-d'));

    $lignes = parseVenteLignesInput($body);
    if ($lignes === []) {
        throw new InvalidArgumentException('Ajoutez au moins un produit à la facture.');
    }

    $devise = normalizeDevise($body['devise'] ?? 'CDF');
    $clientNom = trim($body['client_nom'] ?? '');
    $notes = trim($body['notes'] ?? '');
    $taux = getTauxUsdCdfForDate($db, date('Y-m-d'));

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
        $db->prepare('INSERT INTO ventes (numero, utilisateur_id, client_nom, montant_total, devise, notes) VALUES (?,?,?,?,?,?)')
           ->execute([$numero, $user['id'], $clientNom ?: null, $montantTotal, $devise, $notes ?: null]);
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
        'nb_lignes' => count($prepared),
        'details' => implode(', ', $details),
        'lignes' => $prepared,
    ];
}
