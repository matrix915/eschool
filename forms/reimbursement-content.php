<?php
($parent = mth_parent::getByUser()) || core_secure::loadLogin();

if (!isset($_GET['testing']) && !(($os = core_setting::get('oldreimbursement', 'advance')) && $os->getValue())) {
    header('location: ' . MUSTANG_URI);
    exit;
}

$year = null;
if (req_get::bool('year')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('year'));
}
if (!$year) {
    ($year = mth_schoolYear::getCurrent()) || die('No year defined');
}
core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Request for Reimbursement');
cms_page::setPageContent('Carefully review the information found in Section 4 of Parent Link.', 'ListItemOne', cms_content::TYPE_LIMITED_HTML);
cms_page::setPageContent('Check your child\'s <a href="https://mytechhigh.instructure.com" target="_blank">Homeroom grade in Canvas</a> to ensure it is over 80%.', 'ListItemTwo', cms_content::TYPE_LIMITED_HTML);
cms_page::setPageContent('<a href="https://goo.gl/forms/zhxO52jynkg1LMB43">Select payment option</a> - either “Direct Deposit” or “Check in the Mail” (one form per family)', 'ListItemThree', cms_content::TYPE_LIMITED_HTML);
core_loader::printHeader('student');
?>
<style>
    .mth_reimbursement-Submitted,
    .mth_reimbursement-Resubmitted {
        color: #999;
    }

    .mth_reimbursement-UpdatesRequired,
    .mth_reimbursement-NotSubmitted {
        color: #ff9800;
    }

    .mth_reimbursement-Approved,
    .mth_reimbursement-Paid {
        color: #4caf50;
    }

    .timeline-dot {
        top: 0px;
    }

    #mth_reimursements_total {

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

    a.r-control-btn {
        color: #fff;
    }
</style>
<div class="page">
    <?= core_loader::printBreadCrumb('window'); ?>
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            To submit a Request for Reimbursement, please do the following:
                        </h4>
                    </div>
                    <div class="card-block">
                        <ul class="timeline timeline-single steps-timeline">
                            <li class="timeline-item">
                                <div class="timeline-dot"><i>1</i></div>
                                <div class="timeline-content">
                                    <?= cms_page::getDefaultPageContent('ListItemOne', cms_content::TYPE_LIMITED_HTML) ?>
                                </div>
                            </li>
                            <li class="timeline-item">
                                <div class="timeline-dot"><i>2</i></div>
                                <div class="timeline-content">
                                    <?= cms_page::getDefaultPageContent('ListItemTwo', cms_content::TYPE_LIMITED_HTML) ?>
                                </div>
                            </li>
                            <li class="timeline-item">
                                <div class="timeline-dot"><i>3</i></div>
                                <div class="timeline-content">
                                    <?= cms_page::getDefaultPageContent('ListItemThree', cms_content::TYPE_LIMITED_HTML) ?>
                                </div>
                            </li>
                            <li class="timeline-item">
                                <div class="timeline-dot"><i>4</i></div>
                                <div class="timeline-content">
                                    <?php if (!mth_reimbursement::open()) : ?>
                                        <b>The reimbursement form will be available <?= mth_schoolYear::getCurrent()->reimburse_open('F j') ?>
                                            .</b>
                                    <?php else : ?>
                                        Follow the steps on <a href="#" onclick="showForm()">this form</a> to request a reimbursement.
                                    <?php endif; ?>
                                </div>
                            </li>

                        </ul>
                    </div>
                </div>
            </div><!-- End Col-md-6 -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-block p-0">
                        <div class="p-10">
                            <select onchange="location='?year='+this.value" title="School Year" autocomplete="off" class="form-control">
                                <?php foreach (mth_schoolYear::getSchoolYears(NULL, time()) as $each_year) { ?>
                                    <option value="<?= $each_year->getStartYear() ?>" <?= $each_year->getID() == $year->getID() ? 'selected' : '' ?>><?= $each_year ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php if (($reimbursement = mth_reimbursement::each($parent, NULL, $year))) { ?>
                            <table id="tablereimbu" class="formatted table table-stripped responsive reimbursement-tbl">
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
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php do { ?>
                                        <tr>
                                            <td>
                                                <div class="checkbox-custom checkbox-primary">
                                                    <input type="checkbox" class="rrCB">
                                                    <label></label>
                                                </div>
                                            </td>
                                            <td class="mth_reimbursement-<?= str_replace(' ', '', $reimbursement->status()) ?>">
                                                <span><?= $reimbursement->status() ?></span>

                                            </td>
                                            <td><?= $reimbursement->date_submitted('m/d/y') ?></td>
                                            <td class="mth_reimbursement_amount">$<?= $reimbursement->amount(true) ?></td>
                                            <td><?= $reimbursement->date_paid('m/d/y') ?></td>
                                            <td><?= $reimbursement->student() ?></td>
                                            <td><?= ($reimbursement->schedule_period() ? $reimbursement->schedule_period_description() : (NULL == $reimbursement->type_tag() ? $reimbursement->description() : $reimbursement->type())) ?></td>
                                            <td>
                                                <?php if (!in_array($reimbursement->status(true), [mth_reimbursement::STATUS_NOTSUBMITTED, mth_reimbursement::STATUS_UPDATE])) : ?>
                                                    <a onclick="showForm(<?= $reimbursement->id(); ?>,1)" class="btn btn-sm btn-primary btn-block r-control-btn" href="#">
                                                        View
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($reimbursement->editable()) : ?>
                                                    <a onclick="showForm(<?= $reimbursement->id(); ?>,2)" class="btn btn-sm btn-warning btn-block r-control-btn" href="#">
                                                        Edit
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php } while ($reimbursement = mth_reimbursement::each($parent, NULL, $year)); ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            <p class="text-center">None</p>
                        <?php } ?>
                    </div>
                </div>
                <div id="mth_reimursements_total" style="display: none"></div>
            </div>
        </div> <!-- End Row -->
    </div><!-- End Page Content -->
</div><!-- End Page -->
<?php
core_loader::printFooter('student');
?>
<script>
    function showForm(reimbursementID, mode) {
        global_popup_iframe('mth_reimbursement-popup-form', '/forms/reimbursement-form?reimbursement=' + (reimbursementID ? reimbursementID : 'NEW') + '&mode=' + mode);
    }
    $(function() {

        var $total = $('#mth_reimursements_total');

        function show_total() {

            var info = $('#tablereimbu');
            var infoffset = info.offset();
            var total = 0;
            $('.rrCB:checked').closest('td').siblings('.mth_reimbursement_amount').each(function() {
                total += Number(this.innerHTML.replace(/[^\d.-]/g, ''));
            });

            if ($('.rrCB:checked').length > 0) {
                $total.show().html(total.toFixed(2));
                // $total.css('left',infoffset.left);

                // var scrollHeight = $(document).height();
                // var scrollPosition = $(window).height() + $(window).scrollTop();
                // var isbottom = ((scrollHeight - scrollPosition) / scrollHeight) == 0;

                // if (isbottom) {
                //     $total.css('top',infoffset.top+info.height());
                //     //$total.removeClass('fixed');
                // }else{
                //     $total.css('top','auto');
                //     $total.addClass('fixed');
                // }
            } else {
                $total.hide();
            }
        }

        setInterval(function() {

            show_total();
        }, 2000);

        $('.reimbursement-tbl').DataTable({
            stateSave: false,
            "paging": false,
            "searching": false,
            "info": false,
            "columnDefs": [{
                orderable: false,
                targets: [0, 7]
            }, ],
        });
        var showRR = location.hash.replace(/#?(show([0-9]+))?/i, "$2");
        if (showRR.length > 0) {
            showForm(showRR);
        }

        $('.selectall').change(function() {
            var check = $(this).is(':checked');
            $('.rrCB').prop("checked", check);
        });
    });
</script>