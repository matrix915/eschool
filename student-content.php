<?php
mth_user::isParent() || core_secure::loadLogin();

global $parent, $student;

$parent = mth_parent::getByUser();

$pathArr = core_path::getPath()->getArray();

if ($pathArr[1] === 'new') {
    include core_config::getSitePath() . '/student/new-page.php';
    exit();
}

$student = mth_student::getBySlug($pathArr[1]);

if (!$student || !$student->isEditable()) {
    core_notify::addError('Student Not Found');
    header('Location: /home');
    exit();
}

if (isset($pathArr[2])) {
    $studentPage = $pathArr[2];
} else {
    $studentPage = 'tool-list';
}

cms_page::setDefaultPage(core_path::getPath('/student/' . $studentPage));

$loaderPath = $pathArr;
unset($loaderPath[0], $loaderPath[1]);
core_loader::setPath(core_path::getPath('/student/' . implode('/', $loaderPath)));

include core_config::getSitePath() . '/student/' . $studentPage . '-page.php';
