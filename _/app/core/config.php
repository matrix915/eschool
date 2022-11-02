<?php
if (!defined('ROOT')) {
    define('ROOT', realpath(dirname(__FILE__) . '/../../..'));
}

/**
 * Core Configuration class. Should not be instantiated
 *
 * @author abe
 */
class core_config
{
    private static $_config;
    private static $_installed = [];

    const ENV_PRODUCTION = 1;
    const ENV_STAGING = 2;
    const ENV_DEVELOPMENT = 3;

    private static $environmentLabels = [
      self::ENV_DEVELOPMENT => 'development',
      self::ENV_STAGING => 'staging',
      self::ENV_PRODUCTION => 'production',
    ];

    public static function setEnvironment($environment = self::ENV_DEVELOPMENT)
    {
        self::$_config['Environment'] = $environment;
    }

    public static function isProduction()
    {
        return self::$_config['Environment'] == self::ENV_PRODUCTION;
    }

    public static function isStaging()
    {
        return self::$_config['Environment'] == self::ENV_STAGING;
    }

    public static function isDevelopment()
    {
        return self::$_config['Environment'] == self::ENV_DEVELOPMENT;
    }

    public static function isEnvironment($environment)
    {
        return self::$_config['Environment'] == $environment;
    }

    public static function getEnvironment()
    {
        return self::$_config['Environment'];
    }

    public static function getEnvironmentLabel()
    {
        return self::$environmentLabels[self::getEnvironment()];
    }

    public static function setDbHost($value, $conName = 'DEFAULT')
    {
        self::$_config['DbHost'][$conName] = $value;
    }

    public static function setDbUser($value, $conName = 'DEFAULT')
    {
        self::$_config['DbUser'][$conName] = $value;
    }

    public static function setDbPass($value, $conName = 'DEFAULT')
    {
        self::$_config['DbPass'][$conName] = $value;
    }

    public static function setDb($value, $conName = 'DEFAULT')
    {
        self::$_config['Db'][$conName] = $value;
    }

    public static function getDbHost($conName = 'DEFAULT')
    {
        return !empty(self::$_config['DbHost'][$conName]) ? self::$_config['DbHost'][$conName] : 'localhost';
    }

    public static function getDbUser($conName = 'DEFAULT')
    {
        return self::$_config['DbUser'][$conName];
    }

    public static function getDbPass($conName = 'DEFAULT')
    {
        return self::$_config['DbPass'][$conName];
    }

    public static function getDb($conName = 'DEFAULT')
    {
        return self::$_config['Db'][$conName];
    }

    public static function setTheme($value)
    {
        self::$_config['Theme'] = $value;
    }

    public static function getTheme()
    {
        return !empty(self::$_config['Theme']) ? self::$_config['Theme'] : 'theme1';
    }

    public static function getSitePath()
    {
        return ROOT;
    }

    public static function setAdminTheme($value)
    {
        self::$_config['AdminTheme'] = $value;
    }

    public static function getAdminTheme()
    {
        return !empty(self::$_config['AdminTheme']) ? self::$_config['AdminTheme'] : 'admin';
    }

    public static function getThemePath($isAdmin = FALSE, $uriOnly = FALSE)
    {
        return ($uriOnly ? '' : ROOT) . '/_/themes/' .
        ($isAdmin ? core_config::getAdminTheme() : core_config::getTheme());
    }

    public static function getThemeURI()
    {
        return '/_/themes/' . core_config::getTheme();
    }

    public static function getAdminThemeURI()
    {
        return '/_/themes/' . core_config::getAdminTheme();
    }

    public static function setSecureArea(core_path $path, $level = 10)
    {
        self::$_config['SecureAreas'][$path->getString()] = new stdClass();
        self::$_config['SecureAreas'][$path->getString()]->path = $path;
        self::$_config['SecureAreas'][$path->getString()]->level = (int)$level;
    }

    public static function getSecureLevel(core_path $path)
    {
        if ($path->isAdmin()) {
            return 3;
        }
        if (isset(self::$_config['SecureAreas'][$path->getString()])) {
            return self::$_config['SecureAreas'][$path->getString()]->level;
        }
        $parts = array();
        foreach ($path->getArray() as $pathPart) {
            $parts[] = $pathPart;
            if (isset(self::$_config['SecureAreas']['/' . implode('/', $parts)])) {
                return self::$_config['SecureAreas']['/' . implode('/', $parts)]->level;
            }
        }
        return 0;
    }

    public static function setLoginPath(core_path $path)
    {
        self::$_config['LoginPath'] = $path;
    }

    /**
     *
     * @return core_path
     */
    public static function getLoginPath()
    {
        return !empty(self::$_config['LoginPath']) ? self::$_config['LoginPath'] : new core_path('/_/user/login');
    }

    public static function setPasswordResetPath(core_path $path)
    {
        self::$_config['PasswordResetPath'] = $path;
    }

    /**
     *
     * @return core_path
     */
    public static function getPasswordResetPath()
    {
        return !empty(self::$_config['PasswordResetPath']) ? self::$_config['PasswordResetPath'] : new core_path('/_/user/reset');
    }

    public static function setCoreSessionVar($value)
    {
        self::$_config['CoreSessionVar'] = $value;
    }

    public static function getCoreSessionVar()
    {
        return !empty(self::$_config['CoreSessionVar']) ? self::$_config['CoreSessionVar'] : 'core';
    }

    public static function sessionVar()
    {
        return !empty(self::$_config['CoreSessionVar']) ? self::$_config['CoreSessionVar'] : 'core';
    }


    public static function setSalt($value)
    {
        self::$_config['Salt'] = $value;
    }

    public static function getSalt()
    {
        return !empty(self::$_config['Salt']) ? self::$_config['Salt'] : 'Make this unique to your site';
    }

    public static function setUploadDir($value)
    {
        self::$_config['UploadDir'] = $value;
    }

    public static function getUploadDir($uriOnly = FALSE)
    {
        return !empty(self::$_config['UploadDir'])
            ? self::$_config['UploadDir']
            : ($uriOnly ? '' : self::getSitePath()) . '/_/uploads';
    }

    private static function initSSLpath()
    {

        if (!isset(self::$_config['SSLpaths'])) {
            self::$_config['SSLpaths'] = array();
        }
        if (!in_array('login', self::$_config['SSLpaths'])) {
            self::$_config['SSLpaths'] = array_merge(self::$_config['SSLpaths'], array('_/user/login', '_/user/forgot', '_/user/reset'));
        }
    }

    public static function useSSL($set = null)
    {
        if (!is_null($set)) {
            self::$_config['useSSL'] = $set;
            self::initSSLpath();
        }
        return !empty(self::$_config['useSSL']);
    }

    public static function setSSLpaths(ARRAY $paths)
    {
        self::initSSLpath();
        self::$_config['SSLpaths'] = array_merge((array)self::$_config['SSLpaths'], (array)$paths);
    }

    public static function isSSLpath(core_path $path)
    {
        return TRUE; // should always be using SSL nowadays!
    }

    public static function setVal($field, $value)
    {
        self::$_config[$field] = $value;
    }

    public static function getVal($field)
    {
        return self::$_config[$field];
    }

    public static function DBinstall()
    {
        if (!self::DBpopulateInstalledFiles()) {
            return false;
        }

        $dbFiles = self::DBgetInstallFiles();

        foreach ($dbFiles as $fileName) {
            if (!self::DBinstallSqlFile($fileName)) {
                self::DBsaveInstalledFiles();
                return false;
            }
        }

        return self::DBsaveInstalledFiles();
    }

    public static function DBcatchupLog()
    {
        $dbFiles = self::DBgetInstallFiles();

        foreach ($dbFiles as $fileName) {
            self::$_installed[] = $fileName;
        }
        return self::DBsaveInstalledFiles();
    }

    private static function DBgetInstallFiles()
    {
        return array_diff(scandir(self::getSitePath() . '/_/config/db'), array('..', '.', 'installed.txt', '.DS_Store'));
    }

    private static function DBpopulateInstalledFiles()
    {
        if (!is_file(self::getSitePath() . '/_/config/db/installed.txt')
            && file_put_contents(self::getSitePath() . '/_/config/db/installed.txt', '') === false
        ) {
            return false;
        }
        if ((self::$_installed = file(self::getSitePath() . '/_/config/db/installed.txt')) === false) {
            return false;
        }
        self::$_installed = array_filter(array_map('trim', self::$_installed));
        return true;
    }

    private static function DBsaveInstalledFiles()
    {
        return file_put_contents(
            self::getSitePath() . '/_/config/db/installed.txt',
            implode("\n", self::$_installed)) !== false;
    }

    private static function DBinstallSqlFile($fileName)
    {
        if (in_array($fileName, self::$_installed)) {
            return true;
        }
        $db = new core_db();
        $sql = file_get_contents(self::getSitePath() . '/_/config/db/' . $fileName);
        if (($success = $db->multi_query($sql))) {
            while ($db->more_results() && $db->next_result()) {
                $extraResult = $db->use_result();
                if ($extraResult instanceof mysqli_result) {
                    $extraResult->free();
                }
            }
            self::$_installed[] = $fileName;
        }
        return $success;
    }

    public static function DBgetInstalled()
    {
        return self::$_installed;
    }

    public static function isCli(){
        return PHP_SAPI == 'cli';
    }
}

