<?php

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $configPath = __DIR__ . '/../config/config.php';
        if (!file_exists($configPath)) {
            die('Configuration manquante. Copiez config/config.example.php vers config/config.php');
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
            die('Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
