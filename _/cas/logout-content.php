<?php

//http://staging.mytechhigh.com/_/cas/logout?destination=https%3A%2F%2Fmytechhigh.test.instructure.com%2Flogin%2Fcas%2F1169&gateway=true

core_user::logout();

$destination = req_get::url('destination', false);

if (mth_cas_ticket::validateService($destination)) {
    header('Location: ' . $destination);
    exit();
}

core_loader::redirect('/');