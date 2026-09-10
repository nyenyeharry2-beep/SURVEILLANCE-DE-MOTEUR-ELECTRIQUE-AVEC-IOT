<?php
/**
 * Configuration générale de l'application
 */

require_once __DIR__ . '/database.php';

// Fuseau horaire
date_default_timezone_set('Africa/Lubumbashi');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

// Mois scolaires (ordre d'affichage)
define('SCHOOL_MONTHS', [
    9  => ['code' => 'SEPT',  'label' => 'Septembre', 'short' => 'SEPT'],
    10 => ['code' => 'OCT',   'label' => 'Octobre',   'short' => 'OCTO'],
    11 => ['code' => 'NOV',   'label' => 'Novembre',  'short' => 'NOV'],
    12 => ['code' => 'DEC',   'label' => 'Décembre',  'short' => 'DEC'],
    1  => ['code' => 'JANV',  'label' => 'Janvier',   'short' => 'JANV'],
    2  => ['code' => 'FEV',   'label' => 'Février',   'short' => 'FEV'],
    3  => ['code' => 'MARS',  'label' => 'Mars',      'short' => 'MARS'],
    4  => ['code' => 'AVRIL', 'label' => 'Avril',     'short' => 'AVRIL'],
    5  => ['code' => 'MAI',   'label' => 'Mai',         'short' => 'MAI'],
    6  => ['code' => 'JUIN',  'label' => 'Juin',        'short' => 'JUIN'],
]);

define('APP_NAME', 'Transport Scolaire - C.S LES SUPER GENIES');
define('APP_VERSION', '1.0.0');
