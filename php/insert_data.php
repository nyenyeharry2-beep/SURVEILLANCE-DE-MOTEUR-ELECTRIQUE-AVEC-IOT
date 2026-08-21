<?php
/**
 * insert_data.php - Reception des mesures moteur (POST)
 * URL: http://surveillancemoteurharry.ct.ws/insert_data.php
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Methode POST requise'], 405);
}

if (!checkApiKey()) {
    jsonResponse(['status' => 'error', 'message' => 'Cle API invalide'], 401);
}

$fields = ['ax', 'ay', 'az', 'rpm', 'arms', 'vrms', 'ecart', 'etat', 'relay'];
$data = [];

foreach ($fields as $field) {
    if (!isset($_POST[$field])) {
        jsonResponse(['status' => 'error', 'message' => "Champ manquant: $field"], 400);
    }
}

$data['ax'] = (float) $_POST['ax'];
$data['ay'] = (float) $_POST['ay'];
$data['az'] = (float) $_POST['az'];
$data['rpm'] = (float) $_POST['rpm'];
$data['arms'] = (float) $_POST['arms'];
$data['vrms'] = (float) $_POST['vrms'];
$data['ecart'] = (float) $_POST['ecart'];
$data['etat'] = substr(trim((string) $_POST['etat']), 0, 20);
$data['relay'] = strtoupper(substr(trim((string) $_POST['relay']), 0, 3));
$data['anomalie_vibration'] = isset($_POST['anomalie_vibration']) ? (int) (bool) $_POST['anomalie_vibration'] : 0;
$data['anomalie_vitesse'] = isset($_POST['anomalie_vitesse']) ? (int) (bool) $_POST['anomalie_vitesse'] : 0;

if (!in_array($data['relay'], ['ON', 'OFF'], true)) {
    jsonResponse(['status' => 'error', 'message' => 'Valeur relay invalide'], 400);
}

try {
    $pdo = getDbConnection();
    $sql = 'INSERT INTO moteur_surveillance
            (ax, ay, az, rpm, arms, vrms, ecart, etat, relay_state, anomalie_vibration, anomalie_vitesse)
            VALUES (:ax, :ay, :az, :rpm, :arms, :vrms, :ecart, :etat, :relay, :anom_vib, :anom_vit)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ax' => $data['ax'],
        ':ay' => $data['ay'],
        ':az' => $data['az'],
        ':rpm' => $data['rpm'],
        ':arms' => $data['arms'],
        ':vrms' => $data['vrms'],
        ':ecart' => $data['ecart'],
        ':etat' => $data['etat'],
        ':relay' => $data['relay'],
        ':anom_vib' => $data['anomalie_vibration'],
        ':anom_vit' => $data['anomalie_vitesse'],
    ]);

    // Mise a jour etat relais
    $pdo->prepare('UPDATE etat_relais SET relay_state = :relay WHERE id = 1')
        ->execute([':relay' => $data['relay']]);

    jsonResponse(['status' => 'ok', 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    jsonResponse(['status' => 'error', 'message' => 'Erreur base de donnees'], 500);
}
