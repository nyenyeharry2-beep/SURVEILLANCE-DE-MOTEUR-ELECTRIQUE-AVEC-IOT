<?php
/**
 * API: Traitement du formulaire d'inscription public
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide. Rechargez la page.']);
    exit;
}

// Validation
$nomComplet = trim($_POST['nom_complet'] ?? '');
$classeId = (int) ($_POST['classe_id'] ?? 0);
$section = '';
$parentNom = trim($_POST['parent_nom'] ?? '');
$telephone = trim($_POST['telephone_parent'] ?? '');
$telephone2 = trim($_POST['telephone_parent2'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$arretId = null;
$arretPrecision = trim($_POST['arret_precision'] ?? $_POST['arret_bus'] ?? '');
$mois = (int) ($_POST['mois'] ?? 0);
$montantDu = (float) ($_POST['montant_du'] ?? getDefaultTariffAmount());
$tariffId = (int) ($_POST['tariff_id'] ?? 0) ?: null;
$statutPaiement = $_POST['statut_paiement'] ?? 'impaye';
$montantPaye = (float) ($_POST['montant_paye'] ?? 0);
$yearId = (int) ($_POST['academic_year_id'] ?? 0);

$errors = [];
if (empty($nomComplet)) $errors[] = 'Le nom complet est obligatoire.';
if (!$classeId) $errors[] = 'La classe est obligatoire.';
if (empty($telephone)) $errors[] = 'Le téléphone du parent est obligatoire.';
if (empty($adresse)) $errors[] = 'L\'adresse est obligatoire.';
if (empty($arretPrecision)) $errors[] = 'Veuillez écrire votre arrêt de bus.';
if (!isset(SCHOOL_MONTHS[$mois])) $errors[] = 'Mois invalide.';
if (!$yearId) $errors[] = 'Année scolaire invalide.';

if ($errors) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Calcul montant payé selon statut
if ($statutPaiement === 'paye') {
    $montantPaye = $montantDu;
} elseif ($statutPaiement === 'impaye') {
    $montantPaye = 0;
}

$calc = calculatePaymentStatus($montantDu, $montantPaye);

// Section automatique depuis la classe choisie
if ($classeId) {
    $classeRow = getClassById($classeId);
    if ($classeRow) {
        $section = $classeRow['section'] ?? '';
    }
}

$db = getDB();
$db->beginTransaction();

try {
    // Détection doublon par nom
    $existing = findStudentByName($nomComplet, $yearId);

    if ($existing) {
        $studentId = (int) $existing['id'];
        $dossierNumber = $existing['numero_dossier'];

        // Mettre à jour les infos si nécessaire
        $stmt = $db->prepare(
            'UPDATE students SET parent_nom=?, telephone_parent=?, telephone_parent2=?, adresse=?, arret_id=?, arret_precision=?, classe_id=?, section=?, tariff_id=? WHERE id=?'
        );
        $stmt->execute([$parentNom, $telephone, $telephone2, $adresse, $arretId, $arretPrecision, $classeId, $section, $tariffId, $studentId]);

        logActivity('update_student', 'student', $studentId, "Mise à jour via formulaire: $nomComplet");
    } else {
        $dossierNumber = generateDossierNumber();
        $stmt = $db->prepare(
            'INSERT INTO students (numero_dossier, nom_complet, classe_id, section, parent_nom, telephone_parent, telephone_parent2, adresse, arret_id, arret_precision, tariff_id, academic_year_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$dossierNumber, $nomComplet, $classeId, $section, $parentNom, $telephone, $telephone2, $adresse, $arretId, $arretPrecision, $tariffId, $yearId]);
        $studentId = (int) $db->lastInsertId();

        logActivity('create_student', 'student', $studentId, "Nouvel élève via formulaire: $nomComplet ($dossierNumber)");
    }

    // Paiement du mois (upsert)
    $stmt = $db->prepare('SELECT id FROM payments WHERE student_id = ? AND mois = ? AND academic_year_id = ?');
    $stmt->execute([$studentId, $mois, $yearId]);
    $existingPayment = $stmt->fetch();

    $recuNumber = null;
    if ($montantPaye > 0) {
        $recuNumber = generateReceiptNumber();
    }

    if ($existingPayment) {
        // Fusionner les paiements (additionner)
        $stmt = $db->prepare('SELECT montant_paye, montant_du FROM payments WHERE id = ?');
        $stmt->execute([$existingPayment['id']]);
        $prev = $stmt->fetch();
        $newPaye = (float)$prev['montant_paye'] + $montantPaye;
        $newDu = max((float)$prev['montant_du'], $montantDu);
        $newCalc = calculatePaymentStatus($newDu, $newPaye);

        $stmt = $db->prepare(
            'UPDATE payments SET montant_du=?, montant_paye=?, reste=?, statut=?, numero_recu=COALESCE(?, numero_recu), date_paiement=CURDATE(), source="formulaire" WHERE id=?'
        );
        $stmt->execute([$newDu, $newPaye, $newCalc['reste'], $newCalc['statut'], $recuNumber, $existingPayment['id']]);
        $paymentId = $existingPayment['id'];
    } else {
        $stmt = $db->prepare(
            'INSERT INTO payments (student_id, academic_year_id, mois, montant_du, montant_paye, reste, statut, numero_recu, date_paiement, source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "formulaire")'
        );
        $datePaiement = $montantPaye > 0 ? date('Y-m-d') : null;
        $stmt->execute([$studentId, $yearId, $mois, $montantDu, $montantPaye, $calc['reste'], $calc['statut'], $recuNumber, $datePaiement]);
        $paymentId = (int) $db->lastInsertId();
    }

    logActivity('payment', 'payment', $paymentId, "Paiement $mois: $montantPaye/$montantDu USD pour $nomComplet");

    $db->commit();

    $redirectUrl = BASE_URL . '/confirmation.php?' . http_build_query([
        'dossier' => $dossierNumber,
        'nom' => $nomComplet,
        'mois' => $mois,
        'recu' => $recuNumber ?? '',
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Inscription enregistrée avec succès.',
        'dossier' => $dossierNumber,
        'redirect' => $redirectUrl,
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('submit_inscription: ' . $e->getMessage());
    if (DEBUG_MODE) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.']);
    }
}
