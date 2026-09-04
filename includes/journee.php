<?php

declare(strict_types=1);

require_once __DIR__ . '/journal.php';

/** Heure début journée métier (6h) */
function journeeHeureDebut(): int
{
    return 6;
}

/** Heure fin journée métier (20h) — après 20h = nouvelle journée */
function journeeHeureFin(): int
{
    return 20;
}

/**
 * Date métier : 6h→20h = jour J ; après 20h et avant 6h = jour J+1 (nuit).
 */
function getBusinessDate(?DateTime $now = null): string
{
    $tz = new DateTimeZone(defined('TIMEZONE') ? TIMEZONE : 'Africa/Kinshasa');
    if ($now === null) {
        $now = new DateTime('now', $tz);
    } elseif ($now->getTimezone()->getName() !== $tz->getName()) {
        $now = clone $now;
        $now->setTimezone($tz);
    }

    $hour = (int) $now->format('G');
    if ($hour >= journeeHeureFin()) {
        $now->modify('+1 day');
    }

    return $now->format('Y-m-d');
}

function getBusinessDateTimeRange(string $dateJour): array
{
    $tz = defined('TIMEZONE') ? TIMEZONE : 'Africa/Kinshasa';
    $debut = new DateTime($dateJour . ' ' . str_pad((string) journeeHeureDebut(), 2, '0', STR_PAD_LEFT) . ':00:00', new DateTimeZone($tz));
    $fin = new DateTime($dateJour . ' ' . str_pad((string) journeeHeureFin(), 2, '0', STR_PAD_LEFT) . ':00:00', new DateTimeZone($tz));

    return ['debut' => $debut, 'fin' => $fin];
}

function isWithinBusinessHours(?DateTime $now = null): bool
{
    $tz = new DateTimeZone(defined('TIMEZONE') ? TIMEZONE : 'Africa/Kinshasa');
    $now = $now ?? new DateTime('now', $tz);
    $hour = (int) $now->format('G');

    return $hour >= journeeHeureDebut() && $hour < journeeHeureFin();
}

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
    $date = $date ?: getBusinessDate();
    $journal = getJournal($db, $date);
    if ($journal && !empty($journal['taux_usd_cdf'])) {
        return (float) $journal['taux_usd_cdf'];
    }

    return getTauxUsdCdf();
}

function getJourneeStatus(PDO $db, ?string $date = null): array
{
    ensureJourneeSchema($db);
    $date = $date ?: getBusinessDate();
    $journal = getJournal($db, $date);
    $prev = getPreviousClosedJournal($db, $date);
    $taux = getTauxUsdCdfForDate($db, $date);
    $now = new DateTime('now', new DateTimeZone(defined('TIMEZONE') ? TIMEZONE : 'Africa/Kinshasa'));

    $ouverte = $journal !== null && !(bool) ($journal['cloture'] ?? false);
    $cloturee = $journal !== null && (bool) ($journal['cloture'] ?? false);
    $dansHoraires = isWithinBusinessHours($now);

    $msg = $ouverte
        ? ('Journée ouverte (' . journeeHeureDebut() . 'h-' . journeeHeureFin() . 'h) — ventes autorisées.')
        : ($cloturee
            ? 'Journée clôturée. Ouvrez la nouvelle journée pour vendre.'
            : 'Aucune journée ouverte. Le superviseur doit ouvrir la journée (Journal).');

    if (!$dansHoraires && $ouverte) {
        $msg .= ' Hors horaire 6h-20h : après 20h une nouvelle journée commence.';
    }

    return [
        'date' => $date,
        'date_metier' => $date,
        'heure_debut' => journeeHeureDebut(),
        'heure_fin' => journeeHeureFin(),
        'dans_horaires' => $dansHoraires,
        'ouverte' => $ouverte,
        'cloturee' => $cloturee,
        'existe' => $journal !== null,
        'taux_usd_cdf' => $taux,
        'fond_caisse_cdf' => (float) ($journal['fond_caisse_cdf'] ?? 0),
        'fond_caisse_usd' => (float) ($journal['fond_caisse_usd'] ?? 0),
        'caisse_cloture_cdf' => isset($journal['caisse_cloture_cdf']) ? (float) $journal['caisse_cloture_cdf'] : null,
        'caisse_cloture_usd' => isset($journal['caisse_cloture_usd']) ? (float) $journal['caisse_cloture_usd'] : null,
        'peut_vendre' => $ouverte,
        'message' => $msg,
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
               (SELECT COUNT(*) FROM ventes v WHERE COALESCE(v.date_jour, DATE(v.date_vente)) = j.date_jour AND COALESCE(v.annulee, 0) = 0) AS nb_ventes,
               (SELECT COALESCE(SUM(v.montant_total), 0) FROM ventes v
                WHERE COALESCE(v.date_jour, DATE(v.date_vente)) = j.date_jour AND COALESCE(v.annulee, 0) = 0
                  AND COALESCE(v.devise, "CDF") = "CDF") AS ventes_cdf,
               (SELECT COALESCE(SUM(v.montant_total), 0) FROM ventes v
                WHERE COALESCE(v.date_jour, DATE(v.date_vente)) = j.date_jour AND COALESCE(v.annulee, 0) = 0
                  AND COALESCE(v.devise, "CDF") = "USD") AS ventes_usd
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
