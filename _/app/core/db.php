<?php

/**
 * Extends the mysqli connects using core_config values
 *
 * @author abe
 */
class core_db extends mysqli
{

    protected $conName;

    /* @var core_db */
    private static $_db;

    public static $log_next = false;

    public function __construct($conName = 'DEFAULT')
    {
        if (!core_config::getDb($conName)) {
            die('The database is not configured!');
        }
        parent::__construct(
            core_config::getDbHost($conName),
            core_config::getDbUser($conName),
            core_config::getDbPass($conName),
            core_config::getDb($conName));

        $this->conName = $conName;

        if (mysqli_connect_error()) {
            error_log('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
            header("HTTP/1.1 503 Service Unavailable"); //may want to change this to allow a themed page to be displayed.
            header("Status: 503 Service Unavailable");
            header("Retry-After: 1800");
            die('Unable to connect to database. Please try back later, or contact us for support.');
        }
    }

    public static function checkStatic()
    {
        if (self::$_db === NULL) {
            self::$_db = new core_db();
        }
    }


    /**
     * @param string $query
     * @return mysqli_result|bool
     */
    public function query($query,$resultmode = NULL)
    {
        if(self::$log_next){
            error_log($query);
            self::$log_next = false;
        }
        if (!($result = parent::query($query))) {
            error_log($this->error . ". \nQuery: " . substr($query, 0, 1000));
        }
        return $result;
    }

    /**
     * @param string $query
     * @return mysqli_result|bool
     */
    public static function runQuery($query)
    {
        self::checkStatic();
        $result = self::$_db->query($query);
        return $result;
    }

    public static function getInsertID()
    {
        self::checkStatic();
        return self::$_db->insert_id;
    }

    public static function escape($string)
    {
        self::checkStatic();
        return self::$_db->escape_string($string);
    }

    /**
     *
     * @param string $query
     * @param string $class
     * @param array $parms
     * @return array|boolean array of objects of the specified class
     */
    public function getObjects($query, $class = 'stdClass', ARRAY $parms = null)
    {
        if (($result = $this->query($query))) {
            /* @var $result mysqli_result */
            $arr = array();
            if (!empty($parms)) {
                while ($r = $result->fetch_object($class, $parms)) {
                    $arr[] = $r;
                }
            } else {
                while ($r = $result->fetch_object($class)) {
                    $arr[] = $r;
                }
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    /**
     * @param string $query
     * @param string $class
     * @param array $parms
     * @return array|boolean array of objects of the specified class
     */
    public static function runGetObjects($query, $class = 'stdClass', ARRAY $parms = null)
    {
        self::checkStatic();
        $result = self::$_db->getObjects($query, $class, $parms);
        return $result;
    }

    /**
     *
     * @param string $query
     * @param string $class
     * @param array $parms
     * @return object|boolean
     */
    public function getObject($query, $class = 'stdClass', ARRAY $parms = null)
    {
        if (($result = $this->query($query))) {
            if (!empty($parms)) {
                $obj = $result->fetch_object($class, $parms);
            } else {
                $obj = $result->fetch_object($class);
            }
            $result->close(); 
            return $obj;
        }
        return false;
    }

    /**
     * @param string $query
     * @param string $class
     * @param array $parms
     * @return object|boolean
     */
    public static function runGetObject($query, $class = 'stdClass', ARRAY $parms = null)
    {
        self::checkStatic();
        $result = self::$_db->getObject($query, $class, $parms);
        return $result;
    }

    /**
     * returns the first value for the first column
     * @param string $query
     * @return mixed
     */
    public function getValue($query)
    {
        $result = $this->query($query);

        if ($result && ($arr = $result->fetch_row())) {
            $result->close();
            return $arr[0];
        }
        $result->free_result();
        return NULL;
    }

    /**
     * returns the first value for the first column
     * @param string $query
     * @return mixed
     */
    public static function runGetValue($query)
    {
        self::checkStatic();
        $result =  self::$_db->getValue($query);
        return $result;
    }

    /**
     * resturns the values of the first column for all rows
     * @param string $query
     * @return array
     */
    public function getValues($query)
    {
        $result = $this->query($query);
        if(!$result){ return array(); }
        $returnArr = array();
        while ($arr = $result->fetch_row()) {
            $returnArr[] = $arr[0];
        }
        $result->free_result();
        return $returnArr;
    }

    /**
     * resturns the values of the first column for all rows
     * @param string $query
     * @return array
     */
    public static function runGetValues($query)
    {
        self::checkStatic();
        $result = self::$_db->getValues($query);
        return $result;
    }
}