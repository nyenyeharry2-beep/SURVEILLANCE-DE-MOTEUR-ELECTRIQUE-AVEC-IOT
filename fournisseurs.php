<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'nom'       => trim($_POST['nom'] ?? ''),
            'contact'   => trim($_POST['contact'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'adresse'   => trim($_POST['adresse'] ?? ''),
        ];
        if ($data['nom'] === '') {
            flash('danger', 'Le nom est obligatoire.');
        } else {
            $db->prepare('INSERT INTO fournisseurs (nom, contact, telephone, email, adresse) VALUES (?,?,?,?,?)')
               ->execute(array_values($data));
            flash('success', 'Fournisseur ajouté.');
        }
    }

    if ($action === 'update') {
        $id = (int) $_POST['id'];
        $db->prepare('UPDATE fournisseurs SET nom=?, contact=?, telephone=?, email=?, adresse=? WHERE id=?')
           ->execute([
               trim($_POST['nom'] ?? ''),
               trim($_POST['contact'] ?? ''),
               trim($_POST['telephone'] ?? ''),
               trim($_POST['email'] ?? ''),
               trim($_POST['adresse'] ?? ''),
               $id,
           ]);
        flash('success', 'Fournisseur mis à jour.');
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $db->prepare('DELETE FROM fournisseurs WHERE id = ?')->execute([$id]);
        flash('success', 'Fournisseur supprimé.');
    }

    redirect('fournisseurs.php');
}

$fournisseurs = $db->query('SELECT f.*, COUNT(m.id) AS nb_medicaments FROM fournisseurs f LEFT JOIN medicaments m ON m.fournisseur_id = f.id AND m.actif = 1 GROUP BY f.id ORDER BY f.nom')->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM fournisseurs WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editItem = $stmt->fetch();
}

$pageTitle = 'Fournisseurs';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-truck me-2"></i>Fournisseurs</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fourModal"><i class="bi bi-plus-lg me-1"></i> Ajouter</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nom</th><th>Contact</th><th>Téléphone</th><th>Email</th><th>Médicaments</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($fournisseurs as $f): ?>
            <tr>
                <td><?= e($f['nom']) ?></td>
                <td><?= e($f['contact'] ?: '—') ?></td>
                <td><?= e($f['telephone'] ?: '—') ?></td>
                <td><?= e($f['email'] ?: '—') ?></td>
                <td><span class="badge bg-secondary"><?= $f['nb_medicaments'] ?></span></td>
                <td>
                    <a href="?edit=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <form method="post" class="d-inline" data-confirm="Supprimer ce fournisseur ?">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="fourModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
                <?php if ($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif; ?>
                <div class="modal-header"><h5 class="modal-title"><?= $editItem ? 'Modifier' : 'Nouveau' ?> fournisseur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required value="<?= e($editItem['nom'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Contact</label><input type="text" name="contact" class="form-control" value="<?= e($editItem['contact'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control" value="<?= e($editItem['telephone'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($editItem['email'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Adresse</label><textarea name="adresse" class="form-control" rows="2"><?= e($editItem['adresse'] ?? '') ?></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>

<?php if ($editItem): ?>
<script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('fourModal')).show());</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
