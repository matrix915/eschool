<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/29/17
 * Time: 11:41 AM
 */

namespace core\Factory;


use core\DateTimeWrapper;

class DateTimeWrapperFactory
{
    /**
     * @param null|string $time
     * @return DateTimeWrapper
     */
    public function newDateTimeWrapper($time = null)
    {
        return new DateTimeWrapper($time);
    }
}