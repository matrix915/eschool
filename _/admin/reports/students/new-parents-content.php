<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'New Parents - ' . $year;
$reportArr = array(array(
    'Last Name',
    'First Name',
    'Street Address',
    'City',
    'Zipcode',
    'Phone',
    'Email',
));

$filter = new mth_person_filter();
$filter->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
$filter->setStatusYear(array($year->getID()));
$filter->setNewToSchoolYear($year);
$newStudentParentIDs = $filter->getParentIDs();

$pastYearIDs = array();
foreach (mth_schoolYear::getSchoolYears(NULL, strtotime('-1 week', $year->getDateBegin())) as $pastYear) {
    $pastYearIDs[] = $pastYear->getID();
}

$filter2 = new mth_person_filter();
$filter2->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
$filter2->setStatusYear($pastYearIDs, mth_person_filter::FILTER_STATUS_YEAR_ANY);
$newParentIDs = array_diff($newStudentParentIDs, $filter2->getParentIDs());

foreach (mth_parent::getParents($newParentIDs) as $parent) {
    /* @var $parent mth_parent */
    if (!($address = $parent->getAddress())) {
        core_notify::addError('Address Missing for ' . $parent);
        continue;
    }
    $reportArr[] = array(
        $parent->getLastName(),
        $parent->getFirstName(),
        $address ? $address->getStreet() . ($address->getStreet2() ? ' ' . $address->getStreet2() : '') : '',
        $address ? $address->getCity() : '',
        $address ? $address->getZip() : '',
        $parent->getPhone(),
        $parent->getEmail()
    );
}

include ROOT . core_path::getPath('../report.php');