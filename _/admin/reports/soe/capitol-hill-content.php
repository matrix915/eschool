<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'Capitol Hill Master Report - ' . $year;
$reportArr = [[
    'Student First Name',
    'Student Last Name',
    'Student Grade Level',
 'School of Enrollment'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setParentIDs([553]);
foreach ($filter->getStudents() as $student) {

   
    if (!($packet = mth_packet::getStudentPacket($student))) {
        core_notify::addError('Packet Missing for ' . $student);
        continue;
    }
   
    $grade_level = $student->getGradeLevelValue($year->getID());
    $reportArr[] = [
        $student->getFirstName(),
        $student->getLastName(),
        $grade_level,
        $student->getSchoolOfEnrollment()
    ];
}

include ROOT . core_path::getPath('../report.php');