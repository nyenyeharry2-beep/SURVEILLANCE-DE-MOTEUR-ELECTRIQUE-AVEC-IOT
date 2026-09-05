<?php

declare(strict_types=1);

/**
 * Applique toutes les migrations compatibles InfinityFree au premier accès DB.
 */
function ensureAllSchemas(PDO $db): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    require_once __DIR__ . '/medicaments_unites.php';
    ensureMedicamentUnitesSchema($db);

    require_once __DIR__ . '/ventes.php';
    ensureVenteSchema($db);

    require_once __DIR__ . '/achats.php';
    ensureAchatSchema($db);

    require_once __DIR__ . '/journee.php';
    ensureJourneeSchema($db);

    require_once __DIR__ . '/caisse.php';
    ensureCaisseTable($db);

    $ready = true;
}
