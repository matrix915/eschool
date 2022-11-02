<?php

use \Firebase\JWT\JWT;

class jwt_token
{

    const token_variable = 'INFOCENTER_TOKEN';

    public static function getUserTokenName()
    {
        return STATE . '_' . self::token_variable;
    }

    public static function getMasqueradeTokenName()
    {
        return STATE . '_' . 'MASQ_' . self::token_variable;
    }

    public static function tokenDuration()
    {
        return (60 * 60 * 48);
    }

    public static function createTokenForUser(core_user $user)
    {
        $now = new DateTime();
        $payload = array(
            'iss' => YETI_TOKEN_ISSUER,
            'aud' => YETI_TOKEN_AUDIENCE,
            'iat' => $now->getTimestamp(),
            'sub' => $user->getID(),
            'exp' => $now->getTimestamp() + self::tokenDuration(),
            'role' => self::getUserTokenRole($user),
            'level' => $user->getLevel(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'region' => APP_REGION,
        );

        return JWT::encode($payload, $_SERVER['YETI_TOKEN_SECRET']);
    }

    public static function addUserCookie($token)
    {
        setcookie(self::getUserTokenName(), $token, (int) time() + self::tokenDuration(), '/', YETI_COOKIE_DOMAIN);
    }

    public static function deleteUserCookie()
    {
        // empty value and expiration one hour before
        setcookie(self::getUserTokenName(), '', (int) time() - 3600, '/', YETI_COOKIE_DOMAIN);
    }

    public static function addMasqueradeCookie($token)
    {
        setcookie(self::getMasqueradeTokenName(), $token, (int) time() + self::tokenDuration(), '/', YETI_COOKIE_DOMAIN);
    }

    public static function deleteMasqueradeCookie()
    {
        // empty value and expiration one hour before
        setcookie(self::getMasqueradeTokenName(), '', (int) time() - 3600, '/', YETI_COOKIE_DOMAIN);
    }

    public static function getUserTokenRole(core_user $user)
    {
        if ($user->isAdmin()) {
            return 'ADMIN';
        }
        if ($user->isTeacher()) {
            return 'TEACHER';
        }
        if ($user->isParent()) {
            return 'PARENT';
        }
        if ($user->isStudent()) {
            return 'STUDENT';
        }

        return 'NONE';
    }

    public static function generateTokenForSparkLMS()
    {
        $privateKey = file_get_contents(dirname(__FILE__).'/launchpadtech_private.key');
        $payload = [
            "iss" => "launchpadtech",
            "iat" => time(),
            "nbf" => strtotime("-10 minutes"),
            "aud" => "https://tech.sparkeducation.com/api/",
            "exp" => strtotime("+9 minutes")
        ];

        $token = JWT::encode($payload, $privateKey, 'RS512', 'launchpadtech');
        return $token;
    }

    public static function generateSparkUserPassword()
    {
        $length = 8;
        $strCount =  rand(1, $length - 1);
        $numCount = $length - $strCount;

        $combStr = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pass = array();
        $combLen = strlen($combStr) - 1;
        for ($i = 0; $i < $strCount; $i++) {
            $n = rand(0, $combLen);
            $pass[] = $combStr[$n];
        }

        $combNum = '1234567890';
        $combNumLen = strlen($combNum) - 1;
        for ($i = 0; $i < $numCount; $i++) {
            $n = rand(0, $combNumLen);
            $pass[] = $combNum[$n];
        }
        return implode($pass);
    }
}
