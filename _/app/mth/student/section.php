<?php

/**
 * Description of section
 *
 * @author abe
 */
class mth_student_section
{
    ############################ DATABASE FIELDS #########################

    protected $student_id,
        $period_num,
        $schoolYear_id,
        $name;

    ############################ STATIC MEMBERS ##########################

    protected static $cache = array();

    protected static $names = array(
        'Section 1',
        'Section 2',
        'Section 3',
        'Section 4'
    );

    public static function names()
    {
        return self::$names;
    }

    ############################ GET METHODS ################################

    public function student_id()
    {
        return (int)$this->student_id;
    }

    public function period_num()
    {
        return (int)$this->period_num;
    }

    public function schoolYear_id()
    {
        return (int)$this->schoolYear_id;
    }

    public function name()
    {
        return $this->name;
    }

    ############################ STATIC METHODS ##################################

    /**
     *
     * @param mth_student $student
     * @param mth_period $period
     * @param mth_schoolYear $schoolYear
     * @param string $name
     * @return bool
     */
    public static function set(mth_student $student, mth_period $period, mth_schoolYear $schoolYear, $name)
    {
        $cleanName = req_sanitize::txt($name);
        $success = core_db::runQuery('REPLACE INTO mth_student_section (student_id, period_num, schoolYear_id, name)
                              VALUES (
                                ' . $student->getID() . ',
                                ' . $period->num() . ',
                                ' . $schoolYear->getID() . ',
                                "' . core_db::escape($cleanName) . '"
                              )');
        if ($success && ($schedule = mth_schedule::get($student, $schoolYear))
            && ($schedulePeriod = $schedule->getPeriod($period->num()))
            && ($canvasEnrollment = mth_canvas_enrollment::getBySchedulePeriod($schedulePeriod))
        ) {
            return $success && $canvasEnrollment->set_section($cleanName);
        }
        return $success;
    }

    /**
     *
     * @param int $student_id
     * @param int $period_num
     * @param int $schoolYear_id
     * @return mth_student_section
     */
    public static function get($student_id, $period_num, $schoolYear_id)
    {
        if (NULL === ($section = &self::$cache['get'][$student_id][$period_num][$schoolYear_id])) {
            $section = core_db::runGetObject('SELECT * FROM mth_student_section 
                                WHERE student_id=' . (int)$student_id . '
                                  AND period_num=' . (int)$period_num . '
                                  AND schoolYear_id=' . (int)$schoolYear_id, 'mth_student_section');
        }
        return $section;
    }

    public static function getSectionName($student_id, $period_num, $schoolYear_id)
    {
        if (($section = self::get($student_id, $period_num, $schoolYear_id))) {
            return $section->name();
        }
        return '';
    }

    public static function getAllSectionName($period_num, $schoolYear_id)
    {
        $arr = array();
        $result = core_db::runQuery('SELECT student_id, name FROM mth_student_section 
            WHERE period_num=' . (int)$period_num . '
            AND schoolYear_id=' . (int)$schoolYear_id);
        if($result) {
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = $r[1];
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    public static function cache(ARRAY $student_ids, $period_num, $schoolYear_id)
    {
        if (!$student_ids) {
            $student_ids = array(0);
        }
        $result = core_db::runQuery('SELECT * FROM mth_student_section 
                                WHERE student_id IN (' . implode(',', array_map('intval', $student_ids)) . ')
                                  AND period_num=' . (int)$period_num . '
                                  AND schoolYear_id=' . (int)$schoolYear_id, 'mth_student_section');
        while ($section = $result->fetch_object('mth_student_section')) {
            /* @var $section mth_student_section */
            self::$cache['get'][$section->student_id][$section->period_num][$section->schoolYear_id] = $section;
        }
        $result->free_result();
    }
}
