<?php
$pageTitle = 'Tarifs';
require_once __DIR__ . '/../includes/header_admin.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        if ($isDefault) $db->exec('UPDATE tariffs SET is_default = 0');
        $db->prepare('INSERT INTO tariffs (nom, montant, devise, is_default) VALUES (?, ?, ?, ?)')
           ->execute([trim($_POST['nom']), (float)$_POST['montant'], 'USD', $isDefault]);
        flashMessage('success', 'Tarif ajouté.');
    }
    if ($action === 'edit' && !empty($_POST['id'])) {
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        if ($isDefault) $db->exec('UPDATE tariffs SET is_default = 0');
        $db->prepare('UPDATE tariffs SET nom=?, montant=?, is_default=?, statut=? WHERE id=?')
           ->execute([trim($_POST['nom']), (float)$_POST['montant'], $isDefault, $_POST['statut'], (int)$_POST['id']]);
        flashMessage('success', 'Tarif modifié.');
    }
    redirect(BASE_URL . '/admin/tariffs.php');
}

$tariffs = getActiveTariffs();
$allTariffs = $db->query('SELECT * FROM tariffs ORDER BY is_default DESC, nom')->fetchAll();
?>

<h2 class="mb-4"><i class="bi bi-tag"></i> Tarifs de transport</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">Ajouter un tarif</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required placeholder="Ex: Zone 1"></div>
                    <div class="mb-2"><label class="form-label">Montant (USD) *</label><input type="number" name="montant" class="form-control" step="0.01" required value="50"></div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_default" class="form-check-input" id="defNew">
                        <label class="form-check-label" for="defNew">Tarif par défaut</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark"><tr><th>Catégorie</th><th>Tarif</th><th>Défaut</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($allTariffs as $t): ?>
                    <tr>
                        <td><?= e($t['nom']) ?></td>
                        <td><strong><?= number_format($t['montant'], 0) ?> USD</strong></td>
                        <td><?= $t['is_default'] ? '<span class="badge bg-primary">Défaut</span>' : '' ?></td>
                        <td><?= $t['statut'] === 'actif' ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                        <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTariff<?= $t['id'] ?>"><i class="bi bi-pencil"></i></button></td>
                    </tr>
                    <div class="modal fade" id="editTariff<?= $t['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <div class="modal-header"><h5 class="modal-title">Modifier tarif</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-2"><label class="form-label">Nom</label><input type="text" name="nom" class="form-control" value="<?= e($t['nom']) ?>" required></div>
                                        <div class="mb-2"><label class="form-label">Montant</label><input type="number" name="montant" class="form-control" step="0.01" value="<?= $t['montant'] ?>"></div>
                                        <div class="form-check mb-2"><input type="checkbox" name="is_default" class="form-check-input" <?= $t['is_default']?'checked':'' ?>><label class="form-check-label">Par défaut</label></div>
                                        <div class="mb-2"><label class="form-label">Statut</label><select name="statut" class="form-select"><option value="actif" <?= $t['statut']==='actif'?'selected':'' ?>>Actif</option><option value="inactif">Inactif</option></select></div>
                                    </div>
                                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
