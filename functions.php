<?php
require_once __DIR__ . '/config.php';

$lang = [];
function load_language() {
    global $lang;
    $supported = ['en', 'ru'];
    $code = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'ru';
    $code = in_array($code, $supported) ? $code : 'ru';
    if ($code !== ($_COOKIE['lang'] ?? null)) setcookie('lang', $code, time() + 86400 * 365, '/');
    $lang = require __DIR__ . '/lang/' . $code . '.php';
    return $code;
}
$lang_code = load_language();

function __($key, $vars = []) {
    global $lang;
    $str = $lang[$key] ?? $key;
    foreach ($vars as $k => $v) $str = str_replace('{' . $k . '}', $v, $str);
    return $str;
}

function json_read($file) {
    $path = DATA_DIR . '/' . $file;
    if (!file_exists($path)) return [];
    $data = file_get_contents($path);
    return json_decode($data, true) ?: [];
}

function json_write($file, $data) {
    $path = DATA_DIR . '/' . $file;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function get_users() { return json_read('users.json'); }
function save_users($users) { json_write('users.json', $users); }
function get_posts() { return json_read('posts.json'); }
function save_posts($posts) { json_write('posts.json', $posts); }

function get_user($username) {
    $users = get_users();
    foreach ($users as $u) {
        if ($u['username'] === $username) return $u;
    }
    return null;
}

function get_user_by_id($id) {
    $users = get_users();
    foreach ($users as $u) {
        if ($u['id'] === $id) return $u;
    }
    return null;
}

function get_post($id) {
    $posts = get_posts();
    foreach ($posts as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function generate_id() {
    return bin2hex(random_bytes(8));
}

function time_ago($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

function format_text($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/\[img\](.+?)\[\/img\]/', '<div class="post-media"><img src="uploads/images/$1" alt="image" loading="lazy"></div>', $text);
    $text = preg_replace('/#(\w+)/', '<a href="search.php?q=%23$1" class="tag">#$1</a>', $text);
    $text = nl2br($text);
    return $text;
}

function extract_tags($text) {
    preg_match_all('/#(\w+)/', $text, $matches);
    return array_unique($matches[1]);
}

function format_comment_text($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/#(\w+)/', '<a href="search.php?q=%23$1" class="tag">#$1</a>', $text);
    $text = nl2br($text);
    return $text;
}

function get_avatar_url($username) {
    $path = 'uploads/avatars/' . $username . '.webp';
    if (file_exists(__DIR__ . '/' . $path)) return $path;
    $path = 'uploads/avatars/' . $username . '.jpg';
    if (file_exists(__DIR__ . '/' . $path)) return $path;
    $path = 'uploads/avatars/' . $username . '.png';
    if (file_exists(__DIR__ . '/' . $path)) return $path;
    $path = 'uploads/avatars/' . $username . '.gif';
    if (file_exists(__DIR__ . '/' . $path)) return $path;
    $colors = ['ff6600', 'ff8833', 'cc5500', 'ffaa44', 'dd6600'];
    $hash = crc32($username);
    $color = $colors[abs($hash) % count($colors)];
    $initial = strtoupper($username[0] ?? '?');
    return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" fill="#' . $color . '" rx="40"/><text x="40" y="47" text-anchor="middle" fill="#fff" font-family="Arial,sans-serif" font-size="32" font-weight="bold">' . $initial . '</text></svg>');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function is_logged_in() {
    return isset($_SESSION['username']);
}

function require_login() {
    if (!is_logged_in()) redirect('login.php');
}

function get_post_score($post) {
    return ($post['upvotes'] ?? 0) - ($post['downvotes'] ?? 0);
}

function cmp_posts_newest($a, $b) {
    return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
}

function cmp_posts_top($a, $b) {
    $sA = get_post_score($a);
    $sB = get_post_score($b);
    if ($sB !== $sA) return $sB - $sA;
    return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
}
