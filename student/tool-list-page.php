<?php
/* @var $parent mth_parent */
/* @var $student mth_student */

function packetUpdateList($student)
{
    $list = '';
    if (core_setting::get('personal_information', 'packet_settings')->getValue()) {
        $list .= '<p style="font-weight: 600;">Personal Information</p>';
        $list .= '<p>' . core_setting::get('personal_information', 're-enrollment_packet')->getValue() . '</p>';
    }

    if (core_setting::get('proof_of_residency', 'packet_settings')->getValue()) {
        $list .= '<p style="font-weight: 600;">Proof of Residency</p>';
        $list .= '<p>' . core_setting::get('proof_of_residency', 're-enrollment_packet')->getValue() . '</p>';
    }

    if (core_setting::get('immunizations', 'packet_settings')->getValue() && $student->getRequiredImmunizations()) {
        $list .= '<p style="font-weight: 600;">Immunizations</p>';
        $list .= '<p>' . core_setting::get('immunizations', 're-enrollment_packet')->getValue() . '</p>';
    }

    if (core_setting::get('iep_documents', 'packet_settings')->getValue() 
        && $student->specialEd()
        && mth_student::getSped($student->specialEd()) != mth_student::SPED_LABEL_NO
        && mth_student::getSped($student->specialEd()) != mth_student::SPED_LABEL_EXIT) {
        $list .= '<p style="font-weight: 600;">IEP/504 Documents</p>';
        $list .= '<p>' . core_setting::get('iep_documents', 're-enrollment_packet')->getValue() . '</p>';
    }

    // if (core_setting::get('parent_id', 'packet_settings')->getValue()) {
    //     $list .= '<p style="font-weight: 600;">Parent ID</p>';
    //     $list .= '<p>' . core_setting::get('parent_id', 're-enrollment_packet')->getValue() . '</p>';
    // }
    $school_year = mth_schoolYear::getNext();
    return str_replace(
        array(
            '[STUDENT_NAME]',
            '[UPCOMING_GRADE_LEVEL]',
        ),
        array(
            $student->getPreferredFirstName(),
            $student->getGradeLevel(false,false, $school_year->getID())
        ),
        $list
    );
}

function unlockDocuments($student) 
{
    $documents = [];

    if (core_setting::get('personal_information', 'packet_settings')->getValue()) {
        $documents[] = 'personal_information';
    }

    if (core_setting::get('proof_of_residency', 'packet_settings')->getValue()) {
        $documents[] = 'ur';
    }

    if (core_setting::get('immunizations', 'packet_settings')->getValue() && $student->getRequiredImmunizations()) {
        $documents[] = 'im';
    }

    if (core_setting::get('iep_documents', 'packet_settings')->getValue() 
        && $student->specialEd()
        && mth_student::getSped($student->specialEd()) != mth_student::SPED_LABEL_NO
        && mth_student::getSped($student->specialEd()) != mth_student::SPED_LABEL_EXIT) {
        $documents[] = 'iep';
    }
    return $documents;
}

if (req_get::bool('reapply')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('reapply'));

    $application = mth_application::create($student,  $year);
    $application->setCityOfResidence($student->getCity());

    if(($student->getGradeLevelValue() >= 9 && $student->getGradeLevelValue() <= 12) 
        && $student->isNewFromDiplomaSeeking($year) ) {
            $student->setDiplomaSeeking(NULL);
    }
    
    if (!$application->submit(true)) {
        core_notify::addError('Unable to submit the application. Please contact us for support.');
    } else {
        $student->populateNextYearGradeLevel();
        // change schoolofenrollment status to Unassigned status when reapply
        core_db::runQuery('UPDATE mth_student_school SET school_of_enrollment = 0 WHERE student_id=' . $student->getID() .' AND school_year_id=' . $year->getID());
    }
    core_loader::redirect();
}

if (req_get::bool('IntentToReEnroll')) {
    ((($year = mth_schoolYear::getYearReEnrollOpen()) || /* Used to let QA test re-enrollment*/ (preg_match('/(viviend)*(@codev\.com)/', core_user::getUserEmail()) && $year = mth_schoolYear::getNext()))
        && ($currentYear = mth_schoolYear::getCurrent())
        && ($student->isActive($currentYear) || $student->isActive($currentYear->getPreviousYear()))) || core_loader::redirect('/home');
    $ireenroll = req_get::txt('IntentToReEnroll') == 'Yes';
    $student->setStatus(
        ($ireenroll ? mth_student::STATUS_PENDING : mth_student::STATUS_WITHDRAW),
        $year,
        ($ireenroll ? null : $currentYear->getDateEnd('Y-m-d H:i:s'))
    );

    if ($ireenroll) {
        $student->setReenrolled($ireenroll, $year->getID());
        if(($student->getGradeLevelValue($year->getID()) >= 9 && $student->getGradeLevelValue() <= 12) 
            && $student->isNewFromDiplomaSeeking($year) ) {
                $student->setDiplomaSeeking(NULL);
        }

        if (core_setting::get('unlock_packet', 'packet_settings')->getValue() && !empty(packetUpdateList($student))) {
            $student->populateNextYearGradeLevel();
            mth_student_immunizations::gradeLevelReenrollRefresh($student->getID(), $student->getGradeLevel(false, false, $currentYear->getNext()));

            $packet = mth_packet::getStudentPacket($student);
            $packet->setStatus(mth_packet::STATUS_MISSING);
            $packet->setReuploadFiles(unlockDocuments($student));
            $packet->setReenrollFiles([], true);
            $link = (@$_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/student/' . $student->getSlug() . '/packet';
            $email = new core_emailservice();
            $emailContent = core_setting::get('ReEnrollmentPacketContent', 'Re-enrollment');
            $emailSubject = core_setting::get('ReEnrollmentPacketSubject', 'Re-enrollment');
            $email->send(
                [$student->getParent()->getEmail()],
                $emailSubject->getValue(),
                str_replace(
                    array(
                        '[PARENT]',
                        '[STUDENT_NAME]',
                        '[PACKET_INFORMATIONS]',
                        '[LINK]'
                    ),
                    array(
                        $student->getParent()->getPreferredFirstName(),
                        $student->getPreferredFirstName(),
                        packetUpdateList($student),
                        $link
                    ),
                    $emailContent->getValue()
                )
            );
        } 
    } else {
        if ($withdrawal = mth_withdrawal::getOrCreate($student, $year)) {
            $withdrawal->set_reason_txt(req_post::txt('reason_txt'));
            $withdrawal->set_reenroll_action();
            $withdrawal->set_effective_date();
            $withdrawal->setActive(false);
            $withdrawal->save();
        }
    }
    core_notify::addMessage('Intent to Re-enroll for ' . $student->getPreferredFirstName() . ' received.');
    core_loader::redirect('/home');
}

if (!empty($_GET['updateEmail'])) {
    if ($student->setEmail($_GET['updateEmail'])) {
        if ($student->errorUpdatingCanvasLoginEmail() && core_setting::get('AccountAuthorizationConfigID', 'Canvas')) {
            core_notify::addError('Unable to update ' . $student->getPreferredFirstName() . '\'s canvas account. You will need to get that updated before ' . $student->getPreferredFirstName() . ' will be able to login to canvas again.');
        } else {
            core_notify::addMessage($student->getPreferredFirstName() . '\'s email address has been updated.');
        }
    } else {
        core_notify::addError('Unable to update ' . $student->getPreferredFirstName() . '\'s email address.  Please enter a valid email address that is not already in use.');
    }
    //header('Location: /student/' . $student->getSlug());
    core_loader::redirect('/home');
    exit();
}

if (($year = mth_schoolYear::getOpenReg())
    && !mth_schedule::get($student, $year)
    && $student->canSubmitSchedule()
) {
    mth_schedule::create($student, $year);
}

cms_page::setPageTitle('Student Tools');
cms_page::setPageContent('');
cms_page::setPageContent('', 'Small Print', cms_content::TYPE_LIMITED_HTML);



core_loader::printHeader('student');

?>
<div class="page">
    <?= core_loader::printBreadCrumb('window'); ?>
    <div class="page-content container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 id="page-title" class="student-page-title card-title mb-0"><?= $student ?></h3>
            </div>
            <div class="card-block">
                <?php if ($student->getEmail()) : ?>
                    <script>
                        function updateEmail(email) {
                            updateEmail.email = email;
                            global_confirm('Are you sure you want to set the student\'s email to <b>' + email + '</b>?',
                                function() {
                                    window.location = '?updateEmail=' + encodeURIComponent(updateEmail.email);
                                },
                                'Yes',
                                'Back');
                        }
                    </script>
                    <div id="change-email" style="display: none">
                        <p>
                            <label for="email">Email:</label>
                            <input type="email" id="email" value="<?= $student->getEmail() ?>">
                        </p>
                        <p style="text-align: right">
                            <input type="button" onclick="updateEmail($('#email').val());" value="Update"> &nbsp;
                            <input type="button" onclick="global_popup_close('change-email')" value="Cancel">
                        </p>
                    </div>
                <?php endif; ?>
                <ul class="mth_student-details-list">
                    <?php if ($student->getEmail()) : ?>
                        <li><b><?= $student->getEmail() ?></b> (<a onclick="global_popup('change-email')">change</a>)</li>
                    <?php endif; ?>
                    <li>Current Grade: <?= $student->getGradeLevel() ?></li>
                    <?php if (($school = $student->getSOEname(mth_schoolYear::getPrevious()))) : ?>
                        <li><?= mth_schoolYear::getPrevious() ?> School of Enrollment: <?= $school ?></li>
                    <?php endif; ?>
                    <?php if (($school = $student->getSOEname(mth_schoolYear::getCurrent()))) : ?>
                        <li><?= mth_schoolYear::getCurrent() ?> School of Enrollment: <?= $school ?></li>
                    <?php endif; ?>
                    <li>SPED: <?= $student->specialEd(true) ?></li>
                    <li>
                        Diploma-seeking:
                        <?= $student->getGradeLevelValue() < 9 ? 'N/A' : ($student->diplomaSeeking() ? 'Yes' : 'No') ?>
                    </li>
                    <li>
                        SAGE Opt-out (<?= mth_schoolYear::getCurrent() ?>):
                        <?php if ($student->getGradeLevel(mth_schoolYear::getCurrent()) > 2 && ($student->isPendingOrActive())) : ?>
                            <?= mth_testOptOut::getByStudent($student, mth_schoolYear::getCurrent()) ? 'Yes' : 'No' ?>
                        <?php else : ?>
                            N/A
                        <?php endif; ?>
                    </li>
                    <?php if ($student->isActive() && ($nextYear = mth_schoolYear::getNext()) && ($status = $student->getStatus($nextYear))) : ?>
                        <li>
                            <?= $nextYear ?>:
                            <?php
                                switch ($status) {
                                    case mth_student::STATUS_ACTIVE:
                                    case mth_student::STATUS_PENDING:
                                        echo 'Enrolled';
                                        break;
                                    default:
                                        echo mth_student::statusLabel($status);
                                        break;
                                }
                                ?>
                        </li>
                    <?php endif; ?>
                </ul>
                <?= cms_page::getDefaultPageMainContent() ?>
                <div class="student-tool-list">

                    <?php if (($schedule = mth_schedule::eachOfStudent($student->getID()))) : ?>
                        <div id="schedule-link">
                            <a href="/student/<?= $student->getSlug() ?>/schedule/<?= $schedule->schoolYear() ?>">Schedule</a>
                            <ul>
                                <?php do { ?>
                                    <li>
                                        <a href="/student/<?= $student->getSlug() ?>/schedule/<?= $schedule->schoolYear() ?>"><?= $schedule->schoolYear() ?></a>
                                    </li>
                                <?php } while ($schedule = mth_schedule::eachOfStudent($student->getID())); ?>

                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php $nextYear = mth_schoolYear::getNext() ?>
                    <?php if ((($student->getStatus() || ($nextYear && $student->getStatus($nextYear)))
                            && ($packet = mth_packet::getStudentPacket($student))
                            && $packet->getDateAccepted())
                        || (!$student->getStatus()
                            && ($nextYear)
                            && !$student->getStatus($nextYear)
                            && ($app = mth_application::getStudentApplication($student))
                            && $app->isAccepted())
                    ) : ?>
                        <a href="/student/<?= $student->getSlug() ?>/packet">Enrollment Packet</a>
                    <?php endif; ?>

                    <div style="clear: both;"></div>
                    <p>
                        <small>
                            <?= cms_page::getDefaultPageContent('Small Print', cms_content::TYPE_LIMITED_HTML); ?>
                        </small>

                    </p>
                    <p>
                        <?php
                        if (!core_notify::hasNotifications()) :
                            foreach (mth_student_notifications::getStudentNotifications($student) as $notification) :
                                if (substr($notification, 8, -1) == substr(mth_student_notifications::APPLICANT, 3, -1)) :
                                    ?>
                                    <div class="alert alert-alt alert-danger">
                                        <b style="color:#ff0000"><?= $notification ?></b>
                                    </div>
                                <?php
                                        elseif ($notification != mth_student_notifications::NONE) :
                                            ?>
                                    <li><?= $notification ?></li>
                        <?php
                                endif;
                            endforeach;
                        endif;
                        ?>
                    </p>
                    <a class="btn btn-primary btn-round" href="/">Back</a>
                </div>
            </div>

        </div>
    </div>
</div>
<?php

core_loader::printFooter('student');
