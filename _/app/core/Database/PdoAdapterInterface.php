<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/11/17
 * Time: 11:54 AM
 */

namespace core\Database;


interface PdoAdapterInterface
{
    public function connect();
    public function disconnect();

    /**
     * @param string $sql
     * @param array $options
     * @return $this
     */
    public function prepare($sql, array $options = array());

    /**
     * @param array $array
     * @return array with the keys prepared to be used as statement parameters.
     */
    public function parametrizeArray(array $array);

    public function parametrizeList(array $array, $keyPrefix);

    /**
     * @param array $parameters
     * @return $this
     */
    public function execute(array $parameters = array());

    public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null);

    public function fetchAll($fetch_style = null, $fetch_argument=null, array $ctor_args=null);

    public function fetchClass($className);

    public function fetchAllClass($className);

    public function getLastInsertId($name = null);

    /**
     * @param $table
     * @param array $where
     * @return $this
     */
    public function select($table, array $where=[]);

    public function insert($table, array $bind);
    public function update($table, array $bind, array $where);
    public function delete($table, array $where);

    public function countAffectedRows();
}