<?php

if (!req_get::bool('newPass')) {
    header('Location: /');
    exit();
}

if (req_post::bool('email')) {
    core_loader::formSubmitable(req_post::txt('email') . '-' . req_get::txt('newPass')) || exit();

    $user = core_user::findUser(req_post::txt('email'), req_get::txt('newPass'));

    if (!$user) {
        core_notify::addError('Invalid code provided!');
        core_loader::redirect('/');
    }

    if ($user->changePassword(req_post::raw('password'))) {
        core_user::login(req_post::txt('email'), req_post::raw('password'));
        core_notify::addMessage('Your password has been set.');
    } else {
        core_notify::addError('Unable to set the password. Please contact us for support.');
    }
    core_loader::redirect();
}

core_loader::includejQueryValidate();

cms_page::setPageTitle('Create New Password');
core_loader::addClassRef('page-login-v3 layout-full page-forgot-page');
core_loader::addCssRef('login', core_config::getThemeURI() . '/assets/css/loginv3.min.css');
core_loader::printHeader();
?>
    <script type="text/javascript">
        $(function () {
            $('#passwordResetForm').validate({
                rules: {
                    "password2": {
                        required: true,
                        equalTo: '#password'
                    }
                }
            });
        });
    </script>
<div class="page vertical-align text-center" data-animsition-in="fade-in" data-animsition-out="fade-out">&gt;
    <div class="page-content vertical-align-middle">
        <div class="panel">
            <div class="panel-body text-left">
                <h3 class="mb-20"><?= cms_page::getDefaultPageTitleContent()?></h3>
                <form method="post" class="core-secure-password-reset-form" id="passwordResetForm"
                    action="<?= core_path::getPath()->getString() != core_config::getPasswordResetPath() ? core_path::getPath() : '/' ?>?newPass=<?= req_get::urlencode('newPass') ?>">

                    <div class="form-group form-material">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" value="<?= req_get::txt('email') ?>" class="form-control " required>
                    </div>
                    <div class="form-group form-material">
                        <label for="password">New Password:</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <small id="passwordDescription">Your password should be at least 6 characters long and include uppercase,
                            lowercase, numbers and other characters (!@#$%^&*)
                        </small>
                    </div>
                    <div class="form-group form-material">
                        <label for="password2">Re-enter Password:</label>
                        <input type="password" name="password2" id="password2" class="form-control" required>
                    </div>
                    <div>
                        <button type="submit" id="submitButton" class="btn btn-primary btn-round btn-block">Create New Password</button>
                    </div>
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
