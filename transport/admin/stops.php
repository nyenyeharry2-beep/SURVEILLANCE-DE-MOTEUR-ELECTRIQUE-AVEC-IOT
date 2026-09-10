<?php
$pageTitle = 'Arrêts de bus';
require_once __DIR__ . '/../includes/header_admin.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare('INSERT INTO bus_stops (nom, adresse, description) VALUES (?, ?, ?)')
           ->execute([trim($_POST['nom']), trim($_POST['adresse']), trim($_POST['description'])]);
        logActivity('create_stop', 'bus_stop', (int)$db->lastInsertId(), 'Nouvel arrêt');
        flashMessage('success', 'Arrêt ajouté.');
    }
    if ($action === 'edit' && !empty($_POST['id'])) {
        $db->prepare('UPDATE bus_stops SET nom=?, adresse=?, description=?, statut=? WHERE id=?')
           ->execute([trim($_POST['nom']), trim($_POST['adresse']), trim($_POST['description']), $_POST['statut'], (int)$_POST['id']]);
        flashMessage('success', 'Arrêt modifié.');
    }
    if ($action === 'delete' && !empty($_POST['id'])) {
        $db->prepare('UPDATE bus_stops SET statut="inactif" WHERE id=?')->execute([(int)$_POST['id']]);
        flashMessage('success', 'Arrêt désactivé.');
    }
    redirect(BASE_URL . '/admin/stops.php');
}

$stops = $db->query(
    'SELECT bs.*, (SELECT COUNT(*) FROM students s WHERE s.arret_id = bs.id AND s.statut="actif") AS nb_eleves
     FROM bus_stops bs ORDER BY bs.nom'
)->fetchAll();
?>

<h2 class="mb-4"><i class="bi bi-geo-alt"></i> Arrêts de bus</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">Ajouter un arrêt</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
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
                    <thead class="table-dark"><tr><th>Arrêt</th><th>Adresse</th><th>Élèves</th><th>Statut</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($stops as $s): ?>
                    <tr>
                        <td><strong><?= e($s['nom']) ?></strong></td>
                        <td><?= e($s['adresse'] ?: '—') ?></td>
                        <td><span class="badge bg-primary"><?= $s['nb_eleves'] ?> élèves</span></td>
                        <td><?= $s['statut'] === 'actif' ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <div class="modal-header"><h5 class="modal-title">Modifier: <?= e($s['nom']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-2"><label class="form-label">Nom</label><input type="text" name="nom" class="form-control" value="<?= e($s['nom']) ?>" required></div>
                                        <div class="mb-2"><label class="form-label">Adresse</label><input type="text" name="adresse" class="form-control" value="<?= e($s['adresse']) ?>"></div>
                                        <div class="mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= e($s['description']) ?></textarea></div>
                                        <div class="mb-2"><label class="form-label">Statut</label>
                                            <select name="statut" class="form-select"><option value="actif" <?= $s['statut']==='actif'?'selected':'' ?>>Actif</option><option value="inactif" <?= $s['statut']==='inactif'?'selected':'' ?>>Inactif</option></select>
                                        </div>
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
