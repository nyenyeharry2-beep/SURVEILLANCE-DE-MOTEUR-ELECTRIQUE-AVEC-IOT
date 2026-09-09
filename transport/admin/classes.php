<?php
$pageTitle = 'Classes';
require_once __DIR__ . '/../includes/header_admin.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare('INSERT INTO classes (nom, section, ordre) VALUES (?, ?, ?)')
           ->execute([trim($_POST['nom']), trim($_POST['section']), (int)$_POST['ordre']]);
        flashMessage('success', 'Classe ajoutée.');
    }
    if ($action === 'edit' && !empty($_POST['id'])) {
        $db->prepare('UPDATE classes SET nom=?, section=?, ordre=?, statut=? WHERE id=?')
           ->execute([trim($_POST['nom']), trim($_POST['section']), (int)$_POST['ordre'], $_POST['statut'], (int)$_POST['id']]);
        flashMessage('success', 'Classe modifiée.');
    }
    redirect(BASE_URL . '/admin/classes.php');
}

$classes = $db->query(
    'SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.classe_id = c.id AND s.statut="actif") AS nb_eleves
     FROM classes c ORDER BY c.ordre, c.nom, c.section'
)->fetchAll();
?>

<h2 class="mb-4"><i class="bi bi-mortarboard"></i> Classes</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">Ajouter une classe</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required placeholder="Ex: 6ème année"></div>
                    <div class="mb-2"><label class="form-label">Section</label><input type="text" name="section" class="form-control" placeholder="A, B, C..."></div>
                    <div class="mb-2"><label class="form-label">Ordre</label><input type="number" name="ordre" class="form-control" value="0"></div>
                    <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark"><tr><th>Classe</th><th>Section</th><th>Ordre</th><th>Élèves</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($classes as $c): ?>
                    <tr>
                        <td><?= e($c['nom']) ?></td>
                        <td><?= e($c['section'] ?: '—') ?></td>
                        <td><?= $c['ordre'] ?></td>
                        <td><span class="badge bg-primary"><?= $c['nb_eleves'] ?></span></td>
                        <td><?= $c['statut'] === 'actif' ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editClass<?= $c['id'] ?>"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <div class="modal fade" id="editClass<?= $c['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <div class="modal-header"><h5 class="modal-title">Modifier classe</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-2"><label class="form-label">Nom</label><input type="text" name="nom" class="form-control" value="<?= e($c['nom']) ?>" required></div>
                                        <div class="mb-2"><label class="form-label">Section</label><input type="text" name="section" class="form-control" value="<?= e($c['section']) ?>"></div>
                                        <div class="mb-2"><label class="form-label">Ordre</label><input type="number" name="ordre" class="form-control" value="<?= $c['ordre'] ?>"></div>
                                        <div class="mb-2"><label class="form-label">Statut</label><select name="statut" class="form-select"><option value="actif" <?= $c['statut']==='actif'?'selected':'' ?>>Actif</option><option value="inactif">Inactif</option></select></div>
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
