<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
$messaging = new Kyrios\Messaging($db);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare(
        'UPDATE users SET full_name = ?, phone = ?, bio = ?, shop_name = ?, shop_description = ? WHERE id = ?'
    );
    $stmt->execute([
        trim($_POST['full_name'] ?? ''),
        trim($_POST['phone'] ?? ''),
        trim($_POST['bio'] ?? ''),
        $user['role'] === 'vendeur' ? trim($_POST['shop_name'] ?? '') : null,
        $user['role'] === 'vendeur' ? trim($_POST['shop_description'] ?? '') : null,
        $user['id'],
    ]);
    $success = 'Profil mis à jour avec succès.';
    $user = $auth->user();
}

$pageTitle = 'Mon profil';
$currentPage = 'profile';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout" style="grid-template-columns:1fr;max-width:700px;">
    <main>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="card" style="padding:24px;">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
                <img src="<?= avatarUrl($user['avatar_url'], $user['full_name']) ?>" alt="" style="width:80px;height:80px;border-radius:50%;">
                <div>
                    <h2><?= e($user['full_name']) ?></h2>
                    <?= roleBadge($user['role']) ?>
                    <?php if ($user['is_verified']): ?><span class="verified"> Compte vérifié</span><?php endif; ?>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin-top:4px;"><?= e($user['email']) ?></p>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" class="form-control" rows="3"><?= e($user['bio'] ?? '') ?></textarea>
                </div>

                <?php if ($user['role'] === 'vendeur'): ?>
                <div class="form-group">
                    <label>Nom de la boutique</label>
                    <input type="text" name="shop_name" class="form-control" value="<?= e($user['shop_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Description boutique</label>
                    <textarea name="shop_description" class="form-control" rows="2"><?= e($user['shop_description'] ?? '') ?></textarea>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>

            <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);">
                <a href="/logout.php" class="btn btn-secondary" style="color:var(--danger);">Se déconnecter</a>
            </div>
        </div>
    </main>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
