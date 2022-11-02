<?php
include '../app/inc.php';

core_loader::formSubmitable() || die();

if (!(req_post::bool('email') && req_post::bool('first_name') && req_post::bool('last_name'))) {
    core_notify::addError('Unable to collect your information. Please try again.');
    core_loader::redirect(req_post::url('path') . '?login=1');
}

if (($user = core_user::findUser(req_post::txt('email')))) {
    core_notify::addError('There is already an account associated with the email: ' . req_post::txt('email'));
    core_notify::addError('Use the "I forgot my password" link if needed.');
    core_loader::redirect(req_post::url('path') . '?login=1');
}

if (($user = core_user::newUser(req_post::txt('email'), req_post::txt('first_name'), req_post::txt('last_name'), 1))) {
    $user->sendPasswordResetEmail();
    core_notify::addMessage('Your account was succesfully created!');
    core_loader::redirect('/_/user/welcome');
} else {
    core_notify::addError('Unable to create an account. Please try again later, or contact us for support');
    core_loader::redirect(req_post::url('path') . '?login=1');
}
