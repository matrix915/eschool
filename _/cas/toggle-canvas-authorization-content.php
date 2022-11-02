<?php
if (!core_user::isUserAdmin()) {
    core_loader::print404headers();
    die('Page not found');
}

if (req_get::bool('enable')) {
    $response = mth_canvas::exec(
        '/accounts/' . mth_canvas::account_id() . '/account_authorization_configs',
        array(
            'auth_type' => 'cas',
            'auth_base' => 'http' . (core_secure::usingSSL() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/_/cas'
        ));
    if ($response) {
        core_notify::addMessage('SSO Enabled');
        core_setting::set('AccountAuthorizationConfigID', $response->id, core_setting::TYPE_TEXT, 'Canvas');
    } else {
        core_notify::addError('Unable to enable canvas SSO');
    }
    core_loader::redirect();
} elseif (req_get::bool('disable')) {
    $response = mth_canvas::exec(
        '/accounts/' . mth_canvas::account_id() . '/account_authorization_configs/' . core_setting::get('AccountAuthorizationConfigID', 'Canvas')->getValue(),
        array(),
        mth_canvas::METHOD_DELETE);
    if ($response) {
        core_notify::addMessage('SSO Disabled');
    } else {
        core_notify::addError('Unable to disable canvas SSO');
    }
    core_setting::set('AccountAuthorizationConfigID', '', core_setting::TYPE_TEXT, 'Canvas');
    core_loader::redirect();
}

core_loader::isPopUp();
core_loader::printHeader();
?>
    <button type="button"  class="iframe-close btn btn-secondary btn-round" onclick="top.global_popup_iframe_close('SSOpopup')">Close</button>
    <h2>Toggle Canvas SSO</h2>
    <p>
        <small><?= mth_canvas::url() ?></small>
    </p>
<?php if (($AuthID = core_setting::get('AccountAuthorizationConfigID', 'Canvas')) && $AuthID->getValue()): ?>
    <p>
        Canvas currently requires users to login through this site to access canvas.
    </p>
    <p>
        <a href="?disable=1">Disable SSO</a>
    </p>
<?php else: ?>
    <p>
        Enabling SSO will make canvas use this site to authenticate users. Only those with user accounts on this site
        will be able to login to canvas.
    </p>
    <p>
        <a href="?enable=1">Enable SSO</a>
    </p>
<?php endif; ?>

<?php
core_loader::printFooter();