<?php
declare(strict_types=1);

function parentAsset(string $file): string
{
    return 'assets/' . $file;
}

/** Logo léger pour chargement rapide (60 Ko vs 600 Ko PNG) */
function parentLogoAsset(): string
{
    return parentAsset('logo.jpg');
}

function parentPageUrl(string $tab): string
{
    $url = 'suivi.php?tab=' . urlencode($tab);
    if (parentIsApp()) {
        $url .= '&app=1';
    }
    return $url;
}

function parentIsApp(): bool
{
    if (isset($_GET['app']) && (string) $_GET['app'] === '1') {
        return true;
    }
    return !empty($_SESSION['parent_app']);
}

function renderParentHead(string $title = 'Suivi Paiements'): void
{
    $isApp = parentIsApp();
    ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1B3A6B">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($title) ?> — Super Genies</title>
    <style>
        :root {
            --navy: #1B3A6B;
            --royal: #2B579A;
            --red: #C62828;
            --bg: #FFFFFF;
            --surface: #F5F7FA;
            --paid: #2E7D32;
            --unpaid: #D32F2F;
            --partial: #F57C00;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: #1a1a1a;
            padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px));
            min-height: 100vh;
        }
        body.app-mode {
            padding-top: env(safe-area-inset-top, 0px);
            -webkit-tap-highlight-color: transparent;
        }
        .app-bar {
            background: var(--navy);
            color: #fff;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .app-bar img {
            width: 40px; height: 40px;
            border-radius: 8px;
            object-fit: contain;
            background: #fff;
        }
        .app-bar-title { font-size: 14px; font-weight: 700; line-height: 1.2; }
        .app-bar-sub { font-size: 11px; opacity: .92; margin-top: 2px; }
        .content { padding: 16px; max-width: 720px; margin: 0 auto; }
        .welcome h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 6px;
        }
        .welcome p { color: #757575; font-size: .95rem; line-height: 1.45; margin-bottom: 16px; }
        .carousel-wrap { margin-bottom: 16px; }
        .carousel {
            position: relative;
            width: 100%;
            height: 200px;
            border-radius: 16px;
            overflow: hidden;
            background: #ddd;
        }
        .carousel-slide {
            position: absolute; inset: 0;
            opacity: 0;
            transition: opacity .5s ease;
        }
        .carousel-slide.active { opacity: 1; }
        .carousel-slide img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .carousel-caption {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: rgba(27, 58, 107, .75);
            color: #fff;
            padding: 8px 12px;
            font-weight: 600;
            font-size: .9rem;
            text-align: center;
        }
        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
        }
        .carousel-dots span {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #ccc;
            transition: all .2s;
        }
        .carousel-dots span.active {
            width: 10px; height: 10px;
            background: var(--red);
        }
        .field-outlined {
            position: relative;
            margin-bottom: 8px;
        }
        .field-outlined input {
            width: 100%;
            padding: 16px 14px 14px 44px;
            border: 2px solid var(--navy);
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
            outline: none;
        }
        .field-outlined input:focus { border-color: var(--royal); }
        .field-outlined label {
            position: absolute;
            left: 12px; top: -10px;
            background: var(--bg);
            padding: 0 6px;
            font-size: .75rem;
            font-weight: 600;
            color: var(--navy);
        }
        .field-outlined .ico-search {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            width: 20px; height: 20px;
            opacity: .55;
        }
        .btn-search {
            width: 100%;
            background: var(--red);
            color: #fff;
            border: 0;
            border-radius: 28px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn-search:active { opacity: .9; }
        .alert-err {
            background: rgba(211, 47, 47, .1);
            color: var(--unpaid);
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            font-size: .9rem;
            line-height: 1.45;
        }
        .alert-ok {
            background: rgba(46, 125, 50, .1);
            color: var(--paid);
            padding: 12px 16px;
            border-radius: 12px;
            margin-top: 16px;
        }
        .student-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        .student-card h3 { color: var(--navy); margin-bottom: 8px; }
        .fee-card {
            background: #fff;
            border-radius: 12px;
            padding: 12px 16px;
            margin-top: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: .9rem;
        }
        .fee-card .paye { color: var(--paid); font-weight: 600; }
        .fee-card .impaye { color: var(--unpaid); font-weight: 600; }
        .fee-card .partiel { color: var(--partial); font-weight: 600; }
        .fee-note { font-size: .78rem; color: var(--partial); margin-top: 4px; }
        .section-title {
            font-weight: 700;
            color: var(--navy);
            margin: 16px 0 8px;
            font-size: 1rem;
        }
        .btn-outline {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 12px;
            padding: 12px;
            border: 1px solid var(--red);
            color: var(--red);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            background: #fff;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            display: flex;
            z-index: 30;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
        .bottom-nav a {
            flex: 1;
            text-decoration: none;
            color: #1a1a1a;
            text-align: center;
            padding: 8px 4px 10px;
            font-size: 11px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .bottom-nav a .nav-icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            padding: 4px 16px;
        }
        .bottom-nav a.active {
            color: var(--navy);
            font-weight: 600;
        }
        .bottom-nav a.active .nav-icon-wrap {
            background: rgba(43, 87, 154, .15);
        }
        .nav-icon { width: 22px; height: 22px; fill: currentColor; }
        .card-page {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        .card-page h2 { color: var(--navy); margin-bottom: 10px; font-size: 1.05rem; }
        .card-page label { display: block; font-weight: 600; margin: 10px 0 4px; font-size: .9rem; }
        .card-page input, .card-page select, .card-page textarea {
            width: 100%; padding: 10px;
            border: 1px solid #ccc; border-radius: 8px;
            font-size: 1rem;
        }
        .card-page textarea { min-height: 100px; }
        .card-page ul { padding-left: 1.2rem; margin: 8px 0; }
        .card-page li { margin: 4px 0; }
        .hidden { display: none !important; }
        .hint { color: #888; font-size: .85rem; }
        .communique-wrap { margin-bottom: 16px; }
        .communique-wrap > h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 10px;
        }
        .communique-card {
            background: #fff;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            border-left: 4px solid var(--red);
        }
        .communique-card h3 {
            color: var(--navy);
            font-size: .98rem;
            margin-bottom: 4px;
        }
        .communique-date {
            color: #888;
            font-size: .78rem;
            margin-bottom: 8px;
        }
        .communique-body {
            font-size: .92rem;
            line-height: 1.45;
            color: #333;
        }
    </style>
    <?php
}

function renderParentAppBar(): void
{
    ?>
    <header class="app-bar">
        <img src="<?= htmlspecialchars(parentLogoAsset()) ?>" alt="Logo" width="40" height="40" decoding="async">
        <div>
            <div class="app-bar-title">C.S. LES SUPER GENIES</div>
            <div class="app-bar-sub">Suivi des paiements</div>
        </div>
    </header>
    <?php
}

/** @param list<array<string, mixed>> $communiques */
function renderParentCommuniques(array $communiques): void
{
    if ($communiques === []) {
        return;
    }
    ?>
    <div class="communique-wrap">
        <h2>📢 Communiqués de l'école</h2>
        <?php foreach ($communiques as $c): ?>
            <article class="communique-card">
                <h3><?= htmlspecialchars($c['titre']) ?></h3>
                <div class="communique-date">
                    <?= htmlspecialchars(date('d/m/Y', strtotime($c['created_at']))) ?>
                </div>
                <div class="communique-body"><?= nl2br(htmlspecialchars($c['contenu'])) ?></div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function renderParentCarousel(): void
{
    $slides = [
        ['img' => 'carousel_felicitations.jpg', 'title' => 'Félicitations à nos finalistes'],
        ['img' => 'carousel_petrochimie.jpg', 'title' => 'Pétrochimie — Inscription'],
        ['img' => 'carousel_inscriptions.jpg', 'title' => 'Inscriptions 2026-2027'],
    ];
    ?>
    <div class="carousel-wrap">
        <div class="carousel" id="carousel">
            <?php foreach ($slides as $i => $s): ?>
                <div class="carousel-slide<?= $i === 0 ? ' active' : '' ?>">
                    <img src="<?= htmlspecialchars(parentAsset($s['img'])) ?>" alt="" loading="lazy" decoding="async">
                    <div class="carousel-caption"><?= htmlspecialchars($s['title']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="carousel-dots" id="carousel-dots">
            <?php foreach ($slides as $i => $_): ?>
                <span class="<?= $i === 0 ? 'active' : '' ?>"></span>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    (function(){
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dots span');
        if (!slides.length) return;
        let i = 0;
        setInterval(function(){
            slides[i].classList.remove('active');
            dots[i].classList.remove('active');
            i = (i + 1) % slides.length;
            slides[i].classList.add('active');
            dots[i].classList.add('active');
        }, 4500);
    })();
    </script>
    <?php
}

function renderParentBottomNav(string $activeTab): void
{
    $tabs = [
        'accueil' => ['label' => 'Accueil', 'icon' => 'search'],
        'messagerie' => ['label' => 'Messagerie', 'icon' => 'email'],
        'inscriptions' => ['label' => 'Inscriptions', 'icon' => 'info'],
        'trousseau' => ['label' => 'Trousseau', 'icon' => 'bag'],
    ];
    $icons = [
        'search' => '<path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>',
        'email' => '<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>',
        'info' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>',
        'bag' => '<path d="M18 6h-2c0-2.21-1.79-4-4-4S8 3.79 8 6H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-6-2c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm6 16H6V8h2v2c0 .55.45 1 1 1s1-.45 1-1V8h4v2c0 .55.45 1 1 1s1-.45 1-1V8h2v12z"/>',
    ];
    ?>
    <nav class="bottom-nav" id="bottom-nav">
        <?php foreach ($tabs as $key => $t): ?>
            <a href="#" data-tab="<?= htmlspecialchars($key) ?>" class="nav-tab <?= $activeTab === $key ? 'active' : '' ?>">
                <span class="nav-icon-wrap">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$t['icon']] ?></svg>
                </span>
                <?= htmlspecialchars($t['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <script>
    (function(){
        var panels = document.querySelectorAll('.tab-panel');
        var links = document.querySelectorAll('#bottom-nav a[data-tab]');
        function showTab(name) {
            panels.forEach(function(p){ p.classList.add('hidden'); });
            var panel = document.getElementById('panel-' + name);
            if (panel) panel.classList.remove('hidden');
            links.forEach(function(a){
                a.classList.toggle('active', a.getAttribute('data-tab') === name);
            });
            window.scrollTo(0, 0);
        }
        links.forEach(function(a){
            a.addEventListener('click', function(e){
                e.preventDefault();
                showTab(a.getAttribute('data-tab'));
            });
        });
        document.querySelectorAll('a[data-goto-tab]').forEach(function(a){
            a.addEventListener('click', function(e){
                e.preventDefault();
                showTab(a.getAttribute('data-goto-tab'));
            });
        });
    })();
    </script>
    <?php
}

function renderParentCommuniqueNotifier(array $initialCommuniques = []): void
{
    $apiUrl = 'api/communiques.php';
    $initialJson = json_encode(array_map(static function (array $c): array {
        return [
            'id' => (int) $c['id'],
            'titre' => $c['titre'],
            'contenu' => $c['contenu'],
        ];
    }, $initialCommuniques), JSON_UNESCAPED_UNICODE);
    ?>
    <div id="sg-communique-banner" class="sg-banner" aria-live="polite" hidden>
        <div class="sg-banner-inner">
            <div class="sg-banner-handle" aria-hidden="true"></div>
            <div class="sg-banner-row">
                <div class="sg-banner-icon" aria-hidden="true">📢</div>
                <div class="sg-banner-text">
                    <strong id="sg-banner-title"></strong>
                    <p id="sg-banner-body"></p>
                    <span class="sg-banner-hint">Balayez vers le haut pour fermer</span>
                </div>
            </div>
        </div>
    </div>
    <style>
        .sg-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: calc(8px + env(safe-area-inset-top, 0px)) 12px 0;
            pointer-events: none;
            transform: translateY(-120%);
            transition: transform .35s cubic-bezier(.4,0,.2,1);
        }
        .sg-banner.sg-visible {
            transform: translateY(0);
            pointer-events: auto;
        }
        .sg-banner.sg-dragging { transition: none; }
        .sg-banner-inner {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(0,0,0,.22);
            border-left: 4px solid var(--red, #C62828);
            overflow: hidden;
            touch-action: none;
        }
        .sg-banner-handle {
            width: 36px; height: 4px;
            background: #ccc;
            border-radius: 2px;
            margin: 8px auto 4px;
        }
        .sg-banner-row {
            display: flex;
            gap: 12px;
            padding: 4px 14px 14px;
            align-items: flex-start;
        }
        .sg-banner-icon { font-size: 1.5rem; line-height: 1; }
        .sg-banner-text { flex: 1; min-width: 0; }
        .sg-banner-text strong {
            display: block;
            color: var(--navy, #1B3A6B);
            font-size: .95rem;
            margin-bottom: 4px;
        }
        .sg-banner-text p {
            margin: 0;
            font-size: .85rem;
            color: #444;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .sg-banner-hint {
            display: block;
            font-size: .72rem;
            color: #999;
            margin-top: 6px;
        }
    </style>
    <script>
    (function(){
        const API = <?= json_encode($apiUrl, JSON_UNESCAPED_SLASHES) ?>;
        const INITIAL = <?= $initialJson ?: '[]' ?>;
        const DISMISSED_KEY = 'sg_communiques_dismissed';
        const NOTIFIED_KEY = 'sg_communiques_notified';
        const banner = document.getElementById('sg-communique-banner');
        const titleEl = document.getElementById('sg-banner-title');
        const bodyEl = document.getElementById('sg-banner-body');
        if (!banner || !titleEl || !bodyEl) return;

        let current = null;
        let touchStartY = 0, touchStartX = 0, touchDeltaY = 0, touchDeltaX = 0;
        let lastList = [];

        function readJson(key) {
            try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch(e) { return []; }
        }
        function writeJson(key, arr) {
            localStorage.setItem(key, JSON.stringify(arr));
        }
        function isDismissed(id) { return readJson(DISMISSED_KEY).includes(String(id)); }
        function dismiss(id) {
            const d = readJson(DISMISSED_KEY);
            const s = String(id);
            if (!d.includes(s)) { d.push(s); writeJson(DISMISSED_KEY, d); }
        }
        function markNotified(id) {
            const n = readJson(NOTIFIED_KEY);
            const s = String(id);
            if (!n.includes(s)) { n.push(s); writeJson(NOTIFIED_KEY, n); }
        }
        function wasNotified(id) { return readJson(NOTIFIED_KEY).includes(String(id)); }

        function notifyNative(c) {
            if (window.SuperGeniesAndroid && typeof SuperGeniesAndroid.showCommuniqueNotification === 'function') {
                try {
                    SuperGeniesAndroid.showCommuniqueNotification(String(c.id), c.titre, c.contenu);
                } catch (e) {}
            }
        }

        function hideBanner() {
            banner.classList.remove('sg-visible', 'sg-dragging');
            banner.style.transform = '';
            banner.style.opacity = '';
            current = null;
        }

        function showBanner(c) {
            current = c;
            titleEl.textContent = c.titre;
            bodyEl.textContent = c.contenu;
            banner.hidden = false;
            requestAnimationFrame(function(){ banner.classList.add('sg-visible'); });
        }

        function pickNext(list) {
            return list
                .filter(function(c){ return !isDismissed(c.id); })
                .sort(function(a, b){ return b.id - a.id; })[0] || null;
        }

        function processList(list) {
            lastList = list;
            list.filter(function(c){ return !isDismissed(c.id); }).forEach(function(c) {
                if (!wasNotified(c.id)) {
                    markNotified(c.id);
                    notifyNative(c);
                }
            });
            const next = pickNext(list);
            if (!next) {
                hideBanner();
                banner.hidden = true;
                return;
            }
            if (!current || isDismissed(current.id) || String(current.id) !== String(next.id)) {
                if (!banner.classList.contains('sg-visible')) {
                    showBanner(next);
                } else if (current && String(current.id) !== String(next.id)) {
                    showBanner(next);
                }
            }
        }

        async function poll() {
            if (document.hidden) return;
            try {
                const res = await fetch(API + '?t=' + Date.now(), { cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                if (data.success && Array.isArray(data.communiques)) {
                    processList(data.communiques);
                }
            } catch (e) {}
        }

        function dismissCurrent() {
            if (!current) return;
            dismiss(current.id);
            banner.classList.remove('sg-visible');
            banner.style.transform = 'translateY(-120%)';
            setTimeout(function(){
                hideBanner();
                const next = pickNext(lastList);
                if (next) {
                    showBanner(next);
                } else {
                    banner.hidden = true;
                }
            }, 280);
        }

        banner.addEventListener('touchstart', function(e){
            if (!current) return;
            touchStartY = e.touches[0].clientY;
            touchStartX = e.touches[0].clientX;
            touchDeltaY = 0;
            touchDeltaX = 0;
            banner.classList.add('sg-dragging');
        }, { passive: true });

        banner.addEventListener('touchmove', function(e){
            if (!current) return;
            touchDeltaY = e.touches[0].clientY - touchStartY;
            touchDeltaX = e.touches[0].clientX - touchStartX;
            if (touchDeltaY < 0) {
                banner.style.transform = 'translateY(' + touchDeltaY + 'px)';
            } else if (Math.abs(touchDeltaX) > 10) {
                banner.style.transform = 'translateX(' + touchDeltaX + 'px)';
                banner.style.opacity = String(Math.max(0.35, 1 - Math.abs(touchDeltaX) / 180));
            }
        }, { passive: true });

        banner.addEventListener('touchend', function(){
            if (!current) return;
            banner.classList.remove('sg-dragging');
            if (touchDeltaY < -50 || Math.abs(touchDeltaX) > 90) {
                dismissCurrent();
            } else {
                banner.style.transform = '';
                banner.style.opacity = '';
            }
        });

        if (INITIAL.length) {
            processList(INITIAL);
        } else {
            poll();
        }
        setInterval(poll, 120000);
        document.addEventListener('visibilitychange', function(){
            if (!document.hidden) poll();
        });
    })();
    </script>
    <?php
}
