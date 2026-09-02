<?php
/**
 * Copiez ce fichier vers config.php et remplissez vos identifiants InfinityFree.
 * Dans le panneau InfinityFree : MySQL Databases → créez une base et un utilisateur.
 */

define('DB_HOST', 'sqlXXX.infinityfree.com'); // Remplacez XXX par votre numéro de serveur
define('DB_NAME', 'if0_XXXXXXXX_pharma');      // Nom de la base de données
define('DB_USER', 'if0_XXXXXXXX');             // Utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe');       // Mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'PharmaGest');
define('APP_URL', 'https://votre-site.infinityfreeapp.com'); // URL de votre site
define('TIMEZONE', 'Africa/Kinshasa'); // Fuseau horaire RDC

// 1 USD = X Francs Congolais (CDF)
define('TAUX_USD_CDF', 2850);

// Devise par défaut pour les prix des médicaments
define('DEVISE_DEFAUT', 'CDF');

date_default_timezone_set(TIMEZONE);
