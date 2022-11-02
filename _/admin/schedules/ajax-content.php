<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 12/31/15
 * Time: 3:55 PM
 */

($schedule = mth_schedule::getByID(req_get::int('schedule'))) || die('Schedule not found');
($student = $schedule->student()) || die('Schedule student missing');
($parent = $student->getParent()) || die('Student\'s parent missing');

if (req_get::bool('getHomeroomGrade')) {
    if (($enrollment = mth_canvas_enrollment::getBySchedulePeriod($schedule->getPeriod(1)))
        && $enrollment->id()
    ) {
        exit($enrollment->grade(true) . '%');
    }
    exit('...');
}
if (req_get::bool('getHomeroomZeroCount')) {
    if (($enrollment = mth_canvas_enrollment::getBySchedulePeriod($schedule->getPeriod(1)))
        && $enrollment->id()
    ) {
        exit((string)$enrollment->zeroCount(true));
    }
    exit('...');
}