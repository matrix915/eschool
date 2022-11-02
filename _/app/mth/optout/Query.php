<?php
/**
 * User: Rex
 * Date: 11/30/17
 * Time: 12:52 AM
 */

namespace mth\optout;


use core\Database\PdoAdapterInterface;
use core\DateTimeWrapper;
use core\Injectable;
use mth\yoda\courses;

class Query
{
    use Injectable, Injectable\PdoAdapterFactoryInjector;

    const PAGE_SIZE = 250;
    const ROLE_STUDENT = 1;
    const STATUS_DELETED = 0;

    protected static $query = 'SELECT */*SELECT*/ 
        FROM mth_testOptOut as mt 
        left join  mth_testOptOut_student as mts on mts.testOptOut_id=mt.testOptOut_id
        WHERE 1/*WHERE*/ 
        ORDER BY date_submitted ASC /*LIMIT*/';

    /** @var  PdoAdapterInterface */
    protected $PdoAdapter;

    protected $where = [],
        $bind = [],$year = null;

    public function __construct()
    {
        $this->PdoAdapter = $this->getPdoAdapter();
    }

    public function setYear(array $year){
        $this->bind[':school_year_ids'] = implode(',',$year);
        $this->where['school_year_ids'] = 'school_year_id in (:school_year_ids)';
        return $this;
    }

    protected function getQuery($page=null,$select=null){
        $tags = ['1/*WHERE*/'];
        $replace = [implode(' AND ',$this->where)];
        if($page){
            $tags[] = '/*LIMIT*/';
            $replace[] = 'LIMIT '.(($page-1)*self::PAGE_SIZE).','.self::PAGE_SIZE;
        }
        if($select){
            $tags[] = '*/*SELECT*/';
            $replace[] = $select;
        }
        return str_replace($tags,$replace,self::$query);
    }

    /**
     * @param null|int $page
     * @return mth_testOptOut[]
     */
    public function getAll($page=null){
        
        $query = str_replace(array_keys($this->bind), array_values($this->bind),$this->getQuery($page));
     
        return \core_db::runGetObjects($query,\mth_testOptOut::class);
        // return $this->PdoAdapter
        //     ->prepare($this->getQuery($page))
        //     ->execute($this->bind);
            //->fetchAllClass(courses::class);
    }

}