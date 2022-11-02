<!DOCTYPE html>
<html class="no-js css-menubar" lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?=core_setting::getSiteName()?> - <?=cms_page::getDefaultPageTitleContent()?></title>

  <link rel="apple-touch-icon" href="<?=core_config::getThemeURI();?>/assets/photos/mth-logo.png">
  <link rel="shortcut icon" href="<?=core_config::getThemeURI();?>/assets/photos/mth-logo.png">

  <!-- Stylesheets -->
  <?php core_loader::addCssRef('bootstrap', core_config::getThemeURI() . '/assets/css/bootstrap.min.css')?>
  <?php core_loader::addCssRef('bootstrap-extend', core_config::getThemeURI() . '/assets/css/bootstrap-extend.min.css')?>
  <?php core_loader::addCssRef('site', core_config::getThemeURI() . '/assets/css/site.min.css')?>
  <?php core_loader::addCssRef('blue', core_config::getThemeURI() . '/assets/css/blue.css')?>

  <?php core_loader::addCssRef('infocenter', core_config::getThemeURI() . '/assets/css/infocenter.css')?>

  <!-- Plugins -->
  <?php core_loader::addCssRef('animsition', core_config::getThemeURI() . '/vendor/animsition/animsition.min.css')?>
  <?php core_loader::addCssRef('asScrollable', core_config::getThemeURI() . '/vendor/asscrollable/asScrollable.min.css')?>
  <?php core_loader::addCssRef('waves', core_config::getThemeURI() . '/vendor/waves/waves.min.css')?>
  <?php core_loader::addCssRef('sweetalert', core_config::getThemeURI() . '/vendor/sweetalert/sweetalert.min.css')?>
  <?php core_loader::addCssRef('toastr', core_config::getThemeURI() . '/vendor/toastr/toastr.min.css')?>

  <!-- Page -->
  <?php core_loader::addCssRef('profile', core_config::getThemeURI() . '/assets/css/profile.min.css')?>

  <!-- Fonts -->
  <?php core_loader::addCssRef('material', core_config::getThemeURI() . '/assets/fonts/material-design/material-design.min.css')?>
  <?php core_loader::addCssRef('font', core_config::getThemeURI() . '/assets/fonts/font-awesome/font-awesome.minfd53.css')?>


  <?php core_loader::addJsRef('breakpoints', core_config::getThemeURI() . '/vendor/breakpoints/breakpoints.min.js')?>
  <?php core_loader::includejQueryUI();?>
  <?php core_loader::printJsCssRefs();?>
  <link rel='stylesheet' href="https://fonts.googleapis.com/css?family=Roboto:400,400italic,700">
  <script>
    Breakpoints();
  </script>
</head>

<body class="animsition <?=core_user::isUserAdmins() || core_user::isUserTeacher() ? 'site-menubar-push site-menubar-fixed site-menubar-open' : ''?> <?=core_loader::printBodyClass()?>">

    <nav class="site-navbar navbar navbar-fixed-top navbar-inverse bg-blue-600 has-nav-header" role="navigation">
        <div class="navbar-header">
            <?php if (core_user::isUserAdmins() || core_user::isUserTeacher()): ?>
                <button type="button" class="navbar-toggler hamburger hamburger-close navbar-toggler-left" data-toggle="menubar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="hamburger-bar"></span>
                </button>
                <button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-collapse" data-toggle="collapse">
                    <i class="icon md-more" aria-hidden="true"></i>
                </button>
            <?php endif;?>
            <div class="navbar-brand navbar-brand-center site-gridmenu-toggle" data-toggle="gridmenu">
            <div class="arrow-left">
                    </div>
                <a href="" class="brand-logo-text">
                            <span>Info</span><span class="right">Center</span>
                        </a>
                        <div class="arrow-right">
                    </div>
            </div>
            <?php if (core_user::isUserAdmins() || core_user::isUserTeacher()): ?>
                <button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-search" data-toggle="collapse">
                    <span class="sr-only">Toggle Search</span>
                    <i class="icon md-search" aria-hidden="true"></i>
                </button>
            <?php endif;?>
        </div>
        <div class="navbar-container container-fluid">
            <!-- Navbar Collapse -->
            <div class="collapse navbar-collapse navbar-collapse-toolbar" id="site-navbar-collapse">
                <?php if (core_user::isUserAdmins() || core_user::isUserTeacher()): ?>
                    <!-- Navbar Toolbar -->
                    <ul class="nav navbar-toolbar">
                        <li class="nav-item hidden-float" id="toggleMenubar">
                            <a class="nav-link" data-toggle="menubar" href="#" role="button">
                            <i class="icon hamburger hamburger-arrow-left">
                                <span class="sr-only">Toggle menubar</span>
                                <span class="hamburger-bar"></span>
                            </i>
                            </a>
                        </li>
                        <li class="nav-item">
                        </li>

                    </ul>
                    <!-- End Navbar Toolbar -->
                    <div class="nav nav-toolbar navbar-left navbar-toolbar-left" style="margin-top:14px;">
                        <form >
                            <div class="input-search input-search-dark inline-search">
                                <i class="input-search-icon md-search" aria-hidden="true"></i>
                                <input type="text" class="form-control quick_search" name="" placeholder="Search...">
                            </div>
                        </form>
                    </div>

                    <!-- Navbar Toolbar Right -->
                    <ul class="nav navbar-toolbar navbar-right navbar-toolbar-right">
                        <?php if (($availableAreas = cms_page::getDefaultPageAvailableContentAreas())): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false" data-animation="scale-up" role="button">
                            <i class="icon md-edit"></i>
                            </a>
                            <div class="dropdown-menu" role="menu">
                                <?php foreach ($availableAreas as $location => $content): ?>
                                    <a class="dropdown-item" role="menuitem" onclick="<?=getContentEditLink($content, cms_page::getDefaultPagePath())?>">
                                        <?=$location?>
                                    </a>
                                <?php endforeach;?>
                            </div>
                        </li>
                        <?php endif;?>
                    </ul>
                    <!-- End Navbar Toolbar Right -->
                <?php endif;?>
                <div class="navbar-brand navbar-brand-center site-gridmenu-toggle" data-toggle="gridmenu">
                    <div class="arrow-left">
                    </div>
                    <a href="" class="brand-logo-text">
                        <span>Info</span><span class="right">Center</span>
                    </a>
                    <div class="arrow-right">
                    </div>
                </div>
            </div>
            <!-- End Navbar Collapse -->

            <!-- Site Navbar Seach -->
            <div class="collapse navbar-search-overlap" id="site-navbar-search">
                <form role="search">
                    <div class="form-group">
                        <div class="input-search full-width-search">
                            <form action>
                            <i class="input-search-icon md-search" aria-hidden="true"></i>
                            <input type="text" class="form-control quick_search" name="site-search" placeholder="Search..." autofocus style="padding-left: 4.109rem;">
                            <input type="hidden" id="quick_search_selected">
                            <button type="reset" class="input-search-close clear-input icon md-close" aria-label="Clear"></button>
                            <button type="button" class="input-search-close icon md-long-arrow-up" data-target="#site-navbar-search" data-toggle="collapse" aria-label="Close"></button>
                            </form>
                        </div>
                    </div>
                </form>
            </div>
            <!-- End Site Navbar Seach -->
        </div>
    </nav>

    <?php if (core_user::isUserAdmins() || core_user::isUserTeacher()): ?>
    <?php $avatar = core_user::getUserAvatar();?>
    <div class="site-menubar">
        <div class="site-menubar-header">
            <div class="cover overlay">
                <img class="cover-image" src="<?=core_config::getThemeURI()?>/assets/photos/dashboard-header.jpg" alt="...">
                <div class="overlay-panel vertical-align overlay-background" onclick="document.location='/_/user/profile';">
                    <div class="vertical-align-middle">
                        <div class="avatar avatar-lg">
                            <img src="<?=$avatar ? $avatar : (core_config::getThemeURI() . '/assets/portraits/default.png')?>" alt="">
                        </div>
                        <div class="site-menubar-info">
                            <h5 class="site-menubar-user"><?=core_user::getUserFirstName()?> <?=core_user::getUserLastName()?></h5>
                            <p class="site-menubar-email"><a><?=core_user::getUserEmail()?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="site-menu-item" style="background-color:#1e88e5;">
            <a href="?logout=1" style="color:#fff;padding: 0 25px;line-height: 38px;" >
                <i class="site-menu-icon fa fa-sign-out" aria-hidden="true"></i>
                <span class="site-menu-title">Log Out</span>
            </a>
        </div>
        <div class="site-menubar-body">
            <div>
                <div>
                    <?php
$adminnav = core_user::isUserSubAdmin() ? 'sub-admin' : 'admin';
$adminnav = core_user::isUserTeacher() ? 'teacher' : $adminnav;
cms_nav::printNav($adminnav, 'site-menu', null, true, true);
?>
                </div>
            </div>
        </div>
    </div>
    <?php endif;?>

    <div class="page">
        <div class="page-header">
            <h1 class="page-title"><?=cms_page::getDefaultPageTitleContent()?></h1>
            <p class="page-subtitle"><?=cms_page::getDefaultPageMainContent();?></p>
        </div>
        <div class="page-content container-fluid">