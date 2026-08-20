<?php

declare(strict_types=1);

namespace Kyrios;

final class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400, ?array $details = null): void
    {
        $payload = ['success' => false, 'error' => $message];
        if ($details !== null) {
            $payload['details'] = $details;
        }
        self::json($payload, $status);
    }

    public static function success(array $data = [], int $status = 200): void
    {
        self::json(array_merge(['success' => true], $data), $status);
    }
}
