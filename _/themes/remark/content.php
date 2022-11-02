<?php
core_loader::printHeader();
?>
<div class="page vertical-align text-center" data-animsition-in="fade-in" data-animsition-out="fade-out">&gt;
    <div class="page-content vertical-align-middle">
      <i class="icon md-face icon-spin page-maintenance-icon font-size-20" aria-hidden="true"></i>
      <h2><?= cms_page::getDefaultPageTitleContent()?></h2>
      <div class="font-size-16"><?=cms_page::getDefaultPageMainContent(); ?></div>
      <p><a class="btn btn-primary btn-round" style="color:#fff" href="/">GO TO HOME PAGE</a></p>
      <footer class="page-copyright">
      <?php core_loader::printMTHFooterContent();?>
      </footer>
    </div>
</div>
<?php
core_loader::printFooter();