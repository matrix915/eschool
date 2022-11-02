<?php
require_once '../app/inc.php';

function setupErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
            echo "  Fatal error on line $errline in file $errfile";
            echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
            echo "Aborting...<br />\n";
            exit(1);
            break;

        case E_USER_WARNING:
            core_notify::addError("<b>My WARNING</b> [$errno] $errstr");
            break;

        case E_USER_NOTICE:
            core_notify::addError("<b>My NOTICE</b> [$errno] $errstr");
            break;

        default:
            core_notify::addError("Unknown error type: [$errno] $errstr");
            break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}

set_error_handler("setupErrorHandler");
$defaultSettingsRan = false;
if ((empty($_GET['catchup']) && core_config::DBinstall())
    || (!empty($_GET['catchup']) && core_config::DBcatchupLog())
) {
    require_once '../config/default_settings.php';
    $defaultSettingsRan = true;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup</title>
</head>
<body>
<h1>Setup Ran</h1>
<h2>Database Files Installed</h2>
<p><?= implode('<br>', core_config::DBgetInstalled()) ?></p>
<?php if ($defaultSettingsRan): ?><p>config/default_settings.php ran</p><?php endif; ?>
<?php if (core_notify::hasNotifications()): ?>
    <h2>Errors</h2>
    <?php core_notify::printNotices() ?>
<?php endif; ?>
</body>
</html>