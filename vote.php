<?php
require_once __DIR__ . '/functions.php';
require_login();

$post_id = $_POST['post_id'] ?? '';
$action = $_POST['action'] ?? '';
$redirect_url = $_POST['redirect'] ?? 'index.php';

update_vote($post_id, $_SESSION['username'], $action);
redirect($redirect_url);
