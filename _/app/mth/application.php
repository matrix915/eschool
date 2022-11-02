<?php

/**
 * application
 *
 * @author abe
 */
class mth_application
{
    protected $application_id;
    protected $student_id;
    protected $school_year_id;
    protected $status;
    protected $city_of_residence;
    protected $agrees_to_policies;
    protected $referred_by;
    protected $date_started;
    protected $date_submitted;
    protected $date_accepted;
    protected $accepted_by_user_id;
    protected $midyear_application;

    const STATUS_STARTED = 'Started';
    const STATUS_SUBMITTED = 'Submitted';
    const STATUS_ACCEPTED = 'Accepted';
    const STATUS_DENIED = 'Denied';
    const STATUS_DELETED = 'Deleted';

    public static function getAvailableStatuses()
    {
        return array(self::STATUS_SUBMITTED, self::STATUS_ACCEPTED);
    }

    private $_updateFields;

    protected static $cache = array();

    /**
     *
     * @param mth_student $student
     * @param mth_schoolYear $schoolYear
     * @return mth_application
     */
    public static function startApplication(mth_student $student, mth_schoolYear $schoolYear)
    {
        $application = core_db::runGetObject('SELECT * FROM mth_application 
                                          WHERE student_id=' . $student->getID() . ' 
                                            AND school_year_id=' . $schoolYear->getID() . '
                                            AND status!="' . self::STATUS_DELETED . '"', 'mth_application');
        if (!$application) {
            $application = self::create($student, $schoolYear);
        }
        return $application;
    }

    /**
     *
     * @param mth_student $student
     * @param mth_schoolYear $schoolYear
     * @return mth_application
     */
    public static function create(mth_student $student, mth_schoolYear $schoolYear)
    {
        core_db::runQuery('INSERT INTO mth_application 
                    (student_id, school_year_id, status, date_started) 
                    VALUES (' . $student->getID() . ',' . $schoolYear->getID() . ',"' . self::STATUS_STARTED . '", NOW())');
        return self::getApplicationByID(core_db::getInsertID());
    }

    /**
     *
     * @param mth_student $student
     * @param null $schoolYearId
     * @return mth_application
     */
    public static function getStudentApplication(mth_student $student, $schoolYearId = null)
    {
        $filters = ['StudentID' => $student->getID()];
        if ($schoolYearId !== null) {
            $filters['SchoolYear'] = $schoolYearId;
        }

        $applications = self::getApplications($filters);
        if (!empty($applications)) {
            return $applications[0];
        }
    }

    /**
     *
     * @param int $application_id
     * @return mth_application
     */
    public static function getApplicationByID($application_id)
    {
        return core_db::runGetObject('SELECT * FROM mth_application 
                                    WHERE application_id=' . (int) $application_id, 'mth_application');
    }

    /**
     *
     * @param array $filter accepts status.
     * @return array of mth_application objects
     */
    public static function getApplications(array $filter)
    {
        $studentIdClause = isset($filter['StudentID'])
                ? ' AND student_id' . (is_array($filter['StudentID'])
                    ? ' IN (' . implode(',',$filter['StudentID']) . ')'
                    : '=' . (int) $filter['StudentID'])
                : '';
        return core_db::runGetObjects(
            'SELECT * FROM mth_application 
                                    WHERE 1
                                    ' . (isset($filter['Status'])
                ? ' AND status="' . core_db::escape($filter['Status']) . '"'
                : ' AND status!="' . self::STATUS_DELETED . '"') . '
                ' . $studentIdClause . (isset($filter['SchoolYear']) ? ' AND school_year_id=' . $filter['SchoolYear'] : '') . '
                ORDER BY application_id DESC',
            'mth_application'
        );
    }

    /**
     *
     * @return mth_application[]
     */
    public static function getSubmittedApplications()
    {
        return self::getApplications(array('Status' => self::STATUS_SUBMITTED));
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param bool $reset
     * @return mth_application
     */
    public static function eachSubmittedApplication(mth_schoolYear $year = NULL, $reset = false)
    {
        $result = &self::$cache['eachSubmittedApplication'][$year ? $year->getID() : NULL];
        if ($result === NULL) {
            $result = core_db::runQuery('SELECT * FROM mth_application 
                                    WHERE status="' . self::STATUS_SUBMITTED . '"
                                      ' . ($year ? 'AND school_year_id=' . $year->getID() : ''));
        }
        if (!$reset && ($application = $result->fetch_object('mth_application'))) {
            return $application;
        }
        $result->data_seek(0);
        return NULL;
    }

    /**
     *
     * @return array of mth_application objects
     */
    public static function getAcceptedApplications()
    {
        return self::getApplications(array('Status' => self::STATUS_ACCEPTED));
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param bool $reset
     * @return mth_application
     */
    public static function eachOfYear(mth_schoolYear $year, $reset = false)
    {
        $result = &self::$cache['eachOfYear'][$year->getID()];
        if (!isset($result)) {
            $result = core_db::runQuery('SELECT * FROM mth_application 
                                    WHERE school_year_id=' . $year->getID() . '
                                      AND status!="' . self::STATUS_DELETED . '"');
        }
        if (!$reset && ($app = $result->fetch_object('mth_application'))) {
            return $app;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    public function setSchoolYear(mth_schoolYear $schoolYear)
    {
        $this->school_year_id = $schoolYear->getID();
        $this->_updateFields['school_year_id'] = 'school_year_id=' . $this->school_year_id;
    }

    public function setCityOfResidence($city)
    {
        $this->city_of_residence = cms_content::sanitizeText($city);
        $this->_updateFields['city_of_residence'] = 'city_of_residence="' . core_db::escape($this->city_of_residence) . '"';
    }

    public function setReferredBy($referredBy)
    {
        $this->referred_by = cms_content::sanitizeText($referredBy);
        $this->_updateFields['referred_by'] = 'referred_by="' . core_db::escape($this->referred_by) . '"';
    }

    public function setMidYear($isMidyear)
    {
        $this->midyear_application = cms_content::sanitizeText($isMidyear);
        $this->_updateFields['midyear_application'] = 'midyear_application="' . core_db::escape($this->midyear_application) . '"';
    }
    public function submit($agrees_to_policies)
    {
        $this->agrees_to_policies = (int) (bool) $agrees_to_policies;
        $this->_updateFields['agrees_to_policies'] = 'agrees_to_policies="' . $this->agrees_to_policies . '"';
        if (!$this->agrees_to_policies) {
            return false;
        }
        $this->setDateSubmittedToToday();
        $this->setStatus(self::STATUS_SUBMITTED);
        $saved = $this->save();
        
        $email = new core_emailservice();
        $email->send(
            array(core_setting::getSiteEmail()->getValue()),
            'New Application Submitted',
            '<p>' . $this->getStudent()->getParent() . ' has submitted an application for ' . $this->getStudent() . '</p>
            <p>You can manage applications under Admin>Applications</p>',
            null,
            explode(",", core_setting::get("adminEmail", '')->getValue()),
            [core_setting::getSiteEmail()->getValue()]
        );

        return $saved;
    }

    public function setStatus($status)
    {
        if ($status == $this->getStatus()) {
            return TRUE;
        }
        if (!in_array($status, self::getAvailableStatuses())) {
            return false;
        }
        $this->status = $status;
        $this->_updateFields['status'] = '`status`="' . $this->status . '"';
    }

    public function setDateSubmittedToToday()
    {
        $this->date_submitted = date('Y-m-d H:i:s');
        $this->_updateFields['date_submitted'] = 'date_submitted="' . $this->date_submitted . '"';
    }

    public function accept($doMidYear)
    {
        $this->date_accepted = date('Y-m-d H:i:s');
        $this->_updateFields['date_accepted'] = 'date_accepted="' . $this->date_accepted . '"';
        $this->_updateFields['midyear_application'] = 'midyear_application=' . ($doMidYear ? 1 : 0);
        $this->setStatus(self::STATUS_ACCEPTED);
        $this->save();
        $student = $this->getStudent();
        $student->setStatus(NULL, $this->getSchoolYear(true));
        $packet = mth_packet::create($student);
        if ($packet->getDeadline() < time()) {
            $packet->resetDeadline();
            $packet->setStatus(mth_packet::STATUS_STARTED);
        }
        $parent = $student->getParent();
        $emailContent = $this->getAcceptanceEmailContent($packet);
        $emailSubject = str_replace(
           array(
              '[PARENT]',
              '[STUDENT]',
              '[YEAR]',
              '[DEADLINE]'
           ),
           array(
              $this->getStudent()->getParent()->getPreferredFirstName(),
              $this->getStudent()->getPreferredFirstName(),
              $this->getSchoolYear(),
              $packet->getDeadline('F j, Y')
           ),
           core_setting::get('applicationAcceptedEmailSubject', 'Applications')
        );
        if ($emailContent) {
            $email = new core_emailservice();
            return $email->send(
                array($parent->getEmail()),
                $emailSubject,
                $emailContent,
                null,
                explode(",", core_setting::get("adminEmail", '')->getValue()),
                [core_setting::getSiteEmail()->getValue()]
            );
        }
        error_log('applicationAcceptedEmailContent not set');
        return false;
    }

    public function getAcceptanceEmailContent(mth_packet $packet)
    {
        return str_replace(
            array(
                '[PARENT]',
                '[STUDENT]',
                '[YEAR]',
                '[DEADLINE]'
            ),
            array(
                $this->getStudent()->getParent()->getPreferredFirstName(),
                $this->getStudent()->getPreferredFirstName(),
                $this->getSchoolYear(),
                $packet->getDeadline('F j, Y')
            ),
            core_setting::get('applicationAcceptedEmailContent', 'Applications')
        );
    }

    public function save()
    {
        if (empty($this->_updateFields)) {
            return true;
        }
        return core_db::runQuery('UPDATE mth_application
                              SET ' . implode(',', $this->_updateFields) . ' 
                              WHERE application_id=' . $this->getID());
    }

    public function __destruct()
    {
        $this->save();
    }

    public function getID()
    {
        return (int) $this->application_id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isSubmitted()
    {
        return $this->status != self::STATUS_STARTED;
    }

    public function isAccepted()
    {
        return $this->status == self::STATUS_ACCEPTED;
    }

    public function isHidden()
    {
        return $this->hidden;
    }
    public function getSchoolYearID()
    {
        return (int) $this->school_year_id;
    }

    public function getSchoolYear($getObject = false)
    {
        if (!$this->school_year_id || !($schoolYear = mth_schoolYear::getByID($this->school_year_id))) {
            return false;
        }
        if ($getObject) {
            return $schoolYear;
        }
        return (string) $schoolYear;
    }

    public function getCityOfResidence()
    {
        return $this->city_of_residence;
    }

    public function getReferredBy()
    {
        return $this->referred_by;
    }

    public function getDateSubmitted($format = NULL)
    {
        if (!$this->isSubmitted() || empty($this->date_submitted)) {
            return false;
        }
        if ($format) {
            return date($format, strtotime($this->date_submitted));
        }
        return strtotime($this->date_submitted);
    }

    public function getDateAccepted($format = NULL)
    {
        if (!$this->isAccepted() || empty($this->date_accepted)) {
            return false;
        }
        if ($format) {
            return date($format, strtotime($this->date_accepted));
        }
        return strtotime($this->date_accepted);
    }

    public function getAcceptedByUserID()
    {
        return (int) $this->accepted_by_user_id;
    }

    public function getStudentID()
    {
        return (int) $this->student_id;
    }

    /**
     *
     * @return mth_student
     */
    public function getStudent()
    {
        return mth_student::getByStudentID($this->getStudentID());
    }

    public function getMidyearApplication()
    {
        return (int) $this->midyear_application;
    }

    public function delete()
    {
        if (($student = $this->getStudent())) {
            $student->delete(); //attempt to delete student
        }
        return core_db::runQuery('UPDATE mth_application SET status="' . self::STATUS_DELETED . '" WHERE application_id=' . $this->getID());
    }

    public function hideSiblings($currentyear = true)
    {
        if (($student = $this->getStudent()) && ($parent = $student->getParent())) {
            $applications = [];
            foreach ($parent->getStudents() as $student) {
                if (($application = mth_application::getStudentApplication($student))
                    && $application->getStatus() == self::STATUS_SUBMITTED
                    && !$application->isHidden()
                    && ($currentyear && $application->getSchoolYearID() == $this->getSchoolYearID())
                ) {
                    $applications[] = $application->getID();
                }
            }

            return count($applications) > 0 ? core_db::runQuery('UPDATE mth_application SET hidden=1 WHERE application_id in(' . (implode(',', $applications)) . ')') : false;
        }

        return false;
    }

    public function unhideSiblings($currentyear = true)
    {
        if (($student = $this->getStudent()) && ($parent = $student->getParent())) {
            $applications = [];
            foreach ($parent->getStudents() as $student) {
                if (($application = mth_application::getStudentApplication($student))
                    &&  $application->getStatus() == self::STATUS_SUBMITTED
                    && $application->isHidden()
                    && ($currentyear && $application->getSchoolYearID() == $this->getSchoolYearID())
                ) {
                    $applications[] = $application->getID();
                }
            }

            return count($applications) > 0 ? core_db::runQuery('UPDATE mth_application SET hidden=0 WHERE application_id in(' . (implode(',', $applications)) . ')') : false;
        }

        return false;
    }

    public static function deleteStudentApplications(mth_student $student)
    {
        return core_db::runQuery('UPDATE mth_application SET status="' . self::STATUS_DELETED . '" WHERE student_id=' . $student->getID());
    }

    public function __toString()
    {
        if ($this->isAccepted()) {
            return 'Accepted: ' . $this->getDateAccepted('M. j, Y');
        } elseif ($this->isSubmitted()) {
            return 'Submitted: ' . $this->getDateSubmitted('M. j, Y') . ($this->getStatus() != self::STATUS_SUBMITTED ? '(' . $this->getStatus() . ')' : '');
        } else {
            return $this->getStatus();
        }
    }

    public static function getCount($status = self::STATUS_SUBMITTED)
    {
        if (!in_array($status, self::getAvailableStatuses())) {
            return FALSE;
        }
        return core_db::runGetValue('SELECT COUNT(a.application_id) 
                                  FROM mth_application AS a
                                    INNER JOIN mth_student AS s ON s.student_id=a.student_id
                                  WHERE a.status="' . core_db::escape($status) . '"');
    }

    public static function getHiddenCount($status = self::STATUS_SUBMITTED)
    {
        if (!in_array($status, self::getAvailableStatuses())) {
            return FALSE;
        }
        return core_db::runGetValue('SELECT COUNT(a.application_id) 
                                  FROM mth_application AS a
                                    INNER JOIN mth_student AS s ON s.student_id=a.student_id
                                  WHERE a.hidden=1 and a.status="' . core_db::escape($status) . '"');
    }

    public function isReturning(mth_student $student = null)
    {
       if ($student === null) {
          $student = $this->getStudent();
       }
       //TODO: Gaurd clause to account for falsey student
       return (($this->school_year_id == mth_schoolYear::getCurrent()->getID() && (($student->isActive() && $student->getReenrolled($this->school_year_id))))
          ||
          ($this->school_year_id <= mth_schoolYear::getPrevious()->getID() && $student->isActive(mth_schoolYear::getPrevious()))
       );
    }
}
