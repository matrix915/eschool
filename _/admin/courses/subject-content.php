<?php

if (req_get::bool('subject_id')) {
    ($subject = mth_subject::getByID(req_get::int('subject_id'))) || die('Subject not found');
} else {
    $subject = new mth_subject();
}

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || die();

    $subject->setName(req_post::txt('name'));
    $subject->setDesc(req_post::txt('desc'));
    $subject->showProviders(req_post::bool('show_providers'));
    $subject->set_allow_2nd_sem_change(req_post::int_array('allow_2nd_sem_change'));
    $subject->setPeriods(req_post::int_array('periods'));
    $subject->available(req_post::bool('available'));

    if($subject->archived(req_post::bool('archived'))) {
        $subject->available(false);
    }

    if ($subject->save()) {
        core_notify::addMessage('Subject Saved');
        core_loader::reloadParent();
    } else {
        core_notify::addError('Unable to save subject!');
        core_loader::redirect('?subject_id=' . $subject->getID());
    }
}

core_loader::isPopUp();
core_loader::printHeader();
?>
    <button type="button" class="iframe-close btn btn-round btn-secondary" onclick="top.location.reload(true)">Close</button>
    <h2><?= $subject->getID() ? 'Edit' : 'New' ?> Subject</h2>
<?php if ($subject->getID()): ?>
    <p>
        Changes to this subject will be reflected in any schedule with this subject (name and description).
    </p>
<?php endif ?>
<div class="panel">
    <div class="panel-body">
        <form
        action="?form=<?= uniqid('mth_subject-edit') ?><?= $subject->getID() ? '&subject_id=' . $subject->getID() : '' ?>"
        method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" name="name" value="<?= $subject->getName() ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="desc" value="<?= $subject->getDesc() ?>" class="form-control"> 
            </div>
            <div class="checkbox-custom checkbox-primary">
                <input type="checkbox" name="show_providers" id="show_providers" value="1"
                        <?= $subject->showProviders() || !$subject->getID() ? 'checked' : '' ?> >
                <label for="show_providers">
                    Show a list of providers for the parent to select from.
                </label>
            </div>
            <div class="form-group">
                <label>Periods</label>
                <?php while ($period = mth_period::each()): ?>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="periods[]" id="periods-<?= $period->num() ?>"
                                    value="<?= $period->num() ?>" <?= $subject->inPeriod($period->num()) ? 'checked' : '' ?>>
                        <label for="periods-<?= $period->num() ?>">
                            <?= $period->num() ?>
                            <?= !$period->required() ? '(optional)' : '' ?>
                        </label>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="form-group">
                <label>
                    2nd Semester
                    <small>(grades 9-12)</small>
                </label>
                If this is the subject, allow parents to change the following periods:
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="allow_2nd_sem_change[]" id="allow_2nd_sem_change-5" value="5"
                            <?= $subject->allow_2nd_sem_change(5) ? 'checked' : '' ?> >
                    <label for="allow_2nd_sem_change-5">
                    Period 5
                </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="allow_2nd_sem_change[]" id="allow_2nd_sem_change-6" value="6"
                            <?= $subject->allow_2nd_sem_change(6) ? 'checked' : '' ?> >
                    <label for="allow_2nd_sem_change-6">
                        Period 6
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="available" id="available" value="1"
                        <?= $subject->available() ? 'checked' : '' ?>>
                    <label for="available">
                        This subject is available for parents to select.
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="archived" id="archived" value="1"
                        <?= $subject->archived() ? 'checked' : '' ?>>
                    <label for="archived">
                        Archived
                    </label>
                </div>
            </div>
       
            <button type="submit" class="btn btn-primary btn-round">Save</button>
           
        </form>
    </div>
</div>
    
<?php
core_loader::printFooter();