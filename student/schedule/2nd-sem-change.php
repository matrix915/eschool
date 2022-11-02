<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $pathArr array */

($currentSchedule = mth_schedule::get($student))
|| core_loader::reloadParent('Schedule not found');

($oldSchedulePeriod = mth_schedule_period::getByID(req_get::int('schedule_period')))
|| core_loader::reloadParent('Invalid schedule period id provided');

($newSchedulPeriod = $oldSchedulePeriod->duplicateTo2ndSem(true))
|| core_loader::reloadParent('Unable to create new schedule period');

if (req_get::bool('duplicateOnly')) {
    core_loader::reloadParent();
}

core_loader::redirect('period?schedule_period=' . $newSchedulPeriod->id());
