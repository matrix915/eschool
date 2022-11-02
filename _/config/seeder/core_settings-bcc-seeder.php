<?php
core_setting::init(
    'adminEmail',
    '',
    'admin@mytechhigh.com',
    core_setting::TYPE_TEXT,
    true,
    'Admin Email',
    '<p>This is admin email address</p>'
);

core_setting::init(
    'schedulebcc',
    'Schedules',
    'help@mytechhigh.com, admin@mytechhigh.com, kparkinson@mytechhigh.com, kelly@mytechhigh.com, madisen@mytechhigh.com, julie@mytechhigh.com',
    core_setting::TYPE_TEXT,
    true,
    'Email BCC',
    '<p>Send an email copy to this email address</p>'
);