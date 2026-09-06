<?php

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $configPath = __DIR__ . '/../config/config.php';
        if (!file_exists($configPath)) {
            dbFailConfigMissing();
        }

        require_once $configPath;

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            dbFail('Erreur de connexion à la base de données.');
        }

        static $schemaReady = false;
        if (!$schemaReady) {
            $bootstrap = __DIR__ . '/schema_bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
                ensureAllSchemas($pdo);
            } else {
                $schemaFile = __DIR__ . '/medicaments_unites.php';
                if (file_exists($schemaFile)) {
                    require_once $schemaFile;
                    ensureMedicamentUnitesSchema($pdo);
                }
            }
            $schemaReady = true;
        }
    }

    return $pdo;
}

function dbFailConfigMissing(): void
{
    $message = 'Configuration manquante. Créez config/config.php via setup.php ou copiez config/config.example.php.';
    $setupUrl = 'setup.php';

    if (defined('NOUVELLE_EVE_API')) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'setup_url' => $setupUrl,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $safeSetup = htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8');
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Configuration manquante</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:520px;margin:3rem auto;padding:0 1rem;line-height:1.5}';
    echo 'a.btn{display:inline-block;margin-top:1rem;padding:.6rem 1rem;background:#0d6efd;color:#fff;text-decoration:none;border-radius:.375rem}</style>';
    echo '</head><body>';
    echo '<h1>Configuration manquante</h1>';
    echo '<p>Le fichier <code>config/config.php</code> n\'existe pas sur le serveur.</p>';
    echo '<p>Ouvrez l\'assistant de configuration pour le créer en une étape :</p>';
    echo '<p><a class="btn" href="' . $safeSetup . '">Configurer le site → setup.php</a></p>';
    echo '<p><small>Ou dans File Manager : copiez <code>config/config.example.php</code> vers <code>config/config.php</code> puis éditez vos identifiants MySQL InfinityFree.</small></p>';
    echo '</body></html>';
    exit;
}

function dbFail(string $message): void
{
    if (defined('NOUVELLE_EVE_API')) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    die(htmlspecialchars($message));
}
