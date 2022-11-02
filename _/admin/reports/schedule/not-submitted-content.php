<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Schedules Not Submitted - ' . $year;
$reportArr = array(array(
    'Schedule Status',
    'Date Packet was Accepted',
    'Student Last',
    'Student First',
    'Student Email',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Parent Phone',
));

$filter = new mth_person_filter();
$filter->setStatus(mth_student::STATUS_PENDING);
$filter->setStatusYear($year->getID());

$students = $filter->getStudents();

foreach ($students as $student) {
    /* @var $student mth_student */
    if ((($schedule = mth_schedule::get($student, $year))
            && ($schedule->isSubmited() || $schedule->isToChange()))
        || !($parent = $student->getParent())
    ) {
        continue;
    }
    $packet = mth_packet::getStudentPacket($student);
    $reportArr[] = array(
        $schedule ? $schedule->status() : 'Not Started',
        $packet ? $packet->getDateAccepted('m/d/Y') : '',
        $student->getLastName(),
        $student->getFirstName(),
        $student->getEmail(),
        $parent->getPreferredLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone()
    );
}

include ROOT . core_path::getPath('../report.php');