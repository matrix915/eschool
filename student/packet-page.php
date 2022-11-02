<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $pathArr array */

$packet = mth_packet::getStudentPacket($student);
if (!$packet) {
    core_loader::redirect('/student/' . $student->getSlug());
}
$packetURI = '/student/' . $student->getSlug() . '/packet';
$packetStep = &$_SESSION['mth_packet_step'][$student->getSlug()];
$packetRunValidation = &$_SESSION['mth_packet_runValidation'][$student->getSlug()];

if (!empty($_GET['file'])) {
    include core_config::getSitePath() . '/student/packet/file.php';
    exit();
}

function levelOneComplete(mth_packet $packet, mth_student $student, mth_parent $parent)
{
    return $student->getFirstName()
    && $student->getLastName()
    && $student->getEmail()
    && $parent->getAddress()
    && ($cell = $parent->getPhone('Cell'))
    && $cell->getName()
    && $packet->getSecondaryContactFirst()
    && $packet->getSecondaryEmail()
    && $packet->getSecondaryPhone();
}

function levelTwoComplete(mth_packet $packet, mth_student $student)
{
    return $student->getDateOfBirth()
    && $student->getGender()
    && $packet->isHispanic() !== null
    && $packet->getRace()
    && $packet->getLanguage()
    && $packet->getLanguageAtHome()
    && $packet->getWorkedInAgriculture() !== null
    && $packet->getMilitary() !== null;
}

function levelThreeComplete(mth_packet $packet, mth_student $student)
{
    // return $packet->getSchoolDistrict() &&
    // && !is_null($packet->getSpecialEd())
    // && (!$packet->getSpecialEd(true)
    //     || ($packet->getUnderstandsSpecialEd()
    //         && $packet->getUnderstandsSpedScheduling()))
    return $student->getGradeLevelValue(mth_schoolYear::getNext())
        && ($packet->getLastSchoolType()
        || ($packet->getLastSchoolName()
            && $packet->getLastSchoolAddress()
            && $packet->getPermissionToRequestRecords()));
}

function levelFourComplete(mth_packet $packet, mth_student $student)
{
    $missingDocument = false;
    foreach ($packet->getReuploadFiles() as $document) {
        if ($document != 'personal_information' && $document != 'last_school' && $document != 'last_school_address') {
            $missingDocument = true;
        }
    }
    return mth_packet_file::getPacketFile($packet, 'bc')
    && mth_packet_file::getPacketFile($packet, 'im')
    && mth_packet_file::getPacketFile($packet, 'ur')
    && (!$packet->requireIEP() || mth_packet_file::getPacketFile($packet, 'iep'))
    && !$missingDocument
        && ($student->getAddress()->getState() != 'OR' ? true : mth_packet_file::getPacketFile($packet, 'itf'));
}

function packetReadyToSubmit(mth_packet $packet, mth_student $student, mth_parent $parent)
{
    return levelOneComplete($packet, $student, $parent)
    && levelTwoComplete($packet, $student)
    && levelThreeComplete($packet, $student)
    && levelFourComplete($packet, $student);
}

if ($packet->isMissingInfo()) {
    if ($student->getReenrolled() && in_array('personal_information', $packet->getReuploadFiles()) && (core_setting::get('personal_information', 'packet_settings')->getValue() || $packet->requiresReenrollFiles())) {
        if (core_setting::get('personal_information', 'packet_settings')->getValue()
            && in_array('personal_information', $packet->getReuploadFiles())
            && (empty($pathArr[3]) && !isset($packetStep))
        ) {
            $packetStep = 1;
            header('location: ' . $packetURI . '/1');
            exit();
        } elseif (!$packet->requiresReenrollFiles()
            && core_setting::get('personal_information', 'packet_settings')->getValue()
            && in_array('personal_information', $packet->getReuploadFiles())
            && !empty($pathArr[3])
            && !in_array($pathArr[3], [1, 2, 3, 5])
        ) {
            $packetStep = 1;
            header('location: ' . $packetURI . '/1');
            exit();
        } elseif (!core_setting::get('personal_information', 'packet_settings')->getValue()
            && !in_array('personal_information', $packet->getReuploadFiles())
            && $packet->requiresReenrollFiles()
            && !levelFourComplete($packet, $student)
            && (empty($pathArr[3]) || $pathArr[3] < 4)
        ) {
            $packetStep = 4;
            header('location: ' . $packetURI . '/4');
            exit();
        } elseif (!core_setting::get('personal_information', 'packet_settings')->getValue()
            && !in_array('personal_information', $packet->getReuploadFiles())
            && packetReadyToSubmit($packet, $student, $parent)
        ) {
            if ($packet->resubmit()) {
                core_notify::addMessage('We received your updated file(s) and/or information. Thank you!');
            } else {
                core_notify::addMessage('There was a problem resubmitting your packet, please try again.');
            }
        }
    } else {
        if (!$packet->requiresReenrollFiles()
            && core_setting::get('personal_information', 'packet_settings')->getValue()
            && !empty($pathArr[3])
            && !in_array($pathArr[3], [1, 2, 3, 5])
        ) {
            $packetStep = 1;
            header('location: ' . $packetURI . '/1');
        } else {
            if (!empty($pathArr[3]) && $pathArr[3] != 4
                && !(in_array('last_school', $packet->getReuploadFiles())
                    || in_array('last_school_address', $packet->getReuploadFiles())
                )
                && !core_setting::get('personal_information', 'packet_settings')->getValue()
            ) {
                core_notify::addError('Please resubmit ' . $student->getPreferredFirstName() . '\'s required Document(s)');
                $packetRunValidation = true;
                $packetStep = 4;
                header('Location: ' . $packetURI . '/4');
            } elseif (!$packet->getReuploadFiles()
                && packetReadyToSubmit($packet, $student, $parent)
                && !core_setting::get('personal_information', 'packet_settings')->getValue()
            ) {
                if ($packet->resubmit()) {
                    core_notify::addMessage('We received your updated file(s) and/or information. Thank you!');
                } else {
                    core_notify::addMessage('There was a problem resubmitting your packet, please try again.');
                }
            }
        }
    }
}

if (empty($pathArr[3])) {
    $packetRunValidation = false;
    if ($packet->isSubmitted()) {
        header('Location: ' . $packetURI . '/6');
        exit();
    }
    if (!isset($packetStep)) {
        $packetStep = 1;
    }

    if (!levelOneComplete($packet, $student, $parent) || ($student->getReenrolled() && core_setting::get('personal_information', 'packet_settings')->getValue() && $packetStep === 1)) {
        if ($packetStep > 1) {
            core_notify::addError('Please fill out this form');
            $packetRunValidation = true;
        }
        $packetStep = 1;
        header('location: ' . $packetURI . '/1');
        exit();
    }
    if (!levelTwoComplete($packet, $student) || ($student->getReenrolled() && core_setting::get('personal_information', 'packet_settings')->getValue() && $packetStep === 2)) {
        if ($packetStep > 2) {
            core_notify::addError('Please fill out this form');
            $packetRunValidation = true;
        }
        $packetStep = 2;
        header('location: ' . $packetURI . '/2');
        exit();
    }
    if (!levelThreeComplete($packet, $student) || ($student->getReenrolled() && core_setting::get('personal_information', 'packet_settings')->getValue() && $packetStep === 3)) {
        if ($packetStep > 3) {
            core_notify::addError('Please fill out this form');
            $packetRunValidation = true;
        }
        $packetStep = 3;
        header('location: ' . $packetURI . '/3');
        exit();
    }

    if (!levelFourComplete($packet, $student) || ($student->getReenrolled() && $packet->requiresReenrollFiles() && $packetStep === 4)) {
        if ($packetStep > 4) {
            core_notify::addError('Please upload the required documents');
            $packetRunValidation = true;
        }
        $packetStep = 4;
        header('Location: ' . $packetURI . '/4');
        exit();
    }

    if ($packetStep > 5) {
        core_notify::addError('Please complete this form');
        $packetRunValidation = true;
    }

    $packetStep = 5;
    header('Location: ' . $packetURI . '/5');
    exit();
} else {
    if ($pathArr[3] == 6 && !$packet->isSubmitted()) {
        header('Location: ' . $packetURI);
        exit();
    }

    if ($packet->isSubmitted()) {
        $pathArr[3] = 6;
    }

    include core_config::getSitePath() . '/student/packet/' . $pathArr[3] . '.php';
    exit();
}

core_loader::print404headers();

?>
    <div class="page">
        <div class="page-content container-fluid text-center">
            <h1>Packet Page Not Found</h1>
            <a href="/home">Go back Home</a>
        </div>
    </div>
<?php
core_loader::printFooter('student');