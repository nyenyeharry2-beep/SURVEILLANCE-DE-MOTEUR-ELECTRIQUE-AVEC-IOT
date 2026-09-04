<?php

declare(strict_types=1);

require_once __DIR__ . '/journal.php';

function ensureJourneeSchema(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $db->exec('
        CREATE TABLE IF NOT EXISTS journaux_quotidiens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date_jour DATE NOT NULL UNIQUE,
            taux_usd_cdf DECIMAL(12, 2) NULL,
            fond_caisse_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
            fond_caisse_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
            caisse_cloture_cdf DECIMAL(14, 2) NULL,
            caisse_cloture_usd DECIMAL(14, 2) NULL,
            stock_initial_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
            stock_initial_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
            entrees_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
            entrees_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
            sorties_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
            sorties_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
            stock_final_cdf DECIMAL(14, 2) NOT NULL DEFAULT 0,
            stock_final_usd DECIMAL(14, 2) NOT NULL DEFAULT 0,
            cloture TINYINT(1) NOT NULL DEFAULT 0,
            utilisateur_id INT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            cloture_at TIMESTAMP NULL,
            FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    $columns = [
        'taux_usd_cdf' => 'DECIMAL(12, 2) NULL AFTER date_jour',
        'fond_caisse_cdf' => 'DECIMAL(14, 2) NOT NULL DEFAULT 0',
        'fond_caisse_usd' => 'DECIMAL(14, 2) NOT NULL DEFAULT 0',
        'caisse_cloture_cdf' => 'DECIMAL(14, 2) NULL',
        'caisse_cloture_usd' => 'DECIMAL(14, 2) NULL',
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->exec("ALTER TABLE journaux_quotidiens ADD COLUMN {$col} {$def}");
        } catch (Throwable $e) {
            // Column already exists
        }
    }

    $ready = true;
}

function getTauxUsdCdfForDate(PDO $db, ?string $date = null): float
{
    ensureJourneeSchema($db);
    $date = $date ?: date('Y-m-d');
    $journal = getJournal($db, $date);
    if ($journal && !empty($journal['taux_usd_cdf'])) {
        return (float) $journal['taux_usd_cdf'];
    }

    return getTauxUsdCdf();
}

function getJourneeStatus(PDO $db, ?string $date = null): array
{
    ensureJourneeSchema($db);
    $date = $date ?: date('Y-m-d');
    $journal = getJournal($db, $date);
    $prev = getPreviousClosedJournal($db, $date);
    $taux = getTauxUsdCdfForDate($db, $date);

    $ouverte = $journal !== null && !(bool) ($journal['cloture'] ?? false);
    $cloturee = $journal !== null && (bool) ($journal['cloture'] ?? false);

    return [
        'date' => $date,
        'ouverte' => $ouverte,
        'cloturee' => $cloturee,
        'existe' => $journal !== null,
        'taux_usd_cdf' => $taux,
        'fond_caisse_cdf' => (float) ($journal['fond_caisse_cdf'] ?? 0),
        'fond_caisse_usd' => (float) ($journal['fond_caisse_usd'] ?? 0),
        'caisse_cloture_cdf' => isset($journal['caisse_cloture_cdf']) ? (float) $journal['caisse_cloture_cdf'] : null,
        'caisse_cloture_usd' => isset($journal['caisse_cloture_usd']) ? (float) $journal['caisse_cloture_usd'] : null,
        'peut_vendre' => $ouverte,
        'message' => $ouverte
            ? 'Journée ouverte — ventes autorisées.'
            : ($cloturee
                ? 'Journée clôturée. Ouvrez une nouvelle journée pour vendre.'
                : 'Aucune journée ouverte. Le superviseur doit ouvrir la journée.'),
        'journal_id' => $journal ? (int) $journal['id'] : null,
        'jour_precedent_cloture' => $prev ? $prev['date_jour'] : null,
    ];
}

function assertJourneeOuverte(PDO $db, ?string $date = null): void
{
    $status = getJourneeStatus($db, $date);
    if (!$status['peut_vendre']) {
        if (defined('NOUVELLE_EVE_API')) {
            apiError($status['message'], 403);
        }
        throw new RuntimeException($status['message']);
    }
}

function openJourneeWithCaisse(
    PDO $db,
    string $date,
    float $fondCdf,
    float $fondUsd,
    float $tauxUsdCdf
): int {
    ensureJourneeSchema($db);

    $existing = getJournal($db, $date);
    if ($existing) {
        if ($existing['cloture']) {
            if (defined('NOUVELLE_EVE_API')) {
                apiError('Cette journée est déjà clôturée.');
            }
            throw new RuntimeException('Journée déjà clôturée.');
        }
        $db->prepare('
            UPDATE journaux_quotidiens
            SET taux_usd_cdf = ?, fond_caisse_cdf = ?, fond_caisse_usd = ?
            WHERE id = ?
        ')->execute([$tauxUsdCdf, $fondCdf, $fondUsd, $existing['id']]);

        return (int) $existing['id'];
    }

    $anyOpen = $db->query('SELECT date_jour FROM journaux_quotidiens WHERE cloture = 0 LIMIT 1')->fetch();
    if ($anyOpen) {
        if (defined('NOUVELLE_EVE_API')) {
            apiError('Clôturez d\'abord la journée du ' . $anyOpen['date_jour'] . ' avant d\'en ouvrir une nouvelle.');
        }
        throw new RuntimeException('Clôturez la journée ouverte avant d\'en ouvrir une nouvelle.');
    }

    $journalId = openJournalDay($db, $date);

    $db->prepare('
        UPDATE journaux_quotidiens
        SET taux_usd_cdf = ?, fond_caisse_cdf = ?, fond_caisse_usd = ?
        WHERE id = ?
    ')->execute([$tauxUsdCdf, $fondCdf, $fondUsd, $journalId]);

    return $journalId;
}

function closeJourneeWithCaisse(
    PDO $db,
    string $date,
    float $caisseClotureCdf,
    float $caisseClotureUsd,
    int $userId
): void {
    ensureJourneeSchema($db);
    $journal = getJournal($db, $date);
    if (!$journal) {
        if (defined('NOUVELLE_EVE_API')) {
            apiError('Aucune journée ouverte pour cette date.');
        }
        throw new RuntimeException('Aucune journée pour cette date.');
    }
    if ($journal['cloture']) {
        if (defined('NOUVELLE_EVE_API')) {
            apiError('Journée déjà clôturée.');
        }
        throw new RuntimeException('Journée déjà clôturée.');
    }

    closeJournalDay($db, (int) $journal['id'], $userId);

    $db->prepare('
        UPDATE journaux_quotidiens
        SET caisse_cloture_cdf = ?, caisse_cloture_usd = ?
        WHERE id = ?
    ')->execute([$caisseClotureCdf, $caisseClotureUsd, $journal['id']]);
}

function fetchRapportParJours(PDO $db, string $debut, string $fin): array
{
    ensureJourneeSchema($db);
    $stmt = $db->prepare('
        SELECT j.date_jour, j.taux_usd_cdf, j.fond_caisse_cdf, j.fond_caisse_usd,
               j.caisse_cloture_cdf, j.caisse_cloture_usd, j.cloture, j.cloture_at,
               j.sorties_cdf, j.sorties_usd,
               (SELECT COUNT(*) FROM ventes v WHERE DATE(v.date_vente) = j.date_jour) AS nb_ventes,
               (SELECT COALESCE(SUM(v.montant_total), 0) FROM ventes v
                WHERE DATE(v.date_vente) = j.date_jour AND COALESCE(v.devise, "CDF") = "CDF") AS ventes_cdf,
               (SELECT COALESCE(SUM(v.montant_total), 0) FROM ventes v
                WHERE DATE(v.date_vente) = j.date_jour AND COALESCE(v.devise, "CDF") = "USD") AS ventes_usd
        FROM journaux_quotidiens j
        WHERE j.date_jour BETWEEN ? AND ?
        ORDER BY j.date_jour DESC
    ');
    $stmt->execute([$debut, $fin]);

    return array_map(static function (array $row): array {
        return [
            'date' => $row['date_jour'],
            'taux_usd_cdf' => $row['taux_usd_cdf'] ? (float) $row['taux_usd_cdf'] : getTauxUsdCdf(),
            'fond_caisse_cdf' => (float) $row['fond_caisse_cdf'],
            'fond_caisse_usd' => (float) $row['fond_caisse_usd'],
            'caisse_cloture_cdf' => $row['caisse_cloture_cdf'] !== null ? (float) $row['caisse_cloture_cdf'] : null,
            'caisse_cloture_usd' => $row['caisse_cloture_usd'] !== null ? (float) $row['caisse_cloture_usd'] : null,
            'cloture' => (bool) $row['cloture'],
            'cloture_at' => $row['cloture_at'],
            'nb_ventes' => (int) $row['nb_ventes'],
            'ventes_cdf' => (float) $row['ventes_cdf'],
            'ventes_usd' => (float) $row['ventes_usd'],
            'sorties_cdf' => (float) $row['sorties_cdf'],
            'sorties_usd' => (float) $row['sorties_usd'],
        ];
    }, $stmt->fetchAll());
}

function parseVenteLignesInput(array $body): array
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
        ]];
    }

    return [];
}
