<?php
/** @var $year mth_schoolYear */
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Schedules Not Unlocked for 2nd Semester Changes - ' . $year;
$reportArr = array(array(
    'Student Last',
    'Student First',
    'Grade',
    'Student Email',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Parent Phone',
));

if($year->getSecondSemOpen()<time()){
    while ($schedule = mth_schedule::eachOfYear($year)) {
        if ($schedule->second_sem_change_available() && !$schedule->isToChange()) {
            
            if (!($student = $schedule->student())
            || !($parent = $student->getParent())
            ) {
                continue;
            }

            $packet = mth_packet::getStudentPacket($student);
            $reportArr[] = array(
                $student->getLastName(),
                $student->getFirstName(),
                $student->getGradeLevel(),
                $student->getEmail(),
                $parent->getPreferredLastName(),
                $parent->getPreferredFirstName(),
                $parent->getEmail(),
                $parent->getPhone()
            );
        }
    }
}

include ROOT . core_path::getPath('../report.php');