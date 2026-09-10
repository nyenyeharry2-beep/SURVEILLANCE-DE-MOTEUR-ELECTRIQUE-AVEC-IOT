<?php
$pageTitle = 'Historique';
require_once __DIR__ . '/../includes/header_admin.php';

$db = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$total = (int) $db->query('SELECT COUNT(*) FROM activity_log')->fetchColumn();
$stmt = $db->prepare(
    'SELECT al.*, u.nom AS user_nom FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ? OFFSET ?'
);
$stmt->execute([$perPage, $offset]);
$logs = $stmt->fetchAll();
$totalPages = ceil($total / $perPage);
?>

<h2 class="mb-4"><i class="bi bi-clock-history"></i> Historique des opérations</h2>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Type</th><th>Détails</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                <td><?= e($log['user_nom'] ?? 'Système / Public') ?></td>
                <td><code><?= e($log['action']) ?></code></td>
                <td><?= e($log['entity_type'] ?? '—') ?> #<?= $log['entity_id'] ?? '' ?></td>
                <td class="small"><?= e($log['details'] ?? '') ?></td>
                <td class="small"><?= e($log['ip_address'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Aucun historique.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-content-center gap-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
