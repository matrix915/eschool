<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/18/17
 * Time: 10:32 AM
 */

namespace core\Factory;


use core\Response;

class ResponseFactory
{
    /**
     * @return Response
     */
    public function getResponse(){
        return new Response();
    }
}