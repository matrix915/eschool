<?php
/** @var mth_schoolYear $year */
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Student Information - ' . $year;
$reportArr = [[
    'Parent Person #',
    'Student Person #',
    'Last',
    'First',
    'Middle',
    'Preferred First',
    'Preferred Last',
    'DOB',
    'Gender',
    'Email',
    'Grade Level',
    'Grade Level School Year',
    'SPED',
    'Parent Last',
    'Parent First',
    'Phone',
    'Street',
    'Street2',
    'City',
    'State',
    'Zip',
    'Secondary Contact First',
    'Secondary Contact Last',
    'Secondary Contact Phone',
    'Secondary Contact Email',
    'Birth Place',
    'Birth Country',
    'Hispanic',
    'Race',
    'First language',
    'Adult language at home',
    'Child language at home',
    'Friend language',
    'Preferred correspondence language',
    'Recently Moved for Agriculture',
    'Presently Living Location',
    'Lives With'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE]);
$filter->setStatusYear($year->getID());
if(req_get::is_set('grade')){
    $filter->setGradeLevel(req_get::int_array('grade'));
}
if(req_get::is_set('district')){
    $pq = new \mth\packet\query();
    $student_ids = $pq->setSchoolDistricts(req_get::txt_array('district'))->getStudentIds();
    if(empty($student_ids)){
        $student_ids = [0];
    }
    $filter->setStudentIDs($student_ids);
}
$missingPackets = 0;
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
    $reportArr[] = [
        $parent->getPersonID(),
        $student->getPersonID(),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getMiddleName(),
        $student->getPreferredFirstName(),
        $student->getPreferredLastName(),
        $student->getDateOfBirth('m/d/Y'),
        $student->getGender(),
        $student->getEmail(),
        $student->getGradeLevelValue($year->getID()),
        $year->getName(),
        $student->specialEd(true),
        $parent->getLastName(),
        $parent->getFirstName(),
        $parent->getPhone(),
        $address->getStreet(),
        $address->getStreet2(),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $packet->getSecondaryContactFirst(),
        $packet->getSecondaryContactLast(),
        $packet->getSecondaryPhone(),
        $packet->getSecondaryEmail(),
        $packet->getBirthPlace(),
        $packet->getBirthCountry(true),
        ($packet->isHispanic()?'Yes':'No'),
        $packet->getRace(),
        $packet->getLanguage(),
        $packet->getLanguageAtHome(),
        $packet->getLanguageHomeChild(),
        $packet->getLanguageFriends(),
        $packet->getLanguageHomePreferred(),
        ($packet->getWorkMove()?'Yes':'No'),
        $packet->getLivingLocation(true),
        $packet->getLivesWith(true)
    ];
}

/** @noinspection PhpIncludeInspection */
include ROOT . core_path::getPath('../report.php');