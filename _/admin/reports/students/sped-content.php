<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'SPED Students - ' . $year;

$reportArr = [
     [
          'Type',
          'Status',
          'Date Packet Accepted',
          'School of Enrollment',
          'Legal Student First Name',
          'Legal Student Last Name',
          'Current Year Grade Level',
          'Gender',
          'Parent First Name',
          'Parent Last Name',
          'Parent Email',
          'Parent Phone',
          'City of Residence'
     ]
];

$filter = new mth_person_filter();
$filter->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
$filter->setStatusYear(array($year->getID()));
$filter->setSpecialEd([mth_student::SPED_504, mth_student::SPED_EXIT, mth_student::SPED_IEP]);
foreach ($filter->getStudents() as $student) {
     if (!($parent = $student->getParent())) {
          core_notify::addError('Parent Missing for ' . $student);
          break;
     }

     if (!($packet = mth_packet::getStudentPacket($student))) {
          core_notify::addError('Packet Missing for ' . $student);
          continue;
     }

     $reportArr[] = [
          $student->specialEd(true),
          $student->getSOEStatus($year, $packet),
          $packet->getDateAccepted('m/d/Y'),
          $student->getSOEname($year),
          $student->getFirstName(),
          $student->getLastName(),
          $student->getGradeLevel(),
          $student->getGender(),
          $parent->getFirstName(),
          $parent->getLastName(),
          $parent->getEmail(),
          $parent->getPhone(),
          $parent->getCity()
     ];
}

include ROOT . core_path::getPath('../report.php');
