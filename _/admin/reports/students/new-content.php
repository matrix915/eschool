<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'New Students - ' . $year;
$reportArr = array(array(
    'Packet Aprv.',
    'Login [parent email]',
    'Password', //mth2015
    'Confirm Password', //mth2015
    'Parent First Name',
    'Parent Last Name',
    'Parent Email',
    'Student First Name',
    'Student Last Name',
    'School',
    '2015-16 Grade Level',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Student Legal Middle Name',
    'Student Preferred Name',
    '2015-16 Grade Level',
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
    'Language', //No
    'First Language', //English
    'Spoken most', //English
    'Agricultural', //No
    'Military', //No
    'Attended Provo?', //No
    'Last School Attended (if any)',
    'Address of Last School Attendend (if any)',
    'Special Ed? (Yes or No)',
    'Relationship to Student', //mother
    'Parent Last Name',
    'Parent First Name',
    'Language Spoken',//English
    'Street Address',
    'City',
    'Zipcode',
    'Phone 000-000-0000',
    'Parent Email',
    'Agree to Mutual Exchange?', //No
    'Parent Name',
    'Parent Name',
    'Rel to Student?',//Other
    'Language',//English
    'City',
    'Phone 000-000-0000',
    'Secondary Contact Name',
    'Rel to Student?', //Other
    'Language', //English
    'City',
    'Phone 000-000-0000',
    'Policy', //Yes
    'Policy', //Yes
    'Photographs' //No
));

function isRace(mth_packet $packet = NULL, $race = NULL)
{
    if (!$packet) {
        return '';
    }
    if (is_null($isRace = $packet->isRace($race))) {
        return '';
    }
    return $isRace ? 'Yes' : 'No';
}

$filter = new mth_person_filter();
//$filter->setSchoolOfEnrollment(array(mth_student::SCHOOL_ESCHOOL));
$filter->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
$filter->setStatusYear(array($year->getID()));
$filter->setNewToSchoolYear($year);
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
    $reportArr[] = array(
        $packet ? $packet->getDateAccepted('m/d/Y') : '',
        $parent->getEmail(),
        'mth' . $year->getStartYear(),
        'mth' . $year->getStartYear(),
        $parent->getFirstName(),
        $parent->getLastName(),
        $parent->getEmail(),
        $student->getFirstName(),
        $student->getLastName(),
        $student->getSOEname($year, false),
        $student->getGradeLevelValue($year->getID()),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getPreferredFirstName(),
        $student->getGradeLevelValue($year->getID()),
        $parent->getPhone(),
        $address ? $address->getStreet() . ($address->getStreet2() ? ' ' . $address->getStreet2() : '') : '',
        $address ? $address->getCity() : '',
        $address ? $address->getZip() : '',
        $student->getEmail(),
        $student->getGender(),
        $student->getDateOfBirth('m/d/Y'),
        $packet ? $packet->getBirthPlace() : '',
        $packet ? $packet->getBirthCountry(true) : '',
        $packet ? ($packet->isHispanic() ? 'Yes' : 'No') : '',
        $packet ? $packet->getRace() : '',
        $packet ? ($packet->getLanguage() != 'English' ? 'Yes' : 'No') : '',
        $packet ? $packet->getLanguage() : '',
        $packet ? $packet->getLanguageAtHome() : '',
        $packet ? ($packet->getWorkedInAgriculture() ? 'Yes' : 'No') : '',
        $packet ? ($packet->getMilitary() ? 'Yes' : 'No') : '',
        'No',
        $packet ? $packet->getLastSchoolName() : '',
        $packet ? $packet->getLastSchoolAddress(false) : '',
        $student->specialEd() ? 'Yes' : 'No',
        'mother',
        $parent->getLastName(),
        $parent->getFirstName(),
        $packet ? $packet->getLanguageAtHome() : '',
        $address ? $address->getStreet() . ($address->getStreet2() ? ' ' . $address->getStreet2() : '') : '',
        $address ? $address->getCity() : '',
        $address ? $address->getZip() : '',
        $parent->getPhone(),
        $parent->getEmail(),
        'No',
        $parent->getName(),
        $parent->getName(),
        'Other',
        $packet ? $packet->getLanguageAtHome() : '',
        $address ? $address->getCity() : '',
        $parent->getPhone(),
        $packet ? $packet->getSecondaryContactFirst() . ' ' . $packet->getSecondaryContactLast() : '',
        'Other',
        $packet ? $packet->getLanguageAtHome() : '',
        $address ? $address->getCity() : '',
        $packet ? $packet->getSecondaryPhone() : '',
        'Yes',
        'Yes',
        'No'
    );
}
if ($missingPackets) {
    core_notify::addError('Missing packets for ' . $missingPackets . ' Students');
}

include ROOT . core_path::getPath('../report.php');