<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
$messaging = new Kyrios\Messaging($db);

$conversations = $messaging->getConversations((int) $user['id']);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

$activeConvId = isset($_GET['conv']) ? (int) $_GET['conv'] : null;
$messages = [];
$activeUser = null;

if (isset($_GET['user'])) {
    $targetUserId = (int) $_GET['user'];
    $activeConvId = $messaging->getOrCreateConversation((int) $user['id'], $targetUserId);
}

if ($activeConvId) {
    $messages = $messaging->getMessages($activeConvId, (int) $user['id']);
    foreach ($conversations as $conv) {
        if ((int) $conv['id'] === $activeConvId) {
            $activeUser = $conv;
            break;
        }
    }
}

$pageTitle = 'Messages';
$currentPage = 'messages';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="messages-layout">
    <div class="conversations-list">
        <div class="conversations-header">💬 Messagerie</div>

        <div style="padding:12px;">
            <input type="search" id="userSearch" class="form-control" placeholder="Rechercher un utilisateur..." style="font-size:0.85rem;">
            <div id="searchResults" style="margin-top:8px;"></div>
        </div>

        <?php foreach ($conversations as $conv): ?>
        <a href="/messages.php?conv=<?= $conv['id'] ?>" class="conversation-item <?= $activeConvId === (int)$conv['id'] ? 'active' : '' ?>" style="text-decoration:none;color:inherit;">
            <img src="<?= avatarUrl($conv['avatar_url'], $conv['full_name']) ?>" alt="">
            <div class="conversation-info">
                <h4><?= e($conv['full_name']) ?> <?= roleBadge($conv['role']) ?></h4>
                <p><?= e($conv['last_message'] ?? 'Nouvelle conversation') ?></p>
            </div>
            <?php if ($conv['unread_count'] > 0): ?>
            <span class="conversation-unread"><?= $conv['unread_count'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <?php if (empty($conversations)): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:0.9rem;">
            Aucune conversation.<br>Recherchez un utilisateur pour démarrer.
        </div>
        <?php endif; ?>
    </div>

    <div class="chat-area">
        <?php if ($activeUser): ?>
        <div class="chat-header">
            <img src="<?= avatarUrl($activeUser['avatar_url'], $activeUser['full_name']) ?>" alt="">
            <span><?= e($activeUser['full_name']) ?></span>
            <?= roleBadge($activeUser['role']) ?>
        </div>
        <div class="chat-messages" id="chatMessages">
            <?php foreach ($messages as $msg): ?>
            <div class="message-bubble <?= (int)$msg['sender_id'] === (int)$user['id'] ? 'sent' : 'received' ?>">
                <?= e($msg['content']) ?>
                <div class="message-time"><?= timeAgo($msg['created_at']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <form class="chat-input" onsubmit="sendMessage(event, <?= $activeConvId ?>)">
            <input type="text" id="messageInput" placeholder="Écrire un message..." required autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm">Envoyer</button>
        </form>
        <?php else: ?>
        <div class="chat-empty">
            <div style="text-align:center;">
                <p style="font-size:3rem;margin-bottom:12px;">💬</p>
                <h3>Vos messages</h3>
                <p style="color:var(--text-muted);">Sélectionnez une conversation ou recherchez un utilisateur</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const currentConvId = <?= $activeConvId ?: 'null' ?>;
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
