<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Notes - ' . $year;

$reportArr = [
     [
          'Parent First Name',
          'Parent Last Name',
          'Parent Email',
          'Parent Phone',
          'Note'
     ]
];

$filter = new mth_person_filter();
$filter->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
$filter->setStatusYear(array($year->getID()));
$filter->sethasNote(true);
foreach ($filter->getParents() as $parent) {
     if(!($note = $parent->note()) || empty(trim($note->getNote()))){
          continue;
     }
     $reportArr[] = [
          $parent->getFirstName(),
          $parent->getLastName(),
          $parent->getEmail(),
          $parent->getPhone(),
          $note->getNote()
     ];
}

include ROOT . core_path::getPath('../report.php');