<?php
/* @var $reportArr ARRAY */
/* @var $file */

//this file should be included in a -content.php page with $reportArr set

if (empty($reportArr)) {
    core_notify::addError('No report data');
    core_loader::reloadParent();
}


function prepForCSV($value)
{
    $value = req_sanitize::txt_decode($value);
    $quotes = false;
    if (strpos($value, '"') !== false) {
        $value = str_replace('"', '""', $value);
        $quotes = true;
    }
    if (!$quotes && (strpos($value, ',') !== false || strpos($value, "\n") !== false)) {
        $quotes = true;
    }
    if ($quotes) {
        $value = '"' . trim($value) . '"';
    }
    return $value;
}



if (req_get::bool('csv')) {
    core_notify::reset();
    include 'inc/csv.php';
} elseif (req_get::bool('google')) {
    core_notify::reset();
    include 'inc/google.php';
} elseif (isset($currentLoad)) {
} else {
    include 'inc/html.php';
}
