<?php

if (!empty($_GET['submitForm'])) {
    if (!core_loader::formSubmitable('core-secure-password-forgot-form-' . $_GET['submitForm'])) {
        exit();
    }
    $are_fields_present = req_post::bool('email') && req_post::bool('lastname');

    if(!$are_fields_present ){
        core_notify::addError('Required fields are not present.');
    }elseif (($user = core_user::findUser(req_post::txt('email'),null,['last_name'=>req_post::txt('lastname')]))) {
        if ($user->sendPasswordResetEmail(true)) {
            core_notify::addMessage('Reset password email sent. Check your inbox.');
        } else {
            core_notify::addError('Unable to send reset password link. Please contact us.');
        }
    } elseif (($student = mth_student::getByEmail(req_post::txt('email')))) {
        core_notify::addMessage('You have not activated your account here yet. You need to login using your birth date (e.g. 7/4/1776) to activate your account.');
        core_loader::redirect('/?login=1');
    } else {
        core_notify::addError('There is no account associated with that email or last name.');
    }

    header('location:/_/user/forgot');
    exit();
}

cms_page::setPageTitle('Reset Password');
core_loader::addClassRef('page-login-v3 layout-full page-forgot-page');
core_loader::addCssRef('login', core_config::getThemeURI() . '/assets/css/loginv3.min.css');
core_loader::printHeader();
?>
 <div class="page vertical-align text-center" data-animsition-in="fade-in" data-animsition-out="fade-out">&gt;
    <div class="page-content vertical-align-middle">
        <div class="panel">
            <div class="panel-body text-left">
                <h3 class="mb-20"><?= cms_page::getDefaultPageTitleContent()?></h3>
                <form method="post" class="core-secure-password-forgot-form"
                    action="?submitForm=<?= time() ?>">
                    <div class="form-group  form-material  ">
                        <label for="lastname">Enter Last Name</label>
                        <input type="text" class="form-control" name="lastname" id="lastname">
                    </div>
                    <div class="form-group  form-material  ">
                        <label for="email">Enter Your Email Address</label>
                        <input type="email" class="form-control" name="email" id="email">
                    </div>
                    <div class="form-group clearfix">
                        <button type="submit" class="btn btn-primary btn-round btn-block">Send InfoCenter Password Reset Link</button>
                    </div>
                    <p class="text-center">
                    <a href="/?login=1" >Login</a>
                    </p>
                </form>
            </div>
        </div>
        <footer class="page-copyright page-copyright-inverse">
            <?php core_loader::printMTHFooterContent();?>
        </footer>
    </div>
</div>
<?php
core_loader::printFooter();