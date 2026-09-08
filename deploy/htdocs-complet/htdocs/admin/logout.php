<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
adminLogout();
header('Location: index.php');
exit;
