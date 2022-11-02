<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 6/21/17
 * Time: 3:39 PM
 */

namespace mth\packet;


use core\DateTimeWrapper;

class query
{
    protected static $query = 'SELECT /*SELECT*/ FROM mth_packet AS p /*JOIN*/ WHERE 1 /*WHERE*/';

    protected $where = [],
        $join = [];

    /**
     * @param array $school_districts
     * @return $this
     */
    public function setSchoolDistricts(array $school_districts){
        if(!$school_districts){
            $school_districts = [''];
        }
        $this->join['mth_student'] = 'LEFT JOIN mth_student ms ON ms.student_id = p.student_id';
        $this->join['mth_parent'] = 'LEFT JOIN mth_parent mp ON ms.parent_id = mp.parent_id';
        $this->join['mth_person_address'] = 'LEFT JOIN mth_person_address mpa ON mp.person_id = mpa.person_id';
        $this->join['mth_address'] = 'LEFT JOIN mth_address ma ON mpa.address_id = ma.address_id';
        $this->where['school_districts'] =
            'AND ma.school_district IN ("'.implode('","',array_map(['core_db','escape'],$school_districts)).'")';
        return $this;
    }

    public function setParentSchoolDistricts(array $school_districts, $schoolYearId = 11, $status = [0,1]) {
        $this->join['mth_student'] = ' LEFT JOIN mth_student ms ON p.student_id = ms.student_id';
        $this->join['mth_student_status'] = 'LEFT JOIN mth_student_status AS ss ON ss.student_id=ms.student_id';
        $this->join['mth_parent'] = ' LEFT JOIN mth_parent mp ON ms.parent_id = mp.parent_id';
        $this->join['mth_person'] = ' LEFT JOIN mth_person mps ON mp.person_id = mps.person_id';
        $this->join['mth_person_address'] = ' LEFT JOIN mth_person_address mpa ON mps.person_id = mpa.person_id';
        $this->join['mth_address'] = ' LEFT JOIN mth_address ma ON mpa.address_id = ma.address_id';
        $this->where['school_year_id'] = ' AND ss.school_year_id=' . $schoolYearId; 
        $this->where['status'] = 'AND ss.status IN (' . implode(',', $status) . ')'; 
        $this->where['school_districts'] =
            ' AND ma.school_district IN ("'.implode('","',array_map(['core_db','escape'],$school_districts)).'")';
        return $this;
    }

    /**
     * @return $this
     */
    public function setIncludePacketsMissingData(){
        $this->where['missing_data'] =
            'AND (`school_district` IS NULL
                 OR `special_ed` IS NULL
                 OR `last_school_type` IS NULL
                 OR `hispanic` IS NULL
                 OR `race` IS NULL
                 OR `language` IS NULL
                 OR `language_home` IS NULL
                 OR `language_home_child` IS NULL
                 OR `language_friends` IS NULL
                 OR `language_home_preferred` IS NULL
                 OR `work_move` IS NULL
                 OR `living_location` IS NULL
                 OR `secondary_contact_first` IS NULL
                 OR `secondary_contact_last` IS NULL
                 OR `secondary_phone` IS NULL
                 OR `secondary_email` IS NULL
                 OR `birth_place` IS NULL
                 OR `birth_country` IS NULL
                 OR `agrees_to_policy` IS NULL)';
        return $this;
    }

    public function setIncludePacketsMissingTooeleData(){
        $this->where['missing_data'] =
            "AND ((`school_district` IS NULL or `school_district` = '')
                 OR (`language` = '' or `language` IS NULL)
                 OR (`language_home` = '' or  `language_home` IS NULL)
                 OR (`language_home_child` = '' or `language_home_child` IS NULL)
                 OR (`language_friends` = '' or `language_friends` IS NULL)
                 OR (`language_home_preferred` = '' or `language_home_preferred` IS NULL)
                 OR (`living_location` = '' or `living_location` is NULL))";
        return $this;
    }

    /**
     * @param array $statuses
     * @return $this
     */
    public function setStatuses(array $statuses){
        if(!$statuses){
            $statuses = [''];
        }
        $this->where['statuses'] =
            'AND p.status IN ("'.implode('","',array_map(['core_db','escape'],$statuses)).'")';
        return $this;
    }

    /**
     * @param DateTimeWrapper $min
     * @param DateTimeWrapper $max
     * @return $this
     */
    public function setDateSubmitted(DateTimeWrapper $min, DateTimeWrapper $max)
    {
        $this->where['date_submitted'] =
            'AND p.date_submitted IS NOT NULL
            '.($min->getDatetime()?'AND p.date_submitted>="'.$min->Format('Y-m-d H:i:s').'"':'').'
            '.($min->getDatetime()?'AND p.date_submitted<="'.$max->Format('Y-m-d H:i:s').'"':'');
        return $this;
    }

    /**
     * @param array $student_ids
     * @return $this
     */
    public function setStudentIds(array $student_ids){
        if(!$student_ids){
            $student_ids = [0];
        }
        $this->where['student_ids'] =
            'AND p.student_id IN ('.implode(',',array_map('intval',$student_ids)).')';
        return $this;
    }

    protected function getQuery($select = 'p.*'){
        return str_replace(
            [
                '/*SELECT*/',
                '/*JOIN*/',
                '/*WHERE*/'
            ],
            [
                $select,
                implode(PHP_EOL, $this->join),
                implode(PHP_EOL,$this->where)
            ],
            self::$query
        );
    }

    /**
     * @return \mth_packet[]|bool
     */
    public function getAll(){
        return \core_db::runGetObjects($this->getQuery(),\mth_packet::class);
    }


    public function getStudentIds(){
        //\core_db::$log_next = true;
        return \core_db::runGetValues($this->getQuery('p.student_id'));
    }
}