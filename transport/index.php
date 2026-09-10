<?php
/**
 * Page d'accueil publique - Redirige vers le formulaire d'inscription
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';

redirect(BASE_URL . '/inscription.php');
