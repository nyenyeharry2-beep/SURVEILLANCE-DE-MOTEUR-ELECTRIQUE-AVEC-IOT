<?php
/**
 * Applique les migrations DB — ouvrez une fois dans le navigateur, puis supprimez ce fichier.
 */
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$db = getDB();
require_once __DIR__ . '/includes/medicaments_unites.php';
require_once __DIR__ . '/includes/ventes.php';
require_once __DIR__ . '/includes/achats.php';

ensureMedicamentUnitesSchema($db);
ensureVenteSchema($db);
ensureAchatSchema($db);

flash('success', 'Migrations appliquées avec succès.');
redirect('dashboard.php');
