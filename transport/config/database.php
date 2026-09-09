<?php
/**
 * Configuration base de données - InfinityFree
 * 
 * IMPORTANT: Modifiez ces valeurs selon votre compte InfinityFree
 * Panel: MySQL Databases > Connection Details
 */

define('DB_HOST', 'sql205.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'if0_42871659_genies');
define('DB_USER', 'if0_42871659');
define('DB_PASS', 'JoJyWjiexiP1TAd'); // Changez si différent
define('DB_CHARSET', 'utf8mb4');

// URL de base de l'application (sans slash final)
// Si fichiers dans htdocs/ directement : https://genies.free.je
// Si fichiers dans htdocs/transport/     : https://genies.free.je/transport
define('BASE_URL', 'https://genies.free.je');

// Mode debug (désactiver en production)
define('DEBUG_MODE', false);
