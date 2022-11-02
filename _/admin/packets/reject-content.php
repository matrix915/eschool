<?php

($packet = mth_packet::getByID($_GET['packet'])) || die('Packet Not Found. Please refresh');

($student = $packet->getStudent()) || die('Student Not Found. Please refresh');
($parent = $student->getParent()) || die('Parent Not Found. Please refresh');

$fileTypeArr = array(
    'bc' => 'Birth Certificate',
    'im' => 'Current immunization record or Personal Exemption form',
    'ur' => 'Proof of Residency',
    'iep' => 'IEP or 504 Plan',
    'im-7' => '7th Grade Immunization',
    'im-k' => 'Kindergarten Immunization',
    // 'last_school' => 'Name and Address of Last School Attended'
);
if ($student->getAddress()->getState() == 'OR') {
    $fileTypeArr['itf'] = 'Inter-District Form';
    $fileTypeArr['im-OR'] = 'Oregon Immunization';
}

$fileTypeInstructions = array(
    'ur' => '<b style="color:red;">issued within 60 days</b> (current utility bill, mortgage or rental statement)',
);

$emailContent = core_setting::get('packetMissingInfoEmail', 'Packets');

$link = (@$_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/student/' . $student->getSlug() . '/packet';

$emailContentFilled = str_replace(
    array(
        '[PARENT]',
        '[STUDENT]',
        '[LINK]',
    ),
    array(
        $parent->getPreferredFirstName(),
        $student->getPreferredFirstName(),
        '<a href="' . $link . '" target="_blank">' . $link . '</a>',
    ),
    preg_replace('/<p>\s*\[([A-Z0-9]+)\]\s*<\/p>/i', '[$1]', $emailContent->getValue())
);

function valid_form()
{
    if (!core_loader::formSubmitable('packet-reject-form-' . $_GET['form'])) {
        core_notify::addError('Unable validate form please try again.');
        return false;
    }

    if (!isset($_POST['reupload_files'])) {
        core_notify::addError('Please select atleast 1 required file to upload.');
        return false;
    }
    return true;
}

if (!empty($_GET['form']) && valid_form()) {
    $packet->setStatus(mth_packet::STATUS_MISSING);
    if (in_array('last_school', $_POST['reupload_files'])) {
        $_POST['reupload_files'][] = 'last_school_address';
    }

    $packet->setReuploadFiles($_POST['reupload_files']);
    $reuploadFile = array();
    $reuploadFiles = $packet->getReuploadFiles();
    foreach ($reuploadFiles as $fileTypeID) {
        if (!isset($fileTypeArr[$fileTypeID])) {
            continue;
        }
        $reuploadFile[] = $fileTypeArr[$fileTypeID]
            . (isset($fileTypeInstructions[$fileTypeID]) ? ' ' . $fileTypeInstructions[$fileTypeID] : '');
    }
    $email = new core_emailservice();
    $email_result = $email->send(
        array($parent->getEmail()),
        core_setting::get('packetMissingInfoEmailSubject', 'Packets')->getValue(),
        str_replace(
            array(
                '[FILES]',
                '[INSTRUCTIONS]',
            ),
            array(
                "\n<ul>\n<li>" . implode("</li>\n<li>", $reuploadFile) . "</li>\n</ul>\n",
                strip_tags($_POST['specific_instructions'], '<a><b><i><em><strong><br><ul><li><ol><u><s><p>'),
            ),
            $emailContentFilled
        ),
        null,
        [core_setting::getSiteEmail()->getValue()]
    );

    // $email->send(["test@gmail.com"], "Test email", "HTML content", ["infocenter+staging@mytechhigh.com"]);

    if (!$email_result) {
        core_notify::addError('Unable to send Missing Info Email to parent!');
        echo "<script>console.log('email sent error.')</script>";
    } else {
        echo "<script>console.log('email sent success.')</script>";

        foreach (['im-k', 'im-7','im-OR'] as $extraIm) {
            $position = array_search($extraIm, $reuploadFiles);
            if ($position !== false) {
                $reuploadFiles[$position] = 'im';
            }
        }
        $packet->setReuploadFiles(array_unique($reuploadFiles));
        // $addSpecialInstructions = $_POST['specific_instructions'] ? "" . preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", strip_tags($_POST['specific_instructions'])) : "\n";
        $addSpecialInstructions = $_POST['specific_instructions'] ? "\n\n" . preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", strip_tags($_POST['specific_instructions'])) : "\n";
        $oldNotes = $packet->getAdminNotes() ? "\n\n" . $packet->getAdminNotes() : '';
        $packet->setAdminNotes(date('m/d/Y') . " - Missing Info\n- " . strip_tags(implode("\n- ", $reuploadFile)) . $addSpecialInstructions . $oldNotes);
    }

    exit('<html><head><script>
   top.global_popup_iframe_close(\'mth-packet-reject\');
   top.document.getElementById(\'mth_packet_edit\').getElementsByTagName(\'iframe\')[0].contentWindow.location.reload();
    </script></head></html>');
}

core_loader::includeCKEditor();

cms_page::setPageTitle('Reject Packet');
core_loader::isPopUp();
core_loader::printHeader();

?>
<style>
  #email-preview {
    padding: 20px;
    border: dotted 1px #ddd;
    color: #999;
  }
</style>

<h2>Missing Info on <?=$student->getPreferredFirstName()?>'s Enrollment Packet</h2>
<form action="?packet=<?=$packet->getID()?>&form=<?=uniqid()?>" method="post">
  <div lcass="form-group">
    <label>Require File Uploads</label>
    <?php foreach ($fileTypeArr as $fileTypeID => $fileType): ?>
      <div class="checkbox-custom checkbox-primary">
        <input type="checkbox" name="reupload_files[]" id="reupload_files-<?=$fileTypeID?>" value="<?=$fileTypeID?>">
        <label for="reupload_files-<?=$fileTypeID?>">
          <?=$fileType?>
        </label>
      </div>
    <?php endforeach;?>
  </div>
  <p>
    <label>Specific Instructions</label>
    <textarea name="specific_instructions" id="specific_instructions"></textarea>
  </p>

  <div class="card">
    <div class="card-header">
      <h4 class="card-title mb-0">Email Preview</h4>
    </div>
    <div id="email-preview" class="card-block">
      <?=str_replace(
    array(
        '[FILES]',
        '[INSTRUCTIONS]',
    ),
    array(
        '<span id="email-file-list"></span>',
        '<span id="email-instructions"></span>',
    ),
    $emailContentFilled
);?>
    </div>
  </div>
  <p>
    <button type="submit" class="btn btn-round btn-primary btn-submit">Send</button>
    <button type="button" class="btn btn-round btn-secondary" onclick="top.global_popup_iframe_close('mth-packet-reject')">Cancel</button>
  </p>
</form>
<script>
  CKEDITOR.config.removePlugins = "image,forms,youtube,iframe,print,stylescombo,table,tabletools,undo,specialchar,removeformat,pastefromword,pastetext,smiley,font,clipboard,selectall,format,blockquote,div,resize,elementspath,find,maximize,showblocks,sourcearea,scayt,colorbutton,about,wsc,justify,bidi,horizontalrule";
  CKEDITOR.config.removeButtons = "Subscript,Superscript,Anchor";
  var specific_instructions = $('#specific_instructions');
  specific_instructions.ckeditor();
  var fileTypes = <?=json_encode($fileTypeArr)?>;
  var fileTypeInstructions = <?=json_encode($fileTypeInstructions)?>;
  var emailFiles = $('#email-file-list');
  var emailInstructions = $('#email-instructions');
  setInterval(function() {
    var selectedFiles = [];
    var has7thGradeIm = false;
    var hasKGradeIm = false;
    var hasInterDItf = false;
    var hasIm = false;
    var hasOregonIM = false;
    var hasHarmony = false;
    $('input[name="reupload_files[]"]:checked').each(function() {
      selectedFiles.push(fileTypes[this.value] + (fileTypeInstructions[this.value] ? ' ' + fileTypeInstructions[this.value] : ''));
      if(this.value === 'im') {
        hasIm = true;
        if(specific_instructions.val().indexOf('standard-immunization-instructions') < 0) {
          const imString = '<div id="standard-immunization-instructions"><p>State law requires that all students be <a href="https://immunize.utah.gov/information-for-the-public/immunization-recommendations/school-childcare-immunization-requirements/">fully immunized</a> or submit a <a href="https://immunize.utah.gov/immunization-education-module/">Personal Exemption</a> form. The immunization record you sent does not include the following:</p><ul><li></li></ul></div>';
          CKEDITOR.instances.specific_instructions.insertHtml(imString, 'unfiltered_html');
        }
      }
      if(this.value === 'im-7') {
        has7thGradeIm = true;
        if(specific_instructions.val().indexOf('7th-grade-immunization-instructions') < 0) {
          const SeventhGradeimString = '<div id="7th-grade-immunization-instructions"><p>State law requires that students entering 7th grade be <a href="https://immunize.utah.gov/information-for-the-public/immunization-recommendations/school-childcare-immunization-requirements/">fully immunized</a> or submit a <a href="https://immunize.utah.gov/immunization-education-module/">Personal Exemption</a> form that was issued in ' + new Date().getFullYear() + '. Please provide an exemption form that was issued this year.</p</div>';
          CKEDITOR.instances.specific_instructions.insertHtml(SeventhGradeimString, 'unfiltered_html');
        }
      }
      if(this.value === 'im-k') {
        hasKGradeIm = true;
        if(specific_instructions.val().indexOf('k-grade-immunization-instructions') < 0) {
          const KGradeimString = '<div id="k-grade-immunization-instructions"><p>State law requires that students entering kindergarten be <a href="https://immunize.utah.gov/information-for-the-public/immunization-recommendations/school-childcare-immunization-requirements/">fully immunized</a> or submit a <a href="https://immunize.utah.gov/immunization-education-module/">Personal Exemption</a> form that was issued in ' + new Date().getFullYear() + '. Please provide an exemption form that was issued this year.</div></div>';
          CKEDITOR.instances.specific_instructions.insertHtml(KGradeimString, 'unfiltered_html');
        }
      }
      if(this.value === 'itf') {
        hasInterDItf = true;
        if(specific_instructions.val().indexOf('inter-district-form-upload-instructions') < 0) {
          const hasInterDItfString = '<div id="inter-district-form-upload-instructions"><p>Please fill out and upload your district\'s  \"Inter-district Transfer Form\" <a href="https://docs.google.com/spreadsheets/d/1rpdFhGBHumz7o9aHKcwSY2dW7W5hlgwbMTUYc1aD4mQ/edit#gid=0">(see list).</a></div></div';
          CKEDITOR.instances.specific_instructions.insertHtml(hasInterDItfString, 'unfiltered_html');
        }
      }
      //OR Immunization only
      if(this.value === 'im-OR') {
        hasOregonIM = true;
        if(specific_instructions.val().indexOf('OregonImmunization') < 0) {
          const oregonIMinstruction = `<div id="OregonImmunization">
                                            <p>State law requires that all students be 
                                              <a href="https://www.oregon.gov/oha/PH/PREVENTIONWELLNESS/VACCINESIMMUNIZATION/GETTINGIMMUNIZED/Pages/SchRequiredImm.aspx" target="_blank">
                                                    fully immunized
                                              </a> 
                                              or submit a 
                                              <a href="https://www.oregon.gov/oha/PH/PREVENTIONWELLNESS/VACCINESIMMUNIZATION/GETTINGIMMUNIZED/Pages/non-medical-exemption.aspx" target="_blank">Personal Exemption</a> form. 
                                              The immunization record you sent does not include the following:
                                            <ul>
                                              <li></li>
                                            </ul>
                                            </p>
                                        </div`;
          CKEDITOR.instances.specific_instructions.insertHtml(oregonIMinstruction, 'unfiltered_html');
        }
      }
      if(this.value === 'last_school') {
        hasHarmony = true;
        if(specific_instructions.val().indexOf('Harmony') < 0) {
          const harmonyString = '<div id="harmony-instructions"><p>You listed Harmony as your prior school of enrollment.  Please contact them to determine in which of their partner schools your child was enrolled.</p></div>';
          CKEDITOR.instances.specific_instructions.insertHtml(harmonyString, 'unfiltered_html');
        }
      }
    });

    if(!hasIm && specific_instructions.val().indexOf('standard-immunization-instructions') >= 0) {
      CKEDITOR.instances.specific_instructions.document.getById('standard-immunization-instructions').remove()
    }
    if(!has7thGradeIm && specific_instructions.val().indexOf('7th-grade-immunization-instructions') >= 0) {
      CKEDITOR.instances.specific_instructions.document.getById('7th-grade-immunization-instructions').remove()
    }
    if(!hasKGradeIm && specific_instructions.val().indexOf('k-grade-immunization-instructions') >= 0) {
      CKEDITOR.instances.specific_instructions.document.getById('k-grade-immunization-instructions').remove()
    }
    if(!hasInterDItf && specific_instructions.val().indexOf('inter-district-form-upload-instructions') >= 0) {
      CKEDITOR.instances.specific_instructions.document.getById('inter-district-form-upload-instructions').remove()
    }
    if(!hasOregonIM && specific_instructions.val().indexOf('OregonImmunization') >= 0) {
      CKEDITOR.instances.specific_instructions.document.getById('OregonImmunization').remove()
    }
    if(!hasHarmony && specific_instructions.val().indexOf('harmony-instructions') >= 0) {
      CKEDITOR.instances.specific_instructions.document.getById('harmony-instructions').remove()
    }

    if(selectedFiles.length > 0) {
      emailFiles.html('<ul><li>' + selectedFiles.join('</li><li>') + '</li></ul>');
    } else {
      emailFiles.html('');
    }
    // console.log("emailFiles.val(): ", '<ul><li>' + selectedFiles.join('</li><li>') + '</li></ul>')
    // console.log("specific_instructions.val(): ", specific_instructions.val())
    emailInstructions.html(specific_instructions.val());
  }, 1000);
</script>
<script>
  $(function() {
    $('.btn-submit').click(function() {
      global_waiting();
    });
  });
</script>
<?php

core_loader::printFooter();

?>

