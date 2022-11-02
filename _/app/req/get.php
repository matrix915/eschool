<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of get
 *
 * @author abe
 */
class req_get
{

    /**
     *
     * @return \req_array
     */
    public static function req_array($field = NULL)
    {
        if ($field) {
            if (is_array($_GET[$field])) {
                return new req_array($_GET[$field]);
            } else {
                return new req_array(array());
            }
        }
        return new req_array($_GET);
    }

    public static function int($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return req_sanitize::int($_GET[$field]);
    }

    public static function int_array($field)
    {
        if (!isset($_GET[$field]) || !is_array($_GET[$field])) {
            return array();
        }
        return array_map(array('req_sanitize', 'int'), $_GET[$field]);
    }

    public static function float($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return req_sanitize::float($_GET[$field]);
    }

    public static function txt($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return req_sanitize::txt($_GET[$field]);
    }

    public static function txt_array($field)
    {
        if (!isset($_GET[$field]) || !is_array($_GET[$field])) {
            return array();
        }
        return array_map(array('req_sanitize', 'txt'), $_GET[$field]);
    }

    public static function multi_txt($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return req_sanitize::multi_txt($_GET[$field]);
    }

    public static function html($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return req_sanitize::html($_GET[$field]);
    }

    public static function url($field, $localOnly = true)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return req_sanitize::url($_GET[$field], $localOnly);
    }

    public static function urlencode($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return req_sanitize::urlencode($_GET[$field]);
    }

    public static function raw($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return $_GET[$field];
    }

    public static function bool($field)
    {
        return !empty($_GET[$field]);
    }

    public static function strtotime($field)
    {
        if (!isset($_GET[$field])) {
            return NULL;
        }
        return strtotime($_GET[$field]);
    }

    public static function remove($field)
    {
        unset($_GET[$field]);
    }

    public static function is_set($field)
    {
        return isset($_GET[$field]);
    }
}
