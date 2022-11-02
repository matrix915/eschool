<?php

/**
 *
 * @author abe
 */
class core_secure
{
    public static function startSession()
    {
        if (core_config::useSSL() && self::usingSSL()) {
            session_set_cookie_params(3600, '/', $_SERVER['HTTP_HOST'], true);
        }

        // we don't want this thing to load up the session table and start sessions for no reason
        if (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'ELB-HealthChecker')) {
            return;
        }
        session_start();
    }

    public static function hasAccess(core_path $path = NULL)
    {

        if (!$path) {
            $path = core_path::getPath();
        }

        return core_config::getSecureLevel($path) <= core_user::getUserLevel()
        || $path->getString() == core_config::getLoginPath()->getString();
    }

    public static function loadLogin()
    {
        self::redirectToSSL();
        if (core_user::getUserID() && req_get::bool('login') && core_config::useSSL() && !self::usingSSL()) {
            header('location: https://' . $_SERVER['HTTP_HOST'] . core_path::getPath());
            exit();
        }
        core_loader::printPage(core_config::getLoginPath());
        exit();
    }

    public static function userFun()
    {

        self::startSession();
     
        if (req_get::bool('logout')) {
            if (core_user::getUserID()) {
                setcookie('rememberMe', core_user::getCurrentUser()->getCookie(), time() - 9999999, '/', $_SERVER['HTTP_HOST'], self::sslCookie());
            }
            core_user::logout();
            header('Location: /');
            exit();
        }

        if (req_get::bool('stope')) {
            core_user::stopEmulation();
            header('Location: /');
            if($new_active_user = core_user::getCurrentUser()){
                $home_url = $new_active_user->getHomeUrl();
                core_loader::redirect($home_url);
            }
            exit();
        }


        if (req_get::bool('newPass')) {
            self::redirectToSSL();
            core_loader::printPage(core_config::getPasswordResetPath());
            exit();
        }

        if (!headers_sent() && req_post::bool('email') && req_post::bool('password')) {
            if (!(core_user::login(req_post::txt('email'), req_post::raw('password')))) {
                core_notify::addError('Invalid email or password.');
                core_loader::redirect('?login=1'.(req_post::is_set('callback')?('&callback='.req_post::txt('callback')):''));
            }
            if (req_post::bool('rememberMe') && core_user::getUserID()) {
                self::setRememberMeCookie();
            }

            if(req_post::is_set('callback') && !empty(trim(req_post::txt('callback')))){
                header('Location: '.req_post::txt('callback').'?token='.md5('frominfocenter'));
                exit();;
            }

            core_loader::redirect();
        }

        if (!core_user::getUserID() && req_cookie::bool('rememberMe')) {
            core_user::loginByCookie(req_cookie::raw('rememberMe'));
        }

        if (req_get::bool('login')) {
            self::loadLogin();
        }

        if (core_config::isSSLpath(core_path::getPath())) {
            self::redirectToSSL();
        }

        self::hasAccess() || self::loadLogin();

        if (core_config::useSSL()
            && !core_config::isSSLpath(core_path::getPath())
            && !core_user::getUserID()
            && self::usingSSL()
            && empty($_POST)
        ) {
            header('location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    public static function setRememberMeCookie()
    {
        setcookie('rememberMe', core_user::getCurrentUser()->getCookie(), time() + 60 * 60 * 24 * 30, '/', $_SERVER['HTTP_HOST'], self::sslCookie());
    }

    public static function redirectToSSL()
    {
        if (core_config::useSSL() && self::usingSSL()) {
            session_destroy();
            session_regenerate_id(true);
            session_start();
            header('location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    public static function usingSSL()
    {
        if (!empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] === 'on'
        ) {
            return true;
        }
        return false;
    }

    public static function sslCookie()
    {
        return core_config::useSSL() || self::usingSSL();
    }
}
