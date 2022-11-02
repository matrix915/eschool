<?php
core_user::getUserID() || core_loader::redirect('/');

mth_user::requirePasswordChange() || core_loader::redirect('/');

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || exit();

    $user = core_user::getCurrentUser();

    if ($user->changePassword(req_post::raw('password'))) {
        mth_user::set_requirePasswordChange(false);
        core_notify::addMessage('Your password has been set.');
    } else {
        core_notify::addError('Unable to set the password. Please contact us for support.');
    }
    core_loader::redirect();
}

core_loader::includejQueryValidate();

cms_page::setPageTitle('Set Your Password');
core_loader::printHeader();
?>
    <script type="text/javascript">
        $(function () {
            $('#setPasswordForm').validate({
                rules: {
                    "password2": {
                        required: true,
                        equalTo: '#password'
                    }
                }
            });
        });
    </script>
    <form method="post" class="set-password-form" id="setPasswordForm"
          action="?form=<?= uniqid('setPasswordForm_') ?>">

        <p>
            <label for="password">New Password:</label>
            <input type="password" name="password" id="password" required>
            <small id="passwordDescription">Your password should be at least <b>8 characters</b> long and
                include <b>uppercase</b>, <b>lowercase</b>, <b>numbers</b> and <b>other characters (!@#$%^&*)</b>
            </small>
        </p>
        <p>
            <label for="password2">Re-enter Password:</label>
            <input type="password" name="password2" id="password2" required>
        </p>
        <p>
            This will be the password you will use from now on.
        </p>
        <p>
            <input type="submit" value="Set Password" id="submitButton">
        </p>
    </form>

<?php
core_loader::printFooter();
