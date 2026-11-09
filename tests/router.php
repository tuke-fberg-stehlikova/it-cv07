<?php
// Emuluje Apache s mod_rewrite a mod_userdir pre php -S: všetko pod prefixom ide do index.php.
$prefix = '/student/test.user/it/cv06';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = dirname(__DIR__);
if (str_starts_with($uri, $prefix)) {
    $file = $root . substr($uri, strlen($prefix));
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
    $_SERVER['SCRIPT_NAME'] = $prefix . '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
    chdir($root);
    require $root . '/index.php';
    return true;
}
http_response_code(404);
echo 'outside prefix';
return true;
