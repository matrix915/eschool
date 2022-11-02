<?php

if (!($authTokenSet = core_setting::get(DROPBOX_TOKEN_VAR, 'DropBox'))) {
    $authTokenSet = core_setting::init(DROPBOX_TOKEN_VAR, 'DropBox', '');
}

core_loader::isPopUp();
core_loader::printHeader();

include 'dropbox-auth-header.php';

if (req_get::is_set('code') && req_get::is_set('state')) {

    $accessToken = $authHelper->getAccessToken(req_get::txt('code'), req_get::txt('state'), $callbackUrl);

    $authTokenSet->update($accessToken->getToken());

    ?>
    <p>The site has been authorized to interact with DropBox. <a onclick="window.close()">Close</a></p>
    <?php
} else {
    ?>
    <p>Unable to authorize interaction with DropBox. <a href="/_/admin/settings/dropbox-start">Click here</a> to try
        again</p>
    <?php
}

core_loader::printFooter();