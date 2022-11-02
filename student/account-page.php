<?php
if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || exit();
    $_email = req_post::txt('studentemail');
    if(req_post::bool('update')){
        $user = $student->userAccount();
        $emailChange =  $_email != $user->getEmail();
        if($emailChange && core_user::findUser($_email)){
            core_notify::addError('The email ' . $_email . ' is associated with a different account and cannot be used.');
        }else{  
            if($student->setEmail($_email)){
                core_notify::addMessage('Email successfully updated!');
            }else{
                core_notify::addError('Unable to update '.$student->getPreferredFirstName().'\s email');
            }
        }

        if (!empty($_POST['newstudentpassword'])) {
            if ($user->changePassword($_POST['newstudentpassword'])) {
                core_notify::addMessage('Password has been changed, student will need to use the new password to login from now on.');
            }else{
                core_notify::addError('Unable to change student\'s password. Please try again later, or contact us if you need assistance');
            }
        }
    }else{
        if($user = core_user::findUser($_email)){
            core_notify::addError('The email ' . $_email . ' is associated with a different account and cannot be used.');
        }else{
            if (
                ($newUser = core_user::newUser($_email, $student->getPreferredFirstName(), $student->getPreferredLastName(), mth_user::L_STUDENT,req_post::raw('studentpassword')))
            ) {
                $user_id = $newUser->getID();
                $person = core_db::runQuery('UPDATE mth_person SET user_id=' . $user_id . ' 
                                    WHERE person_id=' . $student->getPersonID());
                core_notify::addMessage('Awesome!  You have successfully created your child’s account!');
            }else{
                core_notify::addError('An error occur when creating an account.');
            }
        }
    }

    core_loader::redirect();
    exit();
}

$username = $student->getEmail();
$new = true;
if($user = $student->userAccount()){
    $new = false;
    $username = $user->getEmail();
    $password = $user->getPasswordResetCode();
}

cms_page::setPageContent(
    'Your child\'s account is active. Students with accounts can login using the information below',
    'Edit Student Account',
    cms_content::TYPE_HTML
);

cms_page::setPageContent(
    'You can allow your child to access infocenter by creating their own account. Please fill out the form below.',
    'Create Active Student Account',
    cms_content::TYPE_HTML
);


core_loader::isPopUp();
core_loader::printHeader();

?>
<button type="button" class="iframe-close btn btn-round btn-secondary" onclick="top.global_popup_iframe_close('yoda_account_popup')">Close</button>
<div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><?=$student?></h4>
                        <p class="card-subtitle"><?=$new?'Create an account for '.$student:'Update '.$student.'\'s account'?></p>
                    </div>
                    <div class="card-block">
                        <div class="card-footer bg-info" style="margin: -1.429rem;">
                            <?php if(!$new):?>
                                <?= cms_page::getDefaultPageContent('Edit Student Account', cms_content::TYPE_HTML);?>
                            <?php else:?>
                                <?= cms_page::getDefaultPageContent('Create Active Student Account', cms_content::TYPE_HTML);?>
                            <?php endif;?>
                        </div>
                      
                        <form method="post" class="set-password-form mt-40" id="setPasswordForm" action="?form=<?= uniqid('setAccountForm') ?>">
                            <div class="form-group">
                                <label>Student Email:</label>
                                <input type="email" name="studentemail" class="form-control" value="<?=$username?>" required/>
                            </div>
                            <?php if($new):?>
                                <div class="form-group">
                                    <label>Password:</label>
                                    <input type="password" name="studentpassword"  class="form-control" id="password" required>
                                    <small id="passwordDescription">Your password should be at least <b>8 characters</b> long and
                                        include <b>uppercase</b>, <b>lowercase</b>, <b>numbers</b> and <b>other characters (!@#$%^&*)</b>
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="password2">Re-enter Password:</label>
                                    <input type="password" class="form-control" name="studentpassword2" id="password2" required/>
                                </div>
                            <?php else:?>
                                <fieldset class="p-20" style="border:1px solid #ccc;">
                                    <div class="alert dark alert-alt alert-warning">
                                        Use these fields only if you want to change password
                                    </div>
                                    <input type="hidden" value="1" name="update">
                                    <div class="form-group">
                                        <label>New Password:</label>
                                        <input type="password" name="newstudentpassword"  class="form-control" id="newstudentpassword" >
                                        <small id="passwordDescription">Your password should be at least <b>8 characters</b> long and
                                            include <b>uppercase</b>, <b>lowercase</b>, <b>numbers</b> and <b>other characters (!@#$%^&*)</b>
                                        </small>
                                    </div>
                                    <div class="form-group">
                                        <label for="password2">Re-enter New Password:</label>
                                        <input type="password" class="form-control" name="renewstudentpassword" id="renewstudentpassword"/>
                                    </div>
                                </fieldset>
                            <?php endif;?>
                           <br>
                            
                            <button type="submit" id="submitButton" class="btn btn-lg btn-primary btn-round float-right">
                                <?=$new?'Set':'Update';?> Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
<?php
core_loader::includejQueryValidate();
core_loader::printFooter();
?>

<script type="text/javascript">
    $(function () {
        <?php if($new):?>
            $('#setPasswordForm').validate({
                rules: {
                    "password2": {
                        required: true,
                        equalTo: '#password'
                    }
                }
            });
        <?php else:?>
            $('#setPasswordForm').validate({
                rules: {
                    "renewstudentpassword": {
                        equalTo: '#newstudentpassword'
                    }
                }
            });
        <?php endif;?>
    });
</script>