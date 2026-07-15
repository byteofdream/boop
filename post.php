<?php
require_once __DIR__ . '/functions.php';

$id = $_GET['id'] ?? '';
$post = get_post($id);
if (!$post) { $title = __('post_not_found'); require __DIR__ . '/header.php'; echo '<div class="empty-state"><h2>' . __('post_not_found') . '</h2><p>' . __('go_home') . '</p></div>'; require __DIR__ . '/footer.php'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && is_logged_in()) {
    if (is_banned($_SESSION['username'])) {
        redirect('login.php');
    }
    add_comment($id, $_SESSION['username'], trim($_POST['comment']));
    redirect('post.php?id=' . urlencode($id));
}

$title = $post['title'];
require_once __DIR__ . '/header.php';
?>

<div class="card">
<div class="post-header">
<img class="post-avatar" src="<?= get_avatar_url($post['author']) ?>" alt="">
<div style="flex:1">
<div class="post-meta">
<a href="profile.php?user=<?= urlencode($post['author']) ?>"><?= htmlspecialchars($post['author']) ?></a><?php if (has_checkmark($post['author'])): ?><span class="checkmark">&#10003;</span><?php endif; ?>
&middot; <?= time_ago($post['created_at']) ?>
</div>
<div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
<div class="post-content"><?= format_text($post['content']) ?></div>
<?php if (!empty($post['tags'])): ?>
<div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px">
<?php foreach ($post['tags'] as $tag): ?>
<a href="search.php?q=%23<?= urlencode($tag) ?>" class="tag">#<?= htmlspecialchars($tag) ?></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
<div class="post-footer">
<div class="post-actions">
<form action="vote.php" method="post" style="display:flex;align-items:center;gap:4px">
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input type="hidden" name="action" value="upvote">
<input type="hidden" name="redirect" value="post.php?id=<?= urlencode($id) ?>">
<button class="vote-btn <?= (isset($post['voters'][$_SESSION['username'] ?? '']) && $post['voters'][$_SESSION['username']] === 'up') ? 'upvoted' : '' ?>" type="submit">&uarr;</button>
</form>
<span class="vote-score"><?= get_post_score($post) ?></span>
<form action="vote.php" method="post" style="display:flex;align-items:center;gap:4px">
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input type="hidden" name="action" value="downvote">
<input type="hidden" name="redirect" value="post.php?id=<?= urlencode($id) ?>">
<button class="vote-btn <?= (isset($post['voters'][$_SESSION['username'] ?? '']) && $post['voters'][$_SESSION['username']] === 'down') ? 'downvoted' : '' ?>" type="submit">&darr;</button>
</form>
</div>
</div>
</div>

<h3 style="color:var(--text-tertiary);font-size:14px;margin:24px 0 12px;text-transform:uppercase;letter-spacing:0.5px"><?= __('comments_count', ['count' => count($post['comments'] ?? [])]) ?></h3>

<?php if (is_logged_in()): ?>
<form method="post" style="margin-bottom:20px">
<div class="form-group">
<textarea name="comment" placeholder="<?= __('write_comment') ?>" style="min-height:80px" required></textarea>
</div>
<button type="submit" class="btn btn-small"><?= __('comment') ?></button>
</form>
<?php else: ?>
<p style="color:var(--text-tertiary);font-size:14px;margin-bottom:16px"><?= __('login_to_comment') ?></p>
<?php endif; ?>

<?php if (empty($post['comments'])): ?>
<p style="color:var(--text-muted);font-size:13px"><?= __('no_comments_yet') ?></p>
<?php else: ?>
<?php foreach (array_reverse($post['comments']) as $cmt): ?>
<div class="comment">
<div class="meta">
<a href="profile.php?user=<?= urlencode($cmt['author']) ?>"><?= htmlspecialchars($cmt['author']) ?></a><?php if (has_checkmark($cmt['author'])): ?><span class="checkmark">&#10003;</span><?php endif; ?>
&middot; <?= time_ago($cmt['created_at']) ?>
</div>
<div class="body"><?= format_comment_text($cmt['content']) ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
