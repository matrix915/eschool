<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);


$reportArr = array(array(
    'Student',
    'Household Size',
    'Household Gross Monthly Income'
));



$file = 'Income - ' . $year;


foreach ($filter->getStudents() as $student) {
    if(!($packet = mth_packet::getStudentPacket($student))){
        core_notify::addError($student."'s packet is missing");
        continue;
    }
   
    $reportArr[] = [
        $student,
        $packet->getHouseholdSize(),
        $packet->getHouseholdIncome() 
    ];

}


include ROOT . core_path::getPath('../report.php');