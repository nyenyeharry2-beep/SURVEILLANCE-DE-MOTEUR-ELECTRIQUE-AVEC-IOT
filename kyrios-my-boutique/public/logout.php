<?php
require_once __DIR__ . '/bootstrap.php';
$auth->logout();
header('Location: /login.php');
exit;
