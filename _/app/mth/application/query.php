<?php

/**
 * Created by PhpStorm.
 * User: abe
 * Date: 3/27/17
 * Time: 4:15 PM
 */
class mth_application_query
{
    const PAGE_SIZE = 250;

    protected static $query = 'SELECT a.* FROM mth_application AS a /*JOIN_CLAUSE*/ WHERE 1/*WHERE_CLAUSE*/ /*LIMIT*/';

    protected $join = [],
        $where = [];

    /** @var  mysqli_result */
    protected $result;

    /** @var  mth_application[] */
    protected $all;

    protected function joinStudent(){
        $this->join['student'] = 'LEFT JOIN mth_student AS s ON s.student_id=a.student_id';
    }

    protected function joinStudentGradeLevel(){
        $this->join['student_grade_level'] = 'LEFT JOIN mth_student_grade_level AS sgl 
                                                ON sgl.school_year_id=a.school_year_id 
                                                    AND sgl.student_id=a.student_id';
    }

    /*The Join methods for joinEmailVerifier are needed to get the verification status of the parent's email.
    They are all required to get to mth_emailverifier.  Otherwise it will connect each record in email verified to each application
    and return a very long list of repeated application records.
    */
    public function joinEmailVerifier($verified) {
        if($verified === []) {
            return $this;
        }
    $this->join['emailverifier'] = 'JOIN mth_student as ms ON ms.student_id=a.student_id
    JOIN mth_parent as mp ON mp.parent_id=ms.parent_id
    JOIN mth_person as mpe ON mpe.person_id=mp.person_id
    JOIN mth_emailverifier as mev ON mev.email=mpe.email';
        $this->where['emailverifier'] = 'mev.verified IN ('.implode(',', array_map('intval', $verified)).')';
        return $this;
    }

    public function setSchoolYear(array $schoolYearIDs){
        if(!$schoolYearIDs){ return $this; }
        $this->where['schoolyear'] = 'a.school_year_id IN ('.implode(',', array_map('intval', $schoolYearIDs)).')';
        return $this;
    }
    /**
     * @param array $sped
     * @return $this
     */
    public function setSPED(array $sped){
        if(!$sped){ return $this; }
        $this->joinStudent();
        $this->where['sped'] = 's.special_ed IN (' . implode(',', array_map('intval', $sped)) . ')';
        return $this;
    }

    /**
     * @param array $grade_levels
     * @return $this
     */
    public function setGradeLevel(array $grade_levels){
        if(!$grade_levels){ return $this; }
        $this->joinStudentGradeLevel();
        $this->where['grade_level'] = 'sgl.grade_level IN ("'.implode('","',array_map(['core_db','escape'],$grade_levels)).'")';
        return $this;
    }

    /**
     * @param array $status
     * @return $this
     */
    public function setStatus(array $status){
        if(!$status){ return $this; }
        $this->where['status'] = 'a.status IN ("'.implode('","',array_map(['core_db','escape'],$status)).'")';
        return $this;
    }

    public function setHidden($value){
        $this->where['hidden'] = 'a.hidden = '.(int)$value;
        return $this;
    }

   public function setNoParentFilter(){
       $this->joinStudent();
       $this->where['noParent'] = 's.parent_id IS NOT NULL';
       return $this;
   }

    protected function executeQuery($page=null){
      $tags = ['/*JOIN_CLAUSE*/', '1/*WHERE_CLAUSE*/'];

      $replace = [
        implode("\n", $this->join),
        implode("\n AND ", $this->where)
      ];

      if ($page) {
        $tags[] = '/*LIMIT*/';
        $replace[] = 'LIMIT ' . (($page - 1) * self::PAGE_SIZE) . ',' . self::PAGE_SIZE;
      }

      $this->result = core_db::runQuery(str_replace(
        $tags,
        $replace,
        self::$query
      ));
    }

    /**
     * @return mth_application[]
     */
    public function getAll($page=null){
        if(!$this->result){
            $this->executeQuery($page);
        }
        if($this->all===NULL){
            $this->all = [];
            while($application = $this->result->fetch_object(mth_application::class)){
                /** @var $application mth_application */
                $this->all[$application->getID()] = $application;
            }
        }
        return $this->all;
    }
}