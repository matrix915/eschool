<?php
require_once realpath(dirname(__FILE__) . '/../..').'/_/app/inc.php';

define('DATE_FILE', core_config::getSitePath() . '/_/cron/archiver');

if (!is_file(DATE_FILE)) {
    file_put_contents(DATE_FILE, '0');
}

$today = date('Ymd');
$lastRun = date('Ymd', file_get_contents(DATE_FILE));

($today != $lastRun) || die();

file_put_contents(DATE_FILE, time());

while($archive = mth_archive::getPendings()){
    if($archive->isDue()){
        $archive->execute();
    }
}   