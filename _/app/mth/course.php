<?php

/**
 * mth_course
 *
 * @author abe
 */
class mth_course extends core_model
{
    protected $course_id;
    protected $subject_id;
    protected $title;
    protected $allow_other_mth;
    protected $allow_custom;
    protected $allow_tp;
    protected $custom_course_description;
    protected $min_grade_level;
    protected $max_grade_level;
    protected $alternative_min_grade_level;
    protected $alternative_max_grade_level;
    protected $diploma_valid;
    protected $available;
    protected $allow_2nd_sem_change; // mth2ndSem001.sql
    protected $allowance;
    protected $archived;
    protected $state_codes;
    protected $is_launchpad_course;
    protected $spark_course_id;

    protected static $cache = array();

    public function getCourseTypeOptions($gradeLevel = NULL, $diplomaSeeking = false)
    {
        $options = mth_schedule_period::course_type_options();
        if ($diplomaSeeking && !$this->hasDiplomaProviders($gradeLevel)) {
            unset($options[mth_schedule_period::TYPE_MTH]);
        } elseif (!$diplomaSeeking && !$this->hasProviders($gradeLevel)) {
            unset($options[mth_schedule_period::TYPE_MTH]);
        }
        if (!$this->allow_tp || $diplomaSeeking) {
            unset($options[mth_schedule_period::TYPE_TP]);
        }
        if (!$this->allow_custom || $diplomaSeeking) {
            unset($options[mth_schedule_period::TYPE_CUSTOM]);
        }
        return $options;
    }

    public function getID()
    {
        return (int) $this->course_id;
    }

    public function subjectID($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('subject_id', (int) $set);
        }
        return (int) $this->subject_id;
    }

    public function subject()
    {
        return mth_subject::getByID($this->subject_id);
    }

    public function showProviders()
    {
        if (($subject = mth_subject::getByID($this->subjectID()))) {
            return $subject->showProviders();
        }
        return false;
    }

    public function hasProviders($gradeLevel = NULL)
    {
        return mth_provider::count($gradeLevel, $this) > 0;
    }

    public function hasDiplomaProviders($gradeLevel = NULL)
    {
        return mth_provider::count($gradeLevel, $this, true) > 0;
    }

    public function availableToDiplomaStudents($gradeLevel = NULL)
    {
        return $this->diploma_valid
            && (!$this->showProviders() || $this->hasDiplomaProviders($gradeLevel));
    }

    public function title($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('title', cms_content::sanitizeText($set));
        }
        return req_sanitize::txt_decode($this->title);
    }

    public function allowance($set = null)
    {
        if (!is_null($set)) {
            $this->set('allowance', req_sanitize::float($set));
        }
        return self::getNumber($this->allowance, 2);
    }

    public function allowOtherMTHproviders($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('allow_other_mth', $set ? 1 : 0);
        }
        return (bool) $this->allow_other_mth;
    }

    public function allowCustom($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('allow_custom', $set ? 1 : 0);
        }
        return (bool) $this->allow_custom;
    }

    public function allowTP($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('allow_tp', $set ? 1 : 0);
        }
        return (bool) $this->allow_tp;
    }

    public function customCourseDescription($set = NULL) {
        if (!is_null($set)) {
            $this->set('custom_course_description', $set);
        }
        return $this->custom_course_description;
    }

    public function requireDesc($gradeLevel = NULL)
    {
        return $this->allow_custom
            && !$this->allow_tp
            && !$this->allow_other_mth
            && (!$this->showProviders() || !$this->hasProviders($gradeLevel));
    }

    public function requireTP($gradeLevel = NULL)
    {
        return $this->allow_tp
            && !$this->allow_custom
            && !$this->allow_other_mth
            && (!$this->showProviders() || !$this->hasProviders($gradeLevel));
    }

    public function validForDiploma($gradeLevel = NULL)
    {
        return !$this->showProviders() || $this->hasDiplomaProviders($gradeLevel);
    }

    public function minGradeLevel($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $aviableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set; 
            if (isset($aviableGrades[$index])) {
                $this->set('min_grade_level', (int) $set);
            }
        }
        if (!$this->min_grade_level && $this->min_grade_level !== NULL) {
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
            $aviableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set; 
            if (isset($aviableGrades[$index])) {
                $this->set('alternative_min_grade_level', (int)$set);
            }
        }
        if (!$this->alternative_min_grade_level && $this->alternative_min_grade_level !== NULL) {
            $this->alternative_min_grade_level = 'K';
        }
        return ( $this->alternative_min_grade_level !== NULL && $this->alternative_min_grade_level != -1 ) ? $this->alternative_min_grade_level : ( $this->alternative_min_grade_level == -1 ? 'OR K' : 'K' );
    }

    public function maxGradeLevel($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $aviableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set; 
            if (isset($aviableGrades[$index])) {
                $this->set('max_grade_level', (int) $set);
            }
        }
        if (!$this->max_grade_level && $this->max_grade_level !== NULL) {
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
            $aviableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set; 
            if (isset($aviableGrades[$index])) {
                $this->set('alternative_max_grade_level', (int)$set);
            }
        }
        if (!$this->alternative_max_grade_level && $this->alternative_max_grade_level !== NULL) {
            $this->alternative_max_grade_level = 'K';
        }
        return ( $this->alternative_max_grade_level !== NULL && $this->alternative_max_grade_level != -1 ) ? $this->alternative_max_grade_level : ( $this->alternative_max_grade_level == -1 ? 'OR K' : 12 );
    }

    public function available($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('available', $set ? 1 : 0);
        }
        return (bool) $this->available;
    }

    public function diploma_valid($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('diploma_valid', $set ? 1 : 0);
        }
        return (bool) $this->diploma_valid;
    }

    public function archived($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('archived', $set ? 1 : 0);
        }
        return (bool)$this->archived;
    }

    public function isLaunchpadCourse($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('is_launchpad_course', $set ? 1 : 0);
        }
        return (bool) $this->is_launchpad_course;
    }

    public function sparkCourseId($set=NULL)
    {
        if (!is_null($set)) {
            $this->set('spark_course_id', $set);
        }
        return $this->spark_course_id;
    }

    public function stateCodes($set = NULL)
    {
        if (!is_null($set)) {
            $currentStateCodes = mth_coursestatecode::getCourseCodeMappings($this);

            foreach ($set as $grade => $code) {
                if (!array_key_exists($grade, $currentStateCodes)) {
                    $stateCode = new mth_coursestatecode();
                    $stateCode->grade($grade);
                    $stateCode->state_code($code['state_code']);
                    $stateCode->teacher_name($code['teacher_name']);
                    $stateCode->subject_id($this->subject_id);
                    $stateCode->course_id($this->course_id);
                    $stateCode->save();
                } else {
                    $stateCode = mth_coursestatecode::getByGradeAndCourse($grade, $this);
                    if (!is_null($stateCode)) {
                        $stateCode->state_code($code['state_code']);
                        $stateCode->teacher_name($code['teacher_name']);
                        $stateCode->save();
                    }
                }
            }
        }

        $newCodes = mth_coursestatecode::getCourseCodeMappings($this);
        return $newCodes;
    }

    public function __destruct()
    {
        $this->save();
    }

    public function save()
    {
        if (!$this->course_id && !$this->title) {
            return false;
        }
        if(cms_content::checkForbiddenCharacters($this->title)) {
           core_notify::addError('Invalid characters in course title!');
           return core_loader::redirect(req_post::txt('button') == 'Save/New'
              ? '?subject=' . $this->subjectID()
              : '?course_id=' . $this->getID());
        }
        if (!$this->course_id) {
            core_db::runQuery('INSERT INTO mth_course (title) VALUES ("UNTITLED")');
            $this->course_id = core_db::getInsertID();
        }
        return parent::runUpdateQuery('mth_course', '`course_id`=' . $this->getID());
    }

    /**
     *
     * @param mth_subject $subject
     * @param bool $reset
     * @return mth_course
     */
    public static function getEach(mth_subject $subject, $gradeLevel = NULL, $reset = FALSE, $providerId = null)
    {
        $result = &self::$cache['getEach'][$subject->getID()][$gradeLevel];
        $alternative_min = !empty($_SESSION['allow_above_max_grade_level']) ? 'alternative_' : '';
        $alternative_max = !empty($_SESSION['allow_below_min_grade_level']) ? 'alternative_' : '';
        if($gradeLevel == 'OR-K') {
            $gradeLevel = -1;
        }
        if ($result === NULL) {
            $result = core_db::runQuery('SELECT * FROM mth_course c
              WHERE c.subject_id=' . $subject->getID() . '
              ' . ($gradeLevel ? 'AND c.`available`=1' : '') . '
              ' . ($gradeLevel ? 'AND (c.' . $alternative_min .'min_grade_level<=' . (int)$gradeLevel
                    . ' AND c.' . $alternative_max . 'max_grade_level>=' . (int)$gradeLevel . ')' : '') .
                ($providerId ? ' AND c.course_id IN (SELECT mc.course_id FROM mth_course mc
                  INNER JOIN mth_provider_course_mapping mpch on mc.course_id = mpch.course_id
                   INNER JOIN mth_provider_course mpc on mpch.provider_course_id = mpc.provider_course_id
                    WHERE mpc.provider_id = ' . (int) $providerId . ') '
                    : '')
                . ' ORDER BY c.`available` DESC, IF(c.`title` LIKE "%Other%",1,0) ASC, c.`title`'
              );
        }
        if (!$reset && ($course = $result->fetch_object('mth_course'))) {
            self::$cache['getByID'][$course->getID()] = $course;
            return $course;
        }
        $result->data_seek(0);
        return NULL;
    }

    public function getByTitleLike(mth_subject $subject, $gradeLevel = NULL, $title = "")
    {
        $result = &self::$cache['getByTitleLike'][$subject->getID()][$gradeLevel];
        if ($result === NULL) {
            $result = core_db::runQuery('SELECT * 
            FROM mth_course 
            WHERE subject_id=' . $subject->getID() . '
              ' . ($gradeLevel ? 'AND `available`=1' : '') . '
              ' . ($gradeLevel ? 'AND (min_grade_level<=' . (int) $gradeLevel . ' AND max_grade_level>=' . (int) $gradeLevel . ')' : '') . '
              ' . ($title ? 'AND `title` LIKE "%' . $title . '%"' : '') . '
            ORDER BY `available` DESC');
        }

        if ($course = $result->fetch_object('mth_course')) {
            self::$cache['getByID'][$course->getID()] = $course;
            return $course;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function getAll(mth_subject $subject, $gradeLevel = NULL, $providerId = null)
    {
        $arr = &self::$cache['getAll'][$subject->getID()][$gradeLevel];
        if ($arr === NULL) {
            $arr = array();
            self::getEach($subject, $gradeLevel, true, $providerId);
            while ($course = self::getEach($subject, $gradeLevel, false, $providerId)) {
                $arr[$course->getID()] = $course;
            }
        }
        return $arr;
    }

    public static function getCount(mth_subject $subject, $gradeLevel = NULL, $providerId = null)
    {
        $result = &self::$cache['getEach'][$subject->getID()][$gradeLevel];
        if ($result === NULL) {
            self::getEach($subject, $gradeLevel, true, $providerId);
        }
        return $result->num_rows;
    }

    /**
     *
     * @param int $course_id
     * @return mth_course
     */
    public static function getByID($course_id)
    {
        $course = &self::$cache['getByID'][$course_id];
        if ($course === NULL) {
            $course = core_db::runGetObject('SELECT * FROM mth_course WHERE course_id=' . (int) $course_id, 'mth_course');
        }
        return $course;
    }
    public static function getSprakCourses(){
        $course = core_db::runGetObjects("SELECT course.* FROM mth_course AS course
                WHERE course.spark_course_id != ''");
        return $course;
    }


    /**
     *
     * @param string $title
     * @return mth_course
     */
    public static function getByTitle($title)
    {
        $course = &self::$cache['getByTitle'][$title];
        if ($course === NULL) {
            $course = core_db::runGetObject('SELECT * FROM mth_course WHERE title="' . core_db::escape($title) . '"', 'mth_course');
        }
        return $course;
    }

    public function __toString()
    {
        return $this->title();
    }

    public function delete()
    {
        mth_schoolYear::each(true);
        while ($year = mth_schoolYear::each()) {
            if (mth_schedule_period::countWithCourse($this) > 0) {
                return false;
            }
        }
        return core_db::runQuery('DELETE FROM mth_course WHERE course_id=' . $this->getID());
    }

    public function allow_2nd_sem_change($periodNum)
    {
        $secondSemPeriods = &self::$cache['allow_2nd_sem_change'][$this->course_id];
        if ($secondSemPeriods === NULL) {
            $secondSemPeriods = explode(',', $this->allow_2nd_sem_change);
        }
        return in_array($periodNum, $secondSemPeriods);
    }

    public function set_allow_2nd_sem_change(array $periodNums)
    {
        $this->set('allow_2nd_sem_change', implode(',', array_map('intval', $periodNums)));
        self::$cache['allow_2nd_sem_change'] = NULL;
    }

    /**
     *
     * @return mth_course[]
     */
    public static function getTechCourses()
    {
        return core_db::runGetObjects('SELECT c.* 
                                    FROM mth_course AS c
                                      LEFT JOIN mth_provider_course_mapping AS pcm ON pcm.course_id=c.course_id
                                    WHERE pcm.provider_course_id IS NULL
                                      AND c.title NOT LIKE "%homeroom%"
                                      AND c.allow_other_mth=0
                                      AND c.allow_custom=0
                                      AND c.allow_tp=0
                                    ORDER BY c.title', 'mth_course');
    }

    public function getLinkedProviderCourses()
    {
        return core_db::runGetObjects('SELECT * 
         FROM mth_provider_course where provider_course_id in (
             select provider_course_id from mth_provider_course_mapping where course_id = ' . $this->course_id . '
         )
        ');
    }
}
