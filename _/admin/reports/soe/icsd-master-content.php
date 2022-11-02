<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'ICSD/SEA Master Report - ' . $year;
$reportArr = [[
    'Date Assigned to SoE',
    $year . ' Status',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Student Legal Middle Name',
    'Student Preferred Last Name',
    'Student Preferred First Name',
    'Student Date of Birth MM/DD/YYYY',
    'Student Gender',
    $year.' Grade',
    'Student Email',
    'Parent Legal First Name',
    'Parent Legal Last Name',
    'Parent Email',
    'Phone 000-000-0000',
    'Street Address',
    'Street Line 2',
    'City',
    'State',
    'Zipcode',
    'IEP',
    '504',
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::ICSD);

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

    $sped = $student->specialEd(true);

    $grade_level = $student->getGradeLevelValue($year->getID());
    $reportArr[] = [
        ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
        ($student->isNewFromSOE(\mth\student\SchoolOfEnrollment::ICSD) ? 'New' : 'Return'),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getPreferredLastName(),
        $student->getPreferredFirstName(),
        $student->getDateOfBirth('m/d/Y'),
        $student->getGender(),
        $grade_level,
        $student->getEmail(),
        $parent->getFirstName(),
        $parent->getLastName(),
        $parent->getEmail(),
        $parent->getPhone(),
        $address->getStreet(),
        $address->getStreet2(),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        ($sped && $sped == mth_student::SPED_LABEL_IEP ? 'Yes' : 'No'),
        ($sped && $sped == mth_student::SPED_LABEL_504 ? 'Yes' : 'No'),

    ];
}

include ROOT . core_path::getPath('../report.php');
