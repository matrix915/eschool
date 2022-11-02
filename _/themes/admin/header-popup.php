<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width"/>
    <title><?= core_setting::getSiteName() ?> - <?= cms_page::getDefaultPageTitleContent() ?></title>
    <?php core_loader::addCssRef('AdminCSS', core_config::getAdminThemeURI() . '/admin.css') ?>
    <?php core_loader::addCssRef('AdminPopupCSS', core_config::getAdminThemeURI() . '/admin-popup.css') ?>
    <?php core_loader::printJsCssRefs(); ?>
</head>
<body class="<?php core_loader::printBodyClass() ?>">
<?php core_notify::printNotices() ?>
