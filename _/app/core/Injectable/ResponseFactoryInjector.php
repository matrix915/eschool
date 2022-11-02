<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/18/17
 * Time: 10:34 AM
 */

namespace core\Injectable;


use core\Factory\ResponseFactory;

trait ResponseFactoryInjector
{
    /**
     * @return ResponseFactory
     */
    protected function getResponseFactory(){
        return $this->_getInjected(ResponseFactory::class);
    }
}