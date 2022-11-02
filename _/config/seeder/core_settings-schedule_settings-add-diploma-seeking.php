<?php

core_setting::init(
    'AllowDiplomaSeekingQuestion',
    'Diploma_seeking_question',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Enable Diploma Seeking Question',
    'Diploma Seeking Question'
);

core_setting::init(
    'DiplomaSeekingQuestionDefault',
    'Diploma_seeking_question',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Default value in diploma seeking question',
    'Diploma Seeking Question'
);