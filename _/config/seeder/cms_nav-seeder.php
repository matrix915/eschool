<?php 

$adminNav = cms_nav::getNavObj('admin');
$other = $adminNav->addChild('/_/admin/nav', 'More', 50, true);
$other->addChild('/_/admin/immunizations', 'Immunizations', 0, true, "fa-medkit");