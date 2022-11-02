<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/18/17
 * Time: 11:42 AM
 */


($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$SOEs = [];
foreach(req_get::int_array('soe') as $schoolOfEnrollmentId){

    $SOEs[$schoolOfEnrollmentId] = \mth\student\SchoolOfEnrollment::get($schoolOfEnrollmentId);
}
array_filter($SOEs);

$file = 'Students of '.implode(', ',$SOEs);

$reportArr = [[
    'Date Assigned to SoE',
    'Student Last Name',
    'Student First Name',
    'Student Date of Birth',
    'Student Grade '.$year->getName(),
    'Student District of Residence',
    'Student anticipated Graduation Year'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE,mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(array_keys($SOEs));

foreach ($filter->getStudents() as $student){
    $packet = (mth_packet::getStudentPacket($student) ? mth_packet::getStudentPacket($student) : NULL);
    $reportArr[] = [
        ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getDateOfBirth('n/j/Y'),
        $student->getGradeLevelValue($year),
        ($packet ? $packet->getSchoolDistrict() : ''),
        ($year->getDateEnd('Y') + (12 - intval($student->getGradeLevelValue($year))))
    ];
}

if( in_array(\mth\student\SchoolOfEnrollment::Unassigned, req_get::int_array('soe')) ) {
    foreach ($filter->getUnassigned() as $student) {
        $packet = (mth_packet::getStudentPacket($student) ? mth_packet::getStudentPacket($student) : NULL);
        $reportArr[] = [
            ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
            $student->getLastName(),
            $student->getFirstName(),
            $student->getDateOfBirth('n/j/Y'),
            $student->getGradeLevelValue($year),
            ($packet ? $packet->getSchoolDistrict() : ''),
            ($year->getDateEnd('Y') + (12 - intval($student->getGradeLevelValue($year))))
        ];
    }
}

include ROOT . core_path::getPath('../report.php');