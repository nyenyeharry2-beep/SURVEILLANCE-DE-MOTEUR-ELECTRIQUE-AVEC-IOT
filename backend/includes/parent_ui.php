<?php
declare(strict_types=1);

function parentAsset(string $file): string
{
    return 'assets/' . $file;
}

function parentPageUrl(string $tab): string
{
    $url = 'suivi.php?tab=' . urlencode($tab);
    if (isset($_GET['app']) && (string) $_GET['app'] === '1') {
        $url .= '&app=1';
    }
    return $url;
}

function renderParentHead(string $title = 'Suivi Paiements'): void
{
    ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1B3A6B">
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
            padding-bottom: 72px;
            min-height: 100vh;
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
    </style>
    <?php
}

function renderParentAppBar(): void
{
    ?>
    <header class="app-bar">
        <img src="<?= htmlspecialchars(parentAsset('logo_spag.png')) ?>" alt="Logo">
        <div>
            <div class="app-bar-title">C.S. LES SUPER GENIES</div>
            <div class="app-bar-sub">Suivi des paiements</div>
        </div>
    </header>
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
                    <img src="<?= htmlspecialchars(parentAsset($s['img'])) ?>" alt="">
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
    <nav class="bottom-nav">
        <?php foreach ($tabs as $key => $t): ?>
            <a href="<?= htmlspecialchars(parentPageUrl($key)) ?>" class="<?= $activeTab === $key ? 'active' : '' ?>">
                <span class="nav-icon-wrap">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$t['icon']] ?></svg>
                </span>
                <?= htmlspecialchars($t['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}
