<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) redirect('index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = login_user($_POST['username'] ?? '', $_POST['password'] ?? '');
    if (!$error) redirect('index.php');
}

$title = __('login_title');
require_once __DIR__ . '/header.php';
?>

<div class="auth-form">
<h1><?= __('login') ?></h1>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post">
<div class="form-group">
<label><?= __('username') ?></label>
<input type="text" name="username" required autocomplete="username">
</div>
<div class="form-group">
<label><?= __('password') ?></label>
<input type="password" name="password" required autocomplete="current-password">
</div>
<button type="submit" class="btn"><?= __('login') ?></button>
<p style="margin-top:16px;font-size:13px;color:var(--text-tertiary)"><?= __('no_account') ?></p>
</form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
