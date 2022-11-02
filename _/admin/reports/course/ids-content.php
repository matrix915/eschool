<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Course SIS IDs - ' . $year;
$reportArr = array(
    array(
        'SIS ID',
        'Subject',
        'Course'
    )
);

while ($subject = mth_subject::getEach()) {
    while ($course = mth_course::getEach($subject)) {
        $reportArr[] = array(
            $year->getStartYear() . '-' . $course->getID(),
            $subject->getName(),
            $course->title()
        );
    }
}

include ROOT . core_path::getPath('../report.php');