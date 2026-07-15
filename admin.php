<?php
require_once __DIR__ . '/functions.php';
require_admin();

$tab = $_GET['tab'] ?? 'users';
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ban' && isset($_POST['username'])) {
        $username = trim($_POST['username']);
        $reason = trim($_POST['reason'] ?? '');
        if (get_user($username)) {
            ban_user($username, $reason);
            $success = __('admin_banned', ['user' => $username]);
        } else {
            $error = __('user_not_found');
        }
    }

    if ($action === 'unban' && isset($_POST['username'])) {
        unban_user(trim($_POST['username']));
        $success = __('admin_unbanned', ['user' => trim($_POST['username'])]);
    }

    if ($action === 'checkmark_on' && isset($_POST['username'])) {
        set_checkmark(trim($_POST['username']), 1);
        $success = __('admin_checkmark_on', ['user' => trim($_POST['username'])]);
    }

    if ($action === 'checkmark_off' && isset($_POST['username'])) {
        set_checkmark(trim($_POST['username']), 0);
        $success = __('admin_checkmark_off', ['user' => trim($_POST['username'])]);
    }

    if ($action === 'set_admin' && isset($_POST['username'])) {
        $u = trim($_POST['username']);
        $stmt = db()->prepare("UPDATE users SET role = 'admin' WHERE username = ?");
        $stmt->bind_param('s', $u);
        $stmt->execute();
        $success = __('admin_set_admin', ['user' => $u]);
    }

    if ($action === 'unset_admin' && isset($_POST['username'])) {
        $u = trim($_POST['username']);
        if ($u === $_SESSION['username']) {
            $error = __('admin_cannot_demote_self');
        } else {
            $stmt = db()->prepare("UPDATE users SET role = 'user' WHERE username = ?");
            $stmt->bind_param('s', $u);
            $stmt->execute();
            $success = __('admin_unset_admin', ['user' => $u]);
        }
    }

    if ($action === 'delete_post' && isset($_POST['post_id'])) {
        $stmt = db()->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param('s', $_POST['post_id']);
        $stmt->execute();
        $success = __('admin_post_deleted');
    }

    if ($action === 'delete_comment' && isset($_POST['comment_id'])) {
        $stmt = db()->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param('s', $_POST['comment_id']);
        $stmt->execute();
        $success = __('admin_comment_deleted');
    }
}

$users = get_all_users();
$banned_users = get_banned_users();

$title = __('admin_panel');
require_once __DIR__ . '/header.php';
?>

<div class="tabs">
    <a href="?tab=users" class="tab <?= $tab === 'users' ? 'active' : '' ?>"><?= __('admin_users') ?></a>
    <a href="?tab=bans" class="tab <?= $tab === 'bans' ? 'active' : '' ?>"><?= __('admin_bans') ?></a>
    <a href="?tab=posts" class="tab <?= $tab === 'posts' ? 'active' : '' ?>"><?= __('admin_posts') ?></a>
</div>

<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($tab === 'users'): ?>
<table>
    <thead>
    <tr>
        <th><?= __('username') ?></th>
        <th><?= __('admin_role') ?></th>
        <th><?= __('admin_checkmark') ?></th>
        <th><?= __('admin_actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
        <td>
            <a href="profile.php?user=<?= urlencode($u['username']) ?>"><?= htmlspecialchars($u['username']) ?></a>
        </td>
        <td>
            <?php if (($u['role'] ?? 'user') === 'admin'): ?>
                <span class="badge badge-accent"><?= __('admin') ?></span>
            <?php else: ?>
                <span class="badge"><?= __('user') ?></span>
            <?php endif; ?>
        </td>
        <td>
            <?php if (has_checkmark($u['username'])): ?>
                <span class="checkmark-badge">&#10003;</span>
            <?php else: ?>
                <span class="text-muted">&mdash;</span>
            <?php endif; ?>
        </td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if (has_checkmark($u['username'])): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="checkmark_off">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                    <button type="submit" class="btn btn-small btn-outline"><?= __('admin_remove_checkmark') ?></button>
                </form>
            <?php else: ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="checkmark_on">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                    <button type="submit" class="btn btn-small btn-outline"><?= __('admin_add_checkmark') ?></button>
                </form>
            <?php endif; ?>

            <?php if (!is_banned($u['username'])): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="ban">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                    <input type="text" name="reason" placeholder="<?= __('admin_reason') ?>" style="width:120px;font-size:12px;padding:6px 10px;display:inline-block">
                    <button type="submit" class="btn btn-small btn-danger"><?= __('admin_ban') ?></button>
                </form>
            <?php else: ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="unban">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                    <button type="submit" class="btn btn-small btn-outline"><?= __('admin_unban') ?></button>
                </form>
            <?php endif; ?>

            <?php if (($u['role'] ?? 'user') === 'admin'): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="unset_admin">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                    <button type="submit" class="btn btn-small btn-outline"><?= __('admin_remove_admin') ?></button>
                </form>
            <?php else: ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="set_admin">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                    <button type="submit" class="btn btn-small btn-outline"><?= __('admin_make_admin') ?></button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php elseif ($tab === 'bans'): ?>
<?php if (empty($banned_users)): ?>
<p style="color:var(--text-muted)"><?= __('admin_no_bans') ?></p>
<?php else: ?>
<table>
    <thead>
    <tr>
        <th><?= __('username') ?></th>
        <th><?= __('admin_banned_by') ?></th>
        <th><?= __('admin_reason') ?></th>
        <th><?= __('admin_date') ?></th>
        <th><?= __('admin_actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($banned_users as $b): ?>
    <tr>
        <td><a href="profile.php?user=<?= urlencode($b['username']) ?>"><?= htmlspecialchars($b['username']) ?></a></td>
        <td><?= htmlspecialchars($b['banned_by']) ?></td>
        <td><?= htmlspecialchars($b['reason'] ?: '-') ?></td>
        <td><?= time_ago($b['created_at']) ?></td>
        <td>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="unban">
                <input type="hidden" name="username" value="<?= htmlspecialchars($b['username']) ?>">
                <button type="submit" class="btn btn-small btn-outline"><?= __('admin_unban') ?></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php elseif ($tab === 'posts'): ?>
<?php
$all_posts = get_posts();
usort($all_posts, 'cmp_posts_newest');
$all_posts = array_slice($all_posts, 0, 50);
?>
<?php if (empty($all_posts)): ?>
<p style="color:var(--text-muted)"><?= __('no_posts_yet') ?></p>
<?php else: ?>
<table>
    <thead>
    <tr>
        <th><?= __('post_title') ?></th>
        <th><?= __('username') ?></th>
        <th><?= __('admin_date') ?></th>
        <th><?= __('admin_actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($all_posts as $p): ?>
    <tr>
        <td><a href="post.php?id=<?= urlencode($p['id']) ?>"><?= htmlspecialchars($p['title']) ?></a></td>
        <td><?= htmlspecialchars($p['author']) ?></td>
        <td><?= time_ago($p['created_at']) ?></td>
        <td>
            <form method="post" style="display:inline" onsubmit="return confirm('<?= __('admin_confirm_delete') ?>')">
                <input type="hidden" name="action" value="delete_post">
                <input type="hidden" name="post_id" value="<?= htmlspecialchars($p['id']) ?>">
                <button type="submit" class="btn btn-small btn-danger"><?= __('admin_delete') ?></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
