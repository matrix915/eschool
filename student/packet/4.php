<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */
/* @var $packetRunValidation */

$reuploadFiles = $packet->getReuploadFiles();
$reenrollingStudent = $student->getReenrolled();

if (!empty($_GET['packetForm4'])) {

  mth_packet_file::saveUploadedFiles(array('bc', 'ur'), $packet);
  mth_packet_file::saveUploadedFiles(array('im'), $packet, false);
  mth_packet_file::saveUploadedFiles(array('iep'), $packet, false);
  mth_packet_file::saveUploadedFiles(array('itf'), $packet, false);

  $uploaded = array();

   if($reenrollingStudent && !empty(array_keys($_FILES))) {
       $packet->setReenrollFiles(array_keys($_FILES));
   }

  if ($reuploadFiles) {
    $packet->setReuploadFiles(array_diff($reuploadFiles, array_keys($_FILES)));
  }

  $packetStep = 5;
  exit();
} elseif ($reenrollingStudent && $packet->requiresReenrollFiles()) {
    $missingDocument = false;
    foreach($packet->getReuploadFiles() as $document) {
      if($document != 'personal_information' && $document != 'last_school' && $document != 'last_school_address') {
        $missingDocument = true;
        core_notify::addError('Please upload the required documents');
        break;
      }
    }

    if(!$missingDocument) {
       $packetStep = 5;
    }
}
core_loader::printHeader('student');
core_loader::includejQueryUI();
core_loader::addJsRef('fileUploaderTransport', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.iframe-transport.js');
core_loader::addJsRef('fileUploader', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.fileupload.js');

core_loader::addCssRef('fileLoader', '/_/mth_includes/jQuery-File-Upload-10.31.0/css/jquery.fileupload.css');


  $locationText = "Utah";
  if($student->getAddress()->getState()=='OR'){
    $locationText = "Oregon";
  }
   $fileTypeArr = array(
      'bc' => array($student->getPreferredFirstName() . '\'s Birth Certificate', true),
      'im' => array($student->getPreferredFirstName() . '\'s Immunization Record or Personal Exemption Form', true),
      'ur' => array('Parent\'s Proof of '.$locationText.' Residency <b>issued within 60 days</b> <small>such as a current utility bill, mortgage or rental statement</small>', true),
      'iep' => array('If applicable, copy of IEP or 504 Plan', $packet->requireIEP())
   );

  
   if($student->getAddress()->getState()=='OR'){
      $fileTypeArr['itf']=  array('Upload your inter-district transfer form here',true);
   }

   $settingNamesByFileType = [
      'im' => core_setting::get('iep_documents', 'packet_settings')->getValue(),
      'ur' => core_setting::get('proof_of_residency', 'packet_settings')->getValue(),
      'iep' => core_setting::get('immunizations', 'packet_settings')->getValue()
   ];
?>
<div class="page">
  <?= core_loader::printBreadCrumb('window'); ?>
  <div class="page-content container-fluid">
    <div class="card">
      <?php include core_config::getSitePath() . '/student/packet/header.php'; ?>
      <div class="card-block">
        <h3>Required Documents to scan (or photograph) and upload</h3>
        <p>All documents are kept private and secure. Please upload files specific to this student (ie don't include another student's documents).</p>
        <div class="row">
          <?php foreach ($fileTypeArr as $fileTypeID => $fileType) : ?>
            <?php if ($fileTypeID == 'iep' && $packet && $packet->getSpecialEd(true) == mth_packet::SPECIALED_NO) {
              continue;
            } ?>
            <div class="col-md-6">
              <div class="file-block p-20">
                <?php $thisFileArr = mth_packet_file::getPacketFile($packet, $fileTypeID, false); ?>
                <p>
                  <label for="<?= $fileTypeID ?>" class="<?= (!$thisFileArr && $fileType[1])
                  || in_array($fileTypeID, $reuploadFiles)
                     ? 'file-error'
                     : '' ?>">
                    <?= $fileType[0] ?>
                    <?php if ($fileType[1]) : ?>
                      <small style="display: inline">(required)</small>
                    <?php endif; ?>
                  </label>
                </p>
                <?php 
                  if($fileTypeID =='itf'){
                  echo '<p style="margin-top:-1rem">Please see additional details <a href="https://docs.google.com/document/d/1sv66ExC5S9g3gsqoRqXYcwrlbLc1PSoN8slCaCwPiu0/preview" target="_blank">here.</a></p>';
                }
                ?>
                <p style="color: #090; <?= !$thisFileArr ? 'display:none' : '' ?>" id="<?= $fileTypeID ?>-uploaded_file">
                  <b>Uploaded File<?= (count($thisFileArr) > 1 ? 's' : '') ?>:</b>
                  <?php foreach ($thisFileArr as $fileNum => $thisFile) : /* @var $thisFile mth_packet_file */ ?><?= $fileNum > 0 ? ', ' : '' ?>
                  <a href="?file=<?= $thisFile ? $thisFile->getID() : '' ?>" class="<?= $fileTypeID ?>-file_name"><?= $thisFile ? $thisFile->getName() : '' ?></a><?php endforeach; ?>
                </p>
                <p>
                  <span class="fileinput-button btn btn-secondary btn-round">
                    <span class="button">Select file...</span>
                    <input type="file" name="<?= $fileTypeID ?>" id="<?= $fileTypeID ?>">
                  </span>
                </p>
                <div id="<?= $fileTypeID ?>_progress" class="upload_progress" style="display: none;">
                  <div class="upload_progress-bar" style="width: 0%;"></div>
                </div>
                <?php if (in_array($fileTypeID, ['im', 'iep','itf'])) : ?>
                  <small>Multiple files can be uploaded one at a time.</small><br>
                <?php endif; ?>
                <small>
                  Allowed file types: <?= implode(', ', mth_reimbursement::allowed_receipt_file_types()); ?><br>
                  <b>Less than 25MB</b>
                </small>
              </div>
            </div>

          <?php endforeach; ?>
        </div>
      </div>
      <div class="card-footer text-right">
        <button type="submit" class="btn btn-primary btn-round btn-lg" id="theSubmitButton" onclick="window.location='<?= $packetURI ?>'">Next &raquo</button>
      </div>
    </div>
  </div>
</div>


<?php

core_loader::printFooter('student');
?>

<script>
  $(function() {
    <?php foreach ($fileTypeArr as $fileTypeID => $fileType) : ?>
      $('#<?= $fileTypeID ?>').fileupload({
        url: '?packetForm4=<?= $fileTypeID ?>',
        dataType: 'text',
        dropZone: $(this).parents('div.file-block'),
        done: function(e, data) {
          <?php if (!in_array($fileTypeID, ['im', 'iep','itf'])) : ?>
            $('.<?= $fileTypeID ?>-file_name').remove();
          <?php else : ?>
            $('.<?= $fileTypeID ?>-file_name:last-child').after(', ');
          <?php endif; ?>
          $('#<?= $fileTypeID ?>-uploaded_file').fadeIn().append('<a class="<?= $fileTypeID ?>-file_name" title="Refresh the page to enable download">' + data.files[0].name + '</a>');
        },
        progressall: function(e, data) {
          var progress = parseInt(data.loaded / data.total * 100, 10);
          $('#<?= $fileTypeID ?>_progress').show();
          $('#<?= $fileTypeID ?>_progress .upload_progress-bar').css(
            'width',
            progress + '%'
          );
          if (progress < 10) {
            setUploadStatus('<?= $fileTypeID ?>', false);
          } else if (progress >= 100) {
            setUploadStatus('<?= $fileTypeID ?>', true);
          }
        },
        add: function(e, data) {
          var goUpload = true;
          var uploadFile = data.files[0];
          if (!(/\.(pdf|png|jpg|jpeg|gif|bmp)$/i).test(uploadFile.name)) {
            swal('', 'You must select an image file only', 'warning');
            goUpload = false;
          }
          if (uploadFile.size > 25000000) { // 2mb
            swal('', 'Please upload a smaller image, max size is 25 MB');
            goUpload = false;
          }
          if (goUpload == true) {
            data.submit();
          }
        }
      });
    <?php endforeach; ?>
  });
</script>

<script>
  <?php if ($packetRunValidation) : ?>
    $('.file-block label.file-error').css('color', 'red');
  <?php endif; ?>
  var theSubmitButton = $('#theSubmitButton');

  function setUploadStatus(file, complete) {
    if (setUploadStatus.files === undefined) {
      setUploadStatus.files = {};
    }
    setUploadStatus.files[file] = complete;
    if (!complete) {
      theSubmitButton.prop('disabled', false);
    }
  }

  function readyToMoveOn() {
    for (var f in setUploadStatus.files) {
      if (!setUploadStatus.files[f]) {
        return false;
      }
    }
    return true;
  }
  $('.file-block label.file-error').css('color', 'red');
  setInterval(function() {
    if (!readyToMoveOn()) {
      theSubmitButton.prop('disabled', true);
    } else {
      theSubmitButton.prop('disabled', false);
    }
  }, 1000);
</script>