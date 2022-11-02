<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');


$reportArr = array(array(
    'Previous School',
    'Current School',
    'Student Last',
    'Student First',
    'Student Email',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Parent Phone',
));

while ($packet = mth_packet::each($year, array(mth_packet::STATUS_ACCEPTED))) {
    if (!($student = $packet->getStudent())
        || !($parent = $packet->getStudent()->getParent())
    ) {
        continue;
    }
    $reportArr[] = array(
        $packet->getLastSchoolName(),
        $student->getSchoolOfEnrollment(),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getEmail(),
        $parent->getPreferredLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone()
    );
}
$file = 'Previous Schools - ' . $year;

include ROOT . core_path::getPath('../report.php');