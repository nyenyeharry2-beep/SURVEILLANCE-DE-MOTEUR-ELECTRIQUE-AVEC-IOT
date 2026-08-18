<?php
require_once __DIR__ . '/common.php';
$user = current_user();
if (!$user) json_error('Non connecté.', 401);
json_ok(['ok' => true, 'user' => $user]);
