<?php
/**
 * User: Rex
 * Date: 07/09/19
 * Time: 12:52 AM
 */

namespace mth\yoda\course;

use mth\yoda\courses;

class Query{
     const PAGE_SIZE = 250;
     const ROLE_STUDENT = 1;

     protected static $query = 'SELECT */*SELECT*/ 
     FROM yoda_courses AS c
     /*JOIN*/ 
     WHERE 1/*WHERE*/ 
     ORDER BY c.name /*LIMIT*/';

     protected $where = [],
     $bind = [],$years = null,$join = [],$select = [];


     public function setYear(array $years){
          $this->years = $years;
          $this->bind[':school_year_ids'] = implode(',',$years);
          $this->where['school_year_ids'] = 'c.school_year_id in (:school_year_ids)';
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
  
          if($this->select){
              $tags[] = '*/*SELECT*/';
              $replace[] = implode(',',array_merge(['*'],$this->select));
          }
  
         
          if(!empty($this->join)){
              $tags[] = '/*JOIN*/ ';
              $replace[] = implode(' ',$this->join);
          }
  
          return str_replace($tags,$replace,self::$query);
      }
  
      /**
       * @param null|int $page
       * @return courses[]
       */
      public function getAll($page=null){
          $query = str_replace(array_keys($this->bind), array_values($this->bind),$this->getQuery($page));
          return \core_db::runGetObjects($query,courses::class);
      }

}