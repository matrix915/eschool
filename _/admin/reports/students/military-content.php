<?php

use mth\yoda\courses;

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Active Military - ' . $year;
$reportArr = [
    [
        'Parent First Name',
        'Parent Last Name',
        'Parent Email',
        'Parent Phone',
        'City',
        'State',
        'Student First Name',
        'Student Last Name',
        'Student Gender',
        'Student Current Grade',
        'Special Ed'
    ]
];

$filter = new mth_person_filter();
//$filter->setSchoolOfEnrollment(array(mth_student::SCHOOL_ESCHOOL));
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear(array($year->getID()));
$missingPackets = 0;

foreach ($filter->getStudents() as $student) {
    /* @var $student mth_student */
    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        break;
    }
    if (!($packet = mth_packet::getStudentPacket($student))) {
        $missingPackets++;
    }
    if (!($address = $parent->getAddress())) {
        core_notify::addError('Address Missing for ' . $parent);
        continue;
    }

    if (!($packet = mth_packet::getStudentPacket($student))) {
        //$missingPackets++;
        continue;
    }

    if(!$packet->getMilitary()){
        continue;
    }

    $homeroomgrade = null;
    if ($enrollment = courses::getStudentHomeroom($student->getID(),$year)){
        $homeroomgrade = $enrollment->getStudentHomeroomGrade();
    }
    

    $reportArr[] = [
        $parent->getFirstName(),
        $parent->getLastName(),
        $parent->getEmail(),
        $parent->getPhone(),
        $address ? $address->getCity() : '',
        $address ? $address->getState(): '',
        $student->getFirstName(),
        $student->getLastName(),
        $student->getGender(),
        is_null($homeroomgrade)?'NA':$homeroomgrade.'%',
        $student->specialEd() ? 'Yes' : 'No'
    ];
}

// if ($missingPackets) {
//     core_notify::addError('Missing packets for ' . $missingPackets . ' Students');
// }

include ROOT . core_path::getPath('../report.php');