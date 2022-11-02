<?php

/**
 * For storing enrollment data needed to interact with canvas
 *
 * @author abe
 */
class mth_canvas_enrollment extends core_model
{
    protected $canvas_enrollment_id;
    protected $canvas_user_id;
    protected $canvas_course_id;
    protected $canvas_section_id;
    protected $role;
    protected $status;
    protected $grade;
    protected $grade_updated;
    protected $zero_count;
    protected $zero_count_updated;
    protected $last_activity_at;
    protected $late_count;

    protected $runSaveUpdates = false;

    /** @var  mth_person */
    protected $observing_mth_person;

    const GRADE_EXP = '3 days'; //used in strtotime();
    const GRADE_EXP_EMPTY = '1 day';

    protected static $cache = array();

    const ROLE_STUDENT = 1;
    const ROLE_OBSERVER = 2;
    const ROLE_STUDENT_VIEW = 3;
    const ROLE_OBSERVER_VIEW = 4;

    protected static $roleCanvasLabels = array(
        self::ROLE_STUDENT => 'StudentEnrollment',
        self::ROLE_OBSERVER => 'ObserverEnrollment',
        self::ROLE_STUDENT_VIEW => 'StudentViewEnrollment',
        self::ROLE_OBSERVER_VIEW => 'ObserverViewEnrollment'
    );
    protected static $roleLabels = array(
        self::ROLE_STUDENT => 'Student',
        self::ROLE_OBSERVER => 'Observer',
        self::ROLE_STUDENT_VIEW => 'Student View',
        self::ROLE_OBSERVER_VIEW => 'Observer View'
    );

    public static function roleID($role)
    {
        if (self::ROLE_STUDENT == $role || self::ROLE_OBSERVER == $role) {
            return (int) $role;
        }
        if (($roleid = array_search($role, self::$roleCanvasLabels))) {
            return $roleid;
        }
        return NULL;
    }

    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_INVITED = 3;

    protected static $statusLabels = array(
        self::STATUS_DELETED => 'deleted',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_COMPLETED => 'completed',
        self::STATUS_INVITED => 'invited'
    );

    public static function statusID($status)
    {
        if (($statusID = array_search($status, self::$statusLabels))) {
            return $statusID;
        }
        if (isset(self::$statusLabels[$status])) {
            return (int) $status;
        }
        return self::STATUS_ACTIVE;
    }

    public function id()
    {
        return (int) $this->canvas_enrollment_id;
    }

    public function canvas_user_id()
    {
        return (int) $this->canvas_user_id;
    }

    /**
     *
     * @return mth_canvas_user
     */
    public function canvas_user()
    {
        return mth_canvas_user::getByID($this->canvas_user_id);
    }

    public function canvas_course_id()
    {
        return (int) $this->canvas_course_id;
    }

    /**
     *
     * @return mth_canvas_course
     */
    public function canvas_course()
    {
        return mth_canvas_course::getByID($this->canvas_course_id);
    }

    /**
     *
     * @return boolean
     */
    public function canvas_course_isAvailable()
    {
        if (($canvas_course = $this->canvas_course())) {
            return $canvas_course->isAvailable();
        }
        return false;
    }

    public function canvas_section_id()
    {
        return (int) $this->canvas_section_id;
    }

    public function status($returnNumber = false)
    {
        if ($returnNumber || !isset(self::$statusLabels[$this->status])) {
            return (int) $this->status;
        }
        return self::$statusLabels[$this->status];
    }

    public function isActive()
    {
        return $this->status(true) == self::STATUS_ACTIVE;
    }

    public function isStudent()
    {
        return $this->role == self::ROLE_STUDENT;
    }

    public function set_section($sectionName)
    {
        $newSection = mth_canvas_section::create($this->canvas_course(), $sectionName);
        if ($newSection->canvas_section_id() == $this->canvas_section_id()) {
            return true;
        }
        unset(self::$cache['getByEnrollmentID'][$this->canvas_enrollment_id]); // to make sure $this and $oldEnrollment are different objects
        $oldEnrollment = self::getByEnrollmentID($this->canvas_enrollment_id);
        $this->canvas_enrollment_id = NULL;
        $this->canvas_section_id = $newSection->canvas_section_id();
        return $this->create() && $oldEnrollment->delete(false, false);
    }

    public static function pull(mth_schoolYear $year = NULL, $quitAfterSeconds = 28)
    {
        $endTime = time() + $quitAfterSeconds;
        $completedCourses = &$_SESSION[core_config::getCoreSessionVar()][__CLASS__]['pull-completed'];
        if (!isset($completedCourses)) {
            $completedCourses = array();
        }
        mth_canvas_course::each($year, true);
        while ($course = mth_canvas_course::each($year)) {
            if (in_array($course->id(), $completedCourses)) {
                continue;
            }
            if (!self::pull_course_enrollments($course)) {
                return FALSE;
            }
            $completedCourses[] = $course->id();
            if (time() >= $endTime) {
                return NULL;
            }
        }
        $_SESSION[core_config::getCoreSessionVar()][__CLASS__]['pull-completed'] = array();
        return TRUE;
    }

    public static function pull_from_course($canvas_course_id, $year = NULL, $quitAfterSeconds = 28)
    {


        $clean = core_db::runQuery('DELETE FROM mth_canvas_enrollment WHERE canvas_course_id=' . $canvas_course_id);

        if (!$clean) {
            error_log('Unable to delete canvas enrollments from canvas course' . $canvas_course_id);
            return FALSE;
        }
        mth_canvas_course::eachAndHomeroom($year, true, "homeroom_canvas_course_id=$canvas_course_id");
        $course = mth_canvas_course::eachAndHomeroom($year, false, "homeroom_canvas_course_id=$canvas_course_id");

        if (!self::pull_course_enrollments($course)) {
            return FALSE;
        }

        return TRUE;
    }
    /**
     * Update Home Room Zero count
     *
     * @param mth_schoolYear $year
     * @param integer $quitAfterSeconds
     * @return void
     */
    public static function updateHomeRoomZeros(mth_schoolYear $year = NULL, $quitAfterSeconds = 28)
    {

        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }

        $endTime = time() + $quitAfterSeconds;
        $completedCourses = &$_SESSION[__CLASS__]['completed-homeroom-course'];
        if (!isset($completedCourses)) {
            $completedCourses = array();
        }
        mth_canvas_course::eachAndHomeroom($year, true);
        while ($course = mth_canvas_course::eachAndHomeroom($year)) {
            if (in_array($course->id(), $completedCourses)) {
                continue;
            }
            if (!self::pull_course_student_summaries($course)) {
                return FALSE;
            }

            $completedCourses[] = $course->id();
            if (time() >= $endTime) {
                return NULL;
            }
        }

        $_SESSION[__CLASS__]['completed-homeroom-course'] = array();


        return TRUE;
    }

    /**
     * Save Homeroom zero for speecific canvas_course_id with canva_course user
     *
     * @param array $result
     * @param int $canvas_course_id
     * @return void
     */
    public static function save_homerom_zero($result, $canvas_course_id)
    {

        foreach ($result as $summary) {
            if (!$summary->id) {
                continue;
            }

            $Qs = [
                "zero_count={$summary->tardiness_breakdown->missing}",
                "zero_count_updated = '" . date('Y-m-d H:i:s') . "'",
                "late_count={$summary->tardiness_breakdown->late}"
            ];
            $sql = "UPDATE mth_canvas_enrollment SET " . implode(',', $Qs) . " where 
            canvas_user_id={$summary->id} and canvas_course_id=$canvas_course_id";
            core_db::runQuery($sql);
        }
    }

    public static function pullWithHomeRoom(mth_schoolYear $year = NULL, $quitAfterSeconds = 28)
    {
        $endTime = time() + $quitAfterSeconds;
        $completedCourses = &$_SESSION[core_config::getCoreSessionVar()][__CLASS__]['pullwith-completed'];
        if (!isset($completedCourses)) {
            $completedCourses = array();
        }
        mth_canvas_course::eachAndHomeroom($year, true);
        while ($course = mth_canvas_course::eachAndHomeroom($year)) {
            if (in_array($course->id(), $completedCourses)) {
                continue;
            }
            if (!self::pull_course_enrollments($course)) {
                return FALSE;
            }
            $completedCourses[] = $course->id();
            if (time() >= $endTime) {
                return NULL;
            }
        }
        $_SESSION[core_config::getCoreSessionVar()][__CLASS__]['pullwith-completed'] = array();
        return TRUE;
    }
    /**
     * Pull Student summaries Involvement,Activity Tardiness etc..
     * from course
     * @param mth_canvas_course $course
     * @return bool
     */
    public static function pull_course_student_summaries(mth_canvas_course $course)
    {
        $command = "/courses/" . $course->canvas_course_id() . "/analytics/student_summaries?per_page=50&page=";
        $page = 1;
        while ($result = mth_canvas::exec($command . $page)) {
            if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return FALSE;
            }
            if (count($result) == 0) {
                break;
            }
            self::save_homerom_zero($result, $course->canvas_course_id());
            $page++;
        }
        return TRUE;
    }

    public static function pull_course_enrollments(mth_canvas_course $course)
    {
        $command = '/courses/' . $course->canvas_course_id() . '/enrollments?include[]=grades&per_page=50&page=';
        $page = 1;
        while ($result = mth_canvas::exec($command . $page)) {
            if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return FALSE;
            }
            if (count($result) == 0) {
                break;
            }
            if (!self::save_results_array($result)) {
                error_log('Unable to save canvas enrollments to database');
                return FALSE;
            }
            $page++;
        }
        return TRUE;
    }

    /**
     * Single Pull Course ENrollment
     * @param int $canvas_course_id
     * @param int $page
     * @return array
     */
    public static function single_pull_course_enrollments($canvas_course_id, $page)
    {
        $command = '/courses/' . $canvas_course_id . '/enrollments?include[]=grades&per_page=50&page=';
        $count = 0;
        if ($result = mth_canvas::exec($command . $page)) {
            if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return [
                    'error' => TRUE,
                    'result' => $count
                ];
            }

            $count = count($result);

            if (!self::save_results_array($result)) {
                error_log('Unable to save canvas enrollments to database');
                return [
                    'error' => TRUE,
                    'result' => $count
                ];
            }
        }

        return [
            'error' => FALSE,
            'result' => $count
        ];
    }

    protected static function save_results_array($result)
    {
        $Qs = array();

        foreach ($result as $enrollmentObj) {
            if (!self::roleID($enrollmentObj->role)) {
                continue;
            }

            $grade = array_key_exists('grades', $enrollmentObj) && $enrollmentObj->grades->current_score != null ? ($enrollmentObj->grades->current_score) : "NULL";
            $grade_updated = $grade != "NULL" ? ("'" . date('Y-m-d H:i:s') . "'") : "NULL";
            $last_activity_at = $enrollmentObj->last_activity_at ? ("'" . (date('Y-m-d H:i:s', strtotime($enrollmentObj->last_activity_at))) . "'") : "NULL";
            $Qs[] = '(' . implode(',', array(
                $enrollmentObj->id,
                $enrollmentObj->user_id,
                $enrollmentObj->course_id,
                $enrollmentObj->course_section_id,
                self::roleID($enrollmentObj->role),
                self::statusID($enrollmentObj->enrollment_state),
                $grade,
                $grade_updated,
                $last_activity_at
            )) . ')';
        }
        if (empty($Qs)) {
            return true;
        }

        return core_db::runQuery('REPLACE INTO mth_canvas_enrollment 
                                (canvas_enrollment_id, canvas_user_id, canvas_course_id, canvas_section_id, role, status, grade,grade_updated,last_activity_at)
                                VALUES ' . implode(',', $Qs));
    }

    public static function cacheID(mth_schoolYear &$year = NULL, $alreadyCreated = NULL)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }
        return $year->getID() . '-' . (is_null($alreadyCreated) ? 'all' : $alreadyCreated ? 'exsisting' : 'new');
    }

    /**
     *
     * @param int $canvas_course_id
     * @param bool $reset
     * @return mth_canvas_enrollment
     */
    public static function eachCanvasCourseEnrollment($canvas_course_id, $reset = false)
    {
        if (NULL === ($result = &self::$cache['eachCanvasCourseEnrollment'][$canvas_course_id])) {
            $result = core_db::runQuery('SELECT * FROM mth_canvas_enrollment WHERE canvas_course_id=' . $canvas_course_id);
        }
        if (!$reset && ($enrollment = $result->fetch_object('mth_canvas_enrollment'))) {
            return $enrollment;
        }
        $result->data_seek(0);
        return NULL;
    }

    /**
     * get Each Students Canvas Homeroom Enrollment
     *
     * @param mth_schoolYear $year
     * @param array $courses
     * @param int $grade specify grade less or equal
     * @param int $excluded_id specify which enrollment_id to be excluded
     * @return mth_canvas_enrollment
     */
    public static function eachCanvasHomeRoomEnrollment(mth_schoolYear $year = NULL, $courses = [], $grade = null, $excluded_id = [])
    {
        if (NULL === ($result = &self::$cache['eachCanvasHomeRoomEnrollment'][$year->getID()])) {
            $result = core_db::runQuery("select * from mth_canvas_enrollment where 1
            " . self::_getGradeStatement($grade) . "
            and role  = " . self::ROLE_STUDENT . "
            and status!= " . self::STATUS_DELETED . "
            and (canvas_course_id in(" . implode(',', $courses) . ") 
                    or 
                    canvas_course_id in(
                        select homeroom_canvas_course_id from mth_student_homeroom 
                        where school_year_id=" . $year->getID() . " group by homeroom_canvas_course_id
                    )
                )
                " . (count($excluded_id) > 0 ? ("and canvas_enrollment_id not in(" . implode(',', $excluded_id) . ")") : '') . "
            ");
        }

        if (($enrollment = $result->fetch_object('mth_canvas_enrollment'))) {
            return $enrollment;
        }
        $result->data_seek(0);
        return NULL;
    }

    /**
     * get Each Students Canvas Homeroom Enrollment
     * @deprecated eachCanvasHomeRoomEnrollment
     *
     * @param mth_schoolYear $year
     * @param int $grade specify grade less or equal
     * @param int $excluded_id specify which enrollment_id to be excluded
     * @return mth_canvas_enrollment
     */
    public static function eachHomeRoomEnrollment(mth_schoolYear $year = NULL, $grade = null, $excluded_id = [])
    {
        if (NULL === ($result = &self::$cache['eachHomeRoomEnrollment'][$year->getID()])) {
            $result = core_db::runQuery("select * from mth_canvas_enrollment where 1
            " . self::_getGradeStatement($grade) . "
            and role  = " . self::ROLE_STUDENT . "
            and status!= " . self::STATUS_DELETED . "
            and canvas_course_id in(
                        select homeroom_canvas_course_id from mth_student_homeroom 
                        where school_year_id=" . $year->getID() . " group by homeroom_canvas_course_id
            )
                " . (count($excluded_id) > 0 ? ("and canvas_enrollment_id not in(" . implode(',', $excluded_id) . ")") : '') . "
            ");
        }

        if (($enrollment = $result->fetch_object('mth_canvas_enrollment'))) {
            return $enrollment;
        }
        $result->data_seek(0);
        return NULL;
    }

    /**
     * Get Grade SQl statement
     *
     * @param int $grade
     * @return string
     */
    private static function _getGradeStatement($grade = null)
    {
        if (is_null($grade)) {
            return '';
        }
        if ($grade < 100) {
            return "and (grade is null or grade <= $grade)";
        }
        return "and grade=$grade";
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param bool $alreadyCreated can be TRUE, FALSE, or NULL (NULL will return all)
     * @param bool $reset
     * @return mth_canvas_enrollment
     */
    public static function each(mth_schoolYear $year = NULL, $alreadyCreated = false, $reset = false)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }
        $num = &self::$cache['each-num'][$year->getID()][(is_null($alreadyCreated) ? 'all' : $alreadyCreated ? 'exsisting' : 'new')];

        if (!isset($num)) {
            $num = 0;
        }
        $allEnrollments = self::getAll($year, $alreadyCreated);

        if (
            !$reset && isset($allEnrollments[$num])
            && ($canvas_enrollment = $allEnrollments[$num])
        ) {
            /* @var $canvas_enrollment mth_canvas_enrollment */
            $num++;
            return $canvas_enrollment;
        } else {
            $num = 0;
            return NULL;
        }
    }

    protected static function getAll(mth_schoolYear $year = NULL, $alreadyCreated = false)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }
        $getAll = &self::$cache['getAll'][$year->getID()][(is_null($alreadyCreated) ? 'all' : $alreadyCreated ? 'exsisting' : 'new')];
        if (!isset($getAll)) {
            $getAll = array();
            while ($schedule = mth_schedule::eachOfYear($year)) {
                self::addSchedulePeriods($schedule, $getAll, $alreadyCreated);
            }
        }
        return $getAll;
    }

    protected static function addSchedulePeriods(mth_schedule $schedule, &$getAll, $alreadyCreated = false)
    {
        $schedule->eachPeriod(true);
        while ($schedulePeriod = $schedule->eachPeriod()) {
            if (($canvas_enrollment = self::getBySchedulePeriod($schedulePeriod, self::ROLE_STUDENT))
                && !($alreadyCreated === TRUE && !$canvas_enrollment->id())
                && !($alreadyCreated === FALSE && $canvas_enrollment->id())
                && !in_array($canvas_enrollment, $getAll)
            ) {
                $getAll[] = $canvas_enrollment;
            }
            // if (($canvas_enrollment = self::getBySchedulePeriod($schedulePeriod, self::ROLE_OBSERVER))
            //     && !($alreadyCreated === TRUE && !$canvas_enrollment->id())
            //     && !($alreadyCreated === FALSE && $canvas_enrollment->id())
            //     && !in_array($canvas_enrollment, $getAll)
            // ) {
            //     $getAll[] = $canvas_enrollment;
            // }
        }
    }

    public static function count(mth_schoolYear $year = NULL, $alreadyCreated = false, $forceUpdate = false)
    {
        $count = core_setting::get('count-' . self::cacheID($year, $alreadyCreated), __CLASS__);

        if (!$count || $count->getDateChanged() < time() - (30 * 60) || $forceUpdate) {
            $count = core_setting::set(
                'count-' . self::cacheID($year, $alreadyCreated),
                count(self::getAll($year, $alreadyCreated)),
                core_setting::TYPE_INT,
                __CLASS__
            );
        }
        return $count->getValue();
    }

    public function create($createIfCourseNotAvailable = false)
    {
        if ($this->id()) {
            return true;
        }
        if (!$createIfCourseNotAvailable && !$this->canvas_course_isAvailable()) {
            return true;
        }
        if ($this->canvas_section_id) {
            $command = '/sections/' . $this->canvas_section_id . '/enrollments';
        } else {
            $command = '/courses/' . $this->canvas_course_id . '/enrollments';
        }
        $enrolObj = mth_canvas::exec(
            $command,
            array(
                'enrollment[user_id]' => $this->canvas_user_id(),
                'enrollment[type]' => self::$roleCanvasLabels[$this->role],
                'enrollment[enrollment_state]' => $this->status ? $this->status() : self::$statusLabels[self::STATUS_ACTIVE]
                //disable notification
                //,'enrollment[notify]' => true
            )
        );
        if ($enrolObj && is_object($enrolObj) && isset($enrolObj->id)) {
            core_log::log('Successfully created enrollment ' . $enrolObj->id . ', canvas_user_id: ' . $this->canvas_user_id() . ', canvas_course_id: ' . $this->canvas_course_id(), __CLASS__);
            $this->canvas_enrollment_id = $enrolObj->id;
            return self::save_results_array(array($enrolObj));
        } elseif ($enrolObj) {
            mth_canvas_error::log(
                (isset($enrolObj->message) ? $enrolObj->message : 'Unexpected response'),
                '/courses/' . $this->canvas_course_id() . '/enrollments',
                $enrolObj
            );
        }
        return false;
    }

    public function delete($localRecordOnly = true, $markAsConcluded = false)
    {
        if (!$this->id()) {
            return false;
        }
        if ($localRecordOnly) {
            return core_db::runQuery('DELETE FROM mth_canvas_enrollment WHERE canvas_enrollment_id=' . $this->id());
        }

        core_log::log('Deleting enrollment canvas_user_id: ' . $this->canvas_user_id() . ', canvas_course_id: ' . $this->canvas_course_id(), __CLASS__);
        $enrolObj = mth_canvas::exec(
            '/courses/' . $this->canvas_course_id() . '/enrollments/' . $this->id(),
            array(
                'task' => $markAsConcluded ? 'conclude' : 'delete'
            ),
            mth_canvas::METHOD_DELETE
        );
        if (is_object($enrolObj) && $enrolObj->id && $markAsConcluded) {
            return self::save_results_array(array($enrolObj));
        } else {
            return core_db::runQuery('DELETE FROM mth_canvas_enrollment WHERE canvas_enrollment_id=' . $this->id());
        }
    }

    /**
     * Deletes only the local records of the enrollments
     * @param mth_canvas_user $canvas_user
     * @return bool
     */
    public static function deleteUserEnrollmentRecords(mth_canvas_user $canvas_user)
    {
        return core_db::runQuery('DELETE FROM mth_canvas_enrollment WHERE canvas_user_id=' . $canvas_user->id());
    }

    /**
     *
     * @param mth_schedule $schedule
     * @param int $role
     * @return array Array of mth_canvas_enrollment objects
     */
    public static function getScheduleEnrollments(mth_schedule $schedule, $role = self::ROLE_STUDENT)
    {
        $enrollments = &self::$cache['getScheduleEnrollments'][$schedule->id()][$role];
        if (!isset($enrollments)) {
            $schedule->eachPeriod(true);
            $r = array();
            while ($schedulePeriod = $schedule->eachPeriod()) {
                $r[] = self::getBySchedulePeriod($schedulePeriod, $role);
            }
            $enrollments = array_values(array_filter($r));
        }
        return $enrollments;
    }

    /**
     *
     * @param mth_person $person
     * @param mth_schoolYear $year
     * @return array Array of mth_canvas_enrollment objects
     */
    public static function getPersonEnrollments(mth_person $person, mth_schoolYear $year = NULL)
    {
        if (!($user = mth_canvas_user::get($person))) {
            return array();
        }
        return self::getUserEnrollments($user, $year);
    }

    /**
     *
     * @param mth_person $person
     * @param mth_schoolYear $year
     * @param bool $reset
     * @return mth_canvas_enrollment
     */
    public static function eachPersonEnrollment(mth_person $person, mth_schoolYear $year = NULL, $reset = false)
    {
        if (!($user = mth_canvas_user::get($person))) {
            return NULL;
        }
        return self::eachUserEnrollments($user, $year, $reset);
    }

    /**
     *
     * @param mth_canvas_user $user
     * @param mth_schoolYear $year
     * @return array Array of mth_canvas_enrollment objects
     */
    public static function getUserEnrollments(mth_canvas_user $user, mth_schoolYear $year = NULL)
    {
        $enrollments = &self::$cache['getUserEnrollments'][$user->id()][$year ? $year->getID() : NULL];
        if (!isset($enrollments)) {
            $courseIDs = mth_canvas_course::getIDs($year);
            $enrollments = array();
            if ($courseIDs) {
                $enrollments = core_db::runGetObjects(
                    'SELECT * FROM mth_canvas_enrollment 
                                        WHERE canvas_user_id=' . $user->id() . ' 
                                          AND canvas_course_id IN (' . implode(',', $courseIDs) . ')',
                    __CLASS__
                );
            }
        }
        return $enrollments;
    }

    /**
     *
     * @param mth_canvas_user $user
     * @param mth_schoolYear $year
     * @param bool $reset
     * @return mth_canvas_enrollment
     */
    public static function eachUserEnrollments(mth_canvas_user $user, mth_schoolYear $year = NULL, $reset = false)
    {
        $onNum = &self::$cache['eachUserEnrollments'][$user->id()][$year ? $year->getID() : NULL];
        if (!isset($onNum)) {
            $onNum = 0;
        }
        $enrollments = self::getUserEnrollments($user, $year);
        if (!$reset && isset($enrollments[$onNum]) && ($enrollment = $enrollments[$onNum])) {
            $onNum++;
            return $enrollment;
        } else {
            $onNum = 0;
            return NULL;
        }
    }

    public static function updateCanvasStudentEnrollments(mth_student $student, mth_schoolYear $year = NULL)
    {
        if (!($schedule = mth_schedule::get($student, $year))) {
            if (mth_purchasedCourse::hasPurchasedCourse($student->getParent())) {
                return;
            }
            while ($enrollment = self::eachPersonEnrollment($student, $year)) {
                $enrollment->delete(false);
            }
        } else {
            $hasEnrollments = self::schduleHasActiveEnrollments($schedule);
            $scheduleEnrollments = self::getScheduleEnrollments($schedule);
            $scheduleEnrollmentsIds = array_column($scheduleEnrollments, 'canvas_enrollment_id');
            while ($enrollment = self::eachPersonEnrollment($student, $year)) {
                if (!in_array($enrollment->id(), $scheduleEnrollmentsIds)) {
                    $enrollment->delete(false);
                }
            }
            if ($hasEnrollments) {
                self::createScheduleEnrollments($schedule);
            }
        }
    }

    public static function updateCanvasParentEnrollments(mth_parent $parent, mth_schoolYear $year = NULL)
    {
        $scheduleEnrollments = array();
        $parent->eachStudent(true);
        while ($student = $parent->eachStudent()) {
            if (!($schedule = mth_schedule::get($student, $year))) {
                continue;
            }

            if (self::schduleHasActiveEnrollments($schedule, self::ROLE_OBSERVER)) {
                self::createScheduleEnrollments($schedule);
            }
            self::eachScheduleEnrollment($schedule, self::ROLE_OBSERVER, true);
            while ($enrollment = self::eachScheduleEnrollment($schedule, self::ROLE_OBSERVER)) {
                $scheduleEnrollments[] = $enrollment->canvas_course_id();
            }
        }
        while ($enrollment = self::eachPersonEnrollment($parent, $year)) {
            if (mth_purchasedCourse::hasPurchasedCourse($parent)) {
                return;
            }
            if (!in_array($enrollment->canvas_course_id(), $scheduleEnrollments)) {
                $enrollment->delete(false);
            }
        }
    }

    /**
     *
     * @param mth_schedule $schedule
     * @param int $role
     * @param bool $reset
     * @return mth_canvas_enrollment
     */
    public static function eachScheduleEnrollment(mth_schedule $schedule, $role = self::ROLE_STUDENT, $reset = false)
    {
        $onNum = &self::$cache['eachScheduleEnrollment'][$schedule->id()][$role];
        if (!isset($onNum)) {
            $onNum = 0;
        }
        $enrollments = self::getScheduleEnrollments($schedule, $role);
        if (!$reset && isset($enrollments[$onNum]) && ($enrollment = $enrollments[$onNum])) {
            $onNum++;
            return $enrollment;
        } else {
            $onNum = 0;
            return NULL;
        }
    }

    public static function schduleHasActiveEnrollments(mth_schedule $schedule, $role = self::ROLE_STUDENT)
    {
        self::eachScheduleEnrollment($schedule, $role, true);
        while ($enrollment = self::eachScheduleEnrollment($schedule, $role)) {
            if ($enrollment->isActive()) {
                self::eachScheduleEnrollment($schedule, $role, true);
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param mth_canvas_user $canvas_user
     * @param mth_canvas_course $canvas_course
     * @return mth_canvas_enrollment
     */
    public static function get(mth_canvas_user $canvas_user, mth_canvas_course $canvas_course, $sectionName = NULL, $type = null)
    {
        if (!$canvas_user->id()) {
            return NULL;
        }

        $enrollment = &self::$cache['get'][$canvas_user->id()][$canvas_course->id()];
        if (!isset($enrollment)) {
            $enrollment = core_db::runGetObject(
                sprintf(
                    'SELECT * FROM mth_canvas_enrollment WHERE canvas_user_id=%d AND canvas_course_id=%d',
                    $canvas_user->id(),
                    $canvas_course->id()
                ),
                'mth_canvas_enrollment'
            );
            if (!$enrollment) {
                $enrollment = self::getNew($canvas_user, $canvas_course, $sectionName, $type);
            }
        }
        return $enrollment;
    }

    /**
     *
     * @param int $canvas_enrollment_id
     * @return mth_canvas_enrollment
     */
    public static function getByEnrollmentID($canvas_enrollment_id)
    {
        if (NULL === ($enrollment = &self::$cache['getByEnrollmentID'][$canvas_enrollment_id])) {
            $enrollment = core_db::runGetObject(
                'SELECT * FROM mth_canvas_enrollment 
                                            WHERE canvas_enrollment_id=' . (int) $canvas_enrollment_id,
                'mth_canvas_enrollment'
            );
        }
        return $enrollment;
    }

    protected static function getNew(mth_canvas_user $canvas_user, mth_canvas_course $canvas_course, $sectionName = NULL, $type = null)
    {
        $enrollment = new self();
        $enrollment->canvas_course_id = $canvas_course->id();
        $enrollment->canvas_user_id = $canvas_user->id();
        $enrollment->role = $type ? $type : ($canvas_user->person()->getType() == 'parent' ? self::ROLE_OBSERVER : self::ROLE_STUDENT);
        $enrollment->status = self::STATUS_ACTIVE;
        if ($sectionName) {
            $section = mth_canvas_section::create($canvas_course, $sectionName);
            $enrollment->canvas_section_id = $section->canvas_section_id();
        }
        return $enrollment;
    }

    /**
     *
     * @param mth_schedule_period $schedulePeriod
     * @param int $role
     * @param bool $createAccount create canvas accounts if non-esistant
     * @param bool $forceRefresh do force the cached object to be refreshed
     * @return mth_canvas_enrollment
     */
    public static function getBySchedulePeriod(mth_schedule_period $schedulePeriod, $role = self::ROLE_STUDENT, $createAccount = false, $forceRefresh = false)
    {
        $enrollment = &self::$cache['getBySchedulePeriod'][$schedulePeriod->id()][$role];
        $person = null;
        $student = null;
        if ($enrollment === NULL || $forceRefresh) {
            if (
                !$schedulePeriod->course_type_mth()
                || !$schedulePeriod->course()
                || !($student = $schedulePeriod->schedule()->student())
                || !($parent = $student->getParent())
                || !($person = $role == self::ROLE_STUDENT ? $student : $parent)
            ) {
                return NULL;
            }
            if ($createAccount) {
                mth_canvas_user::createCanvasAccounts($student, false);
            }
            $enrollment = core_db::runGetObject(
                sprintf(
                    'SELECT ce.* 
                        FROM mth_canvas_enrollment AS ce
                          INNER JOIN mth_canvas_user AS cu ON cu.canvas_user_id=ce.canvas_user_id
                          INNER JOIN mth_canvas_course AS cc ON cc.canvas_course_id=ce.canvas_course_id
                        WHERE cu.mth_person_id=%d
                          AND cc.mth_course_id=%d
                          AND cc.school_year_id=%d',
                    $person->getPersonID(),
                    $schedulePeriod->course()->getID(),
                    $schedulePeriod->schedule()->schoolYearID()
                ),
                'mth_canvas_enrollment'
            );
        }
        if (!$enrollment) {
            if ($schedulePeriod->period_number() == 1 && $student) {
                $hm = new \mth\student\HomeroomManager($schedulePeriod->schedule()->schoolYear());
                $canvas_course = mth_canvas_course::getByID($hm->getStudentHomeroomCourseId($student->getID()), false);
            } else {
                $canvas_course = mth_canvas_course::getBySchedulePeriod($schedulePeriod);
            }
            if (
                !$canvas_course
                || !($canvas_user = mth_canvas_user::get($person))
                || !$canvas_course->id()
            ) {
                return NULL;
            }
            $enrollment = self::get(
                $canvas_user,
                $canvas_course,
                mth_student_section::getSectionName(
                    $schedulePeriod->schedule()->student_id(),
                    $schedulePeriod->period()->num(),
                    $schedulePeriod->schedule()->schoolYearID()
                )
            );
        }
        return $enrollment;
    }

    public static function createScheduleEnrollments(mth_schedule $schedule, $createIfCourseNotAvailable = false)
    {
        $schedule->eachPeriod(true);
        $success = array();
        while ($schedulePeriod = $schedule->eachPeriod()) {
            if (($canvasEnrollment = self::getBySchedulePeriod($schedulePeriod, self::ROLE_STUDENT, false, true))) {
                $success[] = $canvasEnrollment->create($createIfCourseNotAvailable);
            }
            // if (($canvasEnrollment = self::getBySchedulePeriod($schedulePeriod, self::ROLE_OBSERVER, false, true))) {
            //     $success[] = $canvasEnrollment->create($createIfCourseNotAvailable);
            // }
        }
        return count($success) == count(array_filter($success));
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param boolean $createIfCourseNotAvailable
     * @param int $quitAfterSeconds
     * @return boolean|null TRUE on success, FALSE if there are errors, NULL on timeout
     */
    public static function createAllEnrollments(mth_schoolYear $year = NULL, $createIfCourseNotAvailable = false, $quitAfterSeconds = 28)
    {
        $endTime = time() + $quitAfterSeconds;
        $success = array();
        while ($enrollment = self::each($year)) {
            $success[] = $enrollment->create($createIfCourseNotAvailable);
            if (time() >= $endTime) {
                return count($success) == count(array_filter($success)) ? NULL : FALSE;
            }
        }
        return count($success) == count(array_filter($success));
    }

    /**
     * Will create create enrollments for schedules with the specified course.
     * @param mth_course $course
     * @param int $role
     * @param mth_schoolYear $year
     * @param boolean $createIfCourseNotAvailable
     * @param int $quitAfterSeconds
     * @return boolean|null TRUE on success, FALSE if there are errors, NULL on timeout
     */
    public static function createCourseEnrollments(mth_course $course, $role = self::ROLE_STUDENT, mth_schoolYear $year = NULL, $createIfCourseNotAvailable = false, $quitAfterSeconds = 28)
    {
        $endTime = time() + $quitAfterSeconds;
        $setting = 'created_enrollments_' . $course->getID();
        $completed = core_setting::get($setting, self::class);
        if ($completed) {
            $completed = unserialize($completed);
        } else {
            $completed = [self::ROLE_STUDENT => [], self::ROLE_OBSERVER => []];
        }
        $success = array();
        mth_schedule_period::eachWithCourse($course, $year, array(mth_schedule::STATUS_ACCEPTED), true);
        while ($schedulePeriod = mth_schedule_period::eachWithCourse($course, $year, array(mth_schedule::STATUS_ACCEPTED))) {
            if (in_array($schedulePeriod->id(), $completed[$role])) {
                continue;
            }
            //note that createAccount param here is true meaning it will sync to canvas user account
            if (!($enrollment = self::getBySchedulePeriod($schedulePeriod, $role, true))) {
                continue;
            }
            $success[] = $enrollment->create($createIfCourseNotAvailable);
            // if($role==self::ROLE_OBSERVER
            //     && ($schedule = $schedulePeriod->schedule())
            //     && ($student = $schedule->student())
            //     && ($studentAccount = mth_canvas_user::get($student))
            // ){
            //     $enrollment->canvas_user()->addObservee($studentAccount);
            // }
            $completed[$role][] = $schedulePeriod->id();
            core_setting::set($setting, serialize($completed), core_setting::TYPE_RAW, self::class);
            if (time() >= $endTime) {
                return count($success) == count(array_filter($success)) ? NULL : FALSE;
            }
        }
        $completed = [self::ROLE_STUDENT => [], self::ROLE_OBSERVER => []];
        core_setting::set($setting, serialize($completed), core_setting::TYPE_RAW, self::class);
        return count($success) == count(array_filter($success));
    }

    /**
     * A singleton process of create canvas course enrollment
     * @param int $schedule_period_id
     * @param int $role
     * @param boolean $createIfCourseNotAvailable
     * @return boolean
     */
    public static function createCourseEnrollment($schedule_period_id, $role = self::ROLE_STUDENT, $createIfCourseNotAvailable = false)
    {
        if ($schedulePeriod = mth_schedule_period::getByID($schedule_period_id)) {

            if (!($enrollment = self::getBySchedulePeriod($schedulePeriod, $role, true))) {
                return true; //skip if no enrollment found
            }

            $response = $enrollment->create($createIfCourseNotAvailable);
            // if($role==self::ROLE_OBSERVER
            //     && ($schedule = $schedulePeriod->schedule())
            //     && ($student = $schedule->student())
            //     && ($studentAccount = mth_canvas_user::get($student))
            // ){
            //     $enrollment->canvas_user()->addObservee($studentAccount);
            // }
            return $response;
        }
        return true; //skip when no schedule period found
    }

    /**
     * Get Schedule period IDS from target Course
     * @param mth_course $course
     * @param mth_schoolYear $year
     * @return array
     */
    public static function getSchedulePeriodIdsFromCourse(mth_course $course, mth_schoolYear $year = NULL, $stauses = [mth_schedule::STATUS_ACCEPTED])
    {
        $schedule_periods = [];
        while ($schedulePeriod = mth_schedule_period::eachWithCourse($course, $year, $stauses)) {
            $schedule_periods[] = $schedulePeriod->id();
        }

        return $schedule_periods;
    }

    public static function flush()
    {
        return core_db::runQuery('DELETE FROM mth_canvas_enrollment WHERE 1');
    }

    /**
     *
     * @return stdClass|bool
     */
    public function getJsonObj()
    {
        if (($jsonObj = &self::$cache['getJsonObj'][$this->canvas_enrollment_id]) === NULL) {
            if (!$this->id()) {
                return $jsonObj = false;
            }
            $jsonObj = mth_canvas::exec('/accounts/' . mth_canvas::account_id() . '/enrollments/' . $this->id());
        }
        return $jsonObj;
    }

    protected function getGradeFromCanvas()
    {
        if (($jsonObj = $this->getJsonObj())
            && isset($jsonObj->grades)
        ) {
            return $jsonObj->grades->current_score;
        }
        return null;
    }

    public function gradeNeedsToBeRefreshed()
    {
        return is_null($this->grade_updated)
            || ($this->grade && strtotime(self::GRADE_EXP, strtotime($this->grade_updated)) < time())
            || (!$this->grade && strtotime(self::GRADE_EXP_EMPTY, strtotime($this->grade_updated)) < time());
    }

    public function grade($forceRefresh = false)
    {
        if (!$this->canvas_enrollment_id) {
            return NULL;
        }
        if ($this->gradeNeedsToBeRefreshed() || $forceRefresh) {
            $this->set('grade', $this->getGradeFromCanvas());
            $this->set('grade_updated', date('Y-m-d H:i:s'));
            $this->runSaveUpdates = true;
        }
        return $this->grade;
    }

    public function comments()
    {
        if (!$this->canvas_enrollment_id) {
            return NULL;
        }
        $comments = [];
        foreach ($this->getSubmissionByUser() as $submission) {
            if (!empty($submission->submission_comments)) {
                foreach ($submission->submission_comments as $comment) {
                    $comments[] = [
                        'comment' => $comment->comment,
                        'date' => date('m/d/Y  h:i A', strtotime($comment->created_at))
                    ];
                }
            }
        }
        return $comments;
    }

    public function submissions()
    {
        return $this->getSubmissionByUser();
    }
    /**
     * Get Grade Straight through db
     *
     * @return float grade
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Get Zeros Straight through db
     *
     * @return int zero count
     */
    public function getZeroCount()
    {
        return $this->zero_count;
    }

    /**
     * Get Assignment Late submission count
     *
     * @return int
     */
    public function getLateCount()
    {
        return $this->late_count;
    }

    public function gradeCached()
    {
        return (bool) $this->grade_updated;
    }

    public function getAssignmentArr()
    {
        if (($assignmentArr = &self::$cache['getAssignmentArr'][$this->canvas_enrollment_id]) === NULL) {
            if (!$this->id()) {
                return $assignmentArr = array();
            }
            $assignmentArr = mth_canvas::exec('/courses/' . $this->canvas_course_id() . '/analytics/users/' . $this->canvas_user_id() . '/assignments');
            if (!is_array($assignmentArr)) {
                $assignmentArr = array();
            }
        }
        return $assignmentArr;
    }


    public function getSubmissionByUser()
    {
        if (($assignmentArr = &self::$cache['getSubmissionByUser'][$this->canvas_enrollment_id]) === NULL) {
            if (!$this->id()) {
                return $assignmentArr = array();
            }
            $assignmentArr = mth_canvas::exec('/courses/' . $this->canvas_course_id() . '/students/submissions?grouped=true&include[]=submission_comments&student_ids[]=' . $this->canvas_user_id());
            if (!is_array($assignmentArr)) {
                $assignmentArr = array();
            }
        }
        return count($assignmentArr) > 0 ? $assignmentArr[0]->submissions : [];
    }

    protected function getZeroCountFromCanvas()
    {
        $zeroCount = 0;
        foreach ($this->getAssignmentArr() as $assignmentObj) {
            $due = strtotime($assignmentObj->due_at);
            $now = time();
            if (
                !$assignmentObj->excused && !empty($assignmentObj->due_at)
                && $due < $now
                && (empty($assignmentObj->submission) || !$assignmentObj->submission->score)
            ) {
                $zeroCount++;
            }
        }
        return $zeroCount;
    }

    public function zeroCount($forceRefresh = false)
    {
        if (!$this->canvas_enrollment_id) {
            return NULL;
        }
        if (
            is_null($this->zero_count)
            || strtotime(self::GRADE_EXP, strtotime($this->zero_count_updated)) < time()
            || $forceRefresh
        ) {
            $this->set('zero_count', $this->getZeroCountFromCanvas());
            $this->set('zero_count_updated', date('Y-m-d H:i:s'));
            $this->runSaveUpdates = true;
        }
        return $this->zero_count;
    }

    public function saveUpdates()
    {
        if (!$this->canvas_enrollment_id) {
            return;
        }
        return parent::runUpdateQuery('mth_canvas_enrollment', 'canvas_enrollment_id=' . $this->id());
    }

    public function __destruct()
    {
        if ($this->runSaveUpdates) {
            $this->saveUpdates();
        }
    }

    public function getLastAcitivity($format = NULL)
    {
        return $this->last_activity_at == '0000-00-00 00:00:00' || is_null($this->last_activity_at) ? null : core_model::getDate($this->last_activity_at, $format);
    }
}
