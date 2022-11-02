<?php

/**
 * Immunizations Settings
 * 
 * @author Cres <crestelitoc@codev.com>
 */

class mth_immunization_settings extends core_model
{
    protected $id;
    protected $title;
    protected $min_grade_level;
    protected $max_grade_level;
    protected $exempt_update;
    protected $min_school_year_required;
    protected $max_school_year_required;
    protected $immunity_allowed;
    protected $level_exempt_update;
    protected $consecutive_vaccine;
    protected $min_spacing_interval;
    protected $min_spacing_date;
    protected $max_spacing_interval;
    protected $max_spacing_date;
    protected $email_update_template;
    protected $tooltip;
    protected static $cache = array();

    const DAYS = 1;
    const WEEKS = 2;
    const MONTHS = 3;

    const DAYS_LABEL = "DAYS";
    const WEEKS_LABEL = "WEEKS";
    const MONTHS_LABEL = "MONTHS";

    protected static $availableTime = array(
        self::DAYS => self::DAYS_LABEL,
        self::WEEKS => self::WEEKS_LABEL,
        self::MONTHS => self::MONTHS_LABEL
    );

    public static function getAvailableTime()
    {
        return self::$availableTime;
    }

    public static function timeLabel($time)
    {
        return isset(self::$availableTime[$time]) ? self::$availableTime[$time] : null;
    }

    /**
     * Start of the getter section
     */

    public function getID()
    {
        return (int)$this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }
    
    public function exemptUpdate()
    {
        return $this->exempt_update;
    }

    public function getMinGradeLevel()
    {
        return $this->min_grade_level;
    }

    public function getMaxGradeLevel()
    {
        return $this->max_grade_level;
    }

    public function getGradeLevelNonApplicable(mth_student $student, $year = NULL) {
        if(!$student) {
          return false;
        }
        if ($year === NULL) {
            $year = mth_schoolYear::getCurrent();
        }
        $studentGrade = $student->getGradeLevel(false, false, $year);
        $maximumGrade = $this->getMaxGradeLevel();
        $minimumGrade = $this->getMinGradeLevel();
        if (!$studentGrade) {
            return false;
        }

        if ($studentGrade === 'K' || $studentGrade === 'OR-K') {
            $newStudentGrade = ($studentGrade === 'OR-K' ? -1 : ( $studentGrade == 'K' ? 0 : $studentGrade));
            $newMaximumGrade = ($maximumGrade === 'OR-K' ? -1 : ( $maximumGrade == 'K' ? 0 : $maximumGrade));
            $newMinimumGrade = ($minimumGrade === 'OR-K' ? -1 : ( $minimumGrade == 'K' ? 0 : $minimumGrade));
            return ($newStudentGrade < $newMinimumGrade || $newStudentGrade > $newMaximumGrade);
        }

        if ($minimumGrade === 'K' || $minimumGrade === 'OR-K') {
            if ($studentGrade === 'OR-K') {
                $studentGrade = -1;
            } elseif ( $studentGrade == 'K') {
                $studentGrade = 0;
            }

            if ($maximumGrade === 'OR-K') {
                $maximumGrade = -1;
            } elseif ($maximumGrade === 'K') {
                $maximumGrade = 0;
            }
            return $studentGrade > $maximumGrade;
        }

        return ($studentGrade < $minimumGrade || $studentGrade > $maximumGrade);
    }
    
    public function getMinSchoolYearRequired()
    {
        return $this->min_school_year_required;
    }    
    
    public function getMaxSchoolYearRequired()
    {
        return $this->max_school_year_required;
    }    
    
    public function isImmunityAllowed()
    {
        return $this->immunity_allowed;
    }  
    
    public function getLevelExemptUpdate()
    {
        return unserialize($this->level_exempt_update);
    }    
    
    public function getConsecutiveVaccine()
    {
        return $this->consecutive_vaccine;
    }    
    
    public function getMinSpacingInterval()
    {
        return $this->min_spacing_interval;
    }    
    
    public function getMinSpacingDate()
    {
        return $this->min_spacing_date;
    }    
    
    public function getMaxSpacingInterval()
    {
        return $this->max_spacing_interval;
    }    
    
    public function getMaxSpacingDate()
    {
        return $this->max_spacing_date;
    }    
    
    public function getEmailUpdateTemplate()
    {
        return $this->email_update_template;
    }

    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * End of getter section
     * Start of setter section
     */
    public function setID($value)
    {
        if(!is_null($value)){
            $this->set('id',$value);
        }
        return $this->id;
    }

    public function setTitle($value)
    {
        if(!is_null($value)){
            $this->set('title',$value);
        }
        return $this->title;
    }

    public function setTooltip($value)
    {
        if(!is_null($value)){
            $this->set('tooltip',$value);
        }
        return $this->tooltip;
    }
    
    public function setExemptUpdate($value)
    {
        if(!is_null($value)){
            $this->set('exempt_update',$value);
        }
        return $this->exempt_update;
    }

    public function setMinGradeLevel($value)
    {
        if(!is_null($value)){
            $this->set('min_grade_level',$value);
        }
        return $this->min_grade_level;
    }

    public function setMaxGradeLevel($value)
    {
        if(!is_null($value)){
            $this->set('max_grade_level',$value);
        }
        return $this->max_grade_level;
    }    
    
    public function setMinSchoolYearRequired($value)
    {
        if(!is_null($value)){
            $this->set('min_school_year_required',$value);
        }
        return $this->min_school_year_required;
    }    
    
    public function setMaxSchoolYearRequired($value)
    {
        if(!is_null($value)){
            $this->set('max_school_year_required',$value);
        }
        return $this->max_school_year_required;
    }    
    
    public function setImmunityAllowed($value)
    {
        if(!is_null($value)){
            $this->set('immunity_allowed',$value);
        }
        return $this->immunity_allowed;
    }  
    
    public function setLevelExemptUpdate($value)
    {
        if(!is_null($value)){
            $this->set('level_exempt_update',serialize($value));
        }
        return $this->level_exempt_update;
    }    
    
    public function setConsecutiveVaccine($value)
    {
        if(!is_null($value)){
            $this->set('consecutive_vaccine',$value);
        }
        return $this->consecutive_vaccine;
    }    
    
    public function setMinSpacingInterval($value)
    {
        if(!is_null($value)){
            $this->set('min_spacing_interval',$value);
        }
        return $this->min_spacing_interval;
    }    
    
    public function setMinSpacingDate($value)
    {
        if(!is_null($value)){
            $this->set('min_spacing_date',(int) $value);
        }
        return $this->min_spacing_date;
    }    
    
    public function setMaxSpacingInterval($value)
    {
        if(!is_null($value)){
            $this->set('max_spacing_interval',$value);
        }
        return $this->max_spacing_interval;
    }    
    
    public function setMaxSpacingDate($value)
    {
        if(!is_null($value)){
            $this->set('max_spacing_date',(int) $value);
        }
        return $this->max_spacing_date;
    }    
    
    public function setEmailUpdateTemplate($value)
    {
        if(!is_null($value)){
            $this->set('email_update_template',$value);
        }
        return $this->email_update_template;
    }

    /**
     * Database query section
     */

    /**
     * Returns mth_immunizations objects one at a time
     * @return mth_immunization_settings
     */
    public static function getEach()
    {
        return core_db::runGetObjects('SELECT * FROM mth_immunization_settings', 'mth_immunization_settings');
    }

    /**
     * Returns mth_immunizations objects one at a time
     * @return mth_immunization_settings
     */
    public static function getAllTitles()
    {
        return core_db::runGetValues('SELECT title FROM mth_immunization_settings');
    }

        /**
     * Returns mth_immunizations objects one at a time
     * @return mth_immunization_settings
     */
    public static function getAllIds()
    {
        return core_db::runGetValues('SELECT id FROM mth_immunization_settings');
    }
    
    /**
     *
     * @param int $immunization_id
     * @return mth_immunization_settings
     */
    public static function getByID($immunization_id)
    {
        $result = &self::$cache['getByID'][$immunization_id];
        if ($result === NULL) {
            $result = core_db::runGetObject('SELECT * FROM mth_immunization_settings WHERE id=' . (int)$immunization_id, 'mth_immunization_settings');
        }
        return $result;
    }

    /**
     *
     * @return mth_immunization_settings
     */
    public function delete()
    {
        return core_db::runQuery('DELETE FROM mth_immunization_settings WHERE id=' . $this->getID());
    }

    /**
     *
     * @return mth_immunization_settings
     */
    public function save()
    {
        if (!$this->getID()) {
            core_db::runQuery('INSERT INTO mth_immunization_settings (title) VALUES ("UNTITLED")');
            $this->id = core_db::getInsertID();
        }
        return parent::runUpdateQuery('mth_immunization_settings', '`id`=' . $this->getID());
    }

    public static function getDate($dateValueStr, $format)
    {
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

}