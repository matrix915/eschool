<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'New Inactive Students - ' . $year;
$reportArr = array([
  'Parent First',
  'Parent Last',
  'Phone Number',
  'Email',
  'Student First',
  'Student Last',
  'Student Grade Level',
  'Date Application Accepted',
  'Packet Status/Date Packet was Submitted'
]);


$applications = mth_application::getApplications([
  'Status' => mth_application::STATUS_ACCEPTED,
  'SchoolYear' => $year->getID()
]);

foreach ($applications as $application) {
    /* @var $student mth_student */
    if(!($student = $application->getStudent())){
      continue;
    }

    if($student->getStatus($year)){
      //SKIP STUDENT WITH STATUS ALREADY
      continue;
    }

    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        break;
    }
    
    if (!($packet = mth_packet::getStudentPacket($student))) {
        $missingPackets++;
    }

    if($packet && $packet->isAccepted()){
      continue;
    }

    $stat = $packet ? (!$packet->isMissingInfo() && $packet->getDateSubmitted('m/d/Y') ? $packet->getDateSubmitted('m/d/Y') : $packet->getStatus()) : '';
  
    $reportArr[] = [
      $parent->getFirstName(),
      $parent->getLastName(),
      $parent->getPhone(),
      $parent->getEmail(),
      $student->getFirstName(),
      $student->getLastName(),
      $student->getGradeLevelValue($year->getID()),
      $application?$application->getDateAccepted('m/d/Y'):'',
      $stat
    ];
}

include ROOT . core_path::getPath('../report.php');