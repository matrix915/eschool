<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */
$yearId = $year->getID();

$file = 'Tooele Skyward Report - ' . $year;
$reportArr = [[
    'Other ID',
    'Stu Last Name',
    'Stu First Name',
    'Birthdate',
    'Entity',
    'Entry Date',
    'Entry code',
    'Entry School',
    'Student Grade',
    'Gender',
    'Language Code',
    'Am Ind/AK Nat',
    'Asian',
    'Black/Afr Amer',
    'Nat HA/Oth PI',
    'White',
    'Local Race Code',
    'Student Status',
    'NY Status',
    'Resident Status',
    'F1/G1 Last Name',
    'F1/G1 Frst Name',
    'F1/G1 Pri Phone',
    'F1/G1 Relation',
    'F1/G2 Last Name',
    'F1/G2 Frst Name',
    'F1/G2 Relation',
    'F1 Street Dir',
    'F1 Street Name',
    'F1 Street Num',
    'F1 City',
    'F1 State',
    'F1 Zip Code',
    'F2/G1 Last Name',
    'F2/G1 Frst Name',
    'F2/G1 Pri Phone',
    'F2/G1 Relation',
    'F2/G2 Last Name',
    'F2/G2 Frst Name',
    'F2/G2 Relation',
    'F2 Street Dir',
    'F2 Street Name',
    'F2 Street Num',
    'F2 State',
    'F2 City',
    'F2 Zip Code',
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$yearId]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::Tooele);
$studentIds = $filter->getStudentIDs();
$selected_schoolYear = mth_schoolYear::getCurrent();
$packets = [];
foreach (mth_packet::getStudentsPackets($studentIds) as $packet) {
    /** @var mth_packet $packet */
    if (!isset($packets[$packet->getStudentID()])) {
        $packets[$packet->getStudentID()] = $packet;
    }
}

foreach (mth_student::getStudents(array('StudentID' => $studentIds)) as $student) {
    $studentId = $student->getID();
    if (!(array_key_exists($studentId, $packets) && ($packet = $packets[$studentId]))) {
        core_notify::addError('Packet Missing for ' . $student);
        continue;
    }
    if((string) $student->getSchoolOfEnrollment(false, $selected_schoolYear->getPreviousYear()) == 'Tooele') {
        continue;
    }
    $parent = $student->getParent();
    $address = $parent->getAddress();
    $schoolDistrict =  $address->getSchoolDistrictOfR();
    $grade_level = $student->getGradeLevelValue($yearId);
    $language = 'EN';
    if ($packet->getLanguageFriends() == 'English') {
        $language = 'EN';
    } else if ($packet->getLanguageFriends() === 'Spanish') {
        $language = 'ES';
    } else if ($packet->getLanguageFriends() === 'German') {
        $language = 'DE';
    } else if ($packet->getLanguageFriends() === 'Dutch') {
        $language = 'DU';
    } else if ($packet->getLanguageFriends() === 'Afrikaans') {
        $language = 'AF';
    } else if ($packet->getLanguageFriends() === 'Amharic') {
        $language = 'AM';
    } else if ($packet->getLanguageFriends() === 'Arabic') {
        $language = 'AR';
    } else if ($packet->getLanguageFriends() === 'Creole French-b') {
        $language = 'CF';
    } else if ($packet->getLanguageFriends() === 'Croatian') {
        $language = 'CR';
    } else if ($packet->getLanguageFriends() === 'Persian (Farsi)') {
        $language = 'FA';
    } else if ($packet->getLanguageFriends() === 'French') {
        $language = 'FR';
    } else if ($packet->getLanguageFriends() === 'Gujarati') {
        $language = 'GU';
    } else if ($packet->getLanguageFriends() === 'Hindi') {
        $language = 'HI';
    } else if ($packet->getLanguageFriends() === 'Hmong') {
        $language = 'HM';
    } else if ($packet->getLanguageFriends() === 'Armenian') {
        $language = 'HY';
    } else if ($packet->getLanguageFriends() === 'Indonesia') {
        $language = 'IN';
    } else if ($packet->getLanguageFriends() === 'Italian') {
        $language = 'IT';
    } else if ($packet->getLanguageFriends() === 'Hebrew') {
        $language = 'IW';
    } else if ($packet->getLanguageFriends() === 'Japanese') {
        $language = 'JA';
    } else if ($packet->getLanguageFriends() === 'Cambodian (Khme)') {
        $language = 'KN';
    } else if ($packet->getLanguageFriends() === 'Korean') {
        $language = 'KO';
    } else if ($packet->getLanguageFriends() === 'Latin') {
        $language = 'LA';
    } else if ($packet->getLanguageFriends() === 'Lao') {
        $language = 'LO';
    } else if ($packet->getLanguageFriends() === 'N. Amer Indian') {
        $language = 'NA';
    } else if ($packet->getLanguageFriends() === 'Navajo') {
        $language = 'NV';
    } else if ($packet->getLanguageFriends() === 'Polish') {
        $language = 'PL';
    } else if ($packet->getLanguageFriends() === 'Portuguese') {
        $language = 'PT';
    } else if ($packet->getLanguageFriends() === 'Russian') {
        $language = 'RU';
    } else if ($packet->getLanguageFriends() === 'Swahle') {
        $language = 'SA';
    } else if ($packet->getLanguageFriends() === 'Sign Languages') {
        $language = 'SL';
    } else if ($packet->getLanguageFriends() === 'Samoan') {
        $language = 'SM';
    } else if ($packet->getLanguageFriends() === 'Swedish') {
        $language = 'SW';
    } else if ($packet->getLanguageFriends() === 'Tagalog') {
        $language = 'TA';
    } else if ($packet->getLanguageFriends() === 'Telugu') {
        $language = 'TE';
    } else if ($packet->getLanguageFriends() === 'Thai') {
        $language = 'TH';
    } else if ($packet->getLanguageFriends() === 'Tongan') {
        $language = 'TO';
    } else if ($packet->getLanguageFriends() === 'Turkish') {
        $language = 'TR';
    } else if ($packet->getLanguageFriends() === 'Ukrainian') {
        $language = 'UK';
    } else if ($packet->getLanguageFriends() === 'Urdu') {
        $language = 'UR';
    } else if ($packet->getLanguageFriends() === 'Vietnamese') {
        $language = 'VI';
    } else if ($packet->getLanguageFriends() === 'Wolof (Gambian)') {
        $language = 'WO';
    } else if ($packet->getLanguageFriends() === 'Yoruba') {
        $language = 'YO';
    } else if ($packet->getLanguageFriends() === 'Cantonese') {
        $language = 'YU';
    } else if ($packet->getLanguageFriends() === 'Chinese-No Cant') {
        $language = 'ZH';
    } else if ($packet->getLanguageFriends() === 'Zulu') {
        $language = 'ZU';
    } else {
        $language = 'EN';
    }
    $relation_ship = '_';
    if ($packet->getRelationShipParentInfo() == 'Father') {
        $relation_ship = '33';
    } else if ($packet->getRelationShipParentInfo() == 'Mother') {
        $relation_ship = '03';
    } else {
        $relation_ship = '_';
    }
    $relation_ship1 = '_';
    if ($packet->getRelationShipSecondaryContact() == 'Father') {
        $relation_ship1 = '33';
    } else if ($packet->getRelationShipSecondaryContact() == 'Mother') {
        $relation_ship1 = '03';
    } else {
        $relation_ship1 = '_';
    }
    $level = sprintf('%02d', $grade_level);
    $reportArr[] = [
        $student->getStudentID(),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getDateOfBirth('m/d/Y'),
        '735',
        date('m/d/Y'),
        'E1',
        '110',
        $grade_level == 'K' ? 'KG' : (string)$level,
        $student->getGender(),
        $language,
        $packet->checkRace('1'),
        $packet->checkRace('2'),
        $packet->checkRace('3'),
        $packet->checkRace('4'),
        $packet->checkRace('5'),
        'XX',
        'A',
        'A',
        $schoolDistrict == 'Tooele' ? 'Yes' : 'No', // resident status
        $parent->getLastName(),
        $parent->getFirstName(),
        $parent->getPhone(),
        $relation_ship,
        '',
        '',
        '', // secondary relation
        '',
        $address->getStreetNanme($address->getStreetNum()),
        $address->getStreetNum(),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $packet->getSecondaryContactLast(),
        $packet->getSecondaryContactFirst(),
        $packet->getSecondaryPhone(),
        $relation_ship1,
        '',
        '',
        '',
        '',
        $address->getStreetNanme($address->getStreetNum()),
        $address->getStreetNum(),
        $address->getState(),
        $address->getCity(),
        $address->getZip(),
    ];
}

include ROOT . core_path::getPath('../report.php');
