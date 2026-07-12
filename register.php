<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) redirect('index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = register_user($_POST['username'] ?? '', $_POST['password'] ?? '');
    if (!$error) redirect('index.php');
}

$title = __('register_title');
require_once __DIR__ . '/header.php';
?>

<div class="auth-form">
<h1><?= __('register') ?></h1>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post">
<div class="form-group">
<label><?= __('username') ?></label>
<input type="text" name="username" required autocomplete="username" maxlength="30">
</div>
<div class="form-group">
<label><?= __('password') ?></label>
<input type="password" name="password" required autocomplete="new-password" minlength="4">
</div>
<button type="submit" class="btn"><?= __('register') ?></button>
<p style="margin-top:16px;font-size:13px;color:#666"><?= __('has_account') ?></p>
</form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
