<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 9/27/17
 * Time: 10:17 AM
 */

namespace mth\Reimbursement;


use core\Database\PdoAdapterInterface;
use core\DateTimeWrapper;
use core\Injectable;

class Query
{
    use Injectable, Injectable\PdoAdapterFactoryInjector;

    const PAGE_SIZE = 250;

    protected static $query = 'SELECT */*SELECT*/ 
                                FROM mth_reimbursement 
                                WHERE 1/*WHERE*/ 
                                ORDER BY reimbursement_id ASC /*LIMIT*/';

    /** @var  PdoAdapterInterface */
    protected $PdoAdapter;

    protected $where = [],
        $bind = [];

    public function __construct()
    {
        $this->PdoAdapter = $this->getPdoAdapter();
    }

    public function setParentIds(array $parentIds)
    {
        if(!$parentIds){ $parentIds = [-1]; }
        $parentIds = $this->PdoAdapter->parametrizeList($parentIds,'parentId');
        $this->bind += $parentIds;
        $this->where['parentIds'] = '`parent_id` IN ('.implode(',',array_keys($parentIds)).')';
        return $this;
    }

    public function setStudentIds(array $studentIds){
        if(!$studentIds){ $studentIds = [-1]; }
        $studentIds = $this->PdoAdapter->parametrizeList($studentIds,'studentId');
        $this->bind += $studentIds;
        $this->where['studentIds'] = '`student_id` IN ('.implode(',',array_keys($studentIds)).')';
        return $this;
    }

    public function setSchoolYearIds(array $schoolYearIds){
        if(!$schoolYearIds){ $schoolYearIds = [-1]; }
        $schoolYearIds = $this->PdoAdapter->parametrizeList($schoolYearIds,'schoolYearId');
        $this->bind += $schoolYearIds;
        $this->where['schoolYearIds'] = '`school_year_id` IN ('.implode(',',array_keys($schoolYearIds)).')';
        return $this;
    }

    public function setStatuses(array $statuses){
        if(!$statuses){ $statuses = [-1]; }
        $statuses = $this->PdoAdapter->parametrizeList($statuses,'status');
        $this->bind += $statuses;
        $this->where['statuses'] = '`status` IN ('.implode(',',array_keys($statuses)).')';
        return $this;
    }

    public function setTypes(array $types){
        if(!$types){ $types = [-1]; }
        $types = $this->PdoAdapter->parametrizeList($types,'type');
        $this->bind += $types;
        $this->where['type'] = '`type` IN ('.implode(',',array_keys($types)).')';
        return $this;
    }

    public function setMethods(array $methods){
        if(!$methods){ $methods = [-1]; }
        $methods = $this->PdoAdapter->parametrizeList($methods,'method');
        $this->bind += $methods;
        $this->where['method'] = '`is_direct_order` IN ('.implode(',',array_keys($methods)).')';
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

    public function dquery($page=null){
        return $this->getQuery($page);
    }

    /**
     * @param null|int $page
     * @return \mth_reimbursement[]
     */
    public function getAll($page=null){
        return $this->PdoAdapter
            ->prepare($this->getQuery($page))
            ->execute($this->bind)
            ->fetchAllClass(\mth_reimbursement::class);
    }

    /**
     * @param null|int $page
     * @return array
     */
    public function getStudentIds($page=null){
        return $this->PdoAdapter
            ->prepare($this->getQuery($page,'student_id'))
            ->execute($this->bind)
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getAllWithRelations($page = null)
    {
        $sql = "SELECT mr.*, DATE_FORMAT(mr.date_submitted, '%d-%M-%Y') AS date_submitted, DATE_FORMAT(mr.date_resubmitted, '%d-%M-%Y') AS date_resubmitted,mp.preferred_first_name AS student_preferred_first_name, mp.preferred_last_name AS student_preferred_last_name, mp.last_name AS student_last_name, mp.first_name AS student_first_name, mpp.preferred_first_name AS parent_preferred_first_name, mpp.first_name AS parent_first_name FROM mth_reimbursement as mr
                    LEFT JOIN mth_student as ms on mr.student_id = ms.student_id
                    INNER JOIN mth_person AS mp ON ms.person_id=mp.person_id
                    LEFT JOIN mth_parent AS mg ON ms.parent_id = mg.parent_id
                    INNER JOIN mth_person AS mpp ON mg.person_id = mpp.person_id
                    WHERE 1/*WHERE*/ 
                    ORDER BY IFNULL(date_resubmitted, date_submitted), IFNULL(student_preferred_last_name, student_last_name) ASC /*LIMIT*/";

        $tags = ['1/*WHERE*/'];
        $replace = [implode(' AND ',$this->where)];
        if($page){
            $tags[] = '/*LIMIT*/';
            $replace[] = 'LIMIT '.(($page-1)*self::PAGE_SIZE).','.self::PAGE_SIZE;
        }

        $final_query = str_replace($tags,$replace,$sql);

        return $this->PdoAdapter
        ->prepare($final_query)
        ->execute($this->bind)
        ->fetchAllClass(\mth_reimbursement::class);
    }

}