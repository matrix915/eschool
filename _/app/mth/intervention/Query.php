<?php
/**
 * User: Rex
 * Date: 11/30/17
 * Time: 12:52 AM
 */

namespace mth\intervention;


use core\Database\PdoAdapterInterface;
use core\DateTimeWrapper;
use core\Injectable;

class Query
{
    use Injectable, Injectable\PdoAdapterFactoryInjector;

    const PAGE_SIZE = 250;
    const ROLE_STUDENT = 1;
    const STATUS_DELETED = 0;

    protected static $query = 'SELECT */*SELECT*/ 
                                FROM mth_canvas_enrollment 
                                WHERE 1/*WHERE*/ 
                                ORDER BY canvas_enrollment_id ASC /*LIMIT*/';

    /** @var  PdoAdapterInterface */
    protected $PdoAdapter;

    protected $where = [],
        $bind = [],$year = null;

    public function __construct()
    {
        $this->PdoAdapter = $this->getPdoAdapter();
    }

    public function setGrade($grade = null){
        if(!is_null($grade)){
           $this->bind[':grade'] = $grade;
           $this->where['grade'] = ($grade < 100)?"(`grade` is null or `grade` <= :grade)":"grade=:grade";
        }
        return $this;
    }

    public function setRoleStatus($role = self::ROLE_STUDENT,$status = self::STATUS_DELETED){
       $this->bind[':role'] = $role;
       $this->where['role'] = '`role`= :role';

       $this->bind[':status'] = $status;
       $this->where['status'] = '`status`!= :status';
       return $this;
    }

    public function setYearId($year){
        $this->year = $year;
        return $this;
    }

    public function setCourseIds($courseIds = array()){
        if(count($courseIds) == 0){
            $this->where['course_ids'] = '`canvas_course_id` in(
                select homeroom_canvas_course_id from mth_student_homeroom 
                where school_year_id='.$this->year.' group by homeroom_canvas_course_id
            )';
        }else{
            $courseIds = $this->PdoAdapter->parametrizeList($courseIds,'course_ids');
            $this->bind += $courseIds;
            $this->where['course_ids'] = '`canvas_course_id` IN ('.implode(',',array_keys($courseIds)).')';
        }
        return $this;
    }

    public function setModifiedSince($timestamp){
        $date = date('Y-m-d H:i:s',$timestamp);
        $this->bind[':modifiedSince'] = $date;
        $this->where['modifiedSince'] = '`last_modified`>:modifiedSince';
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
     * @return \mth_canvas_enrollment[]
     */
    public function getAll($page=null){
        return $this->PdoAdapter
            ->prepare($this->getQuery($page))
            ->execute($this->bind)
            ->fetchAllClass(\mth_canvas_enrollment::class);
    }

}