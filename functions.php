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
    return $stmt->get_result()->fetch_assoc();
}

function get_user_by_id($id) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function create_user($username, $password_hash) {
    $id = generate_id();
    $time = time();
    $check = db()->query("SELECT COUNT(*) FROM users");
    $count = (int) $check->fetch_row()[0];
    $role = $count === 0 ? 'admin' : 'user';
    $stmt = db()->prepare("INSERT INTO users (id, username, password_hash, created_at, last_level, role) VALUES (?, ?, ?, ?, 0, ?)");
    $stmt->bind_param('sssis', $id, $username, $password_hash, $time, $role);
    $stmt->execute();
}

function get_posts() {
    $conn = db();
    $result = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count FROM posts p ORDER BY p.created_at DESC");
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $row['tags'] = json_decode($row['tags'] ?? '[]', true) ?: [];
        $row['comment_count'] = (int) ($row['comment_count'] ?? 0);
        $row['comments'] = [];
        $row['voters'] = [];
        $posts[] = $row;
    }
    if (is_logged_in()) {
        foreach ($posts as &$p) {
            $vstmt = $conn->prepare("SELECT username, vote_type FROM votes WHERE post_id = ?");
            $vstmt->bind_param('s', $p['id']);
            $vstmt->execute();
            foreach ($vstmt->get_result() as $v) {
                $p['voters'][$v['username']] = $v['vote_type'];
            }
        }
        unset($p);
    }
    return $posts;
}

function get_post($id) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    if (!$post) return null;
    $post['tags'] = json_decode($post['tags'] ?? '[]', true) ?: [];
    $cstmt = $conn->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC");
    $cstmt->bind_param('s', $id);
    $cstmt->execute();
    $post['comments'] = $cstmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $vstmt = $conn->prepare("SELECT username, vote_type FROM votes WHERE post_id = ?");
    $vstmt->bind_param('s', $id);
    $vstmt->execute();
    $post['voters'] = [];
    foreach ($vstmt->get_result() as $v) {
        $post['voters'][$v['username']] = $v['vote_type'];
    }
    return $post;
}

function create_post($title, $content, $author, $tags) {
    $id = generate_id();
    $time = time();
    $tj = json_encode($tags);
    $stmt = db()->prepare("INSERT INTO posts (id, title, content, author, tags, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssi', $id, $title, $content, $author, $tj, $time);
    $stmt->execute();
    return $id;
}

function add_comment($post_id, $author, $content) {
    $id = generate_id();
    $time = time();
    $stmt = db()->prepare("INSERT INTO comments (id, post_id, author, content, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssi', $id, $post_id, $author, $content, $time);
    $stmt->execute();
}

function update_vote($post_id, $username, $action) {
    $conn = db();
    $stmt = $conn->prepare("SELECT vote_type FROM votes WHERE post_id = ? AND username = ?");
    $stmt->bind_param('ss', $post_id, $username);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($action === 'upvote') {
        if ($existing) {
            if ($existing['vote_type'] === 'up') {
                $d = $conn->prepare("DELETE FROM votes WHERE post_id = ? AND username = ?");
                $d->bind_param('ss', $post_id, $username); $d->execute();
                $u = $conn->prepare("UPDATE posts SET upvotes = GREATEST(upvotes - 1, 0) WHERE id = ?");
                $u->bind_param('s', $post_id); $u->execute();
            } else {
                $upd = $conn->prepare("UPDATE votes SET vote_type = 'up' WHERE post_id = ? AND username = ?");
                $upd->bind_param('ss', $post_id, $username); $upd->execute();
                $u = $conn->prepare("UPDATE posts SET upvotes = upvotes + 1, downvotes = GREATEST(downvotes - 1, 0) WHERE id = ?");
                $u->bind_param('s', $post_id); $u->execute();
            }
        } else {
            $ins = $conn->prepare("INSERT INTO votes (post_id, username, vote_type) VALUES (?, ?, 'up')");
            $ins->bind_param('ss', $post_id, $username); $ins->execute();
            $u = $conn->prepare("UPDATE posts SET upvotes = upvotes + 1 WHERE id = ?");
            $u->bind_param('s', $post_id); $u->execute();
        }
    } elseif ($action === 'downvote') {
        if ($existing) {
            if ($existing['vote_type'] === 'down') {
                $d = $conn->prepare("DELETE FROM votes WHERE post_id = ? AND username = ?");
                $d->bind_param('ss', $post_id, $username); $d->execute();
                $u = $conn->prepare("UPDATE posts SET downvotes = GREATEST(downvotes - 1, 0) WHERE id = ?");
                $u->bind_param('s', $post_id); $u->execute();
            } else {
                $upd = $conn->prepare("UPDATE votes SET vote_type = 'down' WHERE post_id = ? AND username = ?");
                $upd->bind_param('ss', $post_id, $username); $upd->execute();
                $u = $conn->prepare("UPDATE posts SET downvotes = downvotes + 1, upvotes = GREATEST(upvotes - 1, 0) WHERE id = ?");
                $u->bind_param('s', $post_id); $u->execute();
            }
        } else {
            $ins = $conn->prepare("INSERT INTO votes (post_id, username, vote_type) VALUES (?, ?, 'down')");
            $ins->bind_param('ss', $post_id, $username); $ins->execute();
            $u = $conn->prepare("UPDATE posts SET downvotes = downvotes + 1 WHERE id = ?");
            $u->bind_param('s', $post_id); $u->execute();
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
    $text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
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
    $text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
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
    $colors = ['8B5CF6', '7C3AED', 'A78BFA', '6D28D9', '9D4EDD', 'C084FC'];
    $hash = crc32($username);
    $color = $colors[abs($hash) % count($colors)];
    $initial = strtoupper($username[0] ?? '?');
    return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" fill="#' . $color . '" rx="40"/><text x="40" y="54" text-anchor="middle" fill="#fff" font-family="Arial,sans-serif" font-size="34" font-weight="bold">' . $initial . '</text></svg>');
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

/* ===== ADMIN / BAN / CHECKMARK ===== */

function is_admin() {
    if (!is_logged_in()) return false;
    $user = get_user($_SESSION['username']);
    return $user && ($user['role'] ?? 'user') === 'admin';
}

function require_admin() {
    if (!is_admin()) {
        redirect('index.php');
    }
}

function is_banned($username) {
    $stmt = db()->prepare("SELECT 1 FROM bans WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

function require_not_banned() {
    if (is_logged_in() && is_banned($_SESSION['username'])) {
        session_destroy();
        unset($_SESSION['username']);
        redirect('login.php');
    }
}

function ban_user($username, $reason = '') {
    $time = time();
    $stmt = db()->prepare("INSERT IGNORE INTO bans (username, banned_by, reason, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssi', $username, $_SESSION['username'], $reason, $time);
    $stmt->execute();
}

function unban_user($username) {
    $stmt = db()->prepare("DELETE FROM bans WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
}

function get_banned_users() {
    $result = db()->query("SELECT b.*, u.role FROM bans b JOIN users u ON b.username = u.username ORDER BY b.created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_all_users() {
    $result = db()->query("SELECT * FROM users ORDER BY created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function set_checkmark($username, $value) {
    $v = $value ? 1 : 0;
    $stmt = db()->prepare("UPDATE users SET checkmark = ? WHERE username = ?");
    $stmt->bind_param('is', $v, $username);
    $stmt->execute();
}

function has_checkmark($username) {
    $user = get_user($username);
    return $user && ($user['checkmark'] ?? 0) == 1;
}

/* ===== ACHIEVEMENT SYSTEM ===== */

function get_user_stats($username) {
    $conn = db();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE author = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $posts = (int) $stmt->get_result()->fetch_row()[0];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE author = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $comments = (int) $stmt->get_result()->fetch_row()[0];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(upvotes - downvotes), 0) FROM posts WHERE author = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $score = (int) $stmt->get_result()->fetch_row()[0];

    $user = get_user($username);
    $age_days = $user ? (int) floor((time() - $user['created_at']) / 86400) : 0;

    return compact('posts', 'comments', 'score', 'age_days');
}

function get_user_badges($username) {
    $s = get_user_stats($username);
    $badges = [];

    $defs = [
        'age' => [
            ['id' => 'baby',      'need' => 0,    'tier' => 1],
            ['id' => 'growing',   'need' => 1,    'tier' => 1],
            ['id' => 'regular',   'need' => 7,    'tier' => 2],
            ['id' => 'veteran',   'need' => 30,   'tier' => 3],
            ['id' => 'ancient',   'need' => 365,  'tier' => 4],
        ],
        'posts' => [
            ['id' => 'first_post',   'need' => 1,  'tier' => 1],
            ['id' => 'writer',       'need' => 5,  'tier' => 2],
            ['id' => 'author',       'need' => 10, 'tier' => 3],
            ['id' => 'publisher',    'need' => 25, 'tier' => 4],
        ],
        'comments' => [
            ['id' => 'first_comment', 'need' => 1,  'tier' => 1],
            ['id' => 'talkative',     'need' => 10, 'tier' => 2],
            ['id' => 'chatty',        'need' => 50, 'tier' => 3],
        ],
        'score' => [
            ['id' => 'liked',     'need' => 5,   'tier' => 1],
            ['id' => 'popular',   'need' => 25,  'tier' => 2],
            ['id' => 'famous',    'need' => 100, 'tier' => 3],
            ['id' => 'legendary', 'need' => 500, 'tier' => 4],
        ],
    ];

    $map = ['age' => $s['age_days'], 'posts' => $s['posts'], 'comments' => $s['comments'], 'score' => $s['score']];

    foreach ($defs as $cat => $list) {
        $earned = null;
        foreach ($list as $b) {
            if ($map[$cat] >= $b['need']) $earned = $b;
        }
        if ($earned) {
            $badges[] = [
                'id' => $earned['id'],
                'tier' => $earned['tier'],
                'name' => __("badge_{$earned['id']}"),
                'desc' => __("badge_{$earned['id']}_desc"),
            ];
        }
    }

    return $badges;
}

function get_user_level($username) {
    $s = get_user_stats($username);
    $xp = ($s['posts'] * 10) + ($s['comments'] * 5) + max(0, $s['score']);
    $level = max(1, (int) floor(sqrt($xp / 12)) + 1);
    $next_xp = (int) pow($level, 2) * 12;
    $prev_xp = (int) pow($level - 1, 2) * 12;
    $progress = $next_xp > $prev_xp ? ($xp - $prev_xp) / ($next_xp - $prev_xp) * 100 : 100;
    return ['level' => $level, 'xp' => $xp, 'next_xp' => $next_xp, 'progress' => min(100, max(0, $progress))];
}

function check_level_up($username) {
    $user = get_user($username);
    if (!$user) return null;
    $prev = (int) ($user['last_level'] ?? 0);
    $info = get_user_level($username);
    if ($info['level'] <= $prev) return null;
    $stmt = db()->prepare("UPDATE users SET last_level = ? WHERE username = ?");
    $stmt->bind_param('is', $info['level'], $username);
    $stmt->execute();
    return $info['level'];
}
