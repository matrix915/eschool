<?php

function studentUserIdFromAssessmentFileId($fileId)
{
    return core_db::runGetValue(
        'SELECT mp.user_id FROM yoda_answers_file yaf 
                LEFT JOIN yoda_student_assessments ysa
                    ON yaf.student_assesment_id = ysa.id
                LEFT JOIN mth_person mp
                    ON ysa.person_id = mp.person_id
                WHERE mth_file_id = ' . (int)$fileId . '
                LIMIT 1;');
}

function userCanSeeAssessmentFile(core_user $user, $fileId)
{
    // Unauthenticated users MAY NOT see file
    if (!$user) {
        return false;
    }

    // Logged in user is an admin
    if ($user->isAdmin()) {
        return true;
    }

    // Logged in user is a teacher
    if ($user->isTeacher() || $user->isAssistant()) {
        return true;
    }

    // Logged in user is the student who owns the file
    $studentUserId = studentUserIdFromAssessmentFileId($fileId);
    if ($studentUserId === $user->getID()) {
        return true;
    }

    // Logged in user is a parent of the student who owns the file
    $student = mth_student::getByUserID($studentUserId);
    if(!$student) {
        die('Unauthorized');
    }
    $parents = mth_parent::getParentsByStudentIds([$student->getID()]);
    $parentUserIds = array_map(function (mth_parent $parent) {
        return $parent->getUserID();
    }, $parents);
    if (in_array($user->getID(), $parentUserIds)) {
        return true;
    }

    return false;
}

$user = core_user::getCurrentUser();

if (isset($_GET['download'])) {
    $file = mth_file::get($_GET['download']);
    if (!$file) {
        die();
    }

    if(!$user || !userCanSeeAssessmentFile($user, $file->id())) {
        die('Unauthorized');
    }

    header('Content-type: ' . $file->type());
    header('Content-Disposition: attachment; filename="' . $file->name() . '"');
    echo $file->contents();
    exit();
}

if (isset($_GET['viewpdf'])) {
    $file = mth_file::get($_GET['viewpdf']);
    if (!$file) {
        die();
    }

    //Non-users and invalid users should not see the file
    if(!$user || !userCanSeeAssessmentFile($user, $file->id())) {
        die('Unauthorized');
    }

    header('Content-type: ' . $file->type());
    echo $file->contents();
    exit();
}

core_loader::isPopUp();
core_loader::printHeader();
echo '<button type="button" class="iframe-close btn btn-round btn-secondary" onclick="top.global_popup_iframe_close(\'fileviewer\')">Close</button>';

if (isset($_GET['file'])) {
    $file = mth_file::getByHash($_GET['file']);
    if (!$file) {
        die();
    }

    if(!$user || !userCanSeeAssessmentFile($user, $file->id())) {
        die('Unauthorized');
    }

    $filetype = explode('/', $file->type());
    if ($filetype[0] == 'image') {
        echo '<img class="w-full" src="data:image/png;base64,' . $file->contents(false) . '" />';
    } elseif ($file->type() == 'application/pdf') {
        echo '<a href="?viewpdf=' . $_GET['file'] . '" target="_blank"> View ' . $file->name() . '</a> or <a href="?download=' . $_GET['file'] . '" >Download ' . $file->name() . '</a>';
    } else {
        echo '<a href="?download=' . $_GET['file'] . '"> No viewer for ' . $file->name() . ', click to download instead</a>';
    }

}
core_loader::printFooter();
?>

