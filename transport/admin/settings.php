<?php
$pageTitle = 'Paramètres';
require_once __DIR__ . '/../includes/header_admin.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $keys = ['school_name','school_foundation','school_project','school_address','school_quarter','school_city','school_email','school_phone','default_tariff','default_currency','receipt_prefix','dossier_prefix'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) setSetting($key, trim($_POST[$key]));
        }
        logActivity('update_settings', 'settings', null, 'Paramètres mis à jour');
        flashMessage('success', 'Paramètres enregistrés.');
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $user = getCurrentUser();
        $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();
        if (password_verify($current, $hash) && strlen($new) >= 6) {
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flashMessage('success', 'Mot de passe modifié.');
        } else {
            flashMessage('error', 'Mot de passe actuel incorrect ou nouveau trop court (min 6 car.).');
        }
    }

    if ($action === 'add_year') {
        $label = trim($_POST['year_label'] ?? '');
        if ($label) {
            $db->prepare('INSERT INTO academic_years (label, is_active) VALUES (?, 0)')->execute([$label]);
            flashMessage('success', 'Année scolaire ajoutée.');
        }
    }

    if ($action === 'set_active_year' && !empty($_POST['year_id'])) {
        $db->exec('UPDATE academic_years SET is_active = 0');
        $db->prepare('UPDATE academic_years SET is_active = 1 WHERE id = ?')->execute([(int)$_POST['year_id']]);
        $year = getAcademicYearById((int)$_POST['year_id']);
        if ($year) setSetting('active_academic_year', $year['label']);
        flashMessage('success', 'Année scolaire activée.');
    }

    redirect(BASE_URL . '/admin/settings.php');
}

$settings = getAllSettings();
$years = getAllAcademicYears();
?>

<h2 class="mb-4"><i class="bi bi-gear"></i> Paramètres</h2>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">Informations de l'établissement</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save_settings">
                    <div class="mb-2"><label class="form-label">Fondation</label><input type="text" name="school_foundation" class="form-control" value="<?= e($settings['school_foundation'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label">Projet</label><input type="text" name="school_project" class="form-control" value="<?= e($settings['school_project'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label">Nom de l'école</label><input type="text" name="school_name" class="form-control" value="<?= e($settings['school_name'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label">Adresse</label><input type="text" name="school_address" class="form-control" value="<?= e($settings['school_address'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label">Quartier</label><input type="text" name="school_quarter" class="form-control" value="<?= e($settings['school_quarter'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label">Ville</label><input type="text" name="school_city" class="form-control" value="<?= e($settings['school_city'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" name="school_email" class="form-control" value="<?= e($settings['school_email'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label">Téléphone</label><input type="text" name="school_phone" class="form-control" value="<?= e($settings['school_phone'] ?? '') ?>"></div>
                    <div class="row mb-2">
                        <div class="col-6"><label class="form-label">Préfixe dossier</label><input type="text" name="dossier_prefix" class="form-control" value="<?= e($settings['dossier_prefix'] ?? 'BUS') ?>"></div>
                        <div class="col-6"><label class="form-label">Préfixe reçu</label><input type="text" name="receipt_prefix" class="form-control" value="<?= e($settings['receipt_prefix'] ?? 'BUS') ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">Années scolaires</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Année</th><th>Active</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($years as $y): ?>
                    <tr>
                        <td><?= e($y['label']) ?></td>
                        <td><?= $y['is_active'] ? '<span class="badge bg-success">Active</span>' : '' ?></td>
                        <td>
                            <?php if (!$y['is_active']): ?>
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="set_active_year">
                                <input type="hidden" name="year_id" value="<?= $y['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Activer</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="POST" class="d-flex gap-2 mt-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_year">
                    <input type="text" name="year_label" class="form-control form-control-sm" placeholder="Ex: 2027-2028">
                    <button type="submit" class="btn btn-sm btn-primary">Ajouter</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Changer le mot de passe</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-2"><label class="form-label">Mot de passe actuel</label><input type="password" name="current_password" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Nouveau mot de passe</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-key"></i> Changer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
