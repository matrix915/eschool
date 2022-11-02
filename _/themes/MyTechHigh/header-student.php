<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width"/>
    <title><?= core_setting::getSiteName() ?> - <?= cms_page::getDefaultPageTitleContent() ?></title>
    <?php core_loader::addCssRef('mthCSS', core_config::getThemeURI() . '/style.css') ?>
    <?php core_loader::addJsRef('mthJS', core_config::getThemeURI() . '/style.js') ?>
    <?php core_loader::printJsCssRefs(); ?>
</head>
<body class="<?php core_loader::printBodyClass() ?>">
<!--[if lte IE 8]>
<ul class="core-notify-erros" style="padding:0; margin:0;">
    <li>This site may not look or work well in your browser. <a href="http://www.whatbrowser.org/">Please use a more
        up-to-date browser.</a></li>
</ul>
<![endif]-->
<div id="wrapper">
    <div class="bg">
        <div id="site-header">
            <h1 id="site-title"><a href="/" title="Home">
                    <img src="<?= core_config::getThemeURI() ?>/images/logo.png" alt="My Tech High">
                    <?= core_setting::getSiteName() ?>
                </a></h1>
        </div>
    </div>
    <?php if (core_user::getUserID() && ($parent = mth_parent::getByUser()) && !mth_purchasedCourse::hasPurchasedCourse($parent)): ?>
        <nav id="site-nav"><?php include core_config::getThemePath() . '/mth_menu.php'; ?></nav>
    <?php endif; ?>
    <div class="bg">
        <div id="main">
            <div id="main-content">
                <?php core_notify::printNotices() ?>
