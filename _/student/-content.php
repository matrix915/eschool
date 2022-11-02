<?php
use mth\yoda\courses;
use mth\yoda\messages;
use mth\yoda\assessment;
use mth\yoda\studentassessment;

core_user::getUserLevel() || core_secure::loadLogin();

$YEAR = req_get::bool('y') ? mth_schoolYear::getByStartYear(req_get::int('y')) : mth_schoolYear::getCurrent();

if (req_get::bool('pdf')) {
    if (($student = mth_student::getByStudentID(req_get::int('student'))) && $student->isEditable()) {
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="UNOFFICIAL_Progress_Report.pdf"');
        echo mth_views_homeroom::getPDFcontent($student, $YEAR);
        exit();
    }
}
cms_page::setPageContent('<b>Note: </b>
For students enrolled in a Canvas-based Tech or Entrepreneurship course beginning August 31, 
you can use this 
<a href="https://mytechhigh.instructure.com" rel="noopener noreferrer" target="_blank">quick link</a> 
to access Canvas.', 'Student Page Note', cms_content::TYPE_HTML);
core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Dashboard');
cms_page::setPageContent('');
core_loader::printHeader('student');

$user = core_user::getCurrentUser();
$student = mth_student::getByUserID($user->getID());
$student_homeroom = courses::getStudentHomeroom($student->getID(), $YEAR);
$assesment = new assessment();
$schedule = mth_schedule::get($student, $YEAR);
$logs = $assesment->getStudentLearningLogs($student);
$learninglog_settings = core_setting::get('learninglogs', 'advance');
$allow_learninglog_without_approval = $learninglog_settings->getValue();
?>
<style>
    .old-message {
        display: none;
    }
</style>

<style>
    /**
    * Medium and BELOW
    */
    @media (max-width:1024px) {
        .profile-action {
            padding-top: 30px;
        }
    }

    #recent-message-container img:not([height]),
    #recent-message-container img:not([width]) {
        width: 100% !important;
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

    .feedback-text a {
        color: #2196f3 !important;
    }
</style>
<div class="page parent-profile-page">
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-8 profile-box">
                <div class="card">
                    <a href="/_/user/profile">
                        <div class="cover-photo">
                        </div>
                    </a>
                    <div class="card-block wall-person-info">
                        <a class="avatar bg-white img-bordered person-avatar avatar-cont" href="/_/user/profile">
                        </a>
                        <h2 class="person-name">
                            <a href="/_/user/profile"><?= $student->getName() ?></a>
                        </h2>
                        <div class="card-text">
                            <a class="blue-grey-400"><span><?= $student->getEmail() ? $student->getEmail() : '&nbsp;' ?></span></a>
                        </div>
                        <div class="profile-action">
                            <a class="btn btn-default btn-round  mr-10" href="/_/user/profile">
                                Edit Profile
                            </a>
                            <a class="btn btn-primary btn-round  mr-10" href="?logout=1">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" id="hidesubmitted">
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
                            foreach($logs as $key => $log) :
                                $assessmentcount++;
                                $student_assessment = studentassessment::get($log->getID(), $student->getPersonID());
                                $grade = $student_assessment ? $student_assessment->getGrade() : null;
                                $status = $student_assessment ? $student_assessment->getStatus() : 'Not Submitted';
                                $issubmitted = $student_assessment && $student_assessment->isSubmitted();
                                $draft = $student_assessment && $student_assessment->isDraft() ? ' (Draft)' : '';
                                $isexcused = $student_assessment && $student_assessment->isExcused();
                                $isna = $student_assessment && $student_assessment->isNA();
                                $txt_status = $status;

                                $grade_str = $isexcused || $isna ? 'N/A' : ($grade != null ? $grade . '%' : '');

                                if(!$isexcused && $issubmitted)
                                {
                                    if($grade != null)
                                    {
                                        $txt_status = 'Graded';
                                    } else
                                    {
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
                                        <?php if($schedule && $log->isEditable() && ($allow_learninglog_without_approval || !$schedule->isPending())) : ?>
                                            <a class="logstatus <?= str_replace(' ', '', $status) ?>_status"
                                               onclick="global_popup_iframe('mth_student_learning_logs','/_/user/learning-logs?student=<?= $student->getID() ?>&log=<?= $log->getID() ?>');">
                                                <?= $status_string ?>
                                            </a>
                                        <?php else : ?>
                                            <?= $status_string ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $log->getDeadline('j M Y'); ?>
                                    </td>
                                    <td>
                                        <?= $grade_str ?>
                                    </td>
                                </tr>
                            <?php
                            endforeach;
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4 calendar-box">
                <?php if($student_homeroom) : ?>
                    <?php
                    $messages = new messages();
                    $teacher = $student_homeroom->getTeacher();
                    $latest_message = $messages->getAllByHomeroom($student->getPersonID(), $student_homeroom->getCourseId());
                    $assesment_grade = $student_homeroom->getGrade();
                    $first_sem_grade = $student_homeroom->getGrade(1);
                    $second_sem_grade = $student_homeroom->getGrade(2);
                    $passed = assessment::isPassing($assesment_grade);
                    $first_sem_passed = assessment::isPassing($first_sem_grade);
                    $second_sem_passed = assessment::isPassing($second_sem_grade);
                    ?>
                    <div class="panel panel-primary panel-line">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <?= $student_homeroom->getName() ?>
                            </h3>
                        </div>
                        <div class="panel-body">
                            <?= $YEAR ?>
                            <?= $teacher ? '(' . $teacher->getName() . ' | <a  href="mailto:' . $teacher->getEmail() . '">' . $teacher->getEmail() . '</a>)' : '' ?>
                        </div>
                        <div class="panel-footer" style="padding: 0 30px 0;">
                            <h4 class="mb-0">Grade Summary:
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
                                <a target="_blank"
                                   href="?student=<?= $student->getID() ?>&pdf=1&y=<?= $YEAR->getStartYear() ?>"
                                   title="Report Card"><img class="icon-img"
                                                            src="<?= core_config::getThemeURI() ?>/assets/photos/pdf.png"></a>
                            </h4>
                        </div>
                        <?php if($YEAR->getFirstSemLearningLogsClose() != $YEAR->getLogSubmissionClose()) { ?>
                            <div class="panel-footer" style="padding: 0 30px 0;">
                                <h5 class="mb-0">1st Semester:
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
                            <div class="panel-footer">
                                <h5 class="mb-0">2nd Semester:
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
                        <?php } ?>
                    </div>

                    <div class="panel  panel-bordered panel-warning" id="recent-message-container">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                Recent Messages
                            </h3>
                            <div class="panel-actions panel-actions-keep">
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" id="togglemessage">
                                    <label>Show All</label>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <?php $mcount = 0;
                            $teacherPerson = mth_person::getByUserId($student_homeroom->getTeacherUserID());
                            $teacherUser = core_user::getUserById($student_homeroom->getTeacherUserID());
                            $teacherAvatar = $teacherUser && $teacherUser->getAvatar() ? $teacherUser->getAvatar() : (core_config::getThemeURI() . '/assets/portraits/default.png');
                            foreach($latest_message as $msg) : ?>
                                <?php if($msg->getContent() != 'null' && !is_null($msg->getContent())) : ?>
                                    <div data-count="<?= $mcount ?>"
                                         class="alert alert-alt alert-success <?= $mcount > 4 ? 'old-message' : '' ?>"
                                         role="alert">

                                        <small class="float-right alert-link">
                                            <?= $msg->getDate('m/d/Y  h:i A') ?>
                                        </small>
                                        <div class="row mb-10 align-items-center">
                                            <a class="avatar avatar-lg avatar-cont ml--10"
                                               style="height:50px;background-image:url(<?= $teacherAvatar ?>)">
                                            </a>
                                            <div class="ml-10">
                                                <?php if(($log = $msg->getLearningLog()) && ($assesment = $log->getAssessment())) : ?>
                                                    <b><?= $assesment->getTitle() ?></b>
                                                <br/>
                                                By <span class="blue-500">
                                                    <?= $teacherPerson->getName() ?>
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
	                    <?php else : ?>
                    <div class="alert dark alert-alt alert-warning">No Homeroom</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-30">
            <div class="col">
                <?= cms_page::getDefaultPageContent('Student Page Note', cms_content::TYPE_HTML); ?>
            </div>
        </div>
        <!-- Unlimited Resource Section-->
        <div class="unlimited_resource_container container-collapse">
            <div class="page-header page-header-bordered bg-primary" data-toggle="collapse" aria-hidden="true"
                 href="#unlimited_resource_container">
                <div class="float-right mt-15">
                    <i class="icon md-chevron-right icon-collapse profile-child-control"></i>
                </div>
                <h3 class="text-white">Homeroom Resources</h3>
            </div>
            <div class="page-content container-fluid collapse info-collapse" id="unlimited_resource_container">
                <!-- Homeroom resource section -->
                <section class="mb-50">
                    <div class="row">
                        <?php while($resource = mth_resource_settings::unlimitedResources()) : ?>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-block">
                                        <div class="text-center pb-10">
                                            <?php if($resource->image()) : ?>
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
            <div class="page-header page-header-bordered bg-primary" data-toggle="collapse" aria-hidden="true"
                 href="#optional_resource_container">
                <div class="float-right mt-15">
                    <i class="icon md-chevron-right icon-collapse profile-child-control"></i>
                </div>
                <h3 class="text-white">Optional Homeroom Resources</h3>
            </div>
            <div class="page-content container-fluid collapse info-collapse" id="optional_resource_container">
                <!-- Homeroom resource section -->
                <section class="mb-50">
                    <div class="row">
                        <?php while($oresource = mth_resource_settings::optionalResources(false, null, true)) : ?>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-block">
                                        <div class="text-center pb-10">
                                            <?php if($oresource->image()) : ?>
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
                </section>
                <!-- END Homeroom resource section -->
            </div>
        </div>
        <!-- END Optional Resource Section -->
    </div>
</div>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('student');
?>

<script>
    $(function() {
        var profile_pic = '<?= $user && $user->getAvatar() ?  $user->getAvatar() : (core_config::getThemeURI() . '/assets/portraits/default.png') ?>';
        $('.person-avatar').css('background-image', 'url(' + profile_pic + ')');

        $('#togglemessage').change(function() {
            if ($(this).is(':checked')) {
                $('.old-message').fadeIn();
            } else {
                $('.old-message').fadeOut();
            }
        });

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
    });
</script>