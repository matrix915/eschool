<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */

$file = 'Tooele-MTH Diploma-seeking students - ' . $year;

$reportArr = [[
    'Student Legal Last Name',
    'Student Legal First Name',
    'Gender',
    'Grade',
    'Student Email',
    'Parent Legal First Name',
    'Parent Legal Last Name',
    'Parent Email',
    'Parent Phone',
    'City of Residence',
    'Notes:'
]];

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::Tooele);
$filter->setDiplomaSeeking(true);

foreach ($filter->getStudents() as $student) {

    if(!($parent = $student->getParent())){
        core_notify::addError('Packet parent for ' . $student);
        continue;
    }

    $grade_level = $student->getGradeLevelValue($year->getID());
    $reportArr[] = [
        $student->getLastName(),
        $student->getFirstName(),
        $student->getGender(),
        $grade_level,
        $student->getEmail(),
        $parent->getPreferredFirstName(),
        $parent->getPreferredLastName(),
        $parent->getEmail(),
        $parent->getPhone(),
        $parent->getCity(),
        ($student->isMidYear() ? 'Joined MTH mid-year ' : '') . $parent->note()
    ];
}

include ROOT . core_path::getPath('../report.php');