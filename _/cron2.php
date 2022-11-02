<?php
require_once 'app/inc.php';

define('LIMIT', 30);
$count = 0;

$year = mth_schoolYear::getCurrent();

if (time() < $year->getDateBegin()) {
    exit();
}

$subject = mth_subject::getByName('Homeroom');
while ($course = mth_course::getEach($subject)) {
    $mth_course_ids[] = $course->getID();
}
$canvas_course_query = new mth_canvas_course_query();
$canvas_course_query->set_mth_course_ids($mth_course_ids);
$canvas_course_query->set_school_year_ids(array($year->getID()));

while ($canvas_course = $canvas_course_query->each()) {
    while ($enrollment = mth_canvas_enrollment::eachCanvasCourseEnrollment($canvas_course->canvas_course_id())) {
        if ($enrollment->gradeNeedsToBeRefreshed() && $enrollment->isActive() && $enrollment->isStudent()) {
            $count++;
            $enrollment->grade(true);
        }
        if ($count >= LIMIT) {
            break 2;
        }
    }
}