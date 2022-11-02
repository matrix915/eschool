<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');


$statuses = [
    mth_schedule::STATUS_SUBMITTED,
    mth_schedule::STATUS_ACCEPTED,
    mth_schedule::STATUS_CHANGE,
    mth_schedule::STATUS_RESUBMITTED,
    mth_schedule::STATUS_CHANGE_POST
];

$reportArr = [[
    'Subject',
    'Course',
    'Count'
]];
$subjects = [];
foreach (req_get::int_array('subject') as $subject_id) {
    if (!($subject = mth_subject::getByID($subject_id))) {
        continue;
    }
    $subjects[] = $subject->getName();
    while ($course = mth_course::getEach($subject)) {
        $count = mth_schedule_period::countWithCourse($course, $year, $statuses);
        $core_path =  core_path::getPath();
        $count_link = $count > 0 ?
            "<a onclick=\"top.global_popup_iframe('enrolledPopup', '/_/admin/reports/schedule/enrolled?course_id={$course->getID()}&course={$course->title()}')\">$count</a>" : $count;
        $count_data = req_get::bool('csv') || req_get::bool('google') ? $count : $count_link;
        $reportArr[] = [
            $subject->getName(),
            $course->title(),
            $count_data
        ];
    }
}
$file = 'Enrollment Counts for ' . implode(', ', $subjects) . ' - ' . $year;

include ROOT . core_path::getPath('../report.php');
