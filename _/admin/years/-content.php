<?php

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || die();

    $year = mth_schoolYear::create(req_post::strtotime('date_begin'));
    $year->set_date_begin(req_post::strtotime('date_begin'));
    $year->set_date_end(req_post::strtotime('date_end'));
    $year->set_date_reg_open(req_post::strtotime('date_reg_open'));
    $year->set_date_reg_close(req_post::strtotime('date_reg_close'));
    $year->set_second_sem_start(req_post::strtotime('second_sem_start'));
    $year->set_second_sem_open(req_post::strtotime('second_sem_open'));
    $year->set_second_sem_close(req_post::strtotime('second_sem_close'));
    $year->set_re_enroll_open(req_post::strtotime('re_enroll_open'));
    $year->set_re_enroll_deadline(req_post::strtotime('re_enroll_deadline'));
    $year->set_re_enroll_notification(req_post::int('re_enroll_notification'));
    $year->set_first_sem_learning_logs_close(req_post::strtotime('first_sem_learning_logs_close'));
    $year->set_log_submission_close(req_post::strtotime('log_submission_close'));
    $year->set_reimburse_open(req_post::strtotime('reimburse_open'));
    $year->set_reimburse_tech_open(req_post::strtotime('reimburse_tech_open'));
    $year->set_reimburse_close(req_post::strtotime('reimburse_close'));
    $year->set_direct_order_open(req_post::strtotime('direct_order_open'));
    $year->set_direct_order_tech_enabled(req_post::bool('direct_order_tech_enabled') ? 1 : 0);
    $year->set_direct_order_tech_open(req_post::strtotime('direct_order_tech_open'));
    $year->set_direct_order_close(req_post::strtotime('direct_order_close'));
    $year->set_mid_year(req_post::bool('midyear_application') ? 1 : 0);
    $year->set_application_close(req_post::strtotime('application_close'));
    $year->set_midyear_application_open(req_post::strtotime('midyear_open'));
    $year->set_midyear_application_close(req_post::strtotime('midyear_close'));

    if (($changes = $year->getChanges()) && ($archives = $year->getArchives())  && core_user::getCurrentUser()) {
        $log = new mth_system_log();
        $log->setNewValue(json_encode($changes), core_setting::TYPE_RAW);
        $log->setOldValue(json_encode($archives), core_setting::TYPE_RAW);
        $log->setType(core_setting::TYPE_RAW);
        $log->setTag('School Year:' . $year);
        $log->setUserId(core_user::getCurrentUser()->getID());
        $log->save();
    }

    if ($year->save()) {
        core_notify::addMessage('School Year Saved');
    } else {
        core_notify::addError('Unable to save school year!');
    }
    header('Location: /_/admin/years');
    exit();
}

core_loader::includejQueryUI();

core_loader::includejQueryValidate();

cms_page::setPageTitle('Manage School Years');
cms_page::setPageContent('');
core_loader::printHeader('admin');

$years = mth_schoolYear::getSchoolYears();
$current = mth_schoolYear::getCurrent();
?>
<script>
    function togglePast() {
        if (togglePast.showPast === undefined) {
            togglePast.showPast = false;
        } else {
            togglePast.showPast = !togglePast.showPast;
        }
        if (togglePast.showPast) {
            $('tr.past').show();
            $('#showPastLink').html('Hide past school years');
        } else {
            $('tr.past').hide();
            $('#showPastLink').html('Show past school years');
        }
    }
    var newYear = ['', '', '', '', '', '', '', '', ''];

    function editYear(id) {
        if (id === 'new') {
            $('#date_begin').val(newYear.date_begin);
            $('#date_end').val(newYear.date_end);
            $('#date_reg_open').val(newYear.date_reg_open);
            $('#date_reg_close').val(newYear.date_reg_close);
            $('#second_sem_start').val(newYear.second_sem_start);
            $('#second_sem_open').val(newYear.second_sem_open);
            $('#second_sem_close').val(newYear.second_sem_close);
            $('#re_enroll_open').val(newYear.re_enroll_open);
            $('#re_enroll_deadline').val(newYear.re_enroll_deadline);
            $('#re_enroll_notification').val(newYear.re_enroll_notification);
            $('#reimburse_open').val(newYear.reimburse_open);
            $('#reimburse_tech_open').val(newYear.reimburse_tech_open);
            $('#reimburse_close').val(newYear.reimburse_close);
            $('#direct_order_open').val(newYear.direct_order_open);
            $('#direct_order_tech_enabled').attr('checked', newYear.direct_order_tech_enabled == 1);
            $('#direct_order_tech_open').val(newYear.direct_order_tech_open);
            $('#direct_order_close').val(newYear.direct_order_close);
            $('#school_year_id').val('');
            $('#log_submission_close').val(newYear.log_submission_close);
            $('#application_close').val(newYear.application_close);
            $('#midyear_open').val(newYear.midyear_open);
            $('#midyear_close').val(newYear.midyear_close);
            $('#first_sem_learning_logs_close').val(newYear.first_sem_learning_logs_close);
        } else {
            var row = $('tr#schoolYear' + id + '');
            $('#date_begin').val(row.find('td.date_begin').html());
            $('#date_end').val(row.find('td.date_end').html());
            $('#date_reg_open').val(row.find('td.date_reg_open').html());
            $('#date_reg_close').val(row.find('td.date_reg_close').html());
            $('#second_sem_start').val(row.find('td.second_sem_start').html());
            $('#second_sem_open').val(row.find('td.second_sem_open').html());
            $('#second_sem_close').val(row.find('td.second_sem_close').html());
            $('#re_enroll_open').val(row.find('td.re_enroll_open').html());
            $('#re_enroll_deadline').val(row.find('td.re_enroll_deadline').html());
            $('#re_enroll_notification').val(row.find('td.re_enroll_notification').html());
            $('#first_sem_learning_logs_close').val(row.find('td.first_sem_learning_logs_close').html());
            $('#reimburse_open').val(row.find('td.reimburse_open').html());
            $('#reimburse_tech_open').val(row.find('td.reimburse_tech_open').html());
            $('#reimburse_close').val(row.find('td.reimburse_close').html());
            $('#direct_order_open').val(row.find('td.direct_order_open').html());
            $('#direct_order_tech_enabled').attr('checked', row.find('td.direct_order_tech_enabled').html() == 'Yes');
            $('#direct_order_tech_open').val(row.find('td.direct_order_tech_open').html());
            $('#direct_order_close').val(row.find('td.direct_order_close').html());
            $('#school_year_id').val(id);
            $('#midyear_application').attr('checked', row.data('midyear') == 1);
            $('#log_submission_close').val(row.find('td.log_submission_close').html());
            $('#application_close').val(row.find('td.application_close').html());
            $('#midyear_open').val(row.find('td.midyear_open').html());
            $('#midyear_close').val(row.find('td.midyear_close').html());
        }
        global_popup('editYear');
    }
    $(function() {
        $('#schoolYearForm input[type="text"]').datepicker({
            minDate: "-20Y",
            maxDate: "+100Y",
            changeMonth: true,
            changeYear: true
        });
        $('#schoolYearForm').validate();
        $('.tr_schoolYear').click(function() {
            editYear(this.id.replace('schoolYear', ''));
        });
    });
</script>
<style>
    .tr_schoolYear:hover td,
    .tr_schoolYear:hover th {
        background: #DDEEFF;
        cursor: pointer;
    }

    body table.formatted th {
        text-align: center;
    }

    #editYear {
        padding: 20px !important;
    }
</style>

<div class="card">
    <div class="card-block p-0 table-responsive">
        <table class="formatted vl table table-bordered mb-0">
            <tr class="bold-text">
                <th style="border-bottom:none"></th>
                <th style="border-bottom:none">Begin</th>
                <th style="border-bottom:none">End</th>
                <th colspan="2" class="hidden-md-down">Schedule</th>
                <th colspan="4" class="hidden-md-down">Direct Orders</th>
                <th colspan="3" class="hidden-md-down">Reimbursements</th>
                <th class="hidden-md-down">2nd Semester</th>
                <th colspan="2" class="hidden-md-down">Intent to Re-Enroll</th>
                <th colspan="2" class="hidden-md-down">Log Submission</th>
                <th class="hidden-md-down">Application</th>
                <th colspan="2" class="hidden-md-down">Mid-year Application</th>
            </tr>
            <tr class="hidden-md-down">
                <th style="border-top:none"></th>
                <th style="border-top:none"></th>
                <th style="border-top:none"></th>
                <th title="Date schedule submission is open"> Open</th>
                <th title="Date schedule submission is closed"> Close</th>
                <th title="Date direct order requests open"> Open</th>
                <th title="Direct Order tech enabled">Tech Enabled</th>
                <th title="Date tech direct order requests open"> Tech Open</th>
                <th title="Date direct order requests close"> Close</th>
                <th title="Date reimbursement requests open"> Open</th>
                <th title="Date tech reimbursement requests open"> Tech</th>
                <th title="Date reimbursement requests close"> Close</th>

                <th>Start</th>
                <!-- <th title="Date schedule submission is open">Open</th>
                <th title="Date schedule submission is closed">Close</th> -->
                <th>Open</th>
                <th>Deadline</th>
                <th>1st Sem Close</th>
                <th>Close</th>
                <th>Close</th>
                <th>Open</th>
                <th>Close</th>
            </tr>
            <?php foreach ($years as $num => $year) : /* @var $year mth_schoolYear */ ?>
                <tr class="tr_schoolYear <?= $current && $year->getDateBegin() >= $current->getDateBegin() ? 'future' : 'past' ?>" id="schoolYear<?= $year->getID() ?>" data-midyear="<?= $year->isMidYearAvailable() ? 1 : 0 ?>">
                    <th><?= $year ?></th>
                    <td class="date_begin"><?= $year->getDateBegin('m/d/Y') ?></td>
                    <td class="date_end"><?= $year->getDateEnd('m/d/Y') ?></td>
                    <td class="date_reg_open  hidden-md-down"><?= $year->getDateRegOpen('m/d/Y') ?></td>
                    <td class="date_reg_close  hidden-md-down"><?= $year->getDateRegClose('m/d/Y') ?></td>
                    <td class="direct_order_open  hidden-md-down"><?= $year->direct_order_open('m/d/Y') ?></td>
                    <td class="direct_order_tech_enabled  hidden-md-down"><?= $year->direct_order_tech_enabled() ? 'Yes' : 'No' ?></td>
                    <td class="direct_order_tech_open  hidden-md-down"><?= $year->direct_order_tech_open('m/d/Y') ?></td>
                    <td class="direct_order_close  hidden-md-down"><?= $year->direct_order_close('m/d/Y') ?></td>
                    <td class="reimburse_open  hidden-md-down"><?= $year->reimburse_open('m/d/Y') ?></td>
                    <td class="reimburse_tech_open  hidden-md-down"><?= $year->reimburse_tech_open('m/d/Y') ?></td>
                    <td class="reimburse_close  hidden-md-down"><?= $year->reimburse_close('m/d/Y') ?></td>
                    <td class="second_sem_start hidden-md-down"><?= $year->getSecondSemStart('m/d/Y') ?></td>
                    <td style="display:none" class="second_sem_open hidden-md-down"><?= $year->getSecondSemOpen('m/d/Y') ?></td>
                    <td style="display:none" class="second_sem_close  hidden-md-down"><?= $year->getSecondSemClose('m/d/Y') ?></td>
                    <td class="re_enroll_open  hidden-md-down"><?= $year->getReEnrollOpen('m/d/Y') ?></td>
                    <td class="re_enroll_deadline  hidden-md-down"><?= $year->getReEnrollDeadline('m/d/Y') ?></td>
                    <td style="display:none" class="re_enroll_notification  hidden-md-down"><?= $year->getReEnrollNotification('m/d/Y') ?></td>
                    <td class="first_sem_learning_logs_close hidden-md-down"><?= $year->getFirstSemLearningLogsClose('m/d/Y') ?></td>
                    <td class="log_submission_close hidden-md-down"><?= $year->getLogSubmissionClose('m/d/Y') ?></td>
                    <td class="application_close hidden-md-down"><?= $year->getApplicationClose('m/d/Y') ?></td>
                    <td class="midyear_open hidden-md-down"><?= $year->getMidyearOpen('m/d/Y') ?></td>
                    <td class="midyear_close hidden-md-down"><?= $year->getMidyearClose('m/d/Y') ?></td>
                </tr>
                <?php
                    if (!$num) {
                        $lastYear = $year;
                    }
                    ?>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="card-footer">
        <button type="button" onclick="editYear('new')" class="btn-round btn btn-primary">+ New School Year</button>
        <a onclick="togglePast()" class="link" id="showPastLink"></a>
    </div>
</div>



<script>
    togglePast();
    <?php
    if (
        !empty($lastYear) && ($newYear = mth_schoolYear::create(
            strtotime('+1 year', $lastYear->getDateBegin())
        ))
    ) : ?>
        newYear = <?= json_encode($newYear->getArr('m/d/Y')) ?>;
    <?php endif; ?>
</script>
<div id="editYear" style="display: none; height: 800px;">
    <form action="?form=<?= uniqid('school_year') ?>" id="schoolYearForm" method="post">
        <input id="school_year_id" name="school_year_id" type="hidden">
        <div class="form-group">
            <label for="date_begin">Begin Date</label>
            <input type="text" name="date_begin" id="date_begin" required class="form-control">
        </div>
        <div class="form-group">
            <label for="date_end">End Date</label>
            <input type="text" name="date_end" id="date_end" required class="form-control">
        </div>
        <div class="form-group">
            <label for="date_reg_open">Schedule Submission Begin Date</label>
            <input type="text" name="date_reg_open" id="date_reg_open" required class="form-control">
        </div>
        <div class="form-group">
            <label for="date_reg_close">Schedule Submission End Date</label>
            <input type="text" name="date_reg_close" id="date_reg_close" required class="form-control">
        </div>
        <div class="form-group">
            <label for="direct_order_open">Direct Order Request Open Date</label>
            <input type="text" name="direct_order_open" id="direct_order_open" required class="form-control">
        </div>
        <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="direct_order_tech_enabled" id="direct_order_tech_enabled">
            <label>Enable Direct Order Tech Allowance</label>
        </div>
        <div class="form-group">
            <label for="direct_order_tech_open">Direct Order Request Tech Open Date</label>
            <input type="text" name="direct_order_tech_open" id="direct_order_tech_open" required class="form-control">
        </div>
        <div class="form-group">
            <label for="direct_order_close">Direct Order Request Close Date</label>
            <input type="text" name="direct_order_close" id="direct_order_close" required class="form-control">
        </div>
        <div class="form-group">
            <label for="reimburse_open">Reimbursement Request Open Date</label>
            <input type="text" name="reimburse_open" id="reimburse_open" required class="form-control">
        </div>
        <div class="form-group">
            <label for="reimburse_tech_open">Reimbursement Request Tech Open Date</label>
            <input type="text" name="reimburse_tech_open" id="reimburse_tech_open" required class="form-control">
        </div>
        <div class="form-group">
            <label for="reimburse_close">Reimbursement Request Close Date</label>
            <input type="text" name="reimburse_close" id="reimburse_close" required class="form-control">
        </div>
        <div class="form-group">
            <label for="second_sem_start">2nd Semester Start Date</label>
            <input type="text" name="second_sem_start" id="second_sem_start" required class="form-control">
        </div>
        <div class="form-group">
            <label for="second_sem_open">2nd Semester Schedule Submission Begin Date</label>
            <input type="text" name="second_sem_open" id="second_sem_open" required class="form-control">
        </div>
        <div class="form-group">
            <label for="second_sem_close">2nd Semester Schedule Submission End Date</label>
            <input type="text" name="second_sem_close" id="second_sem_close" required class="form-control">
        </div>
        <div class="form-group">
            <label for="re_enroll_open">Date Parents are notified they need to submit their Intent to
                Re-enroll</label>
            <input type="text" name="re_enroll_open" id="re_enroll_open" required class="form-control">
        </div>
        <div class="form-group">
            <label for="re_enroll_notification">Intent to Re-enroll Reminder Email</label>
            <input type="number" name="re_enroll_notification" id="re_enroll_notification" required class="form-control">
        </div>
        <div class="form-group">
            <label for="re_enroll_deadline">Intent to Re-enroll Deadline</label>
            <input type="text" name="re_enroll_deadline" id="re_enroll_deadline" required class="form-control">
        </div>
        <div class="form-group">
            <label for="first_sem_learning_logs_close">Last Day to submit 1st Semester Learning Logs</label>
            <input type="text" name="first_sem_learning_logs_close" id="first_sem_learning_logs_close" required class="form-control">
        </div>
        <div class="form-group">
            <label for="log_submission_close">Last day to submit logs</label>
            <input type="text" name="log_submission_close" id="log_submission_close" required class="form-control">
        </div>
        <div class="form-group">
            <label for="application_close">Application Close Date</label>
            <input type="text" name="application_close" id="application_close" required class="form-control">
        </div>
        <div class="form-group">
            <label for="midyear_open">Mid-year Application Open Date</label>
            <input type="text" name="midyear_open" id="midyear_open" required class="form-control">
        </div>
        <div class="form-group">
            <label for="midyear_close">Mid-year Application Close Date</label>
            <input type="text" name="midyear_close" id="midyear_close" required class="form-control">
        </div>
        <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="midyear_application" id="midyear_application">
            <label>Allow Mid-year Application</label>
        </div>
        <p>
            <button class="btn btn-round btn-primary" type="submit">Save</button>
            <button class="btn btn-round btn-secondary" onclick="global_popup_close('editYear')" type="button">Cancel</button>
        </p>
    </form>
</div>
<?php

core_loader::printFooter('admin');
