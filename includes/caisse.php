<?php

declare(strict_types=1);

function ensureCaisseTable(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $db->exec('
        CREATE TABLE IF NOT EXISTS mouvements_caisse (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM("entree", "sortie") NOT NULL,
            montant DECIMAL(12, 2) NOT NULL,
            devise ENUM("CDF", "USD") NOT NULL DEFAULT "CDF",
            motif VARCHAR(255) NOT NULL,
            utilisateur_id INT NOT NULL,
            date_mouvement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
            INDEX idx_date (date_mouvement),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    $ready = true;
}

function createMouvementCaisse(PDO $db, array $user, array $body): array
{
    ensureCaisseTable($db);

    $type = strtolower(trim($body['type'] ?? ''));
    $montant = (float) ($body['montant'] ?? 0);
    $devise = normalizeDevise($body['devise'] ?? 'CDF');
    $motif = trim($body['motif'] ?? '');

    if (!in_array($type, ['entree', 'sortie'], true)) {
        if (defined('NOUVELLE_EVE_API')) {
            apiError('Type invalide : entree ou sortie.');
        }
        throw new InvalidArgumentException('Type invalide.');
    }
    if ($montant <= 0) {
        if (defined('NOUVELLE_EVE_API')) {
            apiError('Montant obligatoire.');
        }
        throw new InvalidArgumentException('Montant obligatoire.');
    }
    if ($motif === '') {
        if (defined('NOUVELLE_EVE_API')) {
            apiError('Motif obligatoire.');
        }
        throw new InvalidArgumentException('Motif obligatoire.');
    }

    $db->prepare('
        INSERT INTO mouvements_caisse (type, montant, devise, motif, utilisateur_id)
        VALUES (?, ?, ?, ?, ?)
    ')->execute([$type, $montant, $devise, $motif, (int) $user['id']]);

    return [
        'id' => (int) $db->lastInsertId(),
        'type' => $type,
        'montant' => $montant,
        'devise' => $devise,
        'motif' => $motif,
        'date_mouvement' => date('Y-m-d H:i:s'),
        'vendeur' => $user['nom'],
    ];
}

function fetchMouvementsCaisse(PDO $db, string $date): array
{
    ensureCaisseTable($db);

    $stmt = $db->prepare('
        SELECT m.id, m.type, m.montant, m.devise, m.motif, m.date_mouvement, u.nom AS vendeur
        FROM mouvements_caisse m
        JOIN utilisateurs u ON u.id = m.utilisateur_id
        WHERE DATE(m.date_mouvement) = ?
        ORDER BY m.date_mouvement DESC
    ');
    $stmt->execute([$date]);
    $rows = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        $devise = normalizeDevise($row['devise']);
        $montant = (float) $row['montant'];

        return [
            'id' => (int) $row['id'],
            'type' => $row['type'],
            'montant' => $montant,
            'devise' => $devise,
            'motif' => $row['motif'],
            'date_mouvement' => $row['date_mouvement'],
            'vendeur' => $row['vendeur'],
            'montant_cdf' => convertirDevise($montant, $devise, 'CDF'),
            'montant_usd' => convertirDevise($montant, $devise, 'USD'),
        ];
    }, $rows);
}

function resumeCaisseJour(PDO $db, string $date): array
{
    $mouvements = fetchMouvementsCaisse($db, $date);
    $entreesCdf = 0.0;
    $entreesUsd = 0.0;
    $sortiesCdf = 0.0;
    $sortiesUsd = 0.0;

    foreach ($mouvements as $m) {
        if ($m['type'] === 'entree') {
            $entreesCdf += $m['montant_cdf'];
            $entreesUsd += $m['montant_usd'];
        } else {
            $sortiesCdf += $m['montant_cdf'];
            $sortiesUsd += $m['montant_usd'];
        }
    }

    return [
        'date' => $date,
        'entrees_cdf' => $entreesCdf,
        'entrees_usd' => $entreesUsd,
        'sorties_cdf' => $sortiesCdf,
        'sorties_usd' => $sortiesUsd,
        'solde_cdf' => $entreesCdf - $sortiesCdf,
        'solde_usd' => $entreesUsd - $sortiesUsd,
        'nb_mouvements' => count($mouvements),
    ];
}
