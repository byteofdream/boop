<?php
require_once __DIR__ . '/functions.php';

function register_user($username, $password) {
    $username = trim($username);
    if (strlen($username) < 2 || strlen($username) > 30) return __('username_length');
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) return __('username_chars');
    if (strlen($password) < 4) return __('password_length');

    if (get_user($username)) return __('username_taken');

    create_user($username, password_hash($password, PASSWORD_DEFAULT));
    $_SESSION['username'] = $username;
    return null;
}

function login_user($username, $password) {
    $user = get_user($username);
    if ($user && password_verify($password, $user['password_hash'])) {
        if (is_banned($username)) {
            return __('banned');
        }
        $_SESSION['username'] = $username;
        return null;
    }
    return __('invalid_credentials');
}

function logout_user() {
    session_destroy();
    unset($_SESSION['username']);
}
