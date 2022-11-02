<?php
cms_page::setPageTitle('Email Verification Failed!');
cms_page::setPageContent('<p>You failed to verify your email.<p>
                          <p>Verification link expires 24 hours after your original verification email was sent. You may want to request another verification link from admin@mytechhigh.com.</p>');

core_loader::addClassRef('page-application layout-full');
core_loader::printHeader();
?>
<div class="page" data-animsition-in="fade-in" data-animsition-out="fade-out">
    <div class="page-content container">
        <div class="brand text-center ">
            <span class="brand-logo-text font-size-30">
                    <span>Info</span><span class="mth-blue">Center</span>
            </span>
        </div>
        <div class="panel application-panel mt-20">
            <div class="panel-body">
                <h3 class="mb-20"><?= cms_page::getDefaultPageTitleContent()?></h3>
                <div class="font-size-16"><?=cms_page::getDefaultPageMainContent(); ?></div>
                 <p><a class="btn btn-primary btn-round" style="color:#fff" href="/">GO TO HOME PAGE</a></p>
            </div>
        </div>
         <!-- End panel -->
         <footer class="page-copyright page-copyright-inverse text-center">
            <?php core_loader::printMTHFooterContent();?>
        </footer>
    </div>
</div>
<?php
core_loader::printFooter();
?>

