<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');
$file = 'enrollments';

$reportArr = [
     [
          'course_id',
          'user_id',
          'role',
          'status'
     ]
];   

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
     $reportArr[] = [
          (strpos(strtolower($schedule_period->provider_courseTitle()), 'kiwico') !== false
              ? $year->getStartYear().'-'.$schedule_period->mth_provider_id().'-'.$schedule_period->provider_course_id()
              : $year->getStartYear().'-'.$schedule_period->mth_provider_id() ),
          $person->getEmail(),
          'student',
          'active'
    ];
}

include ROOT . core_path::getPath('../report.php');