<?php

($year = $_SESSION['mth_reports_school_year']) || die('No current year');
/* @var $nextyear mth_schoolYear */
($nextyear = $year->getNextYear()) || die('no next year');

$file = $nextyear . ' Intent to Re-enroll Not Submitted';
$reportArr = array(array(
  'Student First',
  'Student Last',
  'Student Grade Level',
  'Parent First',
  'Parent Last',
  'Parent Email',
  'Parent Phone Number',
  'District of Residence',
  'School Year First Joined'
));

$filter = new mth_person_filter();
$filter->setStatus(array(mth_student::STATUS_ACTIVE));
$filter->setStatusYear(array($year->getID()));
$filter->setExcludeStatusYear(array($nextyear->getID()));
$filter->setGradeLevel(['K', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);

$withdrawn_students = mth_withdrawal::getStudentAutomaticallyWithdrawnByYearId($nextyear->getID());

foreach ($filter->getStudents() as $student) {
  /* @var $student mth_student */
  if (!($parent = $student->getParent())) {
    core_notify::addError('Parent not foud for ' . $student);
    continue;
  }

  $district = 'N/A';
  if ($packet = mth_packet::getStudentPacket($student)) {
    $district = $packet->getSchoolDistrict();
  }

  if ($student->isWithdrawn()) {
    if ($withdrawal = mth_withdrawal::getByStudent($student->getID(), $year->getID())) {
      if (!null == ($withdrawal_creator = $withdrawal->getCreatedByUser()) && $withdrawal_creator->isAdmin()) {
        continue;
      }
      $withdrawal_date = $withdrawal->reenroll_action('m/d/Y');
      if ($withdrawal_date) {
        continue;
      }
    }
  }

  $reportArr[] = array(
    $student->getFirstName(),
    $student->getLastName(),
    $student->getGradeLevel(),
    $parent->getPreferredFirstName(),
    $parent->getPreferredLastName(),
    $parent->getEmail(),
    $parent->getPhone(),
    $district,
    $student->getFirstSchoolYear()
  );
}

foreach($withdrawn_students as $student) {
  if (!($parent = $student->getParent())) {
    core_notify::addError('Parent not foud for ' . $student);
    continue;
  }

  $district = 'N/A';
  if ($packet = mth_packet::getStudentPacket($student)) {
    $district = $packet->getSchoolDistrict();
  }

  $reportArr[] = array(
    $student->getFirstName(),
    $student->getLastName(),
    $student->getGradeLevel(),
    $parent->getPreferredFirstName(),
    $parent->getPreferredLastName(),
    $parent->getEmail(),
    $parent->getPhone(),
    $district,
    $student->getFirstSchoolYear()
  );
}

include ROOT . core_path::getPath('../report.php');
