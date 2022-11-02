<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/17/17
 * Time: 1:31 PM
 */

namespace core\Factory;


use core\view;

class ViewFactory
{
    /**
     * @param $view
     * @return view
     */
    public function getView($view){
        return new view($view);
    }
}