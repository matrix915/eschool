<?php

/**
 * schoolYear
 *
 * @author abe
 */
class mth_schoolYear extends core_model
{
  protected $school_year_id;
  protected $date_begin;
  protected $date_end;
  protected $date_reg_open;
  protected $date_reg_close;
  protected $reimburse_open;
  protected $reimburse_tech_open;
  protected $reimburse_close;
  protected $direct_order_open;
  protected $direct_order_tech_enabled;
  protected $direct_order_tech_open;
  protected $direct_order_close;
  protected $second_sem_start;
  protected $second_sem_open;
  protected $second_sem_close;
  protected $re_enroll_open;
  protected $re_enroll_deadline;
  protected $midyear_application;
  protected $log_submission_close;
  protected $application_close;
  protected $midyear_application_open;
  protected $midyear_application_close;
  protected $re_enroll_notification;
  protected $first_sem_learning_logs_close;

  protected static $cache = array();
  protected $tmp = array();
  protected $changes = [];
  protected $archives = [];

  public function getID()
  {
    return (int) $this->school_year_id;
  }

  public function getArr($format = NULL)
  {
    $arr = array();
    foreach ($this as $field => $value) {
      if (
        $field == 'school_year_id'
        || $field == 'tmp'
        || $field == 'updateQueries'
      ) {
        continue;
      }

      $arr[$field] = self::getDate($value, $format);
    }
    return $arr;
  }

  public function getDateBegin($format = NULL)
  {
    return self::getDate($this->date_begin, $format);
  }

  public function getStartYear()
  {
    if (($startYear = &$this->tmp['getStartYear']) === NULL) {
      $startYear = (int) $this->getDateBegin('Y');
    }
    return $startYear;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public function getPreviousYear()
  {
    return self::getByStartYear($this->getStartYear() - 1);
  }

  /**
   *
   * @return mth_schoolYear
   */
  public function getNextYear()
  {
    return self::getByStartYear($this->getStartYear() + 1);
  }

  public function getDateEnd($format = NULL)
  {
    if (!$this->date_end) {
      $this->date_end = ($this->getStartYear() + 1) . '-05-31';
    }
    return self::getDate($this->date_end, $format);
  }

  public function getDateRegOpen($format = NULL)
  {
    if (!$this->date_reg_open) {
      $this->date_reg_open = $this->getStartYear() . '-03-30';
    }
    return self::getDate($this->date_reg_open, $format);
  }

  public function getDateRegClose($format = NULL)
  {
    if (!$this->date_reg_close) {
      $this->date_reg_close = $this->getStartYear() . '-08-01';
    }
    return self::getDate($this->date_reg_close, $format);
  }

  public function getSecondSemStart($format = NULL)
  {
    if (!$this->second_sem_start) {
      $this->second_sem_start = $this->getDateEnd('Y') . '-01-10';
    }
    return self::getDate($this->second_sem_start, $format);
  }

  public function getSecondSemOpen($format = NULL)
  {
    if (!$this->second_sem_open) {
      $this->second_sem_open = $this->getStartYear() . '-12-10';
    }
    return self::getDate($this->second_sem_open, $format);
  }

  public function getSecondSemClose($format = NULL)
  {
    if (!$this->second_sem_close) {
      $this->second_sem_close = $this->getDateEnd('Y') . '-01-05';
    }
    return self::getDate($this->second_sem_close, $format);
  }

  public function getReEnrollOpen($format = NULL)
  {
    if (!$this->re_enroll_open) {
      $this->re_enroll_open = $this->getStartYear() . '-02-15';
    }
    return self::getDate($this->re_enroll_open, $format);
  }

  public function getReEnrollDeadline($format = NULL)
  {
    if (!$this->re_enroll_deadline) {
      $this->re_enroll_deadline = $this->getStartYear() . '-02-28';
    }
    return self::getDate($this->re_enroll_deadline, $format);
  }

  public function getReEnrollNotification()
  {
    return $this->re_enroll_notification;
  }

  public function getLogSubmissionClose($format = NULL)
  {
    if (!$this->log_submission_close) {
      $this->log_submission_close = $this->getDateEnd('Y') . '-05-22';
    }
    return self::getDate($this->log_submission_close, $format);
  }

  public function getApplicationClose($format = NULL)
  {
    if (!$this->application_close) {
      $this->application_close = $this->getDateEnd('Y') . '-10-01';
    }
    return self::getDate($this->application_close, $format);
  }

  public function getMidyearOpen($format = NULL)
  {
    if (!$this->midyear_application_open) {
      $this->midyear_application_open = $this->getStartYear() . '-11-01';
    }
    return self::getDate($this->midyear_application_open, $format);
  }
  public function getMidyearClose($format = NULL)
  {
    if (!$this->midyear_application_close) {
      $this->midyear_application_close = $this->getStartYear() + 1 . '-02-01';
    }
    return self::getDate($this->midyear_application_close, $format);
  }

  public function getFirstSemLearningLogsClose($format = NULL)
  {
    if (!$this->first_sem_learning_logs_close) {
      $this->first_sem_learning_logs_close = $this->date_end;
    }
    return self::getDate($this->first_sem_learning_logs_close, $format);
  }

  public function reimburse_open($format = NULL)
  {
    if (!$this->reimburse_open) {
      $this->reimburse_open = $this->getStartYear() . '-10-15';
    }
    return self::getDate($this->reimburse_open, $format);
  }

  public function reimburse_tech_open($format = NULL)
  {
    if (!$this->reimburse_tech_open) {
      $this->reimburse_tech_open = $this->getStartYear() . '-11-15';
    }
    return self::getDate($this->reimburse_tech_open, $format);
  }

  public function reimburse_close($format = NULL)
  {
    if (!$this->reimburse_close) {
      $this->reimburse_close = $this->getDateEnd('Y') . '-04-30';
    }
    return self::getDate($this->reimburse_close, $format);
  }

  public function direct_order_open($format = NULL)
  {
    if (!$this->direct_order_open) {
      $this->direct_order_open = $this->getStartYear() . '-10-15';
    }
    return self::getDate($this->direct_order_open, $format);
  }

  public function direct_order_tech_enabled()
  {
    return $this->direct_order_tech_enabled == 1;
  }

  public function direct_order_tech_open($format = NULL)
  {
    if (!$this->direct_order_tech_open) {
      $this->direct_order_tech_open = $this->getStartYear() . '-11-15';
    }
    return self::getDate($this->direct_order_tech_open, $format);
  }

  public function direct_order_close($format = NULL)
  {
    if (!$this->direct_order_close) {
      $this->direct_order_close = $this->getDateEnd('Y') . '-04-30';
    }
    return self::getDate($this->direct_order_close, $format);
  }

  /**
   *
   * @param int $id
   * @return mth_schoolYear
   */
  public static function getByID($id)
  {
    $cache = &self::$cache[$id];
    if (!isset($cache)) {
      $cache = core_db::runGetObject('SELECT * FROM mth_schoolyear WHERE school_year_id=' . (int) $id, 'mth_schoolYear');
    }
    return $cache;
  }

  /**
   *
   * @param int $year
   * @return mth_schoolYear
   */
  public static function getByStartYear($year)
  {
    $cache = &self::$cache[$year];
    if (!isset($cache)) {
      $cache = core_db::runGetObject('SELECT * FROM mth_schoolyear WHERE YEAR(date_begin)=' . (int) $year, 'mth_schoolYear');
    }
    return $cache;
  }

  /**
   * @param null $minTimestamp
   * @param null $maxTimestamp
   * @return mth_schoolYear[]|bool
   */
  public static function getSchoolYears($minTimestamp = NULL, $maxTimestamp = NULL)
  {
    return core_db::runGetObjects(
      'SELECT * FROM mth_schoolyear 
                                  WHERE 1
                                    ' . ($minTimestamp ? 'AND date_end>=' . date('"Y-m-d"', $minTimestamp) : '') . '
                                    ' . ($maxTimestamp ? 'AND date_begin<=' . date('"Y-m-d"', $maxTimestamp) : '') . '
                                  ORDER BY date_begin DESC',
      'mth_schoolYear'
    );
  }

  /**
   *
   * @param int $timestamp
   * @return mth_schoolYear
   */
  public static function getByDate($timestamp)
  {
    $year = &self::$cache['getByDate-' . $timestamp];
    if (!isset($year)) {
      $year = core_db::runGetObject(
        'SELECT * FROM mth_schoolyear 
                                    WHERE date_end>"' . date('Y-m-d H:i:s', $timestamp) . '" 
                                    ORDER BY date_end ASC 
                                    LIMIT 1',
        'mth_schoolYear'
      );
    }
    return $year;
  }

  /**
   *
   * @param bool $reset
   * @return mth_schoolYear
   */
  public static function each($reset = FALSE)
  {
    $result = &self::$cache['each'];
    if (!isset($result)) {
      $result = core_db::runQuery('SELECT * FROM mth_schoolyear ORDER BY date_begin DESC');
    }
    if ($reset) {
      $result->data_seek(0);
      return NULL;
    }
    if (($year = $result->fetch_object('mth_schoolYear'))) {
      return $year;
    }
    $result->data_seek(0);
    return NULL;
  }

  /**
   *
   * @param bool $reset
   * @return mth_schoolYear
   */
  public static function limit(mth_schoolYear $year, $reset = FALSE)
  {
    $result = &self::$cache['limit'];
    if (!isset($result)) {
      $result = core_db::runQuery('SELECT * FROM mth_schoolyear where DATE_FORMAT(date_begin, "%Y")<=' . $year->getStartYear() . ' ORDER BY date_begin DESC');
    }
    if ($reset) {
      $result->data_seek(0);
      return NULL;
    }
    if (($year = $result->fetch_object('mth_schoolYear'))) {
      return $year;
    }
    $result->data_seek(0);
    return NULL;
  }

  /**
   * @return mth_schoolYear[]
   */
  public static function getAll()
  {
    if (NULL === ($all = &self::$cache['all'])) {
      $all = core_db::runGetObjects('SELECT * FROM mth_schoolyear ORDER BY date_begin ASC', __CLASS__);
    }
    return $all;
  }


  /**
   *
   * @return mth_schoolYear
   */
  public static function getCurrent()
  {
    $cache = &self::$cache['current'];
    if (!isset($cache)) {
      $cache = core_db::runGetObject('SELECT * FROM mth_schoolyear WHERE date_begin<=NOW() AND date_end>=NOW()', 'mth_schoolYear');
      if (!$cache) {
        //must be summer so get the next year.
        $cache = self::getByStartYear(date('Y'));
      }
    }
    return $cache;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public static function getNext()
  {
    $cache = &self::$cache['next'];
    if ($cache === NULL) {
      $cache = core_db::runGetObject('SELECT * FROM mth_schoolyear WHERE date_begin>NOW() ORDER BY date_begin ASC LIMIT 1', 'mth_schoolYear');
    }
    return $cache;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public static function getApplicationYear()
  {
    $time = time();
    $min = strtotime('June 1');
    $max = self::getCurrent()->getApplicationClose();
    if ($time > $min && $time < $max) {
      return self::getCurrent();
    } else {
      return self::getNext();
    }
  }

  public static function midYearAvailable()
  {
    $year = &self::$cache['midYearAvailable'];
    if ($year === NULL) {
      $year = self::getCurrent();
    }
    $time = time();

    return ($time >= ($year->getMidyearOpen()) && $time < ($year->getMidyearClose()));
  }

  public function isMidYearAvailable()
  {
    return $this->midyear_application == 1;
  }

  public function isLearningLogOpen()
  {
    return strtotime($this->log_submission_close) > strtotime('-1 day', time());
  }

  /**
   *
   * @return mth_schoolYear
   */
  public static function getPrevious()
  {
    $cache = &self::$cache['previous'];
    if (!isset($cache)) {
      $cache = core_db::runGetObject('SELECT * FROM mth_schoolyear WHERE date_end<NOW() ORDER BY date_begin DESC LIMIT 1', 'mth_schoolYear');
    }
    return $cache;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public static function getUpcomming()
  {
    $cache = &self::$cache['previous'];
    if (!isset($cache)) {
      $cache = core_db::runGetObjects('SELECT * FROM mth_schoolyear WHERE date_end>NOW() ORDER BY date_begin DESC', 'mth_schoolYear');
    }
    return $cache;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public static function getOpenReg()
  {
    $year = &self::$cache['openReg'];
    if (!isset($year)) {
      if (!(self::midYearAvailable() && ($year = self::getCurrent()) && $year->isMidYearAvailable())) {
        $year = core_db::runGetObject(
          'SELECT * FROM mth_schoolyear 
                                      WHERE date_reg_open<=NOW() AND date_reg_close>=NOW() 
                                      ORDER BY date_begin ASC LIMIT 1',
          'mth_schoolYear'
        );
      }
    }
    return $year;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public static function get2ndSemOpenReg()
  {
    $year = &self::$cache['get2ndSemOpenReg'];
    if ($year === NULL) {
      $currentYear = self::getCurrent();
      if (
        $currentYear->getSecondSemOpen() < time()
        && $currentYear->getSecondSemClose() > time()
      ) {
        $year = $currentYear;
      } else {
        $year = false;
      }
    }
    return $year;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public static function getYearReEnrollOpen()
  {
    $year = &self::$cache['getReEnrollOpen'];
    if (!isset($year)) {
      $year = core_db::runGetObject(
        'SELECT * FROM mth_schoolyear 
                                      WHERE re_enroll_open<=date(NOW()) AND DATE_SUB(date_reg_close,INTERVAL 5 DAY)>=date(NOW()) 
                                      ORDER BY date_begin ASC LIMIT 1',
        'mth_schoolYear'
      );
    }
    return $year;
  }

  /**
   *
   * @param int $startTimestamp
   * @return mth_schoolYear
   */
  public static function create($startTimestamp)
  {
    if (($startYear = date('Y', $startTimestamp)) < 1980) {
      return false;
    }
    if (($year = self::getByStartYear($startYear))) {
      return $year;
    }

    $year = new mth_schoolYear();
    $year->date_begin = date('Y-m-d', $startTimestamp);
    if (($lastYear = $year->getPreviousYear())) {
      $year->set_date_end(strtotime('+1 year', $lastYear->getDateEnd()));
      $year->set_date_reg_open(strtotime('+1 year', $lastYear->getDateRegOpen()));
      $year->set_date_reg_close(strtotime('+1 year', $lastYear->getDateRegClose()));
      $year->set_second_sem_start(strtotime('+1 year', $lastYear->getSecondSemStart()));
      $year->set_second_sem_open(strtotime('+1 year', $lastYear->getSecondSemOpen()));
      $year->set_second_sem_close(strtotime('+1 year', $lastYear->getSecondSemClose()));
      $year->set_re_enroll_open(strtotime('+1 year', $lastYear->getReEnrollOpen()));
      $year->set_re_enroll_deadline(strtotime('+1 year', $lastYear->getReEnrollDeadline()));
      $year->set_reimburse_open(strtotime('+1 year', $lastYear->reimburse_open()));
      $year->set_reimburse_tech_open(strtotime('+1 year', $lastYear->reimburse_tech_open()));
      $year->set_reimburse_close(strtotime('+1 year', $lastYear->reimburse_close()));
      $year->set_direct_order_open(strtotime('+1 year', $lastYear->direct_order_open()));
      $year->set_direct_order_tech_open(strtotime('+1 year', $lastYear->direct_order_tech_open()));
      $year->set_direct_order_close(strtotime('+1 year', $lastYear->direct_order_close()));
    }
    return $year;
  }

  protected function __construct()
  {
    //no direct access
  }

  public function __toString()
  {
    return $this->getName();
  }

  /**
   *
   * @return string e.g. "2014-15"
   */
  public function getName()
  {
    return $this->getDateBegin('Y') . '-' . $this->getDateEnd('y');
  }

  /**
   *
   * @return string e.g. "2014-15"
   */
  public function getLongName()
  {
    return $this->getDateBegin('Y') . '-' . $this->getDateEnd('Y');
  }

  public function set_date_begin($timestamp)
  {
    if ($this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->date_begin) != $timestamp) {
        $this->changes['date_begin'] =  date('Y-m-d', $timestamp);
        $this->archives['date_begin'] =  date('Y-m-d', strtotime($this->date_begin));
      }
      $this->set('date_begin', date('Y-m-d', $timestamp));
    }
  }

  public function set_date_end($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp)) {
      if (strtotime($this->date_end) != $timestamp) {
        $this->changes['date_end'] =  date('Y-m-d', $timestamp);
        $this->archives['date_end'] =  date('Y-m-d', $timestamp);
      }
      $this->set('date_end', date('Y-m-d', $timestamp));
    }
  }

  public function set_date_reg_open($timestamp)
  {
    if ($this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->date_reg_open) != $timestamp) {
        $this->changes['date_reg_open'] =  date('Y-m-d', $timestamp);
        $this->archives['date_reg_open'] = date('Y-m-d', strtotime($this->date_reg_open));
      }
      $this->set('date_reg_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_date_reg_close($timestamp)
  {
    //if ($this->getStartYear() + 1 == date('Y', $timestamp)) {
    if (strtotime($this->date_reg_close) != $timestamp) {
      $this->changes['date_reg_close'] =  date('Y-m-d', $timestamp);
      $this->archives['date_reg_close'] =  date('Y-m-d', strtotime($this->date_reg_close));
    }
    $this->set('date_reg_close', date('Y-m-d', $timestamp));
    //}
  }

  public function set_second_sem_start($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp) || $this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->second_sem_start) != $timestamp) {
        $this->changes['second_sem_start'] =  date('Y-m-d', $timestamp);
        $this->archives['second_sem_start'] =  date('Y-m-d', strtotime($this->second_sem_start));
      }
      $this->set('second_sem_start', date('Y-m-d', $timestamp));
    }
  }

  public function set_second_sem_open($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp) || $this->getStartYear() == date('Y', $timestamp)) {
      if ($this->getSecondSemOpen() != $timestamp) {
        $this->changes['second_sem_open'] =  date('Y-m-d', $timestamp);
        $this->archives['second_sem_open'] =  date('Y-m-d', $this->getSecondSemOpen());
      }
      $this->set('second_sem_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_second_sem_close($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp) || $this->getStartYear() == date('Y', $timestamp)) {
      if ($this->getSecondSemClose() != $timestamp) {
        $this->changes['second_sem_close'] =  date('Y-m-d', $timestamp);
        $this->archives['second_sem_close'] =  date('Y-m-d', $this->getSecondSemClose());
      }
      $this->set('second_sem_close', date('Y-m-d', $timestamp));
    }
  }

  public function set_re_enroll_open($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp) || $this->getStartYear() == date('Y', $timestamp)) {
      if ($this->getReEnrollOpen() != $timestamp) {
        $this->changes['re_enroll_open'] =  date('Y-m-d', $timestamp);
        $this->archives['re_enroll_open'] =  date('Y-m-d', $this->getReEnrollOpen());
      }
      $this->set('re_enroll_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_re_enroll_deadline($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp) || $this->getStartYear() == date('Y', $timestamp)) {
      if ($this->getReEnrollDeadline() != $timestamp) {
        $this->changes['re_enroll_deadline'] =  date('Y-m-d', $timestamp);
        $this->archives['re_enroll_deadline'] =  date('Y-m-d', $this->getReEnrollDeadline());
      }
      $this->set('re_enroll_deadline', date('Y-m-d', $timestamp));
    }
  }

  public function set_re_enroll_notification($days = 5)
  {
    $this->set('re_enroll_notification', (int) $days);
  }

  public function set_log_submission_close($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp) || $this->getStartYear() == date('Y', $timestamp)) {
      if ($this->getLogSubmissionClose() != $timestamp) {
        $this->changes['log_submission_close'] =  date('Y-m-d', $timestamp);
        $this->archives['log_submission_close'] =  date('Y-m-d', $this->getLogSubmissionClose());
      }
      $this->set('log_submission_close', date('Y-m-d', $timestamp), true);
    }
  }

  public function set_application_close($timestamp)
  {
    if ($this->getApplicationClose() != $timestamp) {
      $this->changes['application_close'] =  date('Y-m-d', $timestamp);
      $this->archives['application_close'] =  date('Y-m-d', $this->getApplicationClose());
    }
    $this->set('application_close', date('Y-m-d', $timestamp));
  }

  public function set_reimburse_open($timestamp)
  {
    if ($this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->reimburse_open) != $timestamp) {
        $this->changes['reimburse_open'] =  date('Y-m-d', $timestamp);
        $this->archives['reimburse_open'] =  date('Y-m-d', strtotime($this->reimburse_open));
      }
      $this->set('reimburse_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_reimburse_tech_open($timestamp)
  {
    if ($this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->reimburse_tech_open) != $timestamp) {
        $this->changes['reimburse_tech_open'] =  date('Y-m-d', $timestamp);
        $this->archives['reimburse_tech_open'] =  date('Y-m-d', strtotime($this->reimburse_tech_open));
      }
      $this->set('reimburse_tech_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_reimburse_close($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp)) {
      if (strtotime($this->reimburse_close) != $timestamp) {
        $this->changes['reimburse_close'] =  date('Y-m-d', $timestamp);
        $this->archives['reimburse_close'] =  date('Y-m-d', strtotime($this->reimburse_close));
      }
      $this->set('reimburse_close', date('Y-m-d', $timestamp));
    }
  }

  public function set_direct_order_open($timestamp)
  {
    if ($this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->direct_order_open) != $timestamp) {
        $this->changes['direct_order_open'] =  date('Y-m-d', $timestamp);
        $this->archives['direct_order_open'] =  date('Y-m-d', strtotime($this->direct_order_open));
      }
      $this->set('direct_order_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_direct_order_tech_enabled($int)
  {
    if ($this->direct_order_tech_enabled != $int) {
      $this->changes['direct_order_tech_enabled'] = $int;
      $this->archives['direct_order_tech_enabled'] = $this->direct_order_tech_enabled;
    }
    $this->set('direct_order_tech_enabled', $int);
  }

  public function set_direct_order_tech_open($timestamp)
  {
    if ($this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->direct_order_tech_open) != $timestamp) {
        $this->changes['direct_order_tech_open'] =  date('Y-m-d', $timestamp);
        $this->archives['direct_order_tech_open'] =  date('Y-m-d', strtotime($this->direct_order_tech_open));
      }
      $this->set('direct_order_tech_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_direct_order_close($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp)) {
      if (strtotime($this->direct_order_close) != $timestamp) {
        $this->changes['direct_order_close'] =  date('Y-m-d', $timestamp);
        $this->archives['direct_order_close'] =  date('Y-m-d', strtotime($this->direct_order_close));
      }
      $this->set('direct_order_close', date('Y-m-d', $timestamp));
    }
  }

  public function set_midyear_application_open($timestamp)
  {
    if ($this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->midyear_application_open) != $timestamp) {
        $this->changes['midyear_application_open'] =  date('Y-m-d', $timestamp);
        $this->archives['midyear_application_open'] =  date('Y-m-d', strtotime($this->midyear_application_open));
      }
      $this->set('midyear_application_open', date('Y-m-d', $timestamp));
    }
  }

  public function set_midyear_application_close($timestamp)
  {
    if ($this->getStartYear() + 1 == date('Y', $timestamp) || $this->getStartYear() == date('Y', $timestamp)) {
      if (strtotime($this->midyear_application_close) != $timestamp) {
        $this->changes['midyear_application_close'] =  date('Y-m-d', $timestamp);
        $this->archives['midyear_application_close'] =  date('Y-m-d', strtotime($this->midyear_application_close));
      }
      $this->set('midyear_application_close', date('Y-m-d', $timestamp));
    }
  }

  public function set_first_sem_learning_logs_close($date)
  {
    $this->set('first_sem_learning_logs_close', date('Y-m-d', $date));
  }

  public function  set_mid_year($int)
  {
    if ($this->midyear_application != $int) {
      $this->changes['midyear_application'] = $int;
      $this->archives['midyear_application'] = $this->midyear_application;
    }
    $this->set('midyear_application', $int);
  }

  public function getChanges()
  {
    return empty($this->changes) ? false : $this->changes;
  }

  public function getArchives()
  {
    return empty($this->archives) ? false : $this->archives;
  }

  public function save()
  {
    if (!$this->school_year_id) {
      if (!$this->date_begin) {
        return false;
      }
      core_db::runQuery('INSERT INTO mth_schoolYear (date_begin) VALUES ("' . $this->date_begin . '")');
      $this->school_year_id = core_db::getInsertID();
    }
    return parent::runUpdateQuery('mth_schoolYear', 'school_year_id=' . $this->getID());
  }
}
