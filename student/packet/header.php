<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */

cms_page::setPageTitle('Enrollment Packet');
cms_page::setPageContent('');

core_loader::includejQueryValidate();
core_loader::addCssRef('packetStyle', core_config::getThemeURI() . '/packet-style.css');
core_loader::addJsRef('packetScript', core_config::getThemeURI() . '/packet-script.js');

core_loader::printHeader('student');

if (($reenrollingStudent = $student->getReenrolled())) {
   $documentsUnlocked = core_setting::get('iep_documents') || core_setting::get('proof_of_residency') || core_setting::get('immunizations');
   $packetUnlocked = core_setting::get('unlock_packet');
   $personalInformationUnlocked = core_setting::get('personal_information');
}

?>
<div class="card-header p-20">
    <h4 class="card-title mb-0"><?= $student ?>'s Enrollment Packet</h4>
</div>
<div class="card-block">
   <?= cms_page::getDefaultPageMainContent() ?>
   <?php if (!$packet->isSubmitted()): ?>
       <ul class="form-progress">
              <li><a href="<?= $packetURI ?>/1">Contact</a></li>
              <li><a href="<?= $packetURI ?>/2">Personal</a></li>
              <li><a href="<?= $packetURI ?>/3">Education</a></li>
              <li><a href="<?= $packetURI ?>/4">Documents</a></li>
          <li><a href="<?= $packetURI ?>/5">Submission</a></li>
       </ul>
   <?php endif; ?>
</div>