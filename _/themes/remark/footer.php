  <!-- Core  -->
  <?php core_loader::addJsRef('babel',core_config::getThemeURI().'/vendor/babel-external-helpers/babel-external-helpers.js')?>
  
  <?php core_loader::addJsRef('popper',core_config::getThemeURI().'/vendor/popper-js/umd/popper.min.js')?>
  <?php core_loader::addJsRef('bootstrap',core_config::getThemeURI().'/vendor/bootstrap/bootstrap.min.js')?>
  <?php core_loader::addJsRef('animsition',core_config::getThemeURI().'/vendor/animsition/animsition.min.js')?>
  <?php core_loader::addJsRef('jquery.mousewheel.min',core_config::getThemeURI().'/vendor/mousewheel/jquery.mousewheel.min.js')?>
  <?php core_loader::addJsRef('jquery-asScrollbar',core_config::getThemeURI().'/vendor/asscrollbar/jquery-asScrollbar.min.js')?>
  <?php core_loader::addJsRef('jquery-asScrollable',core_config::getThemeURI().'/vendor/asscrollable/jquery-asScrollable.min.js')?>
  <?php core_loader::addJsRef('waves',core_config::getThemeURI().'/vendor/ashoverscroll/jquery-asHoverScroll.min.js')?>
  <?php core_loader::addJsRef('mthJS',core_config::getThemeURI().'/vendor/waves/waves.min.js')?>
  
  <?php core_loader::addJsRef('jquery.touchSwipe',core_config::getThemeURI().'/vendor/touchswipe/jquery.touchSwipe.min.js')?>
  <?php core_loader::addJsRef('jquery.sweetalert',core_config::getThemeURI().'/vendor/sweetalert/sweetalert.min.js')?>
  <?php core_loader::addJsRef('toastr',core_config::getThemeURI().'/vendor/toastr/toastr.min.js')?>
  
  <!-- Plugins -->
  <?php core_loader::addJsRef('State',core_config::getThemeURI().'/assets/js/Section/State.min.js')?>
  <?php core_loader::addJsRef('Component',core_config::getThemeURI().'/assets/js/Section/Component.min.js')?>
  <?php core_loader::addJsRef('Plugin',core_config::getThemeURI().'/assets/js/Section/Plugin.min.js')?>
  <?php core_loader::addJsRef('Base',core_config::getThemeURI().'/assets/js/Section/Base.min.js')?>
  <?php core_loader::addJsRef('Config',core_config::getThemeURI().'/assets/js/Section/Config.min.js')?>

  <?php core_loader::addJsRef('Menubar',core_config::getThemeURI().'/assets/js/Section/Menubar.min.js')?>
  <?php core_loader::addJsRef('Sidebar',core_config::getThemeURI().'/assets/js/Section/Sidebar.min.js')?>
  <?php core_loader::addJsRef('PageAside',core_config::getThemeURI().'/assets/js/Section/PageAside.min.js')?>
  <?php core_loader::addJsRef('menu',core_config::getThemeURI().'/assets/js/menu.min.js')?>
  <?php core_loader::addJsRef('datatable',core_config::getThemeURI().'/vendor/datatable/datatable.min.js')?>
  <?php core_loader::addJsRef('datatableb',core_config::getThemeURI().'/vendor/datatable/datatable.bootstrap.min.js')?>
  <?php core_loader::addJsRef('menu',core_config::getThemeURI().'/assets/js/menu.min.js')?>


  <?php core_loader::printJsCssRefs() ?>

  <!-- Config -->
  <script>
    Config.set('assets', '../assets');
  </script>

  <!-- Page -->
  <?php core_loader::addJsRef('Site',core_config::getThemeURI().'/assets/js/Site.min.js')?>
  <?php core_loader::addJsRef('profile',core_config::getThemeURI().'/assets/js/profile.min.js')?>
  <?php core_loader::addJsRef('infocenter',core_config::getThemeURI().'/assets/js/infocenter.js')?>
  <?php core_loader::printJsCssRefs() ?>

  <?php core_notify::printNotice_remark() ?>
</body>
</html>