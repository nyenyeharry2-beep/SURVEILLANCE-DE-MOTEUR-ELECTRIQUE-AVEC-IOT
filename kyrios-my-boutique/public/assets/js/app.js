// Kyrios My Boutique - Application JavaScript

async function toggleLike(postId, btn) {
    try {
        const res = await fetch('/api/like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId }),
        });
        const data = await res.json();
        btn.classList.toggle('liked', data.liked);
        btn.querySelector('.like-count').textContent = data.count;
    } catch (e) {
        console.error('Erreur like:', e);
    }
}

function toggleComments(postId) {
    const section = document.getElementById('comments-' + postId);
    if (!section) return;

    const isHidden = section.style.display === 'none';
    section.style.display = isHidden ? 'block' : 'none';

    if (isHidden) {
        loadComments(postId);
    }
}

async function loadComments(postId) {
    const list = document.getElementById('comments-list-' + postId);
    if (!list || list.dataset.loaded) return;

    try {
        const res = await fetch('/api/comments.php?post_id=' + postId);
        const comments = await res.json();
        list.innerHTML = comments.map(c =>
            `<div class="comment">
                <div class="comment-bubble">
                    <strong>${escapeHtml(c.full_name)}</strong><br>
                    ${escapeHtml(c.content)}
                </div>
            </div>`
        ).join('');
        list.dataset.loaded = '1';
    } catch (e) {
        console.error('Erreur comments:', e);
    }
}

async function addComment(e, postId) {
    e.preventDefault();
    const input = e.target.querySelector('input');
    const content = input.value.trim();
    if (!content) return;

    try {
        const res = await fetch('/api/comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, content }),
        });
        const data = await res.json();
        if (data.success) {
            const list = document.getElementById('comments-list-' + postId);
            list.innerHTML += `<div class="comment">
                <div class="comment-bubble">
                    <strong>${escapeHtml(data.comment.full_name)}</strong><br>
                    ${escapeHtml(data.comment.content)}
                </div>
            </div>`;
            input.value = '';
        }
    } catch (e) {
        console.error('Erreur comment:', e);
    }
}

function sharePost(postId) {
    const url = window.location.origin + '/index.php#post-' + postId;
    if (navigator.share) {
        navigator.share({ title: 'Kyrios My Boutique', url });
    } else {
        navigator.clipboard.writeText(url);
        alert('Lien copié dans le presse-papier !');
    }
}

async function sendMessage(e, convId) {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    if (!content) return;

    try {
        const res = await fetch('/api/message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: convId, content }),
        });
        const data = await res.json();
        if (data.success) {
            const chat = document.getElementById('chatMessages');
            chat.innerHTML += `<div class="message-bubble sent">
                ${escapeHtml(content)}
                <div class="message-time">À l'instant</div>
            </div>`;
            input.value = '';
            chat.scrollTop = chat.scrollHeight;
        }
    } catch (e) {
        console.error('Erreur message:', e);
    }
}

async function orderProduct(productId, title) {
    const address = prompt('Adresse de livraison (optionnel) :');
    if (address === null) return;

    try {
        const res = await fetch('/api/order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, address }),
        });
        const data = await res.json();
        if (data.success) {
            alert('Commande #' + data.order_id + ' pour "' + title + '" passée avec succès !');
        } else {
            alert(data.error || 'Erreur lors de la commande');
        }
    } catch (e) {
        alert('Erreur réseau');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Post form AJAX
document.getElementById('postForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        const res = await fetch('/api/post.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    } catch (e) {
        form.submit();
    }
});

// User search in messages
let searchTimeout;
document.getElementById('userSearch')?.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const q = e.target.value.trim();
    const results = document.getElementById('searchResults');

    if (q.length < 2) {
        results.innerHTML = '';
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const res = await fetch('/api/search.php?q=' + encodeURIComponent(q));
            const users = await res.json();
            results.innerHTML = users.map(u =>
                `<a href="/messages.php?user=${u.id}" style="display:flex;align-items:center;gap:8px;padding:8px;border-radius:8px;text-decoration:none;color:inherit;">
                    <span>${escapeHtml(u.full_name)}</span>
                    <small style="color:var(--text-muted)">${u.role}</small>
                </a>`
            ).join('');
        } catch (e) {
            console.error('Search error:', e);
        }
    }, 300);
});

// Global search
document.getElementById('globalSearch')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        const q = e.target.value.trim();
        if (q) {
            window.location.href = '/marketplace.php?search=' + encodeURIComponent(q);
        }
    }
});

// Auto-scroll chat
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Poll new messages every 5s
if (typeof currentConvId !== 'undefined' && currentConvId) {
    setInterval(async () => {
        try {
            const res = await fetch('/api/messages.php?conv=' + currentConvId);
            const data = await res.json();
            if (data.messages) {
                const chat = document.getElementById('chatMessages');
                const currentCount = chat.querySelectorAll('.message-bubble').length;
                if (data.messages.length > currentCount) {
                    location.reload();
                }
            }
        } catch (e) { /* silent */ }
    }, 5000);
}
