<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'Provo Students - ' . $year;
$reportArr = [[
    'Acceptance Date',
    'Parent email',
    'Password',
    'Confirm Password',
    'Parent First Name',
    'Parent Last Name',
    'Parent Email',
    'Student First Name',
    'Student Last Name',
    'Student Date of Birth MM/DD/YYYY',
    'School',
    $year.' grade',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Student Legal Middle Name',
    'Student Preferred Name',
    $year.' grade',
    'Phone 000-000-0000',
    'Street Address',
    'City',
    'Zipcode',
    'Student Email Address',
    'Student Gender',
    'Student Date of Birth MM/DD/YYYY',
    'Student Birthplace (City)',
    'Student Birthplace (Country)',
    'Hispanic or Latino Heritage?',
    'Race',
    'LANGUAGE SURVEY',
    'Language',
    'First Language',
    'Spoken most',
    'Born Outside of U.S.',
    'Agricultural',
    'Military',
    'Attended Provo?',
    'Last School Attended (if any)',
    'Address of Last School Attendend (if any)',
    'Special Ed? (Yes or No)',
    'Relationship to Student',
    'Parent Last Name',
    'Parent First Name',
    'Language Spoken',
    'Street Address',
    'City',
    'State',
    'Zipcode',
    'Phone 000-000-0000',
    'Parent Email',
    'Parent Name',
    'Rel to Student?',
    'Language',
    'City',
    'Phone 000-000-0000',
    'Secondary Contact Name',
    'Rel to Student?',
    'Language',
    'City',
    'Phone 000-000-0000',
    'Agree to Mutual Exchange?',
    'Parent Name',
    'Policy',
    'Policy',
    'Policy',
    'Parent Name'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::eSchool);
$filter->setIsNewToSoe(\mth\student\SchoolOfEnrollment::eSchool);

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

    $attended_provo = stripos($packet->getLastSchoolAddress(false),'provo')
        || stripos($packet->getLastSchoolName(),'provo');

    $_sped = $student->specialEd(true);
    $sped =   $_sped &&  $_sped!=mth_student::SPED_LABEL_EXIT?$_sped :'No';
    $password = 'mth'.$year->getStartYear();

    $reportArr[] = [
        $packet->getDateAccepted('m/d/Y'),
        $parent->getEmail(),
        $password,
        $password,
        $parent->getFirstName(),
        $parent->getLastName(),
        $parent->getEmail(),
        $student->getFirstName(),
        $student->getLastName(),
        $student->getDateOfBirth('m/d/Y'),
        'eSchool',
        $student->getGradeLevelValue($year->getID()),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getPreferredFirstName(),
        $student->getGradeLevelValue($year->getID()),
        $parent->getPhone('Home'),
        $address->getStreet().($address->getStreet2()?PHP_EOL.$address->getStreet2():''),
        $address->getCity(),
        $address->getZip(),
        $student->getEmail(),
        $student->getGender(),
        $student->getDateOfBirth('m/d/Y'),
        $packet->getBirthPlace(),
        $packet->getBirthCountry(true),
        ($packet->isHispanic()?'Yes':'No'),
        $packet->getRace(),
        ($packet->getLanguageHomeChild() != 'English'?'Yes':'No'),
        $packet->getLanguage(),
        $packet->getLanguageAtHome(),
        $packet->getLanguageHomeChild(),
        ($packet->getBirthCountry(false)!=='US'?'Yes':'No'),
        ($packet->getWorkedInAgriculture()?'Yes':'No'),
        ($packet->getMilitary()?'Yes':'No'),
        ($attended_provo?'Yes':''),
        $packet->getLastSchoolName(),
        $packet->getLastSchoolAddress(false),
        $sped,
        'Mother',
        $parent->getLastName(),
        $parent->getFirstName(),
        $packet->getLanguageHomePreferred(),
        $address->getStreet().($address->getStreet2()?PHP_EOL.$address->getStreet2():''),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $parent->getPhone('Cell'),
        $parent->getEmail(),
        $parent->getName(),
        'Mother',
        $packet->getLanguageHomePreferred(),
        $address->getCity(),
        $parent->getPhone('Cell'),
        $packet->getSecondaryContactFirst().' '.$packet->getSecondaryContactLast(),
        'Father',
        $packet->getLanguageAtHome(),
        '',
        $packet->getSecondaryPhone(),
        'No',
        $parent->getName(),
        'Yes',
        'Yes, I have informed',
        'I acknowledge',
        $parent->getName()
    ];
}

include ROOT . core_path::getPath('../report.php');