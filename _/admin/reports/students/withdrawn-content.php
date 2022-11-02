<?php

use mth\yoda\homeroom\Query;

($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$file = 'Withdrawn Students';
$reportArr = array(array(
    'Legal Student Name',
    'Grade',
    'Legal Parent Name',
    'Parent Email',
    'Parent Phone',
    'Status Date',
    'SoE'
));

$columnDefs = [
    ['type' => 'date', 'targets' => -2]
];
$sortDef =  [[5, 'asc']];

$filter = new mth_person_filter();
$filter->setStatus(mth_student::STATUS_WITHDRAW);
$filter->setStatusYear(array($year->getID()));

foreach ($filter->getStudents() as $student){
   

    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        continue;
    }

    $reportArr[] = array(
        $student->getName(true,true),
        $student->getGradeLevelValue($year->getID()),
        $parent->getName(true,true),
        $parent->getEmail(),
        $parent->getPhone(),
        $student->getStatusDate($year, 'm/d/Y'),
        $student->getWithdrawalSOE(false, mth_withdrawal::letter_year_calculator($student, $year)) == 'Unassigned' ? '' : $student->getWithdrawalSOE(false, mth_withdrawal::letter_year_calculator($student, $year))
    );
}

include ROOT . core_path::getPath('../report.php');