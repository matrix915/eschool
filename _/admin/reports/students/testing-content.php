<?php

($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$file = 'Testing - ' . $year;
$reportArr = array(array(
    'Student First',
    'Student Last',
    'Grade',
    'Gender',
    'City',
    'SoE',
    'Parent Name',
    'Parent Email',
    'Opt-out?',
    'Date'
));

$filter = new mth_person_filter();
$filter->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
$filter->setStatusYear(array($year->getID()));
if(req_get::is_set('grade')){
    $filter->setGradeLevel(req_get::txt_array('grade'));
}

if(req_get::is_set('soe')){
    $filter->setSchoolOfEnrollment(req_get::int_array('soe'));
}
foreach ($filter->getStudents() as $student) {
    /* @var $student mth_student */
    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        continue;
    }
    if (!($address = $parent->getAddress())) {
        core_notify::addError('Address Missing for ' . $parent);
        continue;
    }
    $reportArr[] = array(
        $student->getFirstName(),
        $student->getLastName(),
        $student->getGradeLevelValue($year->getID()),
        $student->getGender(),
        $parent->getAddress()->getCity(),
        $student->getSchoolOfEnrollment(false, $year),
        $parent->getPreferredLastName() . ', ' . $parent->getPreferredFirstName(),
        $parent->getEmail(),
        (($optOut = mth_testOptOut::getByStudent($student, $year)) ? 'Yes' : 'No'),
        ($optOut ? $optOut->date_submitted('m/d/Y') : '')
    );
}

include ROOT . core_path::getPath('../report.php');