<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * miscellaneous static methods
 *
 * @author abe
 */
class core_misc
{

    public static function password_generator($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789!@#$%^&*-+=';

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public static function prep_csv_cell_value($value)
    {
        $value = req_sanitize::txt_decode($value);
        $quotes = false;
        if (strpos($value, '"') !== false) {
            $value = str_replace('"', '""', $value);
            $quotes = true;
        }
        if (!$quotes && (strpos($value, ',') !== false || strpos($value, "\n") !== false)) {
            $quotes = true;
        }
        if ($quotes) {
            $value = '"' . trim($value) . '"';
        }
        return $value;
    }

    public static function getTestEmailAddress($emailAddress)
    {
        if ($emailAddress == core_setting::getSiteEmail()->getValue()) {
            return $emailAddress;
        }
        $siteEmail = explode('@', core_setting::getSiteEmail()->getValue());
        return $siteEmail[0] . '+' .
        preg_replace('/[^0-9a-zA-Z\-_\.]/', '',
            str_replace('@', '-', $emailAddress)) .
        '@' . $siteEmail[1];
    }
}
