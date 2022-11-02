<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'ICSD/SEA Students Report - ' . $year;
$reportArr = [[
    'Date Assigned to SoE',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Student Middle Name',
    'Student Preferred First Name',
    'Student Date of Birth MM/DD/YYYY',
    'Student Gender',
    'Parent Email',
    'Grade Level',
    'Grade Level School Year',
    'Previous School of Enrollment',
    'SPED',
    '504?',
    'IEP?',
    'Speech?',
    'Parent Legal Last Name',
    'Parent Legal First Name',
    'Relation',
    'Phone',
    'Street',
    'Street2',
    'City',
    'State',
    'Zip',
    'Mailing Address',
    'Physical Address',
    'Secondary Contact First Name',
    'Secondary Contact Last Name',
    'Secondary Contact Phone',
    'Secondary Contact Email',
    'Relation',
    'Birth Place',
    'Birth Country',
    'Ethnicity',
    'Race',
    'First language',
    'ESL?',
    'Adult language at home',
    'Child language at home',
    'Friend language',
    'Preferred correspondence language',
    'Recently Moved for Agriculture',
    'Presently Living Location',
    'Lives With',
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::ICSD);
$filter->setIsNewToSoe(\mth\student\SchoolOfEnrollment::ICSD);

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
    $sped_504 ="No";
    $sped_IEP ="No";
    $sped_Speech = "No";
    if($sped == "504"){
        $sped_504 = "Yes";
        $sped_IEP = "No";
        $sped_Speech = "Yes";
    }else if($sped == "IEP"){
        $sped_504 = "No";
        $sped_IEP = "Yes";
        $sped_Speech = "Yes";
    }else if($sped == "No" || $sped == "Exit"){
        $sped_504 = "No";
        $sped_IEP = "No";
        $sped_Speech = "No";
    }

    $Mailing_Physical_Address = $address->getStreet() . " " . $address->getStreet2() . " " . $address->getCity() . " " . $address->getState() . " " . $address->getZip();

    $grade_level = $student->getGradeLevelValue($year->getID());
    $reportArr[] = [
        ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getPreferredFirstName(),
        $student->getDateOfBirth('m/d/Y'),
        $student->getGender(),
        $parent->getEmail(),
        $grade_level,
        $year,
        $packet->getLastSchoolName(),
        $sped,
        $sped_504,
        $sped_IEP,
        $sped_Speech,
        $parent->getLastName(),
        $parent->getFirstName(),
        $packet->getRelationShipParentInfo(),
        $parent->getPhone(),
        $address->getStreet(),
        $address->getStreet2() ? PHP_EOL . $address->getStreet2() : '',
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $Mailing_Physical_Address,
        $Mailing_Physical_Address,
        $packet->getSecondaryContactFirst(),
        $packet->getSecondaryContactLast(),
        $packet->getSecondaryPhone(),
        $packet->getSecondaryEmail(),
        $packet->getRelationShipSecondaryContact(),
        $packet->getBirthPlace(),
        $packet->getBirthCountry(),
        $packet->isHispanic() ? 'Hispanic Latino/a/x' : 'Non-Hispanic',
        $packet->getRace(),
        $packet->getLanguage(),
        $packet->getLanguage() == "English" ? "No" : "Yes",
        $packet->getLanguageAtHome(),
        $packet->getLanguageHomeChild(),
        $packet->getLanguageFriends(),
        $packet->getLanguageHomePreferred(),
        $packet->getWorkedInAgriculture() ? 'Yes' : 'No',
        $packet->getLivingLocation(),
        $packet->getLivesWith(),

    ];
}

include ROOT . core_path::getPath('../report.php');
