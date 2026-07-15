<?php
require_once __DIR__ . '/functions.php';

$sort = $_GET['sort'] ?? 'new';
$page = max(1, intval($_GET['page'] ?? 1));
$posts = get_posts();

if ($sort === 'top') {
    usort($posts, 'cmp_posts_top');
} else {
    usort($posts, 'cmp_posts_newest');
}

$total = count($posts);
$offset = ($page - 1) * ITEMS_PER_PAGE;
$page_posts = array_slice($posts, $offset, ITEMS_PER_PAGE);
$total_pages = ceil($total / ITEMS_PER_PAGE);

$title = $sort === 'top' ? __('top') : __('new');
require_once __DIR__ . '/header.php';
?>

<div class="sort-bar">
<a href="?sort=new" class="<?= $sort === 'new' ? 'active' : '' ?>"><?= __('new') ?></a>
<a href="?sort=top" class="<?= $sort === 'top' ? 'active' : '' ?>"><?= __('top') ?></a>
</div>

<?php if (empty($page_posts)): ?>
<div class="empty-state">
<h2><?= __('no_posts_yet') ?></h2>
<p><?= __('be_first_post') ?></p>
</div>
<?php endif; ?>

<?php foreach ($page_posts as $post): ?>
<div class="card">
<div class="post-header">
<img class="post-avatar" src="<?= get_avatar_url($post['author']) ?>" alt="">
<div style="flex:1">
<div class="post-meta">
<a href="profile.php?user=<?= urlencode($post['author']) ?>"><?= htmlspecialchars($post['author']) ?></a><?php if (has_checkmark($post['author'])): ?><span class="checkmark">&#10003;</span><?php endif; ?>
&middot; <?= time_ago($post['created_at']) ?>
</div>
<div class="post-title"><a href="post.php?id=<?= urlencode($post['id']) ?>"><?= htmlspecialchars($post['title']) ?></a></div>
<div class="post-content post-preview"><?= format_text($post['content']) ?></div>
</div>
</div>
<div class="post-footer">
<div class="post-actions">
<form action="vote.php" method="post" style="display:flex;align-items:center;gap:4px">
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input type="hidden" name="action" value="upvote">
<button class="vote-btn <?= (isset($post['voters'][$_SESSION['username'] ?? '']) && $post['voters'][$_SESSION['username']] === 'up') ? 'upvoted' : '' ?>" type="submit">&uarr;</button>
</form>
<span class="vote-score"><?= get_post_score($post) ?></span>
<form action="vote.php" method="post" style="display:flex;align-items:center;gap:4px">
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input type="hidden" name="action" value="downvote">
<button class="vote-btn <?= (isset($post['voters'][$_SESSION['username'] ?? '']) && $post['voters'][$_SESSION['username']] === 'down') ? 'downvoted' : '' ?>" type="submit">&darr;</button>
</form>
</div>
<a href="post.php?id=<?= urlencode($post['id']) ?>">&#9998; <?= count($post['comments'] ?? []) ?> <?= __('comments') ?></a>
</div>
</div>
<?php endforeach; ?>

<?php if ($total_pages > 1): ?>
<div class="sort-bar" style="justify-content:center;margin-top:20px">
<?php for ($i = 1; $i <= $total_pages; $i++): ?>
<a href="?sort=<?= $sort ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
