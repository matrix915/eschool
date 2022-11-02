<?php

$service = &$_SESSION['mth_cas_server_data']['service'];
if (req_get::bool('service')) {
    $service = req_get::url('service', false);
}

core_user::getUserLevel() || core_secure::loadLogin();

if (($ticket = mth_cas_ticket::newTicket($service))) {
    header('location: ' . $service . '?ticket=' . $ticket);
    exit();
}

cms_page::setPageTitle('CAS Login');
header("HTTP/1.1 403 FORBIDDEN");
header("Status: 403 FORBIDDEN");
core_loader::printHeader();
?>
    <p>Invalid service</p>
<?php
core_loader::printFooter();