<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/caisse.php';
requireLogin();

$db = getDB();
$date = $_GET['date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    try {
        createMouvementCaisse($db, currentUser(), [
            'type' => $_POST['type'] ?? '',
            'montant' => $_POST['montant'] ?? 0,
            'devise' => $_POST['devise'] ?? 'CDF',
            'motif' => $_POST['motif'] ?? '',
        ]);
        $montant = (float) ($_POST['montant'] ?? 0);
        $devise = normalizeDevise($_POST['devise'] ?? 'CDF');
        flash('success', 'Mouvement enregistré : ' . formatMoney($montant, $devise));
    } catch (InvalidArgumentException $e) {
        flash('danger', $e->getMessage());
    }
    redirect('caisse.php?date=' . urlencode($date));
}

$resume = resumeCaisseJour($db, $date);
$mouvements = fetchMouvementsCaisse($db, $date);

$pageTitle = 'Entrées / Sorties caisse';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-cash-stack me-2"></i>Entrées / Sorties caisse</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= e($date) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Afficher</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body">
                <div class="text-muted small">Entrées (FC)</div>
                <div class="h5 text-success mb-0"><?= formatCDF((float) $resume['entrees_cdf']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger h-100">
            <div class="card-body">
                <div class="text-muted small">Sorties (FC)</div>
                <div class="h5 text-danger mb-0"><?= formatCDF((float) $resume['sorties_cdf']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body">
                <div class="text-muted small">Solde caisse (FC)</div>
                <div class="h5 text-primary mb-0"><?= formatCDF((float) $resume['solde_cdf']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Mouvements</div>
                <div class="h5 mb-0"><?= (int) $resume['nb_mouvements'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><strong>Nouveau mouvement</strong></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select" required>
                            <option value="entree">Entrée (+)</option>
                            <option value="sortie">Sortie (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Devise *</label>
                        <select name="devise" class="form-select">
                            <option value="CDF">Franc Congolais (FC)</option>
                            <option value="USD">Dollar ($)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant *</label>
                        <input type="number" step="0.01" min="0.01" name="montant" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif *</label>
                        <textarea name="motif" class="form-control" rows="3" required placeholder="Ex. Transport, Fournitures, Apport caisse..."></textarea>
                        <small class="text-muted">Obligatoire — le motif s'affiche dans la liste</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><strong>Mouvements du <?= formatDate($date) ?></strong></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Heure</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Motif</th>
                            <th>Vendeur</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($mouvements as $m): ?>
                    <tr>
                        <td><?= date('H:i', strtotime($m['date_mouvement'])) ?></td>
                        <td>
                            <?php if ($m['type'] === 'entree'): ?>
                            <span class="badge bg-success">Entrée</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Sortie</span>
                            <?php endif; ?>
                        </td>
                        <td><?= formatMoney($m['montant'], $m['devise']) ?></td>
                        <td><strong><?= e($m['motif']) ?></strong></td>
                        <td><?= e($m['vendeur']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($mouvements)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun mouvement ce jour.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
