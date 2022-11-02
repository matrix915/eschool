<?php
use mth\yoda\courses;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of reimbursements
 *
 * @author abe
 */
class mth_views_reimbursements
{

    protected static $formPrinted = false;

    const NO_PERIOD = 'Student does NOT have a Period 7 course.';
    const HAS_DIRECT = 'Student has a Period 7 MTH Direct course.';
    const HAS_CUSTOM = 'Student has a Period 7 Custom-built course.';
    const HAS_TP = 'Student has a Period 7 3rd Party Provider course.';
    const HAS_ALLOWANCE = 'Student\'s schedule has a course which affects the Technology Allowance.';
    const RETURNING_STUDENT = 'This is a Returning student.';
    const NEW_STUDENT = 'This is a New student.';
    const KINDER = 'This student is in Kindergarten.';
    const RETURNING_SIBLING = 'This is a sibling of a Returning student';
    const SECOND_SEM_CHANGE = 'Student has made 2nd-semester schedule changes for Period ';
    const REDUCE_TECH_ALLOWANCE = 'This student has a course that reduces Technology Allowance.';
    const REDUCE_TECH_ALLOWANCE_TTA = 'This student has a course that reduces Supplemental Learning Funds.';

    /*used to for reference of the deployment used in line 1007*/
    const MTH_SITES = ['CO','UT'];
    
    public static function printReimbursement(mth_reimbursement $reimbursement,$viewonly = false)
    {
        if($viewonly){
            self::printReimbursementDetails($reimbursement);
        } elseif ($reimbursement->editable()) {
            self::printReimbursementForm($reimbursement);
        } else {
            self::printReimbursementDetails($reimbursement);
        }
    }

    public static function printReimbursementDetails(mth_reimbursement $reimbursement)
    {
        if (!$reimbursement->viewable()) {
            echo '<div class="alert alert-danger">You cannot view this reimbursement request</div>';
            return;
        }
        ?>
        <style>
            b{
                font-weight: bold;
            }
        </style>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <?= $reimbursement->student() ?><br>
                    <?= $reimbursement->schedule_period_description() ?>
                </h4>
            </div>
            <div class="card-block">
                <p>
                <b>Type of <?=$reimbursement->is_direct_order() ? 'Direct Order' : 'Reimbursement' ?>:</b><br>
                <?= $reimbursement->type() ?>
                </p>
                <p>
                    <b>Amount:</b><br> $<?= $reimbursement->amount(true) ?>
                </p>
                <?php if($reimbursement->product_sn()){?>
                    <p>
                        <b>Product: </b><br>
                        <?=$reimbursement->product_name()?> (SN: <?=$reimbursement->product_sn()?>)
                    </p>
                <?php } ?>
                <p>
                    <b>Item Purchase Description:</b><br>
                    <?= $reimbursement->description() ?>
                </p>
                <?php if(!$reimbursement->is_direct_order() && $reimbursement->type(true) != mth_reimbursement::TYPE_DIRECT):?>
                    <p><b>Receipts:</b>
                    <?php $receiptsBySubId = mth_reimbursementreceipt::getReceiptsBySubmissionIds($reimbursement);?>
                    <?php $groupNum = 0;?>
                    <?php foreach ($receiptsBySubId as $key => $submissionsArray): ?>
                        <br/>
                        <?php if($key === 'new') : ?>
                            New Receipts:
                        <?php else : ?>
                            Submission <?= ++$groupNum ?>:
                        <?php endif; ?>
                        <?php foreach ($submissionsArray as $receipt): ?>
                            <?php $receiptFile = mth_file::get($receipt->fileId()); ?>
                            <a class="mth_reimbursement-receipt-link mr-10"
                               href="/_/mth_includes/mth_file.php?hash=<?= $receiptFile->hash() ?>"><?= $receiptFile->name() ?></a>
                        <?php endforeach ?>
                    <?php endforeach ?>
                    </p>
                <?php endif;?>
            </div>
        </div>
        <?php
    }

    public static function printReimbursementForm(mth_reimbursement $reimbursement)
    {
        if (!$reimbursement->editable()) {
            echo '<div class="alert  alert-alt alert-warning">You cannot edit this reimbursement request</div>';
            return;
        }
        if (self::$formPrinted) {
            error_log('You cannot print the reimbursement form more than once on a page');
            echo '<div class="alert  alert-alt alert-danger">Unable to print form</div>';
            return;
        }
        self::$formPrinted = true;
        core_loader::includejQueryValidate();
        if ($reimbursement->student()) {
            $parent = $reimbursement->student()->getParent();
        } else {
            $parent = mth_parent::getByUser();
        }
        if (!($year = $reimbursement->school_year())) {
            $year = mth_schoolYear::getCurrent();
        }
        $hasReceipts = $reimbursement->receipt_file_ids() ? true : false;
        ?>
        <style>
            .changed * {
                color: #990099 !important;
            }

            .type-disabled {
                color: #ccc;
            }
            .fileuploaded{
                background: #ccc;
                padding: 4px 8px;
                border-radius: 15px;
                margin-right:4px;
                display:inline;
            }
            .tech_notes{
                color:#757575;
            }

            #continue,.reimbursement_stage{
                display:none;
            }
            .conf-errror{
                margin:0px;
                display:block;
            }
        </style>
        <script>
            function mth_reimbursement_updatePeriodOptions(doIt) {
                
                if (doIt === undefined) {
                    setTimeout(function () {
                        mth_reimbursement_updatePeriodOptions(true);
                        
                    }, 500);
                    return;
                  
                }
                
                var type = $('#mth_reimbursement_type_options input:checked').val();
                var periodTypesToCHeck =[1,2,5]; //co-responds value to customs,3rdparty,mytechhigh 
                if(periodTypesToCHeck.includes(type*1)){//will only check if second_sem_changes if it is a customs,3rdparty,mytechhigh 
                    period_six_second_sem_check();
                }
                if ($.inArray(type*1,<?=json_encode(mth_reimbursement::techEnabled())?>) == -1) {
                    $('#mth_reimbursement_is_product, #mth_reimbursement_product').hide();
                    $('#for_device, #product_name, #product_sn').val('');
                    $('#mth_reimbursement_schedule_period_id_block').show();
                    if ($('#student_id').val() && type) {
                        global_waiting();
                        $.ajax({
                            url: '?get_schedule=' + $('#student_id').val() + '&type=' + type + '&reimbursement=<?=$reimbursement->id()?>',
                            success: function (data) {
                                $('#schedule_period_id').html(data);
                                global_waiting_hide();
                            },
                            complete: function () {
                                validateForm();
                            }
                        });
                    } else {
                        $('#schedule_period_id').html('<option disabled>Specify the Student and Type of Reimbursement first</option>');
                        validateForm();
                    }
                } else {
                    $('#mth_reimbursement_is_product').show();

                    $('#mth_reimbursement_schedule_period_id_block').hide();
                    $('#mth_reimbursement_is_product').find('.tech_label').hide();
                    
                    if(type == <?=mth_reimbursement::TYPE_SUPPLEMENTAL?>){
                        $('#mth_reimbursement_is_product').find('.tech_label.supplemental').show();
                    }else{
                        $('#mth_reimbursement_is_product').find('.tech_label.techallowance').show();
                    }
                }
            }
            function mth_reimbursement_updateTypeOptions() {
                if ($('#student_id').val()) {
                    global_waiting();
                    mid_year_note();
                    period_six_second_sem_check();
                    $.ajax({
                        url: '?get_available_types=1&student_id=' + $('#student_id').val() + '&reimbursement=<?=$reimbursement->id()?>',
                        success: function (data) {
                            $('#mth_reimbursement_type_options').html(data);
                            <?php  if($reimbursement->id()): ?>
                            mth_reimbursement_updatePeriodOptions(true);
                            <?php  endif; ?>
                            global_waiting_hide();
                        }
                    });
                } else {
                    $('#mth_reimbursement_type_options').html('<small style="color: #999">Select a student first</small>');
                }
            }

            function mid_year_note(){
                if ($('#student_id').val()) {
                    $.ajax({
                        url: '?check_mid_year=1&student_id=' + $('#student_id').val() + '&reimbursement=<?=$reimbursement->id()?>',
                        success: function (data) {
                            if(data==1){
                                $('#mid-year-al').fadeIn();
                            }else{
                                $('#mid-year-al').fadeOut();  
                            }
                        }
                    });
                } else {
                    $('#mid-year-al').fadeOut();    
                }
                
            }

            function period_six_second_sem_check(){//converted to check all periods
                var is_period_six = true;
                
                var type = $('#mth_reimbursement_type_options input:checked').val();
                
                //controls the warning and error if there is null values
                var periodSked =    <?= $reimbursement->schedule_period()? $reimbursement->schedule_period()->id():0?>; 
                
                var schedPeriodId = $('#schedule_period_id').val();
                if(schedPeriodId){//to address every change of period selected
                    periodSked = schedPeriodId;
                }
                
                if(is_period_six){
                    if($('#student_id').val()) {
                        $.ajax({
                            url: '?check_period_6=1&student_id=' + $('#student_id').val()+'&type='+type+'&schedule_period_id='+periodSked,
                            success: function (data) {
                                $('#mth_second_sem_change').html(data);
                            }
                        });
                    } else {
                        $('#mth_second_sem_change').html('');  
                    }
                }
            }

            function validateForm() {
                <?php  if($reimbursement->id()): ?>
                if (!$('#mth_reimbursement_form').valid()) {
                    $('#mth_reimbursement_form').submit();
                }
                <?php  endif; ?>
            }

            function changePeriod(){//onchange value on the dropdown of the periods
                period_six_second_sem_check();
            }
            
            $(function () {
                var $for_device = $('#for_device-Yes');
                setInterval(function () {
                    var reimbursementtype = $('#mth_reimbursement_type_options input:checked').val();
                    if ($for_device.prop('checked') &&   ($.inArray(reimbursementtype*1,<?=json_encode(mth_reimbursement::techEnabled())?>) != -1)) {
                        $('#mth_reimbursement_product').show();
                        $('#mth_reimbursement_product').find('.tech_label').hide();
                        if(reimbursementtype == <?=mth_reimbursement::TYPE_SUPPLEMENTAL?>){
                            $('#mth_reimbursement_product').find('.tech_label.supplemental').show();
                        }else{
                            $('#mth_reimbursement_product').find('.tech_label.techallowance').show();
                        }
                    } else {
                        $('#mth_reimbursement_product').hide();
                        $('#product_name, #product_sn').val('');
                    }
                }, 1000);
                jQuery.validator.addMethod("amount", function (value, element) {
                    return Number(value.replace(/,/g, '')) < <?=$reimbursement->invalid_amount()?>;
                }, "The amount entered is invalid");

                var receiptValidation = {
                    extension: "<?=implode('|', mth_reimbursement::allowed_receipt_file_types())?>"
                };
                var recieptMessages = {
                    required: 'You must upload at least one receipt image',
                    extension: 'File type not allowed'
                };
                $('#product_amount').keyup(function(){
                    var value = ($(this).val()).replace(/[^\d.-]/g, '');
                    $(this).val(value);
                });

                $('#savelater').click(function(e){
                    $form = $('#mth_reimbursement_form');
                    $form.attr('action',$form.attr('action')+'&savelater=1');
                    $form[0].submit();
                    return false;
                });

                $('#discard').click(function(){
                    top.swal({
                        title: "",
                        text: "Are you sure you want to discard this reimbursement?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn-warning",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function () {
                        global_waiting();
                        $.ajax({
                            url:'?discard=<?=$reimbursement->id();?>',
                            dataType: 'JSON',
                            success:function(response){
                                if(response.error == 1){
                                    alert(response.data);
                                }else{
                                    parent.location.reload();
                                }
                                global_waiting_hide();
                            },
                            error: function(){
                                global_waiting_hide();
                            }
                        });
                    });
                });

                $('#mth_reimbursement_form')
                    .submit(function () {

                        if ($(this).valid()) {
                            global_waiting();
                        }
                        return true;
                    })
                    .validate({
                        rules: {
                            type: 'required',
                            amount: {
                                amount: true
                            },
                            for_device: {
                                required: {
                                    depends: function(element){
                                        return $('#type-4').prop('checked');
                                    }
                                }
                            },
                            product_name: {
                                required: {
                                    depends: function(element){
                                        return $for_device.prop('checked');
                                    }
                                }
                            },
                            product_sn: {
                                required: {
                                    depends: function(element){
                                        return $for_device.prop('checked');
                                    }
                                }
                            },
                            product_amount: {
                                required: {
                                    depends: function(element){
                                        return $for_device.prop('checked');
                                    }
                                },
                                min: 1,
                                number: true
                            },
                            receipt1: receiptValidation,
                            receipt2: receiptValidation,
                            receipt3: receiptValidation,
                            receipt4: receiptValidation,
                            receipt5: receiptValidation,
                            receipt6: receiptValidation,
                            receipt7: receiptValidation,
                            receipt8: receiptValidation
                        },
                        messages: {
                            type: 'Please select one',
                            at_least_80: 'The Homeroom grade for your student must be at least 80% and all assignments must be submitted.',
                            product_amount: {
                                required: 'You must enter the amount you paid for this item',
                                min: 'Amount too low'
                            },
                            receipt1: recieptMessages,
                            receipt2: recieptMessages,
                            receipt3: recieptMessages,
                            receipt4: recieptMessages,
                            receipt5: recieptMessages,
                            receipt6: recieptMessages,
                            receipt7: recieptMessages,
                            receipt8: recieptMessages
                        }
                    });
                <?php  if(!$reimbursement->id()): ?>
                if ($('#student_id option').length <= 1) {
                    swal('','You must have an active student with a schedule to submit this form.','warning');
                    setTimeout(function () {
                        top.location = '/home';
                    }, 2000);
                }
                <?php  else: ?>
                mth_reimbursement_updateTypeOptions();
                <?php  endif; ?>
                $('.delete-file').click(function(){
                    deletedfile = $(this).data('hash');
                    
                    swal({
                        title: "",
                        text: "Are you sure you want to delete this file?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn-warning",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function () {
                       
                        global_waiting();
                        $.ajax({
                            url:'?deletefile='+deletedfile,
                            dataType: 'JSON',
                            success:function(response){
                                if(response.error == 1){
                                    alert(response.data);
                                }else{
                                    $('#file_'+deletedfile).fadeOut();
                                }
                                global_waiting_hide();
                            },
                            error: function(){
                                global_waiting_hide();
                            }
                        });
                    });
                });
                <?php if(!core_path::getPath()->isAdmin()):?>
                    var stage = 1;

                    function _disableform(){
                        $('#stage-button').hide();
                        $('.reimbursement_fields').addClass('shy');
                    }
                    if(!$('#student_id').val()){
                       _disableform();
                    }

                    $('#student_id').change(function(){
                        var selected_student = $(this).val();
                        var data = $(this).find('option:selected').data();
                        if((data.grade*1) < 80 || data.zeros > 0){
                            top.swal('','Reminder: Prior to submitting a Request for Reimbursement, a student’s Homeroom grade must be over 80% AND have no missing Learning Logs.','warning');
                            _disableform();
                        }else{
                            $('.reimbursement_fields').removeClass('shy');
                            if(selected_student){
                                $('#stage-button').show();
                            }else{
                                _disableform();
                            }
                        }
                    });
                    
                    $('#stage-button').click(function(){
                        $('#reimbursement_confirm').fadeIn();
                    });

                    $('.confirm-cb').change(function(){
                        if($('.confirm-cb:not(:checked)').length == 0){
                            $('#continue').show();
                        }
                    });

                    $('#continue').click(function(){
                        if(stage == 1){
                            $('#reimbursement_attachment').fadeIn();
                        }else if(stage == 2){
                            $('#reimbursement_note').fadeIn();
                            $('.reimbursement-action').fadeIn();
                            $('#continue').hide();
                        }
                        stage++;
                    });
                <?php endif;?>
            });
        </script>
        <form action="?form=<?= uniqid('mth_reimbursement_form-') ?>&reimbursement=<?= $reimbursement->id() ?>"
              method="post" id="mth_reimbursement_form" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <input type="hidden" id="mth_reimbursement_schoo_year_id" name="school_year_id" value="<?= $year->getID() ?>">
                    <input type="hidden" id="is_direct_order" name="is_direct_order" value="<?= $reimbursement->is_direct_order() ?>">
                    <div class="form-group <?= $reimbursement->field_has_changed('student_id') ? 'changed' : '' ?>">
                        <label for="student_id">Select a Student</label>
                        <select id="student_id" name="student_id" onchange="mth_reimbursement_updateTypeOptions()" required class="form-control">
                            <option></option>
                            <?php foreach ($parent->getStudents() as $student): /* @var $student mth_student */ ?>
                                <?php 
                                    if (!($schedule = mth_schedule::get($student, $year)) || !$student->isActive($year)) {
                                        continue;
                                    } 
                                    $student_homeroom = courses::getStudentHomeroom($student->getID(),$year);
                                    $grade =  $student_homeroom?$student_homeroom->getGrade():null;
                                    $zeros = $student_homeroom?$student_homeroom->getZeros():0;
                                ?>
                                <option
                                    data-grade="<?=$grade?>"
                                    data-zeros="<?=$zeros?>"
                                    value="<?= $student->getID() ?>" <?= $reimbursement->student() && $reimbursement->student()->getID() == $student->getID() ? 'selected' : '' ?>>
                                    <?= $student ?> - <?=$grade===null?'N/A':($grade.'%')?>, <?=$zeros?> missing
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if(core_path::getPath()->isAdmin() && $reimbursement->student() && ($schedule = mth_schedule::get($reimbursement->student(), $year))):?>
                    <div class="form-group">
                        <button type="button" class="btn btn-pink" 
                        onclick="window.open('/_/admin/schedules/schedule?schedule=<?=$schedule->id()?>&static=1','popUpWindow','resizable=yes,scrollbars=yes,toolbar=yes,menubar=no,location=no,directories=no, status=yes')">
                            View Schedule
                        </button>
                    </div>
                    <?php endif;?>
                    <div class="row">
                        <div class="col reimbursement_fields">
                            <div class="checkbox-custom checkbox-primary <?= $reimbursement->field_has_changed('at_least_80') ? 'changed' : '' ?>">
                                <input type="checkbox" name="at_least_80" id="at_least_80" value="1" class="disabled" CHECKED required>
                                <label for="at_least_80">
                                    I have confirmed that my student’s Homeroom grade is over 80% and that all assignments have been
                                    submitted.
                                    <small> If not, please re-submit assignments for additional points before requesting a
                                        <?=$reimbursement->is_direct_order() ? 'direct order' : 'reimbursement'?>.
                                    </small>
                                </label>
                            </div>
                            <label for="at_least_80" class="error"></label>
                            <div class="alert alert-danger alert-alt" id="mid-year-al" style="display:none">
                                <span class="alert-link">Student joined mid-year</span>
                            </div>

                            <?php
                            if ($student->getAddress()->getState() == "OR") : ?>
                                <div class="alert alert-success alert-alt mt-10" id="oregen-student-flag">
                                    <span class="alert-link">Oregon Student</span>
                                </div>
                            <?php
                            endif;
                            ?>   

                            <p <?= $reimbursement->field_has_changed('type') ? 'class="changed"' : '' ?>>
                                <label for="type">Type of <?=$reimbursement->is_direct_order()? 'Direct Order' : 'Reimbursement'?></label>
                                <span id="mth_reimbursement_type_options">
                                    <small style="color: #999">Select a student first</small>
                                </span>
                                <label for="type" class="error"></label>
                            </p>
                            <span id="mth_second_sem_change">
                            </span>

                            <div id="mth_reimbursement_is_product" style="display: none">
                                <p>
                                    <label for="for_device">
                                        <span class="tech_label supplemental" style="displat:none">Does this Supplemental Learning fund request includes a NEW (not used or refurbished) computer, laptop, Chromebook, or iPad/tablet?</span>
                                        <span class="tech_label techallowance" style="displat:none">Is this for a NEW (not used or refurbished) computer, laptop, Chromebook, or iPad/tablet?</span>
                                    </label>
                                    <div class="radio-custom radio-primary">
                                        <input type="radio" id="for_device-Yes" name="for_device" value="Yes" <?= $reimbursement->product_name() ? 'checked' : '' ?>/>
                                        <label for="for_device-Yes">
                                            Yes
                                        </label>
                                    </div>
                                    <div class="radio-custom radio-primary">
                                        <input type="radio" id="for_device-No" name="for_device" value="No" <?= $reimbursement->id() && !$reimbursement->product_name() ? 'checked' : '' ?>/>
                                        <label for="for_device-No">No</label>
                                    </div>
                                    <label for="for_device" class="error"></label>
                                </p>
                            </div>

                            <div id="mth_reimbursement_product" style="display: none;" class="<?= $reimbursement->field_has_changed('product_sn') || $reimbursement->field_has_changed('product_name') ? 'changed' : '' ?>">

                                <p style="color: red">
                                    <b>NOTE:</b>  Please only include the new computer, laptop, Chromebook, or iPad/tablet on this
                                    Request for Reimbursement form.  Submit a separate form for additional 
                                    <span class="tech_label techallowance" style="displat:none">Technology Allowance items</span>
                                    <span class="tech_label supplemental" style="displat:none">Supplemental Learning Funds requests</span>
                                </p>
                                
                                <div class="form-group">
                                    <label for="product_name">Enter Item Name</label>
                                    <small>(i.e. Lenovo Laptop, Dell Computer, iPad, etc.)</small>
                                    <input type="text" name="product_name" id="product_name" class="form-control" value="<?= $reimbursement->product_name() ?>">
                                </div>
                                <div class="form-group">
                                    <label for="product_sn">Enter Item Serial Number</label>
                                    <input type="text" name="product_sn" id="product_sn" class="form-control" value="<?= $reimbursement->product_sn() ?>">
                                </div>
                                <div class="form-group">
                                    <label for="product_amount">Amount Paid for Item</label>
                                    <small>(excluding taxes; note - this amount might be more than you are requesting for reimbursement)</small>
                                    <div class="input-group">
                                        <span class="input-group-addon">$</span>
                                        <input type="text" name="product_amount" class="form-control" id="product_amount" style="max-width: 100px"
                                            value="<?= $reimbursement->product_amount() ?>">
                                    </div>
                                </div>
                            </div>

                            <div id="mth_reimbursement_schedule_period_id_block" class="form-group <?= $reimbursement->field_has_changed('schedule_period_id') ? 'changed' : '' ?>">
                                <label for="schedule_period_id">Select a Period</label>
                                <select name="schedule_period_id" id="schedule_period_id" class="form-control" onChange="changePeriod()" required>
                                    <option></option>
                                    <option disabled>Specify the Student and Type of Reimbursement first</option>
                                </select>
                            </div>
                            <div class="form-group <?= $reimbursement->field_has_changed('amount') ? 'changed' : '' ?>">
                                <label for="amount"><?=$reimbursement->is_direct_order() ? 'Estimated Amount' : 'Total Amount Requested'?></label>
                                
                                <div class="input-group reimburse_link_content">
                                    <?php if(core_path::getPath()->isAdmin()):?>
                                    <span class="input-group-addon">
                                        <div class="checkbox-custom checkbox-primary">
                                            <input type="checkbox" class="reimburse_link_custom reimburse_link" >
                                            <label></label>
                                        </div>
                                    </span>
                                    <?php endif;?>
                                    <span class="input-group-addon">$</span>
                                    <input type="text" name="amount"  class="form-control" id="amount" style="max-width: 100px"
                                            value="<?= $reimbursement->amount(true) ?>" required>
                                </div>
                            </div>
                            <?php if($reimbursement->is_direct_order()): ?>
                            <div class="form-group mb-10 <?= $reimbursement->field_has_changed('direct_order_confirmation') ? 'changed' : '' ?>">
                                <label for="direct_order_confirmation">Order Confirmation</label>
                                <textarea class="form-control" name="direct_order_confirmation" rows="3" id="direct_order_confirmation"><?= $reimbursement->direct_order_confirmation() ?></textarea>
                            </div>
                            <?php endif;?>
                            <?php if(!core_path::getPath()->isAdmin()): //desc here if not admin?>
                            <div class="form-group mb-10 <?= $reimbursement->field_has_changed('description') ? 'changed' : '' ?>">
                                <label for="description">Additional Information</label>
                                <textarea class="form-control" name="description" rows="9" id="description"><?= $reimbursement->description(false) ?></textarea>
                            </div>
                            <?php endif;?>
                        </div>
                    </div>
                </div> 
                <!-- end first colum -->
                <div class="col-md-6">
                   <div class="reimbursement_fields">
                        <?php if(core_path::getPath()->isAdmin()): //desc here if admin?>
                        <div class="form-group mb-10 <?= $reimbursement->field_has_changed('description') ? 'changed' : '' ?>">
                            <label for="description">Additional Information</label>
                            <textarea class="form-control" name="description" rows="9" id="description"><?= $reimbursement->description(false) ?></textarea>
                        </div>
                        <?php endif;?>
                       <?php if($reimbursement->is_direct_order()):?>
                           <label for="direct_order_list_provider">Wishlist Provider</label>
                           <select id="direct_order_list_provider" name="direct_order_list_provider" required class="form-control">
                               <option></option>
                               <?php foreach(["Amazon", "Rainbow Resource"] as $provider):?>
                                   <option value="<?=$provider?>" <?=$provider == $reimbursement->direct_order_list_provider() ? 'selected' : ''?>><?=$provider?></option>
                               <?php endforeach;?>
                           </select>
                           <br />
                           <div class="form-group mb-10 <?= $reimbursement->field_has_changed('direct_order_list_link') ? 'changed' : '' ?>">
                               <label for="direct_order_list_link">Wishlist Link</label>
                               <textarea class="form-control" name="direct_order_list_link" rows="3" id="direct_order_list_link"><?= $reimbursement->direct_order_list_link() ?></textarea>
                           </div>
                       <?php else:?>
                        <div <?= $reimbursement->field_has_changed('receipts') ? 'class="changed"' : '' ?>>
                             <?php if(!core_path::getPath()->isAdmin()):?>
                                <div class="text-center">
                                    <a class="btn btn-success" style="color:#fff" id="stage-button"> 
                                        <b for="receipt1">Scan / upload related receipts</b>
                                        <small>Allowed file types: <?= implode(', ', mth_reimbursement::allowed_receipt_file_types()); ?><br>
                                            <b>Less than 25MB</b>
                                        </small>
                                    </a>
                                </div>
                            <?php else:?>
                                <label for="receipt1">Scan / upload related receipts</label>
                                <small>Allowed file types: <?= implode(', ', mth_reimbursement::allowed_receipt_file_types()); ?><br>
                                    <b>Less than 25MB</b>
                                </small>
                            <?php endif;?>
                            <br>
                            <?php if(!core_path::getPath()->isAdmin()):?>
                                <div  id="reimbursement_confirm" class="<?=$reimbursement->id()?'':'reimbursement_stage'?>">
                                    <b>Per the details in <a href="https://www.mytechhigh.com/parentlink/" target="_blank">Parent Link</a>, and to help ensure that this Request for Reimbursement is complete, please confirm the following before uploading any receipts:</b>
                                    <label class="error conf-errror" for="confirm_receipt"></label>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" class="confirm-cb" name="confirm_receipt" id="confirm_receipt"  <?= $reimbursement->confirm_receipt() ? 'checked' : '' ?>  value="1" required>
                                        <label>
                                            <?= cms_page::getDefaultPageContent('Reimbursement Confirm 1', cms_content::TYPE_HTML); ?>
                                        </label>
                                    </div>
                                    <label class="error conf-errror" for="confirm_related"></label>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" class="confirm-cb" name="confirm_related" id="confirm_related" <?= $reimbursement->confirm_related() ? 'checked' : '' ?>  value="1" required>
                                        <label><?= cms_page::getDefaultPageContent('Reimbursement Confirm 2', cms_content::TYPE_HTML); ?></label>
                                    </div> 
                                    <label class="error conf-errror" for="confirm_dated"></label>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" class="confirm-cb" name="confirm_dated" id="confirm_dated" <?= $reimbursement->confirm_dated() ? 'checked' : '' ?>  value="1" required>
                                        <label>  <?= cms_page::getDefaultPageContent('Reimbursement Confirm 3', cms_content::TYPE_HTML); ?></label>
                                    </div>
                                    <label class="error conf-errror" for="confirm_provided"></label>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox"  class="confirm-cb" name="confirm_provided" id="confirm_provided"  <?= $reimbursement->confirm_provided() ? 'checked' : '' ?>  value="1" required>
                                        <label> <?= cms_page::getDefaultPageContent('Reimbursement Confirm 4', cms_content::TYPE_HTML); ?></label>
                                    </div>
                                    <label class="error conf-errror" for="confirm_allocation"></label>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" class="confirm-cb" name="confirm_allocation" id="confirm_allocation" <?= $reimbursement->confirm_allocation() ? 'checked' : '' ?>  value="1" required>
                                        <label>  <?= cms_page::getDefaultPageContent('Reimbursement Confirm 5', cms_content::TYPE_HTML); ?></label>
                                    </div>
                                    <label class="error conf-errror" for="confirm_update"></label>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox"  class="confirm-cb" name="confirm_update" id="confirm_update" <?= $reimbursement->confirm_update() ? 'checked' : '' ?>  value="1" required>
                                        <label>  <?= cms_page::getDefaultPageContent('Reimbursement Confirm 6', cms_content::TYPE_HTML); ?></label>
                                    </div>
                                    
                                </div>
                            <?php endif;?>
                            <div class="mt-10 mb-10">
                                <?php $receiptsBySubId = mth_reimbursementreceipt::getReceiptsBySubmissionIds($reimbursement);?>
                                <?php $groupNum = 0;?>
                                <?php foreach ($receiptsBySubId as $key => $submissionsArray): ?>
                                <div class="row mt-10">
                                    <?php if($key === 'new') : ?>
                                        New Receipts:
                                    <?php else : ?>
                                        Submission <?= ++$groupNum ?>:
                                    <?php endif; ?>
                                        <?php foreach ($submissionsArray as $receipt): ?>
                                            <?php $receiptFile = mth_file::get($receipt->fileId()); ?>
                                            <div class="fileuploaded mb-10" id="file_<?= $receiptFile->hash() ?>">
                                                <a class="mth_reimbursement-receipt-link"
                                                   href="/_/mth_includes/mth_file.php?hash=<?= $receiptFile->hash() ?>">
                                                    <!-- <a class="mth_reimbursement-receipt-link" href="#" onclick="top.global_popup_iframe('fileviewer','/_/user/fileviewer?file=<?= $receiptFile->id() ?>')"> -->
                                                    <?= $receiptFile->name() ?>
                                    </a>
                                    <?php if(core_path::getPath()->isAdmin()):?>
                                                    <a class="badge badge-secondary badge-round delete-file"
                                                       data-hash="<?= $receiptFile->hash() ?>" style="color:#fff"><i
                                                                class="fa fa-times"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <span style="display: block"></span>
                            <label for="receipt1" class="error"></label>
                            <?php if(!core_path::getPath()->isAdmin()):?>
                                <div  id="reimbursement_attachment" class="<?=$reimbursement->id()?'':'reimbursement_stage'?>">
                                    <input type="file" name="receipt1"
                                        id="receipt1" <?= $hasReceipts && !$reimbursement->require_new_receipt() ? '' : 'required' ?>>
                                    <input type="file" name="receipt2">
                                    <input type="file" name="receipt3">
                                    <input type="file" name="receipt4">
                                    <input type="file" name="receipt5">
                                    <input type="file" name="receipt6">
                                    <input type="file" name="receipt7">
                                    <input type="file" name="receipt8">
                                </div>
                                <br>
                            <?php endif;?>
                        </div>
                       <?php endif;?>


                        <?php if (!core_path::getPath()->isAdmin()): ?>
                            <div id="reimbursement_note" class="<?=$reimbursement->id()?'':'reimbursement_stage'?>">
                                <p>By submitting this form, I acknowledge that my student is not enrolled in any other Utah
                                    school (unless otherwise approved) and will stay enrolled as a full-time, home-based,
                                    distance education student of My Tech High’s Partner School throughout the entire school
                                    year. I understand that if my student withdraws at any time prior to the last day of
                                    instruction or fails to demonstrate active participation towards the approved educational
                                    plan, including all required testing (or opt-out form), I am personally obligated to return
                                    or repay all costs associated with any and all curriculum, kits, software, devices, equipment,
                                    and/or technology
                                    received either directly from My Tech High or through a reimbursement process.</p>
                                <p>
                                <div class="alert bg-info">
                                    <b>NOTE</b>:  Any new computer, Chromebook, or iPad/tablet which is reimbursed with a student’s Technology Allowance funds remains property of the school’s program for three years.  If a student withdraws from the program after year one or year two, the parent will be asked to return the computer, Chromebook, or iPad/tablet or re-pay the depreciated value of the device.
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (core_user::isUserAdmin() && $reimbursement->id()): ?>
                            <div class="card mt-10">
                                <div class="card-block">
                                    <fieldset>
                                        <legend>Admin Only</legend>
                                        <div class="form-group">
                                            <label>
                                                Note
                                            </label>
                                            <textarea class="form-control" name="familynote"><?=$parent->note()?$parent->note()->getNote():''?></textarea>
                                        </div>
                                        <p>
                                            <label for="status">Status</label>
                                            <select name="status" id="status" class="form-control">
                                                <?php foreach (mth_reimbursement::availableStatuses() as $statusID => $status): ?>
                                                    <option
                                                        value="<?= $statusID ?>" <?= $reimbursement->status(true) == $statusID ? 'selected' : '' ?>>
                                                        <?= $status ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </p>
                                        <?php if ($reimbursement->date_paid()): ?>
                                            <p>
                                                Date paid: <?= $reimbursement->date_paid('m/d/Y') ?>
                                            </p>
                                        <?php endif; ?>
                                    </fieldset>
                                </div>
                            </div>
                            
                        <?php endif; ?>
                        <?php if(!$reimbursement->id()):?>
                        <button class="btn btn-success btn-round" type="button" id="continue">Continue</button>
                        <?php endif;?>
                        <p class="<?=!core_path::getPath()->isAdmin() && !$reimbursement->id()?'reimbursement_stage reimbursement-action':''?>">
                            <button type="submit" class="btn btn-round btn-primary" value="1">
                            <?= core_user::isUserAdmin() && $reimbursement->id() ? 'Save' : 'Submit' ?>
                            </button>
                            <button type="button" class="btn btn-round btn-success" id="savelater" name="savelater" value="2">
                                    Save for Later
                            </button>
                            <?php if($reimbursement->isDiscardable()):?>
                            <button type="button" class="btn btn-round btn-danger" id="discard" name="discard">
                                    Discard
                            </button>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <!-- end second column -->
            </div>
        </form>
        <?php
    }

    public static function handleFormAjax()
    {
        if(req_get::bool('deletefile')){
            $deleted = ['error'=>1,'data'=>'File not found'];

            if(($file = mth_file::getByHash(req_get::txt('deletefile'))) && $file->delete()){
                if($delete = mth_reimbursement::delete_reciept($file->id())){
                    $deleted = ['error'=>0,'data'=>'Deleted'];
                }else{
                    $deleted = ['error'=>1,'data'=>'Error deleting file'];
                }
            }
            echo  json_encode($deleted);
            exit();
            return;
        }

        if(req_get::bool('discard')){
            $deleted = ['error'=>1,'data'=>'Reimbursement not found'];
            if($reimbursement = mth_reimbursement::get(req_get::int('discard'))){
                if ($reimbursement->delete()) {
                    core_notify::addMessage('Reimbursement discarded');
                    $deleted = ['error'=>0,'data'=>'Deleted'];
                } else {
                    $deleted = ['error'=>1,'data'=>'Unable to delete reimbursement request'];
                }
            }
            echo  json_encode($deleted);
            exit();
            return;
        }
        
        if (req_get::bool('reimbursement')) {
            $reimbursement = mth_reimbursement::get(req_get::int('reimbursement'));
        } else {
            $reimbursement = new mth_reimbursement();
        }
        if (!$reimbursement || !$reimbursement->editable()) {
            if (req_get::bool('get_schedule') || req_get::bool('get_available_types')) {
                exit('You cannot edit this reimbursement request');
            }
            return;
        }
        if ($reimbursement->student()) {
            $parent = $reimbursement->student()->getParent();
        } else {
            $parent = mth_parent::getByUser();
        }
        if ($reimbursement->school_year()) {
            $year = $reimbursement->school_year();
        } else {
            $year = mth_schoolYear::getCurrent();
        }

        if (req_get::bool('get_schedule')) {
            if (!($student = mth_student::getByStudentID(req_get::int('get_schedule')))
                || !($schedule = mth_schedule::get($student, $year))
                || $student->getParentID() != $parent->getID()
            ) {
                die('<option disabled>Invalid student selection</option>');
            }
            echo '<option></option>';
            $count = 0;
            if ($reimbursement->student_id() != $student->getID()) {
                $reimbursement->set_student_year($student, $year);
            }
            if (req_get::int('type') && req_get::int('type') != $reimbursement->type(true)) {
                $reimbursement->set_type(req_get::int('type'));
            }
            $availableSchedulePeriodIDs = $reimbursement->available_schedule_period_ids();
            $mergeDisplayed = 0;//flag if there is already a merge period displayed
            
            while ($schedulePeriod = $schedule->eachPeriod(false, 'reimbusement_form')) {
                if ($schedulePeriod->none()
                    || !in_array($schedulePeriod->id(), $availableSchedulePeriodIDs)
                ) {
                    continue;
                }
                $count++;
                
                if(in_array($schedulePeriod->period_number(),$reimbursement->merge_custom_periods()) 
                   && $schedulePeriod->course_type(true)==3){//will only gets to this part if this custom built
                     if( $mergeDisplayed == 0){//double if so that it will detects if it is a merge periods should gets here
                            ?>
                            <option value="<?= $schedulePeriod->id() ?>"
                                <?php /*check if the reimbursement schedule_period_id is in the array of the merge schedule periods*/ ?>
                                <?= in_array($reimbursement->schedule_period_id(),$reimbursement->mergeCustomPeriodIDS($schedule)) ? 'selected' : '' ?>>
                                <?= $reimbursement->get_merged_period_description($schedulePeriod) ?>
                            </option>
                            <?php
                            $mergeDisplayed++;
                        }
                }else{//this else is for all not merged periods && not custom built
                    ?>
                        <option value="<?= $schedulePeriod->id() ?>"
                            <?= $reimbursement->schedule_period_id() == $schedulePeriod->id() ? 'selected' : '' ?>>
                            <?= $reimbursement->get_merged_period_description($schedulePeriod) ?>
                        </option>
                    <?php
                }
            }
            if (!$count) {
                ?>
                <option value="" selected>None found for that type</option>
                <?php
            }
            exit();
        }

        if(req_get::bool('check_mid_year')){
            $check_retval = 0;
            if (($student = mth_student::getByStudentID(req_get::int('student_id')))
                && $student->isMidYear($year)
            ){
                $check_retval = 1;
            }
            echo $check_retval;
            exit;
        }

        if(req_get::bool('check_period_6')){
            if(req_get::int('type') ==1 || req_get::int('type') ==2 ||req_get::int('type') ==5){
                if(core_path::getPath()->isAdmin()){
                    $periodsChanged = [];
                    $typeIsFound = false;
                    $foundChange = false;
                    $types = [];
                    $schedulePeriod=0;
                    $customPeriods =[];
                    //get period specific on change of the drop down of periods
                    if(req_get::int('schedule_period_id')){
                        $schedulePeriodValue = mth_schedule_period::getByID(req_get::int('schedule_period_id'));
                        $schedulePeriod = $schedulePeriodValue->period_number();
                    }

                    //check all periods with changes in second sem
                    if (($student = mth_student::getByStudentID(req_get::int('student_id'))) &&  ($schedule = $student->schedule($year))){
                        for($periodCnt=2; $periodCnt < 8; $periodCnt++){
                            if($schedule->hasDifferentSemesterCourse($periodCnt)){
                                $sem1 = $schedule->getPeriod($periodCnt,false);
                                $sem2 = $schedule->getPeriod($periodCnt,true);
                                if(!in_array($sem1->course_type(),$types)){
                                    $types[]= $sem1->course_type();
                                }
                                if(!in_array($sem2->course_type(),$types)){
                                    $types[] = $sem2->course_type();
                                }
                                if($sem1->course_type() == "Custom-built" || $sem2->course_type() == "Custom-built"){
                                    $customPeriods[] = $periodCnt;
                                }
                                $periodsChanged[] = $periodCnt;
                            }
                        }
                    }
                    
                    //$merge_customs_period = [2,3,4,6];
                    $merge_customs_period = $reimbursement->merge_custom_periods();
                    $c = array_intersect($merge_customs_period,  $periodsChanged);//checks if the periods with changes are in merge customs period

                    //used to check if there is really a change in a certain periods in the 2nd sem
                    if(count($periodsChanged) > 0  && count($types) > 0){
                        if((in_array("Custom-built",$types) && req_get::int('type') ==1)){
                            if(in_array($schedulePeriod,$periodsChanged) || //checks if this specific period has change
                                ( in_array($schedulePeriod,$merge_customs_period) &&count($c) > 0)//checks if this period is a merged period and there is a change in one period
                            ){
                                $foundChange = true;
                            }
                        }else{
                            if(in_array($schedulePeriod,$periodsChanged)){
                                $foundChange = true;
                            }
                        }
                    }

                    if($foundChange){
                        echo '<div class="alert alert-alt alert-danger bg-warning tech_notes">';
                        echo self::SECOND_SEM_CHANGE;
                        $List = implode(',',$customPeriods);
                        if(in_array("Custom-built",$types) && req_get::int('type') ==1 
                            && in_array($schedulePeriod,$merge_customs_period)){ //period not included in merged period should be individual flag
                            print_r($List);
                        }else{
                            echo $schedulePeriod;
                        }
                        echo '.</div>';
                    }
                }
            }

            exit();
        }

        if (req_get::bool('get_available_types')) {
            if (!($student = mth_student::getByStudentID(req_get::int('student_id')))
                || $student->getParent() != $parent
            ) {
                die('<span style="color:red">Invalid student</span>');
            }
            if ($reimbursement->student_id() != $student->getID()) {
                $reimbursement->set_student_year($student, $year);
            }
            $types = $reimbursement->is_direct_order() ? mth_reimbursement::direct_order_type_labels() : mth_reimbursement::type_labels();

            foreach ($types as $type => $label) {
                $disabled = !in_array($type, $reimbursement->available_types());
                if($disabled){
                    continue;
                }
                ?>
                <div class="radio-custom radio-primary">
                    <input type="radio" name="type" id="type-<?= $type ?>" value="<?= $type ?>"
                            <?= $reimbursement->type(true) == $type ? 'checked' : '' ?>
                            <?= $disabled ? 'disabled' : 'onclick="mth_reimbursement_updatePeriodOptions()"' ?>>
                    <label for="type-<?= $type ?>" class="<?= $disabled ? 'type-disabled' : '' ?>"
                        <?= !$disabled ? 'onclick="mth_reimbursement_updatePeriodOptions()"' : '' ?>>
                        <?= mth_reimbursement::type_label($type) ?>
                        <?= $type === mth_reimbursement::TYPE_SOFTWARE ? ' (Only for Game Design and LEGO Robotics WeDo 2.0)' : '' ?>
                        <?= mth_reimbursement::TYPE_TECH == $type && $year->reimburse_tech_open() > time() ? ' (Opens ' . $year->reimburse_tech_open('F j') . ')' : '' ?>
                    </label>
                </div>
                <?php
            }

            if(core_path::getPath()->isAdmin() && $reimbursement->type(true) == mth_reimbursement::TYPE_TECH){
                echo '<div class="alert alert-alt alert-danger bg-warning tech_notes">';
                $schedule = $student->schedule($year);
                $current_year = mth_schoolYear::getCurrent();
                $previous_year = mth_schoolYear::getCurrent()->getPreviousYear();

                $notes = [];

                /*MTH has only Scenario for reduce tech allowance*/
                if($student->hasReduceTechAllowance($schedule->id())){
                    if(in_array($_SERVER['STATE'], self::MTH_SITES, TRUE)){
                        $notes[] = self::REDUCE_TECH_ALLOWANCE;
                    }
                }

                if($period7 = $schedule->getPeriod(7, $schedule->schoolYear()->getSecondSemOpen() <= time())){
                    if(!$period7->period()->required()) {
                        if($period7->course_type(true) == mth_schedule_period::TYPE_MTH){
                            $notes[] = self::HAS_DIRECT;
                        }elseif($period7->course_type(true) == mth_schedule_period::TYPE_CUSTOM){
                            $notes[] = self::HAS_CUSTOM;
                        }elseif($period7->course_type(true) == mth_schedule_period::TYPE_TP){
                            $notes[] = self::HAS_TP;
                        }
                    }
                } else {
                    $notes[] = self::NO_PERIOD;
                }

                if($schedule->hasAllowanceCourse()){
                    $notes[] = self::HAS_ALLOWANCE;
                }

                if($student->getGradeLevelValue($current_year->getID()) == 'K'){
                    $notes[] = self::KINDER;
                }

                if($student->getStatus($previous_year)){
                    $notes[] = self::RETURNING_STUDENT;
                }else{
                    $notes[] = self::NEW_STUDENT;
                    if($student->hasActiveSiblings([$previous_year])){
                        $notes[] = self::RETURNING_SIBLING;
                    }
                }

                echo implode('<br>',$notes);

                echo '</div>';
            }

            if(!in_array($_SERVER['STATE'], self::MTH_SITES, TRUE)){
                if(core_path::getPath()->isAdmin() && $reimbursement->type() == "Supplemental Learning Funds"){
                    $schedule = $student->schedule($year);
                    
                    /*TTA only scenario for tech allowance*/
                    if($student->hasReduceTechAllowance($schedule->id())){
                        echo '<div class="alert alert-alt alert-danger bg-warning tech_notes">';
                        echo self::REDUCE_TECH_ALLOWANCE_TTA;
	                     echo '</div>';
                    }
                }
            }

            exit();
        }

        if(req_get::is_set('get_student_schedule')){
            if($student = mth_student::getByStudentID(req_get::int('get_student_schedule'))){
                if($schedule = mth_schedule::get($student, $year)){
                    $parent = $student->getParent();
                    ?>
                    <table style="width:100%;margin-left:8px;">
                        <tr>
                            <td>
                            <div>
                                <?= $student ?>
                                <?= $schedule->schoolYear() ?>
                            </div>
                            <h1 style="margin: 0">Schedule</h1>
                            <?php if ($schedule->isAccepted()): ?>
                                <div style="font-size: 20px; color: green; line-height: 25px; height: 40px;">Approved</div>
                            <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <h3 style="margin: 0">Student</h3>
                                    <div>
                                        <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?student=<?= $student->getID() ?>')">
                                            <?= $student ?></a></div>
                                    <div><?= $student->getGender() ?></div>
                                    <div>
                                        <?= $student->getGradeLevel(true, false, $schedule->schoolYear()); ?>
                                        <small>(<?= $schedule->schoolYear() ?>)</small>
                                    </div>
                                    <div>Diploma: <?= $student->diplomaSeeking() ? 'Yes' : 'No'; ?></div>
                                    <div><?= $student->getSchoolOfEnrollment() ?></div>
                                    <div>SPED: <?= $student->specialEd(true) ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="float: left; margin-right: 20px">
                                    <?php if (($scheduleIDs = mth_schedule::getStudentScheduleIDs($student))): ?>
                                        <h3 style="margin: 0">Student's Schedules</h3>
                                        <?php foreach ($scheduleIDs as $yearID => $schedule_id): ?>
                                            <a onclick="editSchedule(<?= $schedule_id ?>)" style="display: block;">
                                                <?= mth_schoolYear::getByID($yearID) ?></a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <h3 style="margin: 0">Parent</h3>
                                <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?parent=<?= $parent->getID() ?>')">
                                        <?= $parent ?>
                                </a>
                                <hr>
                                <?php if (($enrollment = mth_canvas_enrollment::getBySchedulePeriod($schedule->getPeriod(1)))
                                    && $enrollment->id()
                                ): ?>
                                    <div>Last Activity: <?= $enrollment->getLastAcitivity('j M Y') ?></div>
                                    <div>Homeroom Grade: <span id="homeroomGradeHolder"><img src="/_/includes/img/loading.gif"
                                                                                                style="height: 12px"></span></div>
                                    <div># of Zeros: <span id="homeroomZeroCountHolder"><img src="/_/includes/img/loading.gif"
                                                                                                style="height: 12px"></span></div>
                                    <script>
                                        $(function () {
                                            $('#homeroomGradeHolder').load('/_/admin/schedules/ajax?getHomeroomGrade=1&schedule=<?=$schedule->id()?>');
                                            $('#homeroomZeroCountHolder').load('/_/admin/schedules/ajax?getHomeroomZeroCount=1&schedule=<?=$schedule->id()?>');
                                        });
                                    </script>
                                <?php endif; ?>
                            <td>
                        </tr>
                    </table>
                    
                    
                    
                <hr style="clear: both;">
                <?php
                    mth_views_schedules::entireSchedule($schedule);
                }
            }
            exit();
        }
    }

    /**
     *
     * @return mth_reimbursement The submitted (or saved) reimbuserment request.
     */
    public static function handleFormSubmission()
    {
        if (!req_get::bool('form')) {
            return NULL;
        }
       
        core_loader::formSubmitable(req_get::txt('form')) || die();

        $savelater = req_get::bool('savelater');

        if (req_get::bool('reimbursement')) {
            $reimbursement = mth_reimbursement::get(req_get::int('reimbursement'));
        } else {
            $reimbursement = new mth_reimbursement();
        }
        if ($reimbursement->school_year()) {
            $year = $reimbursement->school_year();
        } else {
            $year = mth_schoolYear::getCurrent();
        }
        if (!($student = mth_student::getByStudentID(req_post::int('student_id')))) {
            exit('Invalid Student');
        }

        if (!$reimbursement || !$reimbursement->editable()) {
            exit('You cannot edit this reimbursement request');
        }

        $reimbursement->set_type(req_post::int('type'));
        if ($reimbursement->isTechEnabled() && req_post::txt('for_device')!=='No') {
            if(!(req_post::bool('product_name') && req_post::bool('product_sn') && req_post::bool('product_amount'))){
                exit('Incomplete form');
            }
            $reimbursement->set_product_name(req_post::txt('product_name'));
            $reimbursement->set_product_sn(req_post::txt('product_sn'));
            $reimbursement->set_product_amount(req_post::float('product_amount'));
        }elseif($reimbursement->isTechEnabled() && req_post::txt('for_device')==='No'){
            $reimbursement->set_product_name(null);
            $reimbursement->set_product_sn(null);
            $reimbursement->set_product_amount(null);
        }
        $reimbursement->set_schedule_period_id(req_post::int('schedule_period_id'))
        || $reimbursement->set_student_year($student, $year);
        $reimbursement->set_at_least_80(req_post::bool('at_least_80'));
        $reimbursement->set_confirm_receipt(req_post::bool('confirm_receipt'));
        $reimbursement->set_confirm_related(req_post::bool('confirm_related'));
        $reimbursement->set_confirm_dated(req_post::bool('confirm_dated'));
        $reimbursement->set_confirm_provided(req_post::bool('confirm_provided'));
        $reimbursement->set_confirm_allocation(req_post::bool('confirm_allocation'));
        $reimbursement->set_confirm_update(req_post::bool('confirm_update'));

        $reimbursement->set_amount(req_post::float('amount'));
        $reimbursement->set_description(req_post::multi_txt('description'));

        if (core_user::isUserAdmin()) {
            $reimbursement->set_status(req_post::int('status'));
            if($parent = $reimbursement->student()->getParent()){
                if($parent->note()){
                    $parent->note()->setNote($_POST['familynote']);
                }else{
                    mth_familynote::create($parent,$_POST['familynote']);
                }
            }
        }

        if (!$reimbursement->save_receipt_files(array('receipt1', 'receipt2', 'receipt3', 'receipt4', 'receipt5', 'receipt6', 'receipt7', 'receipt8')) && !$savelater) {
            $reimbursement->save();
            return $reimbursement;
        }

        $reimbursement->set_is_direct_order(req_post::int('is_direct_order'));
        $reimbursement->set_direct_order_list_provider(req_post::txt('direct_order_list_provider'));
        $reimbursement->set_direct_order_list_link(req_post::raw('direct_order_list_link'));
        $reimbursement->set_direct_order_confirmation(req_post::raw('direct_order_confirmation'));

        if($savelater){
            $reimbursement->set_save(true);
            $reimbursement->set_draft_status();
            $reimbursement->save();
            return $reimbursement;
        }

        if ($reimbursement->submitable()) {
            $reimbursement->submit();
        } else {
            $reimbursement->save();
        }
        return $reimbursement;
    }
}
