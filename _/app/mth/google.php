<?php
set_include_path(ROOT . '/_/mth_includes/' . PATH_SEPARATOR . get_include_path());
require_once 'Google/Client.php';

/**
 * Description of google
 *
 * @author abe
 */
class mth_google
{
    /**
     *
     * @var Google_Client
     */
    protected static $client;

    /**
     *
     * @return Google_Client
     */
    protected static function client()
    {
        if (is_null(self::$client)) {
            self::$client = new Google_Client();
            self::$client->setClientId(GOOGLE_CLIENT_ID);
            self::$client->setClientSecret(GOOGLE_CLIENT_SECRET);
            self::$client->setRedirectUri(
                (core_secure::usingSSL() ? 'https' : 'http') . '://' .
                $_SERVER['HTTP_HOST'] . GOOGLE_REDIRECT_URI);
            self::$client->addScope('https://www.googleapis.com/auth/drive');
        }
        if (($accessToken = core_setting::get('accessToken', __CLASS__))) {
            self::$client->setAccessToken($accessToken->getValue());
        }
        return self::$client;
    }

    public static function isAuthenticated()
    {
        return self::client()->getAccessToken() && !self::client()->isAccessTokenExpired();
    }

    /**
     * redirects to authenticate with google then the user will be redirected to the current path.
     */
    public static function redirectToAuthenticatationURL()
    {
        $_SESSION[core_config::getCoreSessionVar()][__CLASS__]['current_path'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . self::client()->createAuthUrl());
        exit();
    }

    /**
     * Should be executed on the page at mth_google::REDIRECT_URI. Expects that $_GET['code'] exsists.
     * Authenticates, saves access code, and redirects to path where mth_google::redirectToAuthenticatationURL() was called
     */
    public static function authenticate()
    {
        $client = self::client();
        $client->authenticate(req_get::raw('code'));
        core_setting::set('accessToken', $client->getAccessToken(), core_setting::TYPE_RAW, __CLASS__);
        core_loader::redirect($_SESSION[core_config::getCoreSessionVar()][__CLASS__]['current_path']);
    }

    public static function sendFile($title, $mimeType, $content, $convert = false)
    {
        require_once 'Google/Http/MediaFileUpload.php';
        require_once 'Google/Service/Drive.php';

        $service = new Google_Service_Drive(self::client());
        $file = new Google_Service_Drive_DriveFile();
        $file->setTitle($title);
        $result = $service->files->insert(
            $file,
            array(
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'convert' => (bool)$convert
            )
        );
        return (bool)$result;
    }
}
