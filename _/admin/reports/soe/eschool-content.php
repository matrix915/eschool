<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'eSchool - MTH Master Report - ' . $year;

$reportArr = [[
 'Legal Student Last',
 'Legal Student First',
 'DOB',
 $year.' Grade',
 $year.' Status',
 $year.' Status - Returning, New, or Transferred?'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::eSchool);
foreach ($filter->getStudents() as $student) { 
    $grade_level = $student->getGradeLevelValue($year->getID());
    if (!($packet = mth_packet::getStudentPacket($student))) {
        core_notify::addError('Packet Missing for ' . $student);
        continue;
    }

    $reportArr[] = [
        $student->getLastName(),
        $student->getFirstName(),
        $student->getDateOfBirth('m/d/Y'),
        $grade_level,
        ($student->isNewFromSOE(\mth\student\SchoolOfEnrollment::eSchool)?'New':'Return'),
        $student->getSOEStatus($year,$packet)
    ];
}

include ROOT . core_path::getPath('../report.php');