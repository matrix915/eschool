<?php

/**
 * Created by PhpStorm.
 * User: Cres
 * Date: 09/16/202
 * Time: 1:36 PM
 */

/** @var $year mth_schoolYear */ ($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Approved Reimbursement Report - ' . $year;

$reportArr = array(array(
    'Duplicate',
    'Parent First Name',
    'Parent Last Name',
    'Parent Email',
    'Parent Full Name',
    'Approved Amount'
));

while ($reimbursement = mth_reimbursement::allGroupBy($year, 'parent_id', array(mth_reimbursement::STATUS_APPROVED), true, false, true)) {

    if (
        !($parent = $reimbursement->student_parent())
        || !($address = $parent->getAddress())
    ) {
        continue;
    }

    $reportArr[] = array(
       $reimbursement->getOccurrences() ? 'Yes' : '',
        $parent->getFirstName(),
        $parent->getLastName(),
        $parent->getEmail(),
        $parent->getName(false, true),
        '$' . $reimbursement->totalAmount(true)
    );
}

include ROOT . core_path::getPath('../report.php');
