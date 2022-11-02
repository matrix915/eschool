<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 6/21/17
 * Time: 1:46 PM
 */

namespace mth\schedule;


class query
{
    protected static $query = 'SELECT /*SELECT*/ 
                              FROM mth_schedule AS sch
                                /*JOIN*/ 
                              WHERE 1 /*WHERE*/';

    protected $where = [],
            $join = [];


    /**
     * will cause duplicate records
     */
    protected function joinSchedulePeriod(){
        $this->join['schedule_period'] = 'LEFT JOIN mth_schedule_period AS sp ON sp.schedule_id=sch.schedule_id';
    }


    /**
     * @param array $provider_ids
     * @return $this
     */
    public function setProviderIds(array $provider_ids){
        $this->joinSchedulePeriod();
        if(!$provider_ids){
            $provider_ids = [0];
        }
        $this->where['provider_ids'] =
            'AND sp.mth_provider_id IN ('.implode(',',array_map('intval',$provider_ids)).')
            AND sp.course_type='.\mth_schedule_period::TYPE_MTH.'
            AND sp.subject_id IS NOT NULL';
        return $this;
    }

     /**
     * @param array $provider_ids
     * @return $this
     */
    public function isDirect(){
        $this->join['all_schedule_period'] = 'LEFT JOIN mth_schedule_period AS sp ON sp.schedule_id=sch.schedule_id';

        $this->where['direct'] =
            'AND sp.course_type='.\mth_schedule_period::TYPE_MTH.'
            AND sp.subject_id IS NOT NULL';
        return $this;
    }

    /**
     * @param array $school_year_ids
     * @return $this
     */
    public function setSchoolYearIds(array $school_year_ids){
        if(!$school_year_ids){
            $school_year_ids = [0];
        }
        $this->where['school_year_id'] =
            'AND sch.school_year_id IN ('.implode(',',array_map('intval',$school_year_ids)).')';
        return $this;
    }

    /**
     * @param array $status_ids
     * @return $this
     */
    public function setStatuses(array $status_ids){
        if(!$status_ids){
            $status_ids = [9999999]; //so no results are returned
        }
        $this->where['status_ids'] =
            'AND sch.status IN ('.implode(',',array_map('intval',$status_ids)).')';
        return $this;
    }
    /**
     * Filter by grade level
     * @param array $grade_level
     * @param array $school_year_ids
     * @return $this
     */
    public function setGradeLevel(array $grade_level,array $school_year_ids){
        $this->where['grade_level'] = "AND sch.student_id in(select student_id from 
        mth_student_grade_level where school_year_id in('".implode("','",$school_year_ids)."') and grade_level in('".implode("','",$grade_level)."'))";
        return $this;
    }

    protected function getQuery($select='sch.*'){
        if(isset($this->join['schedule_period'])){
            $select = 'DISTINCT '.$select;
        }
        return str_replace(
            [
                '/*SELECT*/',
                '/*JOIN*/',
                '/*WHERE*/'
            ],
            [
                $select,
                implode(PHP_EOL,$this->join),
                implode(PHP_EOL,$this->where)
            ],
            self::$query
        );
    }

    /**
     * @return \mth_schedule[]|bool
     */
    public function getAll($select='sch.*'){
        return \core_db::runGetObjects($this->getQuery($select),\mth_schedule::class);
    }

    public function getStudentIds(){
        //\core_db::$log_next = true;
        return \core_db::runGetValues($this->getQuery('sch.student_id'));
    }
}