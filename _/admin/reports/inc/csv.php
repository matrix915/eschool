<?php
/* @var $reportArr ARRAY */
/* @var $file */

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="' . $file . '.csv"');

foreach ($reportArr as $row) {
    echo implode(',', array_map('prepForCSV', $row)) . "\n";
}