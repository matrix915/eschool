<?php

/**
 * Immunizations
 * 
 * @author Cres <crestelitoc@codev.com>
 */

class mth_student_immunizations extends core_model
{
    protected $student_id;
    protected $immunization_id;
    protected $date_administered;
    protected $exempt;
    protected $immune;
    protected $nonapplicable;
    protected $updated_by;
    protected $date_created;
    protected $date_updated;
    protected static $cache = array();

    /**
     * Start of the getter section
     */

    public function getStudentId()
    {
        return $this->student_id;
    }
    
    public function getImmunizationId()
    {
        return $this->immunization_id;
    }

    public function getDateAdministered($format)
    {
        return self::getDate($this->date_administered, $format);
    }

    public function getExempt()
    {
        return $this->exempt;
    }  
    
    public function getImmune()
    {
        return $this->immune;
    } 
    
    public function getNonapplicable()
    {
        return $this->nonapplicable;
    }

    public function getUpdatedBy()
    {
        return $this->updated_by;
    }    
    
    public function getDateCreated()
    {
        return self::getDate($this->date_created, 'Y-m-d');
    }
    
    public function getDateUpdated()
    {
        return self::getDate($this->level_exempt_update, 'Y-m-d');
    }
    
    /**
     * End of getter section
     * Start of setter section
     */
    public function setStudentId($value)
    {
        if(!is_null($value)){
            $this->set('student_id',(int) $value);
        }
        return $this->student_id;
    }
    
    public function setImmunizationId($value)
    {
        if(!is_null($value)){
            $this->set('immunization_id',$value);
        }
        return $this->immunization_id;
    }

    public function setDateAdministered($value)
    {
        if(!is_null($value)){
            $this->set('date_administered',self::getDate($value, 'Y-m-d'));
        }
        return $this->date_administered;
    }

    public function setExempt($value)
    {
        if(!is_null($value)){
            $this->set('exempt', $value);
        }
        return $this->exempt;
    }    

    public function setImmune($value)
    {
        if(!is_null($value)){
            $this->set('immune', $value);
        }
        return $this->immune;
    } 
    
    public function setNonapplicable($value)
    {
        if(!is_null($value)){
            $this->set('nonapplicable', $value);
        }
        return $this->nonapplicable;
    }    
    
    public function setUpdatedBy($value)
    {
        if(!is_null($value)){
            $this->set('updated_by',$value);
        }
        return $this->updated_by;
    }

    /**
     * Database query section
     */

    /**
     * Returns mth_student_immunizations objects one at a time
     * @return mth_student_immunizations
     */
    public static function getEach()
    {
        return core_db::runGetObjects('SELECT * FROM mth_student_immunizations', 'mth_student_immunizations');
    }

    /**
     *
     * @param int $immunization_id
     * @return mth_student_immunizations
     */
    public static function getByID($immunization_id)
    {
        $result = &self::$cache['getByID'][$immunization_id];
        if ($result === NULL) {
            $result = core_db::runGetObject('SELECT * FROM mth_student_immunizations WHERE id=' . (int)$immunization_id, 'mth_student_immunizations');
        }
        return $result;
    }    
    
    /**
    *
    * @param int $student_id
    * @return mth_student_immunizations
    */
   public static function getByStudent($student_id)
   {
       $result = &self::$cache['getByStudent'][$student_id];
       if ($result === NULL) {
           $result = core_db::runGetObjects('SELECT msi.*, mis.* FROM mth_student_immunizations AS msi 
           LEFT JOIN mth_immunization_settings AS mis ON msi.immunization_id = mis.id  
           WHERE student_id=' . (int)$student_id, 'mth_student_immunizations');
       }
       return $result;
   }


    /**
     *
     * @return mth_student_immunizations
     */
    public function createOrUpdateBulk($date_administered, $ex, $na, $im, $im_ids)
    {
        $student_id = $this->student_id;
        $query = 'INSERT INTO mth_student_immunizations (student_id, immunization_id, date_administered, exempt, nonapplicable, updated_by, immune) VALUES ';
        foreach($im_ids as $id) {
            $query .= '('.
                        $student_id.', '.
                        $id.', '.
                        (isset($date_administered[$id]) && $date_administered[$id] !="" ? "'$date_administered[$id]'" : "NULL").', '.
                        (isset($ex[$id]) ? 1 : 0).','.
                        (isset($na[$id]) ? 1 : 0).','.
                        \core_user::getCurrentUser()->getID().','.
                        (isset($im[$id]) ? 1 : 0).'),';
        }
        $query = rtrim($query, ',');
        $query .= ' ON DUPLICATE KEY UPDATE date_administered=VALUES(date_administered), exempt=VALUES(exempt), nonapplicable=VALUES(nonapplicable), updated_by=VALUES(updated_by), immune=VALUES(immune)';
        
        return core_db::runQuery($query);
    }


    public static function getByImmunizationId($student_id, $immunization_id)
    {
        $result = &self::$cache['studentID'][$student_id]['immunizationID'][$immunization_id];
        if ($result === NULL) {
            $result = core_db::runGetObject('SELECT * FROM mth_student_immunizations WHERE student_id=' . (int)$student_id .' AND immunization_id='.(int)$immunization_id, 'mth_student_immunizations');
        }
        return $result;
    }

    public static function getDate($dateValueStr, $format)
    {
        if($dateValueStr == "0000-00-00 00:00:00") {
            return null;
        }
        
        if (!$dateValueStr) {
            return null;
        }

        if(is_array($dateValueStr)){
            reset($dateValueStr);
            $first_key = key($dateValueStr);
            $dateValueStr = $dateValueStr[$first_key];
        }

        if (($timestamp = &self::$cache['getDate'][$dateValueStr]) === NULL) {
            $timestamp = strtotime($dateValueStr);
        }

        if (!$format) {
            return $timestamp;
        }
        return date($format, $timestamp);
    }

    public static function gradeLevelReenrollRefresh($student_id, $nextGradeLevel) {
        if(!$student_id || !$nextGradeLevel) {
          return false;
        }
        return core_db::runQuery(
            "UPDATE mth_student_immunizations SET nonapplicable = 0 WHERE 
                student_id = " . $student_id . "
                AND immunization_id IN (SELECT id FROM mth_immunization_settings WHERE min_grade_level = '" . $nextGradeLevel . "')
                AND nonapplicable = 1"
        );
    }

}