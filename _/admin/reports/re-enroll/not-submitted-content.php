<?php

($nextyear = $_SESSION['mth_reports_school_year']) || die('No next year');
/* @var $nextyear mth_schoolYear */

($year = $nextyear->getPreviousYear()) || die('No current year');


$file = $nextyear . ' Intent to Re-enroll Not Submitted';
$reportArr = array(array(
    'Parent Last',
    'Parent First',
    'Email',
    'Phone'
));

$filter = new mth_person_filter();
$filter->setStatus(array(mth_student::STATUS_ACTIVE));
$filter->setStatusYear(array($year->getID()));
$filter->setExcludeStatusYear(array($nextyear->getID()));

foreach ($filter->getParents() as $parent) {
    /* @var $parent mth_parent */
    $reportArr[] = array(
        $parent->getPreferredLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone()
    );
}

include ROOT . core_path::getPath('../report.php');