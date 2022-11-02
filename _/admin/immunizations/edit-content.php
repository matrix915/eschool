<?php

if (req_get::bool('immunization_id')) {
    ($immunization = mth_immunization_settings::getByID(req_get::int('immunization_id'))) || die('immunization not found');

    if (req_get::bool('delete') && $immunization->delete()) {
        core_notify::addMessage('Immunization Deleted');
        die('<html><head><script>top.location.reload(true);</script></head></html>');
    }
} else {
    $immunization = new mth_immunization_settings();
}

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || die();
    $immunization->setTitle(req_post::txt('title'));
    $immunization->setExemptUpdate(req_post::bool('exempt_update'));
    $immunization->setMinGradeLevel(req_post::txt('min_grade_level'));
    $immunization->setMaxGradeLevel(req_post::txt('max_grade_level'));
    $immunization->setMinSchoolYearRequired(req_post::int('min_school_year'));
    $immunization->setMaxSchoolYearRequired(req_post::int('max_school_year'));
    $immunization->setImmunityAllowed(req_post::bool('immunity_allowed'));
    $immunization->setLevelExemptUpdate(req_post::txt_array('exempt_update'));
    $immunization->setConsecutiveVaccine(req_post::int('consecutive_vaccine'));
    $immunization->setMinSpacingInterval(req_post::int('min_spacing_interval'));
    $immunization->setMinSpacingDate(req_post::txt('min_spacing_date'));
    $immunization->setMaxSpacingInterval(req_post::int('max_spacing_interval'));
    $immunization->setMaxSpacingDate(req_post::txt('max_spacing_date'));
    $immunization->setEmailUpdateTemplate(req_post::txt('email_update'));
    $immunization->setTooltip(req_post::txt('tooltip'));
   
    if ($immunization->save()) {
        core_notify::addMessage('Immunization Saved');
        core_loader::reloadParent();
    } else {
        core_notify::addError('Unable to save Immunization!');
        core_loader::redirect(req_post::txt('button') == 'Save/New'
            ? '?immunization_id=' . $immunization->getID()
            : '?immunization_id=' . $immunization->getID());
    }
}

core_loader::isPopUp();
core_loader::addCssRef('timecss', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.css');
core_loader::printHeader();
core_loader::includejQueryUI();
$selected_subject = null;
?>
    <button  type="button"  class="iframe-close btn btn-secondary btn-round" onclick="top.location.reload(true)">Close</button>
    <h2><?= $immunization->getID() ? 'Edit' : 'New' ?> Immunization</h2>
    <form action="?form=<?= uniqid('mth_immunization-form') ?><?= $immunization->getID() ? '&immunization_id=' . $immunization->getID() : '' ?>" method="post">
          <div class="card">
            <div class="card-block">
                <div style="width: 50%; float:left;">
                    <div class="form-group">
                        <label for="">Immunization</label>
                        <input type="text" name="title" value="<?= $immunization->getTitle() ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="">Minimum Grade Level</label>
                        <select name="min_grade_level" id="min_grade_level" class="form-control grade_level">
                            <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                                <option value="<?= $grade_level ?>"
                                    <?= $immunization->getMinGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="">Maximum Grade Level</label>
                        <select name="max_grade_level" id="max_grade_level" class="form-control grade_level">
                            <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                                <option value="<?= $grade_level ?>"
                                    <?= $immunization->getMaxGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="">Minimum School Year Required</label>
                        <select name="min_school_year" id="min_school_year" class="form-control" disabled>
                            <option value="" selected>no minimum</option>
                            <?php foreach (mth_schoolYear::getUpcomming() as $year): ?>
                                <option value="<?= $year->getId() ?>"><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="">Maximum School Year Required</label>
                        <select name="max_school_year" id="max_school_year" class="form-control" disabled>
                            <option value="" selected>no maximum</option>
                            <?php foreach (mth_schoolYear::getUpcomming() as $year): ?>
                                <option value="<?= $year->getId() ?>"><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="immunity_allowed" id="immunity_allowed" <?= $immunization->isImmunityAllowed() ? 'checked' : '' ?>>
                        <label for="immunity_allowed">
                           Immunity Allowed
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="tooltip">
                           Note/Tooltip
                        </label>
                        <textarea class="form-control" name="tooltip" id="tooltip" style="width: 100%; min-height: 6rem;"><?= $immunization->getTooltip() ?></textarea>
                    </div>
                </div>
                
                <div style="width: 50%; float:left; padding-left: 1rem;">
                    
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="exempt_update" id="exempt_update" <?= $immunization->exemptUpdate() ? 'checked' : '' ?>>
                        <label for="exempt_update">
                           Require Update if Exempt
                        </label>
                        <div style="padding-left: 1rem;" id="exempt_grade_label_container">
                            <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): 
                                $exempt_level_status = '';
                                if($immunization->getLevelExemptUpdate() != NULL && in_array($grade_level, $immunization->getLevelExemptUpdate())){
                                    $exempt_level_status = 'checked';
                                }
                                ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" class="exempt_grade_level" name="exempt_update[]" value="<?= $grade_level ?>" <?= $exempt_level_status ?>>
                                    <label><?= $grade_desc ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="consecutive_vaccine_trigger" id="consecutive_vaccine_trigger" <?= $immunization->getConsecutiveVaccine() ? 'checked' : '' ?>>
                        <label for="consecutive_vaccine_trigger">
                           Consecutive Vaccine
                        </label>
                        <div style="padding-left: 1rem;" id="consecutive_vaccine_container">
                            <select name="consecutive_vaccine" id="consecutive_vaccine" class="form-control grade_level">
                            <option value="0">-- Select Immunization --</option>
                                <?php 
                                foreach (mth_immunization_settings::getEach() as $settings): 
                                    if($immunization->getID() == $settings->getID()){
                                        continue;
                                    }
                                ?>
                                    <option value="<?= $settings->getID() ?>"
                                        <?= $immunization->getConsecutiveVaccine() == $settings->getID() ? 'selected' : '' ?>><?= $settings->getTitle() ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group spacing">
                        <label style="width:100%;" for="">Minimum Spacing</label>
                        <input min="1" style="width: 35%;float: left;" type="number" id="min_spacing_interval" name="min_spacing_interval" value="<?= $immunization->getMinSpacingInterval() ? $immunization->getMinSpacingInterval() : '' ?>" class="form-control" autocomplete="off">
                        <select name="min_spacing_date" id="min_spacing_date" class="form-control min_spacing_date" style="width: 65%;">
                        <?php foreach (mth_immunization_settings::getAvailableTime() as $key => $timeLabel): ?>
                                <option value="<?= $key ?>"
                                    <?= $immunization->getMinSpacingDate() == $key ? 'selected' : '' ?>><?= $timeLabel ?></option>
                        <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group spacing">
                        <label style="width:100%;" for="">Maximum Spacing</label>
                        <input min="1" style="width: 35%;float: left;" type="number" name="max_spacing_interval" value="<?= $immunization->getMaxSpacingInterval() ? $immunization->getMaxSpacingInterval() : '' ?>" class="form-control" autocomplete="off">
                        <select name="max_spacing_date" id="max_spacing_date" class="form-control max_spacing_date" style="width: 65%;">
                            <option value="0">None</option>
                        <?php foreach (mth_immunization_settings::getAvailableTime() as $key => $timeLabel): ?>
                                <option value="<?= $key ?>"
                                    <?= $immunization->getMaxSpacingDate() == $key ? 'selected' : '' ?>><?= $timeLabel ?></option>
                        <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="">Standard Response for Email Update</label>
                        <input type="text" name="email_update" value="<?= $immunization->getEmailUpdateTemplate() ?>" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button  type="submit" name="button" class="btn btn-round btn-primary" value="Save">Save</button>
                <button type="button" class="btn btn-round btn-danger"  onclick="deleteImmunization()">Delete</button>
            </div>
          </div>
    </form>
<?php
core_loader::addJsRef('momentjs', core_config::getThemeURI() . '/vendor/calendar/moment.min.js');
core_loader::addJsRef('calendarjs', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js');
core_loader::addJsRef('timepickercdn', "https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.js");
core_loader::printFooter();
?>
    <script>
        function deleteImmunization() {
            swal({
                title: "",
                text: "Are you sure you want to delete this immunization? This action cannot be undone.",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-primary",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function () {
                    location.href = '?immunization_id=<?=$immunization->getID()?>&delete=1';
            });
        }

        function toogleUpdateExemptContainer() {
            if ($('input#exempt_update').is(':checked')) {
                $('#exempt_grade_label_container').show();
            }else{
                $('#exempt_grade_label_container').hide();
                $('input[name ="exempt_update[]"]').prop('checked', false);
            }
        }

        function toogleConsecutiveContainer() {
            if ($('input#consecutive_vaccine_trigger').is(':checked')) {
                $('#consecutive_vaccine_container').show();
            }else{
                $('#consecutive_vaccine_container').hide();
                $('#consecutive_vaccine').val("0");
            }
        }

        function calculateGradeLevelRange()
        {
            var min_grade_level = $('#min_grade_level').val() == 'OR-K' ? -1 : $('#min_grade_level').val();
            var max_grade_level = $('#max_grade_level').val() == 'OR-K' ? -1 : $('#max_grade_level').val();
            var start = parseInt(min_grade_level) || 0;
            var end = parseInt(max_grade_level) || 0;
            $('.exempt_grade_level').each(function( index, value ) {
                if(index-1 >= start  && index-1 <= end ) {
                    $(this).parent().show();
                } else {
                    $(this).parent().hide();
                    $(this).prop('checked', false);
                }
            });
        }

        function isSpacingShown()
        {
            if ($('#consecutive_vaccine').val() != 0)
            {
                $('.spacing').show();
                $('#min_spacing_date,#min_spacing_interval').prop('required',true);
            } else {
                $('.spacing').hide();
                $('.spacing input').val("");
                $('#min_spacing_date,#min_spacing_interval').prop('required',false);
            }
        } 
        $(function() {
            isSpacingShown();

            calculateGradeLevelRange();

            toogleUpdateExemptContainer();

            toogleConsecutiveContainer();

            $('#consecutive_vaccine').change(function(){
                isSpacingShown();
            });

            $('.grade_level').on('change', function(){
                calculateGradeLevelRange();
            });

            $('#require_update').click(function(){
                toogleUpdateContainer();
            });

            $('#exempt_update').click(function(){
                toogleUpdateExemptContainer();
            });

            $('#consecutive_vaccine_trigger').click(function() {
                toogleConsecutiveContainer();
                isSpacingShown();
            })
        });
    </script>