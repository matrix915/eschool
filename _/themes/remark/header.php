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

  <?php core_loader::printJsCssRefs(); ?>
  <link rel='stylesheet' href="https://fonts.googleapis.com/css?family=Roboto:400,400italic,700">
  <script>
    Breakpoints();
  </script>
</head>

<body class="animsition <?php core_loader::printClassRefs() ?>">