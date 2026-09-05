<?php
/**
 * Teste chaque étape du tableau de bord — supprimez après réparation.
 */
header('Content-Type: text/plain; charset=utf-8');

$checks = [];

function step(string $label, callable $fn): void
{
    global $checks;
    try {
        $detail = $fn();
        $checks[] = ['ok' => true, 'label' => $label, 'detail' => (string) $detail];
    } catch (ParseError $e) {
        $checks[] = ['ok' => false, 'label' => $label, 'detail' => 'Syntaxe PHP : ' . $e->getMessage()];
    } catch (Throwable $e) {
        $checks[] = ['ok' => false, 'label' => $label, 'detail' => $e->getMessage()];
    }
}

echo "=== TEST DASHBOARD ===\n\n";

step('config/config.php', static function (): string {
    $path = __DIR__ . '/config/config.php';
    if (!file_exists($path)) {
        throw new RuntimeException('Manquant — ouvrez setup.php');
    }
    return $path;
});

step('Charger auth.php', static function (): string {
    require_once __DIR__ . '/includes/auth.php';
    return 'OK';
});

step('Connexion MySQL (getDB)', static function (): string {
    $db = getDB();
    return 'PDO OK';
});

step('Fichier medicaments_unites.php (syntaxe)', static function (): string {
    require_once __DIR__ . '/includes/medicaments_unites.php';
    return 'OK';
});

$db = null;
step('Table medicaments', static function () use (&$db): string {
    $db = getDB();
    $n = (int) $db->query('SELECT COUNT(*) FROM medicaments')->fetchColumn();
    return $n . ' produits';
});

step('Stats dashboard (actif=1)', static function () use (&$db): string {
    $db = $db ?: getDB();
    $n = (int) $db->query('SELECT COUNT(*) FROM medicaments WHERE actif = 1')->fetchColumn();
    return (string) $n;
});

step('Somme ventes du jour', static function () use (&$db): string {
    $db = $db ?: getDB();
    $tot = sommeVentesDual($db, 'DATE(date_vente) = CURDATE()');
    return 'CDF ' . ($tot['total_cdf'] ?? 0);
});

step('Dernières ventes', static function () use (&$db): string {
    $db = $db ?: getDB();
    $db->query('
        SELECT v.*, u.nom AS vendeur
        FROM ventes v
        JOIN utilisateurs u ON u.id = v.utilisateur_id
        ORDER BY v.date_vente DESC
        LIMIT 1
    ')->fetch();
    return 'OK';
});

step('Header (localTimeInfo)', static function (): string {
    $info = localTimeInfo();
    return $info['label'] ?? 'OK';
});

foreach ($checks as $c) {
    echo ($c['ok'] ? '[OK] ' : '[ERREUR] ') . $c['label'];
    if ($c['detail'] !== '') {
        echo ' — ' . $c['detail'];
    }
    echo "\n";
}

echo "\nSi medicaments_unites.php échoue : re-uploadez includes/medicaments_unites.php\n";
echo "Si table medicaments échoue : importez database/schema_nouvelle_eve_complet_v1.7.sql\n";
echo "Puis : login.php → reparer.php → dashboard.php\n";
