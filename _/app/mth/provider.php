<?php

/**
 * mth_provider
 *
 * @author abe
 */
class mth_provider extends core_model
{
    protected $provider_id;
    protected $name;
    protected $desc;
    protected $led_by;
    protected $min_grade_level;
    protected $max_grade_level;
    protected $alternative_min_grade_level;
    protected $alternative_max_grade_level;
    protected $diploma_valid;
    protected $available;
    protected $allow_2nd_sem_change; // mth2ndSem001.sql
    protected $deleted;
    protected $diploma_only;
    protected $popup;
    protected $popup_content;
    protected $available_in_school_assignment;
    protected $archived;
    protected $requires_multiple_periods;
    protected $multiple_periods;

    protected $whereAttr = array();
    protected $orderBy = array();
    protected static $cache = array();
    protected static $led_by_options = array(0 => '', 1 => 'content only', 2 => 'teacher-led', 3 => 'teacher-graded');

    public static function led_by_options()
    {
        return self::$led_by_options;
    }

    /**
     *
     * @param bool $reset
     * @return mth_provider
     */
    public static function each($gradeLevel = NULL, mth_course $course = NULL, $diplomaValidOnly = false, $reset = false)
    {
        $result = &self::$cache['each'][$gradeLevel][$course ? $course->getID() : NULL][$diplomaValidOnly];
        $alternative_min = !empty($_SESSION['allow_above_max_grade_level']) ? 'alternative_' : '';
        $alternative_max = !empty($_SESSION['allow_below_min_grade_level']) ? 'alternative_' : '';
        
        if($gradeLevel == 'OR-K') {
            $gradeLevel = -1;
        }

        if (!isset($result)) {
            $result = core_db::runQuery('SELECT * 
                FROM mth_provider AS p
                WHERE 1 AND deleted!=1
                  ' . ($gradeLevel || $course ? 'AND p.`available`=1' : '') . '
                  ' . ($gradeLevel ? 'AND (p.' . $alternative_min . 'min_grade_level<=' . (int)$gradeLevel .
                    ' AND p.' . $alternative_max . 'max_grade_level>=' . (int)$gradeLevel . ')' : '') . '
                  ' . ($diplomaValidOnly ? 'AND p.diploma_valid=1' : '') . '
                  ' . ($course ? 'AND (p.provider_id IN (SELECT pc.provider_id
                                                    FROM mth_provider_course_mapping AS pcm
                                                      INNER JOIN mth_provider_course AS pc ON pc.provider_course_id=pcm.provider_course_id
                                                    WHERE pcm.course_id=' . $course->getID() . ')
                                    ' . ($course->allowOtherMTHproviders()
    ? 'OR p.provider_id NOT IN (SELECT pc2.provider_id FROM mth_provider_course AS pc2)' : '') . ')' : '') . '
                ORDER BY p.`available` DESC, p.`name`');
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }
        if (($provider = $result->fetch_object('mth_provider'))) {
            self::cache(__CLASS__, $provider->id(), $provider);
            return $provider;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    /**
     * @param mixed $gradeLevel
     * @param mth_course|NULL $course
     * @param bool $diplomaValidOnly
     * @return mth_provider[]
     */
    public static function all($gradeLevel = NULL, mth_course $course = NULL, $diplomaValidOnly = false)
    {
        $arr = &self::$cache['all'][$gradeLevel][$course ? $course->getID() : NULL][$diplomaValidOnly];
        if (!isset($arr)) {
            self::each($gradeLevel, $course, $diplomaValidOnly, true);
            while ($provider = self::each($gradeLevel, $course, $diplomaValidOnly)) {
                $arr[$provider->id()] = $provider;
            }
        }
        return $arr;
    }

    public static function count($gradeLevel = NULL, mth_course $course = NULL, $diplomaValidOnly = false)
    {
        $count = &self::$cache['count'][$gradeLevel][$course ? $course->getID() : NULL][$diplomaValidOnly];
        if (!isset($count)) {
            $all = self::all($gradeLevel, $course, $diplomaValidOnly);
            $count = !empty($all) ? count($all) : 0;
        }
        return $count;
    }

    public static function allow2ndSemChange(mth_course $course)
    {
        $allow2ndSemChange = &self::$cache['allow2ndSemChange'][$course->getID()];
        if (!isset($allow2ndSemChange)) {
            $allow2ndSemChange = core_db::runGetValue('SELECT COUNT(p.provider_id) 
                                                  FROM mth_provider AS p
                                                    INNER JOIN mth_provider_course AS pc ON pc.provider_id=p.provider_id
                                                    INNER JOIN mth_provider_course_mapping AS pm ON pm.provider_course_id=pc.provider_course_id
                                                      AND pm.course_id=' . $course->getID() . '
                                                  WHERE p.allow_2nd_sem_change=1') > 0;
        }
        return $allow2ndSemChange;
    }

    /**
     *
     * @param string $name
     * @return mth_provider
     */
    public static function getByName($name)
    {
        $provider = &self::cache(__CLASS__, 'getByName-' . $name);
        if (!isset($provider)) {
            $provider = core_db::runGetObject('SELECT * FROM mth_provider WHERE `name`="' . core_db::escape($name) . '"', 'mth_provider');
        }
        return $provider;
    }

    /**
     *
     * @param int $provider_id
     * @return mth_provider
     */
    public static function get($provider_id)
    {
        $provider = &self::cache(__CLASS__, (int) $provider_id);
        if (!isset($provider)) {
            $provider = core_db::runGetObject(
                'SELECT * FROM mth_provider 
                                        WHERE provider_id=' . (int) $provider_id,
                'mth_provider'
            );
        }
        return $provider;
    }

    public function id()
    {
        return (int) $this->provider_id;
    }

    public function name($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('name', cms_content::sanitizeText($set));
        }
        return $this->name;
    }

    public function desc($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('desc', cms_content::sanitizeText($set));
        }
        return $this->desc;
    }

    public function deleted($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('deleted', cms_content::sanitizeText($set));
        }
        return $this->deleted;
    }

    public function led_by($returnNumber = false, $set = NULL)
    {
        if (!is_null($set)) {
            if (isset(self::$led_by_options[$set])) {
                $this->set('led_by', intval($set));
            }
        }
        if ($returnNumber || !isset(self::$led_by_options[$this->led_by])) {
            return $this->led_by;
        }
        return self::$led_by_options[$this->led_by];
    }

    public function min_grade_level($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $availableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set;
            if (isset($availableGrades[$index])) {
                $this->set('min_grade_level', (int) $set);
            }
        }
        if ($this->min_grade_level === '0' || $this->min_grade_level === 0) {
            $this->min_grade_level = 'K';
        }
        return ( $this->min_grade_level !== NULL && $this->min_grade_level != -1 ) ? $this->min_grade_level : ( $this->min_grade_level == -1 ? 'OR K' : 'K' );
    }

    public function alternativeMinGradeLevel($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $availableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set;
            if (isset($availableGrades[$index])) {
                $this->set('alternative_min_grade_level', (int)$set);
            }
        }
        if (!$this->alternative_min_grade_level && $this->alternative_min_grade_level !== NULL) {
            $this->alternative_min_grade_level = 'K';
        }
        return ( $this->alternative_min_grade_level !== NULL && $this->alternative_min_grade_level != -1 ) ? $this->alternative_min_grade_level : ( $this->alternative_min_grade_level == -1 ? 'OR K' : 'K' );
    }


    public function max_grade_level($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $availableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set;
            if (isset($availableGrades[$index])) {
                $this->set('max_grade_level', (int) $set);
            }
        }
        if ($this->max_grade_level == 0) {
            $this->max_grade_level = 'K';
        }
        return ( $this->max_grade_level !== NULL && $this->max_grade_level != -1 ) ? $this->max_grade_level : ( $this->max_grade_level == -1 ? 'OR K' : 12 );
    }

    public function alternativeMaxGradeLevel($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $availableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set;
            if (isset($availableGrades[$index])) {
                $this->set('alternative_max_grade_level', (int)$set);
            }
        }
        if (!$this->alternative_max_grade_level && $this->alternative_max_grade_level !== NULL) {
            $this->alternative_max_grade_level = 'K';
        }
        return ( $this->alternative_max_grade_level !== NULL && $this->alternative_max_grade_level != -1 ) ? $this->alternative_max_grade_level : ( $this->alternative_max_grade_level == -1 ? 'OR K' : 12 );
    }

    public function diploma_valid($set = NULL)
    {
        if ($set !== NULL) {
            $this->set('diploma_valid', $set ? 1 : 0);
        }
        return (bool) $this->diploma_valid;
    }

    public function available($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('available', $set ? 1 : 0);
        }
        return (bool) $this->available;
    }

    public function popup($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('popup', $set ? 1 : 0);
        }
        return (bool) $this->popup;
    }

    public function archived($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('archived', $set ? 1 : 0);
        }
        return $this->archived;
    }

    public function requiresMultiplePeriods($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('requires_multiple_periods', $set ? 1 : 0);
        }
        return $this->requires_multiple_periods;
    }

    public function multiplePeriods($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('multiple_periods', json_encode($set));
        }

        if($this->multiple_periods !== '') {
            return json_decode($this->multiple_periods);
        }
        return [];
    }

    /**
     * Method to store if the provider should display in school assignment manager
     * 
     * @param boolean $set
     * @return bool $available_in_school_assignment
     */
    public function isAvailableInSchoolAssignment($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('available_in_school_assignment', $set ? 1 : 0);
        }
        return (bool) $this->available_in_school_assignment;
    }

    public function popup_content($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('popup_content', $set);
        }
        return $this->popup_content;
    }

    public function diplomanaOnly($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('diploma_only', $set ? 1 : 0);
        }
        return $this->diploma_only;
    }

    public function save()
    {
        if (!$this->provider_id && !$this->name) {
            return FALSE;
        }
        if (!$this->provider_id) {
            core_db::runQuery('INSERT INTO mth_provider (`name`) VALUES ("UNNAMED")');
            $this->provider_id = core_db::getInsertID();
        }
        return $this->runUpdateQuery('mth_provider', 'provider_id=' . $this->id());
    }

    public function __destruct()
    {
        $this->save();
    }

    public function __toString()
    {
        $append = '';
        if ($this->led_by() || $this->min_grade_level() > 1 || $this->max_grade_level() < 12) {
            $append = ' (';
            if ($this->led_by()) {
                $append .= $this->led_by() . ' for ';
            }
            $append .= 'grades ' . $this->gradeSpan() . ')';
        }
        return $this->name() . $append;
    }

    public function gradeSpan()
    {
        return $this->min_grade_level() . ($this->min_grade_level() != $this->max_grade_level() ? '-' . $this->max_grade_level() : '');
    }

    public function allow_2nd_sem_change()
    {
        return (bool) $this->allow_2nd_sem_change;
    }

    public function set_allow_2nd_sem_change($value)
    {
        $this->set('allow_2nd_sem_change', $value ? 1 : 0);
    }

    /**
     * Get Providers by Name
     * @param string $name_expression  //sql like expression eg. %string% | string% | %string | string
     * @return void
     */
    public static function getProviderByName($name_expression, $reset = false)
    {
        $result = &self::$cache['getProviderByName'][$name_expression];
        if (!isset($result)) {
            $result = core_db::runQuery("select * from mth_provider where name like '$name_expression'");
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }
        if (($provider = $result->fetch_object('mth_provider'))) {
            self::cache(__CLASS__, $provider->id(), $provider);
            return $provider;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    /**
     * set where condition that will be use in query
     * 
     * @param string $field
     * @param string $value
     * 
     * @return class $this
     */
    public function where($field = "", $value = "", $operator = "=")
    {
        $this->whereAttr[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    /**
     * fetch the data from database
     * 
     * @return obj $result or false for fail
     */
    public function fetch()
    {
        $whereCondition = "";
        $whereAttr = $this->whereAttr;
        if (!empty($whereAttr)) {
            $whereCondition .= "where 1";
            foreach ($whereAttr as $value) {
                $whereCondition .= " && " . $value['field'] . " " . $value['operator'] . " " . $value['value'];
            }
        }

        $orderByCondition = "";
        $orderByAttr = $this->orderBy;
        if (!empty($orderByAttr)) {
            $orderByCondition .= "order by";
            foreach ($orderByAttr as $value) {
                $orderByCondition .= " " . $value['field'] . " " . $value['order'];
            }
        }

        $result = core_db::runGetObjects("select * from mth_provider " . $whereCondition . " " . $orderByCondition);

        if ($result) {
            return $result;
        }
        return false;
    }

    /**
     * order of query
     * 
     * @return class $this
     */
    public function orderBy($field, $order)
    {
        $this->orderBy[] = [
            'field' => $field,
            'order' => $order
        ];
        return $this;
    }
}
