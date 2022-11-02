<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'DIBELS reading test report - ' . $year;
$reportArr = array(array(
    'Student Last',
    'Student First',
    'Grade',
    'School of Enrollment',
    'Gender',
    'Parent First',
    'Parent Email',
    'Parent Phone',
    'Opted out'
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
$filter->setGradeLevel(array(1, 2, 3));

mth_testOptOut::cacheAll($year);

foreach ($filter->getStudents() as $student) {
    /* @var $student mth_student */
    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        break;
    }
    $reportArr[] = array(
        $student->getLastName(),
        $student->getFirstName(),
        $student->getGradeLevelValue($year->getID()),
        $student->getSchoolOfEnrollment(false, $year),
        $student->getGender(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone(),
        (mth_testOptOut::getByStudent($student, $year) ? 'Yes' : 'No')
    );
}

include ROOT . core_path::getPath('../report.php');