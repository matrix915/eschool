<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/29/17
 * Time: 11:35 AM
 */

namespace core;


class DateTimeWrapper
{
    /** @var  \DateTime */
    protected $datetime;

    /**
     * DateTimeWrapper constructor.
     * @param string|null $time
     */
    public function __construct($time=null)
    {
        if($time){
            $this->datetime = new \DateTime($time);
        }
    }


    /**
     * @param $format
     * @return null|string
     */
    public function Format($format)
    {
        if($this->datetime){
            return $this->datetime->format($format);
        }
        return null;
    }

    /**
     * @return \DateTime|null
     */
    public function getDatetime()
    {
        return $this->datetime;
    }



}