<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$reportArr = [[
    'user_id',
    'login_id',
    'first_name',
    'last_name',
    'email',
    'status'
]];

$statuses = [mth_schedule::STATUS_ACCEPTED];
$schedule_periods = mth_schedule_period::allWithProviders([71, 69,70], $year, $statuses, false, true);
foreach ($schedule_periods as $schedule_period) {
    $person = $schedule_period->schedule()->student();
    if(!$person) {
        continue;
    }
    if (!($parent = $person->getParent())) {
        continue;
    }
    if (!$schedule_period->provider_courseTitle()) {
        continue;
    }
    $reportArr[] = array(
        $person->getEmail(),
        $person->getEmail(),
        $person->getPreferredFirstName(),
        $person->getLastName(),
        $person->getEmail(),
        'active'
    );
}

$file = 'users';

include ROOT . core_path::getPath('../report.php');