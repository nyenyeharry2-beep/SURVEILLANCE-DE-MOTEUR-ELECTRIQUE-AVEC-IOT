<?php

function getJournal(PDO $db, string $date): ?array
{
    $stmt = $db->prepare('SELECT * FROM journaux_quotidiens WHERE date_jour = ?');
    $stmt->execute([$date]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getPreviousClosedJournal(PDO $db, string $date): ?array
{
    $stmt = $db->prepare('
        SELECT * FROM journaux_quotidiens
        WHERE date_jour < ? AND cloture = 1
        ORDER BY date_jour DESC
        LIMIT 1
    ');
    $stmt->execute([$date]);
    return $stmt->fetch() ?: null;
}

function getEntreesProduitJour(PDO $db, string $date): array
{
    static $hasStockAjoute = null;
    if ($hasStockAjoute === null) {
        try {
            $db->query('SELECT stock_ajoute FROM achat_lignes LIMIT 1');
            $hasStockAjoute = true;
        } catch (Throwable $e) {
            $hasStockAjoute = false;
        }
    }
    $qteExpr = $hasStockAjoute
        ? 'COALESCE(SUM(COALESCE(al.stock_ajoute, al.quantite)), 0)'
        : 'COALESCE(SUM(al.quantite), 0)';

    $stmt = $db->prepare("
        SELECT al.medicament_id, {$qteExpr} AS qte,
               COALESCE(SUM(al.quantite * al.prix_unitaire), 0) AS montant_cdf
        FROM achat_lignes al
        JOIN achats a ON a.id = al.achat_id
        WHERE a.date_achat = ?
        GROUP BY al.medicament_id
    ");
    $stmt->execute([$date]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[(int) $r['medicament_id']] = $r;
    }
    return $map;
}

function getSortiesProduitJour(PDO $db, string $date): array
{
    static $hasStockDeduit = null;
    if ($hasStockDeduit === null) {
        try {
            $db->query('SELECT stock_deduit FROM vente_lignes LIMIT 1');
            $hasStockDeduit = true;
        } catch (Throwable $e) {
            $hasStockDeduit = false;
        }
    }
    static $hasAnnulee = null;
    if ($hasAnnulee === null) {
        try {
            $db->query('SELECT annulee FROM ventes LIMIT 1');
            $hasAnnulee = true;
        } catch (Throwable $e) {
            $hasAnnulee = false;
        }
    }

    $qteExpr = $hasStockDeduit
        ? 'COALESCE(SUM(COALESCE(vl.stock_deduit, vl.quantite)), 0)'
        : 'COALESCE(SUM(vl.quantite), 0)';
    $annuleeFilter = $hasAnnulee ? 'AND COALESCE(v.annulee, 0) = 0' : '';

    $taux = getTauxUsdCdf();
    $stmt = $db->prepare("
        SELECT vl.medicament_id, {$qteExpr} AS qte,
               COALESCE(SUM(
                   CASE WHEN COALESCE(v.devise, \"CDF\") = \"CDF\" THEN vl.sous_total
                        ELSE vl.sous_total * ?
                   END
               ), 0) AS montant_cdf,
               COALESCE(SUM(
                   CASE WHEN COALESCE(v.devise, \"USD\") = \"USD\" THEN vl.sous_total
                        ELSE vl.sous_total / ?
                   END
               ), 0) AS montant_usd
        FROM vente_lignes vl
        JOIN ventes v ON v.id = vl.vente_id
        WHERE COALESCE(v.date_jour, DATE(v.date_vente)) = ?
          {$annuleeFilter}
        GROUP BY vl.medicament_id
    ");
    $stmt->execute([$taux, $taux, $date]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[(int) $r['medicament_id']] = $r;
    }
    return $map;
}

function getTotauxArgentJour(PDO $db, string $date): array
{
    $entrees = $db->prepare('SELECT COALESCE(SUM(montant_total), 0) FROM achats WHERE date_achat = ?');
    $entrees->execute([$date]);
    $entreesCdf = (float) $entrees->fetchColumn();

    $ventes = sommeVentesDual($db, 'COALESCE(date_jour, DATE(date_vente)) = ?', [$date]);

    return [
        'entrees_cdf' => $entreesCdf,
        'entrees_usd' => convertirDevise($entreesCdf, 'CDF', 'USD'),
        'sorties_cdf' => (float) $ventes['total_cdf'],
        'sorties_usd' => (float) $ventes['total_usd'],
    ];
}

function openJournalDay(PDO $db, string $date): int
{
    $existing = getJournal($db, $date);
    if ($existing) {
        return (int) $existing['id'];
    }

    $prev = getPreviousClosedJournal($db, $date);
    $prevLines = [];

    if ($prev) {
        $stmt = $db->prepare('SELECT * FROM journal_produits WHERE journal_id = ?');
        $stmt->execute([$prev['id']]);
        foreach ($stmt->fetchAll() as $line) {
            $prevLines[(int) $line['medicament_id']] = (int) $line['stock_final'];
        }
    }

    $medicaments = $db->query('SELECT id, quantite_stock, prix_achat, prix_vente FROM medicaments WHERE actif = 1')->fetchAll();
    $entreesMap = getEntreesProduitJour($db, $date);
    $sortiesMap = getSortiesProduitJour($db, $date);
    $argent = getTotauxArgentJour($db, $date);

    $stockInitialCdf = 0.0;
    $stockFinalCdf = 0.0;

    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO journaux_quotidiens (date_jour) VALUES (?)')->execute([$date]);
        $journalId = (int) $db->lastInsertId();

        $insertLine = $db->prepare('
            INSERT INTO journal_produits
            (journal_id, medicament_id, stock_initial, entrees, sorties, stock_final,
             valeur_initial_cdf, valeur_entrees_cdf, valeur_sorties_cdf, valeur_final_cdf)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ');

        foreach ($medicaments as $med) {
            $medId = (int) $med['id'];
            $stockInitial = $prevLines[$medId] ?? (int) $med['quantite_stock'];
            $entrees = (int) ($entreesMap[$medId]['qte'] ?? 0);
            $sorties = (int) ($sortiesMap[$medId]['qte'] ?? 0);
            $stockFinal = $stockInitial + $entrees - $sorties;

            $prixAchat = (float) $med['prix_achat'];
            $prixVente = (float) $med['prix_vente'];

            $valInitial = $stockInitial * $prixAchat;
            $valEntrees = $entrees * $prixAchat;
            $valSorties = $sorties * $prixVente;
            $valFinal = $stockFinal * $prixAchat;

            $insertLine->execute([
                $journalId, $medId, $stockInitial, $entrees, $sorties, $stockFinal,
                $valInitial, $valEntrees, $valSorties, $valFinal,
            ]);

            $stockInitialCdf += $valInitial;
            $stockFinalCdf += $valFinal;
        }

        $db->prepare('
            UPDATE journaux_quotidiens SET
                stock_initial_cdf = ?, stock_initial_usd = ?,
                entrees_cdf = ?, entrees_usd = ?,
                sorties_cdf = ?, sorties_usd = ?,
                stock_final_cdf = ?, stock_final_usd = ?
            WHERE id = ?
        ')->execute([
            $stockInitialCdf,
            convertirDevise($stockInitialCdf, 'CDF', 'USD'),
            $argent['entrees_cdf'],
            $argent['entrees_usd'],
            $argent['sorties_cdf'],
            $argent['sorties_usd'],
            $stockFinalCdf,
            convertirDevise($stockFinalCdf, 'CDF', 'USD'),
            $journalId,
        ]);

        $db->commit();
        return $journalId;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function syncJournalDay(PDO $db, int $journalId, string $date): void
{
    $journal = $db->prepare('SELECT * FROM journaux_quotidiens WHERE id = ?');
    $journal->execute([$journalId]);
    $j = $journal->fetch();
    if (!$j || $j['cloture']) {
        return;
    }

    $entreesMap = getEntreesProduitJour($db, $date);
    $sortiesMap = getSortiesProduitJour($db, $date);
    $argent = getTotauxArgentJour($db, $date);

    $lines = $db->prepare('
        SELECT jp.*, m.prix_achat, m.prix_vente
        FROM journal_produits jp
        JOIN medicaments m ON m.id = jp.medicament_id
        WHERE jp.journal_id = ?
    ');
    $lines->execute([$journalId]);
    $allLines = $lines->fetchAll();
    $existingMedIds = array_column($allLines, 'medicament_id');

    $medicaments = $db->query('SELECT id, prix_achat, prix_vente FROM medicaments WHERE actif = 1')->fetchAll();
    $insertLine = $db->prepare('
        INSERT INTO journal_produits
        (journal_id, medicament_id, stock_initial, entrees, sorties, stock_final,
         valeur_initial_cdf, valeur_entrees_cdf, valeur_sorties_cdf, valeur_final_cdf)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ');

    foreach ($medicaments as $med) {
        if (!in_array((int) $med['id'], array_map('intval', $existingMedIds), true)) {
            $medId = (int) $med['id'];
            $entrees = (int) ($entreesMap[$medId]['qte'] ?? 0);
            $sorties = (int) ($sortiesMap[$medId]['qte'] ?? 0);
            $stockInitial = 0;
            $stockFinal = $stockInitial + $entrees - $sorties;
            $prixAchat = (float) $med['prix_achat'];
            $prixVente = (float) $med['prix_vente'];
            $insertLine->execute([
                $journalId, $medId, $stockInitial, $entrees, $sorties, $stockFinal,
                0, $entrees * $prixAchat, $sorties * $prixVente, $stockFinal * $prixAchat,
            ]);
        }
    }

    $lines->execute([$journalId]);
    $allLines = $lines->fetchAll();

    $stockInitialCdf = 0.0;
    $stockFinalCdf = 0.0;

    $db->beginTransaction();
    try {
        $update = $db->prepare('
            UPDATE journal_produits SET
                entrees = ?, sorties = ?, stock_final = ?,
                valeur_entrees_cdf = ?, valeur_sorties_cdf = ?, valeur_final_cdf = ?
            WHERE id = ?
        ');

        foreach ($allLines as $line) {
            $medId = (int) $line['medicament_id'];
            $stockInitial = (int) $line['stock_initial'];
            $entrees = (int) ($entreesMap[$medId]['qte'] ?? 0);
            $sorties = (int) ($sortiesMap[$medId]['qte'] ?? 0);

            $stockFinal = $line['stock_final_manuel'] !== null
                ? (int) $line['stock_final_manuel']
                : $stockInitial + $entrees - $sorties;

            $prixAchat = (float) $line['prix_achat'];
            $prixVente = (float) $line['prix_vente'];

            $valInitial = $stockInitial * $prixAchat;
            $valEntrees = $entrees * $prixAchat;
            $valSorties = $sorties * $prixVente;
            $valFinal = $stockFinal * $prixAchat;

            $update->execute([
                $entrees, $sorties, $stockFinal,
                $valEntrees, $valSorties, $valFinal,
                $line['id'],
            ]);

            $stockInitialCdf += $valInitial;
            $stockFinalCdf += $valFinal;
        }

        $db->prepare('
            UPDATE journaux_quotidiens SET
                stock_initial_cdf = ?, stock_initial_usd = ?,
                entrees_cdf = ?, entrees_usd = ?,
                sorties_cdf = ?, sorties_usd = ?,
                stock_final_cdf = ?, stock_final_usd = ?
            WHERE id = ?
        ')->execute([
            $stockInitialCdf,
            convertirDevise($stockInitialCdf, 'CDF', 'USD'),
            $argent['entrees_cdf'],
            $argent['entrees_usd'],
            $argent['sorties_cdf'],
            $argent['sorties_usd'],
            $stockFinalCdf,
            convertirDevise($stockFinalCdf, 'CDF', 'USD'),
            $journalId,
        ]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function closeJournalDay(PDO $db, int $journalId, int $userId): void
{
    syncJournalDay($db, $journalId, getJournalDate($db, $journalId));

    $db->prepare('
        UPDATE journaux_quotidiens
        SET cloture = 1, utilisateur_id = ?, cloture_at = NOW()
        WHERE id = ? AND cloture = 0
    ')->execute([$userId, $journalId]);
}

function getJournalDate(PDO $db, int $journalId): string
{
    $stmt = $db->prepare('SELECT date_jour FROM journaux_quotidiens WHERE id = ?');
    $stmt->execute([$journalId]);
    return $stmt->fetchColumn();
}

function getJournalLines(PDO $db, int $journalId): array
{
    $stmt = $db->prepare('
        SELECT jp.*, m.code, m.nom, m.prix_achat, m.prix_vente
        FROM journal_produits jp
        JOIN medicaments m ON m.id = jp.medicament_id
        WHERE jp.journal_id = ?
        ORDER BY m.nom
    ');
    $stmt->execute([$journalId]);
    return $stmt->fetchAll();
}

function updateJournalStockInitial(PDO $db, int $lineId, int $stockInitial): void
{
    $stmt = $db->prepare('
        SELECT jp.*, j.cloture, j.id AS journal_id, j.date_jour, m.prix_achat, m.prix_vente
        FROM journal_produits jp
        JOIN journaux_quotidiens j ON j.id = jp.journal_id
        JOIN medicaments m ON m.id = jp.medicament_id
        WHERE jp.id = ?
    ');
    $stmt->execute([$lineId]);
    $line = $stmt->fetch();
    if (!$line || $line['cloture']) {
        return;
    }

    $stockFinal = $stockInitial + (int) $line['entrees'] - (int) $line['sorties'];
    $db->prepare('
        UPDATE journal_produits SET
            stock_initial = ?,
            stock_final = ?,
            stock_final_manuel = NULL,
            valeur_initial_cdf = ?,
            valeur_final_cdf = ?
        WHERE id = ?
    ')->execute([
        $stockInitial,
        $stockFinal,
        $stockInitial * (float) $line['prix_achat'],
        $stockFinal * (float) $line['prix_achat'],
        $lineId,
    ]);

    syncJournalDay($db, (int) $line['journal_id'], $line['date_jour']);
}

function updateJournalStockFinal(PDO $db, int $lineId, int $stockFinal): void
{
    $stmt = $db->prepare('
        SELECT jp.*, j.cloture, j.id AS journal_id, j.date_jour, m.prix_achat
        FROM journal_produits jp
        JOIN journaux_quotidiens j ON j.id = jp.journal_id
        JOIN medicaments m ON m.id = jp.medicament_id
        WHERE jp.id = ?
    ');
    $stmt->execute([$lineId]);
    $line = $stmt->fetch();
    if (!$line || $line['cloture']) {
        return;
    }

    $db->prepare('
        UPDATE journal_produits SET
            stock_final = ?,
            stock_final_manuel = ?,
            valeur_final_cdf = ?
        WHERE id = ?
    ')->execute([
        $stockFinal,
        $stockFinal,
        $stockFinal * (float) $line['prix_achat'],
        $lineId,
    ]);

    syncJournalDay($db, (int) $line['journal_id'], $line['date_jour']);
}

function createNextDayFromClose(PDO $db, string $nextDate): int
{
    return openJournalDay($db, $nextDate);
}
