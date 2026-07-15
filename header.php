<?php
if (function_exists('opcache_reset')) opcache_reset();
require_once __DIR__ . '/config.php';

$level_up_toast = null;
if (is_logged_in()) {
    $level_up_toast = check_level_up($_SESSION['username']);
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($title) ? htmlspecialchars($title) . ' — ' : '' ?><?= SITE_NAME ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php if ($level_up_toast): ?>
<div class="level-up-toast" id="levelUpToast">
  <div class="level-up-toast-icon">&#9733;</div>
  <div class="level-up-toast-body">
    <div class="level-up-toast-title"><?= __('level_up_title') ?></div>
    <div class="level-up-toast-msg"><?= __('level_up', ['level' => $level_up_toast]) ?></div>
  </div>
  <button class="level-up-toast-close" onclick="document.getElementById('levelUpToast').remove()">&times;</button>
</div>
<script>
setTimeout(function(){ var e=document.getElementById('levelUpToast'); if(e){ e.style.opacity='0'; e.style.transform='translateX(24px)'; setTimeout(function(){ e.remove(); }, 400); } }, 5000);
</script>
<?php endif; ?>
<header>
<a href="index.php" class="logo">boop</a>
<form action="search.php" method="get">
<input type="text" name="q" placeholder="<?= __('search_placeholder') ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
<button type="submit"><?= __('search') ?></button>
</form>
<nav>
<?php if (is_logged_in()): ?>
<a href="create_post.php"><?= __('new_post') ?></a>
<a href="profile.php?user=<?= urlencode($_SESSION['username']) ?>"><?= __('profile') ?></a>
<a href="logout.php"><?= __('logout') ?></a>
<?php else: ?>
<a href="login.php"><?= __('login') ?></a>
<a href="register.php"><?= __('register') ?></a>
<?php endif; ?>
<a href="?<?php
$params = $_GET;
unset($params['lang']);
$params['lang'] = $lang_code === 'ru' ? 'en' : 'ru';
echo http_build_query($params);
?>" class="lang-switch"><?= $lang_code === 'ru' ? 'EN' : 'RU' ?></a>
</nav>
</header>
<div class="container">
