<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/11/17
 * Time: 12:09 PM
 */

namespace core\Database;


class MySql extends PdoAdapterAbstract
{

    protected $database, $host, $user, $pass;

    /**
     * PdoAdapter constructor.
     * @param string $database
     * @param string $host
     * @param string $user
     * @param string $pass
     */
    public function __construct($database, $host, $user, $pass)
    {
        $this->database = $database;
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * @return \PDO
     */
    protected function getPdo()
    {
        $connection = new \PDO(
            'mysql:dbname='.$this->database.';host='.$this->host,
            $this->user,
            $this->pass);
        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
      
        return $connection;
    }

}