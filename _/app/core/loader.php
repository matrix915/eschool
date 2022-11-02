<?php

/**
 *
 *
 * @author abe
 */
class core_loader
{
    protected static $cssRefs = array();
    protected static $jsRefs = array();
    protected static $isActualFile = false;
    protected static $popUp = false;
    protected static $indexes = array();
    protected static $headerItems = array();
    protected static $printedJsCssRefs = array();
    protected static $cacheManafest;
    protected static $classRefs = array();

    /* @var core_path */
    protected static $path;


    public static function printPage(core_path $path = NULL)
    {
        header('Content-Type:text/html; charset=UTF-8');
        if (req_get::bool('core_loader-getSecureItem')) {
            self::getSecureItem();
        }
        if (!$path) {
            $path = core_path::getPath();
        }
        self::$path = $path;
        cms_page::setDefaultPage($path);
        cms_page::executeDefaultPageRedirect();

        if (core_user::isUserAdmin() || core_user::isUserSubAdmin()) {
            include $_SERVER['DOCUMENT_ROOT'] . '/_/includes/global-admin-functions.php';
        }

        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/' . $path->getString() . '-content.php')) {
            self::$isActualFile = true;
            include $_SERVER['DOCUMENT_ROOT'] . '/' . $path->getString() . '-content.php';
        } elseif (is_file($_SERVER['DOCUMENT_ROOT'] . '/' . $path->getString() . '/-content.php')) {
            self::$isActualFile = true;
            include $_SERVER['DOCUMENT_ROOT'] . '/' . $path->getString() . '/-content.php';
        } elseif (($indexPath = self::isUnderIndex($path->getString()))) {
            self::$isActualFile = true;
            include $_SERVER['DOCUMENT_ROOT'] . '/' . $indexPath . '-content.php';
        } else {
            if (cms_page::isDefaultPage404()) {
                self::print404headers();
            }
            if (core_user::getUserLevel() < 1) {
                header('Cache-Control: max-age=86400');
            }
            include core_config::getThemePath($path->isAdmin()) . '/content.php';
        }
    }

    public static function useThemeTemplate()
    {
        include core_config::getThemePath(core_path::getPath()->isAdmin()) . '/content.php';
        exit();
    }

    public static function setPath(core_path $path)
    {
        self::$path = $path;
    }

    public static function print404headers()
    {
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
    }

    public static function addIndex(core_path $path)
    {
        self::$indexes[] = $path->getString();
    }

    public static function isUnderIndex($pathStr)
    {
        foreach (self::$indexes as $indexPath) {
            if (strpos($pathStr, $indexPath) === 0) {
                return $indexPath;
            }
        }
        return false;
    }

    public static function isPopUp($popUp = TRUE)
    {
        self::$popUp = (bool) $popUp;
    }

    public static function addJsRef($name, $path)
    {
        self::$jsRefs[strtolower($name)] = $path;
    }

    public static function addCssRef($name, $path)
    {
        self::$cssRefs[strtolower($name)] = $path;
    }

    public static function addClassRef($name)
    {
        self::$classRefs[] = $name;
    }

    public static function addHeaderItem($html)
    {
        self::$headerItems[] = $html;
    }

    public static function printJsCssRefs()
    {
        $nocache = '';
        if (
            is_file(core_config::getSitePath() . '/.git-hash')
            && ($g = file_get_contents(core_config::getSitePath() . '/.git-hash'))
        ) {
            $nocache = '?' . substr($g, 0, 10);
        }
        self::printCssRefs($nocache);
        if (!isset(self::$printedJsCssRefs['standard']['headerItems'])) {
            foreach (self::$headerItems as $html) {
                echo $html;
            }
            self::$printedJsCssRefs['standard']['headerItems'] = true;
        }
        self::printJsRefs($nocache);
    }

    protected static function printCssRefsOnly()
    {
        $nocache = '';
        if (
            is_file(core_config::getSitePath() . '/.git-hash')
            && ($g = file_get_contents(core_config::getSitePath() . '/.git-hash'))
        ) {
            $nocache = '?' . substr($g, 0, 10);
        }
        self::printCssRefs($nocache);
    }

    protected static function printCssRefs($nocache)
    {
        if (!isset(self::$printedJsCssRefs['standard']['css'])) {
            echo '<link rel="stylesheet" type="text/css" href="/_/includes/global.css' . $nocache . '"> ';
            self::$printedJsCssRefs['standard']['css'] = true;
        }
        foreach (self::$cssRefs as $ref) {
            if (!isset(self::$printedJsCssRefs['css'][$ref])) {
                echo '<link rel="stylesheet" type="text/css" href="' . $ref . ($nocache && strpos($ref, '?') === FALSE ? $nocache : '') . '"> ';
                self::$printedJsCssRefs['css'][$ref] = true;
            }
        }
    }

    public static function printClassRefs()
    {
        if (isset(self::$classRefs)) {
            echo  implode(' ', self::$classRefs);
        }
        self::$classRefs = array();
    }

    protected static function printJsRefs($nocache)
    {
        if (!isset(self::$printedJsCssRefs['standard']['js'])) {
            echo '<script type="text/javascript" src="/_/includes/jquery/jquery-1.11.1.min.js' . $nocache . '"></script> ',
                '<script type="text/javascript" src="/_/includes/global.js' . $nocache . '"></script> ';
            if (core_user::isUserAdmin()) {
                echo '<script type="text/javascript" src="/_/includes/global-admin.js' . $nocache . '"></script> ';
            }
            self::$printedJsCssRefs['standard']['js'] = true;
        }
        foreach (self::$jsRefs as $ref) {
            if (!isset(self::$printedJsCssRefs['js'][$ref])) {
                echo '<script type="text/javascript" src="' . $ref . ($nocache && strpos($ref, '?') === FALSE ? $nocache : '') . '"></script> ';
                self::$printedJsCssRefs['js'][$ref] = true;
            }
        }
    }

    public static function addCacheManafest($uri)
    {
        self::$cacheManafest = $uri;
    }

    public static function printHtmlAttributes()
    {
        if (self::$cacheManafest) {
            echo 'manifest="' . self::$cacheManafest . '"';
        }
    }

    public static function printFooterContent($printMenu = true, $printCredits = true)
    {
        if (self::$popUp) {
            return '';
        }
        if (core_user::getUserID() && $printMenu) {
            include ROOT . '/_/includes/user-menu.php';
        }
        if ($printCredits) {
            echo '<div id="credits"><a href="http://www.goodfront.com/"><img src="/_/includes/img/goodfront.png" alt="GoodFront"><br>Website by Abe Fawson</a></div>';
        }
    }

    public static function printMTHFooterContent($inline = false)
    {
        $credit = '&copy; ' . date('Y') . ' <a target="_blank" href="https://www.mytechhigh.com/">My Tech High, Inc.</a>';
        $links = ' <a href="https://docs.google.com/document/d/1q-LZ8dTk5vgbsFXbo9eNN-cJagbYfz2LP6kuI7NGn2A/pub" target="_blank">
        Terms of Use</a> |
        <a href="https://docs.google.com/document/d/1ZzNWCD8ri27Tl6UImxAXl0jV8vrwo_MgFvUtUrjbJWc/pub" target="_blank">Privacy &amp; COPPA Policy</a>';
        echo $inline ? ($credit . ' ' . $links) : ('<p>' . $credit . '</p><p>' . $links . '</p>');
    }

    public static function printHeader($header = NULL)
    {
        $themePath = core_config::getThemePath(self::$path->isAdmin());
        if (self::$popUp && !$header) {
            $header = 'popup';
        }
        if ($header && is_file($themePath . '/header-' . $header . '.php')) {
            include $themePath . '/header-' . $header . '.php';
        } else {
            include $themePath . '/header.php';
        }
    }

    public static function printBreadCrumb($header = NULL)
    {
        $themePath = core_config::getThemePath(self::$path->isAdmin());
        if ($header && is_file($themePath . '/breadcrumb-' . $header . '.php')) {
            include $themePath . '/breadcrumb-' . $header . '.php';
        } else {
            include $themePath . '/breadcrumb.php';
        }
    }

    public static function printFooter($footer = NULL)
    {
        $themePath = core_config::getThemePath(self::$path->isAdmin());
        if (self::$popUp && !$footer) {
            $footer = 'popup';
        }
        if ($footer && is_file($themePath . '/footer-' . $footer . '.php')) {
            include $themePath . '/footer-' . $footer . '.php';
        } else {
            include $themePath . '/footer.php';
        }
    }

    public static function printBodyClass()
    {
        if (self::isHome()) {
            echo 'home ';
        }

        foreach (self::$path->getArray() as $pathPart) {
            if ($pathPart == '_') {
                continue;
            }
            $pathArr[] = $pathPart;
            echo implode('_', $pathArr) . ' ';
        }

        echo self::$isActualFile ? 'real-page' : 'db-page';

        if (self::$popUp) {
            echo ' popup';
        }
    }

    public static function isHome()
    {
        return self::$path == '/';
    }

    public static function redirect($path = null)
    {
        if ($path) {
            $query = parse_url($path, PHP_URL_QUERY);
            $hash = parse_url($path, PHP_URL_FRAGMENT);
        }
        header('location: ' . core_path::getPath($path) . (!empty($query) ? '?' . $query : '') . (!empty($hash) ? '#' . $hash : ''));
        exit();
    }

    public static function reloadParent($message = NULL, $messageIsError = true, $url = null, $additional_script = null)
    {
        if ($message && $messageIsError) {
            core_notify::addError($message);
        } elseif ($message) {
            core_notify::addMessage($message);
        }
        exit('<!DOCTYPE html><html><script>
      if(parent.global_waiting){
        parent.global_waiting();
      }
      parent.location.' . ($url ? 'href="' . $url . '"' : 'reload(true);') . ';
      parent.location.reload(true); //incase the url provided had a hash
      </script></html>');
    }

    public static function includejQueryValidate()
    {
        core_loader::addJsRef('jQrueyValidate', '/_/includes/jquery-validate/jquery.validate.min.js');
        core_loader::addJsRef('jQrueyValidateAdtl', '/_/includes/jquery-validate/additional-methods.min.js');
    }

    public static function includeDataTables()
    {
        core_loader::addCssRef('DataTables', 'https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.css');
        core_loader::addJsRef('DataTables', 'https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.js');
    }

    /**
     * Load Bootstrap Datatable resource
     *
     * @param $resource js|css
     * @return void
     */
    public static function includeBootstrapDataTables($resource = null)
    {
        if ($resource == 'css' || $resource == null) {
            core_loader::addCssRef('DataTablesCorecss', 'https://cdn.datatables.net/v/bs/dt-1.10.16/af-2.2.2/b-1.4.2/cr-1.4.1/fc-3.2.3/fh-3.1.3/kt-2.3.2/r-2.2.0/rg-1.0.2/rr-1.2.3/sc-1.4.3/sl-1.2.3/datatables.min.css');
            core_loader::addCssRef('DataTablesBootstrapcss', core_config::getThemeURI() . '/vendor/datatable/datatable.bootstrap.min.css');
        }
        if ($resource == 'js' || $resource == null) {
            core_loader::addJsRef('DataTablesCore', core_config::getThemeURI() . '/vendor/datatable/datatable.min.js');
            core_loader::addJsRef('DataTablesBootstrap', core_config::getThemeURI() . '/vendor/datatable/datatable.bootstrap.min.js');
        }
    }

    public static function includejQueryUI()
    {
        core_loader::addJsRef('jQueryUI', '/_/includes/jquery-ui/js/jquery-ui-1.10.4.custom.min.js');
        core_loader::addCssRef('jQueryUI', '/_/includes/jquery-ui/css/smoothness/jquery-ui-1.10.4.custom.min.css');
    }

    public static function includeCKEditor()
    {
        core_loader::addJsRef('ckeditor', '/_/includes/ckeditor/ckeditor.js');
        core_loader::addJsRef('ckeditor-jqueryadapter', '/_/includes/ckeditor/adapters/jquery.js');
    }

    public static function includeCKEditorFive()
    {
        core_loader::addJsRef('ckeditor', '/_/includes/ckeditor5/ckeditor.js');
    }

    /**
     * Checks if the $formSubmissionIdentifier has been used brefor. Returns true if it has not, then adds it to the list of used identifiers
     * @param string $formSubmissionIdentifier
     * @return bool
     */
    public static function formSubmitable($formSubmissionIdentifier = NULL)
    {
        if (is_null($formSubmissionIdentifier)) {
            $formSubmissionIdentifier = self::formSecureValidate();
            if (!$formSubmissionIdentifier) {
                return FALSE;
            }
        }
        if (!isset($_SESSION[core_config::getCoreSessionVar()]['formSubmissions'])) {
            $_SESSION[core_config::getCoreSessionVar()]['formSubmissions'] = array();
        }
        $submitted = in_array((string) $formSubmissionIdentifier, $_SESSION[core_config::getCoreSessionVar()]['formSubmissions'], true);
        $_SESSION[core_config::getCoreSessionVar()]['formSubmissions'][] = (string) $formSubmissionIdentifier;
        return !$submitted;
    }

    public static function formSecureValidate()
    {
        if (
            !isset($_SESSION[core_config::getCoreSessionVar()]['formSubmissions'])
            || !req_post::bool('core_loader-secureItem')
            || empty($_SESSION[core_config::getCoreSessionVar()]['core_loader-secureItem'])
            || req_post::txt('core_loader-secureItem') != core_user::encodePass($_SESSION[core_config::getCoreSessionVar()]['core_loader-secureItem'])
        ) {
            return false;
        }
        return $_SESSION[core_config::getCoreSessionVar()]['core_loader-secureItem'];
    }

    public static function formSecureSubmit($formID, $action, $getSecureItemFromPath = '')
    {
        ?>
        <script>
            $('#<?= $formID ?>').attr('action', '<?= req_sanitize::url($action) ?>').submit(function() {
                if ($(this).find('input[name="core_loader-secureItem"]').length === 0) {
                    var form = $(this);
                    if (form.is('[novalidate]') && !form.valid()) {
                        return false;
                    }
                    global_waiting();
                    $.ajax({
                        url: '<?= $getSecureItemFromPath ?>?core_loader-getSecureItem=<?= $formID ?>',
                        success: function(data) {
                            $('#<?= $formID ?>').append('<input type="hidden" name="core_loader-secureItem" value="' + data + '">').submit();
                        }
                    });
                    return false;
                }
            });
        </script>
<?php
    }

    public static function getSecureItem()
    {
        if (!isset($_SESSION[core_config::getCoreSessionVar()]['formSubmissions'])) {
            $_SESSION[core_config::getCoreSessionVar()]['formSubmissions'] = array();
        }
        $val = uniqid(uniqid(req_get::txt('core_loader-getSecureItem') . '-') . '-');
        $_SESSION[core_config::getCoreSessionVar()]['core_loader-secureItem'] = $val;
        exit(core_user::encodePass($val));
    }
}
