<?php
// Dedicated local test router. This file is outside the public document root.
$_ENV['app.baseURL'] = 'http://127.0.0.1:8081/';
$_SERVER['app.baseURL'] = $_ENV['app.baseURL'];
$public = realpath(__DIR__ . '/../public');
$file = realpath($public . rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
if ($file && str_starts_with($file, $public . DIRECTORY_SEPARATOR) && is_file($file)) { return false; }
require $public . '/index.php';
