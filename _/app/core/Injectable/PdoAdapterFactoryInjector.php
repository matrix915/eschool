<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/11/17
 * Time: 12:19 PM
 */

namespace core\Injectable;


use core\Factory\PdoAdapterFactory;

trait PdoAdapterFactoryInjector
{
    /**
     * @return PdoAdapterFactory
     */
    protected function getPdoAdFactory(){
        return $this->_getInjected(PdoAdapterFactory::class);
    }

    /**
     * @param bool $nonStatic Return a new PdoAdapter and don't store it in the static member
     * @return \core\Database\PdoAdapterInterface
     */
    protected function getPdoAdapter($nonStatic=false){
        return $this->getPdoAdFactory()->getPdoAdapter($nonStatic);
    }
}