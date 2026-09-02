<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'pharmacien';

        if ($nom === '' || $email === '' || strlen($password) < 6) {
            flash('danger', 'Nom, email et mot de passe (6+ caractères) requis.');
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?,?,?,?)')
                   ->execute([$nom, $email, $hash, $role]);
                flash('success', 'Utilisateur créé.');
            } catch (PDOException $e) {
                flash('danger', 'Cet email est déjà utilisé.');
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int) $_POST['id'];
        if ($id !== currentUser()['id']) {
            $db->prepare('UPDATE utilisateurs SET actif = NOT actif WHERE id = ?')->execute([$id]);
            flash('success', 'Statut utilisateur modifié.');
        } else {
            flash('danger', 'Vous ne pouvez pas désactiver votre propre compte.');
        }
    }

    if ($action === 'reset_password') {
        $id = (int) $_POST['id'];
        $password = $_POST['password'] ?? '';
        if (strlen($password) >= 6) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')->execute([$hash, $id]);
            flash('success', 'Mot de passe réinitialisé.');
        } else {
            flash('danger', 'Le mot de passe doit contenir au moins 6 caractères.');
        }
    }

    redirect('utilisateurs.php');
}

$utilisateurs = $db->query('SELECT id, nom, email, role, actif, created_at FROM utilisateurs ORDER BY nom')->fetchAll();

$pageTitle = 'Utilisateurs';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>Utilisateurs</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-plus-lg me-1"></i> Ajouter</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Créé le</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($utilisateurs as $u): ?>
            <tr>
                <td><?= e($u['nom']) ?><?= $u['id'] === currentUser()['id'] ? ' <span class="badge bg-info">Vous</span>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge bg-secondary"><?= e($u['role']) ?></span></td>
                <td><?= $u['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-danger">Inactif</span>' ?></td>
                <td><?= formatDate($u['created_at']) ?></td>
                <td>
                    <?php if ($u['id'] !== currentUser()['id']): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm btn-outline-warning" title="Activer/Désactiver"><i class="bi bi-toggle-on"></i></button>
                    </form>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pwdModal<?= $u['id'] ?>"><i class="bi bi-key"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-header"><h5 class="modal-title">Nouvel utilisateur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nom *</label><input type="text" name="nom" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Mot de passe *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role" class="form-select">
                            <option value="pharmacien">Pharmacien</option>
                            <option value="caissier">Caissier</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Créer</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
