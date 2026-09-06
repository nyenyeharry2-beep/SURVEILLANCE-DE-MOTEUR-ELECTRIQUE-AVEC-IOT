<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($nom === '') {
            flash('danger', 'Le nom est obligatoire.');
        } else {
            try {
                $db->prepare('INSERT INTO categories (nom, description) VALUES (?, ?)')->execute([$nom, $description]);
                flash('success', 'Catégorie créée.');
            } catch (PDOException $e) {
                flash('danger', 'Cette catégorie existe déjà.');
            }
        }
    }

    if ($action === 'update') {
        $id = (int) $_POST['id'];
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        try {
            $db->prepare('UPDATE categories SET nom = ?, description = ? WHERE id = ?')->execute([$nom, $description, $id]);
            flash('success', 'Catégorie mise à jour.');
        } catch (PDOException $e) {
            flash('danger', 'Nom déjà utilisé.');
        }
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash('success', 'Catégorie supprimée.');
    }

    redirect('categories.php');
}

$categories = $db->query('SELECT c.*, COUNT(m.id) AS nb_medicaments FROM categories c LEFT JOIN medicaments m ON m.categorie_id = c.id AND m.actif = 1 GROUP BY c.id ORDER BY c.nom')->fetchAll();

$pageTitle = 'Catégories';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-tags me-2"></i>Catégories</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#catModal"><i class="bi bi-plus-lg me-1"></i> Ajouter</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nom</th><th>Description</th><th>Médicaments</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><?= e($c['nom']) ?></td>
                <td><?= e($c['description'] ?: '—') ?></td>
                <td><span class="badge bg-secondary"><?= $c['nb_medicaments'] ?></span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCat<?= $c['id'] ?>"><i class="bi bi-pencil"></i></button>
                    <form method="post" class="d-inline" data-confirm="Supprimer cette catégorie ?">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($categories as $c): ?>
<div class="modal fade" id="editCat<?= $c['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <div class="modal-header"><h5 class="modal-title">Modifier la catégorie</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nom</label><input type="text" name="nom" class="form-control" required value="<?= e($c['nom']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= e($c['description']) ?></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-header"><h5 class="modal-title">Nouvelle catégorie</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Créer</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
