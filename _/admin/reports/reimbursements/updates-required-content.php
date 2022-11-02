<?php

/** @var $year mth_schoolYear */ ($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Reimbursement Updates Required - ' . $year;

$reportArr = [[
     'Parent Name',
     'Parent Email',
     'Date of Submission'
]];

$columnSort = [[]];

while ($reimbursement = mth_reimbursement::each(null, null, $year, [mth_reimbursement::STATUS_UPDATE])) {
     if (
          !($student = $reimbursement->student())
          || !($parent = $student->getParent())
     ) {
          continue;
     }

     $reportArr[] = [
          $parent->getName(true),
          $parent->getEmail(),
          $reimbursement->date_submitted('m/d/Y')
     ];

     $columnSort[] = [null,null,$reimbursement->date_submitted()];
}

include ROOT . core_path::getPath('../report.php');
