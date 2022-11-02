<?php

/**
 * Description of withdrawal
 *
 * @author abe
 */
class mth_withdrawal extends core_model {
    ################################################ DATABASE FIELDS ############################################

    protected $withdrawal_id;
    protected $student_id;
    protected $school_year_id;
    protected $reason;
    protected $new_school_name;
    protected $new_school_address;
    protected $sig_file_id;
    protected $datetime;
    protected $status;
    protected $active;
    protected $withdrawal_date;
    protected $reason_txt;
    protected $intent_reenroll_action;
    protected $effective_date;
    protected $created_by;
    protected $automatically_withdrawn;

    # OTHER MEMBERS #

    protected $temporary;

    ################################################ STATIC MEMBERS ############################################

    const STATUS_NOTIFIED = 1;
    const STATUS_SUBMITTED = 2;
    const STATUS_SENT_TO_DROPBOX = 3;

    const REASON_GRAD = 1;
    const REASON_TRANS_LOCAL = 2;
    const REASON_TRANS_ONLINE = 3;
    const REASON_TRANS_HOME = 4;

    protected static $reasons = array(
        self::REASON_GRAD => 'Graduating',
        self::REASON_TRANS_LOCAL => 'Transferring to a local district school, physical charter school or private school',
        self::REASON_TRANS_ONLINE => 'Transferring to another online public school',
        self::REASON_TRANS_HOME => 'Transferring to independent homeschooling'
    );

    public static function reasons() {
        return self::$reasons;
    }

    protected static $cache = array();

    ################################################ GET METHODS ############################################

    public function id() {
        return (int) $this->withdrawal_id;
    }

    /**
     * @return mth_withdrawal created_by 
     */
    public function createdBy() {
        return $this->created_by;
    }

    public function getCreatedByUser() {
        return core_user::getUserById($this->created_by);
    }

    /**
     *
     * @return mth_student
     */
    public function student() {
        return mth_student::getByStudentID($this->student_id);
    }

    /**
     *
     * @return mth_schoolYear
     */
    public function school_year() {
        return mth_schoolYear::getByID($this->school_year_id);
    }

    /**
     *
     * @return mth_schoolYear
     */
    public function letter_year() {
        if ($this->withdrawal_date) {
            return mth_schoolYear::getPrevious();
        }

        if (NULL === ($letter_year = &self::$cache['letter_year'][$this->id()])) {
            $letter_year = self::letter_year_calculator($this->student(), $this->school_year());
        }
        return $letter_year;
    }

    public static function letter_year_calculator(mth_student $student, mth_schoolYear $year_withdrawn) {   //old condition
        //($student->getStatusDate($year_withdrawn) < strtotime('+9 days',$year_withdrawn->getDateBegin())
        if (
            $student->getStatusDate($year_withdrawn) < $year_withdrawn->getDateBegin()
            && ($previous_year = $year_withdrawn->getPreviousYear())
            && $student->getSchoolOfEnrollment(true, $previous_year) != \mth\student\SchoolOfEnrollment::Unassigned
        ) {
            $letter_year = $previous_year;
        } else {
            $letter_year = $year_withdrawn;
        }
        return $letter_year;
    }

    public function reason($returnText = true) {
        if ($returnText && isset(self::$reasons[$this->reason])) {
            return self::$reasons[$this->reason];
        }
        return $this->reason;
    }

    public function reason_txt() {
        return $this->reason_txt;
    }

    public function reenroll_action($format = NULL) {
        return self::getDate($this->intent_reenroll_action, $format);
    }

    public function effective_date($format = NULL) {
        return self::getDate($this->effective_date, $format);
    }

    public function set_reenroll_action() {
        $this->set('intent_reenroll_action', date('Y-m-d H:i:s'));
    }

    public function set_effective_date($setToNow = false) {
        $currentDate = date('Y-m-d H:i:s');

        if ($setToNow) {
            $this->set('effective_date', $currentDate);
            return;
        }

        // Calculate effective withdrawal date for parents responding "NO" to re-enroll:
        // If before 6/01 in current school year, set to 6/01
        $minimumEffectiveDate = self::getDate(strtotime('06/01/' . date('Y')), 'Y-m-d H:i:s');
        if (!$setToNow && $currentDate < $minimumEffectiveDate) {
            $this->set('effective_date', $minimumEffectiveDate);
            return;
        }

        // If after, set to NOW
        $this->set('effective_date', $currentDate);
    }

    public function new_school_name() {
        return $this->new_school_name;
    }

    public function new_school_address($html = true) {
        return $html ? $this->new_school_address : strip_tags($this->new_school_address);
    }

    public function sig_file_hash() {
        if (($sig_file = mth_file::get($this->sig_file_id))) {
            return $sig_file->hash();
        }
        return null;
    }

    public function datetime($format = NULL) {
        return self::getDate($this->datetime, $format);
    }

    public function notified() {
        return $this->status >= self::STATUS_NOTIFIED;
    }

    public function isStatusNotified() {
        return $this->status == self::STATUS_NOTIFIED;
    }

    public function isActive() {
        return $this->active;
    }

    public function isUndeclared() {
        return $this->status == 0 && $this->isActive();
    }

    public function submitted() {
        return $this->status >= self::STATUS_SUBMITTED;
    }

    public function isSubmittedOnly() {
        return $this->status == self::STATUS_SUBMITTED;
    }

    public function sent_to_dropbox() {
        return $this->status == self::STATUS_SENT_TO_DROPBOX;
    }

    public function effectiveWithdrawalDate($format = NULL) {
        if (
            !($student = $this->student())
            || !($year = $this->school_year())
            || !($next_year = mth_schoolYear::getNext())
        ) {
            return false;
        }

        if ($this->withdrawal_date) {
            return $format ? date($format, $this->withdrawal_date) : $this->withdrawal_date;
        }

        if ($year->getID() == $next_year->getID()) {
            $prev_year = $year->getPrevious();
            $effective_date = strtotime('+1 day', $prev_year->getDateEnd());
            if ($format) {
                return date($format, $effective_date);
            }
            return $effective_date;
        }

        return $student->getWithdrawalOrStatusDate($year, $format);
    }

    public function sendEmailConfermation() {
        if (
            !($student = $this->student())
            || !($parent = $student->getParent())
            || !($pdf = mth_views_withdrawal::getPDFcontent($this))
            || !(core_setting::get('withdrawalConfirmationEmailSubject', 'Withdrawals'))
        ) {
            return false;
        }
        $email = new core_email(
            array($parent),
            core_setting::get('withdrawalConfirmationEmailSubject', 'Withdrawals')->getValue(),
            str_replace(
                array(
                    '[PARENT_FIRST]',
                    '[STUDENT_FIRST]'
                ),
                array(
                    $parent->getPreferredFirstName(),
                    $student->getPreferredFirstName()
                ),
                core_setting::get('withdrawalConfirmationEmailContent', 'Withdrawals')->getValue()
            )
        );
        $email->addStringAttachment($pdf, 'Withdrawal_Letter.pdf');

        if (($email = $email->preSend()) === false) {
            // error_log($email->ErrorInfo);
            return false;
        }

        $raw = $email->getSentMIMEMessage();
        $mail = new core_emailservice();
        $mail->enableTracking(true);
        return $mail->sendRaw($raw);
    }

    ################################################ SET METHODS ############################################

    public function set_reason($reasonNum) {
        if (isset(self::$reasons[$reasonNum])) {
            $this->set('reason', (int) $reasonNum);
        }
    }

    public function setAutomaticallyWithdrawn($value) {
        $this->set('automatically_withdrawn', $value);
    }

    public function setStudentId($value) {
        $this->set('student_id', $value);
    }

    public function setActive($value) {
        $this->set('active', $value);
    }

    public function setSchoolYearId($value) {
        $this->set('school_year_id', $value);
    }

    public function set_reason_txt($txt) {
        $this->set('reason_txt', $txt);
    }

    public function set_new_school_name($name) {
        $this->set('new_school_name', req_sanitize::txt($name));
    }

    public function set_withdrawal_date($date) {
        $this->withdrawal_date = $date;
    }

    public function set_new_school_address($address) {
        $this->set('new_school_address', nl2br(req_sanitize::multi_txt($address)));
    }

    public function save_sig_file($svgXMLbase64content) {
        if (($sigFile = mth_file::saveFile('sig.svg', base64_decode($svgXMLbase64content), 'image/svg+xml'))) {
            $this->set('sig_file_id', $sigFile->id());
        }
    }

    public function setSigFile(mth_file $file) {
        $this->set('sig_file_id', $file->id());
    }

    ################################################ SAVE METHODS ############################################

    public function save() {
        if ($this->temporary) {
            return null;
        }
        if (!$this->withdrawal_id) {
            if (!$this->student_id || !$this->school_year_id) {
                return false;
            }
            core_db::runQuery('INSERT INTO mth_withdrawal (student_id) VALUES (' . (int) $this->student_id . ')');
            $this->withdrawal_id = core_db::getInsertID();
        }
        return parent::runUpdateQuery('mth_withdrawal', 'withdrawal_id=' . $this->id());
    }

    public function submit() {
        $valid = false;
        if ($this->reason && $this->sig_file_id) {
            $valid = true;
        }
        if (
            in_array($this->reason, array(self::REASON_TRANS_LOCAL, self::REASON_TRANS_ONLINE))
            && (!$this->new_school_name || !$this->new_school_address)
        ) {
            $valid = false;
        }
        if ($valid) {
            $this->set('status', self::STATUS_SUBMITTED);
            $this->set('datetime', date('Y-m-d H:i:s'));
        }
        return $this->save() && $valid;
    }

    public function notify() {
        if (!$this->submitted() && self::sendEmail($this->student())) {
            $this->set('status', self::STATUS_NOTIFIED);
            return $this->save();
        }
        return false;
    }

    public function sendToDropbox($alternate_dir = null, mth_schoolYear $alt_year_dir = null, $inputState = null) {
        if (
            !$this->student() || !$this->letter_year()
            || !($letter_year = $this->letter_year())
            || !($pdf = mth_views_withdrawal::getPDFcontent($this))
        ) {
            error_log('Error sendToDropbox');
            return false;
        }
        if (!($school = $this->student()->getWithdrawalSOE(false, $letter_year))) {
            $school = 'eSchool';
        }
        if (mth_dropbox::uploadFileFromString(
            '/Withdrawal Letters/' . ($alternate_dir ? $alternate_dir . '/' : '') . ($alt_year_dir ? $alt_year_dir : $this->school_year()) . '/' .
            ($inputState == 'OR' ? 'Oregon/' : ''). $school . '/' .
            $this->student()->getName(true) . ' (' . $this->student_id . ').pdf',
            $pdf
        )) {
            $this->set('status', self::STATUS_SENT_TO_DROPBOX);

            // Establish effective withdrawal date if not already set
            if ($this->effective_date() === null) {
                $this->set_effective_date(true);
            }

            $this->save();
            return true;
        }
        foreach (mth_dropbox::errors() as $error) {
            error_log($error);
        }
        return false;
    }

    ################################################ STATIC METHODS ############################################

    public function __construct() { }


    /**
     *
     * @param int $withdrawal_id
     * @return mth_withdrawal
     */
    public static function get($withdrawal_id) {
        if (($withdrawal = &self::$cache['get'][$withdrawal_id]) === NULL) {
            $withdrawal = core_db::runGetObject('SELECT * FROM mth_withdrawal WHERE withdrawal_id=' . (int) $withdrawal_id, 'mth_withdrawal');
        }
        return $withdrawal;
    }

    /**
     *
     * @param int $student_id
     * @param int|null $school_year_id
     * @return mth_withdrawal
     */
    public static function getByStudent($student_id, $school_year_id = NULL) {
        if (($withdrawal = &self::$cache['getByStudent'][$student_id][$school_year_id]) === NULL) {
            $withdrawal = core_db::runGetObject('SELECT * FROM mth_withdrawal AS w
                                            LEFT JOIN mth_schoolyear AS y ON w.school_year_id=y.school_year_id
                                            WHERE w.student_id=' . (int) $student_id . '
                                              ' . ($school_year_id ? 'AND w.school_year_id=' . (int) $school_year_id : '') . '
                                            ORDER BY y.date_begin DESC, w.`datetime` DESC LIMIT 1', 'mth_withdrawal');
        }
        return $withdrawal;
    }

    public static function getLatestNotifiedByStudent($student_id) {
        if (($withdrawal = &self::$cache['getLatestNotifiedByStudent'][$student_id]) === NULL) {
            $withdrawal = core_db::runGetObject('SELECT * FROM mth_withdrawal AS w
                                            LEFT JOIN mth_schoolYear AS y ON w.school_year_id=y.school_year_id
                                            WHERE w.student_id=' . (int) $student_id . ' AND w.status=1
                                            ORDER BY y.date_begin DESC, w.`datetime` DESC LIMIT 1', 'mth_withdrawal');
        }
        return $withdrawal;
    }

    public static function getStudentAutomaticallyWithdrawnByYearId($year_id) {
        if (($withdrawal = &self::$cache['getStudentAutomaticallyWithdrawnByYearId'][$year_id]) === NULL) {
            $withdrawal = core_db::runGetObjects('SELECT *, mp.first_name, mp.last_name FROM mth_student AS ms 
                                            LEFT JOIN mth_person as mp ON ms.person_id = mp.person_id 
                                            LEFT JOIN mth_withdrawal AS w ON ms.student_id = w.student_id
                                            LEFT JOIN mth_schoolYear AS y ON w.school_year_id=y.school_year_id
                                            WHERE w.school_year_id=' . (int) $year_id . ' AND w.automatically_withdrawn=1 
                                            ORDER BY y.date_begin DESC, w.`datetime` DESC', 'mth_student');
        }
        return $withdrawal;
    }

    public static function getOrCreate(mth_student $student, mth_schoolYear $schoolYear, $temp_record = false, $created_by = 0) {
        if (!$temp_record && ($withdrawal = self::getByStudent($student->getID(), $schoolYear->getID()))) {
            return $withdrawal;
        }
        
        $withdrawal = new mth_withdrawal();
        $withdrawal->set('student_id', $student->getID());
        $withdrawal->set('school_year_id', $schoolYear->getID());
        if ($created_by) {
            $withdrawal->set('created_by', $created_by);
        }
        $withdrawal->temporary = (bool) $temp_record;
        $withdrawal->save();
        return $withdrawal;
    }

    public function reset() {
        $this->set('reason', null);
        $this->set('new_school_name', null);
        $this->set('new_school_address', null);
        $this->set('sig_file_id', null);
        $this->set('datetime', null);
        $this->set('status', 0);
        return $this->save();
    }

    public static function repairMisConfigured() {
        $p_year = mth_schoolYear::getPrevious();
        $c_year = mth_schoolYear::getCurrent();
        $n_year = mth_schoolYear::getNext();
        if ($c_year->getID() == $n_year->getID()) {
            $y1 = $p_year->getID();
            $y2 = $c_year->getID();
        } else {
            $y1 = $c_year->getID();
            $y2 = $n_year->getID();
        }
        $result = core_db::runQuery('SELECT w.student_id FROM mth_withdrawal AS w
                                  INNER JOIN mth_student_status AS ss1
                                    ON ss1.student_id=w.student_id
                                      AND ss1.school_year_id=w.school_year_id
                                      AND ss1.status!=' . mth_student::STATUS_WITHDRAW . '
                                  INNER JOIN mth_student_status AS ss2
                                    ON ss2.student_id=w.student_id
                                      AND ss2.school_year_id=' . $y2 . '
                                      AND ss2.status=' . mth_student::STATUS_WITHDRAW . '
                                  WHERE w.school_year_id=' . $y1);

        if ($result->num_rows < 1) {
            return;
        }

        $student_ids = array();
        while ($row = $result->fetch_assoc()) {
            $student_ids[$row['student_id']] = $row['student_id'];
        }

        $result->free_result();

        core_db::runQuery('DELETE FROM mth_withdrawal 
                        WHERE student_id IN (' . implode(',', $student_ids) . ') 
                          AND school_year_id=' . $y2);
        core_db::runQuery('UPDATE mth_withdrawal SET school_year_id=' . $y2 . '
                        WHERE student_id IN (' . implode(',', $student_ids) . ') 
                          AND school_year_id=' . $y1);
        error_log('These student withdrawal letter were repaired: ' . implode(',', $student_ids));
    }

    public static function initEmailContent($subject, $content) {
        core_setting::init(
            'withdrawalNotificationEmailSubject',
            'Withdrawals',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            ' Withdrawal Email Subject',
            '<p>Email sent with link to form for a withdrawan student.</p>'
        );
        core_setting::init(
            'withdrawalNotificationEmailContent',
            'Withdrawals',
            $content,
            core_setting::TYPE_HTML,
            true,
            'Withdrawal Email Content',
            '<p>[LINK] - the link to the form.<br> 
              [PARENT_FIRST] - the parent\'s first name<br>
              [STUDENT_FIRST] - the student\'s preferred first name</p>'
        );
    }

    public static function setActiveValue($student_id, $year_id, $value, $reset = false) {
       return core_db::runQuery('UPDATE mth_withdrawal SET active=' . $value . '
                        ' . ($reset ? ', status = 0 ' : '') . '
                        WHERE student_id = ' . $student_id . ' 
                          AND school_year_id=' . $year_id);
    }

    protected static function sendEmail(mth_student $student) {
        if (
            !($parent = $student->getParent())
            || !(core_setting::get('withdrawalNotificationEmailSubject', 'Withdrawals'))
        ) {
            return false;
        }
        $link = (core_secure::usingSSL() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/student/' . $student->getSlug() . '/withdrawal';
        $email = new core_emailservice();

        return $email->send(
            [$parent->getEmail()],
            core_setting::get('withdrawalNotificationEmailSubject', 'Withdrawals')->getValue(),
            str_replace(
                [
                    '[LINK]',
                    '[PARENT_FIRST]',
                    '[STUDENT_FIRST]'
                ],
                [
                    '<a href="' . $link . '">' . $link . '</a>',
                    $parent->getPreferredFirstName(),
                    $student->getPreferredFirstName()
                ],
                core_setting::get('withdrawalNotificationEmailContent', 'Withdrawals')->getValue()
            )
        );
    }

    public static function initConfirmationEmailContent($subject, $content) {
        core_setting::init(
            'withdrawalConfirmationEmailSubject',
            'Withdrawals',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            ' Withdrawal Confirmation Email Subject',
            '<p>Email sent with submitted withdrawal form in PDF format attached</p>'
        );
        core_setting::init(
            'withdrawalConfirmationEmailContent',
            'Withdrawals',
            $content,
            core_setting::TYPE_HTML,
            true,
            'Withdrawal Confirmation Email Content',
            '<p>[PARENT_FIRST] - the parent\'s first name<br>
              [STUDENT_FIRST] - the student\'s first preferred name</p>'
        );
    }

    public static function delete($studentID, $yearID) {
       if(!$studentID || !$yearID) {
          return core_notify::addError('No student or school year found.');
       }
       core_db::runQuery('DELETE FROM mth_withdrawal
                       WHERE student_id=' . $studentID . '
                       AND school_year_id=' . $yearID);
   }
}
