<?php
($year = mth_schoolYear::getCurrent()) || die('No year defined');

mth_views_reimbursements::handleFormAjax();

if (($success = mth_views_reimbursements::handleFormSubmission()) !== null) {
    if ($success) {
        if (req_get::bool('markApproved')
            && ($reimbursement = mth_reimbursement::get(req_get::int('reimbursement')))
        ) {
            $reimbursement->set_status(mth_reimbursement::STATUS_APPROVED);
            $reimbursement->save();

            if ($reimbursement->is_direct_order()) {
                // Send email to request Zoom meeting
                if (
                    !($emailContent = core_setting::get('directOrderApprovalEmailContent', 'DirectOrders'))
                    || !($emailSubject = core_setting::get('directOrderApprovedEmailSubject', 'DirectOrders'))
                ) {
                    core_notify::addError('Unable to get approval email content');
                    core_loader::redirect('?reimbursement=' . req_get::int('reimbursement'));
                    exit;
                }

                $student = $reimbursement->student();
                $parent = $reimbursement->student_parent();

                $to = $parent->getEmail();

                $subject = $emailSubject->getValue();

                $content = str_replace(
                    array(
                        '[PARENT_FIRST]',
                        '[PARENT_LAST]',
                        '[STUDENT_FIRST]',
                        '[STUDENT_LAST]',
                        '[DIRECT_ORDER_SUBMITTED_DATE]',
                        '[DIRECT_ORDER_AMOUNT]',
                        '[CLASS_PERIOD_DESCRIPTION]',
                    ),
                    array(
                        $parent->getPreferredFirstName(),
                        $parent->getPreferredLastName(),
                        $student->getPreferredFirstName(),
                        $student->getPreferredLastName(),
                        $reimbursement->date_submitted('m/d/Y'),
                        $reimbursement->amount(true),
                        $reimbursement->schedule_period_description(),
                    ),
                    $emailContent->getValue()
                );

                $ses = new core_emailservice();
                $ses->enableTracking(true);

                if ($ses->send(
                    is_array($to) ? $to : [$to],
                    $subject,
                    $content
                )) {
//              core_notify::addMessage('Direct Order Approval Email Sent.');
                } else {
                    core_notify::addError('Could not send Direct Order Approval Email.');
                }
            }

        } elseif (req_get::bool('updatesRequired')) {
            core_loader::redirect('updates-required?reimbursement=' . req_get::int('reimbursement'));
        } elseif (req_get::bool('markOrdered') && ($reimbursement = mth_reimbursement::get(req_get::int('reimbursement')))) {

            // Update Reimbursement as PAID
            $reimbursement->set_status(mth_reimbursement::STATUS_PAID);
            $reimbursement->save();

            // Send "Order Approved" Email
            $email = new core_emailservice();
            $ccs = array_unique($reimbursement->getCC());

            $emailSubject = core_setting::get('directOrderOrderConfirmationEmailSubject', 'DirectOrders');
            $emailContentTemplate = core_setting::get('directOrderOrderConfirmationEmailContent', 'DirectOrders');
            $emailContent = str_replace(
                array(
                    '[PARENT_FIRST_NAME]',
                    '[DATE_DIRECT_ORDER_SUBMITTED]',
                    '[DIRECT_ORDER_WISHLIST_PROVIDER]',
                    '[DIRECT_ORDER_WISHLIST_LINK]',
                    '[DIRECT_ORDER_AMOUNT]',
                    '[STUDENT_FULL_NAME]',
                    '[STUDENT_SCHEDULE_PERIOD_DESCRIPTION]',
                    '[DIRECT_ORDER_CONFIRMATION]',
                    '[ADMIN_USER_FULL_NAME]',
                    '[ADMIN_USER_EMAIL]',
                ),
                array(
                    $reimbursement->student_parent()->getPreferredFirstName(),
                    $reimbursement->date_submitted('m/d/Y'),
                    $reimbursement->direct_order_list_provider(),
                    str_replace("\n", '<br />', $reimbursement->direct_order_list_link()),
                    $reimbursement->amount(true),
                    $reimbursement->student(),
                    ($reimbursement->schedule_period() ? '<b>Class: </b>' . $reimbursement->schedule_period_description() : ''),
                    str_replace("\n", '<br />', $reimbursement->direct_order_confirmation()),
                    core_user::getUserFirstName() . ' ' . core_user::getUserLastName(),
                    core_user::getUserEmail(),
                ),
                $emailContentTemplate->getValue()
            );

            if (!$email->send(
                [$reimbursement->student_parent()->getEmail()],
                $emailSubject->getValue(),
                $emailContent,
                null,
                $ccs
            )) {
                core_notify::addError('An error has occurred, please try again.');
//            core_loader::redirect('?reimbursement=' . $reimbursement->id());
            }

        }
        echo '<script>parent.global_popup_iframe_close("mth_reimbursement-popup-form");if(parent.document.body.classList.contains("family-reimbursement")){parent.document.location.reload();}</script>';
        exit();
    } else {
        core_notify::addError('Unable to save the form.');
        core_loader::redirect('?reimbursement=' . req_get::int('reimbursement'));
    }
}

$reimbursement = mth_reimbursement::get(req_get::int('reimbursement'));

if (!$reimbursement) {
    echo '<script>
            parent.removeDeleted(' . req_get::int('reimbursement') . ');
            parent.global_popup_iframe_close("mth_reimbursement-popup-form");
            parent.swal("","error finding reimbursement record. Try again. If the problem persists refresh the page.","error")
        </script>';
    exit();
}

if (req_get::bool('delete')) {
    if ($reimbursement->delete()) {
        echo "<script>
            if (parent.document.body.classList.contains('family-reimbursement')){
                parent.document.location.reload();
            }else{
                parent.removeDeleted(" . req_get::int('reimbursement') . ");
            }
            parent.global_popup_iframe_close('mth_reimbursement-popup-form');
            </script>";
        exit();
    } else {
        core_notify::addError('Unable to delete reimbursement request');
        core_loader::redirect('?reimbursement=' . req_get::int('reimbursement'));
    }
}

if (req_get::bool('paid') && $reimbursement) {
    $reimbursement->set_status(mth_reimbursement::STATUS_PAID);
    $reimbursement->save();
    core_notify::addMessage('Reimbursement request successfully marked as paid.');
    core_loader::redirect('?reimbursement=' . req_get::int('reimbursement'));
}

core_loader::isPopUp();
core_loader::printHeader();
?>
    <script>
        function mth_reimbursement_approve() {
            global_waiting();
            var form = $('#mth_reimbursement_form');
            if (!form.valid()) {
                global_waiting_hide();
                return;
            }
            form.attr('action', form.attr('action') + '&markApproved=1').submit();
        }
        function mth_reimbursement_updates() {
            global_waiting();
            var form = $('#mth_reimbursement_form');
            if (!form.valid()) {
                global_waiting_hide();
                return;
            }
            form.attr('action', form.attr('action') + '&updatesRequired=1').submit();
        }
        function mth_reimbursement_delete() {
            global_confirm('<p>Are you sure you want to delete this Reimbursement Request. This action cannot be undone.',
            function () {
                location.href = '?reimbursement=<?=$reimbursement->id()?>&delete=1';
            },
            'Yes');
        }
        function mth_reimbursement_paid(){
            global_waiting();
            location.href = '?reimbursement=<?=$reimbursement->id()?>&paid=1';
        }
        function mth_reimbursement_ordered() {
          global_waiting();
          var form = $('#mth_reimbursement_form');
          if (!form.valid()) {
            global_waiting_hide();
            return;
          }
          form.attr('action', form.attr('action') + '&markOrdered=1').submit();
        }

        function closeForm(){
            if ($(parent.document.body).is('.family-reimbursement')){
                parent.document.location.reload();
            }
            parent.global_popup_iframe_close('mth_reimbursement-popup-form');

        }


    </script>

    <button type="button" class="iframe-close btn btn-round btn-secondary" onclick="closeForm()">Close</button>
    <div class="reimbursement-form">
        <h3>Request for <?=$reimbursement->is_direct_order() ? 'Direct Order' : 'Reimbursement'?> Form</h3>
        <div class="panel" id="link-container">
            <div class="panel-body p-10">
                <div class="row">
                        <div class="col">
                            <div style="font-size: 18px;">
                                <span style="color:#2196f3;">Sum of Selected:</span>
                                $<span id="subtotal">0</span>
                            </div>
                            <?php while (($linked = $reimbursement->eachLinked())): ?>
                                <?php
$periodLinked = 0;
$query = core_db::runQuery('select * from mth_schedule_period where schedule_period_id =' . $reimbursement->schedule_period_id());
$periodFrom = $query->fetch_assoc();

$query = core_db::runQuery('select * from mth_schedule_period where schedule_period_id =' . $linked->schedule_period_id());
$period = $query->fetch_assoc();

$periodLinked = $period ? $period["period"] : 0;

if ((in_array($periodFrom["period"], $reimbursement->merge_custom_periods()) &&
    in_array($periodLinked, $reimbursement->merge_custom_periods()))
    || $periodFrom["period"] == $periodLinked //if same period
     || $linked->schedule_period_id() == 0
) { //if the requested period is part of the merged or same period
    ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" class="reimburse_link" value="<?=$linked->amount(true)?> ">
                                    <label onclick="parent.edit(<?=$linked->id()?>,<?=$linked->type(true)?>)" style="color:red;font-weight:bold">
                                            $<?=$linked->amount(true)?> <?=$reimbursement->date_submitted() > $linked->date_submitted() ? 'previously' : 'also'?>
                                            submitted for this item <span style="color:#2196f3">(<?=$linked->status()?>)</span>
                                    </label>
                                </div>
                                <?php
}
?>
                            <?php endwhile;?>
                            <?php while (($dlinked = $reimbursement->eachDirect())): ?>
                                <?php
$query = core_db::runQuery('select * from mth_schedule_period where schedule_period_id =' . $reimbursement->schedule_period_id());
$periodFrom = $query->fetch_assoc();

$query = core_db::runQuery('select * from mth_schedule_period where schedule_period_id =' . $dlinked->schedule_period_id());
$period = $query->fetch_assoc();

$periodLinked = $period ? $period["period"] : 0;

if ((in_array($periodFrom["period"], $reimbursement->merge_custom_periods()) &&
    in_array($periodLinked, $reimbursement->merge_custom_periods()))
    || $periodFrom["period"] == $periodLinked
    || $dlinked->schedule_period_id() == 0
) { //if the requested period is part of the merged or same period
    ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" class="reimburse_link" value="<?=$dlinked->amount(true)?> ">
                                    <label onclick="parent.edit(<?=$dlinked->id()?>,<?=$dlinked->type(true)?>)" style="color:red;font-weight:bold">
                                            $<?=$dlinked->amount(true)?> <?=$reimbursement->date_submitted() > $dlinked->date_submitted() ? 'previously' : 'also'?>
                                            submitted for this item as <?=$dlinked->getTypeLabel()?> <span style="color:#2196f3">(<?=$dlinked->status()?>)</span>
                                    </label>
                                </div>
                             <?php }?>
                            <?php endwhile;?>
                        </div>
                </div>
            </div>
        </div>
        <?php mth_views_reimbursements::printReimbursement($reimbursement)?>
        <hr>
        <p>
        <?php if ($reimbursement->notApproved()): ?>
            <button type="button" class="btn btn-round btn-success" onclick="mth_reimbursement_approve()">Approved</button>
            <button type="button" class="btn btn-round btn-warning" onclick="mth_reimbursement_updates()">Updates Required</button>
            <button type="button" class="btn btn-round btn-danger" onclick="mth_reimbursement_delete()">Delete</button>
        <?php endif;?>
        <?php if (!$reimbursement->isPaid()): ?>
          <?php if ($reimbursement->is_direct_order()): ?>
            <button type="button" class="btn btn-round btn-primary" onclick="mth_reimbursement_ordered()">Mark as Ordered</button>
          <?php else: ?>
            <button type="button" class="btn btn-round btn-primary" onclick="mth_reimbursement_paid()">Mark as Paid</button>
          <?php endif;?>
        <?php endif;?>
        </p>

    </div>
<?php
core_loader::printFooter();
?>
<script>
       $(function(){
            var sum = 0;
            $('.reimburse_link').change(function(){
                if($(this).hasClass('reimburse_link_custom')){
                    $(this).val($('#amount').val());
                }

                calculate_total();
            });

            $('#amount').keyup(function(){
                $('.reimburse_link_custom').val($(this).val());
                calculate_total();
            });

            function calculate_total(){
                sum = 0;
                $('.reimburse_link:checked').each(function(){
                    var amount = ($(this).val()).replace(/[^\d.-]/g, '');
                    sum += (amount*1);
                });

                $('#subtotal').text(sum.toFixed(2));
            }
            $('.reimburse_link').length == 0 && $('#link-container').hide();
        });
</script>