<?php

session_start();

// hapus semua data session
$_SESSION = [];

// hapus cookie session kalau ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// destroy session
session_destroy();

// redirect ke login
header('Location: login.php');
exit;
