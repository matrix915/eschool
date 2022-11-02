<?php
core_setting::init(
    'unlock_packet',
    'packet_settings',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Unlock Enrollment Packet',
    'Enrollment packet will be unlock if the checkbox is check'
);

core_setting::init(
    'personal_information',
    'packet_settings',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Personal Information',
    'Will unlock tabs Contact, Personal, Education, Submission, but not require an update. Any new items to the enrollment packet that a parent hasn’t checked off ie. a new agreement box, etc. will be required'
);

core_setting::init(
    'immunizations',
    'packet_settings',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Immunizations',
    'Allow Immunization upload button to be enabled and require a new document'
);

core_setting::init(
    'proof_of_residency',
    'packet_settings',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Proof of Residency',
    'Allow proof of residency upload button to be enabled and require a new document'
);

core_setting::init(
    'iep_documents',
    'packet_settings',
    false,
    core_setting::TYPE_BOOL,
    true,
    'IEP/504 Documents',
    'Allow 504/IEP upload button to be enabled and require a new document'
);

core_setting::init(
    'parent_id',
    'packet_settings',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Parent ID',
    'Allow Parent ID upload button to be enabled and require a new document (Only available for TN)'
);

core_setting::init(
    'ReEnrollmentPacketContent',
    'Re-enrollment',
    '<p>Hi [PARENT],</p>
    <p>We are thrilled you are returning next year! Please resubmit [STUDENT_NAME]\'s packet with the below details.</p>
    [PACKET_INFORMATIONS]
    <p>Please use the link below to submit the required document(s) and/or information:<br />
    [LINK]</p>
    <p>Thanks!</p>
    <p>- My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Re-enrollment Information email content',
    '<dl>
        <dt>[PARENT]</dt>
        <dd>Parent\'s First Name</dd>
        <dt>[STUDENT_NAME]</dt>
        <dd>Student\'s First Name</dd>
        <dt>[PACKET_INFORMATIONS]</dt>
        <dd>List of avaliable documents that needs to be updated</dd>
        <dt>[LINK]</dt>
        <dd>The link for the parent to access student\'s packet</dd>
      </dl>'
);

core_setting::init(
    'ReEnrollmentPacketSubject',
    'Re-enrollment',
    'Re-enrollment Packet Update',
    core_setting::TYPE_TEXT,
    true,
    'Re-enrollment Information email Subject',
    ''
);

core_setting::init(
    'personal_information',
    're-enrollment_packet',
    'Please ensure all of your personal details are up to date, including contact, address, etc.',
    core_setting::TYPE_TEXT,
    true,
    'Personal Information email content',
    ''
);

core_setting::init(
    'immunizations',
    're-enrollment_packet',
    '<p>Our records indicate that [STUDENT_NAME] needs to updated their immunizations records for the [UPCOMING_GRADE_LEVEL] grade. State law requires that all students be fully immunized or submit a Personal Exemption form. Please provide an updated immunization record or a personal exemption form for the following immunizations:</p>',
    core_setting::TYPE_HTML,
    true,
    'Immunization Information email content',
    '<dl>
        <dt>[STUDENT_NAME]</dt>
        <dd>Student\'s First Name</dd>
        <dt>[UPCOMING_GRADE_LEVEL]</<dt>
        <dd>Upcoming grade Level</dd>
    </dl>'
);

core_setting::init(
    'proof_of_residency',
    're-enrollment_packet',
    'Please upload a Proof of Residency dated in the past 60 days',
    core_setting::TYPE_TEXT,
    true,
    'Residency Information email content',
    ''
);

core_setting::init(
    'iep_documents',
    're-enrollment_packet',
    'Please updated your IEP/504 Documents',
    core_setting::TYPE_TEXT,
    true,
    'IEP/504 Documents Information email content',
    ''
);

core_setting::init(
    'parent_id',
    're-enrollment_packet',
    'Please updated your Parent ID Documents',
    core_setting::TYPE_TEXT,
    true,
    'Parent ID Information email content',
    ''
);
