<?php

use mth\yoda\answers;
use mth\yoda\studentassessment;
use mth\yoda\messages;
use mth\yoda\plgs;
use mth\yoda\courses;
use mth\yoda\assessment;

core_user::getUserLevel() || core_secure::loadLogin();
$year_id = mth_schoolYear::getCurrent()->getID();
if(req_get::bool('select_plg'))
{
    $grade_level = req_post::txt('grade_level');
    $subject = req_post::txt('subject');
    $school_year_id = mth_schoolYear::getCurrent()->getID();
    $plgs = array_map('lowercaseAndCharacterLimit', $_POST['plgs']);
    $previous_plgs = array_key_exists('previous_plgs', $_POST) ? $_POST['previous_plgs'] : [];

    foreach(plgs::get($grade_level, $subject, $school_year_id) as $plg)
    {
        $selected = in_array(trim(lowercaseAndCharacterLimit($plg->getName())), $plgs) ? 'CHECKED' : '';
        $answered = !$selected && array_key_exists($grade_level, $previous_plgs) && in_array(lowercaseAndCharacterLimit($plg->getName()), array_map('lowercaseAndCharacterLimit', $previous_plgs[$grade_level])) ? ' answered' : '';
        echo '<div class="checkbox-custom checkbox-primary' . $answered . '"><input type="checkbox" ' . $selected . ' class="plgname"  name="plgs[]" value="' . $plg->getName() . '"><label>' . $plg->getName() . '</label></div>';
    }
    exit();
}

if(req_get::bool('approve_plg'))
{
    $answer = answers::getUsingId(req_post::int('answer_id'));
    $data = [
        'answer' => $_POST['plgs'],
        'grade_level' => req_post::txt('grade_level')
    ];
    $answer->set('data', json_encode($data));
    echo $answer->save() ? 1 : 0;
    exit();
}

if(req_get::bool('saveTeacherNotes'))
{
    $studentId = req_post::int('student_id');
    $year_id = req_post::int('year_id');
    $student = mth_student::getByStudentID($studentId);
    echo $student->getTeacherNotes($year_id,req_post::multi_txt('teacher_notes'));
    exit();
}

$log = null;
$single_grading = req_get::bool('single');

if(req_get::bool('id'))
{
    $log = studentassessment::getById(req_get::int('id'));
    $_SESSION['skiplog'] = [];
} elseif(req_get::bool('next'))
{
    $current = req_get::int('current');
    $skipped_logs = &$_SESSION['skiplog'];
    if(!$skipped_logs)
    {
        $skipped_logs = [];
    }

    if(!in_array($current, $skipped_logs))
    {
        $skipped_logs[] = $current;
    }

    $log = studentassessment::getNextUngraded(req_get::int('next'), $skipped_logs);
    if(!$log)
    {
        $skipped_logs = [];
        exit('<!DOCTYPE html><html><script>
          parent.global_popup_iframe_close("yoda_assessment_edit");
          parent.updateActiveLog(' . req_get::int('next') . ');
         </script></html>');
    }
} elseif(req_get::bool('form'))
{
    core_loader::formSubmitable($_GET['form']) || die('Form is not submittable');

    $stlog = studentassessment::getById(req_post::int('log_id'));
    if(req_post::int('type') == 2)
    {
        $stlog->set('reset', studentassessment::RESET);
    }
    if(req_post::txt('grade') != 'EX')
    {
        $stlog->set('grade', req_post::int('grade'));
        if($stlog->isExcused())
        {
            $stlog->set('excused', null);
        }
    } else
    {
        $stlog->set('excused', 1);
    }


    $msg = new messages();
    $msg->setInsert('message_title', 'Feedback for Assignment');
    $msg->setInsert('message_content', req_post::html('feedback'));
    if($person = core_user::getCurrentUser()->getPerson())
    {
        $msg->setInsert('person_id', $person->getPersonID());
    }

    if($student = $stlog->getPerson())
    {
        $msg->setInsert('to_person_id', $student->getPersonID());
    }

    if($msg->save())
    {
        $stlog->set('message_id', $msg->getID());
    }

    if($stlog->save())
    {
        core_notify::addMessage($student->getPreferredFirstName() . "'s learning log graded.");
    } else
    {
        core_notify::addMessage('Unable to grade ' . $student->getPreferredFirstName() . "'s learning log.");
    }

    if(!$single_grading)
    {
        core_loader::redirect("?next=" . $stlog->getAssessmentId() . '&current=' . $stlog->getID());
    }
    exit('<!DOCTYPE html><html><script>
     parent.global_popup_iframe_close("yoda_assessment_edit");
     parent.updateActiveLog();
    </script></html>');
} else
{
    exit('Unable to load learning log.');
}

if(!($student = $log->getPerson()))
{
    exit('Student not found.');
}
/** @var $teacherAssessment \mth\yoda\assessment */
if(!($teacherAssessment = $log->getAssessment()))
{
    exit('Assessment not found.');
}

$schedule = null;

if(($course = $teacherAssessment->getCourse()) && ($year = $course->getSchoolYear()))
{
    $schedule = $student->schedule($year);
    $year_id= $course->getSchoolYearId();
}

$past_special_answers = [];
foreach(answers::getPastSelectedSpecial($student->getID()) as $answer)
{
    if($answer)
    {
        $data = json_decode($answer);
        if(isset($data->grade_level) || !$data->grade_level)
        {
            if(!isset($past_special_answers[$data->grade_level]))
            {
                $past_special_answers[$data->grade_level] = [];
            }

            if(!is_null($data->answer))
            {
                $past_special_answers[$data->grade_level] = array_unique(array_merge($past_special_answers[$data->grade_level], $data->answer));
            }
        }
    }
}

function lowercaseAndCharacterLimit($str)
{
    return trim(strtolower(substr($str, 0, 50)));
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<div class="log-header">
    <style>
        .answered.checkbox-custom label::before {
            background-color: #ccc;
            border: 1px solid #9E9E9E;
        }

        .grade-info {
            margin-top: 0;
        }

        .borderless-ta {
            border: none;
            outline: none;
            resize: none;
        }
    </style>
    <span class="float-right row mt-15">
         <?php
         /** @var courses $student_homeroom */
         $student_homeroom = courses::getStudentHomeroom($student->getID(), $year);
         $previousStudentAssessments = studentassessment::getPreviousAssessments($teacherAssessment, $course, $student->getPersonID());
         $nextStudentAssessments = studentassessment::getNextAssessments($teacherAssessment, $course, $student->getPersonID());
         $assesment_grade = $student_homeroom->getGrade();
         $first_sem_grade = $student_homeroom->getGrade(1);
         $second_sem_grade = $student_homeroom->getGrade(2);
         $passed = assessment::isPassing($assesment_grade);
         $first_sem_passed = assessment::isPassing($first_sem_grade);
         $second_sem_passed = assessment::isPassing($second_sem_grade);
         $excusedCount = studentassessment::getExcusedCount($student->getID(), $year);
         $studentuser = core_user::getUserById($student->getUserID());
         $studentAvatar = $studentuser && $studentuser->getAvatar() ? $studentuser->getAvatar() : (core_config::getThemeURI() . '/assets/portraits/default.png');
         ?>
        <div class="mr-10" style="text-align: left;">
        <?php if($year->getFirstSemLearningLogsClose() != $year->getLogSubmissionClose()) :?>
            <div class="">
                <h5 class="grade-info">1st Semester:
                    <?php if(is_null($first_sem_grade)) : ?>
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
                <h5 class="grade-info">2nd Semester:
                    <?php if(is_null($second_sem_grade)) : ?>
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
        <?php else :?>
            <h5 class="grade-info">
                Homeroom Grade:
                <?php if(is_null($assesment_grade)) : ?>
                    N/A
                <?php else : ?>
                <span class="badge badge-lg badge-round <?= $passed ? 'badge-success' : 'badge-danger' ?>">
                            <?= $assesment_grade ?>%
                        </span>
                    <span>
                            <?= $passed ? '- Pass' : '- Fail' ?>
                        </span>
                <?php endif; ?>
            </h5>
        <?php endif;?>
            # of Excused Logs: <?= $excusedCount ?>
        </div>
         <div>
              <?php if(!$single_grading): ?>
                  <button type="button" class="btn btn-round btn-info" id="skip-log" title="Grade Later"
                          onclick="location.href='?next=<?= $log->getAssessmentId() ?>&current=<?= $log->getID() ?>'">
                   <i class="fa fa-history"></i>
              </button>
              <?php endif; ?>
              <button type="button" class="btn btn-round btn-warning" id="reset-log" title="Reset">
                   <i class="fa fa-refresh"></i>
              </button>

              <button type="button" class="btn btn-round btn-success" id="grade-log" title="Grade">
                   <i class="fa fa-check"></i>
              </button>
              <button type="button" class="btn btn-round btn-default" onclick="closeLog()" title="Close">
                   <i class="fa fa-close"></i>
              </button>
         </div>
     </span>
    <h4 class="d-flex justify-content-start align-items-center">
        <a class="avatar avatar-lg avatar-cont ml-10"
           style="height:50px;background-image:url(<?= $studentAvatar ?>)">
        </a>
        <div class="ml-10 mt-5">
            <span class="blue-500"><?= $student ?>'s</span> Learning Logs
            <h5 class="mt-10 d-flex justify-content-start">
                <?= $student->getGradeLevel(true) . ' (' . $student->getAge() . ')' ?>
            </h5>
        </div>
    </h4>
</div>
<div class="row" style="margin-top: 120px; margin-left: -30px; margin-right: 0;">
    <div class="col-md-2">
        <?php if($nextStudentAssessments): ?>
            <div>
                <h4>Next Learning Log</h4>
                <?php foreach($nextStudentAssessments as $tempAssessment) :
                    /**
                     * @var studentassessment $tempAssessment
                     * @var studentassessment $nextStudentAssessment
                     * @var assessment $nextAssessment
                     */
                    $nextStudentAssessment = studentassessment::get($tempAssessment->getAssessmentId(), $student->getPersonID());
                    $nextAssessment = $nextStudentAssessment->getAssessment();
                    $hasPercent = ($nextStudentAssessment->getGrade() || $nextStudentAssessment->getGrade() === 0) ? '- ' . (int) $nextStudentAssessment->getGrade() . '% ' : '';
                    switch($nextStudentAssessment->getStatus())
                    {
                        case studentassessment::STATUS_NA:
                        case studentassessment::STATUS_EXCUSED:
                            $gradeStatement = '- ' . $nextStudentAssessment->getStatus();
                            break;
                        case studentassessment::STATUS_RESET:
                            $gradeStatement = $hasPercent;
                            $gradeStatement .= '- Needs Resubmit';
                            break;
                        default:
                            $gradeStatement = $hasPercent;
                            break;
                    }?>
                    <a class="list-group-item" href="<?= '?id=' . $nextStudentAssessment->getID() . '&' ?>">
                        <?= $nextAssessment->getTitle() ?> <?= $gradeStatement ?>
                        <?= $nextStudentAssessment->isLate() ? ' <span class="badge badge-danger">Late</span>' : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif;
        if($previousStudentAssessments): ?>
            <div>
                <h4>Previous Learning Logs</h4>
                <?php foreach($previousStudentAssessments as $tempAssessment) :
                    /** @var studentassessment $tempAssessment
                     * @var studentassessment $previousStudentAssessment
                     * @var assessment $previousAssessment
                     */
                    $previousStudentAssessment = studentassessment::get($tempAssessment->getAssessmentId(), $student->getPersonID());
                    $previousAssessment = $previousStudentAssessment->getAssessment();
                    switch($previousStudentAssessment->getStatus())
                    {
                        case studentassessment::STATUS_NA:
                        case studentassessment::STATUS_EXCUSED:
                            $gradeStatement = '- ' . $previousStudentAssessment->getStatus();
                            break;
                        case studentassessment::STATUS_RESET:
                            $gradeStatement = (($previousStudentAssessment->getGrade() || $previousStudentAssessment->getGrade() === 0) ? '- ' . (int) $previousStudentAssessment->getGrade() . '% ' : '');
                            $gradeStatement .= '- Needs Resubmit';
                            break;
                        default:
                            $gradeStatement = (($previousStudentAssessment->getGrade() || $previousStudentAssessment->getGrade() === 0) ? '- ' . (int) $previousStudentAssessment->getGrade() . '%' : '');
                            break;
                    }?>
                    <a class="list-group-item" href="<?= '?id=' . $previousStudentAssessment->getID() . '&' ?>">
                        <?= $previousAssessment->getTitle() ?> <?= $gradeStatement ?>
                        <?= $previousStudentAssessment->isLate() ? ' <span class="badge badge-danger">Late</span>' : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-10">
        <form name="logsform" id="logform" method="post"
          action='?form=<?= uniqid('yoda-grade-learning-log') ?><?= $single_grading ? '&single=1' : '' ?>'>
            <div class="row">
                <div class="col-md-5">
                    <h4 class="mb-0"><span class="blue-500"><?= $teacherAssessment->getTitle() ?></span></h4>
                </div>
                <div class="col-md-7 text-right">
                    <ul class="list-unstyled list-inline mb-0">
                        <li class="list-inline-item">
                            <select name="grade" class="form-control" required>
                                <option value="">Select Grade</option>
                                <option value="0">0%</option>
                                <option value="50">50%</option>
                                <option value="60">60%</option>
                                <option value="70">70%</option>
                                <option value="80">80%</option>
                                <option value="90">90%</option>
                                <option value="100">100%</option>
                                <option value="EX">EX</option>
                            </select>
                        </li>
                        <?php if($log->getGrade() !== NULL): ?>
                            <li class="list-inline-item">
                                <div style="font-weight:bold">Grade: <?= $log->getGrade() ?>%</div>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <label id="grade-error" class="error" for="grade"></label>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="type" id="act_type" value="1">
                    <input type="hidden" name="assessment_id" value="<?= $log->getAssessmentId() ?>">
                    <input type="hidden" name="log_id" value="<?= $log->getID() ?>">
                    <div class="panel panel-primary">
                        <div class="panel-body">
                            <div class="pb-20">
                                <span class="text-success"> <?= $log->isReset() ? 'Reset' : 'Submitted' ?> at <b><?= $log->getSubmittedDate('j F Y - g:i a'); ?></b></span>
                                <?php if($log->isLate()): ?>
                                    / <span class="badge badge-danger">Late</span>
                                <?php endif; ?>
                                <?php if($log->isReset()): ?>
                                    <span class="badge badge-danger">Needs Resubmit</span>
                                <?php endif; ?>
                            </div>
                            <?php
                            mth_views_homeroom::getSubmissionView($log->getAssessmentId(), $log, null, false, $year);
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-bordered panel-primary parent-link-collapse"
                         data-toggle="collapse"
                         aria-hidden="true"
                         aria-controls="sched-panels"
                         href="#sched-panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">Schedule</h3>
                        </div>
                        <div class="panel-body p-0">
                            <div class="collapse info-collapse" id="sched-panel">
                                <?php if($schedule): ?>
                                    <table class="table table-stripped">
                                        <thead>
                                        <th>
                                            Period
                                        </th>
                                        <th>
                                            Course
                                        </th>
                                        </thead>
                                        <tbody>
                                        <?php while($period = mth_period::each($schedule->student_grade_level())) : ?>
                                            <?php
                                            if(!($schedulPeriod = mth_schedule_period::get($schedule, $period, true)))
                                            {
                                                continue;
                                            }
                                            ?>
                                            <tr>
                                                <td> <?= $schedulPeriod->period() ?></td>
                                                <td>
                                                    <?php if($schedulPeriod->subject()) : ?>
                                                        <?= $schedulPeriod->courseName() ?>
                                                    <?php else : ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-bordered panel-primary">
                        <div class="panel-heading"
                             data-toggle="collapse"
                             aria-hidden="true"
                             href="#teacher-notes-panel"
                             aria-controls="teacher-notes-panel">
                            <h3 class="panel-title">Student Notes</h3>
                        </div>
                        <div class="collapse info-collapse" id="teacher-notes-panel">
                            <div class="panel-body pt-15">
                                <label id="teacher-notes-error" class="error" for="teacher-notes"></label>
                                <textarea class="borderless-ta w-full" style="border-color: #c7c6c6" rows="5"
                                          id="teacher-notes" name="teacher-notes" readonly
                                ><?= $student->getTeacherNotes($year_id) ?></textarea>
                                <textarea style="display: none;" id="teacher-notes-original"></textarea>
                                <?php if((core_user::isUserTeacher())) : ?>
                                    <div class="mt-15">
                                        <?php if($year_id ==  mth_schoolYear::getCurrent()->getID()){ ?>
                                            <button type="button" class="btn btn-primary btn-round float-right cancel"
                                                    id="teacher-notes-edit"
                                            ><?= (empty($student->getTeacherNotes($year_id)) ? 'Add' : 'Edit') ?></button>
                                        <?php } ?>
                                        <button style="display: none;" type="button"
                                                class="btn btn-primary btn-round float-right cancel ml-5"
                                                id="teacher-notes-save">
                                            Save
                                        </button>
                                        <button style="display: none;" type="button"
                                                class="btn btn-secondary btn-round float-right cancel"
                                                id="teacher-notes-cancel">
                                            Cancel
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-bordered panel-primary edit-feedbacks">
                        <div class="panel-heading" data-toggle="panel-collapse">
                            <h3 class="panel-title">Feedback(s)</h3>
                        </div>
                        <?php if($feedbacks = messages::getAllFromAssessment($student->getPersonID(), $log->getAssessmentId())): ?>
                            <div class="panel-body">
                                <ul class="list-group list-group-bordered">
                                    <?php foreach($feedbacks as $f): ?>
                                        <li class="list-group-item"><?= $f->getContent() ?>
                                            <small class="float-right"
                                                   style="color:#c7c6c6"><?= $f->getDate('j M Y \a\t h:i A') ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <div class="panel-body">
                            <label id="feedback-error" class="error" for="feedback"></label>
                            <textarea id="feedback" name="feedback"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="editplg" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">

        <div class="modal-content">
            <div class="modal-header">
                <select class="plg_grade form-control" name="question">
                    <option></option>
                    <?php foreach(plgs::distictGradeLevels($year) as $grade_level): ?>
                        <option value="<?= $grade_level ?>"><?= $grade_level ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-body plg_container">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-round approve-plg">Save</button>
                <button type="button" class="btn btn-secondary cancel btn-round" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php
core_loader::includejQueryValidate();
core_loader::printFooter();
?>
<script src="//cdn.ckeditor.com/4.10.1/full/ckeditor.js"></script>
<script>
    CKEDITOR.config.removePlugins = "undo,bidi,iframe,print,format,pastefromword,pastetext,about,image,forms,youtube,iframe,print,stylescombo,flash,newpage,save,preview,templates";
    CKEDITOR.config.removeButtons = "Subscript,Superscript";
    CKEDITOR.config.disableNativeSpellChecker = false;
    CKEDITOR.config.autoParagraph = true;
    CKEDITOR.replace( 'feedback',
        {
            enterMode: CKEDITOR.ENTER_BR,
        }
    )

    function closeLog() {
        parent.global_popup_iframe_close('yoda_assessment_edit');
        if (parent.updateActiveLog != undefined) {
            parent.updateActiveLog(<?=$teacherAssessment->getID()?>);
        }
    }

    function gradeLog(action) {
        if (CKEDITOR.instances['feedback'].getData() == '') {
            $('#feedback-error').fadeIn().text('Please leave a feedback first.');
        } else {
            $('#act_type').val(action);
            $('#logform').submit();
        }
    }

    $(function () {
        var grade_level = null;
        var subject = null;
        var plgs = [];
        var previous_plgs = <?php echo json_encode($past_special_answers) ?>;
        var answer_id = null;

        $('.plg-edit-btn').click(function () {
            var $this = $(this);
            var data = $this.data();
            grade_level = data.gradelevel;
            subject = data.subject;
            plgs = data.plgs.split('|');
            answer_id = data.answerid;

            $('.plg_container').html('');
            $("#editplg").modal("show");

            if (plgs.length > 0 && plgs[0] != '') {
                global_waiting();
                changePLG();
            }
        });

        function changePLG() {
            $.ajax({
                'url': '?select_plg=1',
                type: 'POST',
                data: {
                    grade_level: grade_level,
                    subject: subject,
                    plgs: plgs,
                    previous_plgs: previous_plgs,
                },
                success: function (html_response) {
                    $('.plg_grade').val(grade_level);
                    $('.plg_container').html(html_response);
                    global_waiting_hide();
                }
            });
        }

        $('.plg_grade').change(function () {
            grade_level = $(this).val();
            global_waiting();
            changePLG();
        });

        $('.approve-plg').click(function () {
            var plgs = $('.plg_container .plgname:checked').serialize();
            global_waiting();

            $.ajax({
                'url': '?approve_plg=1',
                type: 'POST',
                data: plgs + '&answer_id=' + answer_id + '&grade_level=' + grade_level,
                success: function (response) {
                    if (response == 1) {
                        global_waiting_hide();
                        location.reload();
                    } else {
                        toastr.error('Error occur when saving changes');
                    }

                },
                error: function () {
                    toastr.error('Error occur when saving changes');
                }
            });
        });

        $('#reset-log').click(function () {
            swal({
                    title: "",
                    text: "Are you sure you want to reset this student’s log? It will send it back to be re-submitted.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-warning",
                    confirmButtonText: "Yes, Reset",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function () {
                    gradeLog('2');
                });
        });

        $('#logform').validate({
            rules: {
                grade: {required: true},
                feedback: {ckeditor_required: true}
                    },
            messages: {
                grade: {
                    required: "Please select a grade first."
                            }
                    },
        });

        $('#grade-log').click(function () {
            gradeLog('1');
        });

        $('.answer-container a[href]').attr('target', '_blank');

        $('#teacher-notes-edit').click(function (event) {
            $('#teacher-notes').attr("readonly", false);
            $('#teacher-notes').removeClass("borderless-ta");
            $('#teacher-notes-original').val($('#teacher-notes').val())
            $('#teacher-notes-save,#teacher-notes-cancel').show();
            $(this).hide();
            event.stopPropagation();
        });

        $('#teacher-notes-cancel').click(function (event) {
            $('#teacher-notes').val($('#teacher-notes-original').val())
            $('#teacher-notes').attr("readonly", true);
            $('#teacher-notes').addClass("borderless-ta");
            $('#teacher-notes-original').val('');
            $('#teacher-notes-edit').show();
            $('#teacher-notes-save,#teacher-notes-cancel').hide();
            event.stopPropagation();
        });

        $('#teacher-notes-save').click(function (event) {
            global_waiting();
            $.ajax({
                'url': '?saveTeacherNotes=1',
                type: 'POST',
                data: {
                    teacher_notes: $('#teacher-notes').val(),
                    student_id: <?= $student->getID() ?>,
                    year_id:<?= $year_id?>
                },
                success: function (response) {
                    if(!response) {
                        swal('', 'Unable to save the student notes. Please try again.', 'error');
                    }
                    $('#teacher-notes').attr("readonly", true);
                    $('#teacher-notes').addClass("borderless-ta");
                    $('#teacher-notes-original').val('');
                    $('#teacher-notes-edit').show();
                    if($('#teacher-notes').val() == '' && $('#teacher-notes-edit').html() != 'Add') {
                        $('#teacher-notes-edit').html('Add');
                    } else if ($('#teacher-notes-edit').html() != 'Edit') {
                        $('#teacher-notes-edit').html('Edit');
                    }
                    $('#teacher-notes-save,#teacher-notes-cancel').hide();
                },
            })
            global_waiting_hide();
            event.stopPropagation();
        });
    });
</script>