<?php
($year = $_SESSION['mth_reports_school_year']) || die('Year not set');

$file = 'Referred By Report - ' . $year;
$reportArr = array(array(
    'Referred By',
    'Student Last',
    'Student First',
    'Grade Level',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Parent Phone',
    'Submit Date',
    'App. Status'
));
$columnSort = [[]];

while ($application = mth_application::eachOfYear($year)) {
    if (!$application->getReferredBy()
        || !($student = $application->getStudent())
        || !($parent = $student->getParent())
    ) {
        continue;
    }
    $reportArr[] = array(
        $application->getReferredBy(),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getGradeLevel(),
        $parent->getPreferredLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone(),
        $application->getDateSubmitted('m/d/Y'),
        $application->getStatus()
        
    );

    $columnSort[] = [
        null,null,null,null,null,null,null,null,$application->getDateSubmitted(),null
    ];
}

include ROOT . core_path::getPath('../report.php');