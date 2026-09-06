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

    $steps = [
        static function (PDO $db): void {
            require_once __DIR__ . '/medicaments_unites.php';
            ensureMedicamentUnitesSchema($db);
        },
        static function (PDO $db): void {
            require_once __DIR__ . '/ventes.php';
            ensureVenteSchema($db);
        },
        static function (PDO $db): void {
            require_once __DIR__ . '/achats.php';
            ensureAchatSchema($db);
        },
        static function (PDO $db): void {
            require_once __DIR__ . '/journee.php';
            ensureJourneeSchema($db);
        },
        static function (PDO $db): void {
            require_once __DIR__ . '/caisse.php';
            ensureCaisseTable($db);
        },
    ];

    foreach ($steps as $step) {
        try {
            $step($db);
        } catch (Throwable $e) {
            // Migration partielle — la page peut quand même charger
        }
    }

    $ready = true;
}
