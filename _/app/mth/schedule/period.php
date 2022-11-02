<?php

/**
 * mth_schedule_period
 *
 * @author abe
 */
class mth_schedule_period extends core_model
{
    protected $schedule_period_id;
    protected $schedule_id;
    protected $period;
    protected $second_semester; // mth2ndSem001.sql
    protected $subject_id;
    protected $course_id;
    protected $course_type;
    protected $mth_provider_id;
    protected $tp_name;
    protected $tp_phone;
    protected $tp_website;
    protected $tp_desc;
    protected $custom_desc;
    protected $template_course_description;
    protected $reimbursed; //mth_13.sql
    protected $provider_course_id; //mth_14.sql
    protected $tp_district; //mth_15.sql
    protected $tp_course; //mth_15.sql
    protected $require_change; //mth_16.sql
    protected $changed; //mth_16.sql
    protected $provisional_provider_id;
    protected $allow_above_max_grade_level;
    protected $allow_below_min_grade_level;
    public $saveOtherPeriods = false;

    protected static $cache = array();

    const TYPE_MTH = 1;
    const TYPE_TP = 2;
    const TYPE_CUSTOM = 3;

    protected static $course_type_options = array(
        self::TYPE_MTH => 'My Tech High Direct',
        self::TYPE_TP => '3rd Party Provider',
        self::TYPE_CUSTOM => 'Custom-built'
    );

    public static function course_type_options()
    {
        return self::$course_type_options;
    }

    public static function tp_district_options()
    {
        return mth_packet::getAvailableSchoolDistricts();
    }


    public function id()
    {
        return (int) $this->schedule_period_id;
    }

    /**
     *
     * @return mth_schedule
     */
    public function schedule()
    {
        return mth_schedule::getByID($this->schedule_id);
    }

    /**
     *
     * @return mth_period
     */
    public function period()
    {
        return mth_period::get($this->period);
    }

    public function period_number()
    {
        return $this->period;
    }

    public function second_semester()
    {
        return (bool) $this->second_semester;
    }

    public function allow_above_max_grade_level($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('allow_above_max_grade_level', $set ? 1 : 0);
        }
        return (bool)$this->allow_above_max_grade_level;
    }

    public function allow_below_min_grade_level($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('allow_below_min_grade_level', $set ? 1 : 0);
        }
        return (bool)$this->allow_below_min_grade_level;
    }
    /**
     *
     * @return mth_subject
     */
    public function subject(mth_subject $set = NULL)
    {
        if (!is_null($set)) {
            $this->set('subject_id', $set->getID());
        }
        return mth_subject::getByID($this->subject_id);
    }

    /**
     *
     * @param int|string $set Int subject_id or 'NONE'
     * @return int
     */
    public function subject_id($set = NULL)
    {
        if ($set === 'NONE') {
            $this->none(true);
            return NULL;
        }
        if (!is_null($set) && ($subject = mth_subject::getByID($set))) {
            $this->set('custom_desc', '');
            $this->subject($subject);
        }
        if (($subject = $this->subject())) {
            return $subject->getID();
        }
        return NULL;
    }

    public function subjectName()
    {
        return (string) $this->subject();
    }

    /**
     *
     * @param mth_course $set
     * @return mth_course
     */
    public function course(mth_course $set = NULL)
    {
        if (!is_null($set)) {
            if (!$set->available()) {
                $this->set('course_id', NULL);
                return FALSE;
            }
            $this->set('course_id', $set->getID());
        }
        if ($this->none()) {
            return NULL;
        }
        return mth_course::getByID($this->course_id);
    }

    public function course_id($set = NULL)
    {
        if (!is_null($set) && ($course = mth_course::getByID($set))) {
            $this->course($course);
        }
        if ($this->none()) {
            return NULL;
        }
        if (($course = $this->course())) {
            return $course->getID();
        }
        return NULL;
    }

    public function course_type($returnNum = false, $set = NULL)
    {
        if (!is_null($set) && isset(self::$course_type_options[$set])) {
            $this->set('course_type', (int) $set);
        }
        if ($this->none()) {
            return NULL;
        }
        if ($returnNum || !isset(self::$course_type_options[$this->course_type])) {
            return $this->course_type;
        }
        return self::$course_type_options[$this->course_type];
    }

    public function course_type_custom()
    {
        return $this->course_type(TRUE) == self::TYPE_CUSTOM;
    }

    public function course_type_tp()
    {
        return $this->course_type(TRUE) == self::TYPE_TP;
    }

    public function course_type_mth()
    {
        return $this->course_type(TRUE) == self::TYPE_MTH;
    }

    /**
     *
     * @param mth_provider $set
     * @return mth_provider
     */
    public function mth_provider(mth_provider $set = NULL)
    {
        if (!is_null($set)) {
            $this->set('mth_provider_id', $set->id());
        }
        if (
            $this->none()
            || !$this->course_type_mth()
            || !$this->course()
            || !$this->course()->hasProviders($this->schedule()->student_grade_level())
        ) {
            return NULL;
        }
        return mth_provider::get($this->mth_provider_id);
    }

    public function mth_provider_id($set = NULL)
    {
        if (!is_null($set) && ($provider = mth_provider::get($set))) {
            return $this->mth_provider($provider)->id();
        }
        if (($provider = $this->mth_provider())) {
            return $provider->id();
        }
        if ($set === 0) {
            $this->set('mth_provider_id', NULL);
        }
        return NULL;
    }

    public function getRawProvider($returnId = false)
    {
        if ($returnId) {
            return mth_provider::get($this->mth_provider_id)->id();
        }
        return mth_provider::get($this->mth_provider_id);
    }

    public function getRawProviderCourse()
    {
        return mth_provider_course::getByID($this->provider_course_id);
    }

    public function unset_mth_provider_id()
    {
        $this->set('mth_provider_id', NULL);
    }

    public function unset_course_id()
    {
        $this->set('course_id', NULL);
    }

    public function unset_subject_id()
    {
        $this->set('subject_id', NULL);
    }

    public function unset_course_type()
    {
        $this->set('course_type', NULL);
    }

    public function resetProvisionalProvider()
    {
        $this->provisional_provider_id(0);
        $this->unset_mth_provider_id();
        $this->unset_course_id();
        $this->unset_subject_id();
        $this->unset_course_type();
        $this->save();
    }

    public function provisional_provider_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('provisional_provider_id', $set);
        }
        return $this->provisional_provider_id;
    }

    public function mth_providerName()
    {
        if (($provider = $this->mth_provider())) {
            return $provider->name();
        }
    }

    public function schedule_id()
    {
        return $this->schedule_id;
    }

    public function provider_course(mth_provider_course $set = NULL)
    {
        if (!is_null($set)) {
            $this->set('provider_course_id', $set->id());
        }
        if ($this->none() || !$this->mth_provider()) {
            return NULL;
        }
        return mth_provider_course::getByID($this->provider_course_id);
    }

    public function provider_course_id($set = NULL)
    {
        if (!is_null($set) && ($provider_course = mth_provider_course::getByID($set))) {
            return $this->provider_course($provider_course)->id();
        }
        if (NULL === $set && ($provider_course = $this->provider_course())) {
            return $provider_course->id();
        }
        if (!is_null($set)) {
            $this->set('provider_course_id', $set);
        }

        return NULL;
    }

    public function provider_courseTitle()
    {
        if (($provider_course = $this->provider_course())) {
            return $provider_course->title();
        }
    }

    public function tp_name($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('tp_name', cms_content::sanitizeText($set));
        }
        if (
            $this->none()
            || $this->course_type_custom()
            || $this->provider_course()
        ) {
            return NULL;
        }
        return $this->tp_name;
    }

    public function tp_course($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('tp_course', cms_content::sanitizeText($set));
        }
        if (
            $this->none()
            || $this->course_type_custom()
            || $this->provider_course()
        ) {
            return NULL;
        }
        return $this->tp_course;
    }

    public function tp_phone($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('tp_phone', cms_content::sanitizeText($set));
        }
        if (
            $this->none()
            || $this->course_type_custom()
            || $this->provider_course()
        ) {
            return NULL;
        }
        return $this->tp_phone;
    }

    public function tp_website($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('tp_website', cms_content::sanitizeText($set));
        }
        if ($this->none() || !$this->course_type_tp()) {
            return NULL;
        }
        return $this->tp_website;
    }

    public function tp_desc($set = NULL, $returnHTML = true)
    {
        if (!is_null($set)) {
            $this->set('tp_desc', nl2br(htmlentities(strip_tags($set))));
        }
        if ($this->none() || !$this->course_type_tp()) {
            return NULL;
        }
        return $returnHTML ? $this->tp_desc : strip_tags($this->tp_desc);
    }

    public function tp_district($set = NULL)
    {
        if (!is_null($set) && in_array($set, self::tp_district_options())) {
            $this->set('tp_district', cms_content::sanitizeText($set));
        }
        if (
            $this->none()
            || !$this->course_type_mth()
            || $this->provider_course()
            || !$this->mth_provider()
            || mth_provider_course::count($this->mth_provider(), $this->course())
        ) {
            return NULL;
        }
        if (!$this->tp_district && ($packet = mth_packet::getStudentPacket($this->schedule()->student()))) {
            return $packet->getSchoolDistrict();
        }
        return $this->tp_district;
    }

    public function custom_desc($set = NULL, $returnHTML = true)
    {
        if (!is_null($set) && !$this->none()) {
            $this->set('custom_desc', nl2br(htmlentities(strip_tags($set))));
        }
        if ($this->none() || !$this->course_type_custom()) {
            return NULL;
        }
        return $returnHTML ? $this->custom_desc : strip_tags($this->custom_desc);
    }

    public function template_course_description($set = NULL, $returnHTML = true)
    {
        if (!is_null($set)) {
            $this->set('template_course_description', nl2br(htmlentities(strip_tags($set))));
        }
        if (empty($this->template_course_description)) {
            return '';
        }
        return $returnHTML ? $this->template_course_description : strip_tags($this->template_course_description);
    }

    public function reimbursed($set = NULL, $formatted = true)
    {
        if (!is_null($set)) {
            $this->set('reimbursed', preg_replace('/[^0-9\.]/', '', $set));
        }
        if ($this->none()) {
            return NULL;
        }
        return $formatted ? number_format($this->reimbursed, 2) : (float) $this->reimbursed;
    }

    public function allow_2nd_sem_change()
    {
        if (!($schedule = $this->schedule()) || !($schYear = $schedule->schoolYear())) {
            return false;
        }
        if ((core_user::isUserAdmin() || $this->schedule()->isToChange()) && $schYear->getSecondSemOpen() <= time()) {
            return $this->second_sem_change_available();
        }
        return false;
    }

    public function second_sem_change_available()
    {
        return ($this->schedule()->student_grade_level() > 8
            && (($this->course() && $this->course()->allow_2nd_sem_change($this->period))
                || ($this->subject() && $this->subject()->allow_2nd_sem_change($this->period))))
            || ($this->provider_course()
                && $this->provider_course()->provider()
                && $this->provider_course()->provider()->allow_2nd_sem_change());
    }

    /**
     *
     * @param int $schedule_period_id
     * @return mth_schedule_period
     */
    public static function getByID($schedule_period_id)
    {
        $schedulePeriod = &self::cache(__CLASS__, (int) $schedule_period_id);
        if (!isset($schedulePeriod)) {
            $schedulePeriod = core_db::runGetObject(
                'SELECT * FROM mth_schedule_period 
                                              WHERE schedule_period_id=' . (int) $schedule_period_id,
                'mth_schedule_period'
            );
        }
        return $schedulePeriod;
    }

    /**
     *
     * @param int $school_year_id
     * @return mth_schedule_period
     */
    public static function getSparkCourses($semester)
    {
        $current_school_year = mth_schoolYear::getCurrent();
        $current_year_id = $current_school_year->getID();

        $accept_status = mth_schedule::STATUS_ACCEPTED;
        $schedulePeriod = core_db::runGetObjects(
            "SELECT course.spark_course_id AS subject_spark_course, provider.spark_course_id AS provider_spark_course, person.*, provider.*, per.*   FROM `mth_schedule_period` per
            LEFT JOIN `mth_provider_course` provider ON per.provider_course_id = provider.provider_course_id
            LEFT JOIN `mth_schedule` sch ON per.schedule_id = sch.schedule_id
            LEFT JOIN `mth_student` student ON student.student_id = sch.student_id
            LEFT JOIN `mth_person` person ON student.person_id = person.person_id
            LEFT JOIN `mth_course` course ON per.course_id = course.course_id
            WHERE sch.status = $accept_status AND school_year_id = $current_year_id AND second_semester = $semester"
        );
        return $schedulePeriod;
    }

    /**
     *
     * @param int $school_year_id
     * @return mth_schedule_period
     */
    public static function getSparkProviderCourses($school_year_id, $sem_type)
    {
        $accept_status = mth_schedule::STATUS_ACCEPTED;
        $schedulePeriod = core_db::runGetObjects(
            "SELECT student.parent_id as parent_id, course.spark_course_id AS subject_spark_course, provider.spark_course_id AS provider_spark_course, person.*, provider.*, per.*   FROM
            --  `mth_schedule_period` per
                (
                    SELECT * FROM `mth_schedule_period`  GROUP BY PERIOD, schedule_id , second_semester ORDER BY schedule_period_id ASC
                ) AS per
            LEFT JOIN `mth_provider_course` provider ON per.provider_course_id = provider.provider_course_id
            LEFT JOIN `mth_schedule` sch ON per.schedule_id = sch.schedule_id
            LEFT JOIN `mth_student` student ON student.student_id = sch.student_id
            LEFT JOIN `mth_person` person ON student.person_id = person.person_id
            LEFT JOIN `mth_course` course ON per.course_id = course.course_id
            WHERE sch.status = $accept_status AND school_year_id = $school_year_id AND second_semester = ($sem_type - 1) AND (course.spark_course_id <> '' OR provider.spark_course_id <> '' )"
        );
        return $schedulePeriod;
    }

    /**
     *
     * @param int $school_year_id
     * @return mth_schedule_period
     */
    public static function checkSparkCourse($person_id, $period, $sem_type)
    {
        $current_school_year = mth_schoolYear::getCurrent();
        $current_year_id = $current_school_year->getID();

        $schedulePeriod = core_db::runGetObject(
            "SELECT sch.status as status, student.parent_id as parent_id, course.spark_course_id AS subject_spark_course, provider.spark_course_id AS provider_spark_course, person.*, provider.*, per.*   FROM
            --  `mth_schedule_period` per
                (
                    SELECT * FROM `mth_schedule_period`  GROUP BY PERIOD, schedule_id , second_semester ORDER BY schedule_period_id ASC
                ) AS per
            LEFT JOIN `mth_provider_course` provider ON per.provider_course_id = provider.provider_course_id
            LEFT JOIN `mth_schedule` sch ON per.schedule_id = sch.schedule_id
            LEFT JOIN `mth_student` student ON student.student_id = sch.student_id
            LEFT JOIN `mth_person` person ON student.person_id = person.person_id
            LEFT JOIN `mth_course` course ON per.course_id = course.course_id
            WHERE sch.status != 99 AND sch.status != 66 AND school_year_id = $current_year_id AND person.person_id = $person_id AND per.period = $period AND second_semester = $sem_type"
        );
        return $schedulePeriod;
    }

  

    /**
     *
     * @param int $school_year_id
     * @return mth_schedule_period
     */
    public static function getSemesterCourses($school_year_id, $sem_type)
    {
        $accept_status = mth_schedule::STATUS_ACCEPTED;
        $schedulePeriod = core_db::runGetObjects(
            "SELECT person.*, provider.*, per.*  FROM `mth_schedule_period` per
            LEFT JOIN `mth_provider_course` provider ON per.provider_course_id = provider.provider_course_id
            LEFT JOIN `mth_schedule` sch ON per.schedule_id = sch.schedule_id
            LEFT JOIN `mth_student` student ON student.student_id = sch.student_id
            LEFT JOIN `mth_person` person ON student.person_id = person.person_id
            WHERE sch.status = $accept_status AND school_year_id = $school_year_id AND second_semester = ($sem_type - 1)
            "
        );
        return $schedulePeriod;
    }



    /**
     *
     * @param array $schedule_period_ids
     */
    public static function cacheSchedulePeriods(array $schedule_period_ids)
    {
        $result = core_db::runQuery('SELECT * FROM mth_schedule_period 
                                              WHERE schedule_period_id IN (' . implode(',', array_map('intval', $schedule_period_ids)) . ')');
        while ($schedulePeriod = $result->fetch_object(mth_schedule_period::class)) {
            /** @var mth_schedule_period $schedulePeriod */
            $schedulePeriodCached = &self::cache(__CLASS__, $schedulePeriod->id());
            $schedulePeriodCached = $schedulePeriod;
        }
        $result->free_result();
    }

    /**
     *
     * @param mth_schedule $schedule
     * @param mth_period $period
     * @param bool $second_semester
     * @return mth_schedule_period
     */
    public static function get(mth_schedule $schedule, mth_period $period, $second_semester = false)
    {
        $schedulePeriod = &self::$cache['get'][$schedule->id()][$period->num()][$second_semester];
        if (!isset($schedulePeriod)) {
            $schedulePeriod = core_db::runGetObject(
                'SELECT * FROM mth_schedule_period 
                                              WHERE schedule_id=' . $schedule->id() . ' 
                                                AND `period`=' . $period->num() . '
                                              ORDER BY second_semester ' . ($second_semester ? 'DESC' : 'ASC') . ' 
                                              LIMIT 1',
                'mth_schedule_period'
            );
        }
        return $schedulePeriod;
    }

    /**
     *
     * @param mth_schedule $schedule
     * @param bool $reset
     * @return mth_schedule_period
     */
    public static function each(mth_schedule $schedule, $reset = FALSE, $variation = NULL)
    {
        $result = &self::$cache['each'][$schedule->id()][$variation];
        if (!isset($result)) {
            $result = core_db::runQuery('SELECT * FROM mth_schedule_period 
                                    WHERE schedule_id=' . $schedule->id() . '
                                    ORDER BY `period` ASC, second_semester ASC');
        }
        if ($reset) {
            unset($result); //remove cached $result
            return NULL;
        }
        if (($schedulePeriod = $result->fetch_object('mth_schedule_period'))) {
            return $schedulePeriod;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    /**
     * Check if period has no changes from 1st and 2nd sem
     * @return boolean
     */
    public function noChanges()
    {
        $first_sem = $this->second_semester() ? $this->schedule()->getPeriod($this->period_number(), false) : $this;
        $second_sem = $this->second_semester() ? $this : $this->schedule()->getPeriod($this->period_number(), true);
        return $first_sem &&
            $second_sem &&
            !mth_schedule_period::isDifferentSemSet($first_sem, $second_sem);
    }

    public static function isDifferentSemSet($first_sem, $second_sem)
    {
        return ($first_sem->course_id() != $second_sem->course_id())
            || ($first_sem->provider_course_id() != $second_sem->provider_course_id())
            || ($first_sem->mth_provider_id() != $second_sem->mth_provider_id())
            || ($first_sem->subject_id() != $second_sem->subject_id())
            || ($first_sem->course_type() != $second_sem->course_type());
    }

    /**
     * Get All student schedule period by provider ids
     * @param mth_schedule $schedule
     * @param array $provider_ids
     * @param boolean $reset
     * @return mth_schedule_period
     */
    public static function eachByProvider(mth_schedule $schedule, array $provider_ids = null, $reset = FALSE)
    {

        $result = &self::$cache['eachByProvider'][$schedule->id()];
        if (!isset($result)) {
            $sql = "select * from mth_schedule_period where 
                mth_provider_id " . ($provider_ids ? "in(" . (implode(',', $provider_ids)) . ")" : "is not null") .
                " and schedule_id={$schedule->id()} ORDER BY `period` ASC, second_semester ASC";

            $result = core_db::runQuery($sql);
        }
        if ($reset) {
            unset($result); //remove cached $result
            return NULL;
        }
        if (($schedulePeriod = $result->fetch_object('mth_schedule_period'))) {
            return $schedulePeriod;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    /**
     *
     * @param mth_course $course
     * @param type $reset
     * @return mth_schedule_period
     */
    public static function eachWithCourse(mth_course $course, mth_schoolYear $year = NULL, array $scheduleStatuses = NULL, $reset = FALSE)
    {
        $result = &self::cache(__CLASS__, 'each-course-' . $course->getID() . '-' . $year . '-' . serialize($scheduleStatuses));
        if (!isset($result)) {
            if (empty($scheduleStatuses)) {
                $scheduleStatuses = mth_schedule::accepted_statuses();
            }
            if (!$year && !($year = mth_schoolYear::getCurrent())) {
                return NULL;
            }
            $result = core_db::runQuery('SELECT sp.* FROM mth_schedule_period AS sp
                                      LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                    WHERE sp.course_id=' . $course->getID() . '
                                      AND s.status IN (' . implode(',', array_map('intval', $scheduleStatuses)) . ')
                                      AND s.school_year_id=' . $year->getID() . '
                                      AND sp.course_type=' . self::TYPE_MTH . '
                                      AND (sp.custom_desc IS NULL OR sp.custom_desc!="[:NONE:]")
                                    ORDER BY sp.`period` ASC');
        }
        if (!$reset && ($schedulePeriod = $result->fetch_object('mth_schedule_period'))) {
            return $schedulePeriod;
        }
        $result->data_seek(0);
        return NULL;
    }

    /**
     *
     * @param mth_course $course
     * @param type $reset
     * @return mth_schedule_period
     */
    public static function eachWithCourseStudent(mth_course $course, mth_schoolYear $year = NULL, array $student_status = NULL, $reset = FALSE, $midyear = FALSE)
    {
        $result = &self::cache(__CLASS__, 'eachWithCourseStudent-' . $course->getID() . '-' . $year . '-' . serialize($student_status));
        if (!isset($result)) {

            if (empty($student_status)) {
                $student_status = [mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING];
            }
            $student_status = implode(',', array_map('intval', $student_status));
            if (!$year && !($year = mth_schoolYear::getCurrent())) {
                return NULL;
            }
            $getMidyearApplications = $midyear ? ' inner join mth_application as ma on ma.student_id=mss.student_id ' : '';
            $getMidyear = $midyear ? ' AND ma.midyear_application = 1 AND ma.school_year_id=' . $year->getID() : '';

            $result = core_db::runQuery('SELECT sp.* FROM mth_schedule_period AS sp
                                      LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                    WHERE sp.course_id=' . $course->getID() . '
                                      AND s.school_year_id=' . $year->getID() . '
                                      AND sp.course_type=' . self::TYPE_MTH . '
                                      AND (sp.custom_desc IS NULL OR sp.custom_desc!="[:NONE:]")
                                      AND s.student_id in (select mss.student_id from mth_student_status as mss
                                         ' . $getMidyearApplications . '
                                          where mss.school_year_id=' . $year->getID() . ' and mss.`status`  IN (' . $student_status . ') '
                . $getMidyear . ')
                                    ORDER BY sp.`period` ASC');
        }
        if (!$reset && ($schedulePeriod = $result->fetch_object('mth_schedule_period'))) {
            return $schedulePeriod;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function countWithCourse(mth_course $course, mth_schoolYear $year = NULL, array $scheduleStatuses = NULL)
    {
        $result = &self::cache(__CLASS__, 'each-course-' . $course->getID() . '-' . $year . '-' . serialize($scheduleStatuses));
        if (!isset($result)) {
            self::eachWithCourse($course, $year, $scheduleStatuses, TRUE);
        }
        return $result->num_rows;
    }

    public static function allWithProviderCourse(mth_provider_course $course, mth_schoolYear $year = NULL, array $scheduleStatuses = NULL, $overrideGradeLimits = false)
    {
        $arr = &self::cache(__CLASS__, 'allWithProviderCourse-' . $course->id() . '-' . $year . '-' . serialize($scheduleStatuses));

        if (!isset($arr)) {

            if (empty($scheduleStatuses)) {
                $scheduleStatuses = mth_schedule::accepted_statuses();
            }
            if (!$year && !($year = mth_schoolYear::getCurrent())) {
                return array();
            }

            $query = 'SELECT sp.* FROM mth_schedule_period AS sp
                                        LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                      WHERE sp.provider_course_id=' . $course->id() . '
                                        AND s.status IN (' . implode(',', array_map('intval', $scheduleStatuses)) . ')
                                        AND s.school_year_id=' . $year->getID() . '
                                        AND sp.course_type=' . self::TYPE_MTH . '
                                        AND sp.mth_provider_id=' . $course->provider_id() . '
                                        AND (sp.custom_desc IS NULL OR sp.custom_desc!="[:NONE:]")
                                      ORDER BY sp.`period` ASC';
            $arr = core_db::runGetObjects($query, 'mth_schedule_period');
            foreach ($arr as $num => $schedulePeriod) {
                /* @var $schedulePeriod mth_schedule_period */

                if ($schedulePeriod->provider_course_id() != $course->id() && !$overrideGradeLimits) {
                    unset($arr[$num]);
                }
            }
            sort($arr);
        }
        return $arr;
    }

    /**
     *
     * @param mth_provider_course $course
     * @param type $reset
     * @return mth_schedule_period
     */
    public static function eachWithProviderCourse(mth_provider_course $course, mth_schoolYear $year = NULL, array $scheduleStatuses = NULL, $reset = FALSE, $overrideGradeLevel = false)
    {
        $num = &self::cache(__CLASS__, 'eachWithProviderCourse-' . $course->id() . '-' . $year . '-' . serialize($scheduleStatuses));
        if (!isset($num)) {
            $num = 0;
        }
        $arr = self::allWithProviderCourse($course, $year, $scheduleStatuses, $overrideGradeLevel);
        if (!$reset && isset($arr[$num]) && ($schedulePeriod = $arr[$num])) {
            $num++;
            return $schedulePeriod;
        } else {
            $num = 0;
            return NULL;
        }
    }

    public static function countWithProviderCourse(mth_provider_course $course, mth_schoolYear $year = NULL, array $scheduleStatuses = NULL, $overrideGradeLimits = false)
    {
        return count(self::allWithProviderCourse($course, $year, $scheduleStatuses, $overrideGradeLimits));
    }

    /**
     *
     * @param mth_provider $provider
     * @param mth_schoolYear $year
     * @param array $scheduleStatuses
     * @return array of mth_schedule_period objects
     */
    public static function allWithProvider(mth_provider $provider, mth_schoolYear $year = NULL, array $scheduleStatuses = NULL, $overrideGradeLevel = false)
    {
        $arr = &self::cache(__CLASS__, 'allWithProvider-' . $provider->id() . '-' . $year . '-' . serialize($scheduleStatuses));
        if (!isset($arr)) {

            if (empty($scheduleStatuses)) {
                $scheduleStatuses = mth_schedule::accepted_statuses();
            }
            $scheduleStatuses = implode(',', array_map('intval', $scheduleStatuses));
            if (!$year && !($year = mth_schoolYear::getCurrent())) {
                return array();
            }

            $query = 'SELECT sp.* FROM mth_schedule_period AS sp
                                        LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                      WHERE sp.mth_provider_id=' . $provider->id() . '
                                        AND sp.course_type=' . self::TYPE_MTH . '
                                        AND s.school_year_id=' . $year->getID() . '
                                        AND s.status IN (' . $scheduleStatuses . ')
                                        AND (sp.custom_desc IS NULL OR sp.custom_desc!="[:NONE:]")
                                      ORDER BY sp.`period` ASC';

            $arr = core_db::runGetObjects($query, 'mth_schedule_period');

            foreach ($arr as $num => $schedulePeriod) {
                /* @var $schedulePeriod mth_schedule_period */
                if ($schedulePeriod->mth_provider_id() != $provider->id() && !$overrideGradeLevel) { //This is required becuase the database may contain values which are not being used
                    unset($arr[$num]);
                }
            }
            sort($arr);
        }
        return $arr;
    }

    /**
     *
     * @param mth_provider $provider
     * @param mth_schoolYear $year
     * @param array $student_status
     * @return array of mth_schedule_period objects
     */
    public static function allWithProviderAndStudent(mth_provider $provider, mth_schoolYear $year = NULL, array $student_status = NULL, $midyear = false, $overrideGradeLevel = false)
    {
        $arr = &self::cache(__CLASS__, 'allWithProviderAndStudent-' . $provider->id() . '-' . $year . '-' . serialize($student_status));
        if (!isset($arr)) {

            if (empty($student_status)) {
                $student_status = [mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING];
            }

            $student_status = implode(',', array_map('intval', $student_status));
            if (!$year && !($year = mth_schoolYear::getCurrent())) {
                return array();
            }
            $getMidyearApplications = $midyear ? ' inner join mth_application as ma on ma.student_id=mss.student_id ' : '';
            $getMidyear = $midyear ? ' AND ma.midyear_application = 1 AND ma.school_year_id=' . $year->getID() : '';

            $arr = core_db::runGetObjects('SELECT sp.* FROM mth_schedule_period AS sp
                                        LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                        inner join mth_student as ms on s.student_id=ms.student_id
                                      WHERE sp.mth_provider_id=' . $provider->id() . '
                                        AND sp.course_type=' . self::TYPE_MTH . '
                                        AND s.school_year_id=' . $year->getID() . '
                                        AND (sp.custom_desc IS NULL OR sp.custom_desc!="[:NONE:]")
                                        AND s.student_id in (select mss.student_id from mth_student_status as mss
                                         ' . $getMidyearApplications . '
                                         where mss.school_year_id=' . $year->getID() . ' and mss.`status`  IN (' . $student_status . ') ' . $getMidyear . ')
                                      ORDER BY sp.`period` ASC', 'mth_schedule_period');

            foreach ($arr as $num => $schedulePeriod) {
                /* @var $schedulePeriod mth_schedule_period */
                if ($schedulePeriod->mth_provider_id() != $provider->id() && !$overrideGradeLevel) { //This is required becuase the database may contain values which are not being used
                    unset($arr[$num]);
                }
            }
            sort($arr);
        }
        return $arr;
    }

    /**
     *
     * @param mth_provider $provider
     * @param mth_schoolYear $year
     * @param array $scheduleStatuses
     * @param bool $reset
     * @return mth_schedule_period
     */
    public static function eachWithProvider(mth_provider $provider, mth_schoolYear $year = NULL, array $scheduleStatuses = NULL, $reset = FALSE, $overrideGradeLevel = false)
    {
        $num = &self::cache(__CLASS__, 'eachWithProvider-' . $provider->id() . '-' . $year . '-' . serialize($scheduleStatuses));
        if (!isset($num)) {
            $num = 0;
        }
        $arr = self::allWithProvider($provider, $year, $scheduleStatuses, $overrideGradeLevel);
        if (!$reset && isset($arr[$num]) && ($schedulePeriod = $arr[$num])) {
            $num++;
            return $schedulePeriod;
        } else {
            $num = 0;
            return NULL;
        }
    }

    public static function allWithProviders($provider = [], mth_schoolYear $year = NULL, array $scheduleStatuses = NULL, $overrideGradeLevel = false)
    {
        $arr = &self::cache(__CLASS__, 'allWithProviders-' . $year . '-' . serialize($scheduleStatuses));
        if (!isset($arr)) {

            if (empty($scheduleStatuses)) {
                $scheduleStatuses = mth_schedule::accepted_statuses();
            }
            $scheduleStatuses = implode(',', array_map('intval', $scheduleStatuses));
            if (!$year && !($year = mth_schoolYear::getCurrent())) {
                return array();
            }

            $query = 'SELECT sp.* FROM mth_schedule_period AS sp
                                        LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                      WHERE sp.mth_provider_id IN (' . implode(',', $provider) . ')
                                        AND sp.course_type=' . self::TYPE_MTH . '
                                        AND s.school_year_id=' . $year->getID() . '
                                        AND s.status IN (' . $scheduleStatuses . ')
                                        AND (sp.custom_desc IS NULL OR sp.custom_desc!="[:NONE:]")
                                      ORDER BY sp.`period` ASC';

            $arr = core_db::runGetObjects($query, 'mth_schedule_period');

            sort($arr);
        }
        return $arr;
    }

    /**
     *
     * @param mth_provider $provider
     * @param mth_schoolYear $year
     * @param array $student_status
     * @param bool $reset
     * @return mth_schedule_period
     */
    public static function eachWithProviderStudent(mth_provider $provider, mth_schoolYear $year = NULL, array $student_status = NULL, $reset = FALSE, $midyear = false, $overrideGradeLevel = false)
    {
        $num = &self::cache(__CLASS__, 'eachWithProviderStudent-' . $provider->id() . '-' . $year . '-' . serialize($student_status));
        if (!isset($num)) {
            $num = 0;
        }
        $arr = self::allWithProviderAndStudent($provider, $year, $student_status, $midyear, $overrideGradeLevel);
        if (!$reset && isset($arr[$num]) && ($schedulePeriod = $arr[$num])) {
            $num++;
            return $schedulePeriod;
        } else {
            $num = 0;
            return NULL;
        }
    }

    /**
     *
     * @param mth_schedule $schedule
     * @param mth_period $period
     * @return mth_schedule_period
     */
    public static function create(mth_schedule $schedule, mth_period $period, $second_semester = false)
    {
        if (($schedulePeriod = self::get($schedule, $period, $second_semester))
            && (!$second_semester || $schedulePeriod->second_semester())
        ) {
            return $schedulePeriod;
        }
        if ($second_semester && (!($original = self::get($schedule, $period)) || !$original->allow_2nd_sem_change())) {
            return false;
        } elseif (!$second_semester && !$schedule->editable()) {
            return false;
        }
        core_db::runQuery('INSERT INTO mth_schedule_period (schedule_id,`period`,second_semester) 
                        VALUES (' . $schedule->id() . ',' . $period->num() . ',' . (int) $second_semester . ')');
        if (($schedulePeriod = self::getByID(core_db::getInsertID()))) {
            $schedulePeriod->setDefault();
            if ($second_semester) {
                $schedulePeriod->require_change(true);
            }
        }
        return $schedulePeriod;
    }

    /**
     * @param bool $markAsChanged
     * @return \mth_schedule_period
     */
    public function duplicateTo2ndSem($markAsChanged = false)
    {
        if (!($secondSemPeriod = self::create($this->schedule(), $this->period(), true))) {
            return false;
        }
        $newID = $secondSemPeriod->id();
        $secondSemPeriod = clone $this;
        $secondSemPeriod->schedule_period_id = $newID;
        $secondSemPeriod->second_semester = 1;
        if ($markAsChanged) {
            $secondSemPeriod->changed = date('Y-m-d H:i:s', time() + 10);
        }
        $secondSemPeriod->populateUpdateQueriesArr('mth_schedule_period', array());
        $secondSemPeriod->require_change(true);
        $secondSemPeriod->save();
        return $secondSemPeriod;
    }

    public function validate()
    {
        if (!$this->editable()) {
            return true;
        }

        if ($this->require_change()) {
            return FALSE;
        }
        if (
            empty($this->subject_id)
            && $this->period()
            && !$this->period()->required()
            && $this->none()
        ) {
            return true;
        }
        if (!$this->validateSubject() || !$this->validateCourse() || !$this->validateCourseType()) {
            return false;
        }
        switch ($this->course_type(true)) {
            case self::TYPE_MTH:
                return $this->validateMTHProviderCourse();

            case self::TYPE_TP:
                return $this->validateTPProvider();

            case self::TYPE_CUSTOM:
                return $this->validateCustom();

            default:
                return false;
        }
    }

    public function validateSubject()
    {
        if (!$this->period() || !$this->subject()) {
            return false;
        }
        $availableSubjects = mth_subject::getAll($this->period());
        return isset($availableSubjects[$this->subject_id]);
    }

    public function validateCourse()
    {
        if (!$this->period() || !$this->subject() || !$this->schedule() || !$this->schedule()->student()) {
            return false;
        }
        $availableCourses = mth_course::getAll($this->subject(), $this->schedule()->student_grade_level());
        if (empty($availableCourses)) {
            $this->set('course_id', '');
        }
        return !empty($availableCourses)
            ? isset($availableCourses[$this->course_id()])
            : !$this->course();
    }

    public function validateCourseType()
    {
        if (!$this->subject() || !$this->schedule() || !$this->schedule()->student()) {
            return false;
        }
        if ($this->course()) {
            if ($this->course() && $this->course()->requireTP($this->schedule()->student_grade_level())) {
                $this->course_type(true, self::TYPE_TP);
                return true;
            }
            if ($this->course() && $this->course()->requireDesc($this->schedule()->student_grade_level())) {
                $this->course_type(true, self::TYPE_CUSTOM);
                return true;
            }
        }
        if (!$this->subject()->showProviders()) {
            $this->course_type(TRUE, self::TYPE_MTH);
            return true;
        }
        return (true && $this->course_type(true));
    }

    public function validateTPProvider()
    {
        return ($this->tp_name() && $this->tp_course() && $this->tp_phone() && $this->tp_website());
    }

    public function validateCustom()
    {
        return (true && $this->custom_desc());
    }

    public function validateMTHProvider()
    {
        if (!$this->subject() || !$this->course_type(true) || !$this->schedule() || !$this->schedule()->student()) {
            return false;
        }
        if ($this->course_type(true) != self::TYPE_MTH || !$this->subject()->showProviders()) {
            return true;
        }
        if (!$this->mth_provider()) {
            return false;
        }
        while ($provider = mth_provider::each($this->schedule()->student_grade_level(), $this->course())) {
            if ($provider == $this->mth_provider()) {
                mth_provider::each($this->schedule()->student_grade_level(), $this->course(), false, true);
                return true;
            }
        }
        return FALSE;
    }

    public function validateMTHProviderCourse()
    {
        if (!$this->validateMTHProvider()) {
            return FALSE;
        }
        if (!$this->subject()->showProviders()) {
            return TRUE;
        }
        if (!($provider_courses = mth_provider_course::all($this->mth_provider(), $this->course()))) {
            $this->set('provider_course_id', NULL);
            return ($this->tp_name() && $this->tp_phone() && $this->tp_course() && $this->tp_district());
        }
        if (!$this->provider_course() || !in_array($this->provider_course(), $provider_courses)) {
            return false;
        }
        return TRUE;
    }

    public function hasDefault()
    {
        if (!$this->period() || mth_subject::getCount($this->period()) != 1) {
            return false;
        }
        mth_subject::getEach($this->period(), true);
        $subject = mth_subject::getEach($this->period());
        mth_subject::getEach($this->period(), true);
        return $this->schedule()
            && $this->schedule()->student()
            && ($subject)
            && !$subject->showProviders()
            && mth_course::getCount($subject, $this->schedule()->student_grade_level()) == 1;
    }

    public function setDefault()
    {
        if ($this->hasDefault()) {
            mth_subject::getEach($this->period(), true);
            $subject = mth_subject::getEach($this->period());
            mth_subject::getEach($this->period(), true);
            $this->subject($subject);
            $this->course_type(true, self::TYPE_MTH);
            $this->course(mth_course::getEach($subject, $this->schedule()->student_grade_level()));
        }
    }

    public function courseName()
    {
        if ($this->none()) {
            return 'None';
        }
        if (!($subject = $this->subject()) || (!($course = $this->course()) && !$this->course_type(true))) {
            return 'Not Specified';
        } elseif (!$course) {
            return 'Other';
        } else {
            return $course->title();
        }
    }

    public function __toString()
    {
        if ($this->none()) {
            return 'None';
        }
        $courseName = $this->courseName();
        if ($this->course_type()) {
            return $courseName . ' - ' . $this->course_type();
        }
        return $courseName;
    }

    public function save()
    {
        $updated = false;
        if (!empty($this->updateQueries) && !isset($this->updateQueries['require_change'])) {
            if ($this->saveOtherPeriods) {
                // To prevent infinite loop, set explicitly before applicable saves
                $this->saveOtherPeriods = false;
                $provider = mth_provider::get($this->mth_provider_id());

                if ($provider && $provider->requiresMultiplePeriods()) {
                    $this->saveRelatedPeriods($provider);
                    $this->provisional_provider_id($this->mth_provider_id());
                }
            }

            $this->set('changed', date('Y-m-d H:i:s'));
            $this->schedule()->updated();
            $updated = true;
        }
        $success = parent::runUpdateQuery('mth_schedule_period', 'schedule_period_id=' . $this->id());
        if ($success && $updated && $this->schedule()->isAccepted() && core_user::isUserAdmin()) {
            mth_canvas_enrollment::updateCanvasStudentEnrollments($this->schedule()->student(), $this->schedule()->schoolYear());
            //disable auto observer
            //mth_canvas_enrollment::updateCanvasParentEnrollments($this->schedule()->student()->getParent(), $this->schedule()->schoolYear());
        }
        return $success;
    }

    private function saveRelatedPeriods($provider)
    {
        $periods = $this->schedule()->allPeriods();
        foreach ($periods as $period) {
            if (
                !in_array($period->period, $provider->multiplePeriods()) ||
                $period->period == $this->period
            ) {
                continue;
            }

            if ($period->subject()) {
                $relatedCourses = mth_course::getAll($period->subject(), $this->schedule()->student_grade_level(), false, $this->mth_provider_id());
                if (
                    !(in_array($period->course_id(), array_keys($relatedCourses))) ||
                    $period->mth_provider_id() != $this->mth_provider_id()
                ) {
                    $period->unset_course_type();
                    $period->unset_mth_provider_id();
                }
            }

            $period->provisional_provider_id($this->mth_provider_id());
            $period->save();
        }
    }

    public function __destruct()
    {
        $this->save();
    }

    /**
     *
     * @param bool $set_require_change require or remove requirement to change this schedule period
     * @return bool
     */
    public function require_change($set_require_change = NULL)
    {
        if (!is_null($set_require_change) && $this->schedule()) {
            if ($set_require_change) {
                $this->set('require_change', date('Y-m-d H:i:s'));
                $this->schedule()->setStatus($this->schedule()->isAccepted()
                    ? mth_schedule::STATUS_CHANGE_POST
                    : mth_schedule::STATUS_CHANGE);
            } elseif ($set_require_change === FALSE) {
                $this->set('require_change', NULL);
            }
        }
        return $this->require_change && $this->require_change > $this->changed;
    }

    public function unlock_period()
    {
        if ($this->schedule()) {
            $this->set('require_change', date('Y-m-d H:i:s'));
            $this->schedule()->setStatus($this->schedule()->isAccepted()
                ? mth_schedule::STATUS_CHANGE_POST
                : mth_schedule::STATUS_STARTED);
        }
    }

    public function require_change_pending($set_require_change = NULL)
    {
        if (!is_null($set_require_change) && $this->schedule()) {
            if ($set_require_change) {
                $this->set('require_change', date('Y-m-d H:i:s'));
                $this->schedule()->setStatus(mth_schedule::STATUS_CHANGE_PENDING);
            } elseif ($set_require_change === FALSE) {
                $this->set('require_change', NULL);
            }
        }
        return $this->require_change && $this->require_change > $this->changed;
    }

    public function require_change_date($format = NULL)
    {
        return self::getDate($this->require_change, $format);
    }

    public function changed($format = NULL)
    {
        return self::getDate($this->changed, $format);
    }

    public function changedLast()
    {
        return $this->require_change_date() && $this->changed() > $this->require_change_date();
    }

    public function editable()
    {
        if (core_user::isUserAdmin()) {
            return true;
        }
        return $this->schedule()
            && $this->schedule()->student()
            && $this->schedule()->schoolYear()
            && $this->schedule()->student()->canSubmitSchedule($this->schedule()->schoolYear())
            && ($this->schedule()->editable()
                || ($this->schedule()->isToChange()
                    && $this->require_change_date()));
    }

    public function none($set = NULL)
    {
        if (!is_null($set)) {
            if ($set && $this->period()->required()) {
                return false;
            }
            if ($set) {
                $this->set('custom_desc', $set ? '[:NONE:]' : '');
            }

            if ($set && $this->subject_id) {
                $this->set('subject_id', NULL);
            }
        }
        return $this->custom_desc === '[:NONE:]' && !$this->period()->required();
    }

    protected function set($field, $value, $force = false)
    {
        if ($field == 'subject_id' && $value && !$force) {
            $this->none(false);
        }
        parent::set($field, $value, $force);
    }

    public function delete()
    {
        error_log('mth_schedule_period->delete() executed: ' . print_r(debug_backtrace(), true));
    }

    public static function resetPeriodForDiplomaSeeking($schedule_id, $diploma_seeking = 0)
    {
        $diploma_valid =  $diploma_seeking ? 0 : 1;
        if ($diploma_seeking) {
            core_db::runQuery('UPDATE mth_schedule_period AS msp
                        LEFT JOIN mth_provider AS mp ON msp.mth_provider_id = mp.provider_id
                        LEFT JOIN mth_provider_course AS mpc ON msp.provider_course_id = mpc.provider_course_id
                        SET msp.course_type = NULL, msp.mth_provider_id = NULL 
                        WHERE ( mpc.diploma_only=' . $diploma_valid . ' AND mp.diploma_only=' . $diploma_valid . ' AND mp.diploma_valid=0 OR msp.course_type !=1 ) AND msp.schedule_id=' . $schedule_id);

            core_db::runQuery('UPDATE mth_schedule_period AS msp
                        LEFT JOIN mth_course AS mc ON msp.course_id = mc.course_id 
                        SET msp.subject_id = NULL, msp.course_id = NULL, msp.course_type = NULL, msp.mth_provider_id = NULL 
                        WHERE mc.diploma_valid=' . $diploma_valid . ' AND msp.schedule_id=' . $schedule_id);
        } else {
            core_db::runQuery('UPDATE mth_schedule_period AS msp
                        LEFT JOIN mth_provider AS mp ON msp.mth_provider_id = mp.provider_id
                        LEFT JOIN mth_provider_course AS mpc ON msp.provider_course_id = mpc.provider_course_id
                        SET msp.course_type = NULL, msp.mth_provider_id = NULL 
                        WHERE ( mpc.diploma_only=' . $diploma_valid . ' OR mp.diploma_only=' . $diploma_valid . ') AND msp.schedule_id=' . $schedule_id);
        }
        return true;
    }
}
