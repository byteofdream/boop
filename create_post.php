<?php
require_once __DIR__ . '/functions.php';
require_login();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_val = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!$title_val) $error = __('title_required');
    elseif (!$content) $error = __('content_required');
    else {
        $id = create_post($title_val, $content, $_SESSION['username'], extract_tags($content));
        $success = __('post_created');
        redirect('post.php?id=' . urlencode($id));
    }
}

$title = __('new_post_title');
require_once __DIR__ . '/header.php';
?>

<h1 style="font-size:22px;color:var(--text-primary);margin-bottom:20px"><?= __('new_post_title') ?></h1>

<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post" id="post-form">
<div class="form-group">
<label><?= __('post_title') ?></label>
<input type="text" name="title" maxlength="200" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
</div>
<div class="form-group">
<label><?= __('content') ?></label>
<p style="font-size:12px;color:var(--text-tertiary);margin-bottom:8px"><?= __('formatting_hint') ?></p>
<textarea name="content" id="content-textarea" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
</div>
<div class="upload-area" id="upload-area" onclick="document.getElementById('file-input').click()">
<?= __('upload_hint') ?>
</div>
<input type="file" id="file-input" accept="image/*" multiple style="display:none">
<div class="upload-preview" id="upload-preview"></div>

<button type="submit" class="btn"><?= __('publish') ?></button>
</form>

<script>
document.getElementById('file-input').addEventListener('change', function(e) {
    for (let file of e.target.files) {
        uploadFile(file);
    }
});

document.getElementById('upload-area').addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});
document.getElementById('upload-area').addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});
document.getElementById('upload-area').addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    for (let file of e.dataTransfer.files) {
        if (file.type.startsWith('image/')) uploadFile(file);
    }
});

function uploadFile(file) {
    if (file.size > <?= MAX_FILE_SIZE ?>) { alert('<?= __('file_too_large') ?>'); return; }
    var formData = new FormData();
    formData.append('file', file);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var fn = xhr.responseText.trim();
            if (fn) {
                var ta = document.getElementById('content-textarea');
                ta.value += '\n[img]' + fn + '[/img]\n';
                var preview = document.getElementById('upload-preview');
                var img = document.createElement('img');
                img.src = 'uploads/images/' + fn;
                img.alt = fn;
                preview.appendChild(img);
            } else {
                alert('<?= __('upload_error') ?>: ' + xhr.responseText);
            }
        } else {
            alert('<?= __('upload_error') ?>: ' + xhr.status);
        }
    };
    xhr.send(formData);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
