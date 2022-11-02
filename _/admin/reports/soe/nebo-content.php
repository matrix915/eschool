<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'ALC - Nebo Master Report - ' . $year;
$reportArr = [[
    'Date Assigned to SoE',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Legal Student Middle',
    'DOB',
    'Grade',
    'Year of Graduation',
    'Student District of Residence',
    $year . ' Status',
    $year . ' Status - Returning, New, or Transferred?',
    'Previous School of Enrollment'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::Nebo);
foreach ($filter->getStudents() as $student) {
    if (!($packet = mth_packet::getStudentPacket($student))) {
        core_notify::addError('Packet Missing for ' . $student);
        continue;
    }

    $parent = $student->getParent();
    $address = $parent->getAddress();
    $schoolDistrict =  $address->getSchoolDistrictOfR();
   
    $grade_level = $student->getGradeLevelValue($year->getID());
    $reportArr[] = [
        ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getDateOfBirth('m/d/Y'),
        $grade_level,
        $year->getDateEnd('Y')+(12- (intval($grade_level)) ),
        ( $schoolDistrict ? $schoolDistrict:''),
        ($student->isNewFromSOE(\mth\student\SchoolOfEnrollment::Nebo)?'New':'Return'),
        $student->getSOEStatus($year,$packet),
        $packet->getLastSchoolName()
    ];
}

include ROOT . core_path::getPath('../report.php');