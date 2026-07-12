<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($title) ? htmlspecialchars($title) . ' — ' : '' ?><?= SITE_NAME ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
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
