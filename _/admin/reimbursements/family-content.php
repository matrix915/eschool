<?php

if (req_get::bool('delete')) {
    $rrIDs = array_map('intval', explode('|', req_post::txt('rrIDs')));
    $success = array();
    foreach ($rrIDs as $rrID) {
        if (!($reimbursement = mth_reimbursement::get($rrID))) {
            continue;
        }
        $success[] = $reimbursement->delete();
    }
    if (count($success) != count(array_filter($success))) {
        echo '0';
    } else {
        echo '1';
    }
    exit();
}


if (!$parent = mth_parent::getByParentID(req_get::int('parent'))) {
    die('No Parent Selected');
}

$year = null;
if (req_get::is_set('year')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('year'));
}
if (!$year) {
    ($year = mth_schoolYear::getCurrent()) || die('No year defined');
}
core_loader::isPopUp();
core_loader::includeBootstrapDataTables('css');
core_loader::addClassRef('family-reimbursement');
core_loader::printHeader();
?>
<style>
    .mth_reimbursement-Resubmitted a:not([href]):not([tabindex]),
    .mth_reimbursement-Submitted a:not([href]):not([tabindex]) {
        color: #616161;
    }

    .mth_reimbursement-SubmittedSecond a:not([href]):not([tabindex]),
    .mth_reimbursement-ResubmittedSecond a:not([href]):not([tabindex]) {
        color: #990099;
    }

    .mth_reimbursement-UpdatesRequired a:not([href]):not([tabindex]),
    .mth_reimbursement-UpdatesRequiredSecond a:not([href]):not([tabindex]) {
        color: #ff9800;
    }

    .mth_reimbursement-Paid a:not([href]):not([tabindex]),
    .mth_reimbursement-PaidSecond a:not([href]):not([tabindex]) {
        color: #2196f3;
    }

    .mth_reimbursement-Approved a:not([href]):not([tabindex]),
    .mth_reimbursement-ApprovedSecond a:not([href]):not([tabindex]) {
        color: #43a047;
    }

    #mth_reimursements_total {
        position: absolute;
        display: inline-block;
        color: #2196f3;
        background: rgba(255, 255, 255, .8);
        padding: 10px 20px;
        border-radius: 5px 5px 0 0;
        z-index: 5;
        font-size: 24px;
    }

    #mth_reimursements_total:before {
        content: 'Sum of selected: $'
    }

    #mth_reimursements_total.fixed {
        position: fixed;
        top: auto;
        bottom: 0px;
    }
</style>
<button type="button" class="iframe-close btn btn-round btn-secondary" onclick="closeHistory()">Close</button>
<div class="card">
    <div class="card-header">
        <select onchange="changeYear(this)" title="School Year" autocomplete="off" class="form-control">
            <?php foreach (mth_schoolYear::getSchoolYears(NULL, time()) as $each_year) { ?>
                <option value="<?= $each_year->getStartYear() ?>" <?= $each_year->getID() == $year->getID() ? 'selected' : '' ?>><?= $each_year ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="card-block">
        <table class="table table-stripped responsive reimbursement-tbl">
            <thead>
                <tr>
                    <th>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" class="selectall">
                            <label></label>
                        </div>
                    </th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Student</th>
                    <th>Period</th>
                    <th>Method</th>
                    <th style="min-width:100px;">Approved By</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($reimbursement = mth_reimbursement::each($parent, NULL, $year)) { ?>
                    <?php $class = 'mth_reimbursement-' . (preg_replace('/ /', '', $reimbursement->status())) . ($reimbursement->isSecond() ? 'Second' : '') ?>
                   
                    <tr id="reimbursement-<?= $reimbursement->id(); ?>">
                        <td>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="rrCB" value="<?=$reimbursement->id();?>">
                                <label></label>
                            </div>
                        </td>
                        <td class="<?= $class ?>" class="mth_reimbursement-<?= str_replace(' ', '', $reimbursement->status()) ?>">
                            <a onclick="edit(<?= $reimbursement->id(); ?>,<?= $reimbursement->type(true) ?>)"><?= $reimbursement->status() ?></a>
                        </td>
                        <td><?= $reimbursement->date_submitted('m/d/y') ?></td>
                        <td class="mth_reimbursement_amount">$<?= $reimbursement->amount(true) ?></td>
                        <td><?= $reimbursement->date_paid('m/d/y') ?></td>
                        <td><?= $reimbursement->student() ?></td>
                        <td><?= ($reimbursement->schedule_period() ? $reimbursement->schedule_period_description() : $reimbursement->getTypeLabel()) ?></td>
                        <td><?= ($reimbursement->is_direct_order() ? 'DO' : 'RB') ?></td>
                        <td><?= ($reimbursement->approved_by_id() ? core_user::getUserById($reimbursement->approved_by_id())->getName() : 'N/A') ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <button class="btn btn-primary" onclick="top.global_popup_iframe('directdeduction','/_/admin/reimbursements/direct?sy=<?= $year->getID() ?>&parent=<?= $parent->getID() ?>')"><i class="fa fa-plus"></i> Direct Deduction</button>
        <button class="btn btn-danger" onclick="_delete()">Delete</button>
    </div>
</div>
<div id="mth_reimursements_total" style="display: none"></div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>

<script>
    //
    function edit(reimbursementID, type) {
        if (type == <?= mth_reimbursement::TYPE_DIRECT ?>) {
            top.global_popup_iframe('directdeduction', '/_/admin/reimbursements/direct?reimbursement=' + reimbursementID);
        } else {
            global_popup_iframe('mth_reimbursement-popup-form', '/_/admin/reimbursements/edit?reimbursement=' + reimbursementID);
        }
    }

    function closeHistory() {
        top.global_popup_iframe_close('mth_reimbursement-show');
    }

    function changeYear($this) {
        location = '?year=' + $this.value + '&parent=' + '<?= req_get::int('parent') ?>';
    }

    function removeDeleted(id){
        $DataTable.row($('#reimbursement-'+id)).remove().draw();
    }

    function _delete(){
        swal({
            title: "",
            text: "Are you sure you want to delete reimbursement(s)? This action cannot be undone.",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-warning",
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            closeOnConfirm: true,
            closeOnCancel: true
        },
        function () {
            deleteSelected();
        });
    }
    function deleteSelected() {
        
        var rrIDs = selectedRequests();
        if (rrIDs < 1) {
            return;
        }
        global_waiting();
        $.ajax({
            url: '?delete=1',
            method: 'post',
            data: 'rrIDs=' + rrIDs.join('|'),
            success: function(response) {
                if (response === '0') {
                    setTimeout(function(){
                        swal('', 'Unable to delete some of the reimbursements', 'error');
                    },500); 
                } else {
                    $.each(rrIDs, function(index, id) {
                        removeDeleted(id);
                    });
                }
                global_waiting_hide();
                toastr.success('Reimbursement(s) deleted');
            },
            error: function(){
                swal('', 'Error deleting reimbursements', 'error');
            }
        });
    }

    function selectedRequests() {
        var rrIDs = [];
        var CBs = $('.rrCB:checked');
        if (CBs.length < 1) {
            setTimeout(function(){
                swal('','Select at least one reimbusement request','info');
            },500); 
        } else {
            CBs.each(function () {
                rrIDs.push(this.value);
            });
        }
        return rrIDs;
    }

    $(function() {
        $DataTable = $('.reimbursement-tbl').DataTable({
            bStateSave: false,
            pageLength: 50,
            "columnDefs": [{
                orderable: false,
                targets: [0]
            }],
            aaSorting: [['5', 'asc'], ['6', 'asc'], ['7', 'asc'], ['2', 'asc']] //NOT ZERO INDEXED
        });
        var $total = $('#mth_reimursements_total');

        $('.selectall').change(function() {
            var check = $(this).is(':checked');
            $('.rrCB').prop("checked", check);
        });

        setInterval(function() {
            var total = 0;
            var info = $('.dataTables_info');
            var infoffset = info.offset();
            $('.rrCB:checked').closest('td').siblings('.mth_reimbursement_amount').each(function() {
                total += Number(this.innerHTML.replace(/[^\d.-]/g, ''));
            });


            if ($('.rrCB:checked').length > 0) {
                $total.show().html(total.toFixed(2));
                $total.css('left', infoffset.left);


                var scrollHeight = $(document).height();
                var scrollPosition = $(window).height() + $(window).scrollTop();
                var isbottom = ((scrollHeight - scrollPosition) / scrollHeight) == 0;

                if (isbottom) {
                    $total.css('top', infoffset.top + info.height());
                    $total.removeClass('fixed');
                } else {
                    $total.css('top', 'auto');
                    $total.addClass('fixed');
                }
            } else {
                $total.hide();
            }
        }, 2000);
    })
</script>