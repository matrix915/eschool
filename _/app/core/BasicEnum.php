<?php

namespace core;

/**
 * Created by PhpStorm.
 * User: abe
 * Date: 1/25/17
 * Time: 3:11 PM
 */
class BasicEnum
{
    private static $constCacheArray = NULL;
    protected static $labels = NULL;

    private static function getConstants() {
        if (self::$constCacheArray == NULL) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    public static function getLabels()
    {
        $class = get_called_class();
        if(isset($class::$labels)){
            return $class::$labels;
        }
        return array_map(
            function($constant_name){
                return str_replace('_',' ',$constant_name);
            },
            array_flip(self::getConstants()));
    }

    public static function getLabel($value){
        $labels = self::getLabels();
        if(isset($labels[$value])){
            return $labels[$value];
        }
        return null;
    }

    public static function isValidName($name, $strict = false) {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value, $strict = true) {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }
}