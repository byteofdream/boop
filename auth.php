<?php
require_once __DIR__ . '/functions.php';

function register_user($username, $password) {
    $username = trim($username);
    if (strlen($username) < 2 || strlen($username) > 30) return __('username_length');
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) return __('username_chars');
    if (strlen($password) < 4) return __('password_length');

    $users = get_users();
    foreach ($users as $u) {
        if ($u['username'] === $username) return __('username_taken');
    }

    $users[] = [
        'id' => generate_id(),
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => time(),
        'bio' => '',
    ];

    save_users($users);
    $_SESSION['username'] = $username;
    return null;
}

function login_user($username, $password) {
    $users = get_users();
    foreach ($users as $u) {
        if ($u['username'] === $username && password_verify($password, $u['password_hash'])) {
            $_SESSION['username'] = $username;
            return null;
        }
    }
    return __('invalid_credentials');
}

function logout_user() {
    session_destroy();
    unset($_SESSION['username']);
}
