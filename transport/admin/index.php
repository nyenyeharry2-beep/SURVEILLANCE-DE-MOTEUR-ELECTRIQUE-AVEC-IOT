<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

redirect(BASE_URL . '/admin/' . (isLoggedIn() ? 'dashboard.php' : 'login.php'));
