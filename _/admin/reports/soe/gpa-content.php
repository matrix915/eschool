<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'GPA - MTH Master Report - ' . $year;
$reportArr = [[
    'Date Assigned to SoE',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Grade',
    'Gender',
    $year . ' Status',
    'Parent Legal First Name',
    'Parent Legal Last Name',
    'Parent Email',
    'Parent Phone',
    'City',
    'District',
    $year . ' Status - New, Returning, or Transferred'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::GPA);
foreach ($filter->getStudents() as $student) {

    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        continue;
    }
    if (!($packet = mth_packet::getStudentPacket($student))) {
        core_notify::addError('Packet Missing for ' . $student);
        continue;
    }
    if (!($address = $parent->getAddress())) {
        core_notify::addError('Address Missing for ' . $parent);
        continue;
    }
   
    $schoolDistrict =  $address->getSchoolDistrictOfR();

    $grade_level = $student->getGradeLevelValue($year->getID());
    $reportArr[] = [
        ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
        $student->getLastName(),
        $student->getFirstName(),
        $grade_level,
        $student->getGender(),
        ($student->isNewFromSOE(\mth\student\SchoolOfEnrollment::GPA)?'New':'Return'),
        $parent->getFirstName(),
        $parent->getLastName(),
        $parent->getEmail(),
        $parent->getPhone(),
        $address->getCity(),
        ( $schoolDistrict? $schoolDistrict:''),
        $student->getSOEStatus($year,$packet)
    ];
}

include ROOT . core_path::getPath('../report.php');