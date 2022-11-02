<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'Mid-year ALC - Nebo Master Report - ' . $year;
$reportArr = [[
 'Legal Student Last',
 'Legal Student First',
 'Legal Student Middle',
 'DOB',
 'Grade',
 'Year of Graduation',
 'Student District of Residence',
 $year.' Status',
 $year.' Status - Returning, New, or Transferred?'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE,mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::Nebo);
$filter->setMidYear(true);

foreach ($filter->getStudents() as $student) {

   
    if (!($packet = mth_packet::getStudentPacket($student))) {
        core_notify::addError('Packet Missing for ' . $student);
        continue;
    }
   
    $grade_level = $student->getGradeLevelValue($year->getID());
    $reportArr[] = [
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getDateOfBirth('m/d/Y'),
        $grade_level,
        $year->getDateEnd('Y')+(12- (intval($grade_level)) ),
        ($packet?$packet->getSchoolDistrict():''),
        ($student->isNewFromSOE(\mth\student\SchoolOfEnrollment::Nebo)?'New':'Return'),
        $student->getSOEStatus($year,$packet)
    ];
}

include ROOT . core_path::getPath('../report.php');