<?php
if(!$parent = mth_parent::getByParentID(req_get::int('parent'))){
    die('No Parent Selected');
}

if(req_get::bool('createParent')){
    if(!req_post::bool('student')){
        core_notify::addError('Please select at least 1 student to assign for the new observer.');
        core_loader::redirect('?parent=' . $parent->getID());
        exit();
    }

    if (!mth_parent::validateEmailAddress(req_post::txt('new_user_email'))) {
        if (core_user::validateEmail(req_post::txt('new_user_email'))) {
            core_notify::addError('<b>There is already an account with email address '.req_post::txt('new_user_email').'.</b>');
        } else {
            core_notify::addError('<b>Invalid email address</b>');
        }
    } else {
        $parent = mth_parent::create();
        $parent->setName(req_post::txt('new_user_first'),req_post::txt('new_user_last'));
        $parent->setEmail(req_post::txt('new_user_email'));

        if($parent->makeUser()){
            foreach(req_post::int_array('student') as $student_id){
                if($student = mth_student::getByStudentID($student_id)){
                    $student->setObserver($parent->getID());
                }
            }
        }

        core_notify::addMessage('User invitation has been sent');
    }
    core_loader::redirect('?parent=' . $parent->getID());
    exit();
}

core_loader::isPopUp();
core_loader::addClassRef('family-observer');
core_loader::printHeader();
?>
<button type="button" class="iframe-close btn btn-round btn-secondary" onclick="top.global_popup_iframe_close('observer_popout');">Close</button>

<form action="?createParent=1&parent=<?=req_get::int('parent')?>" method="post" id="createParent" >
    <div class="card">
        <div class="card-block">
            <p>
                This user will receive an email giving them a link to create a password.
            </p>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="new_user_email" class="form-control">
            </div>
            <div class="form-group">
            <label>First Name:</label>
                <input type="text" name="new_user_first" class="form-control">
            </div>
            <div class="form-group">
            <label>Last Name:</label>
                <input type="text" name="new_user_last" class="form-control">
            </div>
            <table class="table">
                <thead>
                    <tr><th></th><th>Child</th><th>Observer</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($parent->getStudents() as $student) : ?>
                        <tr>
                            <td>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="student[]" value="<?=$student->getID()?>">
                                    <label></label>
                                </div>
                            </td>
                            <td>
                            <?= $student ?>
                            </td>
                            <td>
                                <?= $student->getObserver() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button class="btn btn-round btn-primary"  type="submit">Create Observer</button>
        </div>
    </div>
</form>
   

<?php
core_loader::printFooter();
?>