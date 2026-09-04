<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/journal.php';
require_once __DIR__ . '/includes/journee.php';
requireLogin();

$db = getDB();
ensureJourneeSchema($db);
$date = $_GET['date'] ?? getBusinessDate();
$tauxDefaut = getTauxUsdCdf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $postDate = $_POST['date'] ?? $date;

    try {
        if ($action === 'open') {
            $fondCdf = (float) ($_POST['fond_caisse_cdf'] ?? 0);
            $fondUsd = (float) ($_POST['fond_caisse_usd'] ?? 0);
            $taux = (float) ($_POST['taux_usd_cdf'] ?? $tauxDefaut);
            if ($taux <= 0) {
                flash('danger', 'Taux USD/FC obligatoire.');
            } else {
                openJourneeWithCaisse($db, $postDate, $fondCdf, $fondUsd, $taux);
                flash('success', 'Journée du ' . formatDate($postDate) . ' ouverte. Fond caisse et taux enregistrés.');
            }
        }

        if ($action === 'sync') {
            $journal = getJournal($db, $postDate);
            if (!$journal) {
                flash('danger', 'Ouvrez d\'abord la journée.');
            } elseif ($journal['cloture']) {
                flash('warning', 'Cette journée est déjà clôturée.');
            } else {
                syncJournalDay($db, (int) $journal['id'], $postDate);
                flash('success', 'Entrées et sorties actualisées depuis les achats et ventes.');
            }
        }

        if ($action === 'close') {
            $journal = getJournal($db, $postDate);
            if (!$journal) {
                flash('danger', 'Aucun journal pour cette date.');
            } elseif ($journal['cloture']) {
                flash('warning', 'Journée déjà clôturée.');
            } else {
                $caisseCdf = (float) ($_POST['caisse_cloture_cdf'] ?? 0);
                $caisseUsd = (float) ($_POST['caisse_cloture_usd'] ?? 0);
                closeJourneeWithCaisse($db, $postDate, $caisseCdf, $caisseUsd, currentUser()['id']);
                flash('success', 'Journée clôturée. Montant caisse enregistré. Ouvrez la journée suivante avec le fond de caisse.');
            }
        }

        if ($action === 'update_initial') {
            updateJournalStockInitial($db, (int) $_POST['line_id'], (int) $_POST['stock_initial']);
            flash('success', 'Stock initial matin mis à jour.');
        }

        if ($action === 'update_final') {
            updateJournalStockFinal($db, (int) $_POST['line_id'], (int) $_POST['stock_final']);
            flash('success', 'Stock final soir enregistré.');
        }
    } catch (Exception $e) {
        flash('danger', 'Erreur : ' . $e->getMessage());
    }

    redirect('journal.php?date=' . urlencode($postDate));
}

$journal = getJournal($db, $date);
$lines = $journal ? getJournalLines($db, (int) $journal['id']) : [];
$prevJournal = getPreviousClosedJournal($db, $date);
$journeeStatus = getJourneeStatus($db, $date);

$pageTitle = 'Journal quotidien';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-journal-text me-2"></i>Journal quotidien</h1>
    <form method="get" class="d-flex gap-2">
        <input type="date" name="date" class="form-control" value="<?= e($date) ?>">
        <button type="submit" class="btn btn-outline-primary">Voir</button>
    </form>
</div>

<?php if ($prevJournal): ?>
<div class="alert alert-light border mb-4">
    <i class="bi bi-arrow-repeat me-1"></i>
    Stock initial du matin = stock final du <strong><?= formatDate($prevJournal['date_jour']) ?></strong>
    (clôturé)
</div>
<?php endif; ?>

<?php if (!$journal): ?>
<div class="card mb-4">
    <div class="card-body text-center py-5">
        <i class="bi bi-sunrise text-warning" style="font-size:3rem;"></i>
        <h4 class="mt-3">Ouvrir la journée du <?= formatDate($date) ?></h4>
        <p class="text-muted">Indiquez le fond de caisse du matin et le taux USD/FC du jour. Les ventes ne sont possibles qu'après ouverture.</p>
        <form method="post" class="text-start mx-auto" style="max-width:420px">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="open">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <div class="mb-3">
                <label class="form-label">Fond caisse matin (FC)</label>
                <input type="number" name="fond_caisse_cdf" class="form-control" min="0" step="1" value="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Fond caisse matin ($)</label>
                <input type="number" name="fond_caisse_usd" class="form-control" min="0" step="0.01" value="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Taux du jour (1 USD = … FC)</label>
                <input type="number" name="taux_usd_cdf" class="form-control" min="1" step="1" value="<?= (int) $tauxDefaut ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-door-open me-1"></i> Ouvrir la journée
            </button>
        </form>
    </div>
</div>
<?php else: ?>

<div class="d-flex flex-wrap gap-2 mb-4">
    <?php if (!$journal['cloture']): ?>
    <form method="post" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="sync">
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <button type="submit" class="btn btn-outline-primary">
            <i class="bi bi-arrow-clockwise me-1"></i> Actualiser entrées/sorties
        </button>
    </form>
    <form method="post" class="d-inline" data-confirm="Clôturer la journée ? Indiquez le montant en caisse ce soir.">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="close">
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-0">Caisse soir (FC)</label>
                <input type="number" name="caisse_cloture_cdf" class="form-control form-control-sm" min="0" step="1" required placeholder="Montant FC">
            </div>
            <div>
                <label class="form-label small mb-0">Caisse soir ($)</label>
                <input type="number" name="caisse_cloture_usd" class="form-control form-control-sm" min="0" step="0.01" value="0">
            </div>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-moon-stars me-1"></i> Clôturer le soir
            </button>
        </div>
    </form>
    <?php else: ?>
    <span class="badge bg-success fs-6 py-2 px-3">
        <i class="bi bi-check-circle me-1"></i> Journée clôturée le <?= date('d/m/Y H:i', strtotime($journal['cloture_at'])) ?>
    </span>
    <?php endif; ?>
</div>

<h5 class="mb-3"><i class="bi bi-cash-stack me-1"></i> Caisse du jour</h5>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card h-100 border-info">
            <div class="card-body">
                <div class="text-muted small">Fond caisse matin</div>
                <div class="fw-bold"><?= formatCDF((float) ($journal['fond_caisse_cdf'] ?? 0)) ?></div>
                <div class="text-primary"><?= formatUSD((float) ($journal['fond_caisse_usd'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-secondary">
            <div class="card-body">
                <div class="text-muted small">Taux du jour</div>
                <div class="fw-bold">1 USD = <?= number_format((float) ($journal['taux_usd_cdf'] ?? $tauxDefaut), 0, ',', ' ') ?> FC</div>
            </div>
        </div>
    </div>
    <?php if ($journal['cloture']): ?>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-success">
            <div class="card-body">
                <div class="text-muted small">Caisse clôture soir</div>
                <div class="fw-bold"><?= formatCDF((float) ($journal['caisse_cloture_cdf'] ?? 0)) ?></div>
                <div class="text-primary"><?= formatUSD((float) ($journal['caisse_cloture_usd'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<h5 class="mb-3"><i class="bi bi-cash-stack me-1"></i> Récapitulatif stock (FC / $)</h5>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card h-100 border-secondary">
            <div class="card-body">
                <div class="text-muted small">Stock initial matin</div>
                <div class="fw-bold"><?= formatCDF((float) $journal['stock_initial_cdf']) ?></div>
                <div class="text-primary"><?= formatUSD((float) $journal['stock_initial_usd']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-success">
            <div class="card-body">
                <div class="text-muted small">Entrées du jour</div>
                <div class="fw-bold text-success"><?= formatCDF((float) $journal['entrees_cdf']) ?></div>
                <div class="text-primary"><?= formatUSD((float) $journal['entrees_usd']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-danger">
            <div class="card-body">
                <div class="text-muted small">Sorties du jour</div>
                <div class="fw-bold text-danger"><?= formatCDF((float) $journal['sorties_cdf']) ?></div>
                <div class="text-primary"><?= formatUSD((float) $journal['sorties_usd']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-primary">
            <div class="card-body">
                <div class="text-muted small">Stock final soir → demain</div>
                <div class="fw-bold"><?= formatCDF((float) $journal['stock_final_cdf']) ?></div>
                <div class="text-primary"><?= formatUSD((float) $journal['stock_final_usd']) ?></div>
            </div>
        </div>
    </div>
</div>

<h5 class="mb-3"><i class="bi bi-box-seam me-1"></i> Détail par produit (en nature)</h5>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produit</th>
                    <th class="text-center">Stock initial<br><small class="text-muted">matin</small></th>
                    <th class="text-center">Entrées</th>
                    <th class="text-center">Sorties</th>
                    <th class="text-center">Stock final<br><small class="text-muted">soir</small></th>
                    <th>Valeur init. FC</th>
                    <th>Valeur final FC</th>
                    <th>Valeur final $</th>
                    <?php if (!$journal['cloture']): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $totInit = $totEnt = $totSort = $totFinal = 0;
            foreach ($lines as $l):
                $totInit += (int) $l['stock_initial'];
                $totEnt += (int) $l['entrees'];
                $totSort += (int) $l['sorties'];
                $totFinal += (int) $l['stock_final'];
            ?>
            <tr>
                <td><code><?= e($l['code']) ?></code> <?= e($l['nom']) ?></td>
                <td class="text-center fw-bold"><?= $l['stock_initial'] ?></td>
                <td class="text-center text-success">+<?= $l['entrees'] ?></td>
                <td class="text-center text-danger">-<?= $l['sorties'] ?></td>
                <td class="text-center fw-bold text-primary"><?= $l['stock_final'] ?></td>
                <td><?= formatCDF((float) $l['valeur_initial_cdf']) ?></td>
                <td><?= formatCDF((float) $l['valeur_final_cdf']) ?></td>
                <td><?= formatUSD(convertirDevise((float) $l['valeur_final_cdf'], 'CDF', 'USD')) ?></td>
                <?php if (!$journal['cloture']): ?>
                <td class="table-actions">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#initModal<?= $l['id'] ?>" title="Stock matin">
                        <i class="bi bi-sunrise"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#finalModal<?= $l['id'] ?>" title="Stock soir">
                        <i class="bi bi-moon"></i>
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td>TOTAL (quantités)</td>
                    <td class="text-center"><?= $totInit ?></td>
                    <td class="text-center text-success">+<?= $totEnt ?></td>
                    <td class="text-center text-danger">-<?= $totSort ?></td>
                    <td class="text-center text-primary"><?= $totFinal ?></td>
                    <td><?= formatCDF((float) $journal['stock_initial_cdf']) ?></td>
                    <td><?= formatCDF((float) $journal['stock_final_cdf']) ?></td>
                    <td><?= formatUSD((float) $journal['stock_final_usd']) ?></td>
                    <?php if (!$journal['cloture']): ?><td></td><?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if (!$journal['cloture']): ?>
<?php foreach ($lines as $l): ?>
<div class="modal fade" id="initModal<?= $l['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="update_initial">
                <input type="hidden" name="date" value="<?= e($date) ?>">
                <input type="hidden" name="line_id" value="<?= $l['id'] ?>">
                <div class="modal-header"><h6 class="modal-title">Stock initial matin</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted"><?= e($l['nom']) ?></p>
                    <input type="number" name="stock_initial" class="form-control" min="0" required value="<?= $l['stock_initial'] ?>">
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary btn-sm">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="finalModal<?= $l['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="update_final">
                <input type="hidden" name="date" value="<?= e($date) ?>">
                <input type="hidden" name="line_id" value="<?= $l['id'] ?>">
                <div class="modal-header"><h6 class="modal-title">Stock final soir</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted"><?= e($l['nom']) ?></p>
                    <p class="small">Calculé : <?= (int)$l['stock_initial'] ?> + <?= (int)$l['entrees'] ?> - <?= (int)$l['sorties'] ?> = <strong><?= (int)$l['stock_initial'] + (int)$l['entrees'] - (int)$l['sorties'] ?></strong></p>
                    <input type="number" name="stock_final" class="form-control" min="0" required value="<?= $l['stock_final'] ?>">
                    <small class="text-muted">Ajustez si inventaire physique différent</small>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary btn-sm">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>

<div class="card mt-4">
    <div class="card-body small text-muted">
        <strong>Mode d'emploi :</strong>
        <ol class="mb-0">
            <li><strong>Matin</strong> — Ouvrir la journée (stock initial = stock final de la veille)</li>
            <li><strong>Journée</strong> — Les achats et ventes alimentent automatiquement entrées/sorties (bouton Actualiser)</li>
            <li><strong>Soir</strong> — Vérifier le stock final, ajuster si besoin, puis Clôturer</li>
            <li><strong>Lendemain</strong> — Le stock final du soir devient le stock initial du matin</li>
        </ol>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
