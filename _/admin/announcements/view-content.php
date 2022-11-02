<?php
if(!($announcement = mth_announcements::getContentById($_GET['id']))){
    die('announcement not found');
}

   
$contentStr = $announcement->getContent();
$subject = $announcement->getSubject();

//core_loader::includeCKEditor();
cms_page::setPageTitle('Announcement');
core_loader::isPopUp();
core_loader::printHeader();
?>
<button class="btn btn-secondary btn-round iframe-close" type="button" onclick="top.global_popup_close('announcenment_view_popup')">Close</button>
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><?=$subject?></h4>
    </div>
    <div id="email-preview" class="card-block cke-preview">
        <?= $contentStr ?>
    </div>
</div>
<?php
core_loader::printFooter();
?>