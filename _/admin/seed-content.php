<?php

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

?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Seeder</title>
    </head>

    <body>
        <h3>Seeding..</h3>
        <?php
//        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-packet-manual-seeder.php";
//        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-allow-none-periods-seeder.php";
//        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-advance-add-diploma-seeking.php";
//        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-schedule_settings-add-diploma-seeking.php";
//        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-schedule-status-reminders-seeder.php";
//        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-allow-none-periods-seeder.php";
//        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-age-issue-email-seeder.php";
        include "$_SERVER[DOCUMENT_ROOT]/_/config/seeder/core_settings-bcc-seeder.php";
        ?>
        <?php if (core_notify::hasNotifications()) : ?>
            <h2>Errors</h2>
            <?php core_notify::printNotices() ?>
        <?php else : ?>
            <h4>Success</h4>
        <?php
        endif; ?>
    </body>

    </html>