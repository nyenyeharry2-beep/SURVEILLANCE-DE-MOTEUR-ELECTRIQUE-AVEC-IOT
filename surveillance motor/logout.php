<?php
require_once __DIR__ . '/common.php';
start_session();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  setcookie(session_name(), '', cookie_options(time() - 42000));
}
session_destroy();
clear_auth_cookie();
json_ok(['ok' => true]);
