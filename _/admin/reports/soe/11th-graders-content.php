<?php
/**
 * User: Rex
 * Date: 9/01/2017
 * Time: 11:18 PM PST
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
    'Student First Name',
    'Student Last Name',
    'Student Email Address',
    'Parent First Name',
    'Parent Last Name',
    'Parent Email Address',
    'Phone',
    'Opt-Out?',
    'Student District of Residence',
    'Address w/ City'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(array_keys($SOEs));
$filter->setGradeLevel(11);


foreach ($filter->getStudents() as $student){

    if (!($packet = mth_packet::getStudentPacket($student))) {
        core_notify::addError('Packet Missing for ' . $student);
    }

     if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
    }

    if (!($address = $parent->getAddress())) {
        core_notify::addError('Address Missing for ' . $parent);
    }
    $hasoptout = 'No';
    if($optout = mth_testOptOut::getByStudent($student,$year)){
        $hasoptout = 'Yes';
    }

    $address = ($address->getStreet().($address->getStreet2()?PHP_EOL.$address->getStreet2():'') ).', '.$address->getCity();
        
    $reportArr[] = [
        $student->getFirstName(),
        $student->getLastName(),
        $student->getEmail(),
        $parent->getFirstName(),
        $parent->getLastName(),
        $parent->getEmail(),
        $parent->getPhone('Home'),
        $hasoptout,
        ($packet?$packet->getSchoolDistrict():''),
        $address,
    ];
}

include ROOT . core_path::getPath('../report.php');