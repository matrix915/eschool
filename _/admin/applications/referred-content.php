<button type="button" class="iframe-close btn btn-secondary btn-round" onclick="parent.global_popup_iframe_close('application_referred')">
     Close
</button>
<?php
if(!req_get::bool('appid') 
     || !($application = mth_application::getApplicationByID(req_get::int('appid')))
     || !($student = $application->getStudent())
     || !($parent = $student->getParent())){
     die('No Refferal');
}
cms_page::setPageTitle('Refferred');
core_loader::isPopUp();
core_loader::printHeader();
?>

<div class="card">
     <div class="card-header">
          <h4 class="card-title mb-0">
               Referral Details
          </h4>
     </div>
     <div class="card-block">
          <b>Student:</b> <?=$student->getName()?><br>
          <b>Parent:</b> <?=$parent->getName()?><br>
          <b>Referred By:</b> <?=$application->getReferredBy()?><br>
     </div>
</div>
<?php
core_loader::printFooter();
?>