<?php

core_setting::init(
    'packetAgeIssueEmail',
    'Packets',
    "<p>Hi, [PARENT],</p>

<p>[INSTRUCTIONS]</p>

<p>Let us know if you have any questions by contacting Amy at admin@mytechhigh.com. We're happy to help however we can!</p>

<p>My Tech High</p>
",
    core_setting::TYPE_HTML,
    true,
    'Packet Age Issue Email Content',
    "<p>This is the email a parent recieves if their packet is marked as age issue.
          You can use the following codes in the email content which will be replaced with actual values:</p>
          <dl><dt>[PARENT]</dt>
          <dd>Parent's first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student's first name</dd>
          <dt>[GRADE_LEVEL]</dt>
          <dd>Student's grade level they are applying for</dd>
          <dt>[YEAR]</dt>
          <dd>The year they applied for (e.g. 2014-15)</dd>
          <dt>[INSTRUCTIONS]</dt>
          <dd>Where the specific instructions to the parent will be included in the email</dd>
          </dl>"
);

core_setting::init(
    'packetAgeIssueEmailSubject',
    'Packets',
    'My Tech High Enrollment Packet - Age Issue',
    core_setting::TYPE_TEXT,
    true,
    'Packet Age Issue Email Subject',
    ''
);