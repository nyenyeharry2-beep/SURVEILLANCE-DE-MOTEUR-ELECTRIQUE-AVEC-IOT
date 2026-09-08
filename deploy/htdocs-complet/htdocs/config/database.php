<?php
/**
 * Configuration base de données - InfinityFree
 * Déployer sur supergenies2026.site.je (htdocs)
 */
return [
    'host'     => getenv('DB_HOST') ?: 'sql302.infinityfree.com',
    'port'     => getenv('DB_PORT') ?: '3306',
    'dbname'   => getenv('DB_NAME') ?: 'if0_42853060_supergenies',
    'username' => getenv('DB_USER') ?: 'if0_42853060',
    'password' => getenv('DB_PASS') ?: 'TYCeVNtcLMBA',
    'charset'  => 'utf8mb4',
];
