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

  <?php core_loader::printCssRefsOnly(); ?>
  <link rel='stylesheet' href="https://fonts.googleapis.com/css?family=Roboto:400,400italic,700">

  <?php if(core_config::isProduction()):?>
  <!-- Global site tag (gtag.js) - Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=UA-145682523-2"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-145682523-2');
  </script>
  <?php endif?>
</head>

<body class="animsition page-profile-v3">

  <nav class="site-navbar navbar navbar-fixed-top navbar-inverse bg-blue-600 <?=core_user::isUserAdmin()?'admin-nav':''?>" role="navigation">
    <?php if(core_user::isUserAdmin()):?>
    <div class="navbar-header">
        <button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-collapse" data-toggle="collapse">
            <i class="icon md-more" aria-hidden="true"></i>
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
    <?php endif;?>
    <div class="navbar-container container-fluid">
      <div class="navbar-brand navbar-brand-center">
      <div class="arrow-left">
            </div>
        <a href="/" class="brand-logo-text">
          <span>Info</span><span class="right">Center</span>
        </a>
        <div class="arrow-right">
            </div>
      </div>
     
      <?php if(core_user::isUserAdmin()):?>
      <!-- Collapse Navbar -->
      <div class="collapse navbar-collapse navbar-collapse-toolbar" id="site-navbar-collapse">
        <!-- Navbar Toolbar Right -->
        <ul class="nav navbar-toolbar navbar-right navbar-toolbar-right">
          <li class="nav-item">
            <a class="nav-link" href="/_/admin/reports">Admin</a>
          </li>
          <?php if (($availableAreas = cms_page::getDefaultPageAvailableContentAreas())): ?>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false" data-animation="scale-up" role="button">
                <i class="icon md-edit"></i>
                </a>
                <div class="dropdown-menu" role="menu">
                    <?php foreach ($availableAreas as $location => $content): ?>
                        <a class="dropdown-item" role="menuitem" onclick="<?= getContentEditLink($content, cms_page::getDefaultPagePath()) ?>">
                            <?= $location ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </li>
          <?php endif; ?>
        </ul>
        <!-- End Navbar Toolbar Right  -->
      </div>
      <!-- End Collapse Navbar -->
    <?php endif;?>
    </div>
  </nav>
  <?php if(core_user::isEmulating()):?>
    <div class="alert alert-info">
      <a type="button" class="btn btn-info" href="?stope=1">Stop Emulating</a>
    </div>
  <?php endif;?>
