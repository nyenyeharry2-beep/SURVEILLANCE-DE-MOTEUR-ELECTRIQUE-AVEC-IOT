<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$feed = new Kyrios\Feed($db);
$messaging = new Kyrios\Messaging($db);

$posts = $feed->getPosts();
$trending = $feed->getTrendingProducts();
$suggested = $feed->getSuggestedSellers();
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

$pageTitle = 'Accueil';
$currentPage = 'home';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout">
    <?php require __DIR__ . '/includes/sidebar-left.php'; ?>

    <main class="feed">
        <!-- Composer -->
        <div class="card composer">
            <form action="/api/post.php" method="POST" id="postForm">
                <div class="composer-top">
                    <img src="<?= avatarUrl($user['avatar_url'], $user['full_name']) ?>" alt="">
                    <textarea name="content" placeholder="Quoi de neuf, <?= e(explode(' ', $user['full_name'])[0]) ?> ? Partagez un produit ou une actualité..." required></textarea>
                </div>
                <div class="composer-actions">
                    <div>
                        <?php if ($user['role'] === 'vendeur'): ?>
                        <select name="product_id" class="form-control" style="width:auto;display:inline-block;padding:6px 12px;font-size:0.85rem;">
                            <option value="">Lier un produit (optionnel)</option>
                            <?php
                            $productModel = new Kyrios\Product($db);
                            foreach ($productModel->getBySeller((int) $user['id']) as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Publier</button>
                </div>
            </form>
        </div>

        <!-- Feed posts -->
        <?php foreach ($posts as $post):
            $liked = $feed->userLiked((int) $post['id'], (int) $user['id']);
        ?>
        <article class="card" data-post-id="<?= $post['id'] ?>">
            <div class="card-header">
                <img src="<?= avatarUrl($post['avatar_url'], $post['full_name']) ?>" alt="">
                <div class="card-header-info">
                    <h4>
                        <?= e($post['shop_name'] ?: $post['full_name']) ?>
                        <?= roleBadge($post['role']) ?>
                        <?php if ($post['is_verified']): ?><span class="verified" title="Vérifié">✓</span><?php endif; ?>
                    </h4>
                    <span><?= timeAgo($post['created_at']) ?></span>
                </div>
            </div>
            <div class="card-body">
                <p><?= nl2br(e($post['content'])) ?></p>

                <?php if ($post['product_id']): ?>
                <div class="product-embed">
                    <div class="product-embed-image">🛍️</div>
                    <div class="product-embed-body">
                        <h5><?= e($post['product_title']) ?></h5>
                        <div class="product-price"><?= number_format((float)$post['product_price'], 2, ',', ' ') ?> €</div>
                        <a href="/marketplace.php?product=<?= $post['product_id'] ?>" class="btn btn-primary btn-sm" style="margin-top:8px;">Voir le produit</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <button class="post-action <?= $liked ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>, this)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span class="like-count"><?= $post['likes_count'] ?></span> J'aime
                </button>
                <button class="post-action" onclick="toggleComments(<?= $post['id'] ?>)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span><?= $post['comments_count'] ?></span> Commenter
                </button>
                <button class="post-action" onclick="sharePost(<?= $post['id'] ?>)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    Partager
                </button>
            </div>
            <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display:none;">
                <div class="comments-list" id="comments-list-<?= $post['id'] ?>"></div>
                <form class="comment-form" onsubmit="addComment(event, <?= $post['id'] ?>)">
                    <img src="<?= avatarUrl($user['avatar_url'], $user['full_name']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;">
                    <input type="text" placeholder="Écrire un commentaire..." required>
                </form>
            </div>
        </article>
        <?php endforeach; ?>

        <?php if (empty($posts)): ?>
        <div class="card" style="padding:40px;text-align:center;color:var(--text-muted);">
            <p style="font-size:3rem;margin-bottom:12px;">🛍️</p>
            <h3>Bienvenue sur Kyrios My Boutique !</h3>
            <p>Soyez le premier à publier une actualité ou découvrez la marketplace.</p>
            <a href="/marketplace.php" class="btn btn-primary" style="margin-top:16px;">Explorer la boutique</a>
        </div>
        <?php endif; ?>
    </main>

    <aside class="sidebar-right">
        <div class="sidebar-section">
            <h3>🔥 Tendances</h3>
            <?php foreach ($trending as $product): ?>
            <div class="trend-item">
                <div style="width:48px;height:48px;border-radius:8px;background:linear-gradient(135deg,#e0e7ff,#ede9fe);display:flex;align-items:center;justify-content:center;">🛍️</div>
                <div class="trend-info">
                    <h5><?= e($product['title']) ?></h5>
                    <span><?= number_format((float)$product['price'], 2, ',', ' ') ?> € · <?= e($product['shop_name'] ?: $product['seller_name']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-section">
            <h3>🏪 Vendeurs suggérés</h3>
            <?php foreach ($suggested as $seller): ?>
            <div class="suggest-item">
                <img src="<?= avatarUrl($seller['avatar_url'], $seller['full_name']) ?>" alt="">
                <div class="suggest-info">
                    <h5><?= e($seller['shop_name'] ?: $seller['full_name']) ?> <?= $seller['is_verified'] ? '✓' : '' ?></h5>
                    <span><?= $seller['product_count'] ?> produit(s)</span>
                </div>
                <a href="/messages.php?user=<?= $seller['id'] ?>" class="btn btn-primary btn-sm">Contacter</a>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
