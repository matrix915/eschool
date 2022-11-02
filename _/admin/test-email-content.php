<?php

// cms_page::setDefaultTempTitle('Email Test');
// core_loader::printHeader();

// echo 'Sending test email to abe@goodfront.com...';

// $email = new core_email(array('abe@goodfront.com'), 'This is a test', '<p>This is a test email to abe@goodfront.com</p>');

// if ($email->send()) {
//     echo 'success';
// } else {
//     echo 'failed';
// }

// core_loader::printFooter();
$year = mth_schoolYear::getCurrent();
$filter = new mth_person_filter();
$filter->setStatusYear($year->getID());
$filter->setStatus(mth_student::STATUS_ACTIVE);
$personIDs = $filter->getPersonIDs();
echo '<pre>';
print_r($personIDs);
echo '</pre>';