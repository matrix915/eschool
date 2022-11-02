<?php

use mth\packet\LivingLocationEnum;

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$livingLabels = LivingLocationEnum::getLabels();

$file = 'ALC - Nebo Enrollment Report - ' . $year;
$reportArr = [[
    'Date Assigned to SoE',
    'Acceptance Date',
    'Phone 000-000-0000',
    'Street Address',
    'Street Line 2',
    'City',
    'State',
    'Zipcode',
    'County',
    'Parent Legal First Name',
    'Parent Middle Name',
    'Parent Legal Last Name',
    'Parent Date of Birth MM/DD/YYYY',
    'Parent Gender',
    'Parent Phone',
    'Parent Email',
    'Relationship to Student',
    'Parent Main Language Spanish? Y/N',
    'Military',
    'Agricultural',
    'Emergency Contact First Name',
    'Emergency Contact Last Name',
    'Emergency Contact Gender',
    'Emergency Contact Phone',
    'Relationship to Student',
    'Emergency Contact Same Address as Student? Y/N',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Student Legal Middle Name',
    'Student Date of Birth MM/DD/YYYY',
    'Student Gender',
    $year . ' grade',
    'Year of Graduation',
    'Student Email Address',
    'Student Birthplace (City)',
    'Student Birthplace (Country)',
    'Hispanic/Latino Y/N',
    'Race/Ethnicity (American Indian or Alaska Native, Asian, Black or African American, Native Hawaiian or Other Pacific Islander, White)',
    'Student District of Residence',
    $year . ' Status',
    $year . ' Status - Returning, New, or Transferred?',
    'Last School Attended (if any)',
    'Address of Last School Attendend (if any)',
    'Agree to Mutual Exchange?',
    'Student Currently Expelled? Y/N If Yes, please add explanation',
    'Housing Situation (Shared housing due to loss of housing or loss of employment | Motel, hotel, trailer park, or camp ground |  In emergency or transitional shelter | Awaiting Foster Care | Primary nighttime | Living in car, park, public space, etc  | None of the Above)',
    'IEP',
    '504',
    'Medical or Mental Health Conditions',
    'Medications'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::Nebo);

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

    $_sped = $student->specialEd(true);
    $sped =   $_sped &&  $_sped != mth_student::SPED_LABEL_EXIT ? $_sped : 'No';
    $grade_level = $student->getGradeLevelValue($year->getID());

    $reportArr[] = [
        ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
        $packet->getDateAccepted('m/d/Y'),
        $parent->getPhone('Home'),
        $address->getStreet(),
        $address->getStreet2(),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $packet->getBirthCountry(true),
        $parent->getFirstName(),
        $parent->getMiddleName(),
        $parent->getLastName(),
        $parent->getDateOfBirth('m/d/Y'),
        $parent->getGender(),
        $parent->getPhone('Cell'),
        $parent->getEmail(),
        'Parent',
        ($packet->getLanguageAtHome() == 'Spanish' ? 'Yes' : 'No'),
        ($packet->getMilitary() ? 'Yes' : 'No'),
        ($packet->getWorkedInAgriculture() ? 'Yes' : 'No'),
        $packet->getSecondaryContactFirst(),
        $packet->getSecondaryContactLast(),
        '',
        $packet->getSecondaryPhone(),
        '',
        'Yes',
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getDateOfBirth('m/d/Y'),
        $student->getGender(),
        $student->getGradeLevelValue($year->getID()),
        $year->getDateEnd('Y') + (12 - (intval($grade_level))),
        $student->getEmail(),
        $packet->getBirthPlace(),
        $packet->getBirthCountry(true),
        ($packet->isHispanic() ? 'Yes' : 'No'),
        $packet->getRace(),
        $schoolDistrict?  $schoolDistrict:'',
        $student->getStatusLabel($year),
        $student->getSOEStatus($year, $packet),
        $packet->getLastSchoolName(),
        $packet->getLastSchoolAddress(false),
        '',
        ($withdraw = mth_withdrawal::getByStudent($student->getID())) ? 'Yes, ' . $withdraw->reason_txt() : 'No',
        ($packet->getLivingLocation(false) != 5 ? $livingLabels[$packet->getLivingLocation(false)] : 'None of the Above'),
        ($_sped && $_sped == mth_student::SPED_LABEL_IEP ? 'Yes' : 'No'),
        ($_sped && $_sped == mth_student::SPED_LABEL_504 ? 'Yes' : 'No'),
        '',
        ''
    ];
}

include ROOT . core_path::getPath('../report.php');
