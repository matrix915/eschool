<?php

class mth_coursestatecode extends core_model
{
    protected $course_state_code_id;
    protected $school_year_id;
    protected $grade;
    protected $course_id;
    protected $subject_id;
    protected $state_code;
    protected $teacher_name;

    protected static $cache = [];

    public static function getEach(mth_course $course = null, $reset = false)
    {
        $result = &self::$cache['getEach'][$course ? $course->getID() : NULL];
        if ($result === null) {
            $query = '
            SELECT * FROM mth_course_state_code 
            WHERE (course_id, school_year_id) IN 
              (SELECT course_id, MAX(school_year_id) FROM mth_course_state_code GROUP BY course_id)'
            . ($course ? ('AND course_id = ' . $course->getID()) : '');

            $result = core_db::runQuery($query);
        }
        if (!$reset && ($courseStateCode = $result->fetch_object('mth_coursestatecode'))) {
            self::$cache['getByID'][$courseStateCode->getID()] = $courseStateCode;
            return $courseStateCode;
        }
        $result->data_seek(0);
        return null;
    }

    public static function getAll(mth_course $course = null)
    {
        $results = &self::$cache['getAll'][$course ? $course->getID() : NULL];
        if ($results === NULL) {
            $results = [];
            self::getEach($course, true);
            while ($stateCode = self::getEach($course)) {
                $results[$stateCode->getID()] = $stateCode;
            }
        }
        return $results;
    }

    public static function getByGradeAndCourse($grade, mth_course $course)
    {
        if (is_null($grade) || is_null($course)) {
            return null;
        }
        $query = '
            SELECT * FROM mth_course_state_code 
            WHERE grade = ' . (int) $grade . '
            AND course_id = ' . $course->getID() . '
            ORDER BY school_year_id DESC 
            LIMIT 1
            ';
        $result = core_db::runGetObjects($query, 'mth_coursestatecode');
        return count($result) === 1 ? $result[0] : null;
    }
    
    public static function getCourseCodeMappings(mth_course $course)
    {
        $results = self::getAll($course);
        $mappedResults = [];
        foreach ($results as $result) {
            $mappedResults[$result->grade()] = [
                'code' => $result->state_code(),
                'teacher' => $result->teacher_name(),
            ];
        }
        return $mappedResults;
    }

    public function save()
    {
        if (!$this->course_state_code_id &&
            ((!$this->state_code && !$this->teacher_name) || // If new record must have state code or teacher
            (!isset($this->grade) || !$this->course_id))) {
            return false;
        }
        if (!$this->course_state_code_id) {
            core_db::runQuery('INSERT INTO mth_course_state_code (school_year_id) VALUES (' . mth_schoolYear::getCurrent()->getID() . ')');
            $this->course_state_code_id = core_db::getInsertID();
        }
        return parent::runUpdateQuery('mth_course_state_code', '`course_state_code_id`=' . $this->getID());
    }

    public function school_year_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('school_year_id', $set);
        }
        return (int) $this->school_year_id;
    }
    
    public function grade($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('grade', $set);
        }
        return (int) $this->grade;
    }

    public function course_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('course_id', $set);
        }
        return (int) $this->course_id;
    }

    public function subject_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('subject_id', $set);
        }
        return (int) $this->subject_id;
    }
    
    public function state_code($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('state_code', $set);
        }
        return $this->state_code;
    }

    public function teacher_name($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('teacher_name', $set);
        }
        return $this->teacher_name;
    }

    public function getID()
    {
        return (int)$this->course_state_code_id;
    }
}