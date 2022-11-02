<?php
/* @var $reportArr ARRAY */
/* @var $file */

if (!mth_google::isAuthenticated()) {
    mth_google::redirectToAuthenticatationURL();
}

$content = '';
foreach ($reportArr as $row) {
    $content .= implode(',', array_map('prepForCSV', $row)) . "\n";
}

if (mth_google::sendFile($file, 'text/csv', $content, true)) {
    core_notify::addMessage('Report sent to Google Drive');
} else {
    core_notify::addError('Unable to send the report to Google Drive!');
}


?>
<!DOCTYPE html>
<html>
<script>
    window.opener.location.reload(true);
    window.close();
</script>
</html>