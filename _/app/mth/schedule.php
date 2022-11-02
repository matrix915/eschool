<?php

/**
 * mth_schedule
 *
 * @author abe
 */
class mth_schedule extends core_model
{
    protected $schedule_id;
    protected $student_id;
    protected $school_year_id;
    protected $status;
    protected $date_accepted; //mth021.sql
    protected $last_modified; //mth033.sql
    protected $date_submitted;
    protected $current_submission;

    //special cases when join schedule period 
    protected $schedule_period_id;

    //3,5,1,4,6 
    const STATUS_STARTED = 0;
    const STATUS_SUBMITTED = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_CHANGE = 3;
    const STATUS_RESUBMITTED = 4;
    const STATUS_CHANGE_POST = 5;
    const STATUS_CHANGE_PENDING = 6;
    const STATUS_DELETED = 99;
    const STATUS_NOT_STARTED = 66;

    protected static $status_options = array(
        self::STATUS_STARTED => 'Started',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_CHANGE => 'Updates required',
        self::STATUS_RESUBMITTED => 'Resubmitted',
        self::STATUS_CHANGE_POST => 'Unlocked',
        self::STATUS_CHANGE_PENDING => 'Pending Unlock'
    );

    protected static $all_statuses = array(
        self::STATUS_STARTED,
        self::STATUS_SUBMITTED,
        self::STATUS_ACCEPTED,
        self::STATUS_CHANGE,
        self::STATUS_RESUBMITTED,
        self::STATUS_CHANGE_POST,
        self::STATUS_CHANGE_PENDING,
        self::STATUS_DELETED
    );

    protected static $cache = array();

    public static function status_options()
    {
        return self::$status_options;
    }

    public static function all_options()
    {
        return self::$all_statuses;
    }

    public static function status_option_text($status_num)
    {
        return self::$status_options[$status_num];
    }

    public static function pending_statuses()
    {
        return array(self::STATUS_CHANGE, self::STATUS_RESUBMITTED, self::STATUS_SUBMITTED, self::STATUS_CHANGE_POST, self::STATUS_CHANGE_PENDING);
    }

    public static function accepted_statuses()
    {
        return array(self::STATUS_ACCEPTED, self::STATUS_CHANGE_POST, self::STATUS_CHANGE_PENDING);
    }


    public function id()
    {
        return (int) $this->schedule_id;
    }


    public function getSchedulePeriodId()
    {
        return $this->schedule_period_id;
    }

    /**
     *
     * @return mth_student
     */
    public function student()
    {
        return mth_student::getByStudentID($this->student_id);
    }

    public function student_id()
    {
        return (int) $this->student_id;
    }

    /**
     *
     * @return mth_schoolYear
     */
    public function schoolYear()
    {
        return mth_schoolYear::getByID($this->school_year_id);
    }

    public function schoolYearID()
    {
        return (int) $this->school_year_id;
    }

    public function student_grade_level()
    {
        if(!($student = $this->student())) {
            return null;
        }
        return $student->getGradeLevelValue($this->school_year_id);
    }

    public function applyDiplomaSeekingLimits()
    {
        return $this->student()->diplomaSeeking() && $this->student_grade_level() > 8;
    }

    public function isSubmited()
    {
        return in_array($this->status, array(self::STATUS_SUBMITTED, self::STATUS_ACCEPTED, self::STATUS_RESUBMITTED));
    }

    public function isAccepted()
    {
        return $this->status == self::STATUS_ACCEPTED || $this->status == self::STATUS_CHANGE_POST || $this->status == self::STATUS_CHANGE_PENDING;
    }

    public function isAcceptedOnly()
    {
        return $this->status == self::STATUS_ACCEPTED;
    }

    public function isNewSubmission()
    {
        return $this->status == self::STATUS_SUBMITTED;
    }

    public function isResubmitted()
    {
        return $this->status == self::STATUS_RESUBMITTED;
    }

    public function isUpdatesRequired()
    {
        return $this->status == self::STATUS_CHANGE;
    }

    public function isPending()
    {
        return in_array($this->status, self::pending_statuses());
    }

    public function isPendingUnlock()
    {
        return $this->status == self::STATUS_CHANGE_PENDING;
    }

    public function isToChange()
    {
        return $this->status == self::STATUS_CHANGE || $this->status == self::STATUS_CHANGE_POST;
    }

    public function status($returnNumber = false)
    {
        if ($returnNumber) {
            return (int) $this->status;
        }
        if ($this->status == self::STATUS_DELETED) {
            return 'Deleted';
        }
        return self::$status_options[$this->status];
    }

    public function isStatus($statusNum)
    {
        return $this->status == $statusNum;
    }

    public function canRemoveChangeAbility()
    {
        return in_array($this->status, [self::STATUS_CHANGE_POST, self::STATUS_CHANGE_PENDING]);
    }

    public function date_accepted($format = NULL)
    {
        return self::getDate($this->date_accepted, $format);
    }

    public function date_submitted($format = NULL)
    {
        return self::getDate($this->date_submitted, $format);
    }

    public function current_submission($format = NULL)
    {
       return self::getDate($this->current_submission, $format);
    }

    /**
     * if period from schedule has any different per semester
     * @param int $period_number
     * @return boolean
     */
    public function hasDifferentSemesterCourse($period_number)
    {
        $first_sem = $this->getPeriod($period_number, false);
        $second_sem = $this->getPeriod($period_number, true);
        return
            $first_sem &&
            $second_sem &&
            mth_schedule_period::isDifferentSemSet($first_sem, $second_sem);
    }

    /**
     *
     * @param int $period_num
     * @param bool $second_semester
     * @return mth_schedule_period
     */
    public function getPeriod($period_num, $second_semester = false)
    {
        if (!($period = mth_period::get($period_num))) {
            return false;
        }
        return mth_schedule_period::get($this, $period, $second_semester);
    }

    /**
     * @return mixed
     */
    public function getLastModified($format = null)
    {
        return self::getDate($this->last_modified, $format);
    }

    /**
     * @return mixed
     */
    public function getSubmittedDate($format = null)
    {
      return self::getDate($this->date_submitted, $format);
    }

    public function getCurrentSubmissionDate($format = null){
        return self::getDate($this->current_submission, $format);
    }

    /**
     * @return mixed
     */
    public function displayDateSubmitted($scheduleStatus, $format = null){
        if($scheduleStatus !== 'Started'){
            return ' (' . self::getDate($this->date_submitted, $format) . '/Original Submitted)';
        }
        else return '';
    }

    /**
     *
     * @param mth_student $student
     * @param mth_schoolYear $year
     * @return mth_schedule
     */
    public static function get(mth_student $student, mth_schoolYear $year = NULL, $includeDeleted = false)
    {
        if (!$year && !($year = mth_schoolYear::getCurrent())) {
            return false;
        }

        $schedule = &self::cache(__CLASS__, $student->getID() . '-' . $year->getID());
        if (!isset($schedule)) {
            $schedule = core_db::runGetObject(
                'SELECT * FROM mth_schedule 
                                          WHERE student_id=' . $student->getID() . ' 
                                            AND school_year_id=' . $year->getID() . '
                                            ' . ($includeDeleted ? '' : 'AND status!=' . self::STATUS_DELETED) . '
                                          ORDER BY schedule_id DESC',
                'mth_schedule'
            );
        }
        return $schedule;
    }

    /**
     *
     * @param int[] $studentIds
     * @param mth_schoolYear $year
     * @return mth_schedule[]
     */
    public static function getSchedulesByStudentIDs(Array $studentIds, mth_schoolYear $year = NULL, $includeDeleted = false)
    {
        if (is_array($studentIds) && empty($studentIds)) {
            return array();
        }

        if (!$year && !($year = mth_schoolYear::getCurrent())) {
            return false;
        }

        $schedules = core_db::runGetObjects(
            'SELECT * FROM mth_schedule 
                                      WHERE student_id IN(' . implode(',', $studentIds) . ') 
                                        AND school_year_id=' . $year->getID() . '
                                        ' . ($includeDeleted ? '' : 'AND status!=' . self::STATUS_DELETED) . '
                                      ORDER BY schedule_id DESC',
            'mth_schedule'
        );
        if(empty($schedules)) {
            $schedules = [];
        }
        return $schedules;
    }

    /**
     *
     * @param int $schedule_id
     * @return mth_schedule
     */
    public static function getByID($schedule_id)
    {
        $schedule = &self::cache(__CLASS__, (int) $schedule_id);
        if (!isset($schedule)) {
            $schedule = core_db::runGetObject(
                'SELECT * FROM mth_schedule WHERE schedule_id=' . (int) $schedule_id,
                'mth_schedule'
            );
        }
        return $schedule;
    }

    /**
     *
     * @param array $statuses
     * @param null|int $modified_since
     * @param bool $reset
     * @return mth_schedule
     */
    public static function each(array $statuses = NULL, $modified_since = NULL, $reset = false)
    {
        $result = &self::eachResultCache($statuses, $modified_since);
        if (!isset($result)) {
            $result = core_db::runQuery('SELECT * FROM mth_schedule 
                                    WHERE status IN (' . implode(',', $statuses) . ') '
                . ($modified_since ? ' AND last_modified>"' . date('Y-m-d H:i:s', $modified_since) . '"' : '')
                . ' ORDER BY schedule_id DESC');
        }
        if (!$reset && ($schedule = $result->fetch_object('mth_schedule'))) {
            return $schedule;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function count(array $statuses = NULL)
    {
        $result = &self::eachResultCache($statuses);
        if (!isset($result)) {
            self::each($statuses, null, true);
        }
        return $result->num_rows;
    }

    protected static function &eachResultCache(array &$statuses = NULL, $modified_since = false)
    {
        if (empty($statuses)) {
            $statuses = self::pending_statuses();
        } else {
            $statuses = array_map('intval', $statuses);
        }
        sort($statuses);
        return self::$cache['eachResultCache'][implode(',', $statuses)][$modified_since];
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param false $reset
     * @return mth_schedule
     */
    public static function eachOfYear(mth_schoolYear $year, array $statuses = NULL, $reset = false)
    {
        $result = &self::cache(__CLASS__, 'eachOfYear-' . $year->getName() . '-' . serialize($statuses));
        if (!isset($result)) {
            if (empty($statuses)) {
                $statuses = self::accepted_statuses();
            }
            $result = core_db::runQuery('SELECT * FROM mth_schedule 
                                    WHERE school_year_id=' . $year->getID() . '
                                      AND status IN (' . implode(',', array_map('intval', $statuses)) . ')
                                    ORDER BY schedule_id DESC');
        }
        if (!$reset && ($schedule = $result->fetch_object('mth_schedule'))) {
            return $schedule;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    public static function getStudentIdsFromStatus(int $yearId, $status) {
        if(!in_array($status, self::$all_statuses)) {
            return [];
        }
        return core_db::runGetValues('SELECT student_id FROM mth_schedule
                WHERE school_year_id=' . (int) $yearId . ' AND status="' . $status . '"
                GROUP BY student_id');
    }

    public static function eachByStudentIds(mth_schoolYear $year, array $student_ids, array $statuses = NULL, $reset = false)
    {
        $result = &self::cache(__CLASS__, 'eachByStudentIds-' . $year->getName() . '-' . serialize($statuses));
        if (!isset($result)) {
            if (empty($statuses)) { 
                $statuses = self::accepted_statuses();
            }
            $result = core_db::runQuery('SELECT * FROM mth_schedule 
                                    WHERE school_year_id=' . $year->getID() . '
                                      AND status IN (' . implode(',', array_map('intval', $statuses)) . ')
                                      AND student_id IN (' . implode(',', array_map('intval', $student_ids)) . ')
                                    ORDER BY schedule_id DESC');

            if (!$result) {
                return NULL;
            }
        }


        if (!$reset && ($schedule = $result->fetch_object('mth_schedule'))) {
            return $schedule;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    /**
     *
     * @param int $student_id
     * @param bool $reset
     * @return mth_schedule
     */
    public static function eachOfStudent($student_id, $reset = false)
    {
        $result = &self::$cache['eachOfStudent'][$student_id];
        if ($result === NULL) {
            $result = core_db::runQuery('SELECT * FROM mth_schedule 
                                    WHERE student_id=' . (int) $student_id . ' 
                                      AND status!=' . self::STATUS_DELETED . '
                                    ORDER BY schedule_id DESC');
        }
        if (!$reset && ($schedule = $result->fetch_object('mth_schedule'))) {
            return $schedule;
        }
        $result->data_seek(0);
        return NULL;
    }

    /**
     *
     * @param mth_student $student
     * @param mth_schoolYear $year
     * @return mth_schedule
     */
    public static function create(mth_student $student, mth_schoolYear $year)
    {
        if (($schedule = self::get($student, $year))) {
            return $schedule;
        }
        core_db::runQuery('INSERT INTO mth_schedule (student_id, school_year_id, `status`) 
                        VALUES (' . $student->getID() . ', ' . $year->getID() . ',' . self::STATUS_STARTED . ')');
        return self::getByID(core_db::getInsertID());
    }

    /**
     *
     * @param mth_student $student
     * @return array Array of approved student schedule IDs using the school_year_id as the keys
     */
    public static function getStudentScheduleIDs(mth_student $student, $activeOnly = true, $includeDeleted = false, $order = 'ASC')
    {
        $arr = &self::cache(__CLASS__, 'StudentScheduleIDs-' . $student->getID());
        if (!isset($arr)) {
            $arr = array();
            $result = core_db::runQuery('SELECT s.school_year_id, s.schedule_id 
                                    FROM mth_schedule AS s 
                                      LEFT JOIN mth_schoolyear AS y ON y.school_year_id=s.school_year_id
                                    WHERE s.student_id=' . $student->getID() . '
                                      ' . ($activeOnly ? 'AND s.status=' . self::STATUS_ACCEPTED : '') . '
                                      ' . (!$includeDeleted ? 'AND s.status!=' . self::STATUS_DELETED : '') . '
                                    ORDER BY y.date_begin ' . $order);
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = $r[1];
            }
            $result->free_result();
        }
        return $arr;
    }

    public static function getAllStudentScheduleIds(mth_schoolYear $year) 
    {
        $arr = array();
        $result = core_db::runQuery('SELECT s.student_id, s.schedule_id 
            FROM mth_schedule AS s 
            LEFT JOIN mth_schoolYear AS y ON y.school_year_id=s.school_year_id
            WHERE s.school_year_id=' . $year->getID() . '
                AND s.status=2
                AND s.status!=99
            ORDER BY y.date_begin ASC');
        if($result) {
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = $r[1];
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    public static function getStudentScheduleID(mth_student $student)
    {
        $scheduleIDs = self::getStudentScheduleIDs($student);
        if (($currentYear = mth_schoolYear::getCurrent()) && isset($scheduleIDs[$currentYear->getID()])) {
            return $scheduleIDs[$currentYear->getID()];
        }
        if (($nextYear = mth_schoolYear::getNext()) && isset($scheduleIDs[$nextYear->getID()])) {
            return $scheduleIDs[$nextYear->getID()];
        }
        return NULL;
    }

    public static function deleteStudentSchedules(mth_student $student)
    {
        foreach (self::getStudentScheduleIDs($student, false) as $schedule_id) {
            if (($schedule = self::getByID($schedule_id))) {
                $schedule->delete();
            }
        }
    }

    public function submit()
    {
        if (!$this->editable() || !$this->readyToSubmit()) {
            return false;
        }
        if(empty($this->date_submitted('Y-m-d H:i:s')))
        {
           $this->setDateSubmitted();
        }
        $this->setStatus(self::STATUS_SUBMITTED);
        return $this->save();
    }

    public function resubmit()
    {
        if (!$this->isToChange() || !$this->readyToSubmit()) {
            return false;
        }

        $this->setStatus(self::STATUS_RESUBMITTED);
        return $this->save();
    }

    public function setStatus($statusNum)
    {
       if (isset(self::$status_options[$statusNum])) {
          $this->set('status', (int)$statusNum);
       }
    }

    public function setDateSubmitted()
    {
        $this->set('date_submitted', date('Y-m-d H:i:s'));
    }

    public function enable2ndSemChanges($sendEmail = true)
    {
        $regOpen = ($school_year = mth_schoolYear::get2ndSemOpenReg()) && $school_year->getID() == $this->schoolYearID();
        if (
            !$this->second_sem_change_available()
            || (!$regOpen && !core_user::isUserAdmin())
        ) {
            return false;
        }
        if ((in_array($this->status(true), array(self::STATUS_CHANGE_PENDING, self::STATUS_CHANGE_POST, self::STATUS_CHANGE, self::STATUS_SUBMITTED, self::STATUS_RESUBMITTED))
                || $this->date_accepted() > $this->schoolYear()->getSecondSemOpen())
            && !core_user::isUserAdmin()
        ) {
            return false;
        }
        $this->setStatus($this->isAccepted() ? self::STATUS_CHANGE_POST : self::STATUS_CHANGE);
        if ($this->save()) {
            if (!$sendEmail) {
                return true;
            }
            if (
                !($subject = core_setting::get('scheduleUnlockFor2ndSemEmailSubject', 'Schedules'))
                || !($content = core_setting::get('scheduleUnlockFor2ndSemEmail', 'Schedules'))
            ) {
                error_log('You need to run /_/setup.php');
                return false;
            }
            $find = ['[PARENT]', '[STUDENT]', '[SCHOOL_YEAR]', '[DEADLINE]'];
            $replace = [
               $this->student()->getParent()->getPreferredFirstName(),
               $this->student()->getPreferredFirstName(),
               $this->schoolYear(),
               $this->schoolYear()->getSecondSemClose('F j'),
            ];
            $ses = new core_emailservice();
            $ses->enableTracking(true);

            $result = $ses->send(
                [$this->student()->getParent()->getEmail()],
                str_replace(
                    $find,
                    $replace,
                    $subject->getValue()
                ),
                str_replace(
                    $find,
                    $replace,
                    $content->getValue()
                )
            );

            return $result;
        }
        return false;
    }

    public function save()
    {
        if (!$this->schedule_id) {
            error_log('Must create schedule using create method.');
            return false;
        }
        return parent::runUpdateQuery('mth_schedule', 'schedule_id=' . $this->id());
    }

    public function __destruct()
    {
        $this->save();
    }

    public function readyToSubmit()
    {
        $invalidPeriods = $this->invalidPeriods();
        return empty($invalidPeriods);
    }

    /**
     *
     * @return array Array of invalid period numbers
     */
    public function invalidPeriods()
    {
        $invalidPeriodNums = array();
        while ($period = mth_period::each($this->student_grade_level())) {
            if (
                !($schedulePeriod = mth_schedule_period::get($this, $period, $this->schoolYear()->getSecondSemOpen() < time()))
                || !$schedulePeriod->validate()
            ) {
                $invalidPeriodNums[] = $period->num();
            }
        }
        return $invalidPeriodNums;
    }

    /**
     *
     * @param bool $reset
     * @param string $variation
     * @return mth_schedule_period
     */
    public function eachPeriod($reset = FALSE, $variation = NULL)
    {
        return mth_schedule_period::each($this, $reset, $variation);
    }

    /**
     * @param bool $hideReplaced
     * @return mth_schedule_period[]
     */
    public function allPeriods($hideReplaced = true)
    {
        if (NULL === ($arr = &self::$cache['allPeriods'][$this->id()][$hideReplaced])) {
            $arr = array();
            if ($hideReplaced) {
                while ($schedulePeriod = $this->eachPeriod(false, 'allPeriods')) {
                    $arr[$schedulePeriod->period()->num()] = $schedulePeriod;
                }
            } else {
                while ($schedulePeriod = $this->eachPeriod(false, 'allPeriods')) {
                    $arr[$schedulePeriod->period()->num() . '-' . ($schedulePeriod->second_semester() ? '2' : '1')] = $schedulePeriod;
                }
            }
        }
        return $arr;
    }

    public function approve()
    {
        $this->removeChangeRequirements();
        $this->set('status', self::STATUS_ACCEPTED);
        $this->set('date_accepted', date('Y-m-d H:i:s'));
        if (
            $this->save() && $this->student()->setStatus(mth_student::STATUS_ACTIVE, $this->schoolYear())
            && $this->student() && $this->student()->getParent() && $this->schoolYear()
        ) {
            if (!$this->sendApprovalEmail()) {
                core_notify::addError('Unable to send schedule approved email for student ' . $this->student());
            }
            mth_canvas_enrollment::updateCanvasStudentEnrollments($this->student(), $this->schoolYear());
            //mth_canvas_enrollment::updateCanvasParentEnrollments($this->student()->getParent(), $this->schoolYear());
            return true;
        }
        return false;
    }

    public function sendApprovalEmail()
    {
        $secondSem = $this->lastChangeWas2ndSemChange() ? '2ndSem' : '';
        if (
            !($emailSubject = core_setting::get('scheduleApprovedEmailSubject' . $secondSem, 'Schedules'))
            || !($emailContent = core_setting::get('scheduleApprovedEmail' . $secondSem, 'Schedules'))
        ) {
            error_log('scheduleApprovedEmail' . $secondSem . ' not set');
            return false;
        }
        $find = array('[PARENT]', '[STUDENT]', '[SCHOOL_YEAR]', '[SCHEDULE_CLOSE_DATE]', '[SCHOOL_YEAR_START_DATE]');
        $replace = array(
            $this->student()->getParent()->getPreferredFirstName(),
            $this->student()->getPreferredFirstName(),
            $this->schoolYear()->getName(),
            $this->schoolYear()->getDateRegClose('F j'),
            $this->schoolYear()->getDateBegin('F j')
        );

        $email = new core_emailservice();
        return $email->send(
            array($this->student()->getParent()->getEmail()),
            str_replace($find, $replace, $emailSubject),
            str_replace($find, $replace, $emailContent),
            null,
            null,
            [core_setting::getSiteEmail()->getValue()]
        );

    }

    public function delete()
    {
        $this->updateQueries = array();
        return core_db::runQuery('UPDATE mth_schedule 
        SET status=' . self::STATUS_DELETED . ',
            last_modified=NOW()
        WHERE schedule_id=' . $this->id());
    }

    public function restore(mth_archive $archive = null)
    {
        $status = $archive ? $archive->schedule_status() : self::STATUS_ACCEPTED;
        $last_status_date = $archive ? $archive->schedule_date() : null;

        $this->updateQueries = array();
        return core_db::runQuery('UPDATE mth_schedule 
        SET status=' . $status . ($last_status_date ? (',last_modified="' . $last_status_date . '"') : '') . '
        WHERE schedule_id=' . $this->id());
    }

    public function editable()
    {
        if (core_user::isUserAdmin()) {
            return true;
        }
        return $this->status == self::STATUS_STARTED;
        // || $this->status == self::STATUS_CHANGE
        // || $this->status == self::STATUS_CHANGE_POST;
    }

    public function alterable()
    {
        return $this->status == self::STATUS_STARTED
            || $this->status == self::STATUS_CHANGE
            || $this->status == self::STATUS_CHANGE_POST;
    }

    protected function set($field, $value, $force = false)
    {
       if(core_user::isUserTeacherAbove()) {
          parent::set('last_modified', date('Y-m-d H:i:s'));
          parent::set('current_submission', null);
       } elseif (empty($this->current_submission('Y-m-d H:i:s'))) {
          parent::set('last_modified', date('Y-m-d H:i:s'));
          parent::set('current_submission', date('Y-m-d H:i:s'));
       }
        return parent::set($field, $value, $force);
    }

    public function updated()
    {
        if (empty($this->current_submission('Y-m-d H:i:s')) && (int) $this->status === (int) self::STATUS_STARTED ) {
            parent::set('last_modified', date('Y-m-d H:i:s'));
        }
    }

    public function requireChanges(array $schedulePeriodIDs)
    {
        $this->removeChangeRequirements();
        foreach ($schedulePeriodIDs as $schedulePeriodID) {
            if (!($schedulePeriod = mth_schedule_period::getByID($schedulePeriodID))) {
                return false;
            }
            $schedulePeriod->require_change(true);
        }
        return true;
    }

    /**
     * unlockPeriods is use when unlocking periods of submitted schedules
     *
     * @param ARRAY $schedulePeriodIDs
     * @return void
     */
    public function unlockPeriods(array $schedulePeriodIDs)
    {
        $this->removeChangeRequirements();
        foreach ($schedulePeriodIDs as $schedulePeriodID) {
            if (!($schedulePeriod = mth_schedule_period::getByID($schedulePeriodID))) {
                return false;
            }
            $schedulePeriod->unlock_period();
        }
        return true;
    }

    public function requireChangesPending(array $schedulePeriodIDs)
    {

        foreach ($schedulePeriodIDs as $schedulePeriodID) {
            if (!($schedulePeriod = mth_schedule_period::getByID($schedulePeriodID))) {
                return false;
            }
            $schedulePeriod->require_change_pending(true);
        }
        return true;
    }

    public function removeChangeRequirements()
    {
        $this->eachPeriod(true);
        while ($schedulePeriod = $this->eachPeriod()) {
            $schedulePeriod->require_change(false);
        }
        $this->setStatus($this->isAccepted() ? self::STATUS_ACCEPTED : self::STATUS_SUBMITTED);
        if ($this->isAccepted()) {
            mth_canvas_enrollment::updateCanvasStudentEnrollments($this->student(), $this->schoolYear());
            //mth_canvas_enrollment::updateCanvasParentEnrollments($this->student()->getParent(), $this->schoolYear());
        }
    }


    public function second_sem_change_available()
    {
        // If student is a mid-year enrollment for the schedule year, 2nd semester change is unavailable
        if($this->student()->isMidYear($this->schoolYear())) {
            return false;
        }

        $this->eachPeriod(true);
        $availablePeriods = array();
        while ($schedulePeriod = $this->eachPeriod()) {
            if ($schedulePeriod->second_sem_change_available()) {
                $availablePeriods[$schedulePeriod->period()->num()] = true;
            }
            if ($schedulePeriod->second_semester()) {
                unset($availablePeriods[$schedulePeriod->period()->num()]);
            }
        }
        return count($availablePeriods) > 0;
    }


    public function is_unlocked_for_second_sem()
    {
        $is_unclocked_status = in_array($this->status, [self::STATUS_CHANGE_POST, self::STATUS_RESUBMITTED, self::STATUS_CHANGE]);
        $has_second_semester = false;

        $this->eachPeriod(true);
        while ($schedulePeriod = $this->eachPeriod()) {
            if ($schedulePeriod->second_sem_change_available()) {
                $has_second_semester = true;
                break;
            }
        }

        return $has_second_semester && $is_unclocked_status;
    }

    public function second_sem_change_available_for_provider()
    {
        $this->eachPeriod(true);
        $availablePeriods = array();
        while ($schedulePeriod = $this->eachPeriod()) {
            if (
                $schedulePeriod->second_sem_change_available()
                && ($pc = $schedulePeriod->provider_course())
                && ($p = $pc->provider())
                && ($p->allow_2nd_sem_change())
            ) {
                $availablePeriods[$schedulePeriod->period()->num()] = true;
            }
            if ($schedulePeriod->second_semester()) {
                unset($availablePeriods[$schedulePeriod->period()->num()]);
            }
        }
        return count($availablePeriods) > 0;
    }

    /**
     *
     * @param bool $reset
     * @return mth_schedule_period
     */
    public function eachChangedPeriod($reset = false)
    {
        if (!$reset && ($period = $this->eachPeriod())) {
            if ($period->changedLast()) {
                return $period;
            } else {
                return $this->eachChangedPeriod();
            }
        }
        $this->eachPeriod(true);
        return NULL;
    }

    public function lastChangeWas2ndSemChange()
    {
        /** commented out for IN-547 */
        // $this->eachChangedPeriod(true);
        while ($schedulePeriod = $this->eachChangedPeriod()) {
            if ($schedulePeriod->second_semester()) {
                return true;
            }
        }
    }

    public static function getStatusCount(int $statusId, mth_schoolYear $year = NULL) {
        if(!$year && !($year = mth_schoolYear::getCurrent())) {
          return 0;
        }
        if(!in_array($statusId, self::$all_statuses)) {
          return 0;
        }
        $result = core_db::runGetValue('SELECT count(*) FROM mth_schedule
                                    WHERE 1
                                    AND school_year_id=' . $year->getID() .'
                                    AND `status` =' . (int) $statusId);
        return $result;
    }

    public static function getStatusCounts(mth_schoolYear $year = NULL)
    {
        $order = [self::STATUS_CHANGE, self::STATUS_CHANGE_POST, self::STATUS_SUBMITTED, self::STATUS_RESUBMITTED, self::STATUS_CHANGE_PENDING];
        $counts = array_map('intval', self::$status_options);
        $result = core_db::runQuery('SELECT status, count(*) 
                                  FROM mth_schedule 
                                  WHERE 1 
                                    ' . ($year ? 'AND school_year_id=' . $year->getID() : '') . '
                                    AND STATUS!=' . self::STATUS_DELETED . '
                                  GROUP BY STATUS');
        while ($r = $result->fetch_row()) {
            $counts[$r[0]] = $r[1];
        }
        $result->free_result();

        return array_replace(array_flip($order), $counts);
    }

    public static function getNotStartedCount(mth_schoolYear $year = NULL)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return 0;
        }

        $count = &self::cache(__CLASS__, 'getNotStartedCount-' . $year->getID());

        if (!isset($count)) {
            $count = 0;
            $filter = new mth_person_filter();
            $filter->setStatus(mth_student::STATUS_PENDING);
            $filter->setStatusYear($year->getID());

            $students = $filter->getStudents();

            foreach ($students as $student) {
                /* @var $student mth_student */
                if ((($schedule = mth_schedule::get($student, $year)))) {
                    continue;
                }
                $count++;
            }
        }
        return $count;
    }

    public static function getEnrolledStudents($year_id, $course_id, array $scheduleStatuses = null)
    {
        $student_ids =  &self::cache(__CLASS__, 'getEnrolledStudents-' . $year_id . '-' . $course_id);
        if (!isset($student_ids)) {
            $type = mth_schedule_period::TYPE_MTH;
            $sql = "SELECT student_id FROM mth_schedule_period AS sp
                                          LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                        WHERE sp.course_id=$course_id
                                          AND s.status IN (" . implode(',', array_map('intval', $scheduleStatuses)) . ")
                                          AND s.school_year_id=$year_id
                                          AND sp.course_type=$type
                                          AND (sp.custom_desc IS NULL OR sp.custom_desc!='[:NONE:]')
                                        ORDER BY sp.`period` ASC";

            $students = core_db::runGetObjects($sql);

            if (!$students) {
                error_log('Error in getEnrolledStudents query: ' . $sql);
                return;
            }

            foreach ($students as $student) {
                $student_ids[] = $student->student_id;
            }
        }
        return $student_ids;
    }

    public function hasAllowanceCourse()
    {
        foreach ($this->allPeriods() as $period) {
            if ($period->course() && (int) $period->course()->allowance()) {
                return true;
            }
        }
        return false;
    }

    public function getCurrentProviders()
    {
        $providers = [];
        foreach ($this->allPeriods() as $period) {
            if ($provider_id = $period->mth_provider_id()) {
                $providers[] = $provider_id;
            }
        }
        return $providers;
    }

    public static function getScheduleByStudentId($student_id, $school_year = false){
        if(!$school_year){
            $school_year = mth_schoolYear::getCurrent()->getID();
        }
        $delete_status = self::STATUS_DELETED;

        $schedule = core_db::runGetObject("SELECT * FROM mth_schedule 
            WHERE student_id='$student_id' 
            AND school_year_id='$school_year'
            ORDER BY schedule_id DESC");
        return $schedule;
    }
}
