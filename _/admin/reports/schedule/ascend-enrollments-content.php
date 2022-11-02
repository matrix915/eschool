<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$ascend_providers = [26,27];

$statuses = array(mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST);


$reportArr = array(array(
    'Student First',
    'Student Last',
    'Student Grade',
    'Parent First',
    'Parent Email',
));

$query = new \mth\schedule\query();
$query->setProviderIds($ascend_providers)
    ->setStatuses($statuses)
    ->setSchoolYearIds([$year->getID()]);
   

foreach($query->getAll() as $schedule){
    if (!($student = $schedule->student())
        || !($parent = $student->getParent())
    ) {
        continue;
    }

    $reportArr[] = array_merge(array(
        $student->getFirstName(),
        $student->getLastName(),
        $student->getGradeLevelValue($year->getID()),
        $parent->getFirstName(),
        $parent->getEmail()
    ));
}


$file = 'Ascend enrollments - ' . $year;

include ROOT . core_path::getPath('../report.php');