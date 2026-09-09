<?php
$pageTitle = 'Gestion des paiements';
require_once __DIR__ . '/../includes/header_admin.php';

$yearId = getSelectedYearId();
$db = getDB();

$filterMois = (int) ($_GET['mois'] ?? 0);
$filterStatut = $_GET['statut'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT p.*, s.nom_complet, s.numero_dossier, c.nom AS classe_nom, c.section AS classe_section
        FROM payments p
        JOIN students s ON p.student_id = s.id
        LEFT JOIN classes c ON s.classe_id = c.id
        WHERE p.academic_year_id = ?';
$params = [$yearId];

if ($filterMois) { $sql .= ' AND p.mois = ?'; $params[] = $filterMois; }
if ($filterStatut) { $sql .= ' AND p.statut = ?'; $params[] = $filterStatut; }
if ($search) {
    $sql .= ' AND (s.nom_complet LIKE ? OR s.numero_dossier LIKE ? OR p.numero_recu LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

$sql .= ' ORDER BY p.created_at DESC LIMIT 200';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-coin"></i> Paiements</h2>
    <a href="<?= BASE_URL ?>/admin/student.php?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Nouveau paiement (via élève)</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control" placeholder="Rechercher..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="mois" class="form-select">
                    <option value="">Tous les mois</option>
                    <?php foreach (SCHOOL_MONTHS as $num => $info): ?>
                    <option value="<?= $num ?>" <?= $filterMois == $num ? 'selected' : '' ?>><?= e($info['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    <option value="paye" <?= $filterStatut === 'paye' ? 'selected' : '' ?>>Payé</option>
                    <option value="partiel" <?= $filterStatut === 'partiel' ? 'selected' : '' ?>>Partiel</option>
                    <option value="impaye" <?= $filterStatut === 'impaye' ? 'selected' : '' ?>>Non payé</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Reçu</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Mois</th>
                    <th>Dû</th>
                    <th>Payé</th>
                    <th>Reste</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>OK</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
                <td><code><?= e($p['numero_recu'] ?: '—') ?></code></td>
                <td><a href="<?= BASE_URL ?>/admin/student.php?id=<?= $p['student_id'] ?>"><?= e($p['nom_complet']) ?></a></td>
                <td><?= e(formatClassName($p)) ?></td>
                <td><?= e(SCHOOL_MONTHS[(int)$p['mois']]['label'] ?? $p['mois']) ?></td>
                <td><?= number_format($p['montant_du'], 0) ?>$</td>
                <td><?= number_format($p['montant_paye'], 0) ?>$</td>
                <td><?= number_format($p['reste'], 0) ?>$</td>
                <td><?= getPaymentStatusBadge($p['statut']) ?></td>
                <td><?= $p['date_paiement'] ? date('d/m/Y', strtotime($p['date_paiement'])) : '—' ?></td>
                <td><?= $p['verified_ok'] ? '✅' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Aucun paiement.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
