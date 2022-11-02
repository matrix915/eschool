<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$lemi_provider_id = [24];

$statuses = array(mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST);


$reportArr = array(array(
    'Student First',
    'Student Last',
    'Student Grade',
    'Parent First',
    'Parent Email',
    'Period 1',
    'Period 2',
    'Period 3',
    'Period 4',
    'Period 5',
    'Period 6',
    'Period 7'
));

$query = new \mth\schedule\query();
$query->setProviderIds($lemi_provider_id)
    ->setStatuses($statuses)
    ->setSchoolYearIds([$year->getID()]);
    
if(req_get::bool('grade_level')){
    $query->setGradeLevel(req_get::txt_array('grade_level'),[$year->getID()]);
}

if($result = $query->getAll()){
    foreach($result as $schedule){
        if (!($student = $schedule->student())
            || !($parent = $student->getParent())
        ) {
            continue;
        }

        $periods = [];
        while ($period = mth_period::each()){
            if ($sched_p = mth_schedule_period::get($schedule, $period, $schedule->schoolYear()->getSecondSemOpen() < time())) {
                $periods[$period->num()] = $sched_p->mth_provider_id()==17?$sched_p->courseName()." (Dual Enrollment)":$sched_p->courseName();
            }else{
                $periods[$period->num()] = '';
            }
        }
        

        $reportArr[] = array_merge(array(
            $student->getFirstName(),
            $student->getLastName(),
            $student->getGradeLevelValue($year->getID()),
            $parent->getFirstName(),
            $parent->getEmail()
        ),$periods);
    }
}


$file = 'LEMI enrollments - ' . $year;

include ROOT . core_path::getPath('../report.php');