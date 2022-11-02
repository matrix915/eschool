<?php
$url = $_SERVER['REQUEST_URI'];
$host = $_SERVER['HTTP_HOST'];

if (strpos($host, 'www.') === 0) {
    header('location: http://' . substr($host, -1 * (strlen($host) - 4)) . $url);
} elseif ($host === 'sis.mytechhigh.com' && empty($_SERVER['HTTPS'])) {
    header('location: https://' . $host . $url);
} elseif ($host === 'staging.mytechhigh.com' && !empty($_SERVER['HTTPS'])) {
    header('location: http://' . $host . $url);
}

require './_/app/inc.php';
core_loader::printPage();
