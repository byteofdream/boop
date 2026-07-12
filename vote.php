<?php
require_once __DIR__ . '/functions.php';
require_login();

$post_id = $_POST['post_id'] ?? '';
$action = $_POST['action'] ?? '';
$redirect_url = $_POST['redirect'] ?? 'index.php';
$username = $_SESSION['username'];

$posts = get_posts();
foreach ($posts as &$p) {
    if ($p['id'] === $post_id) {
        if (!isset($p['voters'])) $p['voters'] = [];
        $current = $p['voters'][$username] ?? null;

        if ($action === 'upvote') {
            if ($current === 'up') {
                unset($p['voters'][$username]);
                $p['upvotes'] = max(0, ($p['upvotes'] ?? 0) - 1);
            } else {
                if ($current === 'down') {
                    $p['downvotes'] = max(0, ($p['downvotes'] ?? 0) - 1);
                }
                $p['voters'][$username] = 'up';
                $p['upvotes'] = ($p['upvotes'] ?? 0) + 1;
            }
        } elseif ($action === 'downvote') {
            if ($current === 'down') {
                unset($p['voters'][$username]);
                $p['downvotes'] = max(0, ($p['downvotes'] ?? 0) - 1);
            } else {
                if ($current === 'up') {
                    $p['upvotes'] = max(0, ($p['upvotes'] ?? 0) - 1);
                }
                $p['voters'][$username] = 'down';
                $p['downvotes'] = ($p['downvotes'] ?? 0) + 1;
            }
        }
        break;
    }
}
save_posts($posts);
redirect($redirect_url);
