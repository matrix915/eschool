<?php

/**
 * mth_reimbursement
 *
 * @author abe
 */
class mth_reimbursement extends core_model
{
  use \core\Injectable, \core\Injectable\DateTimeWrapperFactoryInjector;

  protected $reimbursement_id;
  protected $parent_id;
  protected $student_id;
  protected $school_year_id;
  protected $at_least_80;
  protected $schedule_period_id;
  protected $type;
  protected $amount;
  protected $total_amount;
  protected $invalid_amount;
  protected $description;
  protected $product_name;
  protected $product_sn;
  protected $product_amount;
  protected $status;
  protected $date_submitted;
  protected $date_resubmitted;
  protected $date_paid;
  protected $fields_last_changed;
  protected $require_new_receipt;
  protected $last_modified;
  protected $last_status;
  protected $resource_request_id;
  protected $approved_by_id;
  protected $occurrences;

  protected $confirm_receipt;
  protected $confirm_related;
  protected $confirm_dated;
  protected $confirm_provided;
  protected $confirm_allocation;
  protected $confirm_update;
  protected $type_tag;

  protected $is_direct_order;
  protected $direct_order_list_provider;
  protected $direct_order_list_link;
  protected $direct_order_confirmation;

  protected $receipt_file_ids = array();
  protected $submission_ids = array();
  protected $each_receipt_on_num = 0;

  protected $new_receipts_uploaded = false;
  protected $saved = false;

  protected $linked_reimbursement;

  const STATUS_NOTSUBMITTED = 0;
  const STATUS_SUBMITTED = 1;
  const STATUS_UPDATE = 2;
  const STATUS_RESUBMITTED = 3;
  const STATUS_APPROVED = 4;
  const STATUS_PAID = 5;

  protected static $admin_statuses = array(
    self::STATUS_PAID,
    self::STATUS_APPROVED,
    self::STATUS_UPDATE
  );

  protected $admin_reviewed = false;

  protected static $status_labels = array(
    self::STATUS_NOTSUBMITTED => 'Not Submitted',
    self::STATUS_UPDATE => 'Updates Required',
    self::STATUS_SUBMITTED => 'Submitted',
    self::STATUS_RESUBMITTED => 'Resubmitted',
    self::STATUS_APPROVED => 'Approved',
    self::STATUS_PAID => 'Paid/Ordered',
  );

  public static function availableStatuses()
  {
    return self::$status_labels;
  }

  public function getOccurrences(){
     return $this->occurrences;
  }

  const METHOD_REIMBURSEMENT = 0;
  const METHOD_DIRECT_ORDER = 1;

  protected static $method_labels = array(
    self::METHOD_REIMBURSEMENT => 'RB',
    self::METHOD_DIRECT_ORDER => 'DO',
  );

  public static function availableMethods()
  {
    return self::$method_labels;
  }

  const TYPE_CUSTOM = 1;
  const TYPE_TP = 2;
  const TYPE_COLLEGE_CREDIT = 3;
  const TYPE_TECH = 4;
  const TYPE_SOFTWARE = 5;
  const TYPE_SUPPLEMENTAL = 6;

  const TYPE_DIRECT = 11;

  protected static $type_labels = array(
    self::TYPE_CUSTOM => 'Custom-built',
    self::TYPE_TP => '3rd Party Provider',
    self::TYPE_SOFTWARE => 'Required Software and Materials',
    self::TYPE_TECH => 'Technology Allowance',
    self::TYPE_SUPPLEMENTAL => 'Supplemental Learning Funds'
  );

  protected static $amin_types = [
    self::TYPE_DIRECT
  ];
  protected static $admin_type_label = [
    self::TYPE_DIRECT => "Direct Deduction"
  ];

  const LIMIT = 2;
  const LIMIT_MERGED = 4;
  const LIMIT_TECH = 2;

  public static function type_labels()
  {
    return self::$type_labels;
  }

  public static function direct_order_type_labels()
  {
    return array(
      self::TYPE_CUSTOM => self::type_label(self::TYPE_CUSTOM),
      self::TYPE_TECH => self::type_label(self::TYPE_TECH)
    );
  }


  public static function type_label($type)
  {
    if (in_array($type, self::$amin_types)) {
      return self::$admin_type_label[$type];
    }
    return self::$type_labels[$type];
  }

  protected static $merge_custom_periods = array(2, 3, 4, 6);

  public static function merge_custom_periods()
  {
    return self::$merge_custom_periods;
  }

  /**
   *
   * @var mth_schoolYear
   */
  protected static $year;

  /**
   * for array filter functions
   * @var mth_student
   */
  protected static $student;

  protected static $date_first;
  protected static $date_second;

  protected static $allowed_receipt_file_types = array(
    'pdf', 'png', 'jpg', 'jpeg', 'gif', 'bmp'
  );

  public static function allowed_receipt_file_types()
  {
    return self::$allowed_receipt_file_types;
  }

  protected static $cache = array();

  //----------------------------------------------------------------------------------------------//
  //----------------GET METHODS-------------------------------------------------------------------//
  //----------------------------------------------------------------------------------------------//

  public function id()
  {
    return (int) $this->reimbursement_id;
  }

  /**
   *
   * @return mth_schoolYear
   */
  public function school_year()
  {
    return mth_schoolYear::getByID($this->school_year_id);
  }

  public function at_least_80()
  {
    return (bool) $this->at_least_80;
  }

  public function confirm_receipt()
  {
    return (bool) $this->confirm_receipt;
  }
  public function confirm_related()
  {
    return (bool) $this->confirm_related;
  }
  public function confirm_dated()
  {
    return (bool) $this->confirm_dated;
  }
  public function confirm_provided()
  {
    return (bool) $this->confirm_provided;
  }
  public function confirm_allocation()
  {
    return (bool) $this->confirm_allocation;
  }
  public function confirm_update()
  {
    return (bool) $this->confirm_update;
  }
  public function is_direct_order()
  {
    return (bool) $this->is_direct_order;
  }
  public function direct_order_list_provider()
  {
    return $this->direct_order_list_provider;
  }
  public function direct_order_list_link()
  {
    return $this->direct_order_list_link;
  }
  public function direct_order_confirmation()
  {
    return $this->direct_order_confirmation;
  }
  public function resource_request_id()
  {
    return $this->resource_request_id;
  }
  public function approved_by_id()
  {
    return $this->approved_by_id;
  }


  public function student_id()
  {
    return (int) $this->student_id;
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
   * @return mth_parent
   */
  public function student_parent()
  {
    return mth_parent::getByParentID($this->parent_id);
  }

  /**
   *
   * @return mth_schedule_period
   */
  public function schedule_period()
  {
    return mth_schedule_period::getByID($this->schedule_period_id);
  }

  public function schedule_period_description()
  {
    if (!($schedule_period = $this->schedule_period())) {
      return '';
    }
    return $this->get_merged_period_description($schedule_period);
  }

  public function schedule_period_id()
  {
    return (int) $this->schedule_period_id;
  }

  public function course_type($returnNum = true)
  {
    if (($schedule_period = $this->schedule_period())) {
      return $schedule_period->course_type($returnNum);
    }
    return NULL;
  }

  public function receipt_file_ids()
  {
    if (empty($this->receipt_file_ids) && $this->id()) {
      $result = mth_reimbursementreceipt::getReceiptsByReimbursementId($this->id());

      foreach ($result as $r) {
        $this->receipt_file_ids[] = $r->fileId();
      }
    }
    return $this->receipt_file_ids;
  }

  public function sorted_submission_ids()
  {
    if ($submissions = mth_reimbursementsubmission::getSubmissionsByReimbursementId(self::id())) {
      foreach ($submissions as $sub) {
        $this->submission_ids[] = $sub->id();
      }
      sort($this->submission_ids);
      return $this->submission_ids;
    }
  }

  public static function delete_reciept($file_id)
  {
    if ($file_id) {
      return core_db::runQuery('DELETE from mth_reimbursement_reciept where file_id=' . $file_id);
    }
    return false;
  }

  /**
   *
   * @param bool $reset
   * @return mth_file
   */
  public function each_receipt($reset = false)
  {
    $this->receipt_file_ids();
    if (
      !$reset
      && isset($this->receipt_file_ids[$this->each_receipt_on_num])
      && ($file = mth_file::get($this->receipt_file_ids[$this->each_receipt_on_num]))
    ) {
      $this->each_receipt_on_num++;
      return $file;
    }
    $this->each_receipt_on_num = 0;
    return NULL;
  }

  /**
   *
   * @return int
   */
  public function type($returnNumber = false)
  {
    if ($returnNumber) {
      return (int) $this->type;
    }

    if (in_array($this->type, self::$amin_types)) {
      return self::$admin_type_label[$this->type];
    }

    if (!isset(self::$type_labels[$this->type])) {
      return (int) $this->type;
    }

    return self::$type_labels[$this->type];
  }

  public function type_tag($returnNumber = false)
  {
    if ($returnNumber) {
      return (int) $this->type_tag;
    }

    if (in_array($this->type_tag, self::$amin_types)) {
      return self::$admin_type_label[$this->type_tag];
    }

    if (!isset(self::$type_labels[$this->type_tag])) {
      return (int) $this->type_tag;
    }

    return self::$type_labels[$this->type_tag];
  }
  /**
   * get reimbursement type label
   * @return String
   */
  public function getTypeLabel()
  {
    if ($this->type(true) == self::TYPE_DIRECT) {
      $prefix_desc = $this->type_tag() ? "{$this->type_tag()} - " : '';
      if (NULL == $this->type_tag()) {
        return $this->description();
      }
      return $prefix_desc . $this->type();
    }
    return $this->type();
  }

  public function getCC()
  {
    $emails = [];
    if ($this->updatesRequired() && ($cc = core_setting::get("reimbursementurcc", 'Reimbursement'))) {
      $emails = empty(trim($cc->getValue())) ? [] : explode(',', $cc->getValue());
    }

    if (
      empty($emails) && in_array($this->type, [self::TYPE_TP, self::TYPE_TECH]) && ($cc = core_setting::get("reimbursementcc", 'Reimbursement'))
    ) {
      $emails = empty(trim($cc->getValue())) ? [] : explode(',', $cc->getValue());
    }

    $trimmed_emails = array_map('trim', $emails);
    return !empty($trimmed_emails) ? $trimmed_emails : null;
  }

  public function amount($format = false)
  {
    if ($format) {
      return self::getNumber($this->amount, 2);
    }
    return $this->amount;
  }

  public function totalAmount($format = false)
  {
    if ($format) {
      return self::getNumber($this->total_amount, 2);
    }
    return $this->total_amount;
  }

  public function invalid_amount()
  {
    return $this->invalid_amount ? (float) $this->invalid_amount : 10000;
  }

  public function description($html = true)
  {
    if (!$html) {
      return strip_tags($this->description);
    }
    return $this->description;
  }

  public function product_name()
  {
    return $this->product_name;
  }

  public function product_sn()
  {
    return $this->product_sn;
  }

  public function product_amount($format = false)
  {
    if ($format) {
      return self::getNumber($this->product_amount, 2);
    }
    return $this->product_amount;
  }

  public function isStatus($status)
  {
    return $this->status == $status;
  }

  public function isSubmitted()
  {
    return !$this->updatesRequired() && !$this->isStatus(self::STATUS_NOTSUBMITTED);
  }

  public function isSaved()
  {
    return $this->saved;
  }


  public function status($returnNumber = false)
  {
    if ($returnNumber || !isset(self::$status_labels[(int) $this->status])) {
      return (int) $this->status;
    }
    if ($this->type() != 'Direct Deduction' || (int)$this->status != self::STATUS_PAID)
      return self::$status_labels[(int) $this->status];
    return 'Processed';
  }

  public function updatesRequired()
  {
    return $this->status == self::STATUS_UPDATE;
  }

  public function notApproved()
  {
    return !$this->isStatus(self::STATUS_APPROVED) && !$this->isStatus(self::STATUS_PAID);
  }

  public function isPaid()
  {
    return $this->isStatus(self::STATUS_PAID);
  }

  public function date_submitted($format = NULL)
  {
    if ($this->date_resubmitted) {
      return self::getDate($this->date_resubmitted, $format);
    }
    return self::getDate($this->date_submitted, $format);
  }

  public function date_paid($format = NULL)
  {
    return self::getDate($this->date_paid, $format);
  }

  public function field_has_changed($field)
  {
    if (!is_array($this->fields_last_changed)) {
      $this->fields_last_changed = explode('|', $this->fields_last_changed);
    }
    return in_array($field, $this->fields_last_changed);
  }

  public function require_new_receipt()
  {
    return (bool) $this->require_new_receipt;
  }

  public function editable()
  {
    return $this->viewable()
      && (core_user::isUserAdmin()
        || !$this->id()
        || $this->updatesRequired()
        || $this->isStatus(self::STATUS_NOTSUBMITTED)
        || $this->isStatus(self::STATUS_SUBMITTED)
        || $this->isStatus(self::STATUS_RESUBMITTED));
  }

  public function isDiscardable()
  {
    return $this->isStatus(self::STATUS_NOTSUBMITTED)
      || $this->isStatus(self::STATUS_SUBMITTED)
      || $this->isStatus(self::STATUS_RESUBMITTED);
  }

  public function viewable()
  {
    return core_user::isUserAdmin()
      || !$this->student()
      || ($this->student()->getParent() == mth_parent::getByUser());
  }

  public function submitable()
  {
    return $this->editable()
      && $this->student()
      && $this->school_year()
      && $this->at_least_80()
      && in_array($this->type, $this->available_types())
      && ($this->isTechEnabled()
        //$this->type(true) == self::TYPE_TECH
        || in_array($this->schedule_period_id, $this->available_schedule_period_ids()))
      && $this->amount
      && $this->receipt_file_ids()
      && !$this->require_new_receipt()
      && (!$this->status()
        || (!$this->isSubmitted()
          && $this->student_parent() == mth_parent::getByUser()));
  }

  /**
   *
   * @param bool $reset
   * @param tring $varient
   * @return mth_reimbursement
   */
  public function old_eachLinked($reset = false, $varient = NULL)
  {
    if (NULL === ($result = &self::$cache['eachLinked'][$this->reimbursement_id][$varient])) {
      $result = core_db::runQuery('SELECT * FROM mth_reimbursement 
                          WHERE reimbursement_id!=' . $this->id() . ' 
                            AND student_id=' . (int) $this->student_id . ' 
                            AND school_year_id=' . (int) $this->school_year_id . '
                            AND ' . ($this->type == self::TYPE_TECH || $this->type == self::TYPE_SUPPLEMENTAL
        ? '`type`=' . $this->type
        : 'schedule_period_id=' . (int) $this->schedule_period_id));
    }
    if (!$reset && ($reimbursement = $result->fetch_object('mth_reimbursement'))) {
      return $reimbursement;
    }
    $result->data_seek(0);
    return NULL;
  }

  /**
   * Construct Sql Statement that will determine if specific period have the same period course
   * mth_Reimbursement and mth_Schedule_period should be a joint table when using this function
   * where msp is mth_schedule_period and mr is mth_reimbursement
   * @param mth_schedule_period $period
   * @return string
   */
  public static function theSamePeriodStmt(mth_schedule_period $period)
  {
    $and = [];
    $or = "";


    $and[] = "msp.period={$period->period_number()}";
    $and[] = "msp.schedule_id={$period->schedule_id()}";

    if ($period->subject_id()) {
      $and[] = "msp.subject_id={$period->subject_id()}";
    }

    if ($period->course_type(true)) {
      $and[] = "msp.course_type={$period->course_type(true)}";
    }

    if ($period->course_id()) {
      $and[] = "msp.course_id = {$period->course_id()}";
    }

    if ($period->provider_course_id()) {
      $and[] = "msp.provider_course_id={$period->provider_course_id()}";
    }

    if ($period->mth_provider_id()) {
      $and[] = "msp.mth_provider_id={$period->mth_provider_id()}";
    }


    $or = !empty($and) ? " or (" . implode(" AND ", $and) . ")" : "";

    return "(mr.schedule_period_id={$period->id()}$or)";
  }


  public function eachLinked($reset = false, $varient = NULL)
  {
    if (NULL === ($result = &self::$cache['eachLinked'][$this->reimbursement_id][$varient])) {
      $no_period_involved = $this->type == self::TYPE_TECH || $this->type == self::TYPE_SUPPLEMENTAL;

      $period_stmt = '';
      $join = '';


      if (!$no_period_involved) {
        if ($period = $this->schedule_period()) {
          if($this->schedule_period()->course_type() !== 'Custom-built'){
            $period_stmt = self::theSamePeriodStmt($period);
          }else{
            $period_stmt = '(mr.schedule_period_id='.$this->schedule_period_id.' OR (msp.schedule_id='.
                                      $this->schedule_period()->schedule_id().' AND msp.course_type=3))';
          }
          
          $join  = "inner join mth_schedule_period as msp on msp.schedule_period_id=mr.schedule_period_id";
        } else {
          $period_stmt =  'mr.schedule_period_id=' . (int) $this->schedule_period_id;
        }
      }

      $type_stmt = ($no_period_involved) ? "`type` = {$this->type}" : $period_stmt;

      $sql = "select mr.* from mth_reimbursement as mr $join WHERE reimbursement_id!={$this->id()} 
            and student_id={$this->student_id}
            AND school_year_id={$this->school_year_id} AND " . $type_stmt;
            
      $result = core_db::runQuery($sql);
    }
    if (!$reset && ($reimbursement = $result->fetch_object('mth_reimbursement'))) {
      return $reimbursement;
    }
    $result->data_seek(0);
    return NULL;
  }

  /**
   *
   * @param bool $reset
   * @return mth_reimbursement
   */
  public function eachDirect($reset = false)
  {
    if (NULL === ($result = &self::$cache['eachDirect'][$this->reimbursement_id])) {
       $include_homeroom = $this->type == self::TYPE_TECH || $this->type == self::TYPE_SUPPLEMENTAL ? ' OR `description` LIKE "%Direct Deduction - Homeroom Resource%"' : '';
          $result = core_db::runQuery('SELECT * FROM mth_reimbursement 
                          WHERE reimbursement_id!=' . $this->id() . ' 
                            AND student_id=' . (int) $this->student_id . ' 
                            AND school_year_id=' . (int) $this->school_year_id . '
                            AND `type`=' . self::TYPE_DIRECT . '
                            AND (`type_tag` IN ('. $this->type .')'. $include_homeroom . ')');
    }
    if (!$reset && ($reimbursement = $result->fetch_object('mth_reimbursement'))) {
      return $reimbursement;
    }
    $result->data_seek(0);
    return NULL;
  }

  public function isSecond()
  {
    if (NULL === ($isSecond = &self::$cache['isSecond'][$this->reimbursement_id])) {
      $isSecond = false;
      $this->eachLinked(true, 'secondCheck');
      while (($reimbursement = $this->eachLinked(false, 'secondCheck'))) {
        if ($reimbursement->date_submitted() < $this->date_submitted()) {
          $isSecond = true;
        }
      }
    }
    return $isSecond;
  }

  public function available_types()
  {
    if (!$this->student_id || !$this->school_year()) {
      return array();
    }
    $availableTypes = self::availableTypes($this->student(), $this->school_year());
    if ($this->type && $this->id() && isset(self::$type_labels[$this->type]) && !in_array($this->type, $availableTypes)) {
      $availableTypes[] = $this->type;
    }
    return $availableTypes;
  }

  public function available_schedule_period_ids()
  {
    if (
      !$this->student()
      || !$this->school_year()
      || !$this->type
      || !($schedule = mth_schedule::get($this->student(), $this->school_year()))
    ) {
      return array();
    }
    $avaialablePeriods = self::availableSchedulePeriodIDs($schedule, $this->type);
    if (
      $this->schedule_period_id && $this->id()
      && !in_array($this->schedule_period_id, $avaialablePeriods)
      && ($schedulePeriod = $this->schedule_period())
      && in_array($schedulePeriod->id(), self::mergedSchedulePeriodIDs($schedule, $this->type))
    ) {
      $avaialablePeriods[] = $schedulePeriod->id();
    }
    return $avaialablePeriods;
  }

  public function get_merged_period_description(mth_schedule_period $schedulePeriod, $returnPeriodNums = false)
  {
   
    $mergedSchedules = self::mergedSchedulePeriodIDs($schedulePeriod->schedule(), $this->type);
    if (
      $this->type != self::TYPE_CUSTOM
      || !in_array($schedulePeriod->period()->num(), self::$merge_custom_periods)
      || !in_array($schedulePeriod->id(), $mergedSchedules)
    ) {
      return $schedulePeriod->period() . ' - ' . $schedulePeriod;
    }
    $remove = array('elementary', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11);
    $courseNames = [];
    $schedule = $schedulePeriod->schedule();
    $startPeriod = 1;
    $count = 0;
    while ($startPeriod <= 6) {
      // Evaluate the most recent semester of schedule period
      $mostRecentPeriod = false;
      if (($eachSchPeriod = $schedule->getPeriod($startPeriod, true))
        && ($eachSchPeriod->course_type(true) == mth_schedule_period::TYPE_CUSTOM)
        && in_array($startPeriod, self::$merge_custom_periods)
      ) {
        // If there is a first and second semester for this period, we want to add the 2nd semester period after the 1st.
        // We will assign this to a variable for now and add it to the main array after the possible 1st semester class
        // has been evaluated.
        $mostRecentPeriod = [
          'period' => $startPeriod,
          'label' => trim(str_ireplace($remove, '', $eachSchPeriod->courseName()))
        ];
        $count++;
      }

      // If most recent semester is a second semester class, also evaluate the first semester
      if (($eachSchPeriod && $eachSchPeriod->second_semester())
        && ($eachSchPeriod = $schedule->getPeriod($startPeriod, false))
        && ($eachSchPeriod->course_type(true) == mth_schedule_period::TYPE_CUSTOM)
        && in_array($startPeriod, self::$merge_custom_periods)
      ) {
        $courseNames[] = [
          'period' => $startPeriod,
          'label' => trim(str_ireplace($remove, '', $eachSchPeriod->courseName()))
        ];
        $count++;
      }

      if ($mostRecentPeriod !== false) {
        $courseNames[] = $mostRecentPeriod;
      }

      $startPeriod++;
    }
    if ($count == 1) {
      return $schedulePeriod->period() . ' - ' . $schedulePeriod;
    }
    $periodNums = array_column($courseNames, 'period');
    $periodLabels = array_column($courseNames, 'label');

    if ($returnPeriodNums) {
      return $periodNums;
    }

    if ($count == 2) {
      $periodNums = implode(' and ', $periodNums);
    } else {
      $lastPeriodIndex = count($periodNums) - 1;
      $periodNums[$lastPeriodIndex] = 'and ' . $periodNums[$lastPeriodIndex];
      $periodNums = implode(', ', $periodNums);
    }

    return 'Periods ' . $periodNums . ' - ' . implode(', ', $periodLabels) . ' - ' . $schedulePeriod->course_type();
  }

  /**
   * @return \core\DateTimeWrapper
   */
  public function getLastModified()
  {
    return $this->getDateTimeWrapperFactory()->newDateTimeWrapper($this->last_modified);
  }

  //----------------------------------------------------------------------------------------------//
  //----------------SET METHODS-------------------------------------------------------------------//
  //----------------------------------------------------------------------------------------------//

  public function set_schedule_period_id($schedule_period_id)
  {
    if (!$this->type) {
      error_log('The type must be set first');
      return false;
    }
    if (($schedule_period = mth_schedule_period::getByID($schedule_period_id))
      && ($year = $schedule_period->schedule()->schoolYear())
      && ($student = $schedule_period->schedule()->student())
      && ($parent = $student->getParent())
      && ($parent == mth_parent::getByUser() || core_user::isUserAdmin())
      && in_array($schedule_period_id, self::availableSchedulePeriodIDs($schedule_period->schedule(), $this->type))
    ) {
      $this->set('parent_id', $parent->getID());
      $this->set('student_id', $student->getID());
      $this->set('schedule_period_id', $schedule_period->id());
      $this->set('school_year_id', $year->getID());
      return true;
    }
    return false;
  }

  public function set_save($value)
  {
    $this->saved = $value;
  }

  public function set_student_year(mth_student $student, mth_schoolYear $school_year)
  {
    $this->set('parent_id', $student->getParentID());
    $this->set('student_id', $student->getID());
    $this->set('school_year_id', $school_year->getID());
  }

  public function set_at_least_80($value)
  {
    $this->set('at_least_80', $value ? 1 : 0);
  }

  public function set_confirm_receipt($value)
  {
    $this->set('confirm_receipt', $value ? 1 : 0);
  }
  public function set_confirm_related($value)
  {
    $this->set('confirm_related', $value ? 1 : 0);
  }
  public function set_confirm_dated($value)
  {
    $this->set('confirm_dated', $value ? 1 : 0);
  }
  public function set_confirm_provided($value)
  {
    $this->set('confirm_provided', $value ? 1 : 0);
  }
  public function set_confirm_allocation($value)
  {
    $this->set('confirm_allocation', $value ? 1 : 0);
  }
  public function set_confirm_update($value)
  {
    $this->set('confirm_update', $value ? 1 : 0);
  }
  public function set_is_direct_order($value)
  {
    $this->set('is_direct_order', $value ? 1 : 0);
  }
  public function set_direct_order_list_provider($value)
  {
    $this->set('direct_order_list_provider', $value);
  }
  public function set_direct_order_list_link($value)
  {
    $this->set('direct_order_list_link', $value);
  }
  public function set_direct_order_confirmation($value)
  {
    $this->set('direct_order_confirmation', $value);
  }

  public function set_type($value)
  {
    $this->set('type', (int) $value);
    if (
      $this->isTechEnabled()
      //$value == self::TYPE_TECH
    ) {
      $this->set('schedule_period_id', NULL);
    }
  }

  public function set_tag_type($value)
  {
    $this->set('type_tag', (int) $value);
  }

  public function set_resource_request_id($value)
  {
    $this->set('resource_request_id', $value);
  }

  public function set_amount($value)
  {
    $this->set('amount', req_sanitize::float($value));
  }

  public function set_invalid_amount()
  {
    $this->set('invalid_amount', $this->amount());
  }

  public function set_description($value)
  {
    $this->set('description', nl2br(req_sanitize::multi_txt($value)));
  }

  public function set_product_name($product_name)
  {
    $this->set('product_name', req_sanitize::txt($product_name));
  }

  public function set_product_sn($product_serial_number)
  {
    $this->set('product_sn', req_sanitize::txt($product_serial_number));
  }

  public function set_product_amount($value)
  {
    $this->set('product_amount', req_sanitize::float($value));
  }

  public function set_date_paid($value)
  {
    $this->set('date_paid', $value);
  }

  public function save_receipt_files(array $arrayOfFileFieldNames)
  {
    $this->receipt_file_ids = $this->receipt_file_ids();
    $success = $submitted = 0;
    foreach ($arrayOfFileFieldNames as $fieldName) {
      if (!isset($_FILES[$fieldName])) {
        continue;
      }

      if ($_FILES[$fieldName]['error'] == UPLOAD_ERR_NO_FILE) {
        continue;
      }
      $submitted++;
      if (!in_array(
        preg_replace('/^.*\.([^\.]+)$/', '$1', strtolower($_FILES[$fieldName]['name'])),
        self::$allowed_receipt_file_types
      )) {
        continue;
      }
      if (($file = mth_file::saveUploadedFile($fieldName))) {
        if ($this->reimbursement_id) {
          mth_reimbursementreceipt::create($this->reimbursement_id, $file->id());
        }
        $this->receipt_file_ids[] = $file->id();
        $this->new_receipts_uploaded = true;
        $success++;
      }
    }
    if ($success) {
      $this->set('require_new_receipt', 0);
    }
    return $submitted == $success;
  }

  public function set_require_new_receipt($value = true)
  {
    $this->set('require_new_receipt', $value ? 1 : 0);
  }

  public function set_approved_by_id()
  {
     $this->set('approved_by_id', core_user::getUserID());
  }

  public function set_status($status)
  {
    if ($status !== $this->status(true) && in_array($status, self::$admin_statuses)) {
      $this->admin_reviewed = true;
    }

    if (!isset(self::$status_labels[$status])) {
      return false;
    }
    if (!$this->isStatus(self::STATUS_PAID) && $status == self::STATUS_PAID) {
      $this->set('date_paid', date('Y-m-d H:i:s'));
    }
    if($status == self::STATUS_APPROVED && $this->approved_by_id() === NULL) {
       self::set_approved_by_id();
    }
    $this->set('status', intval($status));
  }

  //----------------------------------------------------------------------------------------------//
  //----------------SAVE METHODS------------------------------------------------------------------//
  //----------------------------------------------------------------------------------------------//

  public function submit()
  {
    if (!$this->submitable()) {
      return false;
    }
    if (!$this->status()) {
      $this->set('date_submitted', date('Y-m-d H:i:s'));
    } else {
      $this->set('fields_last_changed', implode('|', array_keys($this->updateQueries)) . ($this->new_receipts_uploaded ? '|receipts' : ''));
      $this->set('date_resubmitted', date('Y-m-d H:i:s'));
    }
    $this->updateQueries['status'] = '`status`=' . $this->set_submission_status();
    return $this->save() && ($this->status = $this->set_submission_status());
  }

  public function set_submission_status()
  {
    if ($this->status == self::STATUS_NOTSUBMITTED && $this->last_status) {
      return $this->get_status_from_last();
    }
    if ($this->updatesRequired()) {
      return self::STATUS_RESUBMITTED;
    }
    return self::STATUS_SUBMITTED;
  }

  public function get_status_from_last()
  {
    if ($this->last_status == self::STATUS_UPDATE) {
      return self::STATUS_RESUBMITTED;
    }
    return $this->last_status;
  }

  /**
   * Should be called before changing status
   *
   * @return void
   */
  public function set_draft_status()
  {
    if ($this->updatesRequired()) {
      return false;
    }

    $last_status = intval($this->status);

    if ($last_status == self::STATUS_NOTSUBMITTED) {
      $last_status = $this->last_status;
    }
    $this->set('last_status', $last_status);
    $this->set('status', self::STATUS_NOTSUBMITTED);
  }

  public function save()
  {
    if (!$this->editable()) {
      return false;
    }
    if (!$this->reimbursement_id) {
      if (!$this->student_id) {
        return false;
      }
      core_db::runQuery('INSERT INTO mth_reimbursement (student_id) 
                          VALUES (' . $this->student_id . ')');
      $this->reimbursement_id = core_db::getInsertID();
      if ($this->new_receipts_uploaded) {
        foreach ($this->receipt_file_ids as $file_id) {
          mth_reimbursementreceipt::create($this->reimbursement_id, $file_id);
        }
      }
    }

    if (!empty($this->receipt_file_ids())) {

      $reimbursementReceipts = mth_reimbursementreceipt::getReceiptsByReimbursementId($this->reimbursement_id);
      $newSubmission = $this->admin_reviewed ? mth_reimbursementsubmission::create($this->reimbursement_id) : 0;

      foreach ($reimbursementReceipts as $receipt) {
        if (!$receipt->submissionId() && $newSubmission) {
          $receipt->SetSubmissionIdIfNull($newSubmission->id());
        }
        $receipt->save($this->reimbursement_id);
      }
    }
    return parent::runUpdateQuery('mth_reimbursement', 'reimbursement_id=' . $this->id());
  }

  public function doesExist()
  {
    if (empty($this->updateQueries)) {
      return false;
    }
    $query = $this->updateQueries;
    unset($query['resource_request_id']);
    unset($query['date_paid']);
    return core_db::runGetObject('SELECT * FROM mth_reimbursement WHERE ' . implode(' AND ', $query));
  }

  public function delete()
  {
    $this->each_receipt(true);
    while ($file = $this->each_receipt()) {
      $file->delete();
    }
    return core_db::runQuery('DELETE FROM mth_reimbursement_reciept WHERE reimbursement_id=' . $this->id())
      && core_db::runQuery('DELETE FROM mth_reimbursement WHERE reimbursement_id=' . $this->id());
  }

  public function deleteByResourceRequestId()
  {
    return core_db::runQuery('DELETE FROM mth_reimbursement WHERE resource_request_id=' . $this->resource_request_id());
  }

  public function isTechEnabled()
  {
    return in_array($this->type(true), self::techEnabled());
  }

  public static function techEnabled()
  {
    return [self::TYPE_TECH, self::TYPE_SUPPLEMENTAL];
  }

  //----------------------------------------------------------------------------------------------//
  //----------------STATIC METHODS----------------------------------------------------------------//
  //----------------------------------------------------------------------------------------------//


  public static function available($type = NULL, mth_schoolYear $year = NULL, mth_student $student = NULL)
  {
    if (!$year && !($year = mth_schoolYear::getCurrent())) {
      return false;
    }

    if ($type == self::TYPE_TECH) {
      $date = $year->reimburse_tech_open();
    } else {
      $date = $year->reimburse_open();
    }

    return $date <= time() && self::type_can_be_used_for_student($type, $year, $student);
  }

  public static function open(mth_schoolYear $year = NULL)
  {
    if (!$year && !($year = mth_schoolYear::getCurrent())) {
      return false;
    }
    return $year->reimburse_open() <= time() && time() <= $year->reimburse_close();
  }

  protected static function availableTypes(mth_student $student = NULL, mth_schoolYear $year = NULL)
  {
    $availableTypes = &self::$cache[$student ? $student->getID() : NULL][$year ? $year->getID() : NULL];
    if (!$availableTypes) {
      $availableTypes = array();
      foreach (array_keys(self::$type_labels) as $type) {
        if (self::available($type, $year, $student) && self::isTypeEnable($type)) {
          $availableTypes[] = $type;
        }
      }
    }
    return $availableTypes;
  }

  public static function isTypeEnable($type)
  {
    return in_array($type, mth_reimbursementtype::getEnabledPlaceHolders());
  }

  protected static function type_can_be_used_for_student($type, mth_schoolYear $year, mth_student $student = NULL)
  {
    if (!$student || $type != self::TYPE_TECH) {
      return TRUE;
    }
    return core_db::runGetValue('SELECT COUNT(reimbursement_id) 
                                  FROM mth_reimbursement 
                                  WHERE `type`=' . self::TYPE_TECH . ' 
                                    AND school_year_id=' . $year->getID() . ' 
                                    AND student_id=' . $student->getID()) < self::LIMIT_TECH;
  }

  protected static function availableSchedulePeriodIDs(mth_schedule $schedule, $reimbursement_type_id)
  {
    if (NULL === ($schedulePeriodIDs = &self::$cache['availableSchedulePeriodIDs'][$schedule->id()][$reimbursement_type_id])) {
      $schedulePeriodIDs = array();
      foreach ($schedule->allPeriods(false) as $schedulePeriod) {

        $mergedPeriodNum = $reimbursement_type_id == self::TYPE_CUSTOM
          && self::scheduleHasMergedPeriods($schedule, $reimbursement_type_id)
          && in_array($schedulePeriod->period()->num(), self::$merge_custom_periods);
        if (
          !in_array($schedulePeriod->id(), self::mergedSchedulePeriodIDs($schedule, $reimbursement_type_id))
          || (!$mergedPeriodNum && self::countReimbursements($schedulePeriod) >= self::LIMIT)
          || ($mergedPeriodNum && self::countReimbursements($schedulePeriod) >= self::LIMIT_MERGED)
          || ($schedulePeriod->second_semester() && $schedulePeriod->noChanges())
        ) {
          continue;
        }

        $schedulePeriodIDs[] = $schedulePeriod->id();
      }
    }
    return $schedulePeriodIDs;
  }

  protected static function mergedSchedulePeriodIDs(mth_schedule $schedule, $reimbursement_type_id)
  {
    if (NULL === ($schedulePeriodIDs = &self::$cache['mergedSchedulePeriodIDs'][$schedule->id()][$reimbursement_type_id])) {
      $schedulePeriodIDs = array();
      $mergedPeriodsAccountedFor = false;
      foreach ($schedule->allPeriods(false) as $period_num => $schedulePeriod) {
        if (($reimbursement_type_id == self::TYPE_CUSTOM
            || !$mergedPeriodsAccountedFor
            || !in_array($period_num, self::$merge_custom_periods))
          && self::schedulePeriodAvailableForType($schedulePeriod, $reimbursement_type_id)
        ) {

          if (!$mergedPeriodsAccountedFor) {
            $mergedPeriodsAccountedFor = ($reimbursement_type_id == self::TYPE_CUSTOM
              && in_array($period_num, self::$merge_custom_periods));
          }

          $schedulePeriodIDs[] = $schedulePeriod->id();
        }
      }
    }
    return $schedulePeriodIDs;
  }

  public function mergedPeriodIDs()
  {
    return self::mergedSchedulePeriodIDs($this->schedule_period()->schedule(), $this->type);
  }

  protected static function replacedSchedulePeriodIDs(mth_schedule $schedule)
  {
    if (NULL === ($schedulePeriodIDs = &self::$cache['replacedSchedulePeriodIDs'][$schedule->id()])) {
      $schedulePeriodIDs = array();
      $lastPeriodId = null;
      foreach ($schedule->allPeriods() as $period_num => $schedulePeriod) {
        if ($schedulePeriod->second_semester() && $lastPeriodId) {
          $schedulePeriodIDs[] = $lastPeriodId;
        }
        $lastPeriodId = $schedulePeriod->id();
      }
    }
    return $schedulePeriodIDs;
  }

  protected static function scheduleHasMergedPeriods(mth_schedule $schedule, $reimbursement_type_id)
  {
    if (NULL === ($scheduleHasMergedPeriods = &self::$cache['scheduleHasMergedPeriods'][$schedule->id()])) {
      $scheduleHasMergedPeriods = false;
      $mergedSchedulePeriodIDs = self::mergedSchedulePeriodIDs($schedule, $reimbursement_type_id);
      foreach ($schedule->allPeriods() as $schedulePeriod) {
        if (
          self::schedulePeriodAvailableForType($schedulePeriod, $reimbursement_type_id)
          && !in_array($schedulePeriod->id(), $mergedSchedulePeriodIDs)
        ) {
          $scheduleHasMergedPeriods = true;
          break;
        }
      }
    }
    return $scheduleHasMergedPeriods;
  }

  protected static function countReimbursements(mth_schedule_period $schedulePeriod)
  {
    $count = &self::$cache['countReimbursements'][$schedulePeriod->id()];
    if (!$count) {
      $stmt = self::theSamePeriodStmt($schedulePeriod);
      $count = core_db::runGetValue('SELECT COUNT(reimbursement_id) 
                                      FROM mth_reimbursement as mr
                                      inner join mth_schedule_period as msp on msp.schedule_period_id=mr.schedule_period_id
                                      WHERE ' . $stmt);
    }
    return $count;
  }


  protected static function schedulePeriodAvailableForType(mth_schedule_period $schedulePeriod, $reimbursement_type_id)
  {
    switch ($reimbursement_type_id) {
      case self::TYPE_CUSTOM:
        return $schedulePeriod->course_type(true) == mth_schedule_period::TYPE_CUSTOM;
      case self::TYPE_TP:
        return $schedulePeriod->course_type(true) == mth_schedule_period::TYPE_TP;
      case self::TYPE_SOFTWARE:
        return $schedulePeriod->course_type(true) == mth_schedule_period::TYPE_MTH
          && $schedulePeriod->period()->num() != 1;
      case self::TYPE_COLLEGE_CREDIT:
        return true;
      case self::TYPE_TECH:
        return false;
      case self::TYPE_SUPPLEMENTAL:
        return false;
    }
  }

  /**
   *
   * @param mth_parent $parent
   * @param mth_student $student
   * @param mth_schoolYear $school_year
   * @param int|array $status Status intager or array of statuses
   * @param bool $reset
   * @return mth_reimbursement
   */
  public static function each(
    mth_parent $parent = NULL,
    mth_student $student = NULL,
    mth_schoolYear $school_year = NULL,
    $status = NULL,
    $reset = false
  ) {
    $result = &self::$cache['each'][($parent ? $parent->getID() : 'ALL')][($student ? $student->getID() : 'ALL')][$school_year ? $school_year->getID() : 'ALL'][$status ? implode('-', (array) $status) : 'ALL'];
    if (!isset($result)) {
      $result = core_db::runQuery('SELECT * FROM mth_reimbursement 
                                    WHERE 1
                                      ' . ($parent ? 'AND parent_id=' . $parent->getID() : '') . '
                                      ' . ($student ? 'AND student_id=' . $student->getID() : '') . '
                                      ' . ($school_year ? 'AND school_year_id=' . $school_year->getID() : '') . '
                                      ' . ($status ? 'AND `status` IN (' . implode(',', array_map('intval', (array) $status)) . ')' : '') . '
                                    ORDER BY reimbursement_id ASC');
    }
    if (!$reset && ($reimbursement = $result->fetch_object('mth_reimbursement'))) {
      return $reimbursement;
    }
    $result->data_seek(0);
    return NULL;
  }

  /**
   * @param mth_schoolYear $school_year
   * @param string $group
   * @param int|array $status
   * @param boolean $include_amount_sum
   * 
   * @return mth_reinbursement
   */
  public static function allGroupBy(mth_schoolYear $school_year, $group = NULL, $status = NULL, $include_amount_sum = false, $reset = false, $parentNameDuplicates = false)
  {
     $parentNames = '';
     if($parentNameDuplicates) {
        $parentNames = ' JOIN mth_parent as mp ON mp.parent_id=mr.parent_id
        JOIN mth_person AS mper ON mper.person_id=mp.person_id
        LEFT JOIN (SELECT COUNT(*) AS occurrences, first_name, last_name
         FROM (SELECT person_id, first_name, last_name FROM mth_person 
         UNION 
         SELECT person_id, preferred_first_name, preferred_last_name FROM mth_person)
         AS mth_person_names
         WHERE mth_person_names.last_name IS NOT NULL
         GROUP BY mth_person_names.first_name, mth_person_names.last_name
         HAVING occurrences > 1) AS duplicates ON ((duplicates.first_name=mper.first_name AND duplicates.last_name=mper.last_name) OR (duplicates.first_name=mper.preferred_first_name AND duplicates.last_name=mper.preferred_last_name)) ';
     }
    $result = &self::$cache['each-group-by'][$school_year ? $school_year->getID() : 'ALL'][$status ? implode('-', (array) $status) : 'ALL'];
    if (!isset($result)) {
      $result = core_db::runQuery('SELECT mr.* ' . ($include_amount_sum ? ',SUM(mr.amount) as total_amount' : '') . ($parentNameDuplicates ? ',duplicates.occurrences' : '') . ' FROM mth_reimbursement as mr'
         . $parentNames . '
                                  WHERE mr.`is_direct_order` = 0
                                  ' . ($school_year ? 'AND mr.`school_year_id`=' . $school_year->getID() : '') . '
                                  ' . ($status ? 'AND mr.`status` IN (' . implode(',', array_map('intval', (array) $status)) . ')' : '') . '
                                  ' . ($group ? 'GROUP BY mr.`' . $group . '`' : '') . '
                                  ORDER BY mr.reimbursement_id ASC');
    }
    if (!$reset && $reimbursement = $result->fetch_object('mth_reimbursement')) {
      return $reimbursement;
    }
    $result->data_seek(0);
    return NULL;
  }

  /**
   *
   * @param int $reimbursement_id
   * @return mth_reimbursement
   */
  public static function get($reimbursement_id)
  {
    $reimbursement = &self::$cache['get'][$reimbursement_id];
    if (!isset($reimbursement)) {
      $reimbursement = core_db::runGetObject('SELECT * FROM mth_reimbursement WHERE reimbursement_id=' . (int) $reimbursement_id, 'mth_reimbursement');
    }
    return $reimbursement;
  }

  public static function statusCounts(mth_schoolYear $year)
  {
    $counts = array();
    $exclude = implode(',', self::$amin_types);
    $result = core_db::runQuery('SELECT `status`, count(*)
                                  FROM mth_reimbursement 
                                  WHERE school_year_id=' . $year->getID() . '
                                  and type not in(' . $exclude . ')
                                  GROUP BY `status`');
    while ($r = $result->fetch_row()) {
      $counts[$r[0]] = $r[1];
    }
    $result->free_result();
    return $counts;
  }
  /**
   * Get Ahead date_modifed
   */
  public static function detectDiff()
  {
    return core_db::runGetValue('select count(*) from mth_reimbursement where last_modified > NOW()');
  }

  public static function fixDiff()
  {
    return core_db::runQuery('update mth_reimbursement set last_modified=NOW() where last_modified > NOW()');
  }

  public static function typeCounts(mth_schoolYear $year, $statuses)
  {
    $counts = array();
    $exclude = implode(',', self::$amin_types);
    $_statuses = $statuses ? (' and status in(' . implode(',', $statuses) . ')') : '';
    $result = core_db::runQuery('SELECT `type`, count(*)
                                  FROM mth_reimbursement 
                                  WHERE school_year_id=' . $year->getID() . $_statuses . '
                                  and type not in(' . $exclude . ')
                                  GROUP BY `type`');
    while ($r = $result->fetch_row()) {
      $counts[$r[0]] = $r[1];
    }
    $result->free_result();
    return $counts;
  }

  public static function methodCounts(mth_schoolYear $year, $statuses)
  {
    $counts = array();
    $exclude = implode(',', self::$amin_types);
    $_statuses = $statuses ? (' and status in(' . implode(',', $statuses) . ')') : '';
    $result = core_db::runQuery('SELECT `is_direct_order`, count(*)
                                  FROM mth_reimbursement 
                                  WHERE school_year_id=' . $year->getID() . $_statuses . '
                                  and type not in(' . $exclude . ')
                                  GROUP BY `is_direct_order`');
    while ($r = $result->fetch_row()) {
      $counts[$r[0]] = $r[1];
    }
    $result->free_result();
    return $counts;
  }

  public static function statusLabel($statusNumber)
  {
    if (isset(self::$status_labels[$statusNumber])) {
      return self::$status_labels[$statusNumber];
    }
    return NULL;
  }

  public static function countByStudent(mth_student $student)
  {
    return core_db::runGetValue('SELECT COUNT(reimbursement_id) AS `count` FROM mth_reimbursement 
                                  WHERE student_id=' . $student->getID());
  }

  //get all mergeCustomPeriodIds
  public static function mergeCustomPeriodIDS(mth_schedule $schedule)//for the dropdown
  {
    $values =[];
    $sql = 'SELECT schedule_period_id FROM mth_schedule_period 
            WHERE schedule_id= '.$schedule->id().' AND course_type = 3 AND period IN ('.implode(',', self::$merge_custom_periods).')
            ORDER BY `period` ASC, second_semester ASC';
    $results = core_db::runQuery($sql);
   
    while($r = $results->fetch_assoc()){
      $values[]=$r['schedule_period_id'];
    }
    return $values;

  }
}
