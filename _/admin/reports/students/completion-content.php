<?php

/** @var $year mth_schoolYear */
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$file = 'Completion Certificates - '.$year->getName();
$reportArr = array(array(
    'Student First Name',
    'Student Last Name',
    'Street Address',
    'City',
    'Zipcode',
));

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE,mth_student::STATUS_TRANSITIONED, mth_student::STATUS_GRADUATED]);
$filter->setStatusYear($year->getID());
$filter->setGradeLevel([12]);

foreach ($filter->getStudents() as $student) {
    if(!($parent = $student->getParent())){ continue; }

    if (!($address = $parent->getAddress())) {
          core_notify::addError('Address Missing for ' . $parent);
          continue;
     }


    $reportArr[] = array(
        $student->getFirstName(),
        $student->getLastName(),
        $address ? ($address->getStreet().($address->getStreet2()?PHP_EOL.$address->getStreet2():'')):'',
        $address ? $address->getCity() : '',
        $address ? $address->getZip() : '',
    );
}

/** @noinspection PhpIncludeInspection */
include ROOT . core_path::getPath('../report.php');