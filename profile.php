<?php
require_once __DIR__ . '/functions.php';

$username = $_GET['user'] ?? '';
$user = get_user($username);
if (!$user) { $title = __('user_not_found'); require __DIR__ . '/header.php'; echo '<div class="empty-state"><h2>' . __('user_not_found') . '</h2><p>' . __('go_home') . '</p></div>'; require __DIR__ . '/footer.php'; exit; }

$is_owner = is_logged_in() && $_SESSION['username'] === $username;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_owner && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        if (in_array($mime, $GLOBALS['allowed_image_types'])) {
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $dest = AVATAR_DIR . '/' . $username . '.' . $ext;
            foreach (glob(AVATAR_DIR . '/' . $username . '.*') as $old) unlink($old);
            move_uploaded_file($file['tmp_name'], $dest);
            redirect('profile.php?user=' . urlencode($username));
        }
    }
}

$posts = get_posts();
$user_posts = array_filter($posts, fn($p) => $p['author'] === $username);
usort($user_posts, 'cmp_posts_newest');

$stats = get_user_stats($username);
$badges = get_user_badges($username);
$levelInfo = get_user_level($username);

$title = $username;
require_once __DIR__ . '/header.php';
?>

<div class="profile-header">
<div style="position:relative">
<img src="<?= get_avatar_url($username) ?>" alt="" class="<?= $is_owner ? 'avatar-upload' : '' ?>" id="avatar-img" title="<?= $is_owner ? __('edit_profile') : '' ?>">
<?php if ($is_owner): ?>
<form method="post" enctype="multipart/form-data" id="avatar-form" style="display:none">
<input type="file" name="avatar" id="avatar-input" accept="image/*">
</form>
<?php endif; ?>
</div>
<div>
<div class="username"><?= htmlspecialchars($username) ?></div>
<div class="meta"><?= __('joined', ['time' => time_ago($user['created_at'])]) ?> &middot; <?= count($user_posts) ?> <?= __('posts') ?></div>
</div>
</div>

<?php if ($is_owner): ?>
<script>
document.getElementById('avatar-img').addEventListener('click', function() {
    document.getElementById('avatar-input').click();
});
document.getElementById('avatar-input').addEventListener('change', function() {
    this.form.submit();
});
</script>
<?php endif; ?>

<div class="profile-stats">
  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-value"><?= $stats['posts'] ?></span>
      <span class="stat-label"><?= __('posts') ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-value"><?= $stats['comments'] ?></span>
      <span class="stat-label"><?= __('comments') ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-value"><?= $stats['score'] ?></span>
      <span class="stat-label"><?= __('score') ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-value"><?= $stats['age_days'] ?></span>
      <span class="stat-label"><?= __('days_on_site') ?></span>
    </div>
  </div>

  <div class="level-bar">
    <div class="level-info">
      <span class="level-badge"><?= __('level') ?> <?= $levelInfo['level'] ?></span>
      <span class="level-xp"><?= $levelInfo['xp'] ?> / <?= $levelInfo['next_xp'] ?> XP</span>
    </div>
    <div class="level-progress">
      <div class="level-progress-fill" style="width: <?= round($levelInfo['progress']) ?>%"></div>
    </div>
  </div>

  <?php if (!empty($badges)): ?>
  <h4 class="badges-title"><?= __('achievements') ?></h4>
  <div class="badges-grid">
    <?php foreach ($badges as $badge): ?>
    <div class="badge-card badge-tier-<?= $badge['tier'] ?>" title="<?= htmlspecialchars($badge['desc']) ?>">
      <span class="badge-dot"></span>
      <span class="badge-name"><?= htmlspecialchars($badge['name']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<h3 style="color:var(--text-tertiary);font-size:14px;margin:0 0 16px;text-transform:uppercase;letter-spacing:0.5px"><?= __('your_posts') ?></h3>

<?php if (empty($user_posts)): ?>
<p style="color:var(--text-muted);font-size:14px"><?= __('no_posts_yet') ?></p>
<?php else: ?>
<?php foreach ($user_posts as $post): ?>
<div class="card">
<div class="post-title"><a href="post.php?id=<?= urlencode($post['id']) ?>"><?= htmlspecialchars($post['title']) ?></a></div>
<div class="post-meta"><?= time_ago($post['created_at']) ?> &middot; <?= __('score') ?>: <?= get_post_score($post) ?> &middot; <?= count($post['comments'] ?? []) ?> <?= __('comments') ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
