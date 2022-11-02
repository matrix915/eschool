<?php

use mth\yoda\courses;
use mth\yoda\assessment;

$fromsched = req_get::is_set('fromschedule');

if (req_get::is_set('reimbursed')) {
    ($period = mth_schedule_period::getByID(req_get::int('period'))) || die(0);
    $period->reimbursed(req_get::float('reimbursed'));
    die(1);
}

($schedule = mth_schedule::getByID(req_get::int('schedule'))) || die('Schedule not found');
($student = $schedule->student()) || die('Schedule student missing');
($parent = $student->getParent()) || die('Student\'s parent missing');

$schoolYear = $schedule->schoolYear();
$scheduleID = $schedule->id();

if (req_get::bool('getHomeroomGrade')) {
    if (($enrollment = mth_canvas_enrollment::getBySchedulePeriod($schedule->getPeriod(1)))
        && $enrollment->id()
    ) {
        exit($enrollment->grade(true) . '%');
    }
    exit('...');
}
if (req_get::bool('getHomeroomZeroCount')) {
    if (($enrollment = mth_canvas_enrollment::getBySchedulePeriod($schedule->getPeriod(1)))
        && $enrollment->id()
    ) {
        exit((string) $enrollment->zeroCount(true));
    }
    exit('...');
}

if (req_get::bool('setStatus')) {
    $schedule->setStatus(req_get::int('setStatus'));
    if ($schedule->save()) {
        core_notify::addMessage('The schedule status has been set');
    } else {
        core_notify::addError('Unable to change the status!');
    }
    core_loader::redirect('?schedule=' . $scheduleID . ($fromsched ? '&fromschedule=1' : ''));
}

if (req_get::bool('approve')) {
    if ($schedule->approve()) {
        core_notify::addMessage('The schedule has been approved');
    } else {
        core_notify::addError('Unable to approve the schedule!');
    }
    core_loader::redirect('?schedule=' . $scheduleID . ($fromsched ? '&fromschedule=1' : ''));
}

if (req_get::bool('removeChangeReqs')) {
    $schedule->removeChangeRequirements();
    core_loader::redirect('?schedule=' . $scheduleID . ($fromsched ? '&fromschedule=1' : ''));
}

if (req_get::bool('enable2ndSem')) {
    if ($schedule->enable2ndSemChanges()) {
        core_notify::addMessage('Schedule set to allow 2nd semester changes');
    } else {
        core_notify::addError('Unable to modify schedule');
    }
    core_loader::redirect('?schedule=' . $scheduleID . ($fromsched ? '&fromschedule=1' : ''));
}

if (req_get::bool('delete')) {
    if ($schedule->delete()) {
        core_notify::addMessage('Schedule deleted.');
        core_loader::reloadParent();
    } else {
        core_notify::addError('Unable to delete the schedule!');
    }
    core_loader::redirect('?schedule=' . $scheduleID . ($fromsched ? '&fromschedule=1' : ''));
}

if (req_get::bool('pdf')) {
    header('Content-type: application/pdf');
    echo mth_views_schedules::getPDFcontent($schedule);
    exit();
}

if (req_get::bool('sendToDropbox')) {

    if (mth_views_schedules::sendToDropbox($schedule)) {
        core_notify::addMessage('Schedule successfully sent to dropbox.');
    } else {
        core_notify::addError('Error sending schedule to dropbox.');
    }

    core_loader::redirect('?schedule=' . $scheduleID . ($fromsched ? '&fromschedule=1' : ''));
    exit();
}

if (req_get::bool('pdfreportcard')) {
    header('Content-type: application/pdf');
    header('Content-Disposition: attachment; filename="UNOFFICIAL_Progress_Report.pdf"');
    echo mth_views_homeroom::getPDFcontent($student, $schoolYear);
    exit();
}

if (req_get::bool('llpdf')) {
  $assessment = new assessment();
  if ($student->isEditable()) {
    header('Content-type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $student->getFirstName() . '-' . $student->getLastName() . '-' . $schoolYear . '-Learning-Logs.pdf"');
    echo mth_views_learninglog::getStudentLogsPDFView($student, $assessment, $schoolYear);
    exit();
  }
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<script>
    var fromschedule = <?= $fromsched ? 'true' : 'false'; ?>;

    function closeSchedule() {
        if ($(top.document.body).is('.admin_schedules') && fromschedule) {
            top.updateSchedule(<?= $scheduleID ?>);
        }
        top.global_popup_iframe_close('mth_schedule-edit-<?= $scheduleID ?>');
    }

    function editSchedule(schedule_id) {
        if ($(top.document.body).is('.admin_schedules')) {
            top.updateSchedule(<?= $scheduleID ?>);
        }
        viewSchedule(schedule_id);
    }

    function viewSchedule(schedule_id) {
        top.global_popup_iframe('mth_schedule-edit-' + schedule_id, '/_/admin/schedules/schedule?schedule=' + schedule_id);
    }


    function deleteSchedule() {
        swal({
                title: "",
                text: "Are you sure you want to completely delete this schedule? This action cannot be undone.",
                type: "warning",
                showCancelButton: !0,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: !1,
                closeOnCancel: true
            },
            function() {
                location.href = '?schedule=<?= $scheduleID ?>&delete=1<?= $fromsched ? '&fromschedule=1' : '' ?>';
            });
    }

    function show_change_info() {
        swal("", "Changing the schedule status with this tool will not notify the parent of the change in status and will not unlock specific periods, use it only to correct schedule status if it is wrong.", "info");
    }

    function changeStat($this) {
        location.href = '?schedule=<?= $scheduleID ?>&setStatus=' + $this.value + '<?= $fromsched ? '&fromschedule=1' : '' ?>';
    }
</script>
<style>
    small,
    th a {
        color: #999;
        text-decoration: none;
    }
</style>
<?php if (!req_get::bool('static')) : ?>
    <button type="button" class="iframe-close btn btn-round btn-secondary" onclick="closeSchedule()">Close</button>
<?php endif; ?>
<div class="card">
    <div class="card-block higlight-links">
        <div class="row">
            <div class="col-md-3">
                <div>
                    <strong><?= $student ?></strong>
                    <?= $schoolYear ?>
                </div>
                <h1 style="margin: 0">Schedule</h1>
                <?php if ($schedule->isAccepted()) : ?>
                    <div style="font-size: 20px; color: green; line-height: 25px; height: 40px;">Approved</div>
                <?php endif; ?>
                <button type="button" class="btn btn-primary btn-round" onclick="window.open(location.origin+'/_/admin/schedules/schedule?schedule=<?= $scheduleID ?>&pdf=1')">View PDF</button>
                <?php
                if ($student->isMidYear($schoolYear)) : ?>
                    <div class="alert alert-danger alert-alt mt-10" id="mid-year-al">
                        <span class="alert-link">Student joined mid-year</span>
                    </div>
                <?php
                endif;
                ?>

                <?php
                if ($student->getAddress()->getState() == "OR") : ?>
                    <div class="alert alert-success alert-alt mt-10" id="oregen-student-flag">
                        <span class="alert-link">Oregon Student</span>
                    </div>
                <?php
                endif;
                ?>                
            </div>
            <div class="col-md-3">
                <h3 style="margin: 0">Student</h3>
                <div>
                    <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?student=<?= $student->getID() ?>')">
                        <?= $student ?></a></div>
                <div><?= $student->getGender() ?></div>
                <div>
                    <?= $student->getGradeLevel(true, false, $schoolYear); ?>
                    <small>(<?= $schoolYear ?>)</small>
                </div>
                <div>Diploma: <?= $student->diplomaSeeking() ? 'Yes' : 'No'; ?></div>
                <div><?= $student->getSchoolOfEnrollment(false, $schoolYear) ?></div>
                <div>SPED: <?= $student->specialEd(true) ?></div>
            </div>
            <div class="col-md-3">
                <?php if (($scheduleIDs = mth_schedule::getStudentScheduleIDs($student))) : ?>
                    <h3 style="margin: 0">Student's Schedules</h3>
                    <?php foreach ($scheduleIDs as $yearID => $schedule_id) : ?>
                        <?php if ($scheduleID == $schedule_id) {
                                    continue;
                                } ?>
                        <a onclick="viewSchedule(<?= $schedule_id ?>)" style="display: block;">
                            <?= mth_schoolYear::getByID($yearID) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <h3 style="margin: 0">Parent</h3>
                <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?parent=<?= $parent->getID() ?>')">
                    <?= $parent ?>
                </a>
                <hr>

                <?php if ($enrollment = courses::getStudentHomeroom($student->getID(), $schoolYear)) : ?>
                    <?php $homeroomgrade = $enrollment->getGrade(); ?>
                    <div>
                        Homeroom Grade: <span id="homeroomGradeHolder"><?= is_null($homeroomgrade) ? 'NA' : $homeroomgrade . '%' ?></span>
                        <?php if ($schoolYear->getFirstSemLearningLogsClose() == $schoolYear->getLogSubmissionClose()) : ?>
                            <span>/ # of Zeros: <?= $enrollment->getStudentHomeroomZeros() ?></span>
                        <?php endif; ?>
                        <a target="_blank" href="?schedule=<?= $scheduleID ?>&pdfreportcard=1&y=<?= $schoolYear->getStartYear() ?>" title="Report Card"><img class="icon-img" src="<?= core_config::getThemeURI() ?>/assets/photos/pdf.png"></a>
                    </div>
                    <div>
                            Learning Logs: <a target="_blank" href="?schedule=<?= $scheduleID ?>&llpdf=1&y=<?= $schoolYear->getStartYear() ?>" title="Learning Logs"><img class="icon-img" src="<?= core_config::getThemeURI() ?>/assets/photos/pdf.png"></a>
                    </div>
                    <?php if ($schoolYear->getFirstSemLearningLogsClose() != $schoolYear->getLogSubmissionClose()) : ?>
                        <div>
                            1st Semester: <span><?= $enrollment->getGrade(1) ? $enrollment->getGrade(1) . '%' : 'N/A' ?> / <span id="homeroomZeroCountHolder"># of Zeros: <?= $enrollment->getStudentHomeroomZeros(1) ?></span></span> <br />
                            2nd Semester: <span><?= $enrollment->getGrade(2) ? $enrollment->getGrade(2) . '%' : 'N/A' ?> / <span id="homeroomZeroCountHolder"># of Zeros: <?= $enrollment->getStudentHomeroomZeros(2) ?></span></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div>
                    <a onclick="top.global_popup_iframe('mth_reimbursement-show','/_/admin/reimbursements/family?parent=<?= $student->getParentID() ?>&year=<?= $schoolYear ?>')">
                        <?php $has_reimbursements = false; ?>
                        <?php $reimbursementStatus = [
                            mth_reimbursement::STATUS_SUBMITTED,
                            mth_reimbursement::STATUS_UPDATE,
                            mth_reimbursement::STATUS_RESUBMITTED,
                            mth_reimbursement::STATUS_APPROVED,
                            mth_reimbursement::STATUS_PAID
                        ] ?>
                        <?php if (($reimbursement = mth_reimbursement::each(null, $student, $schoolYear, $reimbursementStatus))) : ?>
                            <?php $has_reimbursements = true; ?><span style="color: red; font-weight: bold;">Reimbursements Submitted</span>
                        <?php endif; ?>
                        <?php if (!$has_reimbursements) : ?>
                            <span style="color: black">No Reimbursements</span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-block p-0">
        <?php mth_views_schedules::scheduleDetails($schedule) ?>
    </div>
</div>
<p>
    <?php if ($schedule->isAccepted()) : ?>
        <?php if ($schedule->canRemoveChangeAbility()) : ?>
            <button type="button" class="btn btn-round btn-warning" onclick="location.href='?schedule=<?= $scheduleID ?>&removeChangeReqs=1'">Remove Change Requirement/Ability</button>
        <?php endif; ?>
        <?php if (!$schedule->isStatus(mth_schedule::STATUS_CHANGE_POST)) : ?>
            <button type="button" class="btn btn-round btn-info" onclick="top.global_popup_iframe('mth_schedule-change','/_/admin/schedules/change?schedule=<?= $scheduleID ?>')">Unlock for Changes</button>
        <?php endif; ?>
        <button type="button" class="btn btn-round btn-primary" onclick="location.href='?schedule=<?= $scheduleID ?>&sendToDropbox=1'">Send to Dropbox</button>
    <?php elseif ($schedule->isPending()) : ?>
        <?php if ($schedule->isStatus(mth_schedule::STATUS_CHANGE)) : ?>
            <button type="button" class="btn btn-round btn-pink" onclick="location.href='?schedule=<?= $scheduleID ?>&removeChangeReqs=1<?= $fromsched ? '&fromschedule=1' : '' ?>'">Remove Change Requirement/Ability</button>
        <?php else : ?>
            <button type="button" class="btn btn-round btn-success" onclick="location.href='?schedule=<?= $scheduleID ?>&approve=1<?= $fromsched ? '&fromschedule=1' : '' ?>'">Approve Schedule</button>
            <button type="button" class="btn btn-round btn-primary" onclick="top.global_popup_iframe('mth_schedule-change','/_/admin/schedules/change?schedule=<?= $scheduleID ?>')">Change Schedule</button>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (
        $schedule->second_sem_change_available()
        && !$schedule->isToChange()
        && $schoolYear->getSecondSemOpen() <= time()
    ) : ?>
        <button type="button" class="btn btn-round btn-secondary" onclick="location.href='?schedule=<?= $scheduleID ?>&enable2ndSem=1<?= $fromsched ? '&fromschedule=1' : '' ?>'">Enable 2nd Semester Updates</button>
    <?php endif; ?>
    <button type="button" onclick="deleteSchedule()" class="btn btn-round btn-danger">Delete</button>
    <span style="float:right">
        Set the schedule status
        (<a onclick="show_change_info();">?</a>):
        <select id="mth_schedule_status_select" onchange="changeStat(this)">
            <?php foreach (mth_schedule::status_options() as $status => $label) : ?>
                <option value="<?= $status ?>" <?= $schedule->isStatus($status) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </span>
</p>
<?php
core_loader::printFooter();
?>