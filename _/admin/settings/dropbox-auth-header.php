<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 3/21/17
 * Time: 10:45 AM
 */

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;

//Configure Dropbox Application
$app = new DropboxApp(DROPBOX_CLIENT_ID, DROPBOX_CLIENT_SECRET);

//Configure Dropbox service
$dropbox = new Dropbox($app);

//DropboxAuthHelper
$authHelper = $dropbox->getAuthHelper();

//Callback URL
$callbackUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/_/admin/settings/dropbox-finish';