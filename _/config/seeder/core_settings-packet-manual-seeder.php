<?php
core_setting::init(
    'ManualPacketReminderContent',
    'Packets',
    '<p>Hi [PARENT],</p>
    <p>Just a reminder to complete the following for [STUDENT]\'s My Tech High enrollment by [DEADLINE].</p>
    <p>Please use the link below to submit the required document(s) and/or information:</p>
    <p>[LINK]</p>
    <p>Let us know if you have any questions by contacting us at <a href="help@mytechhigh.com">help@mytechhigh.com</a>. We\'re happy to help in any way we can!</p>
    <p>Thanks!</p>
    <p>My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Packet Manual Reminder Email Content',
    '<dl>
        <dt>[PARENT]</dt>
        <dd>Parent\'s First Name</dd>
        <dt>[STUDENT]</dt>
        <dd>Student\'s First Name</dd>
        <dt>[LINK]</dt>
        <dd>The link for the parent to access student\'s packet</dd>
        <dt>[DEADLINE]</dt>
        <dd>The deadline that the packet information must be all submitted</dd>
    </dl>'
);

core_setting::init(
    'ManualPacketReminderSubject',
    'Packets',
    'My Tech High Enrollment Packet - A Reminder',
    core_setting::TYPE_TEXT,
    true,
    'Packet Manual Reminder Email Subject',
    ''
);