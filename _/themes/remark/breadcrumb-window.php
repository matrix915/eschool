<?php
$user = core_user::getCurrentUser();
?>
<div class="page-header page-header-bordered">
    <h1 class="page-title"><?= cms_page::getDefaultPageTitleContent() ?></h1>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?=$user->getHomeUrl()?>">Home</a></li>
        <li class="breadcrumb-item active"><?= cms_page::getDefaultPageTitleContent() ?></li>
    </ol>
    <div class="page-header-actions">
    <a class="btn btn-secondary btn-round" href="<?=$user->getHomeUrl()?>">Close</a>
    </div>
</div>