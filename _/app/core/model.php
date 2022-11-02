<?php

/**
 * core_model
 *
 * @author abe
 */
abstract class core_model
{
    protected $updateQueries = array();
    private static $_cache = array();

    /**
     * set value and enterers field update query in the the updateQueries array
     * @param string $field
     * @param string $value this function will make sure the value is escaped for the database, but no other sanitation.
     */
    protected function set($field, $value, $force = false)
    {
        if ((($value === NULL && $this->$field === NULL) || $this->$field === (string)$value) && !$force) {
            return;
        }
        $this->$field = $value;
        if (is_null($value)) {
            $this->updateQueries[$field] = '`' . $field . '`=NULL';
        } else {
            $this->updateQueries[$field] = '`' . $field . '`="' . core_db::escape($value) . '"';
        }
    }

    /**
     * DON'T USE, STRING CONCATINATION FOR IDENTIFIER IS TOO SLOW
     */
    protected static function &cache($class, $identifier, $set = NULL)
    {
        if ($set) {
            self::$_cache[$class][$identifier] = $set;
        }
        return self::$_cache[$class][$identifier];
    }

    protected static function deleteCached($class, $identifier)
    {
        unset(self::$_cache[$class][$identifier]);
    }

    public static function getDate($dateValueStr, $format)
    {
        if($dateValueStr == "0000-00-00 00:00:00") {
            return null;
        }
        
        if (!$dateValueStr) {
            return null;
        }

        if(is_array($dateValueStr)){
            reset($dateValueStr);
            $first_key = key($dateValueStr);
            $dateValueStr = $dateValueStr[$first_key];
        }

        if (($timestamp = &self::$_cache['getDate'][$dateValueStr]) === NULL) {
            $timestamp = strtotime($dateValueStr);
        }

        if (!$format) {
            return $timestamp;
        }
        return date($format, $timestamp);
    }

    /**
     *
     * @param mixed $number
     * @param int $formatWithThisManyDigitsAfterDecimal if any value is provided a formated number will be returned (1,233.56)
     * @return mixed
     */
    public static function getNumber($number, $formatWithThisManyDigitsAfterDecimal = NULL)
    {
        if (!is_null($formatWithThisManyDigitsAfterDecimal)) {
            return number_format($number, $formatWithThisManyDigitsAfterDecimal);
        }
        return $number;
    }

    protected function populateUpdateQueriesArr($table, ARRAY $excludeFields)
    {
        $result = core_db::runQuery('SHOW COLUMNS FROM  `' . $table . '`');
        while ($field = $result->fetch_object()) {
            $fieldName = $field->Field;
            if (in_array($fieldName, $excludeFields)) {
                continue;
            }
            if ($field->Null == 'YES' && !$this->$fieldName) {
                $this->set($fieldName, NULL, true);
            } else {
                $this->set($fieldName, $this->$fieldName, true);
            }
        }
        $result->free_result();
    }

    protected function runUpdateQuery($table, $whereClause)
    {
        if (empty($this->updateQueries)) {
            return true;
        }
        $success = core_db::runQuery('UPDATE `' . $table . '` SET ' . implode(',', $this->updateQueries) . ' WHERE ' . $whereClause);
        $this->updateQueries = array();
        return $success;
    }

    //general use sanitize methods?
}
