<?php
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    redirect('dashboard.php');
}

redirect('login.php');
