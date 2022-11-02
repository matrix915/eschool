<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);


$reportArr = array(array(
    'Race',
    'Total'
));

$races = mth_packet::getAvailableRace();
foreach($races as $race){
    $reportArr[] = [$race,0];
}
$reportArr[] = ['Other',0];

$file = 'Ethnicity - ' . $year;

$students_with_packet = $filter->getStudents();
$with_packet = 0;
foreach ($students_with_packet as $student) {
    if(!($packet = mth_packet::getStudentPacket($student))){
        core_notify::addError($student."'s packet is missing");
        continue;
    }
    $with_packet++;
    if(!($student_races = $packet->getRace(true,true))){
        $reportArr[count($reportArr)-1][1] += 1;
        continue;
    }

    $matches = array_values(array_intersect($races,$student_races));
    $other_count = abs(count($matches) - count($student_races));

    foreach($matches as $match_race){
        foreach($reportArr as $key=>$report){
            if($report[0] == $match_race){
                $reportArr[$key][1] += 1; 
            }
        }
    }

    if($other_count > 0){
        $reportArr[count($reportArr)-1][1] += $other_count;
    }

}

foreach($reportArr as $key=>&$report){
    if($key == 0){continue;}
    $report[1] = ($report[1]?round(($report[1]/$with_packet)*100,2):'0').'%';
}


include ROOT . core_path::getPath('../report.php');