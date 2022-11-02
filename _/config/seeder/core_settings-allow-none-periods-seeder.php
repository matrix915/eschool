<?php

while ($period = mth_period::each()) {
  $num = $period->num();
  core_setting::init(
    'allow_none_period_'.$num,
    'schedule_period',
    '0',
    core_setting::TYPE_BOOL,
    true,
    'Period '.$num,
    'Period '.$num
  );
}

core_setting::init(
    'allow_none',
    'schedule_period',
    '0',
    core_setting::TYPE_BOOL,
    true,
    'Enable option "None"',
    'Schedule Periods can be allowed to be set to none if this checkbox is checked.'
);