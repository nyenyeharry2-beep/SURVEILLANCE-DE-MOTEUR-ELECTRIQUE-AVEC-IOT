<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$search = trim($_GET['q'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $data = [
            'code'             => trim($_POST['code'] ?? ''),
            'nom'              => trim($_POST['nom'] ?? ''),
            'categorie_id'     => $_POST['categorie_id'] ?: null,
            'fournisseur_id'   => $_POST['fournisseur_id'] ?: null,
            'prix_achat'       => (float) ($_POST['prix_achat'] ?? 0),
            'prix_vente'       => (float) ($_POST['prix_vente'] ?? 0),
            'quantite_stock'   => (int) ($_POST['quantite_stock'] ?? 0),
            'seuil_alerte'     => (int) ($_POST['seuil_alerte'] ?? 10),
            'date_expiration'  => $_POST['date_expiration'] ?: null,
            'description'      => trim($_POST['description'] ?? ''),
        ];

        if ($data['code'] === '' || $data['nom'] === '') {
            flash('danger', 'Le code et le nom sont obligatoires.');
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $db->prepare('INSERT INTO medicaments (code, nom, categorie_id, fournisseur_id, prix_achat, prix_vente, quantite_stock, seuil_alerte, date_expiration, description) VALUES (?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$data['code'], $data['nom'], $data['categorie_id'], $data['fournisseur_id'], $data['prix_achat'], $data['prix_vente'], $data['quantite_stock'], $data['seuil_alerte'], $data['date_expiration'], $data['description']]);
                    flash('success', 'Médicament ajouté avec succès.');
                } else {
                    $id = (int) $_POST['id'];
                    $stmt = $db->prepare('UPDATE medicaments SET code=?, nom=?, categorie_id=?, fournisseur_id=?, prix_achat=?, prix_vente=?, quantite_stock=?, seuil_alerte=?, date_expiration=?, description=? WHERE id=?');
                    $stmt->execute([$data['code'], $data['nom'], $data['categorie_id'], $data['fournisseur_id'], $data['prix_achat'], $data['prix_vente'], $data['quantite_stock'], $data['seuil_alerte'], $data['date_expiration'], $data['description'], $id]);
                    flash('success', 'Médicament mis à jour.');
                }
            } catch (PDOException $e) {
                flash('danger', 'Erreur : ce code existe déjà ou données invalides.');
            }
        }
        redirect('medicaments.php');
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $db->prepare('UPDATE medicaments SET actif = 0 WHERE id = ?')->execute([$id]);
        flash('success', 'Médicament désactivé.');
        redirect('medicaments.php');
    }
}

$sql = 'SELECT m.*, c.nom AS categorie_nom, f.nom AS fournisseur_nom
        FROM medicaments m
        LEFT JOIN categories c ON c.id = m.categorie_id
        LEFT JOIN fournisseurs f ON f.id = m.fournisseur_id
        WHERE m.actif = 1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (m.nom LIKE ? OR m.code LIKE ?)';
    $params = ["%$search%", "%$search%"];
}
$sql .= ' ORDER BY m.nom ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$medicaments = $stmt->fetchAll();

$categories = $db->query('SELECT id, nom FROM categories ORDER BY nom')->fetchAll();
$fournisseurs = $db->query('SELECT id, nom FROM fournisseurs ORDER BY nom')->fetchAll();

$editMed = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM medicaments WHERE id = ? AND actif = 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editMed = $stmt->fetch();
}

$pageTitle = 'Médicaments';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-box-seam me-2"></i>Médicaments</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medModal">
        <i class="bi bi-plus-lg me-1"></i> Ajouter
    </button>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" class="form-control" placeholder="Rechercher par nom ou code..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Rechercher</button>
                <?php if ($search): ?><a href="medicaments.php" class="btn btn-outline-secondary">Effacer</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th><th>Nom</th><th>Catégorie</th><th>Prix vente</th>
                    <th>Stock</th><th>Expiration</th><th class="table-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($medicaments as $m): ?>
            <tr>
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><?= e($m['categorie_nom'] ?? '—') ?></td>
                <td><?= formatMoney((float) $m['prix_vente']) ?></td>
                <td>
                    <?php if ($m['quantite_stock'] <= $m['seuil_alerte']): ?>
                    <span class="badge bg-danger"><?= $m['quantite_stock'] ?></span>
                    <?php else: ?>
                    <?= $m['quantite_stock'] ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($m['date_expiration']): ?>
                        <?= formatDate($m['date_expiration']) ?>
                        <?php if (isExpired($m['date_expiration'])): ?>
                        <span class="badge badge-expired ms-1">Expiré</span>
                        <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="table-actions">
                    <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <form method="post" class="d-inline" data-confirm="Désactiver ce médicament ?">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($medicaments)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Aucun médicament trouvé.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="medModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="<?= $editMed ? 'update' : 'create' ?>">
                <?php if ($editMed): ?><input type="hidden" name="id" value="<?= $editMed['id'] ?>"><?php endif; ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editMed ? 'Modifier' : 'Ajouter' ?> un médicament</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code *</label>
                            <input type="text" name="code" class="form-control" required value="<?= e($editMed['code'] ?? '') ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required value="<?= e($editMed['nom'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie_id" class="form-select">
                                <option value="">— Aucune —</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($editMed['categorie_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fournisseur</label>
                            <select name="fournisseur_id" class="form-select">
                                <option value="">— Aucun —</option>
                                <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= ($editMed['fournisseur_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= e($f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prix achat</label>
                            <input type="number" step="0.01" name="prix_achat" class="form-control" value="<?= e($editMed['prix_achat'] ?? '0') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prix vente</label>
                            <input type="number" step="0.01" name="prix_vente" class="form-control" value="<?= e($editMed['prix_vente'] ?? '0') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="quantite_stock" class="form-control" value="<?= e($editMed['quantite_stock'] ?? '0') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Seuil alerte</label>
                            <input type="number" name="seuil_alerte" class="form-control" value="<?= e($editMed['seuil_alerte'] ?? '10') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date expiration</label>
                            <input type="date" name="date_expiration" class="form-control" value="<?= e($editMed['date_expiration'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= e($editMed['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editMed): ?>
<script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('medModal')).show());</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
