<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('KYRIOS_DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('KYRIOS_DB_PORT') ?: 3306),
        'name' => getenv('KYRIOS_DB_NAME') ?: 'kyrios',
        'user' => getenv('KYRIOS_DB_USER') ?: 'root',
        'pass' => getenv('KYRIOS_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'KYRIOS API',
        'env' => getenv('KYRIOS_APP_ENV') ?: 'development',
        'debug' => (getenv('KYRIOS_APP_DEBUG') ?: 'true') === 'true',
        'base_url' => rtrim(getenv('KYRIOS_BASE_URL') ?: 'http://localhost/kyrios/api/public', '/'),
        'token_ttl_hours' => 720,
    ],
    'upload' => [
        'base_path' => __DIR__ . '/../storage',
        'images' => 'images',
        'videos' => 'videos',
        'audio' => 'audio',
        'max_size_mb' => 25,
    ],
];
