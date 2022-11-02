<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/11/17
 * Time: 12:12 PM
 */

namespace core\Factory;


use core\Database\MySql;
use core\Database\PdoAdapterInterface;

class PdoAdapterFactory
{

    /** @var  PdoAdapterInterface */
    protected static $adapter;

    /**
     * @param bool $nonStatic Return a new PdoAdapter and don't store it in the static member
     * @return PdoAdapterInterface
     */
    public function getPdoAdapter($nonStatic=false){
        //this could be modified to allow accessing other types of databases, with different configurations
        if($nonStatic){
            return new MySql(
                \core_config::getDb(),
                \core_config::getDbHost(),
                \core_config::getDbUser(),
                \core_config::getDbPass()
            );
        }
        if(!self::$adapter){
            self::$adapter = new MySql(
                \core_config::getDb(),
                \core_config::getDbHost(),
                \core_config::getDbUser(),
                \core_config::getDbPass()
            );
        }
        return self::$adapter;
    }
}