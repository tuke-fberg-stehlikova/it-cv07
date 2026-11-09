<?php
$prefix = '/student/test.user/it/cv07';
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, $prefix) !== 0) { http_response_code(404); echo 'bad prefix'; return true; }
$_SERVER['SCRIPT_NAME'] = $prefix . '/index.php';
unset($_SERVER['HTTPS']);   // test cez http: secure cookie vypnutá, nech sa vracia
chdir(__DIR__ . '/..');
require __DIR__ . '/../index.php';
return true;
