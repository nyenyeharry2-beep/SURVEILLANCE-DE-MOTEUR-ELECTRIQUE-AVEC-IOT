<?php
/**
 * dashboard.php - Interface web de supervision
 */
require_once __DIR__ . '/config.php';

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    $cmd = strtoupper(trim((string) $_POST['cmd']));
    if (in_array($cmd, ['ON', 'OFF'], true)) {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('INSERT INTO commandes (cmd) VALUES (:cmd)');
            $stmt->execute([':cmd' => $cmd]);
            $message = "Commande $cmd envoyee. L'ESP32 la recuperera sous 5 secondes.";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Erreur lors de l envoi de la commande.';
            $messageType = 'error';
        }
    }
}

$latest = null;
$history = [];
$relayState = 'OFF';

try {
    $pdo = getDbConnection();
    ensureDatabaseSchema();

    $stmt = $pdo->query('SELECT * FROM moteur_surveillance ORDER BY date_mesure DESC, id DESC LIMIT 1');
    $latest = $stmt->fetch();

    $stmt = $pdo->query('SELECT * FROM moteur_surveillance ORDER BY date_mesure DESC, id DESC LIMIT 20');
    $history = $stmt->fetchAll();

    $stmt = $pdo->query('SELECT relay_state FROM etat_relais WHERE id = 1');
    $relayRow = $stmt->fetch();
    if ($relayRow) {
        $relayState = $relayRow['relay_state'];
    }
} catch (PDOException $e) {
    $message = getDbErrorMessage($e) . ' Ouvrez install.php pour corriger.';
    $messageType = 'error';
}

function badgeClass(string $etat): string
{
    switch ($etat) {
        case 'NORMAL':
            return 'badge-ok';
        case 'ANOMALIE':
            return 'badge-warn';
        case 'MOTEUR_ARRETE':
            return 'badge-off';
        default:
            return 'badge-err';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title>Surveillance Moteur Harry</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #0f172a;
            color: #e2e8f0;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 24px; }
        h1 { margin-top: 0; }
        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,.25);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        .metric {
            background: #334155;
            border-radius: 10px;
            padding: 14px;
        }
        .metric label { display: block; font-size: 12px; opacity: .8; }
        .metric strong { font-size: 22px; }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: bold;
        }
        .badge-ok { background: #166534; }
        .badge-warn { background: #b45309; }
        .badge-off { background: #475569; }
        .badge-err { background: #991b1b; }
        .btn {
            border: 0;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-on { background: #16a34a; color: white; }
        .btn-off { background: #dc2626; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #334155; text-align: left; font-size: 14px; }
        th { color: #94a3b8; }
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .msg-success { background: #14532d; }
        .msg-error { background: #7f1d1d; }
        .msg-info { background: #1e3a8a; }
    </style>
</head>
<body>
<div class="container">
    <h1>Surveillance Moteur Electrique</h1>
    <p>Domaine: surveillancemoteurharry.ct.ws | Actualisation auto 10s</p>

    <?php if ($message): ?>
        <div class="msg msg-<?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Derniere mesure</h2>
        <?php if ($latest): ?>
            <p>
                <span class="badge <?= badgeClass($latest['etat']) ?>">
                    <?= htmlspecialchars($latest['etat']) ?>
                </span>
                &nbsp; Relais: <strong><?= htmlspecialchars($latest['relay_state']) ?></strong>
                &nbsp; <?= htmlspecialchars($latest['date_mesure']) ?>
            </p>
            <div class="grid">
                <div class="metric"><label>RPM</label><strong><?= number_format($latest['rpm'], 1) ?></strong></div>
                <div class="metric"><label>ARMS (m/s²)</label><strong><?= number_format($latest['arms'], 3) ?></strong></div>
                <div class="metric"><label>VRMS (mm/s)</label><strong><?= number_format($latest['vrms'], 3) ?></strong></div>
                <div class="metric"><label>Ecart (%)</label><strong><?= number_format($latest['ecart'], 2) ?></strong></div>
                <div class="metric"><label>AX</label><strong><?= number_format($latest['ax'], 3) ?></strong></div>
                <div class="metric"><label>AY</label><strong><?= number_format($latest['ay'], 3) ?></strong></div>
                <div class="metric"><label>AZ</label><strong><?= number_format($latest['az'], 3) ?></strong></div>
            </div>
        <?php else: ?>
            <p>Aucune donnee disponible. Verifiez la connexion ESP32.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Commande moteur</h2>
        <p>Etat relais connu: <strong><?= htmlspecialchars($relayState) ?></strong></p>
        <form method="post">
            <button class="btn btn-on" type="submit" name="cmd" value="ON">Demarrer moteur (ON)</button>
            <button class="btn btn-off" type="submit" name="cmd" value="OFF">Arreter moteur (OFF)</button>
        </form>
    </div>

    <div class="card">
        <h2>Historique (20 dernieres mesures)</h2>
        <table>
            <thead>
            <tr>
                <th>Date</th><th>RPM</th><th>ARMS</th><th>VRMS</th><th>Ecart</th><th>Etat</th><th>Relais</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['date_mesure']) ?></td>
                    <td><?= number_format($row['rpm'], 1) ?></td>
                    <td><?= number_format($row['arms'], 3) ?></td>
                    <td><?= number_format($row['vrms'], 3) ?></td>
                    <td><?= number_format($row['ecart'], 2) ?></td>
                    <td><?= htmlspecialchars($row['etat']) ?></td>
                    <td><?= htmlspecialchars($row['relay_state']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
