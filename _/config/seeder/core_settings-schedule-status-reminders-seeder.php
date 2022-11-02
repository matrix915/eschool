<?php

//START SUBMITTED
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_SUBMITTED . '-subject',
  'scheduleBulk',
  "[SCHOOL_YEAR] Schedule",
  core_setting::TYPE_TEXT,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_SUBMITTED) . ' Schedules Email Subject',
  ''
);
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_SUBMITTED . '-content',
  'scheduleBulk',
  "<p>[PARENT],</p>
<p>We are excited to have [STUDENT] participate in the My Tech High program and appreciate you submitted [STUDENT]'s schedule. We are working hard to process schedules and once the schedule is approved, we will send an email. 
Thanks for your patience and understanding!</p>

<p>If you have any, please email us at help@mytechhigh.com.</p>

<p>Thanks!</p>
<p>My Tech High</p>",
  core_setting::TYPE_HTML,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_SUBMITTED) . ' Schedules Email Content',
  "<p>Changeable Options for email content and subject</p>
          <dl><dt>[PARENT]</dt>
          <dd>Parent's first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student's first name</dd>
          <dt>[SCHOOL_YEAR]</dt>
          <dd>The school year of the schedule (2021-22)</dd>
          <dt>[LINK]</dt>
          <dd>The link to the student's editable schedule</dd>
          </dl>"
);
//END SUBMITTED

//START RESUBMITTED
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_RESUBMITTED . '-subject',
  'scheduleBulk',
  "[SCHOOL_YEAR] Schedule",
  core_setting::TYPE_TEXT,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_RESUBMITTED) . ' Schedules Email Subject',
  ''
);
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_RESUBMITTED . '-content',
  'scheduleBulk',
  "<p>[PARENT],</p>
<p>We are excited to have [STUDENT] participate in the My Tech High program and appreciate you submitted [STUDENT]'s schedule. We are working hard to process schedules and once the schedule is approved, we will send an email. 
Thanks for your patience and understanding!</p>

<p>If you have any, please email us at help@mytechhigh.com.</p>

<p></p>Thanks!</p>
<p>My Tech High</p>",
  core_setting::TYPE_HTML,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_RESUBMITTED) . ' Schedules Email Content',
  "<p>Changeable Options for email content and subject</p>
          <dl><dt>[PARENT]</dt>
          <dd>Parent's first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student's first name</dd>
          <dt>[SCHOOL_YEAR]</dt>
          <dd>The school year of the schedule (2021-22)</dd>
          <dt>[LINK]</dt>
          <dd>The link to the student's editable schedule</dd>
          </dl>"
);
//END RESUBMITTED

//START UPDATES REQUIRED
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_CHANGE . '-subject',
  'scheduleBulk',
  "[STUDENT]'s [SCHOOL_YEAR] Schedule Needs to be Resubmitted",
  core_setting::TYPE_TEXT,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_CHANGE) . ' Schedules Email Subject',
  ''
);
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_CHANGE . '-content',
  'scheduleBulk',
  "<p>[PARENT],</p>
<p>We are excited to have you participate in the My Tech High program. Please update [STUDENT]s [SCHOOL_YEAR] by July 31 to remain enrolled. The below Period(s) needs to be changed. </p>
<p>[PERIOD_LIST] - The list of periods that need to be changed (This should be used in Updates Required and Unlocked templated only)</
<p>[LINK] - Link to student’s edible schedule</p>
<p>If you have any, please email us at help@mytechhigh.com.</p>

<p>Thanks!</p>
<p>My Tech High</p>",
  core_setting::TYPE_HTML,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_CHANGE) . ' Schedules Email Content',
  "<p>Changeable Options for email content and subject</p>
          <dl><dt>[PARENT]</dt>
          <dd>Parent's first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student's first name</dd>
          <dt>[SCHOOL_YEAR]</dt>
          <dd>The school year of the schedule (2021-22)</dd>
          <dt>[LINK]</dt>
          <dd>The link to the student's editable schedule</dd>
          <dt>[PERIOD_LIST]</dt>
          <dd>The list of periods that need to be changed</dd>
          </dl>"
);
//END UPDATES REQUIRED

//START UNLOCKED
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_CHANGE_POST . '-subject',
  'scheduleBulk',
  "[STUDENT]'s [SCHOOL_YEAR] Schedule Needs to be Resubmitted",
  core_setting::TYPE_TEXT,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_CHANGE_POST) . ' Schedules Email Subject',
  ''
);
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_CHANGE_POST . '-content',
  'scheduleBulk',
  "<p>[PARENT],</p>
<p>We are excited to have you participate in the My Tech High program. Please update [STUDENT]'s [SCHOOL_YEAR] by July 31 to remain enrolled. The below Period(s) needs to be changed. </p>
<p>[PERIOD_LIST] - The list of periods that need to be changed (This should be used in Updates Required and Unlocked templated only)</
<p>[LINK] - Link to student’s edible schedule</p>
<p>If you have any, please email us at help@mytechhigh.com.</p>

<p>Thanks!</p>
<p>My Tech High</p>",
  core_setting::TYPE_HTML,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_CHANGE_POST) . ' Schedules Email Content',
  "<p>Changeable Options for email content and subject</p>
          <dl><dt>[PARENT]</dt>
          <dd>Parent's first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student's first name</dd>
          <dt>[SCHOOL_YEAR]</dt>
          <dd>The school year of the schedule (2021-22)</dd>
          <dt>[LINK]</dt>
          <dd>The link to the student's editable schedule</dd>
          <dt>[PERIOD_LIST]</dt>
          <dd>The list of periods that need to be changed</dd>
          </dl>"
);
//END UNLOCKED

//START STARTED
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_STARTED . '-subject',
  'scheduleBulk',
  "[STUDENT]'s [SCHOOL_YEAR] Schedule due July 31",
  core_setting::TYPE_TEXT,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_STARTED) . ' Schedules Email Subject',
  ''
);
core_setting::init(
  'scheduleStatus-' . mth_schedule::STATUS_STARTED . '-content',
  'scheduleBulk',
  "<p>[PARENT],
<p>We are excited to have you participate in the My Tech High program. Please submit [STUDENT]'s [SCHOOL_YEAR] by July 31 to remain enrolled. </p>

<p>If you have any, please email us at help@mytechhigh.com.</

<p>Thanks!</p>
<p>My Tech High</p>",
  core_setting::TYPE_HTML,
  true,
  mth_schedule::status_option_text(mth_schedule::STATUS_STARTED) . ' Schedules Email Content',
  "<p>Changeable Options for email content and subject</p>
          <dl><dt>[PARENT]</dt>
          <dd>Parent's first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student's first name</dd>
          <dt>[SCHOOL_YEAR]</dt>
          <dd>The school year of the schedule (2021-22)</dd>
          <dt>[LINK]</dt>
          <dd>The link to the student's editable schedule</dd>
          </dl>"
);
//END STARTED

//START NOT STARTED
core_setting::init(
  'scheduleStatus-not_started-subject',
  'scheduleBulk',
  "[STUDENT]'s [SCHOOL_YEAR] Schedule due July 31",
  core_setting::TYPE_TEXT,
  true,
  'Not Started Schedules Email Subject',
  ''
);
core_setting::init(
  'scheduleStatus-not_started-content',
  'scheduleBulk',
  "<p>[PARENT],</p>
<p>We are excited to have you participate in the My Tech High program. Please submit [STUDENT]'s [SCHOOL_YEAR] by July 31 to remain enrolled. </p>

<p>If you have any, please email us at help@mytechhigh.com.</

<p>Thanks!</p>
<p>My Tech High</p>",
  core_setting::TYPE_HTML,
  true,
  'Not Started Schedules Email Content',
  "<p>Changeable Options for email content and subject</p>
          <dl><dt>[PARENT]</dt>
          <dd>Parent's first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student's first name</dd>
          <dt>[SCHOOL_YEAR]</dt>
          <dd>The school year of the schedule (2021-22)</dd>
          <dt>[LINK]</dt>
          <dd>The link to the student's editable schedule</dd>
          </dl>"
);
//END NOT STARTED