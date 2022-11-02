<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width"/>
    <?php $altTitle = cms_page::getDefaultPageContent('html-header-title', cms_content::TYPE_TEXT); ?>
    <title><?= core_setting::getSiteName() ?>
        - <?= $altTitle && $altTitle->getContent() ? $altTitle : cms_page::getDefaultPageTitleContent() ?></title>
    <?php core_loader::addCssRef('Theme1CSS', core_config::getThemeURI() . '/theme1.css') ?>
    <?php core_loader::addCssRef('Theme1popupCSS', core_config::getThemeURI() . '/theme1-popup.css') ?>
    <?php core_loader::printJsCssRefs(); ?>
    <meta name="description"
          content="<?= cms_page::getDefaultPageContent('meta-description', cms_content::TYPE_TEXT) ?>">
    <meta name="keywords" content="<?= cms_page::getDefaultPageContent('meta-keywords', cms_content::TYPE_TEXT) ?>">
</head>
<body class="<?php core_loader::printBodyClass() ?>">
<?php core_notify::printNotices() ?>
