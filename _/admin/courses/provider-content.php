<?php

if (!empty($_GET['provider_id'])) {
    ($provider = mth_provider::get($_GET['provider_id'])) || die('Provider not found');
} else {
    $provider = new mth_provider();
}

if (!empty($_GET['form'])) {
    $minGradeLevel = (req_post::txt('min_grade_level') == 'OR-K') ? -1 : (req_post::txt('min_grade_level') == 'K' ? 0 : req_post::txt('min_grade_level'));
    $maxGradeLevel = (req_post::txt('max_grade_level') == 'OR-K') ? -1 : (req_post::txt('max_grade_level') == 'K' ? 0 : req_post::txt('max_grade_level'));
    
    $alternative_min_grade_level = (req_post::txt('alternative_min_grade_level') == 'OR-K') ? -1 : (req_post::txt('alternative_min_grade_level') == 'K' ? 0 : req_post::txt('alternative_min_grade_level'));
    $alternative_max_grade_level = (req_post::txt('alternative_max_grade_level') == 'OR-K') ? -1 : (req_post::txt('alternative_max_grade_level') == 'K' ? 0 : req_post::txt('alternative_max_grade_level'));

    core_loader::formSubmitable($_GET['form']) || die();
    $msg = 'Provider Saved';
    if (isset($_POST['deleted']) && $_POST['deleted'] == 1) {
        $provider->deleted(1);
        $msg = 'Provider Deleted';
    } else {
        $provider->name($_POST['name']);
        $provider->desc($_POST['desc']);
        $provider->led_by(TRUE, $_POST['led_by']);
        $provider->min_grade_level($minGradeLevel);
        !isset($maxGradeLevel) || $provider->max_grade_level($maxGradeLevel);
        $provider->alternativeMaxGradeLevel($alternative_max_grade_level);
        $provider->alternativeMinGradeLevel($alternative_min_grade_level);
        $provider->diploma_valid(req_post::bool('diploma_valid'));
        $provider->available(!empty($_POST['available']));
        $provider->set_allow_2nd_sem_change(req_post::bool('allow_2nd_sem_change'));
        $provider->diplomanaOnly(!empty($_POST['diploma_only']));
        $provider->popup(req_post::bool('popup'));
        $provider->popup_content(req_post::txt('popup_content'));
        $provider->isAvailableInSchoolAssignment(req_post::bool('available_in_school_assignment'));
        $provider->requiresMultiplePeriods(req_post::bool('requires_multiple_periods'));
        $provider->multiplePeriods(req_post::int_array('periods'));
        if ($provider->archived(req_post::bool('archived'))) {
            $provider->available(false);
        }
    }


    if ($provider->save()) {
        core_notify::addMessage($msg);
        core_loader::reloadParent();
    } else {
        core_notify::addError('Unable to save provider!');
        core_loader::redirect('?provider_id=' . $provider->id());
    }
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<button type="button" class="iframe-close btn btn-secondary btn-round" onclick="top.location.reload(true)">Close</button>
<h2><?= $provider->id() ? 'Edit' : 'New' ?> Provider</h2>
<?php if ($provider->id()) : ?>
    <p>
        Changes to this provider will be reflected in all schedules with this provider.
    </p>
<?php endif ?>
<form name="provider_form" action="?form=<?= uniqid('mth_provider-form') ?><?= $provider->id() ? '&provider_id=' . $provider->id() : '' ?>" method="post">
    <div class="card">
        <div class="card-block">
            <div class="form-group">
                <label class="col-form-label">
                    Name
                </label>
                <input type="text" name="name" class="form-control" value="<?= $provider->name() ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="desc" class="form-control" value="<?= $provider->desc() ?>">
            </div>
            <div class="form-group">
                <label>Led By</label>
                <select name="led_by" id="led_by" class="form-control">
                    <?php foreach (mth_provider::led_by_options() as $key => $value) : ?>
                        <option value="<?= $key ?>" <?= $provider->led_by(true) == $key ? 'selected' : '' ?>><?= $value ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Minimum Grade Level</label>
                <select name="min_grade_level" id="min_grade_level" class="form-control">
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                        <option value="<?= $grade_level ?>" <?= $provider->min_grade_level() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Maximum Grade Level</label>
                <select name="max_grade_level" id="max_grade_level" class="form-control">
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                        <option value="<?= $grade_level ?>" <?= $provider->max_grade_level() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Alternative Minimum Grade Level</label>
                <select name="alternative_min_grade_level" id="alternative_min_grade_level" class="form-control">
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                        <option value="<?= $grade_level ?>" <?= $provider->alternativeMinGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Alternative Maximum Grade Level</label>
                <select name="alternative_max_grade_level" id="alternative_max_grade_level" class="form-control">
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                        <option value="<?= $grade_level ?>" <?= $provider->alternativeMaxGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Diploma-seeking Valid</label>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="diploma_valid" id="diploma_valid" value="1" <?= $provider->diploma_valid() ? 'checked' : '' ?>>
                    <label for="diploma_valid">
                        Available for Diploma-seeking Students
                    </label>
                </div>
            </div>

            <div class="checkbox-custom checkbox-primary">
                <input type="checkbox" name="diploma_only" id="diploma_only" value="1" <?= $provider->diplomanaOnly() ? 'checked' : '' ?>>
                <label for="diploma_only">
                    Only visible/available to Diploma-seeking Students
                </label>
            </div>

            <div class="form-group">
                <label>2nd Semester</label>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="allow_2nd_sem_change" id="allow_2nd_sem_change" value="1" <?= $provider->allow_2nd_sem_change() ? 'checked' : '' ?>>
                    <label for="allow_2nd_sem_change">
                        Allow parents to change any period where this is the provider
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Available</label>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="available" id="available" value="1" <?= $provider->available() ? 'checked' : '' ?>>
                    <label for="available">
                        Check if this provider should be available for parents to select.
                    </label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="popup" id="popup" value="1" <?= $provider->popup() ? 'checked' : '' ?>>
                    <label for="popup">
                        Check to activate pop-up box.
                    </label>
                </div>
            </div>
            <div class="form-group" id="popup_content_cont" <?= $provider->popup() ? '' : 'style="display:none"' ?>>
                <label>Pop-up Content</label>
                <textarea class="form-control" name="popup_content" id="popup_content"><?= $provider->popup_content() ?></textarea>
            </div>
            <div class="form-group">
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="available_in_school_assignment" id="available_in_school_assignment" value="1" <?= $provider->isAvailableInSchoolAssignment() ? 'checked' : '' ?>>
                    <label for="available_in_school_assignment">
                        Check to display in School of Enrollment Assigment Manager filter
                    </label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="archived" id="archived" value="1" <?= $provider->archived() ? 'checked' : '' ?>>
                    <label for="archived">
                        Archived
                    </label>
                </div>
            </div>

            <div class="checkbox-custom checkbox-primary">
                <input type="checkbox" name="requires_multiple_periods" id="requires_multiple_periods" value="1"
                    <?= $provider->requiresMultiplePeriods() ? 'checked' : '' ?>>
                <label for="requires_multiple_periods">
                    Requires Multiple Periods
                </label>
            </div>
            <div class="form-group" id="multiple_periods"
                 style="<?= 'display:' . ($provider->requiresMultiplePeriods() ? 'block;' : 'none;') ?>">
                <label>Full-Schedule Periods</label>
                <?php while ($period = mth_period::each()):
                    if ($period->num() ===  1) continue; ?>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="periods[]" id="periods-<?= $period->num() ?>"
                               value="<?= $period->num() ?>" <?= in_array($period->num(), $provider->multiplePeriods()) ? 'checked' : '' ?>>
                        <label for="periods-<?= $period->num() ?>">
                            <?= 'Period ' . $period->num() ?>
                        </label>
                    </div>
                <?php endwhile; ?>
            </div>

        </div>
        <div class="card-footer">
            <button class="btn btn-primary btn-round" type="submit">Save</button>
            <button class="btn btn-primary  btn-danger btn-round" type="button" id="delete">Delete</button>
            <input type="hidden" value="0" name="deleted">
        </div>
    </div>
</form>
<script>
    $(function() {
        $('#delete').click(function() {
            $('[name="deleted"]').val("1");
            $('[name="provider_form"]')[0].submit();
        });
        $('#popup').change(function() {
            if ($(this).is(':checked')) {
                $('#popup_content_cont').fadeIn();
            } else {
                $('#popup_content_cont').fadeOut();
            }
        });
      $('#requires_multiple_periods').click(function() {
        if ($(this).is(':checked')) {
          $('#multiple_periods').show()
        } else {
          $('#multiple_periods').hide()
        }
      });
    });

</script>
<?php
core_loader::printFooter();
