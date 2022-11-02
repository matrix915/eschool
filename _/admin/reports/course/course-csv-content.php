<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Course CSV - ' . $year;
$reportArr = array(
    array(
        'course_id',
        'short_name',
        'long_name',
        'status'
    )
);

$statuses = [mth_schedule::STATUS_ACCEPTED];
$schedule_periods = mth_schedule_period::allWithProviders([71, 69,70], $year, $statuses, false, true);
$course_id_array = [];
foreach ($schedule_periods as $schedule_period) {
    $person = $schedule_period->schedule()->student();
    if (!$person) {
        continue;
    }
    if (!($parent = $person->getParent())) {
        continue;
    }
    if (!$schedule_period->provider_courseTitle()) {
        continue;
    }
    $course_id = ( strpos(strtolower($schedule_period->provider_courseTitle()), 'kiwico') !== false ? $year->getStartYear().'-'.$schedule_period->mth_provider_id().'-'.$schedule_period->provider_course_id() : $year->getStartYear().'-'.$schedule_period->mth_provider_id() );
    if (in_array($course_id, $course_id_array)) {
        continue;
    }
    $reportArr[] = array(
        $course_id,
        $schedule_period->provider_courseTitle(),
        $schedule_period->provider_courseTitle(),
        'active'
    );
    $course_id_array[] = $course_id;
}

include ROOT . core_path::getPath('../report.php');