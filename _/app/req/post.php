<?php

/**
 * Description of post
 *
 * @author abe
 */
class req_post
{

    /**
     *
     * @return \req_array
     */
    public static function req_array($field = NULL)
    {
        if ($field) {
            if (is_array($_POST[$field])) {
                return new req_array($_POST[$field]);
            } else {
                return new req_array(array());
            }
        }
        return new req_array($_POST);
    }

    public static function int($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return req_sanitize::int($_POST[$field]);
    }

    public static function int_array($field)
    {
        if (!isset($_POST[$field]) || !is_array($_POST[$field])) {
            return array();
        }
        return array_map(array('req_sanitize', 'int'), $_POST[$field]);
    }

    public static function float($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return req_sanitize::float($_POST[$field]);
    }

    public static function txt($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return req_sanitize::txt($_POST[$field]);
    }

    public static function txt_array($field)
    {
        if (!isset($_POST[$field]) || !is_array($_POST[$field])) {
            return array();
        }
        return array_map(array('req_sanitize', 'txt'), $_POST[$field]);
    }

    public static function is_array($field)
    {
        return isset($_POST[$field]) && is_array($_POST[$field]);
    }

    public static function multi_txt($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return req_sanitize::multi_txt($_POST[$field]);
    }

    public static function html($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return req_sanitize::html($_POST[$field]);
    }

    public static function url($field, $localOnly = true)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return req_sanitize::url($_POST[$field], $localOnly);
    }

    public static function urlencode($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return req_sanitize::urlencode($_POST[$field]);
    }

    public static function raw($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return $_POST[$field];
    }

    public static function bool($field)
    {
        return !empty($_POST[$field]);
    }

    public static function strtotime($field)
    {
        if (!isset($_POST[$field])) {
            return NULL;
        }
        return strtotime($_POST[$field]);
    }

    public static function remove($field)
    {
        unset($_POST[$field]);
    }

    public static function is_set($field)
    {
        return isset($_POST[$field]);
    }
}
