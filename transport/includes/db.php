<?php
/**
 * Connexion MySQL - InfinityFree genies.free.je
 * Identifiants intégrés + essai automatique si config incorrecte
 */

function getDbCredentials(): array
{
    return [
        'host' => defined('DB_HOST') ? DB_HOST : 'sql205.infinityfree.com',
        'port' => defined('DB_PORT') ? (int) DB_PORT : 3306,
        'name' => defined('DB_NAME') ? DB_NAME : 'if0_42871659_genies',
        'user' => defined('DB_USER') ? DB_USER : 'if0_42871659',
        'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
    ];
}

function getDbPasswordCandidates(): array
{
    $passwords = [];
    if (defined('DB_PASS') && DB_PASS !== '') {
        $passwords[] = DB_PASS;
    }
    // Mots de passe InfinityFree (variantes de casse)
    $passwords[] = 'JojYwJiexiP1TAd';
    $passwords[] = 'JoJyWjiexiP1TAd';
    return array_values(array_unique($passwords));
}

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $cred = getDbCredentials();
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $lastError = null;
    foreach (getDbPasswordCandidates() as $pass) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cred['host'],
                $cred['port'],
                $cred['name'],
                $cred['charset']
            );
            $pdo = new PDO($dsn, $cred['user'], $pass, $options);
            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e;
        }
    }

    // Essai mysqli en secours
    if (function_exists('mysqli_connect')) {
        foreach (getDbPasswordCandidates() as $pass) {
            $mysqli = @new mysqli($cred['host'], $cred['user'], $pass, $cred['name'], $cred['port']);
            if (!$mysqli->connect_error) {
                $mysqli->set_charset($cred['charset']);
                $pdo = new PDO(
                    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cred['host'], $cred['port'], $cred['name'], $cred['charset']),
                    $cred['user'],
                    $pass,
                    $options
                );
                $mysqli->close();
                return $pdo;
            }
        }
    }

    $msg = $lastError ? $lastError->getMessage() : 'Connexion impossible';
    die('<div style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;border:2px solid #dc3545;border-radius:8px;">'
        . '<h2 style="color:#dc3545;">Erreur connexion MySQL</h2>'
        . '<p>' . htmlspecialchars($msg) . '</p>'
        . '<p><strong>Action :</strong> InfinityFree → MySQL Databases → Change Password → mettez <code>GeniesBus2026</code> → modifiez DB_PASS dans config/database.php</p>'
        . '</div>');
}
