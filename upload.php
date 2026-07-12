<?php
ini_set('display_errors', 0);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo __('no_file');
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo __('upload_error') . ' ' . $file['error'];
    exit;
}

if ($file['size'] > MAX_FILE_SIZE) {
    http_response_code(400);
    echo __('file_too_large');
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);

$ext = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => null,
};

if (!$ext) {
    http_response_code(400);
    echo __('invalid_type');
    exit;
}

$filename = generate_id() . '.' . $ext;
$dest = IMAGE_DIR . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo __('save_failed');
    exit;
}

echo $filename;
