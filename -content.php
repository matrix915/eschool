<?php
if (core_user::getUserID()) {
    $home = core_user::getCurrentUser()->isStudent() ? '/_/student' : '/home';
    header('Location: ' . $home);
    exit();
}


cms_page::setPageTitle('Welcome to My Tech High\'s Student Information Services');
cms_page::setPageContent(
    '<p>Our personalized disatance education program is available to students between the ages of 5-18 residing in Utah who learn best at home.</p>
    <p>Learn more at <a href="https://www.mytechhigh.com">mytechhigh.com</a></p>',
    'Below Application Content',
    cms_content::TYPE_HTML
);

cms_page::setPageContent(
    '<p><b>NOTE:</b> System emails will come from either <span style="color:#d81b60;">system@mytechhigh.com</span> or <span style="color:#d81b60;">admin@mytechhigh.com</span>, so be sure to add these email addresses to your contact list.</p>',
    'Below Home Contents',
    cms_content::TYPE_HTML
);

core_loader::includejQueryValidate();
core_loader::addClassRef('page-home-page layout-full');
core_loader::printHeader();

$current_year = mth_schoolYear::getCurrent();
$year = mth_schoolYear::getApplicationYear();
$other_year = $year != $current_year ? $current_year : null;
$midyearavailable = $current_year ? ($current_year->midYearAvailable() && $current_year->isMidYearAvailable()) : false;
?>
<!-- Page -->
<nav class="site-navbar navbar navbar-fixed-top navbar-inverse bg-blue-600">
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
</div>
</nav>
<div class="page" data-animsition-in="fade-in" data-animsition-out="fade-out">
    <!-- <div class="brand text-center p-10" style="background:#2196f3">
    <div class="navbar-brand navbar-brand-center ">
        <div class="arrow-left">
        </div>
        <span class="brand-logo-text">
            Utah Infocenter
        </span>
        <div class="arrow-right">
        </div>
    </div> -->
    <div class="page-content container">
        <div class="row mt-60">
            <div class="col-md-6">
                <h2 style="color:#fff">RETURNING PARENT OR STUDENT?</h2>
                <a class="btn btn-primary btn-lg" href="/?login=1">Login to InfoCenter</a>
                <p class="mt-10"><a href="/_/user/forgot">Forgot password?</a></p>
                <div class="font-size-16 text-left" style="color:#fff">
                    <h4 class="mt-20" style="color:#fff">Access InfoCenter several times each week to:</h4>
                    <ul class="pl-20">
                        <li>Read Announcements and view the Calendar</li>
                        <li>Check on your child(ren)’s Homeroom grades</li>
                        <li>Update student and family information</li>
                        <li>Submit applications and enrollment packets</li>
                        <li>Build and manage schedules in Schedule Builder</li>
                        <li>Submit and track reimbursements</li>
                        <li>Submit state testing opt-out forms</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 text-center">
                <div class="mt-40 " style="display:flex">
                    <img src="<?= core_config::getThemeURI() ?>/images/utah.png" class="img-fluid">
                    <img src="<?= core_config::getThemeURI() ?>/images/OR_logo.png" style="height: 250px;"   class="img-fluid">
                </div>
            </div>

        </div>


    </div>
    <section style="background: rgba(255,255,255,0.8);">
        <div class="container">
            <div class="row">
                <div class="col pt-50 pb-50">
                    <h2>NEW PARENT?</h2>
                    <?php if ($other_year && $midyearavailable) : ?>
                        <div class="form-group clearfix mt-20">
                            <a class="btn btn-success btn-lg" href="application?y=<?= $other_year->getStartYear() ?>">Apply for the <?= $other_year ?> Program (January Start)</a>
                        </div>
                    <?php endif ?>
                    <?php if ($year) : ?>
                        <div class="form-group clearfix mt-20">
                            <a class="btn btn-mth-orange btn-lg" href="application">Apply for the <?= $year ?> Program (August Start)</a>
                        </div>
                    <?php endif ?>
                    <div class="font-size-16 mt-20">
                        <?= cms_page::getDefaultPageContent('Below Application Content', cms_content::TYPE_HTML); ?>
                    </div>

                    <div class="mt-40">
                        <?= cms_page::getDefaultPageContent('Below Home Contents', cms_content::TYPE_HTML); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="page-copyright page-copyright-inverse mt-0 pt-2 pb-2 bg-blue-600">
        <div class="container">
            <div class="row">
                <div class="col my-auto">
                    <?php core_loader::printMTHFooterContent(true); ?>
                </div>
                <div class="col text-center">
                    <a href="https://aws.amazon.com/what-is-cloud-computing"><img src="https://d0.awsstatic.com/logos/powered-by-aws-white.png" alt="Powered by AWS Cloud Computing"></a>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
core_loader::printFooter();
?>
<script>
    $(function() {
        if (<?= !core_config::isProduction() ? 'true' : 'false' ?>) {
            swal({
                    title: 'Note',
                    text: "This is a BETA Test Site. If you are a beta tester, click 'Continue'. If NOT, click 'Go to My Tech High' to return to the active My Tech High Site.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-primary",
                    confirmButtonText: "Go to My Tech High",
                    cancelButtonText: "Continue",
                    closeOnConfirm: false,
                    closeOnCancel: true
                },
                function() {
                    location.href = "https://infocenter.mytechhigh.com";
                });
        }
    });
</script>