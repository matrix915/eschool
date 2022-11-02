<?php

use mth\yoda\courses;
use mth\yoda\assessment;
use mth\yoda\studentassessment;
use mth\yoda\messages;
use mth\yoda\settings;

core_user::getUserLevel() || core_secure::loadLogin();

if (req_get::bool('pdf')) {
    if (($student = mth_student::getByStudentID(req_get::int('student'))) && $student->isEditable()) {
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="UNOFFICIAL_Progress_Report.pdf"');
        echo mth_views_homeroom::getPDFcontent($student, mth_schoolYear::getCurrent());
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
$homeroom = new courses();
$assesment = new assessment();
$messages = new messages();
$duecount = 0;
$assesment_grade = 0;
$showplaceholder = !core_config::isProduction() || time() >= strtotime(date_format(date_create("2018-08-27"), "Y/m/d"));
$viewonly = $parent->isObserver();

$fil = null;

if (req_get::is_set('f')) {
    $fil = 1;
}

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
        min-height: 321px;
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
</style>
<div class="page">
    <?= core_loader::printBreadCrumb('window'); ?>
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-3">
                <div class="card vendor-panel" onclick="window.open('https://www.brainpop.com/', '_blank');">

                    <div class="card-block panel-block">
                        <img class="img-fluid" src="<?= core_config::getThemeURI() ?>/assets/photos/brainpop.png">
                        <hr>
                        <div><b>URL:</b> <a href="https://www.brainpop.com/" target="_blank">https://www.brainpop.com/</a></div>
                        <div><b>URL:</b> <a href="#" onclick="event.preventDefault();window.open('https://jr.brainpop.com/', '_blank');">https://jr.brainpop.com/</a></div>
                        <br>

                        <b>Username:</b> <span>techtrep</span><br>
                        <b>Password:</b> techtrep2018<br>
                    </div>

                </div>
            </div>
            <div class="col-md-3">
                <div class="card vendor-panel" onclick="window.open('https://www.generationgenius.com', '_blank');">
                    <div class="card-block panel-block">
                        <img class="img-fluid" src="<?= core_config::getThemeURI() ?>/assets/photos/generationgenius.png">
                        <hr>
                        <div><b>URL:</b> <a href="https://www.generationgenius.com" target="_blank">https://www.generationgenius.com</a></div>
                        <br>
                        <b>Username:</b> <span>techtrepacademy</span><br>
                        <b>Password:</b> sciencerocks18<br>
                    </div>

                </div>
            </div>
            <div class="col-md-3">
                <div class="card vendor-panel" onclick="window.open('http://app.tangmath.com', '_blank');">
                    <div class="card-block panel-block">
                        <img class="img-fluid" src="<?= core_config::getThemeURI() ?>/assets/photos/tangmath.png">
                        <hr>
                        <div><b>URL:</b> <a href="http://app.tangmath.com" target="_blank">app.tangmath.com</a></div><br>
                        <b>Username:</b> <span>puzzles@techtrepacademy.com</span><br>
                        <br>
                        <span>No password needed</span><br>
                    </div>

                </div>
            </div>
            <div class="col-md-3">
                <div class="card vendor-panel">
                    <div class="card-block panel-block">
                        <img class="img-fluid" src="<?= core_config::getThemeURI() ?>/assets/photos/trepacademy.png">
                        <hr>
                        <div class="text-center">
                            <b>Live Virtual Science Classes</b><br>
                            <div>Access the class schedule, class links and recordings here:</div>
                            <a href="https://drive.google.com/drive/folders/1eUZAIXmxSJTKV3tBqfeJBFJE0Fokj25Y?usp=sharing" target="_blank">Live Virtual Science Classes</a>
                        </div>
                        <br>
                        <div>
                            <a class="btn btn-round btn-pink btn-block" href="/forms/resource">Request Optional Homeroom Resources</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="list-group list-group-bordered">
                    <?php foreach ($STUDENTS as $student_id => $student) : ?>
                        <?php
                        $schedule = mth_schedule::get($student, mth_schoolYear::getCurrent());
                        if ($schedule && ($schedule->isAccepted() || $schedule->isPending())) :
                            $is_pending =  $schedule->isPending();
                            ?>
                            <?php
                            // if (!$active_student_id && $student_count == 0) {
                            //     $active_student_id = $student_id;
                            //     $active_schedule = $schedule;
                            // } elseif ($active_student_id && $active_student_id == $student_id) {
                            //     $active_schedule = $schedule;
                            // }
                            ?>

                            <a class="list-group-item <?= $is_pending ? 'disabled' : '' ?> <?= $active_student_id && $active_student_id == $student_id ? 'active' : ''; ?>" href="<?= $is_pending ? '#' : "?student=$student_id" ?>">
                                <?= $student->getName() ?>
                                <?php if ($is_pending) : ?>
                                    (Schedule Pending)
                                <?php else : ?>
                                    <?php if (!$viewonly) : ?>
                                        <small class="yoda-control" data-slug="/student/<?= $student->getSlug() ?>/account">
                                            <?= $student->userAccount() ? '(Edit Yoda Account)' : '(Create optional Yoda Account)' ?>
                                        </small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </a>
                            <?php $student_count++; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $student_homeroom = courses::getStudentHomeroom($active_student_id, mth_schoolYear::getCurrent());
            if ($showplaceholder || ($student_homeroom && $student_homeroom->getName() == 'Homeroom - Pilot')) : ?>
                <?php

                if ($student_homeroom) :
                    $selected_student = mth_student::getByStudentID($active_student_id);

                    if (!$selected_student->isEditable()) {
                        core_notify::addError('Student Not Found');
                        header('Location: /home');
                        exit();
                    }


                    $logs = $assesment->getStudentLearningLogs($selected_student);
                    $latest_message = $messages->getMessageByPersonId($selected_student->getPersonID());
                    $assesment_grade = $student_homeroom->getStudentHomeroomGrade();
                    $passed = assessment::isPassing($assesment_grade);
                    $teacher = $student_homeroom->getTeacher();
                    ?>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title" style="color:#fff"><?= $student_homeroom->getName() ?></h3>
                                <p class="card-subtitle">
                                    <?= mth_schoolYear::getCurrent(); ?>
                                    <?= $teacher ? '(' . $teacher->getName() . ' | <a  href="mailto:' . $teacher->getEmail() . '">' . $teacher->getEmail() . '</a>)' : '' ?>
                                </p>

                            </div>
                            <div class="card-block">
                                <div class="">
                                    <h4>Grade Summary:
                                        <?php if (is_null($assesment_grade)) : ?>
                                            NA
                                        <?php else : ?>
                                            <span class="badge badge-lg badge-round <?= $passed ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $assesment_grade ?>%
                                            </span>
                                            <span>
                                                <?= $passed ? '- Pass' : '- Fail' ?>
                                            </span>
                                        <?php endif; ?>
                                        <a target="_blank" href="?student=<?= $selected_student->getID() ?>&pdf=1" title="Report Card"><img class="icon-img" src="<?= core_config::getThemeURI() ?>/assets/photos/pdf.png"></a>
                                    </h4>
                                </div>
                                <div class="">
                                    <h4>Recent Messages</h4>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" id="togglemessage" data-person="<?= $selected_student->getPersonID(); ?>">
                                        <label>Show All</label>
                                    </div>
                                    <?php $mcount = 0;
                                    foreach ($latest_message as $msg) : ?>
                                        <?php if ($msg->getContent() != 'null' && !is_null($msg->getContent())) : ?>
                                            <div data-count="<?= $mcount ?>" class="alert alert-alt alert-success <?= $mcount > 1 ? 'old-message' : '' ?>" role="alert">
                                                <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                                            <span aria-hidden="true">×</span>
                                                                        </button> -->
                                                <small class="float-right alert-link">
                                                    <?= $msg->getDate('m/d/Y  h:i A') ?>
                                                </small>
                                                <?php
                                                if (($log = $msg->getLearningLog()) && ($assesment = $log->getAssessment())) {
                                                    echo '<b>' . $assesment->getTitle() . '</b>';
                                                }
                                                ?>
                                                <?= '"'. str_replace(['<p>', '</p>'], '', $msg->getContent()).'"'?>
                                                <?php $mcount++; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" id="hidesubmitted" <?= $fil ? 'CHECKED' : '' ?>>
                                    <label for="hidesubmitted">Hide Submitted Logs</label>
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
                                            $student_assessment = studentassessment::get($log->getID(), $selected_student->getPersonID());
                                            $grade = $student_assessment ? $student_assessment->getGrade() : null;
                                            $status = $student_assessment ? $student_assessment->getStatus() : 'Not Submitted';
                                            $issubmitted =  $student_assessment ? $student_assessment->isSubmitted() : false;
                                            $draft =  $student_assessment && $student_assessment->isDraft() ? ' (Draft)' : '';
                                            $isexcused = $student_assessment && $student_assessment->isExcused();
                                            $isna = $student_assessment && $student_assessment->isNA();
                                            $txt_status  = $status;

                                            $grade_str = $isexcused || $isna ? 'N/A' : ($grade != null ? $grade . '%' : '');

                                            if (!$isexcused && $issubmitted) {
                                                if ($grade) {
                                                    $txt_status = 'Graded';
                                                } else {
                                                    $txt_status = $status . ' - ' . $student_assessment->getSubmittedDate('j M Y \a\t h:i A');
                                                }
                                            }

                                            $status_string = $txt_status . $draft;

                                            ?>
                                            <tr class="<?= $issubmitted ? 'll_submitted' : 'll_unsubmitted' ?>">
                                                <td>
                                                    <?= $log->getTitle() ?>
                                                </td>
                                                <td>
                                                    <?php if ($log->isEditable()) : ?>
                                                        <a class="logstatus <?= str_replace(' ', '', $status) ?>_status" onclick="global_popup_iframe('mth_student_learning_logs','/_/user/learning-logs?student=<?= $active_student_id ?>&log=<?= $log->getID() ?>');">
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
                            display_tech_courses($active_schedule);
                        } ?>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h3 class="card-title" style="color:#fff">Homeroom</h3>
                            <p class="card-subtitle">
                                <?= mth_schoolYear::getCurrent(); ?>
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
<?php

function display_tech_courses($active_schedule)
{
    $tech_courses = mth_canvas_enrollment::getScheduleEnrollments($active_schedule);
    if (!empty($tech_courses)) :
        foreach ($tech_courses as $course) :
            $mth_course = $course->canvas_course()->mth_course();
            $teacher = $course->canvas_course()->teacher();
            ?>
            <div class="card techcourses" id="course_<?= $course->id() ?>" data-id="<?= $course->id() ?>">
                <div class="card-header bg-primary">
                    <h3 class="card-title" style="color:#fff"><?= $mth_course->title() ?></h3>
                    <p class="card-subtitle">
                        <?= mth_schoolYear::getCurrent(); ?>
                        <?= $teacher ? '( Tech Mentor: ' . $teacher . ' )' : '' ?>
                    </p>
                </div>
                <div class="card-block">
                    <div class="alert alert-alt alert-primary">
                        Message Tech Mentor through Canvas Inbox&nbsp;
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
    $(function() {
        function _getHideSubmitted() {
            var hide_submitted = localStorage.getItem('hide_submitted') ?
                localStorage.getItem('hide_submitted') : 0;
            return Boolean(hide_submitted * 1);
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

            show_all();
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var $tr = $(dttable.row(dataIndex).node());
                    return hide_submitted ? $tr.hasClass('ll_unsubmitted') : true;
                }
            );
            dttable.draw();
        }

        $('#hidesubmitted').prop('checked', _getHideSubmitted()).change(function() {
            var isChecked = $(this).is(':checked');
            localStorage.setItem('hide_submitted', isChecked ? 1 : 0);
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

                        $course_cont.find('.gradesummary').text(grade ? grade + '%' : 'NA');
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