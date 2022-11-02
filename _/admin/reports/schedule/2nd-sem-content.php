<?php
/** @var $year mth_schoolYear */
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Schedules Unlocked for 2nd Semester Changes - ' . $year;
$reportArr = array(array(
    'Student Last',
    'Student First',
    'Student Email',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Parent Phone',
    'Mid-Year',
));

if($year->getSecondSemOpen()<time()){
    while ($schedule = mth_schedule::eachOfYear($year,[mth_schedule::STATUS_CHANGE_POST, mth_schedule::STATUS_CHANGE])) {
        if (!($student = $schedule->student())
            || !($parent = $student->getParent())
        ) {
            continue;
        }
        $packet = mth_packet::getStudentPacket($student);
        $reportArr[] = array(
            $student->getLastName(),
            $student->getFirstName(),
            $student->getEmail(),
            $parent->getPreferredLastName(),
            $parent->getPreferredFirstName(),
            $parent->getEmail(),
            $parent->getPhone(),
            ($student->isMidYear($year)) ? '<span style="color: red;">Yes</span>' : 'No'
        );
    }
}

include ROOT . core_path::getPath('../report.php');