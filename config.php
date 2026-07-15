<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123123123');
define('DB_NAME', 'boop');

define('DATA_DIR', __DIR__ . '/data');
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('AVATAR_DIR', UPLOAD_DIR . '/avatars');
define('IMAGE_DIR', UPLOAD_DIR . '/images');
define('SITE_NAME', 'boop');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ITEMS_PER_PAGE', 20);

$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
