<?php

$application = mth_application::getApplicationByID(req_get::int('app'));
if (!$application) {
    exit();
}

$student = mth_student::getByStudentID($application->getStudentID());
if (!$student) {
    exit();
}

$parent = mth_parent::getByParentID($student->getParentID());
if (!$parent) {
    exit();
}

if (req_get::bool('form')) {
    if (!core_loader::formSubmitable('application_edit_form_' . req_get::txt('form'))) {
        exit();
    }
    $application->setStatus(req_post::txt('status'));
    $application->setMidYear((int)req_post::txt('midyear_application'));
    $application->setSchoolYear(mth_schoolYear::getByID(req_post::int('schoolYear_id')));
    core_notify::addMessage('Application saved');
    header('location: /_/admin/applications/edit?app=' . $application->getID());
    exit();
}

cms_page::setPageTitle('Edit Application');
core_loader::isPopUp();
core_loader::printHeader();

?>
    <style type="text/css">
        table.formatted td, table.formatted th {
            vertical-align: top;
            padding: 5px 10px;
        }

        table.formatted {
            width: 100%;
        }
    </style>
    <h1 class="mt-0">Application</h1>
<?php if ($application->isSubmitted()): ?>
    <p>Submitted <?= $application->getDateSubmitted('M. j, Y') ?></p>
<?php endif; ?>
    <button type="button" class="iframe-close btn btn-round btn-secondary" onclick="parent.global_popup_iframe_close('mth_application_edit')">Close</button>
    <form action="?app=<?= $application->getID() ?>&form=<?= time() ?>" method="post">
        <table class="formatted person-links">
            <tr>
                <th>Student</th>
                <td>
                    <b><a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?student=<?= $student->getID() ?>')">
                            <?= $student ?>
                        </a></b>
                    <small><?= $student->getGradeLevel(true, false, $application->getSchoolYearID()) ?>
                        (<?= $application->getSchoolYear(true) ?>)
                    </small>
                    <div><?= $student->getEmail(true) ?></div>
                    <div><?= $student->getAddress() ?></div>
                </td>
            </tr>
            <tr>
                <th>Parent</th>
                <td>
                    <b><a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?parent=<?= $parent->getID() ?>')">
                            <?= $parent ?>
                        </a></b>
                    <div><?= $parent->getEmail(true) ?></div>
                    <div><?= $parent->getPhoneNumbers(true) ?></div>
                </td>
            </tr>
            <tr>
                <th>City</th>
                <td><?= $application->getCityOfResidence() ?></td>
            </tr>
            <tr>
                <th>Referred By</th>
                <td><?= $application->getReferredBy() ?></td>
            </tr>
            <tr>
                <th>Mid-year Application</th>
                <td>
                    <select name="midyear_application" class="form-control">
                        <option value="1" <?= $application->getMidyearApplication() ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= $application->getMidyearApplication() ? '' : 'selected' ?>>No</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>School Year</th>
                <td>
                    <select name="schoolYear_id" class="form-control">
                        <?php while ($eachYear = mth_schoolYear::each()): ?>
                            <option value="<?= $eachYear->getID() ?>"
                                <?= $eachYear->getID() == $application->getSchoolYearID() ? 'selected' : '' ?>><?= $eachYear ?></option>
                        <?php endwhile; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <select name="status" class="form-control" id="application_status">
                        <?php foreach (mth_application::getAvailableStatuses() as $status): ?>
                            <option <?= $application->getStatus() == $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Changing the status to accepted here will not invoke <br>
                        the email notification or affect the student's status.
                    </small>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit" class="btn btn-primary btn-round">Save</button>
                    <button type="button" value="Close" class="btn btn-secondary btn-round"
                           onclick="if(top.updateTable){top.updateTable();top.global_popup_iframe_close('mth_application_edit');}else{top.location.reload(true);}">
                    Cancel
                    </button>
                   
                </td>
            </tr>
        </table>
    </form>
<?php
core_loader::printFooter();
?>
<script>
    $(function(){
        var accepted = '<?=mth_application::STATUS_ACCEPTED?>';
        $('#application_status').change(function(){
            if($(this).val() == accepted){
                swal('','Changing the status to accepted here will not invoke the email notification or affect the student\'s status.','warning');
            }
        });
    });
</script>