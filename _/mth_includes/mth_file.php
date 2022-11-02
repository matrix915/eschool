<?php
if (extension_loaded('newrelic')) {
  newrelic_disable_autorum(TRUE);
  newrelic_ignore_transaction(TRUE);
  newrelic_ignore_apdex(TRUE);
  newrelic_background_job(TRUE);
}
include $_SERVER['DOCUMENT_ROOT'] . '/_/app/inc.php';

$file = mth_file::getByHash(req_get::txt('hash'));

if (!$file) {
  core_loader::print404headers();
  exit();
}

header('Access-Control-Allow-Origin: *');
header('Content-type: ' . $file->type());
header('Content-Disposition: attachment; filename="' . $file->name(true) . '"');
header("Access-Control-Allow-Headers: X-Requested-With");

echo $file->contents();
