<?php

use mth\yoda\courses;
use mth\yoda\assessment;
use mth\yoda\studentassessment;
use mth\yoda\messages;

mth_user::isParent() || core_secure::loadLogin();

$host  =  $_SERVER['HTTP_HOST'];
$domain = strstr($host, '.');
$YEAR = req_get::bool('y') ? mth_schoolYear::getByStartYear(req_get::int('y')) : mth_schoolYear::getCurrent();
$homeroom = new courses();
$assesment = new assessment();
$messages = new messages();

// if ($domain == '.techtrepacademy.com') {
//     core_loader::redirect('/_/user/grades-messages-public');
// }
if (req_get::bool('current_log')) {
    $response = '';
    if ($student_assessment = studentassessment::getCurrentWeekLog($YEAR->getID(), req_get::int('current_log'))) {
        $response = $student_assessment->isSubmitted() ? 'submitted' : '';
    }

    echo $response;
    exit;
}

if (req_get::bool('pdf')) {
    if (($student = mth_student::getByStudentID(req_get::int('student'))) && $student->isEditable()) {
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="UNOFFICIAL_Progress_Report.pdf"');
        echo mth_views_homeroom::getPDFcontent($student, $YEAR);
        exit();
    }
}

if (req_get::bool('llpdf')) {
  if (($student = mth_student::getByStudentID(req_get::int('student'))) && $student->isEditable()) {
    header('Content-type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $student->getFirstName() . '-' . $student->getLastName() . '-' . $YEAR . '-Learning-Logs.pdf"');
    echo mth_views_learninglog::getStudentLogsPDFView($student, $assesment, $YEAR);
    exit();
  }
}

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Homerrom');
cms_page::setPageContent('');
core_loader::printHeader('student');

$parent = mth_parent::getByUser();
$STUDENTS = $parent->getStudents();


$active_student_id = isset($_GET['student']) ? $_GET['student'] : null;

$active_schedule = null;
$student_count = 0;
$duecount = 0;
$assesment_grade = 0;
$first_sem_grade = 0;
$second_sem_grade = 0;
$hideplaceholder = !core_config::isProduction() || time() >= strtotime(date_format(date_create("2018-08-27"), "Y/m/d"));
$viewonly = $parent->isObserver();
$active_people = [];
$fil = null;

if (req_get::is_set('f')) {
    $fil = 1;
}

$learninglog_settings = core_setting::get('learninglogs', 'advance');
$allow_learninglog_without_approval = $learninglog_settings->getValue();

?>
<style>
    .yoda-control {
        float: right;
    }

    .yoda-control:hover {
        text-decoration: underline;
    }

    .dataTables_info {
        z-index: 9;
    }

    .panel-block {
        min-height: 326px;
    }

    .vendor-panel {
        cursor: pointer;
    }

    a.logstatus {
        color: #2196f3 !important;
    }

    a.Submitted_status,
    a.Resubmitted_status {
        color: #28a745 !important;
    }

    a.ResubmitNeeded_status {
        color: red !important;
    }

    .old-message {
        display: none;
    }
    .feedback-text a {
        color: #2196f3 !important;
    }
</style>
<div class="page">
    <div class="page-header page-header-bordered mb-0">
        <h1 class="page-title"><?= cms_page::getDefaultPageTitleContent() ?></h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active"><?= cms_page::getDefaultPageTitleContent() ?></li>
        </ol>
        <div class="page-header-actions">
            <a class="btn btn-secondary btn-round" href="/">Close</a>
        </div>
    </div>
    <!-- Learning Log Section -->
    <div class="learning_log_container container-collapse">
        <div class="page-header page-header-bordered bg-primary" data-toggle="collapse" aria-hidden="true" href="#learning_log_container">
            <div class="float-right mt-15">
                <i class="icon md-chevron-down icon-collapse profile-child-control"></i>
            </div>
            <h3 class="text-white"><i class="fa fa-book"></i> Learning Logs</h3>
        </div>
        <div class="page-content container-fluid collapse info-collapse show" id="learning_log_container">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <select class="form-control" onchange="location.href=this.value">
                            <?php while ($sy = mth_schoolYear::limit(mth_schoolYear::getCurrent())) : ?>
                                <option <?= $sy->getStartYear() == $YEAR->getStartYear() ? 'SELECTED' : '' ?> value="?y=<?= $sy->getStartYear() ?><?= req_get::bool('student') ? '&student=' . req_get::int('student') : '' ?>">
                                    School Year <?= $sy ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="pb-10"><i>Click your child's name below to access their Learning Logs</i></div>
                    <div class="list-group list-group-bordered">
                        <?php foreach ($STUDENTS as $student_id => $student) : ?>
                            <?php
                                $schedule = mth_schedule::get($student, $YEAR);
                                $allowed = false;
                                if ($allow_learninglog_without_approval) {
                                    if (
                                        $schedule && ($schedule->isAccepted() ||
                                            $schedule->isPending() ||
                                            $schedule->isSubmited() ||
                                            $schedule->isResubmitted() ||
                                            $schedule->isUpdatesRequired() ||
                                            $schedule->isPendingUnlock() ||
                                            $schedule->isToChange())
                                    ) {
                                        $allowed = true;
                                    }
                                } else {
                                    if (
                                        $schedule && ($schedule->isAccepted() || $schedule->isPending())
                                    ) {
                                        $allowed = true;
                                    }
                                }
                                if ($allowed) :
                                    $is_pending = $allow_learninglog_without_approval ? false : $schedule->isPending();
                                    // $is_pending =  $schedule->isPending() && !$schedule->is_unlocked_for_second_sem();
                                    ?>
                                <?php
                                        if ($active_student_id && $active_student_id == $student_id) {
                                            $active_schedule = $schedule;
                                        }
                                        $active_people[] = $student->getPersonID();
                                        ?>

                                <a class="list-group-item <?= $is_pending ? 'disabled' : '' ?> <?= $active_student_id && $active_student_id == $student_id ? 'active' : ''; ?>" href="<?= $is_pending ? '#' : "?student=$student_id&y={$YEAR->getStartYear()}" ?>">
                                    <?= $student->getName() ?>
                                    <span title="Submitted Current Log" id="current_log_submitted_<?= $student->getPersonID(); ?>" class="text-success" style="display:none"><i class="fa fa-check"></i></span>
                                    <span title="Not Submitted Current Log" id="current_log_unsubmitted_<?= $student->getPersonID(); ?>" class="text-danger" style="display:none"><i class="fa fa-times"></i></span>
                                    <?php if ($is_pending) : ?>
                                        (Schedule Pending)
                                    <?php else : ?>
                                        <?php if (!$viewonly) : ?>
                                            <small class="yoda-control" data-slug="/student/<?= $student->getSlug() ?>/account">
                                                <?= $student->userAccount() ? '(Edit Account)' : '(Create optional Account)' ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </a>
                                <?php $student_count++; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="card">
                        <div class="card-block">
                            <!-- <span class="text-success"><i class="fa fa-check"></i> = Submitted</span>  -->
                            <!-- /<span class="text-danger"><i class="fa fa-times"></i> = Not Submitted</span> -->
                            A green check mark (<span class="text-success"><i class="fa fa-check"></i></span>) next to the student's name means the <b>current week</b>'s log has been submitted.
                        </div>
                    </div>
                </div>
                <?php
                $student_homeroom = courses::getStudentHomeroom($active_student_id, $YEAR);
                if ($hideplaceholder || ($student_homeroom && $student_homeroom->getName() == 'Homeroom - Pilot')) : ?>
                    <?php
                        $selected_student = mth_student::getByStudentID($active_student_id, true);
                        $allowed = false;
                        if ($allow_learninglog_without_approval) {
                            if (
                                $student_homeroom && ($schedule = mth_schedule::get($selected_student, $YEAR)) && ($schedule->isAccepted() ||
                                    $schedule->isPending() ||
                                    $schedule->isSubmited() ||
                                    $schedule->isResubmitted() ||
                                    $schedule->isUpdatesRequired() ||
                                    $schedule->isPendingUnlock() ||
                                    $schedule->isToChange())
                            ) {
                                $allowed = true;
                            }
                        } else {
                            if (
                                $student_homeroom && ($schedule = mth_schedule::get($selected_student, $YEAR)) && $schedule->isAcceptedOnly()
                            ) {
                                $allowed = true;
                            }
                        }

                        if ($allowed) :
                            if (!$selected_student->isEditable()) {
                                core_notify::addError('Student Not Found');
                                header('Location: /home');
                                exit();
                            }

                            $logs = $assesment->getLearningsLogsAssissments($selected_student, $YEAR);
                            $latest_message = $messages->getAllByHomeroom($selected_student->getPersonID(), $student_homeroom->getCourseId());
                            $assesment_grade = $student_homeroom->getGrade();
                            $first_sem_grade = $student_homeroom->getGrade(1);
                            $second_sem_grade = $student_homeroom->getGrade(2);
                            $passed = assessment::isPassing($assesment_grade);
                            $first_sem_passed = assessment::isPassing($first_sem_grade);
                            $second_sem_passed = assessment::isPassing($second_sem_grade);
                            $teacher = $student_homeroom->getTeacher();
                            ?>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-primary">
                                    <a class="float-right" href="https://docs.google.com/forms/d/e/1FAIpQLScg9rChLr_L4sEH5WQywtDd5Du9LLHrI7-cSkvd75bqTldjIA/viewform?usp=sf_link" target="_blank" title="Feedback"><i style="vertical-align: super;">Feedback?</i> <i class="fa fa-inbox" style="font-size:30px"></i></a>
                                    <h3 class="card-title" style="color:#fff"><?= $student_homeroom->getName() ?></h3>
                                    <p class="card-subtitle">
                                        <?= $YEAR; ?>
                                        <?= $teacher ? '(' . $teacher->getName() . ' | <a  href="mailto:' . $teacher->getEmail() . '">' . $teacher->getEmail() . '</a>)' : '' ?>
                                    </p>

                                </div>
                                <div class="card-block">
                                    <div class="d-flex flex-row justify-content-between">
                                        <h4>Grade Summary:
                                            <?php if (is_null($assesment_grade)) : ?>
                                                N/A
                                            <?php else : ?>
                                                <span class="badge badge-lg badge-round <?= $passed ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $assesment_grade ?>%
                                                </span>
                                                <span>
                                                    <?= $passed ? '- Pass' : '- Fail' ?>
                                                </span>
                                            <?php endif; ?>
                                            <span>
                                                <a target="_blank" href="?student=<?= $selected_student->getID() ?>&pdf=1&y=<?= $YEAR->getStartYear() ?>" title="Report Card"><img class="icon-img" src="<?= core_config::getThemeURI() ?>/assets/photos/pdf.png"></a>
                                            </span>
                                        </h4>
                                        <h4>
                                            Learning Logs <a target="_blank" href="?student=<?= $selected_student->getID() ?>&llpdf=1&y=<?= $YEAR->getStartYear() ?>" title="Learning Logs"><img class="icon-img" src="<?= core_config::getThemeURI() ?>/assets/photos/pdf.png"></a>
                                        </h4>
                                    </div>
                                    <?php if ($YEAR->getFirstSemLearningLogsClose() != $YEAR->getLogSubmissionClose()) { ?>
                                        <div class="">
                                            <h5>1st Semester:
                                                <?php if (is_null($first_sem_grade)) : ?>
                                                    N/A
                                                <?php else : ?>
                                                    <span class="badge badge-lg badge-round <?= $first_sem_passed ? 'badge-success' : 'badge-danger' ?>">
                                                        <?= $first_sem_grade ?>%
                                                    </span>
                                                    <span>
                                                        <?= $first_sem_passed ? '- Pass' : '- Fail' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h5>
                                        </div>
                                        <div class="">
                                            <h5>2nd Semester:
                                                <?php if (is_null($second_sem_grade)) : ?>
                                                    N/A
                                                <?php else : ?>
                                                    <span class="badge badge-lg badge-round <?= $second_sem_passed ? 'badge-success' : 'badge-danger' ?>">
                                                        <?= $second_sem_grade ?>%
                                                    </span>
                                                    <span>
                                                        <?= $second_sem_passed ? '- Pass' : '- Fail' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h5>
                                        </div>
                                    <?php } ?>
                                    <div class="">
                                        <h4>Recent Messages</h4>
                                        <div class="checkbox-custom checkbox-primary">
                                            <input type="checkbox" id="togglemessage">
                                            <label>Show All</label>
                                        </div>
                                        <?php $mcount = 0;
                                        $teacherPerson = mth_person::getByUserId($student_homeroom->getTeacherUserID());
                                        $teacherUser = core_user::getUserById($student_homeroom->getTeacherUserID());
                                        $teacherAvatar = $teacherUser && $teacherUser->getAvatar()
                                            ? $teacherUser->getAvatar()
                                            : (core_config::getThemeURI() . '/assets/portraits/default.png');
                                        foreach ($latest_message as $msg) : ?>
                                            <?php if ($msg->getContent() != 'null' && !is_null($msg->getContent())) : ?>
                                                <div data-count="<?= $mcount ?>" class="alert alert-alt alert-success <?= $mcount > 1 ? 'old-message' : '' ?>" role="alert">
                                                    <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                                                                <span aria-hidden="true">×</span>
                                                                                            </button> -->
                                                    <small class="float-right alert-link">
                                                        <?= $msg->getDate('m/d/Y  h:i A') ?>
                                                    </small>
                                                    <div class="row mb-10 align-items-center">
                                                        <a class="avatar avatar-lg avatar-cont ml--10" style="height:50px;background-image:url(<?= $teacherAvatar ?>)">
                                                        </a>
                                                        <div class="ml-10">
                                                            <?php if (($log = $msg->getLearningLog()) && ($assesment = $log->getAssessment())) :?>
                                                            <b><?=$assesment->getTitle()?></b>
                                                            <br/>
                                                            By <span class="blue-500">
                                                                <?=$teacherPerson->getName()?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="feedback-text"><?= '"'. str_replace(['<p>', '</p>'], '', $msg->getContent()).'"'?></div>
                                                <?php $mcount++; ?>
                                                </div>
                                            <?php endif;
                                        endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <div class="checkbox-custom checkbox-primary" style="display:inline;margin-right:20px;">
                                        <input type="checkbox" id="hidesubmitted" <?= $fil ? 'CHECKED' : '' ?>>
                                        <label for="hidesubmitted">Hide Submitted Logs</label>
                                    </div>
                                    <div class="checkbox-custom checkbox-primary" style="display:inline">
                                        <input type="checkbox" id="hidegraded">
                                        <label for="hidegraded">Hide Graded Logs</label>
                                    </div>
                                </div>
                                <div class="card-block pl-0 pr-0">
                                    <table class="table responsive" id="logstable">
                                        <thead>
                                            <th>Learning Log</th>
                                            <th>Status</th>
                                            <th>Due</th>
                                            <th>Grade</th>
                                        </thead>
                                        <tbody>
                                            <?php
                                                    $gradesum = 0;
                                                    $assessmentcount = 0;
                                                    foreach ($logs as $key => $log) :
                                                        $assessmentcount++;
                                                        $grade = $log->getGrade() != null ? $log->getGrade() : null;
                                                        $status = $log->person_id && $log->getStatus() ? $log->getStatus() : 'Not Submitted';
                                                        $issubmitted = $log->person_id &&  $log->isSubmitted() ? true : false;
                                                        $draft = $log->isDraft() ? ' (Draft)' : '';
                                                        $isexcused = $log->isExcused() ? true : false;
                                                        $isna = $log->isNA() ? true : false;
                                                        $is_resubmit_needed = $log->isReset() ? true : false;
                                                        $txt_status  = $status;

                                                        $is_graded = $grade != null;
                                                        $excempt_graded = $is_graded || $isexcused || $isna;
                                                        $grade_str =   $isexcused || $isna ? 'N/A' : ($is_graded ? $grade . '%' : '');

                                                        if (!$isexcused && $issubmitted) {
                                                            if ($grade != null) {
                                                                $txt_status = 'Graded';
                                                            } else {
                                                                $txt_status = $status . ' - ' . $log->getSubmittedDate('j M Y \a\t h:i A');
                                                            }
                                                        }

                                                        $status_string = $txt_status . $draft;
                                                        ?>
                                                <tr class="<?= $issubmitted ? 'll_submitted' : 'll_unsubmitted' ?><?= $excempt_graded && !$is_resubmit_needed ? ' ll_graded' : '' ?>">
                                                    <td>
                                                        <?= $log->getTitle() ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($log->isEditable()) : ?>
                                                            <a class="logstatus <?= str_replace(' ', '', $status) ?>_status" onclick="global_popup_iframe('mth_student_learning_logs','/_/user/learning-logs?student=<?= $active_student_id ?>&log=<?= $log->getID() ?>&y=<?= $YEAR->getStartYear() ?>');">
                                                                <?= $status_string ?>
                                                            </a>
                                                        <?php else : ?>
                                                            <?= $status_string ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $log->getDeadline('j M Y'); ?></td>
                                                    <td><?= $grade_str ?></td>
                                                </tr>
                                            <?php
                                                    endforeach;
                                                    ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php if (!is_null($active_schedule)) {
                                        display_tech_courses($active_schedule, $YEAR);
                                    } ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title" style="color:#fff">Homeroom</h3>
                                <p class="card-subtitle">
                                    <?= $YEAR ?>
                                </p>
                            </div>
                            <div class="card-block">
                                <div class="alert alert-info">Weekly Learning Logs will go live here by August 27</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <!-- END Learning Log Section -->
    <!-- Unlimited Resource Section-->
    <div class="unlimited_resource_container container-collapse">
        <div class="page-header page-header-bordered bg-primary" data-toggle="collapse" aria-hidden="true" href="#unlimited_resource_container">
            <div class="float-right mt-15">
                <i class="icon md-chevron-right icon-collapse profile-child-control"></i>
            </div>
            <h3 class="text-white">Homeroom Resources</h3>
        </div>
        <div class="page-content container-fluid collapse info-collapse" id="unlimited_resource_container">
            <!-- Homeroom resource section -->
            <section class="mb-50">
                <div class="row">
                    <?php while ($resource = mth_resource_settings::unlimitedResources()) : ?>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-block">
                                    <div class="text-center pb-10">
                                        <?php if ($resource->image()) : ?>
                                            <img src="<?= $resource->getBanner() ?>" class="img-fluid">
                                        <?php else : ?>
                                            <h4><?= $resource->name() ?></h4>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?= $resource->content(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
            <!-- END Homeroom resource section -->
        </div>
    </div>
    <!-- END Unlimited Resource Section -->
    <!-- Optional Resource Section-->
    <div class="optional_resource_container container-collapse">
        <div class="page-header page-header-bordered bg-primary" data-toggle="collapse" aria-hidden="true" href="#optional_resource_container">
            <div class="float-right mt-15">
                <i class="icon md-chevron-right icon-collapse profile-child-control"></i>
            </div>
            <h3 class="text-white">Optional Homeroom Resources</h3>
        </div>
        <div class="page-content container-fluid collapse info-collapse" id="optional_resource_container">
            <!-- Homeroom resource section -->
            <section class="mb-50">
                <div class="row">
                    <?php while ($oresource = mth_resource_settings::optionalResources(false, null, true)) : ?>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-block">
                                    <div class="text-center pb-10">
                                        <?php if ($oresource->image()) : ?>
                                            <img src="<?= $oresource->getBanner() ?>" class="img-fluid">
                                        <?php else : ?>
                                            <h4><?= $oresource->name() ?></h4>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?= $oresource->content(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="text-center">
                    <a class="btn btn-round btn-pink" href="/forms/resource">
                        Request Optional Homeroom Resources
                    </a>
                </div>
            </section>
            <!-- END Homeroom resource section -->
        </div>
    </div>
    <!-- END Optional Resource Section -->
</div>
<?php

function display_tech_courses($active_schedule, $YEAR)
{
    $tech_courses = mth_canvas_enrollment::getScheduleEnrollments($active_schedule);
    $canvas_url = core_setting::get('URL', 'Canvas');
    if (!empty($tech_courses)) :
        foreach ($tech_courses as $course) :
            $mth_course = $course->canvas_course()->mth_course();
            $teacher = $course->canvas_course()->teacher();
            ?>
            <div class="card techcourses" id="course_<?= $course->id() ?>" data-id="<?= $course->id() ?>">
                <div class="card-header bg-primary">
                    <h3 class="card-title" style="color:#fff"><?= $mth_course->title() ?></h3>
                    <p class="card-subtitle">
                        <?= $YEAR ?>
                        <?= $teacher ? '( Tech Mentor: ' . $teacher . ' )' : '' ?>
                    </p>
                </div>
                <div class="card-block">
                    <div class="alert" style="background:#279fb9;color:#fff">
                        Use the Inbox feature in your student's <a href="<?= $canvas_url ?>/conversations" target="_blank" style="color:#fff;font-weight:bold;text-decoration:underline">Canvas</a> account to send a message to the Tech Mentor.&nbsp;
                        <div style="display:inline-block;padding:1px 2px;background:#279fb9">
                            <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px;fill: #fff;position: relative;top: 4px;" version="1.1" x="0" y="0" viewBox="0 0 280 280" enable-background="new 0 0 280 280" xml:space="preserve">
                                <path d="M91.72,120.75h96.56V104.65H91.72Zm0,48.28h80.47V152.94H91.72Zm0-96.56h80.47V56.37H91.72Zm160.94,34.88H228.52V10.78h-177v96.56H27.34A24.17,24.17,0,0,0,3.2,131.48V244.14a24.17,24.17,0,0,0,24.14,24.14H252.66a24.17,24.17,0,0,0,24.14-24.14V131.48A24.17,24.17,0,0,0,252.66,107.34Zm0,16.09a8.06,8.06,0,0,1,8,8v51.77l-32.19,19.31V123.44ZM67.58,203.91v-177H212.42v177ZM27.34,123.44H51.48v79.13L19.29,183.26V131.48A8.06,8.06,0,0,1,27.34,123.44ZM252.66,252.19H27.34a8.06,8.06,0,0,1-8-8V202l30,18H230.75l30-18v42.12A8.06,8.06,0,0,1,252.66,252.19Z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="">
                        <h4>Grade Summary:
                            <span class="badge badge-lg badge-round badgegrade">
                                <span class="gradesummary">
                                    ...
                                </span>
                            </span>
                            <span class="gradestatus">
                            </span>
                        </h4>
                    </div>
                    <div>
                        <h4>Recent Messages</h4>
                        <div class="recent-message"></div>
                    </div>
                </div>
            </div>
<?php
        endforeach;
    endif;
}

core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('student');
?>
<script>
    var active_people = <?= json_encode($active_people) ?>;
    var selected_year = <?= $YEAR->getID() ?>;
    var sid = 0;
    $(function() {

        function getCurrentLog() {
            if (active_people[sid] != undefined) {

                $.ajax({
                    url: '?current_log=' + active_people[sid] + '&year=' + selected_year,
                    type: 'GET',
                    success: function(response) {
                        if (response != '') {
                            var id = '#current_log_' + response + '_' + active_people[sid];
                            $(id).fadeIn();
                        }

                        sid += 1;
                        getCurrentLog();
                    }
                });
            }
        }

        function _getHideSubmitted() {
            var hide_submitted = localStorage.getItem('hide_submitted') ?
                localStorage.getItem('hide_submitted') : 0;
            return Boolean(hide_submitted * 1);
        }

        function _getHideGraded() {
            var hide_graded = localStorage.getItem('hide_graded') ?
                localStorage.getItem('hide_graded') : 0;
            return Boolean(hide_graded * 1);
        }

        dttable = $('#logstable').DataTable({
            columnDefs: [{
                type: 'dateNonStandard',
                targets: 2
            }],
            "bPaginate": true,
            "aaSorting": [
                [2, 'desc']
            ],
            "pageLength": 10
        });

        function show_all() {
            $.fn.dataTable.ext.search.pop();
            dttable.draw();
        }

        function show_rows() {
            var hide_submitted = $('#hidesubmitted').is(':checked');
            var hide_graded = $('#hidegraded').is(':checked');

            show_all();
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var $tr = $(dttable.row(dataIndex).node());
                    if (hide_submitted) {
                        return $tr.hasClass('ll_unsubmitted');
                    }

                    if (hide_graded) {
                        return !$tr.hasClass('ll_graded');
                    }

                    return true;
                }
            );
            dttable.draw();
        }

        getCurrentLog();

        $('#hidesubmitted').prop('checked', _getHideSubmitted()).change(function() {
            var isChecked = $(this).is(':checked');
            localStorage.setItem('hide_submitted', isChecked ? 1 : 0);
            show_rows();
        });

        $('#hidegraded').prop('checked', _getHideGraded()).change(function() {
            var isChecked = $(this).is(':checked');
            localStorage.setItem('hide_graded', isChecked ? 1 : 0);
            show_rows();
        });

        show_rows();

        $('.yoda-control').click(function(e) {
            var slug = $(this).data('slug');
            global_popup_iframe('yoda_account_popup', slug);
            e.stopPropagation();
            return false;
        });



        $('.techcourses').each(function() {
            var id = $(this).data('id');
            $.ajax({
                url: '/_/user/ajax?getgrade=1&enrollment=' + id,
                dataType: 'JSON',
                success: function(response) {
                    if (response.error == 0) {
                        var $course_cont = $('#course_' + id);
                        var grade = response.data.grade;
                        var ispassing = grade && grade >= 80;

                        $course_cont.find('.gradesummary').text(grade ? grade + '%' : 'N/A');
                        grade && $course_cont.find('.gradestatus').text(ispassing ? ' Pass' : 'Fail');
                        grade && $course_cont.find('.badgegrade').addClass(ispassing ? 'badge-success' : 'badge-danger');

                        response.data.comments.length > 0 && $.each(response.data.comments, function(index, value) {
                            $course_cont.find('.recent-message').append('<div class="alert alert-alt alert-success"><small class="float-right alert-link">' + value.date + '</small>' + value.comment + '</div>');
                        });

                    }

                }
            });
        });

        $('#togglemessage').change(function() {
            if ($(this).is(':checked')) {
                $('.old-message').fadeIn();
            } else {
                $('.old-message').fadeOut();
            }
        });
    });
</script>