<!DOCTYPE html>
<html class="no-js css-menubar" lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?= core_setting::getSiteName() ?> - <?= cms_page::getDefaultPageTitleContent() ?></title>

  <link rel="apple-touch-icon" href="<?= core_config::getThemeURI();?>/assets/photos/mth-logo.png">
  <link rel="shortcut icon" href="<?= core_config::getThemeURI();?>/assets/photos/mth-logo.png">

  <!-- Stylesheets -->
  <?php core_loader::addCssRef('bootstrap', core_config::getThemeURI() . '/assets/css/bootstrap.min.css') ?>
  <?php core_loader::addCssRef('bootstrap-extend', core_config::getThemeURI() . '/assets/css/bootstrap-extend.min.css') ?>
  <?php core_loader::addCssRef('site', core_config::getThemeURI() . '/assets/css/site.min.css') ?>
  <?php core_loader::addCssRef('blue', core_config::getThemeURI() . '/assets/css/blue.css') ?>

  <?php core_loader::addCssRef('infocenter', core_config::getThemeURI() . '/assets/css/infocenter.css') ?>

  <!-- Plugins -->
  <?php core_loader::addCssRef('animsition', core_config::getThemeURI() . '/vendor/animsition/animsition.min.css') ?>
  <?php core_loader::addCssRef('asScrollable', core_config::getThemeURI() . '/vendor/asscrollable/asScrollable.min.css') ?>
  <?php core_loader::addCssRef('waves', core_config::getThemeURI() . '/vendor/waves/waves.min.css') ?>
  <?php core_loader::addCssRef('sweetalert', core_config::getThemeURI() . '/vendor/sweetalert/sweetalert.min.css') ?>
  <?php core_loader::addCssRef('toastr', core_config::getThemeURI() . '/vendor/toastr/toastr.min.css') ?>

  <!-- Page -->
  <?php core_loader::addCssRef('profile', core_config::getThemeURI() . '/assets/css/profile.min.css') ?>
  
  <!-- Fonts -->
  <?php core_loader::addCssRef('material', core_config::getThemeURI() . '/assets/fonts/material-design/material-design.min.css') ?>
  <?php core_loader::addCssRef('font', core_config::getThemeURI() . '/assets/fonts/font-awesome/font-awesome.minfd53.css') ?>
  
  
  <?php core_loader::addJsRef('breakpoints',core_config::getThemeURI().'/vendor/breakpoints/breakpoints.min.js')?>
  <?php core_loader::includejQueryUI();?>
  <?php core_loader::printJsCssRefs(); ?>
  <link rel='stylesheet' href="https://fonts.googleapis.com/css?family=Roboto:400,400italic,700">
  <script>
    Breakpoints();
  </script>
</head>

<body class="animsition site-menubar-push site-menubar-fixed site-menubar-open <?=  core_loader::printBodyClass()?>">
    
<nav class="site-navbar navbar navbar-fixed-top navbar-inverse bg-blue-600 has-nav-header" role="navigation">
        <div class="navbar-header">
            <button type="button" class="navbar-toggler hamburger hamburger-close navbar-toggler-left" data-toggle="menubar">
                <span class="sr-only">Toggle navigation</span>
                <span class="hamburger-bar"></span>
            </button>
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
        <div class="navbar-container container-fluid">
            <!-- Navbar Collapse -->
            <div class="collapse navbar-collapse navbar-collapse-toolbar" id="site-navbar-collapse">
                
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
        </div>
    </nav>

    <?php $avatar = (core_user::getUserAvatar()?:(core_config::getThemeURI().'/assets/portraits/default.png'));?>
    <div class="site-menubar">
        <div class="site-menubar-header">
            <div class="cover overlay">
                <img class="cover-image" src="<?= core_config::getThemeURI()?>/assets/photos/dashboard-header.jpg" alt="...">
                <div class="overlay-panel vertical-align overlay-background" onclick="document.location='/_/user/profile';">
                    <div class="vertical-align-middle">
                        <div class="avatar avatar-lg">
                            <img src="<?= $avatar ?>" alt="">
                        </div>
                        <div class="site-menubar-info">
                            <h5 class="site-menubar-user"><?= core_user::getUserFirstName()?> <?= core_user::getUserLastName()?></h5>
                            <p class="site-menubar-email"><a><?= core_user::getUserEmail()?></a></p>
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
                    $teacherNav = core_user::isUserTeacher()?'teacher':'teacherassistant';
                    cms_nav::printNav($teacherNav, 'site-menu',NULL,TRUE,TRUE);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="page-header">
            <h1 class="page-title"><?=cms_page::getDefaultPageTitleContent()?></h1>
            <p class="page-subtitle"><?=cms_page::getDefaultPageMainContent();?></p>
        </div>
        <div class="page-content container-fluid">
        <?php if(core_user::isEmulating()):?>
            <div class="alert alert-info">
            <a type="button" class="btn btn-info" href="?stope=1">Stop Emulating</a>
            </div>
        <?php endif;?>

            