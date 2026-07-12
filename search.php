<?php
require_once __DIR__ . '/functions.php';

$q = trim($_GET['q'] ?? '');
$results = [];

if ($q) {
    $posts = get_posts();
    $is_tag_search = $q[0] === '#';
    $search_tag = $is_tag_search ? mb_strtolower(substr($q, 1)) : '';

    foreach ($posts as $p) {
        $match = false;
        if ($is_tag_search) {
            foreach ($p['tags'] as $tag) {
                if (mb_strpos(mb_strtolower($tag), $search_tag) !== false) { $match = true; break; }
            }
        } else {
            $lc_q = mb_strtolower($q);
            if (mb_strpos(mb_strtolower($p['title']), $lc_q) !== false || mb_strpos(mb_strtolower($p['content']), $lc_q) !== false) {
                $match = true;
            }
            foreach ($p['tags'] as $tag) {
                if (mb_strpos(mb_strtolower($tag), $lc_q) !== false) { $match = true; break; }
            }
        }
        if ($match) $results[] = $p;
    }
    usort($results, 'cmp_posts_newest');
}

$title = $q ? __('search_title', ['query' => $q]) : __('search');
require_once __DIR__ . '/header.php';
?>

<h1 style="font-size:20px;color:#eee;margin-bottom:4px"><?= __('search') ?></h1>
<p style="font-size:13px;color:#666;margin-bottom:20px">
<?php if ($q): ?>
<?= __('results_for', ['count' => count($results), 'query' => htmlspecialchars($q)]) ?>
<?php else: ?>
<?= __('search_hint') ?>
<?php endif; ?>
</p>

<?php if ($q && empty($results)): ?>
<div class="empty-state">
<h2><?= __('no_results') ?></h2>
<p><?= __('try_different') ?></p>
</div>
<?php endif; ?>

<?php foreach ($results as $post): ?>
<div class="card">
<div class="post-header">
<img class="post-avatar" src="<?= get_avatar_url($post['author']) ?>" alt="">
<div style="flex:1">
<div class="post-meta">
<a href="profile.php?user=<?= urlencode($post['author']) ?>"><?= htmlspecialchars($post['author']) ?></a>
&middot; <?= time_ago($post['created_at']) ?>
</div>
<div class="post-title"><a href="post.php?id=<?= urlencode($post['id']) ?>"><?= htmlspecialchars($post['title']) ?></a></div>
<div class="post-content post-preview"><?= format_text($post['content']) ?></div>
</div>
</div>
<div class="post-footer">
<span><?= __('score') ?>: <?= get_post_score($post) ?></span>
<a href="post.php?id=<?= urlencode($post['id']) ?>"><?= count($post['comments'] ?? []) ?> <?= __('comments') ?></a>
</div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
