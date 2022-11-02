<?php

/**
 * Description of transitioned
 *
 * @author Rex
 */
class mth_transitioned extends core_model
{
  ################################################ DATABASE FIELDS ############################################

  protected $transition_id;
  protected $student_id;
  protected $school_year_id;
  protected $reason;
  protected $new_school_name;
  protected $new_school_address;
  protected $sig_file_id;
  protected $datetime;
  protected $status;

  # OTHER MEMBERS #

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

  public static function reasons()
  {
    return self::$reasons;
  }

  protected static $cache = array();

  ################################################ GET METHODS ############################################

  public function id()
  {
    return (int) $this->transition_id;
  }

  /**
   *
   * @return mth_student
   */
  public function student()
  {
    return mth_student::getByStudentID($this->student_id);
  }

  /**
   *
   * @return mth_schoolYear
   */
  public function school_year()
  {
    return mth_schoolYear::getByID($this->school_year_id);
  }

  /**
   *
   * @return mth_schoolYear
   */
  public function letter_year()
  {
    if (NULL === ($letter_year = &self::$cache['letter_year'][$this->id()])) {
      $letter_year = self::letter_year_calculator($this->student(), $this->school_year());
    }
    return $letter_year;
  }

  public static function letter_year_calculator(mth_student $student, mth_schoolYear $year_transitioned)
  {

    if (
      $student->getStatusDate($year_transitioned) < strtotime('+9 days', $year_transitioned->getDateBegin())
      && ($previous_year = $year_transitioned->getPreviousYear())
      && $student->getSchoolOfEnrollment(true, $previous_year) != \mth\student\SchoolOfEnrollment::Unassigned
    ) {
      $letter_year = $previous_year;
    } else {
      $letter_year = $year_transitioned;
    }
    return $letter_year;
  }

  public function reason($returnText = true)
  {
    if ($returnText && isset(self::$reasons[$this->reason])) {
      return self::$reasons[$this->reason];
    }
    return $this->reason;
  }

  public function new_school_name()
  {
    return $this->new_school_name;
  }

  public function new_school_address($html = true)
  {
    return $html ? $this->new_school_address : strip_tags($this->new_school_address);
  }

  public function sig_file_hash()
  {
    if (($sig_file = mth_file::get($this->sig_file_id))) {
      return $sig_file->hash();
    }
    return null;
  }

  public function datetime($format = NULL)
  {
    return self::getDate($this->datetime, $format);
  }

  public function notified()
  {
    return $this->status >= self::STATUS_NOTIFIED;
  }

  public function submitted()
  {
    return $this->status >= self::STATUS_SUBMITTED;
  }

  public function sent_to_dropbox()
  {
    return $this->status == self::STATUS_SENT_TO_DROPBOX;
  }

  public function effectiveWithdrawalDate($format = NULL)
  {
    if (
      !($student = $this->student())
      || !($year = $this->school_year())
      || !($next_year = mth_schoolYear::getNext())
    ) {
      return false;
    }

    if ($year->getID() == $next_year->getID()) {
      $prev_year = $year->getPrevious();
      $effective_date = strtotime('+1 day', $prev_year->getDateEnd());
      if ($format) {
        return date($format, $effective_date);
      }
      return $effective_date;
    }

    return $student->getStatusDate($year, $format);
  }

  ################################################ SET METHODS ############################################

  public function set_reason($reasonNum)
  {
    if (isset(self::$reasons[$reasonNum])) {
      $this->set('reason', (int) $reasonNum);
    }
  }

  public function set_new_school_name($name)
  {
    $this->set('new_school_name', req_sanitize::txt($name));
  }

  public function set_new_school_address($address)
  {
    $this->set('new_school_address', nl2br(req_sanitize::multi_txt($address)));
  }

  public function save_sig_file($svgXMLbase64content)
  {
    if (($sigFile = mth_file::saveFile('sig.svg', base64_decode($svgXMLbase64content), 'image/svg+xml'))) {
      $this->set('sig_file_id', $sigFile->id());
    }
  }

  public function setSigFile(mth_file $file)
  {
    $this->set('sig_file_id', $file->id());
  }

  ################################################ SAVE METHODS ############################################

  public function save()
  {

    if (!$this->transition_id) {
      if (!$this->student_id || !$this->school_year_id) {
        return false;
      }
      core_db::runQuery('INSERT INTO mth_transitioned (student_id) VALUES (' . (int) $this->student_id . ')');
      $this->transition_id = core_db::getInsertID();
    }
    return parent::runUpdateQuery('mth_transitioned', 'transition_id=' . $this->id());
  }

  public function submit()
  {
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

  public function notify()
  {
    if (!$this->submitted() && self::sendEmail($this->student())) {
      $this->set('status', self::STATUS_NOTIFIED);
      return $this->save();
    }
    return false;
  }

  public function sendUndeclaredToDropbox($alternate_dir = null, mth_schoolYear $alt_year_dir = null) {
      if (
          !$this->student()
          || !($year = $this->school_year())
          || !($affidavit =  mth_views_transitioned::getNewAffidavit($this))
      ) {
          return false;
      }
      if (!($school = $this->student()->getSchoolOfEnrollment(false, $year))) {
          $school = 'eSchool';
      }
      $address = $this->student()->getParent()->getAddress();
      $inputState = $address ? $address->getState() : 'UT';
  
      if (
      mth_dropbox::uploadFileFromString(
          '/Withdrawal Letters/' . ($alternate_dir ? $alternate_dir . '/' : '')
          . ($alt_year_dir ? $alt_year_dir : $this->school_year()) .'/'. 
          ($inputState == 'OR' ? 'Oregon/' : '').'Undeclared/' . $school . '/'
          . $this->student()->getName(true) . ' (' . $this->student_id . ').pdf',
          $affidavit
      )
      ) {
          $this->set('status', self::STATUS_SENT_TO_DROPBOX);
          $this->save();
          return true;
      }
      foreach (mth_dropbox::errors() as $error) {
          error_log($error);
      }
      return false;
  }

  public function sendToDropbox($alternate_dir = null, mth_schoolYear $alt_year_dir = null, $inputState = null)
  {
    if (
      !$this->student() || !$this->school_year()
      || !($letter_year = $this->school_year())
      || !($pdf = mth_views_transitioned::getWithdrawalLetter($this))
      || !($affidavit =  mth_views_transitioned::getNewAffidavit($this))
    ) {
      return false;
    }
    if (!($school = $this->student()->getSchoolOfEnrollment(false, $letter_year))) {
      $school = 'eSchool';
    }
    if (
      mth_dropbox::uploadFileFromString(
        '/Transitioned/' .
          ($alternate_dir ? $alternate_dir . '/' : '') .
          ($alt_year_dir ? $alt_year_dir : $this->school_year()) . '/' . 
          ($inputState == 'OR' ? 'Oregon/' : '').$school . '/' .
          $this->student()->getName(true) . ' (' . $this->student_id . ')/withdrawal_letter.pdf',
        $pdf
      ) &&
      mth_dropbox::uploadFileFromString(
        '/Transitioned/' .
          ($alternate_dir ? $alternate_dir . '/' : '') .
          ($alt_year_dir ? $alt_year_dir : $this->school_year()) . '/' . 
          ($inputState == 'OR' ? 'Oregon/' : '').$school . '/' .
          $this->student()->getName(true) . ' (' . $this->student_id . ')/Home_School_Affidavit.pdf',
        $affidavit
      )
    ) {
      $this->set('status', self::STATUS_SENT_TO_DROPBOX);
      $this->save();
      return true;
    }
    foreach (mth_dropbox::errors() as $error) {
      error_log($error);
    }
    return false;
  }

  ################################################ STATIC METHODS ############################################

  protected function __construct()
  {
  }


  /**
   *
   * @param int $transition_id
   * @return mth_transitioned
   */
  public static function get($transition_id)
  {
    if (($transition = &self::$cache['get'][$transition_id]) === NULL) {
      $transition = core_db::runGetObject('SELECT * FROM mth_transitioned WHERE transition_id=' . (int) $transition_id, 'mth_transitioned');
    }
    return $transition;
  }

  /**
   *
   * @param int $student_id
   * @param int|null $school_year_id
   * @return mth_transitioned
   */
  public static function getByStudent($student_id, $school_year_id = NULL)
  {
    if (($transition = &self::$cache['getByStudent'][$student_id][$school_year_id]) === NULL) {
      $transition = core_db::runGetObject('SELECT * FROM mth_transitioned AS w
                                            LEFT JOIN mth_schoolYear AS y ON w.school_year_id=y.school_year_id
                                            WHERE w.student_id=' . (int) $student_id . '
                                              ' . ($school_year_id ? 'AND w.school_year_id=' . (int) $school_year_id : '') . '
                                            ORDER BY y.date_begin DESC, w.`datetime` DESC LIMIT 1', 'mth_transitioned');
    }
    return $transition;
  }

  public static function getOrCreate(mth_student $student, mth_schoolYear $schoolYear, $temp_record = false)
  {
    if (!$temp_record && ($transition = self::getByStudent($student->getID(), $schoolYear->getID()))) {
      return $transition;
    }
    $transition = new mth_transitioned();
    $transition->set('student_id', $student->getID());
    $transition->set('school_year_id', $schoolYear->getID());
    $transition->save();
    return $transition;
  }

  public static function repairMisConfigured()
  {
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
    $result = core_db::runQuery('SELECT w.student_id FROM mth_transitioned AS w
                                  INNER JOIN mth_student_status AS ss1
                                    ON ss1.student_id=w.student_id
                                      AND ss1.school_year_id=w.school_year_id
                                      AND ss1.status!=' . mth_student::STATUS_TRANSITIONED . '
                                  INNER JOIN mth_student_status AS ss2
                                    ON ss2.student_id=w.student_id
                                      AND ss2.school_year_id=' . $y2 . '
                                      AND ss2.status=' . mth_student::STATUS_TRANSITIONED . '
                                  WHERE w.school_year_id=' . $y1);

    if ($result->num_rows < 1) {
      return;
    }

    $student_ids = array();
    while ($row = $result->fetch_assoc()) {
      $student_ids[$row['student_id']] = $row['student_id'];
    }

    $result->free_result();

    core_db::runQuery('DELETE FROM mth_transitioned 
                        WHERE student_id IN (' . implode(',', $student_ids) . ') 
                          AND school_year_id=' . $y2);
    core_db::runQuery('UPDATE mth_transitioned SET school_year_id=' . $y2 . '
                        WHERE student_id IN (' . implode(',', $student_ids) . ') 
                          AND school_year_id=' . $y1);
    error_log('These student transition letter were repaired: ' . implode(',', $student_ids));
  }
}
