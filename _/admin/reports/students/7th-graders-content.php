<?php

/** @var $year mth_schoolYear */
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$file = '7th Graders - '.$year->getName();
$reportArr = array(array(
    'Last Name',
    'First Name',
    'Grade',
    'New/Return',
    'Parent Email',
    'School Year Packet Accepted',
    'First Year School of Enrollment',
    'Exemption form for Tdap Booster, Meningococcal and Varicella?'
));

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE,mth_student::STATUS_PENDING]);
$filter->setStatusYear($year->getID());
$filter->setGradeLevel(7);

foreach ($filter->getStudents() as $student) {
    if(!($parent = $student->getParent())){ continue; }
    $packet = mth_packet::getStudentPacket($student);
    $year_accepted = $packet?mth_schoolYear::getByDate($packet->getDateAccepted()):null;
    
    $reportArr[] = array(
        $student->getLastName(),
        $student->getFirstName(),
        $student->getGradeLevelValue($year->getID()),
        ($student->isReturnStudent($year)?'Return':'New'),
        $parent->getEmail(),
        $year_accepted,
        $student->getFirstSOE(true,false),
        $packet?($packet->isExempImmunization()?'Yes':'No'):'No',
    );
}

/** @noinspection PhpIncludeInspection */
include ROOT . core_path::getPath('../report.php');