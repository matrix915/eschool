<?php

use mth\yoda\homeroom\Query;

($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$file = '80% or Less Homeroom Grade';
$reportArr = array(array(
    'Student Name',
    'Grade',
    'Homeroom Grade',
    'Parent Name',
    'Parent Email',
    'Parent Phone'
));

$query = new Query();
$query->setYear([$year->getID()]);
$query->setStatus([mth_student::STATUS_ACTIVE,mth_student::STATUS_PENDING],$year->getID());
if($enrollments = $query->getAll(req_get::int('page'))){
    foreach($enrollments as $enrollment){
        $stgrade = $enrollment->getGrade();
        if($stgrade > 80){
            continue;
        }
        if(!$student = $enrollment->student()){
            continue;
        }

        if (!($parent = $student->getParent())) {
            core_notify::addError('Parent Missing for ' . $student);
            continue;
        }

        $reportArr[] = array(
            $student->getName(true,true),
            $student->getGradeLevelValue($year->getID()),
            is_null($stgrade)?'NA': $stgrade.'%',
            $parent->getName(true),
            $parent->getEmail(),
            $parent->getPhone()
        );
    }
}

include ROOT . core_path::getPath('../report.php');