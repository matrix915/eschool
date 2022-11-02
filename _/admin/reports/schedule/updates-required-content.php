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

while ($schedule = mth_schedule::each(array(mth_schedule::STATUS_CHANGE,mth_schedule::STATUS_CHANGE_POST))) {
    if (!($student = $schedule->student())
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