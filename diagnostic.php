<?php
/**
 * Diagnostic InfinityFree — supprimez ce fichier après réparation.
 */
header('Content-Type: text/plain; charset=utf-8');

$checks = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $checks;
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

check('PHP version >= 7.4', version_compare(PHP_VERSION, '7.4.0', '>='), PHP_VERSION);

$configPath = __DIR__ . '/config/config.php';
check('config/config.php existe', file_exists($configPath), $configPath);
check('includes/medicaments_unites.php', file_exists(__DIR__ . '/includes/medicaments_unites.php'));
check('includes/schema_util.php', file_exists(__DIR__ . '/includes/schema_util.php'));
check('includes/achats.php', file_exists(__DIR__ . '/includes/achats.php'));
check('achats.php (page)', file_exists(__DIR__ . '/achats.php'));
check('Pas de dossier achats/ conflictuel', !is_dir(__DIR__ . '/achats'), is_dir(__DIR__ . '/achats') ? 'Supprimez le dossier htdocs/achats/' : '');
check('ventes.php', file_exists(__DIR__ . '/ventes.php'));
check('medicaments.php', file_exists(__DIR__ . '/medicaments.php'));

if (file_exists($configPath)) {
    try {
        require_once __DIR__ . '/includes/db.php';
        require_once __DIR__ . '/includes/medicaments_unites.php';
        require_once __DIR__ . '/includes/schema_util.php';
        $db = getDB();
        check('Connexion MySQL', true);

        ensureMedicamentUnitesSchema($db);
        check('Migration type_unite', dbColumnExists($db, 'medicaments', 'type_unite'));
        check('Migration unite_vente', dbColumnExists($db, 'vente_lignes', 'unite_vente'));
        check('Migration unite_entree', dbColumnExists($db, 'achat_lignes', 'unite_entree'));

        $db->query('SELECT COUNT(*) FROM medicaments')->fetchColumn();
        check('Table medicaments', true);
    } catch (ParseError $e) {
        check('Syntaxe PHP', false, $e->getMessage() . ' — re-uploadez includes/medicaments_unites.php');
    } catch (Throwable $e) {
        check('Connexion / base', false, $e->getMessage());
    }
} else {
    check('Connexion MySQL', false, 'Créez config/config.php depuis config.example.php');
}

echo "=== DIAGNOSTIC PHARMACIE NOUVELLE EVE ===\n\n";
foreach ($checks as $c) {
    echo ($c['ok'] ? '[OK] ' : '[ERREUR] ') . $c['label'];
    if ($c['detail'] !== '') {
        echo ' — ' . $c['detail'];
    }
    echo "\n";
}

echo "\nSi config.php manque : copiez config/config.example.php → config/config.php\n";
echo "Si fichiers manquent : re-uploadez pharmagest-filemanager-complet.zip et extrayez dans htdocs/\n";
echo "403 sur achats.php : vérifiez qu'il n'y a PAS un dossier achats/ sans fichier achats.php\n";
