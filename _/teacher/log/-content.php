<?php
use mth\yoda\assessment;

core_user::getUserLevel() || core_secure::loadLogin();

if (!req_get::bool('log') || ! ($assessment = assessment::getById(req_get::int('log')))) {
     die('Assessment not found');
}
core_loader::isPopUp();
core_loader::printHeader();

?>
<div class="log-header">
     <button type="button" class="float-right btn btn-round btn-default" onclick="closeLog()">
          <i class="fa fa-close"></i>
     </button>
     <h4><span style="color:#2196f3"><?= $assessment->getTitle() ?></h4>
</div>
<div class="row" style="margin-top: 60px;">
     <?php mth_views_learninglog::getView($assessment)?>
</div>
<?php core_loader::printFooter(); ?>
<script>
     function closeLog() {
          parent.global_popup_iframe_close('yoda_assessment_view');
     }
</script>