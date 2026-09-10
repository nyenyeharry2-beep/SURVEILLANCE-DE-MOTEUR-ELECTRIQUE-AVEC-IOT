<?php
$pageTitle = 'Fiche élève';
require_once __DIR__ . '/../includes/header_admin.php';

$yearId = getSelectedYearId();
$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$classes = getActiveClasses();
$stops = getActiveStops();
$tariffs = getActiveTariffs();

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'save_student') {
        $data = [
            'nom_complet' => trim($_POST['nom_complet'] ?? ''),
            'classe_id' => (int) ($_POST['classe_id'] ?? 0) ?: null,
            'section' => '',
            'parent_nom' => trim($_POST['parent_nom'] ?? ''),
            'telephone_parent' => trim($_POST['telephone_parent'] ?? ''),
            'telephone_parent2' => trim($_POST['telephone_parent2'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'arret_id' => (int) ($_POST['arret_id'] ?? 0) ?: null,
            'arret_precision' => trim($_POST['arret_precision'] ?? ''),
            'tariff_id' => (int) ($_POST['tariff_id'] ?? 0) ?: null,
            'verified' => isset($_POST['verified']) ? 1 : 0,
        ];

        if ($data['classe_id']) {
            $cl = getClassById((int)$data['classe_id']);
            $data['section'] = $cl['section'] ?? '';
        }

        if ($id) {
            $stmt = $db->prepare(
                'UPDATE students SET nom_complet=?, classe_id=?, section=?, parent_nom=?, telephone_parent=?, telephone_parent2=?, adresse=?, arret_id=?, arret_precision=?, tariff_id=?, verified=? WHERE id=?'
            );
            $stmt->execute([...array_values($data), $id]);
            logActivity('update_student', 'student', $id, 'Modification fiche élève');
            flashMessage('success', 'Fiche mise à jour.');
        } else {
            $dossier = generateDossierNumber();
            $stmt = $db->prepare(
                'INSERT INTO students (numero_dossier, nom_complet, classe_id, section, parent_nom, telephone_parent, telephone_parent2, adresse, arret_id, arret_precision, tariff_id, academic_year_id, verified)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$dossier, $data['nom_complet'], $data['classe_id'], $data['section'], $data['parent_nom'], $data['telephone_parent'], $data['telephone_parent2'], $data['adresse'], $data['arret_id'], $data['arret_precision'], $data['tariff_id'], $yearId, $data['verified']]);
            $id = (int) $db->lastInsertId();
            logActivity('create_student', 'student', $id, "Création manuelle: {$data['nom_complet']}");
            flashMessage('success', 'Élève créé.');
        }
        redirect(BASE_URL . '/admin/student.php?id=' . $id);
    }

    if ($postAction === 'save_payment' && $id) {
        $mois = (int) ($_POST['mois'] ?? 0);
        $montantDu = (float) ($_POST['montant_du'] ?? 0);
        $montantPaye = (float) ($_POST['montant_paye'] ?? 0);
        $mode = trim($_POST['mode_paiement'] ?? '');
        $obs = trim($_POST['observation'] ?? '');
        $verifiedOk = isset($_POST['verified_ok']) ? 1 : 0;
        $calc = calculatePaymentStatus($montantDu, $montantPaye);
        $recu = $montantPaye > 0 ? generateReceiptNumber() : null;

        $stmt = $db->prepare('SELECT id FROM payments WHERE student_id=? AND mois=? AND academic_year_id=?');
        $stmt->execute([$id, $mois, $yearId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $db->prepare(
                'UPDATE payments SET montant_du=?, montant_paye=?, reste=?, statut=?, numero_recu=COALESCE(?, numero_recu), date_paiement=?, mode_paiement=?, observation=?, verified_ok=?, source="admin" WHERE id=?'
            )->execute([$montantDu, $montantPaye, $calc['reste'], $calc['statut'], $recu, $montantPaye > 0 ? date('Y-m-d') : null, $mode, $obs, $verifiedOk, $existing['id']]);
        } else {
            $db->prepare(
                'INSERT INTO payments (student_id, academic_year_id, mois, montant_du, montant_paye, reste, statut, numero_recu, date_paiement, mode_paiement, observation, verified_ok, source)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "admin")'
            )->execute([$id, $yearId, $mois, $montantDu, $montantPaye, $calc['reste'], $calc['statut'], $recu, $montantPaye > 0 ? date('Y-m-d') : null, $mode, $obs, $verifiedOk]);
        }
        logActivity('payment', 'student', $id, "Paiement mois $mois: $montantPaye USD");
        flashMessage('success', 'Paiement enregistré.');
        redirect(BASE_URL . '/admin/student.php?id=' . $id);
    }
}

$student = $id ? getStudentById($id) : null;
$payments = $id ? getStudentPaymentsMatrix($id, $yearId) : [];
$defaultTariff = getDefaultTariff();
$isNew = ($action === 'add' && !$id);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person"></i> <?= $isNew ? 'Nouvel élève' : e($student['nom_complet'] ?? 'Fiche élève') ?></h2>
    <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<?php if (!$student && !$isNew): ?>
<div class="alert alert-danger">Élève introuvable.</div>
<?php else: ?>

<div class="admin-form-logo no-print"><?= renderSchoolLogo('medium') ?></div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">Informations</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="save_student">
                    <?php if ($student): ?>
                    <p class="small text-muted">Dossier: <code><?= e($student['numero_dossier']) ?></code></p>
                    <?php endif; ?>
                    <div class="mb-2">
                        <label class="form-label">Nom complet *</label>
                        <input type="text" name="nom_complet" class="form-control" required value="<?= e($student['nom_complet'] ?? '') ?>">
                    </div>
                    <div class="row mb-2">
                        <div class="col-8">
                            <label class="form-label">Classe</label>
                            <select name="classe_id" class="form-select">
                                <option value="">—</option>
                                <?php foreach (getClassesGroupedBySection() as $sectionName => $sectionClasses): ?>
                                <optgroup label="<?= e($sectionName) ?>">
                                    <?php foreach ($sectionClasses as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($student['classe_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e(formatClassName($c)) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($student['section']) || !empty($student['classe_section'])): ?>
                        <div class="col-4">
                            <label class="form-label">Section</label>
                            <input type="text" class="form-control" value="<?= e($student['section'] ?? $student['classe_section'] ?? '') ?>" readonly>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Parent / Tuteur</label>
                        <input type="text" name="parent_nom" class="form-control" value="<?= e($student['parent_nom'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone_parent" class="form-control" value="<?= e($student['telephone_parent'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Téléphone 2</label>
                        <input type="text" name="telephone_parent2" class="form-control" value="<?= e($student['telephone_parent2'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" class="form-control" rows="2"><?= e($student['adresse'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Arrêt</label>
                        <select name="arret_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($stops as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($student['arret_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Précision arrêt</label>
                        <input type="text" name="arret_precision" class="form-control" value="<?= e($student['arret_precision'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tarif</label>
                        <select name="tariff_id" class="form-select">
                            <?php foreach ($tariffs as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($student['tariff_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= e($t['nom']) ?> — <?= $t['montant'] ?> USD</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="verified" class="form-check-input" id="verified" <?= ($student['verified'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="verified">Vérifié OK ✓</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($student): ?>
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header">Situation financière — <?= e(getAcademicYearById($yearId)['label'] ?? '') ?></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Mois</th><th>Frais</th><th>Payé</th><th>Reste</th><th>Statut</th><th>OK</th></tr></thead>
                    <tbody>
                    <?php foreach (SCHOOL_MONTHS as $num => $info):
                        $p = $payments[$num] ?? null;
                    ?>
                    <tr>
                        <td><?= e($info['label']) ?></td>
                        <td><?= $p ? number_format($p['montant_du'], 0) . '$' : '—' ?></td>
                        <td><?= $p ? number_format($p['montant_paye'], 0) . '$' : '—' ?></td>
                        <td><?= $p ? number_format($p['reste'], 0) . '$' : '—' ?></td>
                        <td><?= $p ? getPaymentStatusBadge($p['statut']) : '—' ?></td>
                        <td><?= ($p && $p['verified_ok']) ? '✅' : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Enregistrer / Modifier un paiement</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="save_payment">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Mois</label>
                            <select name="mois" class="form-select" required>
                                <?php foreach (SCHOOL_MONTHS as $num => $info): ?>
                                <option value="<?= $num ?>"><?= e($info['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Frais dus</label>
                            <input type="number" name="montant_du" class="form-control" step="0.01" value="<?= getDefaultTariffAmount() ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Montant payé</label>
                            <input type="number" name="montant_paye" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mode paiement</label>
                            <select name="mode_paiement" class="form-select">
                                <option value="Espèces">Espèces</option>
                                <option value="Mobile Money">Mobile Money</option>
                                <option value="Virement">Virement</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Observation</label>
                            <input type="text" name="observation" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="verified_ok" class="form-check-input" id="payOk">
                                <label class="form-check-label" for="payOk">Marquer OK (vérifié par admin)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success"><i class="bi bi-cash"></i> Enregistrer paiement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
