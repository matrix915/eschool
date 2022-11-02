<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$statuses = [
    mth_schedule::STATUS_ACCEPTED,
    mth_schedule::STATUS_SUBMITTED,
    mth_schedule::STATUS_CHANGE,
    mth_schedule::STATUS_RESUBMITTED,
    mth_schedule::STATUS_CHANGE_POST
];

$reportArr = [[
    'Provider',
    'Course',
    'Count'
]];
$providers = [];
foreach (req_get::int_array('provider') as $provider_id) {
    if (!($provider = mth_provider::get($provider_id))) {
        continue;
    }
    $providers[] = $provider->name();
    if (mth_provider_course::count($provider) > 0) {
        while ($course = mth_provider_course::each($provider)) {
            $reportArr[] = [
                $provider->name(),
                $course->title(),
                mth_schedule_period::countWithProviderCourse($course, $year, $statuses, true)
            ];
        }
    } else {
        while ($schedule_period = mth_schedule_period::eachWithProvider($provider, $year, $statuses)) {
            $name = str_replace(
                [' ', '.', 'school', 'hs', 'high', 'jr'],
                ['', '', '', '', '', 'junior'],
                strtolower($schedule_period->tp_district() . $schedule_period->tp_name()));
            if (!isset($reportArr[$name])) {
                $reportArr[$name] = [
                    $provider->name(),
                    ucwords($schedule_period->tp_district() . ' - ' . $schedule_period->tp_name()),
                    0
                ];
            }
            $reportArr[$name][2]++;
            if (strlen($reportArr[$name][1]) < strlen($schedule_period->tp_district() . ' - ' . $schedule_period->tp_name())) {
                $reportArr[$name][1] = ucwords($schedule_period->tp_district() . ' - ' . $schedule_period->tp_name());
            }
        }
    }
}
$file = 'Enrollment Counts for ' . implode(', ', $providers) . ' - ' . $year;

include ROOT . core_path::getPath('../report.php');