<?php
use mth\aws\ses;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // sanitize the email & activation code
    $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
    $activation_code = $_GET['activation_code'];

    if ($email && $activation_code) {
        $user = mth_emailverifier::getByEmailCode($email, $activation_code);

        // if user exists and activate the user successfully
        if ($user && mth_emailverifier::activateUser($email, $activation_code)) {
            $ses = new ses;
            $ses->sendConfirmationEmail($email);
            header('Location: ' . 'https://' . $_SERVER['HTTP_HOST'] . '/verify');
        } else {
            header('Location: ' . 'https://' . $_SERVER['HTTP_HOST'] . '/verify/failed');
        }
    } else {
        header('Location: ' . 'https://' . $_SERVER['HTTP_HOST'] . '/verify/failed');
    }
} else {
    header('Location: ' . 'https://' . $_SERVER['HTTP_HOST'] . '/verify/failed');
}
