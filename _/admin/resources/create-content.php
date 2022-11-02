<?php
if (!empty($_GET['resource_id'])) {
    ($resource = mth_resource_settings::getById($_GET['resource_id'])) || die('Resource not found');
} else {
    $resource = new mth_resource_settings();
}


if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die('Unable to submit form');
    if ($_FILES['image']['error'] == 0 && ($result = $resource->uploadBanner($_FILES['image']))) {
        $resource->set_image($result);
    }
    $minGradeLevel = (req_post::txt('min_grade_level') == 'OR-K') ? -1 : (req_post::txt('min_grade_level') == 'K' ? 0 : req_post::txt('min_grade_level'));
    $maxGradeLevel = (req_post::txt('max_grade_level') == 'OR-K') ? -1 : (req_post::txt('max_grade_level') == 'K' ? 0 : req_post::txt('max_grade_level'));
    $resource->name(req_post::txt('resource_name'));
    $resource->min_grade_level($minGradeLevel);
    $resource->max_grade_level($maxGradeLevel);
    $resource->set_available(!empty($_POST['available']));
    $resource->cost(req_post::float('cost'));
    $resource->set_show_cost(!empty($_POST['show_cost']));
    $resource->isDirectDeduction(!empty($_POST['direct_deduction']) ? 1 : 0);
    $resource->set_content(req_post::html('content'));
    $resource->set_show_parent(req_post::bool('show_parent') ? 1 : 0);
    $resource->resourceType(req_post::int('resource_type'));

    if ($resource->save()) {
        $current_year = mth_schoolYear::getCurrent();
        if (!empty($_POST['direct_deduction'])) {
            $resource_request = new mth_resource_request;
            $resource_request->whereResourceId([$resource->getID()]);
            $resource_request->whereYearId([$current_year->getID()]);
            while ($request = $resource_request->query()) {
                $year = mth_schoolYear::getByID($request->school_year_id());
                $student = $request->student();
                if ($student) {
                    $reimbursement = new mth_reimbursement();
                    $reimbursement->set_type(mth_reimbursement::TYPE_DIRECT);
                    $reimbursement->set_status(mth_reimbursement::STATUS_PAID);
                    $reimbursement->set_amount($resource->cost());
                    $reimbursement->set_description('Direct Deduction - Homeroom Resource - ' . $resource->name());
                    $reimbursement->set_student_year($student, $year);
                    $reimbursement->set_at_least_80(true);
                    $reimbursement->set_tag_type(NULL);
                    $reimbursement->set_date_paid($request->createDate('Y-m-d H:i:s'));
                    $reimbursement->set_resource_request_id($request->getID());

                    if (!$reimbursement->doesExist()) {
                        $reimbursement->save();
                    }
                }
            }
        } else {
            $resource_request = new mth_resource_request;
            $resource_request->whereResourceId([$resource->getID()]);
            $resource_request->whereYearId([$current_year->getID()]);
            while ($request = $resource_request->query()) {
                $reimbursement = new mth_reimbursement();
                $reimbursement->set_resource_request_id($request->getID());
                $reimbursement->deleteByResourceRequestId();
            }
        }
        core_notify::addMessage('Resouce Saved');
        core_loader::reloadParent();
    } else {
        core_notify::addError('Unable to save resource!');
        core_loader::redirect('?provider_id=' . $resource->getID());
    }
}
core_loader::isPopUp();
core_loader::printHeader();
?>
<button type="button" class="iframe-close btn btn-secondary btn-round" onclick="top.location.reload(true)">Close</button>
<h2><?= $resource->getID() ? 'Edit' : 'New' ?> Homeroom Resource</h2>
<form name="resource_form" action="?form=<?= uniqid('mth_resource-form') ?><?= $resource->getID() ? '&resource_id=' . $resource->getID() : '' ?>" method="post" enctype="multipart/form-data">
    <div class="card">
        <div class="card-block">
            <div class="form-group">
                <label class="col-form-label">
                    Name
                </label>
                <input type="text" name="resource_name" class="form-control" value="<?= $resource->name() ?>" required>
            </div>
            <div class="form-group">
                <label>Minimum Grade Level</label>
                <select name="min_grade_level" id="min_grade_level" class="form-control">
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                        <option value="<?= $grade_level ?>" <?= $resource->min_grade_level() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Maximum Grade Level</label>
                <select name="max_grade_level" id="max_grade_level" class="form-control">
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                        <option value="<?= $grade_level ?>" <?= $resource->max_grade_level() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="cost">Cost</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" name="cost" class="form-control" id="cost" style="max-width: 100px" value="<?= $resource->cost() ?>">
                    <span class="input-group-addon">
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" id="show_cost" name="show_cost" value="1" <?= $resource->showCost() ? 'checked' : '' ?> />
                            <label for="show_cost">Show to parent</label>
                        </div>
                    </span>
                    <span class="input-group-addon">
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" id="direct_deduction" name="direct_deduction" value="1" <?= $resource->isDirectDeduction() ? 'checked' : '' ?> />
                            <label for="direct_deduction">Direct Deduction</label>
                        </div>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label>Resource Type</label>
                <select class="form-control" name="resource_type">
                    <?php foreach (mth_resource_settings::availableTypes() as $rid => $resource_type) : ?>
                        <option value="<?= $rid ?>" <?= $rid == $resource->resourceType() ? 'SELECTED' : '' ?>><?= $resource_type ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Available</label>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="available" id="available" value="1" <?= $resource->isAvailable() ? 'checked' : '' ?>>
                    <label for="available">
                        Check if this resource should be available for parents to select.
                    </label>
                </div>
            </div>
            <hr>
            <div class="form-group">
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="show_parent" id="show_parent" value="1" <?= $resource->showToParent() ? 'checked' : '' ?>>
                    <label for="show_parent">
                        Show resource from resources banner.
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Details</label>
                <textarea name="content" class="form-control" id="resource-content"><?= htmlentities($resource->content()) ?></textarea>
            </div>
            <div class="form-group">
                <label>Image Banner</label>
                <input type="file" name="image">
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary btn-round" type="submit">Save</button>
        </div>
    </div>
</form>
<?php
core_loader::printFooter();
?>
<script src="//cdn.ckeditor.com/4.10.0/basic/ckeditor.js"></script>
<script>
    $(function() {
        CKEDITOR.config.removePlugins = 'about';
        CKEDITOR.config.disableNativeSpellChecker = false;
        CKEDITOR.replace('resource-content');
    });
</script>