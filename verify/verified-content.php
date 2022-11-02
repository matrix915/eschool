<?php
if(isset($_GET['form']) && isset($_POST['email'])){
    $email  = trim($_POST['email']);
    if($user = mth_emailverifier::update($email)){
        core_loader::redirect('/verify/thank');
    }else{
        core_loader::redirect();
    }

    exit();
}

cms_page::setPageTitle('Email Verification');
cms_page::setPageContent('');

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
                <form  action="?form=<?= uniqid() ?>" method="post">
                    <div class="form-group">
                        <label>Please input your email address:</label>
                        <input type="text" name="email" placeholder="" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-round btn-primary">Submit</button>
                </form>
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

