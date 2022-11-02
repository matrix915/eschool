<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/14/17
 * Time: 4:26 PM
 */

namespace mth\Injector\Student;


use mth\Factory\Student\HomeroomManagerFactory;

trait HomeroomManagerFactoryInjector
{
    /**
     * @return HomeroomManagerFactory
     */
    protected function getHomeroomManagerFactory(){
        return $this->_getInjected(HomeroomManagerFactory::class);
    }
}