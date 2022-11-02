<?php
core_setting::init(
    'ReEnrollmentPacketAcceptanceContent',
    'Re-enrollment',
    '<p>Hi [PARENT],</p>
    <p>Thank you for submitting record of or a current exemption form for [STUDENT]’s 7th grade immunizations.  We are excited for [STUDENT] to participate in the 2021-22 My Tech High Program!</p>
    <p>Thanks!</p>
    <p>- My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Re-enrollment Packet Acceptance email content',
    '<dl>
        <dt>[PARENT]</dt>
        <dd>Parent\'s First Name</dd>
        <dt>[STUDENT_NAME]</dt>
        <dd>Student\'s First Name</dd>
    </dl>'
);

core_setting::init(
    'ReEnrollmentPacketAcceptanceSubject',
    'Re-enrollment',
    'Record of 7th grade immunizations received',
    core_setting::TYPE_TEXT,
    true,
    'Re-enrollment Packet Acceptance email Subject',
    ''
);