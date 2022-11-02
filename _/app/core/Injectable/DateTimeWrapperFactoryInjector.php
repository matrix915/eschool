<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/29/17
 * Time: 11:42 AM
 */

namespace core\Injectable;


use core\Factory\DateTimeWrapperFactory;

trait DateTimeWrapperFactoryInjector
{
    /**
     * @return DateTimeWrapperFactory
     */
    protected function getDateTimeWrapperFactory(){
        return $this->_getInjected(DateTimeWrapperFactory::class);
    }
}