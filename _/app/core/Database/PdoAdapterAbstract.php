<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/11/17
 * Time: 11:56 AM
 */

namespace core\Database;


use PDO;
use PDOException;
use RuntimeException;

abstract class PdoAdapterAbstract implements PdoAdapterInterface
{
    /**
     * @var PDO
     */
    protected $connection;

    /**
     * @var \PDOStatement
     */
    protected $statement;


    protected function getStatement() {
        if ($this->statement === null) {
            throw new PDOException(
                "There is no PDOStatement object for use.");
        }
        return $this->statement;
    }

    /**
     * @return PDO
     */
    abstract protected function getPdo();


    public function connect() {
        // if there is a PDO object already, return early
        if ($this->connection) {
            return;
        }

        try {
            $this->connection = $this->getPdo();
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function disconnect() {
        $this->connection = null;
    }

    /**
     * @param $sql
     * @param array $options
     * @return $this
     */
    public function prepare($sql, array $options = array()) {
        $this->connect();
        try {
            $this->statement = $this->connection->prepare($sql, $options);
            return $this;
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function parametrizeArray(array $arr){
        return array_combine(
            array_map(
                function($key){
                    return ':'.
                        preg_replace(
                            '/[^a-z]+/',
                            '_',
                            trim(
                                strtolower($key),
                                " \t\n\r\0\x0B:"
                            )
                        );
                },
                array_keys($arr)
            ),
            $arr);
    }

    public function parametrizeList(array $arr, $keyPrefix){
        $return = [];
        $c=0;
        foreach($arr as $value){
            $return[':'.$keyPrefix.($c+=1)] = $value;
        }
        return $return;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function execute(array $parameters = array()) {
        try {
            $this->getStatement()->execute($parameters);
            return $this;
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function countAffectedRows() {
        try {
            return $this->getStatement()->rowCount();
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function getLastInsertId($name = null) {
        $this->connect();
        return $this->connection->lastInsertId($name);
    }

    public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null) {
        try {
            return $this->getStatement()->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function fetchAll($fetch_style = null, $fetch_argument=null, array $ctor_args=null) {

        try {
            if($ctor_args){
                return $this->getStatement()->fetchAll($fetch_style, $fetch_argument,$ctor_args);
            }elseif($fetch_argument){
                return $this->getStatement()->fetchAll($fetch_style, $fetch_argument);
            }elseif($fetch_style){
                return $this->getStatement()->fetchAll($fetch_style);
            }else{
                return $this->getStatement()->fetchAll();
            }
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function getQueryString()
    {
        return $this->getStatement()->queryString;
    }

    public function fetchClass($className)
    {
        try {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $this->getStatement()->setFetchMode(PDO::FETCH_CLASS,$className);
            return $this->getStatement()->fetch();
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function fetchAllClass($className)
    {
        return $this->fetchAll(PDO::FETCH_CLASS,$className);
    }

    protected function prepareWhere(array $where = [], $prefix='w'){
        $whereBind = [];
        $whereQuery = [];
        if ($where) {
            foreach ($where as $col => $value) {
                $whereBind[':' . $prefix.$col] = $value;
                $whereQuery[] = '`'. $col . '` = :' . $prefix.$col;
            }
        }
        return [$whereQuery,$whereBind];
    }


    public function select($table, array $where = []) {

        list($whereQuery, $whereBind) = $this->prepareWhere($where);

        $sql = 'SELECT * FROM `' . $table.'`  WHERE '. (($where) ? implode(' AND ',$whereQuery) : ' 1');
        $this->prepare($sql)
            ->execute($whereBind);
        return $this;
    }

    public function insert($table, array $bind) {
        $cols = implode(', ', array_keys($bind));
        $bind = $this->parametrizeArray($bind);
        $values = implode(', ', array_keys($bind));

        $sql = 'INSERT INTO ' . $table
            . ' (' . $cols . ')  VALUES (' . $values . ')';
        return (int) $this->prepare($sql)
            ->execute($bind)
            ->getLastInsertId();
    }

    public function update($table, array $bind, array $where) {
        list($setQuery, $setBind) = $this->prepareWhere($bind,'s');
        list($whereQuery, $whereBind) = $this->prepareWhere($where);

        $bind = $setBind+$whereBind;
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $setQuery)
            . ' WHERE ' . implode(' AND ',$whereQuery);

        return $this->prepare($sql)
            ->execute($bind)
            ->countAffectedRows();
    }

    public function delete($table, array $where) {
        list($whereQuery, $whereBind) = $this->prepareWhere($where);
        $sql = 'DELETE FROM `' . $table . '` WHERE ' . $whereQuery;
        return $this->prepare($sql)
            ->execute($whereBind)
            ->countAffectedRows();
    }

}