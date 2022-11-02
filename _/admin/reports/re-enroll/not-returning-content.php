<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');
/* @var $nextyear mth_schoolYear */
($nextyear = $year->getNextYear()) || die('no next year');

$file = $nextyear . ' Intent to Re-enroll Not Returning';
$reportArr = array(array(
     'Date Submitted',
     'Student First',
     'Student Last',
     'Student Grade Level',
     'Parent First',
     'Parent Last',
     'Parent Email',
     'Parent Phone Number',
     'District of Residence',
     'School Year First Joined',
     'Reason for Not Returning'
));

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_WITHDRAW]);
$filter->setStatusYear(array($nextyear->getID()));

foreach ($filter->getStudents() as $student) {
     /* @var $student mth_student */
     if (!($parent = $student->getParent())) {
          continue;
     }
     if (!($packet = mth_packet::getStudentPacket($student)) && !$student->isActive()) {
          continue;
     }
     $reason = '';
     $withdraw_date = '';
     if ($withdraw = mth_withdrawal::getByStudent($student->getID())) {
          $reason = $withdraw->reason_txt();
          $withdraw_date = $withdraw->reenroll_action('m/d/Y');
          if (!$withdraw->reenroll_action()) {
               continue;
          }
     }

     $reportArr[] = array(
          $withdraw_date,
          $student->getFirstName(),
          $student->getLastName(),
          $student->getGradeLevel(),
          $parent->getPreferredFirstName(),
          $parent->getPreferredLastName(),
          $parent->getEmail(),
          $parent->getPhone(),
          $packet->getSchoolDistrict(),
          $student->getFirstSchoolYear(),
          $reason
     );
}

include ROOT . core_path::getPath('../report.php');
