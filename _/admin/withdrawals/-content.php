<?php

if (req_get::bool('y')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('y'));
}
if (empty($year)) {
    $year = mth_schoolYear::getCurrent();
}

$repair_ran = &$_SESSION[__FILE__]['repair_ran'];
if (!$repair_ran) {
    mth_withdrawal::repairMisConfigured();
    $repair_ran = true;
}

if (req_get::bool('resend_to_dropbox')) {
    $student = mth_student::getByStudentID(req_get::int('resend_to_dropbox'));
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';
    if (
        !($withdrawal = mth_withdrawal::getByStudent(req_get::int('resend_to_dropbox'), $year->getID()))
        || !$withdrawal->submitted()
    ) {
        core_notify::addError('No withdrawal letter submitted for ' . ($student ? $student->getName() : 'the selected student'));
        die('0');
    }

    if (!$withdrawal->sendToDropbox(null, null, $inputState)) {
        core_notify::addError('Unable to send the withdrawal letter for ' . ($student ? $student->getName() : 'the selected student') . ' to Dropbox');
    }

    if($withdrawal->datetime() == NULL) {
      if (!($transition = mth_transitioned::getOrCreate($student, $year))) {
        core_notify::addError('No transition for ' . ($student ? $student->getName() : 'the selected student') . ' has been found or created');
        die('0');
      }

      if (!$transition->sendUndeclaredToDropbox()) {
        core_notify::addError('Unable to send the affidavit letter for ' . ($student ? $student->getName() : 'the selected student') . ' to Dropbox');
      }
    }

    mth_withdrawal::setActiveValue($student->getID(), $year->getID(), 1);
    mth_withdrawal::delete($student->getID(), $year->getNextYear()->getID());
    $student->setStatus(NULL, $year->getNextYear());
    echo 1;
    exit();
}

if (req_get::bool('send_to_dropbox')) {
    $student = mth_student::getByStudentID(req_get::int('send_to_dropbox'));
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';
    if (
        !($withdrawal = mth_withdrawal::getByStudent(req_get::int('send_to_dropbox'), $year->getID()))
        || !$withdrawal->submitted()
    ) {
        core_notify::addError('No withdrawal letter submitted for ' . ($student ? $student->getName() : 'the selected student'));
        die('0');
    }
    if (!$withdrawal->sendEmailConfermation()) {
        core_notify::addError('Unable to send the email confirmation for ' . ($student ? $student->getName() : 'the selected student'));
    }
    if (!$withdrawal->sendToDropbox(null, null, $inputState)) {
        core_notify::addError('Unable to send the withdrawal letter for ' . ($student ? $student->getName() : 'the selected student') . ' to Dropbox');
    }
    mth_withdrawal::setActiveValue($student->getID(), $year->getID(), 1);
    mth_withdrawal::delete($student->getID(), $year->getNextYear()->getID());
    $student->setStatus(NULL, $year->getNextYear());
    echo 1;
    exit();
}
if (req_get::bool('send_to_dropbox_empty')) {
    $student = mth_student::getByStudentID(req_get::int('send_to_dropbox_empty'));
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';
    $withdrawal = mth_withdrawal::getOrCreate($student, $year, false, core_user::getUserID());
    mth_withdrawal::setActiveValue($student->getID(), $year->getID(), 1);
    mth_withdrawal::delete($student->getID(), $year->getNextYear()->getID());
    $student->setStatus(NULL, $year->getNextYear());
    if ($withdrawal->submitted()) {
        core_notify::addError('A withdrawal letter for ' . ($student ? $student->getName() : 'the selected student') . ' has been submitted');
        die('0');
    }
    if (!$withdrawal->sendEmailConfermation()) {
        core_notify::addError('Unable to send the email confirmation for ' . ($student ? $student->getName() : 'the selected student'));
    }
    if (!$withdrawal->sendToDropbox(null, null, $inputState)) {
        core_notify::addError('Unable to send the withdrawal letter for ' . ($student ? $student->getName() : 'the selected student') . ' to Dropbox');
    }
    if (!($transition = mth_transitioned::getOrCreate($student, $year))) {
        core_notify::addError('No transition for ' . ($student ? $student->getName() : 'the selected student') . ' has been found or created');
        die('0');
    }
    if (!$transition->sendUndeclaredToDropbox()) {
        core_notify::addError('Unable to send the affidavit letter for ' . ($student ? $student->getName() : 'the selected student') . ' to Dropbox');
    }
    echo 1;
    exit();
}

if (req_get::bool('create_undeclared')) {
    $student = mth_student::getByStudentID(req_get::int('create_undeclared'));
    $withdrawal = mth_withdrawal::getOrCreate($student, $year, false, core_user::getUserID());
    mth_withdrawal::setActiveValue($student->getID(), $year->getID(), 1);
    mth_withdrawal::delete($student->getID(), $year->getNextYear()->getID());
    $student->setStatus(NULL, $year->getNextYear());
    if ($withdrawal->submitted()) {
        core_notify::addError('A withdrawal letter for ' . ($student ? $student->getName() : 'the selected student') . ' has been submitted');
        die('0');
    }
    echo 1;
    exit();
}

if (req_get::bool('notify')) {
    ($student = mth_student::getByStudentID(req_get::int('notify'))) || die('0');
    $withdrawal = mth_withdrawal::getOrCreate($student, $year, false, core_user::getUserID());
    mth_withdrawal::setActiveValue($student->getID(), $year->getID(), 1);
    if ($withdrawal && $withdrawal->notify()) {
        echo 1;
        exit();
    }
    if ($withdrawal->submitted()) {
        core_notify::addError('Withdrawal form already submitted for ' . $student->getName());
        die();
    }
    core_notify::addError('Unable to send notification for ' . $student->getName());
    die();
}

if (req_get::bool('reset')) {
    ($student = mth_student::getByStudentID(req_get::int('reset'))) || die('0');

    if (!($withdrawal = mth_withdrawal::getByStudent($student->getID(), $year->getID()))) {
        core_notify::addError('Withdrawal form not found for ' . $student->getName());
        die();
    }

    if ($withdrawal->reset()) {
        mth_withdrawal::setActiveValue($student->getID(), $year->getID(), 0);
        echo 1;
        exit();
    }

    core_notify::addError('Unable to reset withdrawal form for' . $student->getName());
    die();
}

$deletedString = core_setting::get('deleted-withdrawals-' . $year->getName(), 'withdrawals');
if ($deletedString) {
    $deleted = unserialize($deletedString);
} else {
    $deleted = [];
}
if (req_get::bool('delete')) {
    $deleted[req_get::int('delete')] = req_get::int('delete');
    core_setting::set('deleted-withdrawals-' . $year->getName(), serialize($deleted), core_setting::TYPE_RAW, 'withdrawals');
    exit();
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Withdrawals');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
<style>
    /* #mth_schoolYear_filter {
            position: relative;
            z-index: 3;
            margin-right: 200px;
        } */

    #mth_withdrawal_table_info {
        display: none;
    }

    tr.mth_withdrawal-not_notified td {
        color: red;
    }

    tr.mth_withdrawal-notified td {
        color: #1181DE;
    }

    tr.mth_withdrawal-submitted td {
        color: #000;
    }


    tr.dropbox-sent td {
        color: #999;
    }

    tr.undeclared td {
        color: #999;
    }

    label {
        display: inline-block;
        margin-left: 10px;
    }

    /* .dropbox-sent{
            display:none;
        } */
</style>
<script>
    dttable = null;
    $(function() {
        dttable = $('#mth_withdrawal_table').DataTable({
            'aoColumnDefs': [{
                "bSortable": false,
                "aTargets": [0]
            }],
            "bStateSave": false,
            "bPaginate": true,
            "aaSorting": [
                [1, 'asc']
            ],
            "pageLength": 25
        }).on('page.dt', function() {
            //var info = dttable.page.info();
            $('.globalcb:checked').trigger('click');
        });
    });

    function createUndeclared() {
        var CBs = $('.withdrawalCB:checked');
        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        createUndeclared.completed = 0;
        createUndeclared.timeout = 0;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.get('?y=<?= $year->getStartYear() ?>&create_undeclared=' + thisCB.value, function() {
                    createUndeclared.completed++;
                    if (createUndeclared.completed === CBs.length) {
                        location.href = '?y=<?= $year->getStartYear() ?>';
                    }
                });
            }, createUndeclared.timeout * 1000);
            createUndeclared.timeout++;
        });
    }

    function sendToDropBox(undeclared) {
        var CBs = $('.withdrawalCB:checked');
        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        sendToDropBox.completed = 0;
        sendToDropBox.timeout = 0;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.get('?y=<?= $year->getStartYear() ?>&send_to_dropbox' + (undeclared ? '_empty' : '') + '=' + thisCB.value, function() {
                    sendToDropBox.completed++;
                    if (sendToDropBox.completed === CBs.length) {
                        location.href = '?y=<?= $year->getStartYear() ?>';
                    }
                });
            }, sendToDropBox.timeout * 1000);
            sendToDropBox.timeout++;
        });
    }

    function resendToDropBox() {
        var CBs = $('.withdrawalCB:checked');
        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        sendToDropBox.completed = 0;
        sendToDropBox.timeout = 0;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.get('?y=<?= $year->getStartYear() ?>&resend_to_dropbox=' + thisCB.value, function() {
                    sendToDropBox.completed++;
                    if (sendToDropBox.completed === CBs.length) {
                        location.href = '?y=<?= $year->getStartYear() ?>';
                    }
                });
            }, sendToDropBox.timeout * 1000);
            sendToDropBox.timeout++;
        });
    }

    function notify() {
        var CBs = $('.withdrawalCB:checked');
        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        notify.completed = 0;
        notify.timeout = 0;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.ajax({
                    url: '?y=<?= $year->getStartYear() ?>&notify=' + thisCB.value,
                    method: 'GET',
                    cache: false,
                    complete: function() {
                        notify.completed++;
                        if (notify.completed === CBs.length) {
                            location.href = '?y=<?= $year->getStartYear() ?>';
                        }
                    }
                });
            }, notify.timeout * 1000);
            notify.timeout++;
        });
    }

    function reset() {
        var CBs = $('.withdrawalCB:checked');


        global_waiting();
        reset.completed = 0;
        reset.timeout = 0;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.ajax({
                    url: '?y=<?= $year->getStartYear() ?>&reset=' + thisCB.value,
                    method: 'GET',
                    cache: false,
                    complete: function() {
                        reset.completed++;
                        if (reset.completed === CBs.length) {
                            location.href = '?y=<?= $year->getStartYear() ?>';
                        }
                    }
                });
            }, reset.timeout * 1000);
            reset.timeout++;
        });
    }

    function doReset() {
        var CBs = $('.withdrawalCB:checked');
        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }

        swal({
            title: '',
            text: 'Are you sure you want to continue Reset process?',
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-warning",
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            closeOnConfirm: true,
            closeOnCancel: true
        }, reset);
    }

    function deleteWithdrawals() {
        var CBs = $('.withdrawalCB:checked');
        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        deleteWithdrawals.completed = 0;
        deleteWithdrawals.timeout = 0;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.ajax({
                    url: '?y=<?= $year->getStartYear() ?>&delete=' + thisCB.value,
                    method: 'GET',
                    cache: false,
                    complete: function() {
                        deleteWithdrawals.completed++;
                        if (deleteWithdrawals.completed === CBs.length) {
                            location.href = '?y=<?= $year->getStartYear() ?>';
                        }
                    }
                });
            }, deleteWithdrawals.timeout * 500);
            deleteWithdrawals.timeout++;
        });
    }
</script>

<?php
$showwaswithdrawn = req_get::bool('waswithdrawn');
$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_WITHDRAW]);
$filter->setStatusYear(array($year->getID()));
$students = [];
$previous_year = $year->getPreviousYear();
foreach ($filter->getStudents() as $student) {
    if (!$previous_year) {
        continue;
    }

    if (is_null($student->getStatus($previous_year)) && $student->getStatusDate($year) < $year->getDateBegin()) { //if new then status date should be greater or equal to year start){ 
        continue;
    }

    if (!isset($deleted[$student->getID()])) {
        $withdrawal = mth_withdrawal::getByStudent($student->getID(), $year->getID());
        if (!$showwaswithdrawn && ($withdrawal && $withdrawal->isUndeclared())) {
            continue;
        }
        $student->withdrawal['is_notified'] = $withdrawal && $withdrawal->notified() ? true : false;
        $student->withdrawal['is_submitted'] = $withdrawal && $withdrawal->submitted() ? $withdrawal->datetime('m/d/Y') : false;
        $student->withdrawal['is_send_to_dropbox'] = $withdrawal && $withdrawal->sent_to_dropbox() ? true : false;
        $student->withdrawal['is_undeclared'] = $withdrawal && $withdrawal->isUndeclared() ? true : false;
        $students[] = $student;
    }
}
$withdrawalCount = count($students);

?>

<div class="nav-tabs-horizontal nav-tabs-inverse">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" href="/_/admin/withdrawals">
                Withdrawn
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="/_/admin/withdrawals/transferred">
                Transferred
            </a>
        </li>
    </ul>
    <div class="tab-content p-20">
        <div class="tab-pane active" role="tabpanel">
            <p id="mth_schoolYear_filter">
                <select onchange="location.href='?y='+this.value+'<?= $showwaswithdrawn ? '&waswithdrawn=1' : '' ?>'" title="School Year">
                    <?php while ($eachYear = mth_schoolYear::each()) : ?>
                        <option value="<?= $eachYear->getStartYear() ?>" <?= $eachYear->getID() == $year->getID() ? 'selected' : '' ?>><?= $eachYear ?></option>
                    <?php endwhile; ?>
                </select> &nbsp;
                Total: <span id="mth_withdrawal_count"><?= number_format($withdrawalCount) ?></span>&nbsp;|&nbsp;

                <label for="showUnSubmitted">
                    <input type="checkbox" id="showUnSubmitted" checked>
                    Show un-submitted
                </label>
                <label for="showDropbox">
                    <input type="checkbox" id="showDropbox">
                    Show sent to Dropbox
                </label>
                <label for="showWasWith">
                    <input type="checkbox" id="showWasWith" <?= $showwaswithdrawn ? 'CHECKED' : '' ?>>
                    Show was withdrawn
                </label>
            </p>
            <div class="row">
                <table id="mth_withdrawal_table" class="table responsive">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" title="Un/Check All" class="globalcb" onclick="$('tr:visible .withdrawalCB:lt(25)').prop('checked',window.cb = !window.cb)">
                            </th>
                            <th>Student</th>
                            <th>Parent</th>
                            <th>SoE</th>
                            <th>City</th>
                            <th>Status Date</th>
                            <th>Notified</th>
                            <th>Submitted</th>
                            <th>Dropbox</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sent = [];
                        foreach ($students as $student) {
                            ?>
                            <tr class="mth_withdrawal-<?= $student->withdrawal['is_notified'] ? 'notified' : 'not_notified' ?> mth_withdrawal-<?= $student->withdrawal['is_submitted'] || $student->withdrawal['is_undeclared'] ? 'submitted' : 'not_submitted' ?>  dropbox-<?= $student->withdrawal['is_send_to_dropbox'] ? 'sent' : 'not-sent' ?> <?= $student->withdrawal['is_undeclared'] ? 'undeclared' : '' ?>">
                                <td>
                                    <input type="checkbox" class="withdrawalCB" title="<?= $student->getName() ?>" value="<?= $student->getID() ?>">
                                </td>
                                <td> <?= $student->getName(true) ?></td>
                                <td> <?= $student->getParent()->getName(true) ?> </td>
                                <td><?= $student->getWithdrawalSOE(false, mth_withdrawal::letter_year_calculator($student, $year)) ?></td>
                                <td> <?= $student->getAddress() ? $student->getAddress()->getCity() : '' ?> </td>
                                <td><?= $student->getStatusDate($year, 'm/d/Y') ?></td>
                                <td><?= $student->withdrawal['is_notified'] ? 'Yes' : 'No' ?></td>
                                <td><?= $student->withdrawal['is_submitted'] ? $student->withdrawal['is_submitted'] : 'No' ?></td>
                                <td><?= $student->withdrawal['is_send_to_dropbox'] ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="fixed-button-bar" style="position: fixed;bottom: 2px;">
    <button type="button" onclick="notify()" class="btn btn-primary btn-round">Notify</button>
    <button type="button" onclick="sendToDropBox()" class="btn btn-success btn-round">Send to Dropbox/Email Confirmation</button>
    <button type="button" onclick="sendToDropBox(true)" class="btn btn-info btn-round">Send Undeclared to Dropbox/Email Confirmation</button>
    <button type="button" onclick="createUndeclared()" class="btn btn-info btn-round">Create Undeclared</button>
    <button style="display: none !important;" type="button" onclick="deleteWithdrawals()" class="btn btn-danger btn-round">Delete</button>
    <button type="button" onclick="resendToDropBox()" class="btn btn-success btn-round">Resend to Dropbox</button>
    <button type="button" onclick="doReset()" class="btn btn-warning btn-round">Reset</button>
</div>



<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    function show_rows() {
        var showunsubmitted = $('#showUnSubmitted').is(':checked');
        var showsentdropbox = $('#showDropbox').is(':checked');
        var showWasWithrawn = $('#showWasWith').is(':checked');

        show_all();
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var $tr = $(dttable.row(dataIndex).node());
                var submitted = showunsubmitted ?
                    ($tr.hasClass('mth_withdrawal-not_submitted') || $tr.hasClass('mth_withdrawal-submitted')) :
                    (showsentdropbox ? ($tr.hasClass('mth_withdrawal-not_submitted') || $tr.hasClass('mth_withdrawal-submitted')) : $tr.hasClass('mth_withdrawal-submitted'));

                var sent = showsentdropbox ?
                    (showunsubmitted ? ($tr.hasClass('dropbox-not-sent') || $tr.hasClass('dropbox-sent')) :
                        ($tr.hasClass('undeclared') || $tr.hasClass('dropbox-sent'))) :
                    $tr.hasClass('dropbox-not-sent');

                return submitted && sent;
            }
        );
        dttable.draw();
        var entry_count = $('#mth_withdrawal_table').DataTable().page.info().recordsDisplay;
        $('#mth_withdrawal_count').html(entry_count);
    }

    function show_all() {
        $.fn.dataTable.ext.search.pop();
        dttable.draw();
    }

    $(function() {

        show_rows();
        $('#showUnSubmitted').change(function() {
            show_rows();
        });

        $('#showDropbox').change(function() {
            show_rows();
        });

        $('#showWasWith').change(function() {
            if ($(this).is(':checked')) {
                location.href = "?y=<?= $year->getStartYear() ?>&waswithdrawn=1";
            } else {
                location.href = "?y=<?= $year->getStartYear() ?>";
            }

        });


    });
</script>