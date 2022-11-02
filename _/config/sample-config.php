<?php
/**
 * Copy this file to config.php and enter all the configuration variables for the environment
 */

core_config::setTheme('MyTechHigh');

core_config::setEnvironment(core_config::ENV_DEVELOPMENT);
//core_config::setDbHost('localhost'); //defaults to 'localhost'
core_config::setDbUser('***REMOVED***');
core_config::setDbPass('***REMOVED***');
core_config::setDb('***REMOVED***');

//only use this for ENV_DEVELOPMENT and ENV_STAGING
core_loader::addCssRef('MTHdev', core_config::getThemeURI() . '/mth_dev.css');

core_config::setSalt('***REMOVED***');

core_loader::addIndex(core_path::getPath('/student'));
core_loader::addCssRef('MTHGlobalCSS', core_config::getThemeURI() . '/mth_global.css');
core_loader::addJsRef('MTHGlobalJS', core_config::getThemeURI() . '/mth_global.js');
//for the api endpoint to access new frontend application
define('MUSTANG_API_URI','http://localhost:4000');

//for sending packets and other files to dropbox
define('DROPBOX_CLIENT_ID','***REMOVED***');
define('DROPBOX_CLIENT_SECRET','***REMOVED***');
define('DROPBOX_TOKEN_VAR','accessTokenV2');

//for sending reports to Google Drive
define('GOOGLE_CLIENT_ID','***REMOVED***');
define('GOOGLE_CLIENT_SECRET','***REMOVED***');
define('GOOGLE_REDIRECT_URI','/_/admin/settings/google-oauth');


function defineDefaultSettingConstants(){ //called in default_settings.php

    //DEFAULT SETTINGS --- These are used during site setup, many of these can be changed by admin uses.

    define('MTH_DEFAULT_EMAIL','abe@goodfront.com');
    define('MTH_GOOGLE_API_KEY','***REMOVED***');//used for javascript address validation

    define('MTH_SMTP_ADDRESS','***REMOVED***');
    define('MTH_SMTP_USER','***REMOVED***');
    define('MTH_SMTP_PASSWORD','***REMOVED***');
    define('MTH_SMTP_HOST','***REMOVED***');
    define('MTH_SMTP_PORT','465');
    define('MTH_SMTP_SECURE','ssl');

    define('MTH_CANVAS_URL','https://mytechhigh.test.instructure.com');
    define('MTH_CANVAS_TOKEN','***REMOVED***');

    define('MTH_WOO_KEY','***REMOVED***');
    define('MTH_WOO_SECRET','***REMOVED***');
    define('MTH_WOO_SITE','https://www.mytechhigh.com/');

    define('MTH_AWS_KEY_ID','***REMOVED***');
    define('MTH_AWS_KEY_SECRET','***REMOVED***');
    define('MTH_S3_REGION','***REMOVED***');
    define('MTH_S3_BUCKET','***REMOVED***');
}


mth_user::preFun();

core_secure::userFun();

mth_user::postFun();
