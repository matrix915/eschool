<?php

/**
 * notify
 *
 * @author abe
 */
class core_notify
{
    protected static $messageArr;
    protected static $errorArr;
    protected static $warningArr;

    public static function init()
    {
        self::$messageArr = &$_SESSION[core_config::getCoreSessionVar()]['notify']['message'];
        self::$errorArr = &$_SESSION[core_config::getCoreSessionVar()]['notify']['error'];
        self::$warningArr = &$_SESSION[core_config::getCoreSessionVar()]['notify']['warning'];

        if (!isset(self::$messageArr)) {
            self::reset();
        }
    }

    public static function addError($errorMessage)
    {
        self::init();
        if (is_array($errorMessage)) {
            foreach ($errorMessage as $message) {
                self::addError($message);
            }
            return;
        }
        if (!in_array($errorMessage, self::$errorArr, true)) {
            self::$errorArr[] = $errorMessage;
        }
    }

    public static function addWarning($warningMessage)
    {
        self::init();
        if (is_array($warningMessage)) {
            foreach ($warningMessage as $message) {
                self::addWarning($message);
            }
            return;
        }
        if (!in_array($warningMessage, self::$warningArr, true)) {
            self::$warningArr[] = $warningMessage;
        }
    }

    public static function addMessage($message)
    {
        self::init();
        if (is_array($message)) {
            foreach ($message as $thisMessage) {
                self::addMessage($thisMessage);
            }
            return;
        }
        if (!in_array($message, self::$messageArr, true)) {
            self::$messageArr[] = $message;
        }
    }

    public static function reset()
    {
        self::$errorArr = array();
        self::$messageArr = array();
        self::$warningArr = array();
    }

    public static function printNotices()
    {
        self::init();
        if (!empty(self::$errorArr)) {
            ?>
            <div class="core-notify-erros">
                <ul>
                    <?php foreach (self::$errorArr as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }

        if (!empty(self::$messageArr)) {
            ?>
            <div class="core-notify-messages">
                <ul>
                    <?php foreach (self::$messageArr as $message): ?>
                        <li><?= $message ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }

        if (!empty(self::$warningArr)) {
            ?>
            <div class="core-notify-warning">
                <ul>
                    <?php foreach (self::$warningArr as $message): ?>
                        <li><?= $message ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }

        self::reset();
    }

    public static function printNotice_remark(){
        self::init();
        ?>
            <script>
                $(function(){
                    toastr.options = {"positionClass": "toast-bottom-full-width","timeOut": "5000","extendedTimeOut": "1000","closeButton": true};
                    <?php
                     if (!empty(self::$errorArr)):
                        foreach (self::$errorArr as $error):
                    ?>
                        toastr.error("<?= $error ?>")
                    <?php 
                        endforeach;
                    endif;

                    if (!empty(self::$messageArr)):
                       foreach (self::$messageArr as $message):
                    ?>
                        toastr.success("<?= $message ?>")
                    <?php 
                        endforeach;
                    endif;
                    
                    if (!empty(self::$warningArr)):
                        foreach (self::$warningArr as $message):
                     ?>
                         toastr.warning("<?= $message ?>")
                     <?php 
                         endforeach;
                     endif;
                     ?>

                    
                   
                });
            </script>
        <?php
        self::reset();
    }

    public static function hasNotifications()
    {
        self::init();
        return !empty(self::$errorArr) || !empty(self::$messageArr) || !empty(self::$warningArr);
    }

    public static function hasErrors()
    {
        self::init();
        return !empty(self::$errorArr);
    }
}
