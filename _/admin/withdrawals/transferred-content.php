<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 9/8/17
 * Time: 1:10 PM
 */


if (req_get::bool('y')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('y'));
}
if (empty($year)) {
    $year = mth_schoolYear::getCurrent();
}
$previous_year = $year->getPreviousYear();

define('SETTING_NAME','sent_to_dropbox_student_ids-'.$year->getName());
define('SETTING_CAT', 'transfer_withdrawals');

$sent_to_dropbox = core_setting::get(SETTING_NAME,SETTING_CAT);
if(!$sent_to_dropbox){
    $sent_to_dropbox = [];
    core_setting::set(
        SETTING_NAME,
        serialize($sent_to_dropbox),
        core_setting::TYPE_RAW,
        SETTING_CAT);
}else{
    $sent_to_dropbox = unserialize($sent_to_dropbox);
}

if (req_get::bool('send_to_dropbox')) {
    $student = mth_student::getByStudentID(req_get::int('send_to_dropbox'));
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';
    $new_soe = $student->getSchoolOfEnrollment(false,$year);
    
    $year = mth_schoolYear::getCurrent();
    $currentSchoolSetting = $student->getSchoolOfEnrollment(true,$year,true);
    $withdrawal = mth_withdrawal::getOrCreate($student, $previous_year, true);
    
    $withdrawal->set_new_school_name($new_soe->getLongName());
    $withdrawal->set_new_school_address($new_soe->getAddresses(false));
    $withdrawal->set_reason(mth_withdrawal::REASON_TRANS_ONLINE);
    $sy = mth_schoolYear::getCurrent()->getDateBegin('Y');
    
    $withdrawal->set_withdrawal_date(strtotime('06/01/'.$sy));
    $withdrawal->set_effective_date();

    if(($packet = mth_packet::getStudentPacket($student))
        && ($packet_sig_file_id = $packet->getSignatureFileID())
        && ($packet_sig_file = mth_packet_file::getByID($packet_sig_file_id))
        && ($sig_file = $packet_sig_file->getFile())
    ){
        $withdrawal->setSigFile($sig_file);
    }
    if ($withdrawal->sendToDropbox('Transferred From',$year, $inputState)) {
        $sent_to_dropbox[] = $student->getID();
        core_setting::set(
            SETTING_NAME,
            serialize($sent_to_dropbox),
            core_setting::TYPE_RAW,
            SETTING_CAT);
    }else{
        core_notify::addError('Unable to send the withdrawal letter for ' . ($student ? $student->getName() : 'the selected student') . ' to Dropbox');
    }
    echo 1;
    exit();
}


$filter1 = new mth_person_filter();
$filter1->setStudentIDs((new \mth\student\SchoolOfEnrollment\Query())->getStudentIdsWhoTransferredSoE($year));
$filter1->setStatus([mth_student::STATUS_ACTIVE,mth_student::STATUS_PENDING]);
$filter1->setStatusYear([$year->getID()]);
$filter1->setTransferred();
$students = $filter1->getStudents();

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Withdrawals');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
    <!--suppress CssUnusedSymbol -->
    <style>
        /* #mth_schoolYear_filter {
            position: relative;
            z-index: 3;
            margin-right: 200px;
        } */

        #mth_withdrawal_table_info {
            display: none;
        }

        label {
            display: inline-block;
            margin-left: 10px;
        }
    </style>
<!--suppress JSCheckFunctionSignatures -->
   

<div class="nav-tabs-horizontal nav-tabs-inverse">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="/_/admin/withdrawals">
                Withdrawn
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link active"  href="/_/admin/withdrawals/transferred">
                Transferred
            </a>
        </li>
    </ul>
    <div class="tab-content p-20">
        <div class="tab-pane active" role="tabpanel">
            <div class="tab-pane active" role="tabpanel">
                <p id="mth_schoolYear_filter">
                    <select onchange="location.href='?y='+this.value" title="School Year">
                        <?php while ($eachYear = mth_schoolYear::each()): ?>
                            <option value="<?= $eachYear->getStartYear() ?>"
                                <?= $eachYear->getID() == $year->getID() ? 'selected' : '' ?>><?= $eachYear ?></option>
                        <?php endwhile; ?>
                    </select> &nbsp;
                    Total: <span id="mth_withdrawal_count"></span>
                </p>
            </div>
            <div class="row">
                <table id="mth_withdrawal_table" class="table responsive"> 
                    <thead>
                    <tr>
                        <th>
                            <input type="checkbox" title="Un/Check All"
                                onclick="$('tr:visible .withdrawalCB').prop('checked',window.cb = !window.cb)">
                        </th>
                        <th>Student</th>
                        <th>Parent</th>
                        <th>City</th>
                        <th><?=$year?></th>
                        <th><?=$previous_year?></th>
                        <th>Dropbox</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $scount = 0;
                    foreach ($students as $student){
                        $withdrawal = mth_withdrawal::getByStudent($student->getID(), $year->getID());
                        if(!($packet = mth_packet::getStudentPacket($student))){
                            continue;
                        }
                        
                        if(!$packet->isAccepted()){
                            continue;
                        }
                        $scount++;
                        ?>
                        <tr class="mth_withdrawal-<?= !$withdrawal || !$withdrawal->notified() ? 'not_notified' : 'notified' ?> mth_withdrawal-<?= $withdrawal && $withdrawal->submitted() ? 'submitted' : 'not_submitted' ?>  dropbox-<?= $withdrawal && $withdrawal->sent_to_dropbox() ? 'sent' : 'not-sent' ?>">
                            <td>
                                <input type="checkbox" class="withdrawalCB" title="<?= $student->getName() ?>"
                                    value="<?= $student->getID() ?>">
                            </td>
                            <td> <?= $student->getName(true) ?> </td>
                            <td> <?= $student->getParent()->getName(true) ?> </td>
                            <td> <?= $student->getAddress() ? $student->getAddress()->getCity() : '' ?> </td>
                            <td><?= $student->getSchoolOfEnrollment(false, $year) ?></td>
                            <td><?= $student->getSchoolOfEnrollment(false, $previous_year) ?></td>
                            <td><?= in_array($student->getID(),$sent_to_dropbox) ? 'Yes' : 'No' ?></td>
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
    <button type="button" class="btn btn-round btn-primary" onclick="sendToDropBox()">Send to Dropbox</button>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    $(function () {
        $('#mth_withdrawal_count').text('<?=$scount?>');
        $('#mth_withdrawal_table').dataTable({
            'aoColumnDefs': [{"bSortable": false, "aTargets": [0]}],
            "bStateSave": true,
            "bPaginate": false,
            "aaSorting": [[1, 'asc']]
        });
        // $('#mth_schoolYear_filter').css('margin-bottom', '-50px');
    });
    function sendToDropBox(undeclared) {
        var CBs = $('.withdrawalCB:checked');
        if (CBs.length < 1) {
            swal('','Select at least one student','warning');
            return;
        }
        global_waiting();
        sendToDropBox.completed = 0;
        sendToDropBox.timeout = 0;
        CBs.each(function () {
            var thisCB = this;
            setTimeout(function () {
                $.get('?y=<?=$year->getStartYear()?>&send_to_dropbox' + (undeclared ? '_empty' : '') + '=' + thisCB.value, function () {
                    sendToDropBox.completed++;
                    if (sendToDropBox.completed === CBs.length) {
                        location.href = '?y=<?=$year->getStartYear()?>';
                    }
                });
            }, sendToDropBox.timeout * 1000);
            sendToDropBox.timeout++;
        });
    }
</script>