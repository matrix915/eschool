<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Reimbursement Amounts entered - ' . $year;
$reportArr = array(array(
    'Student Last',
    'Student First',
    'Student Email',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Parent Phone',
    'Amount'
));

while ($schedule = mth_schedule::each(array(mth_schedule::STATUS_ACCEPTED))) {
    $amount = 0;
    if ($schedule->schoolYear() != $year
        || !($student = $schedule->student())
        || !($parent = $student->getParent())
    ) {
        continue;
    }
    while ($schedule_period = $schedule->eachPeriod()) {
        if (!$schedule_period->reimbursed()) {
            continue;
        }
        $amount += $schedule_period->reimbursed();
    }
    if ($amount == 0) {
        continue;
    }
    $reportArr[] = array(
        $student->getLastName(),
        $student->getFirstName(),
        $student->getEmail(),
        $parent->getPreferredLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone(),
        '$' . number_format($amount, 2)
    );
}

include ROOT . core_path::getPath('../report.php');