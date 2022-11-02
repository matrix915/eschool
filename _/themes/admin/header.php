<!DOCTYPE html>
<html <?php core_loader::printHtmlAttributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= core_setting::getSiteName() ?> - <?= cms_page::getDefaultPageTitleContent() ?></title>
    <?php core_loader::addCssRef('AdminCSS', core_config::getAdminThemeURI() . '/admin.css') ?>
    <?php core_loader::addCssRef('SuperFish-Verticle', '/_/includes/superfish/css/superfish-vertical.css') ?>
    <?php core_loader::printJsCssRefs(); ?>
    <?php core_loader::includejQueryUI();?>
</head>
<body class="<?php core_loader::printBodyClass() ?>">
<div id="wrapper">
    <?php if (core_user::isUserAdmin()): ?>
        <nav id="site-nav"><?php cms_nav::printNav('admin', 'sf-menu sf-vertical') ?></nav>
    <?php endif; ?>
    <div id="main">
        <div id="main-content">
            <?php core_notify::printNotices() ?>
            <h1 id="page-title"><?= cms_page::getDefaultPageTitleContent() ?></h1>
