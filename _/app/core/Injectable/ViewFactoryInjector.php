<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/17/17
 * Time: 1:33 PM
 */

namespace core\Injectable;


use core\Factory\ViewFactory;

trait ViewFactoryInjector
{
    /**
     * @return ViewFactory
     */
    protected function getViewFactory(){
        return $this->_getInjected(ViewFactory::class);
    }
}