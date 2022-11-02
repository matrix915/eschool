<?php
include $_SERVER['DOCUMENT_ROOT'] . '/_/app/inc.php';

header('Content-type: application/json');


if (!empty($_GET['parent']['email'])) {
    if (!mth_person::validateEmailAddress($_GET['parent']['email'])) {
        echo '"That email is already being used. <a href=\\"/?login=1\\" style=\\"pointer-events: auto;\\">Please login</a> to complete an application."';
    } else {
        // Check the formatting is correct
        if(filter_var($_GET['parent']['email'], FILTER_VALIDATE_EMAIL) === false){
            echo "Please enter a valid email address.";
        }
        // Next check the domain is real.
        $emailArray = explode("@", $_GET['parent']['email']);

        if (checkdnsrr(array_pop($emailArray), "MX")) {
            echo 'true';
        } else {
            echo 'Please enter a valid email address.';
        }
    }
}
if (!empty($_GET['student']['email'])) {
    if (($student = mth_student::getByStudentID($_GET['studentid']))
        && $student->getEmail() == strtolower($_GET['student']['email'])
    ) {
        echo 'true';
    } elseif (!mth_person::validateEmailAddress($_GET['student']['email'])) {
        echo '"That email is already being used."';
    } else {
        echo 'true';
    }
}