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

function db() {
    static $conn = null;
    static $migrated = false;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('DB error: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    if (!$migrated) {
        $migrated = true;
        require_once __DIR__ . '/migrations.php';
        run_migrations($conn);
    }
    return $conn;
}

function get_user($username) {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_user_by_id($id) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function create_user($username, $password_hash) {
    $stmt = db()->prepare("INSERT INTO users (id, username, password_hash, created_at) VALUES (?, ?, ?, ?)");
    $id = generate_id();
    $time = time();
    $stmt->bind_param('sssi', $id, $username, $password_hash, $time);
    $stmt->execute();
}

function get_posts() {
    $conn = db();
    $result = $conn->query("
        SELECT p.*,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        ORDER BY p.created_at DESC
    ");
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $row['tags'] = json_decode($row['tags'] ?? '[]', true) ?: [];
        $row['comments'] = [];
        $row['voters'] = [];
        $row['content_preview'] = '';
        $posts[$row['id']] = $row;
    }

    if (!empty($posts) && is_logged_in()) {
        $ids = array_keys($posts);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('s', count($ids));
        $stmt = $conn->prepare("SELECT post_id, username, vote_type FROM votes WHERE post_id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $vresult = $stmt->get_result();
        while ($vrow = $vresult->fetch_assoc()) {
            $posts[$vrow['post_id']]['voters'][$vrow['username']] = $vrow['vote_type'];
        }
    }

    return array_values($posts);
}

function get_post($id) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    if (!$post) return null;

    $post['tags'] = json_decode($post['tags'] ?? '[]', true) ?: [];

    $cstmt = $conn->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC");
    $cstmt->bind_param('s', $id);
    $cstmt->execute();
    $cresult = $cstmt->get_result();
    $post['comments'] = [];
    while ($c = $cresult->fetch_assoc()) {
        $post['comments'][] = $c;
    }

    $vstmt = $conn->prepare("SELECT username, vote_type FROM votes WHERE post_id = ?");
    $vstmt->bind_param('s', $id);
    $vstmt->execute();
    $vresult = $vstmt->get_result();
    $post['voters'] = [];
    while ($v = $vresult->fetch_assoc()) {
        $post['voters'][$v['username']] = $v['vote_type'];
    }

    return $post;
}

function create_post($title, $content, $author, $tags) {
    $stmt = db()->prepare("INSERT INTO posts (id, title, content, author, tags, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $id = generate_id();
    $time = time();
    $tags_json = json_encode($tags);
    $stmt->bind_param('sssssi', $id, $title, $content, $author, $tags_json, $time);
    $stmt->execute();
    return $id;
}

function add_comment($post_id, $author, $content) {
    $stmt = db()->prepare("INSERT INTO comments (id, post_id, author, content, created_at) VALUES (?, ?, ?, ?, ?)");
    $id = generate_id();
    $time = time();
    $stmt->bind_param('ssssi', $id, $post_id, $author, $content, $time);
    $stmt->execute();
}

function update_vote($post_id, $username, $action) {
    $conn = db();
    $stmt = $conn->prepare("SELECT vote_type FROM votes WHERE post_id = ? AND username = ?");
    $stmt->bind_param('ss', $post_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();

    if ($action === 'upvote') {
        if ($existing) {
            if ($existing['vote_type'] === 'up') {
                $dstmt = $conn->prepare("DELETE FROM votes WHERE post_id = ? AND username = ?");
                $dstmt->bind_param('ss', $post_id, $username);
                $dstmt->execute();
                $ustmt = $conn->prepare("UPDATE posts SET upvotes = GREATEST(upvotes - 1, 0) WHERE id = ?");
                $ustmt->bind_param('s', $post_id);
                $ustmt->execute();
            } else {
                $ustmt = $conn->prepare("UPDATE votes SET vote_type = 'up' WHERE post_id = ? AND username = ?");
                $ustmt->bind_param('ss', $post_id, $username);
                $ustmt->execute();
                $ustmt2 = $conn->prepare("UPDATE posts SET upvotes = upvotes + 1, downvotes = GREATEST(downvotes - 1, 0) WHERE id = ?");
                $ustmt2->bind_param('s', $post_id);
                $ustmt2->execute();
            }
        } else {
            $istmt = $conn->prepare("INSERT INTO votes (post_id, username, vote_type) VALUES (?, ?, 'up')");
            $istmt->bind_param('ss', $post_id, $username);
            $istmt->execute();
            $ustmt = $conn->prepare("UPDATE posts SET upvotes = upvotes + 1 WHERE id = ?");
            $ustmt->bind_param('s', $post_id);
            $ustmt->execute();
        }
    } elseif ($action === 'downvote') {
        if ($existing) {
            if ($existing['vote_type'] === 'down') {
                $dstmt = $conn->prepare("DELETE FROM votes WHERE post_id = ? AND username = ?");
                $dstmt->bind_param('ss', $post_id, $username);
                $dstmt->execute();
                $ustmt = $conn->prepare("UPDATE posts SET downvotes = GREATEST(downvotes - 1, 0) WHERE id = ?");
                $ustmt->bind_param('s', $post_id);
                $ustmt->execute();
            } else {
                $ustmt = $conn->prepare("UPDATE votes SET vote_type = 'down' WHERE post_id = ? AND username = ?");
                $ustmt->bind_param('ss', $post_id, $username);
                $ustmt->execute();
                $ustmt2 = $conn->prepare("UPDATE posts SET downvotes = downvotes + 1, upvotes = GREATEST(upvotes - 1, 0) WHERE id = ?");
                $ustmt2->bind_param('s', $post_id);
                $ustmt2->execute();
            }
        } else {
            $istmt = $conn->prepare("INSERT INTO votes (post_id, username, vote_type) VALUES (?, ?, 'down')");
            $istmt->bind_param('ss', $post_id, $username);
            $istmt->execute();
            $ustmt = $conn->prepare("UPDATE posts SET downvotes = downvotes + 1 WHERE id = ?");
            $ustmt->bind_param('s', $post_id);
            $ustmt->execute();
        }
    }
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
