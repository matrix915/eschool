<?php

/**
 * Description of post
 *
 * @author abe
 */
class req_array implements ArrayAccess
{
    protected $array;


    public function __construct(ARRAY $array)
    {
        $this->array = $array;
    }

    public function __get($field)
    {
        return $this->txt($field);
    }

    public function __set($field, $value)
    {
        $this->set($field, $value);
    }

    public function __unset($field)
    {
        $this->remove($field);
    }

    public function offsetExists($field)
    {
        return isset($this->array[$field]);
    }

    public function offsetGet($field)
    {
        return $this->txt($field);
    }

    public function offsetSet($field, $value)
    {
        $this->set($field, $value);
    }

    public function offsetUnset($field)
    {
        $this->remove($field);
    }


    public function set($field, $value)
    {
        $this->array[$field] = $value;
    }

    public function int($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return req_sanitize::int($this->array[$field]);
    }

    public function int_array($field)
    {
        if (!isset($this->array[$field]) || !is_array($this->array[$field])) {
            return array();
        }
        return array_map(array('req_sanitize', 'int'), $this->array[$field]);
    }

    public function float($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return req_sanitize::float($this->array[$field]);
    }

    public function txt($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return req_sanitize::txt($this->array[$field]);
    }

    public function txt_array($field)
    {
        if (!isset($this->array[$field]) || !is_array($this->array[$field])) {
            return array();
        }
        return array_map(array('req_sanitize', 'txt'), $this->array[$field]);
    }

    public function is_array($field)
    {
        return isset($this->array[$field]) && is_array($this->array[$field]);
    }

    public function multi_txt($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return req_sanitize::multi_txt($this->array[$field]);
    }

    public function html($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return req_sanitize::html($this->array[$field]);
    }

    public function url($field, $localOnly = true)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return req_sanitize::url($this->array[$field], $localOnly);
    }

    public function urlencode($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return req_sanitize::urlencode($this->array[$field]);
    }

    public function raw($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return $this->array[$field];
    }

    public function bool($field)
    {
        return !empty($this->array[$field]);
    }

    public function strtotime($field)
    {
        if (!isset($this->array[$field])) {
            return NULL;
        }
        return strtotime($this->array[$field]);
    }

    public function remove($field)
    {
        unset($this->array[$field]);
    }

    public function is_set($field)
    {
        return isset($this->array[$field]);
    }
}
