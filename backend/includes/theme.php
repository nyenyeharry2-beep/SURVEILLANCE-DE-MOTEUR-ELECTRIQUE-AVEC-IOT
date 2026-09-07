<?php
declare(strict_types=1);

function themeAssetUrl(string $file): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    if (str_contains($base, '/admin')) {
        return '../assets/' . $file;
    }
    return 'assets/' . $file;
}

function renderThemeHead(string $title, string $variant = 'app'): void
{
    $config = getAppConfig();
    $logo = themeAssetUrl('logo_spag.png');
    ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0; padding-bottom: 72px;
            background: #f5f7fa; color: #1a1a1a;
        }
        body.auth-page {
            min-height: 100vh; padding-bottom: 0;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #1B3A6B 0%, #0d2137 100%);
        }
        .auth-box {
            background: #fff; border-radius: 16px; padding: 1.75rem;
            width: 100%; max-width: 420px; margin: 1rem;
            box-shadow: 0 8px 32px rgba(0,0,0,.25);
        }
        .brand { text-align: center; margin-bottom: 1rem; }
        .brand img {
            width: 72px; height: 72px; object-fit: contain;
            border-radius: 12px; border: 2px solid #eef2f8;
        }
        .brand h1 { margin: .5rem 0 0; color: #1B3A6B; font-size: 1.05rem; }
        .brand .motto { color: #C62828; font-size: .75rem; font-weight: 700; letter-spacing: .05em; }
        .brand .sub { color: #666; font-size: .8rem; margin-top: .25rem; }
        .top {
            background: linear-gradient(135deg, #1B3A6B 0%, #0d2137 100%);
            color: #fff; padding: 12px 16px;
            display: flex; align-items: center; gap: 12px;
        }
        .top img { width: 48px; height: 48px; border-radius: 8px; object-fit: contain; background: #fff; }
        .top-text h1 { margin: 0; font-size: .95rem; }
        .top-text p { margin: 2px 0 0; font-size: .75rem; opacity: .9; }
        .top-actions { margin-left: auto; }
        .top-actions a { color: #fff; text-decoration: none; font-size: .85rem; }
        .wrap { padding: 12px; max-width: 720px; margin: 0 auto; }
        .card {
            background: #fff; border-radius: 12px; padding: 14px;
            margin-bottom: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.08);
        }
        h2 { margin: 0 0 10px; color: #1B3A6B; font-size: 1.05rem; }
        label { display: block; font-weight: 600; margin: 8px 0 4px; font-size: .9rem; }
        input, select, textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 8px; font-size: 1rem;
        }
        textarea { min-height: 100px; resize: vertical; }
        .btn {
            background: #C62828; color: #fff; border: 0; border-radius: 8px;
            padding: 12px; font-weight: 700; width: 100%; font-size: 1rem; margin-top: 10px; cursor: pointer;
        }
        .btn-blue { background: #1B3A6B; }
        .btn-sm { width: auto; padding: 8px 12px; font-size: .85rem; }
        .btn-green { background: #2e7d32; }
        .btn-outline { background: #fff; color: #1B3A6B; border: 1px solid #1B3A6B; }
        .ok { background: #e8f5e9; color: #1b5e20; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .err { background: #ffebee; color: #b71c1c; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .warn { background: #fff8e1; color: #e65100; padding: 10px; border-radius: 8px; margin-bottom: 10px; font-size: .9rem; }
        .student { background: #eef2f8; border-radius: 8px; padding: 12px; margin-top: 10px; }
        .fee { display: flex; justify-content: space-between; gap: 8px; padding: 8px 0; border-bottom: 1px solid #eee; font-size: .9rem; }
        .fee-note { font-size: .78rem; color: #e65100; margin-top: 2px; }
        .paye { color: #2e7d32; font-weight: 600; }
        .impaye { color: #c62828; font-weight: 600; }
        .partiel { color: #f57c00; font-weight: 600; }
        .tabs { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #ddd; display: flex; z-index: 10; }
        .tabs a { flex: 1; text-align: center; padding: 10px 4px; text-decoration: none; color: #666; font-size: 11px; }
        .tabs a.active { color: #1B3A6B; font-weight: 700; background: #eef2f8; }
        .hidden { display: none; }
        .hint { font-size: .85rem; color: #666; }
        .stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }
        .stat { background: #eef2f8; border-radius: 8px; padding: 10px; text-align: center; }
        .stat b { display: block; font-size: 1.3rem; color: #1B3A6B; }
        .badge { display: inline-block; background: #1B3A6B; color: #fff; font-size: .7rem; padding: 2px 8px; border-radius: 10px; }
        ul { padding-left: 1.2rem; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .filters { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .filters a { padding: 6px 10px; border-radius: 20px; background: #eee; text-decoration: none; color: #333; font-size: 12px; }
        .filters a.active { background: #1B3A6B; color: #fff; }
        .msg { border-left: 4px solid #C62828; padding: 10px; margin-bottom: 10px; background: #fafafa; border-radius: 0 8px 8px 0; }
        .msg.en_cours { border-color: #F57C00; }
        .msg.traite { border-color: #2E7D32; }
        .msg-actions { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
        @media (max-width: 480px) { .grid2 { grid-template-columns: 1fr; } }
    </style>
    <?php
}

function renderBrandHeader(string $subtitle = '', ?string $logoutHref = null): void
{
    $config = getAppConfig();
    $logo = themeAssetUrl('logo_spag.png');
    ?>
    <div class="top">
        <img src="<?= htmlspecialchars($logo) ?>" alt="Logo Super Genies">
        <div class="top-text">
            <h1><?= htmlspecialchars($config['school_name']) ?></h1>
            <p><?= htmlspecialchars($subtitle ?: $config['school_motto']) ?></p>
        </div>
        <?php if ($logoutHref): ?>
            <div class="top-actions"><a href="<?= htmlspecialchars($logoutHref) ?>">Déconnexion</a></div>
        <?php endif; ?>
    </div>
    <?php
}

function renderBrandBlock(): void
{
    $config = getAppConfig();
    $logo = themeAssetUrl('logo_spag.png');
    ?>
    <div class="brand">
        <img src="<?= htmlspecialchars($logo) ?>" alt="C.S. Les Super Genies">
        <h1><?= htmlspecialchars($config['school_name']) ?></h1>
        <div class="motto"><?= htmlspecialchars($config['school_motto']) ?></div>
        <div class="sub"><?= htmlspecialchars($config['school_address']) ?></div>
    </div>
    <?php
}
