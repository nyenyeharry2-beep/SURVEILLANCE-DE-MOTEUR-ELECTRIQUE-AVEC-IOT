<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/medicaments_unites.php';
requireLogin();

$db = getDB();
ensureMedicamentUnitesSchema($db);
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
            'type_unite'       => normalizeTypeUnite($_POST['type_unite'] ?? 'comprime_plaquette'),
            'prix_comprime'    => (float) ($_POST['prix_comprime'] ?? 0),
            'prix_plaquette'   => (float) ($_POST['prix_plaquette'] ?? 0),
            'prix_flacon'      => (float) ($_POST['prix_flacon'] ?? 0),
            'comprimes_par_plaquette' => max(1, (int) ($_POST['comprimes_par_plaquette'] ?? 10)),
            'quantite_stock'   => (int) ($_POST['quantite_stock'] ?? 0),
            'seuil_alerte'     => (int) ($_POST['seuil_alerte'] ?? 10),
            'date_fabrication' => $_POST['date_fabrication'] ?: null,
            'date_expiration'  => $_POST['date_expiration'] ?: null,
            'description'      => trim($_POST['description'] ?? ''),
        ];

        if ($data['type_unite'] === 'flacon') {
            if ($data['prix_flacon'] <= 0 && $data['prix_vente'] > 0) {
                $data['prix_flacon'] = $data['prix_vente'];
            }
            $data['prix_vente'] = $data['prix_flacon'];
        } else {
            if ($data['prix_comprime'] <= 0 && $data['prix_vente'] > 0) {
                $data['prix_comprime'] = $data['prix_vente'];
            }
            if ($data['prix_plaquette'] <= 0 && $data['prix_comprime'] > 0) {
                $data['prix_plaquette'] = $data['prix_comprime'] * $data['comprimes_par_plaquette'];
            }
            $data['prix_vente'] = $data['prix_comprime'];
        }

        if ($data['code'] === '' || $data['nom'] === '') {
            flash('danger', 'Le code et le nom sont obligatoires.');
        } elseif ($data['date_fabrication'] && $data['date_expiration'] && $data['date_fabrication'] > $data['date_expiration']) {
            flash('danger', 'La date de fabrication doit être antérieure à la date d\'expiration.');
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $db->prepare('INSERT INTO medicaments (code, nom, categorie_id, fournisseur_id, prix_achat, prix_vente, type_unite, prix_comprime, prix_plaquette, prix_flacon, comprimes_par_plaquette, quantite_stock, seuil_alerte, date_fabrication, date_expiration, description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$data['code'], $data['nom'], $data['categorie_id'], $data['fournisseur_id'], $data['prix_achat'], $data['prix_vente'], $data['type_unite'], $data['type_unite'] === 'flacon' ? null : $data['prix_comprime'], $data['type_unite'] === 'flacon' ? null : $data['prix_plaquette'], $data['type_unite'] === 'flacon' ? $data['prix_flacon'] : null, $data['comprimes_par_plaquette'], $data['quantite_stock'], $data['seuil_alerte'], $data['date_fabrication'], $data['date_expiration'], $data['description']]);
                    flash('success', 'Médicament ajouté avec succès.');
                } else {
                    $id = (int) $_POST['id'];
                    $stmt = $db->prepare('UPDATE medicaments SET code=?, nom=?, categorie_id=?, fournisseur_id=?, prix_achat=?, prix_vente=?, type_unite=?, prix_comprime=?, prix_plaquette=?, prix_flacon=?, comprimes_par_plaquette=?, quantite_stock=?, seuil_alerte=?, date_fabrication=?, date_expiration=?, description=? WHERE id=?');
                    $stmt->execute([$data['code'], $data['nom'], $data['categorie_id'], $data['fournisseur_id'], $data['prix_achat'], $data['prix_vente'], $data['type_unite'], $data['type_unite'] === 'flacon' ? null : $data['prix_comprime'], $data['type_unite'] === 'flacon' ? null : $data['prix_plaquette'], $data['type_unite'] === 'flacon' ? $data['prix_flacon'] : null, $data['comprimes_par_plaquette'], $data['quantite_stock'], $data['seuil_alerte'], $data['date_fabrication'], $data['date_expiration'], $data['description'], $id]);
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
    <div class="d-flex gap-2">
        <?php if ((currentUser()['role'] ?? '') === 'admin'): ?>
        <a href="medicaments_import.php" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
        </a>
        <?php endif; ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medModal">
            <i class="bi bi-plus-lg me-1"></i> Ajouter
        </button>
    </div>
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
                    <th>Code</th><th>Nom</th><th>Catégorie</th><th>Type / Prix</th>
                    <th>Stock</th><th>Fabrication</th><th>Expiration</th><th>Statut</th><th class="table-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($medicaments as $m):
                $m = enrichMedicamentRow($m);
            ?>
            <tr>
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><?= e($m['categorie_nom'] ?? '—') ?></td>
                <td>
                    <?php if ($m['type_unite'] === 'flacon'): ?>
                    <span class="badge bg-info text-dark">Flacon</span>
                    <?= formatCDF((float) $m['prix_flacon']) ?> / fl
                    <?php else: ?>
                    <span class="badge bg-primary">Comprimé</span>
                    <?= formatCDF((float) $m['prix_comprime']) ?> / cp
                    <br><small class="text-muted"><?= formatCDF((float) $m['prix_plaquette']) ?> / plt (<?= (int) $m['comprimes_par_plaquette'] ?> cp)</small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($m['quantite_stock'] <= $m['seuil_alerte']): ?>
                    <span class="badge bg-danger"><?= e($m['stock_label']) ?></span>
                    <?php else: ?>
                    <?= e($m['stock_label']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $m['date_fabrication'] ? formatDate($m['date_fabrication']) : '—' ?>
                </td>
                <td>
                    <?php if ($m['date_expiration']): ?>
                        <?= formatDate($m['date_expiration']) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if ($m['date_expiration']): ?>
                    <span class="badge <?= expirationStatusClass($m['date_expiration']) ?>">
                        <?= e(expirationStatusLabel($m['date_expiration'])) ?>
                    </span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Non renseigné</span>
                    <?php endif; ?>
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
            <tr><td colspan="9" class="text-center text-muted py-4">Aucun médicament trouvé.</td></tr>
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
                        <div class="col-md-4">
                            <label class="form-label">Type de vente *</label>
                            <select name="type_unite" class="form-select" id="type-unite">
                                <option value="comprime_plaquette" <?= ($editMed['type_unite'] ?? 'comprime_plaquette') === 'comprime_plaquette' ? 'selected' : '' ?>>Comprimé / Plaquette</option>
                                <option value="flacon" <?= ($editMed['type_unite'] ?? '') === 'flacon' ? 'selected' : '' ?>>Flacon</option>
                            </select>
                        </div>
                        <div class="col-md-3 prix-comprime-field">
                            <label class="form-label">Prix / comprimé (FC)</label>
                            <input type="number" step="0.01" name="prix_comprime" class="form-control" value="<?= e($editMed['prix_comprime'] ?? $editMed['prix_vente'] ?? '0') ?>">
                        </div>
                        <div class="col-md-3 prix-comprime-field">
                            <label class="form-label">Prix / plaquette (FC)</label>
                            <input type="number" step="0.01" name="prix_plaquette" class="form-control" value="<?= e($editMed['prix_plaquette'] ?? '0') ?>">
                        </div>
                        <div class="col-md-2 prix-comprime-field">
                            <label class="form-label">Cp / plaquette</label>
                            <input type="number" name="comprimes_par_plaquette" class="form-control" value="<?= e($editMed['comprimes_par_plaquette'] ?? '10') ?>">
                        </div>
                        <div class="col-md-3 prix-flacon-field">
                            <label class="form-label">Prix / flacon (FC)</label>
                            <input type="number" step="0.01" name="prix_flacon" class="form-control" value="<?= e($editMed['prix_flacon'] ?? $editMed['prix_vente'] ?? '0') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prix achat</label>
                            <input type="number" step="0.01" name="prix_achat" class="form-control" value="<?= e($editMed['prix_achat'] ?? '0') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prix vente (réf.)</label>
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
                            <label class="form-label">Date fabrication</label>
                            <input type="date" name="date_fabrication" class="form-control" value="<?= e($editMed['date_fabrication'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date expiration *</label>
                            <input type="date" name="date_expiration" class="form-control" value="<?= e($editMed['date_expiration'] ?? '') ?>">
                            <small class="text-muted">Alerte <?= getAlerteExpirationMois() ?> mois avant</small>
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
<script>
(function () {
    const typeSelect = document.getElementById('type-unite');
    if (!typeSelect) return;
    function toggleTypeFields() {
        const flacon = typeSelect.value === 'flacon';
        document.querySelectorAll('.prix-comprime-field').forEach(el => el.style.display = flacon ? 'none' : '');
        document.querySelectorAll('.prix-flacon-field').forEach(el => el.style.display = flacon ? '' : 'none');
    }
    typeSelect.addEventListener('change', toggleTypeFields);
    toggleTypeFields();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
