<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'ALC - Nebo Students - ' . $year;
$reportArr = [[
    'Date Assigned to SoE',
    'Acceptance Date',
    $year.' Grade',
    'Parent Legal First Name',
    'Parent Legal Last Name',
    'Relationship',
    'Parent Email',
    'Phone 000-000-0000',
    'Street Address',
    'City',
    'State',
    'Zipcode',
    'Student Legal First Name',
    'Student Middle Name',
    'Student Legal Last Name',
    'Student Date of Birth MM/DD/YYYY',
    'Student Gender',
    'Student Birthplace (City)',
    'Student Birthplace (Country)',
    'Restricted Info',
    'First Language',
    'Child language in home',
    'Adults language in home',
    'Correspondence language',
    'Street Address',
    'City',
    'State',
    'Zipcode',
    'Phone 000-000-0000',
    'Type',
    'Homeless',
    'Special Ed? (Yes or No)',
    'Health',
    'Military',
    'Legal',
    'Contacts',
    'Emergency Contacts',
    'Hispanic or Latino Heritage?',
    'Race',
    'Last School Attended (if any)',
    'Address of Last School Attendend (if any)',
    'Notes',
    'Parent Name',
    'Student Name',
    'Received and Reviewed',
    'Parent Name'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::Nebo);
$filter->setIsNewToSoe(\mth\student\SchoolOfEnrollment::Nebo);

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

    $_sped = $student->specialEd(true);
    $sped =   $_sped &&  $_sped!=mth_student::SPED_LABEL_EXIT?$_sped :'No';
    
    $reportArr[] = [
        $packet->dateAssignedToSoe(false, 'm/d/Y'),
        $packet->getDateAccepted('m/d/Y'),
        $student->getGradeLevelValue($year->getID()),
        $parent->getFirstName(),
        $parent->getLastName(),
        'Parents',
        $parent->getEmail(),
        $parent->getPhone('Cell'),
        $address->getStreet().($address->getStreet2()?PHP_EOL.$address->getStreet2():''),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getLastName(),
        $student->getDateOfBirth('m/d/Y'),
        $student->getGender(),
        $packet->getBirthPlace(),
        $packet->getBirthCountry(true),
        'Restrict data directory & photos',
        $packet->getLanguage(),
        $packet->getLanguageHomeChild(),
        $packet->getLanguageAtHome(),
        $packet->getLanguageHomePreferred(),
        $address->getStreet().($address->getStreet2()?PHP_EOL.$address->getStreet2():''),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $parent->getPhone(),
        'Residence',
        'Skip',
        $sped,
        'No',
        ($packet->getMilitary()?'Yes':'No'),
        'Skip',
        'Skip',
        'Skip',
        ($packet->isHispanic()?'Yes':'No'),
        $packet->getRace(),
        $packet->getLastSchoolName(),
        $packet->getLastSchoolAddress(),
        'Skip',
        $parent->getName(),
        $student->getName(false,true),
        'Click button',
        $parent->getName()
    ];
}

include ROOT . core_path::getPath('../report.php');