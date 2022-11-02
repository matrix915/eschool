<?php

($packet = mth_packet::getByID($_GET['packet'])) || die('Packet Not Found. Please refresh');

($student = $packet->getStudent()) || die('Student Not Found. Please refresh');
($parent = $student->getParent()) || die('Parent Not Found. Please refresh');

$ageIssueOptions = array(
  'noprevious' => 'No Prior School History',
  'previous' => 'Enrolled in Previous School',
);

$contentOptions = array(
    'noprevious' => "<p>We're reviewing [STUDENT]'s Enrollment Packet and need to confirm their grade.
        The packet indicates that you'd like [STUDENT] in [GRADE_LEVEL] for the [APPLICATION_SCHOOL_YEAR] school year, but age-wise they could be in [BLANK].</p>
        <p>Has [STUDENT] previously been enrolled in a public school or public school program?  If so, in which year and grade?</p>",
    'previous' => "<p>We're reviewing [STUDENT]'s Enrollment Packet and need to confirm their grade.
        The packet indicates that you'd like [STUDENT] in [GRADE_LEVEL] for the [APPLICATION_SCHOOL_YEAR] school year, but age-wise they could be in [BLANK].</p>
        <p>In which year and grade was [STUDENT] most recently enrolled in [PREVIOUS_SCHOOL_NAME]?</p>",
);

$instructionsFind = array(
  '[STUDENT]',
  '[GRADE_LEVEL]',
  '[APPLICATION_SCHOOL_YEAR]',
  '[PREVIOUS_SCHOOL_NAME]',
);

$packetYear = mth_packet::getActivePacketYear($packet);

$instructionsReplace = array(
  $student->getPreferredFirstName(),
  $student->getGradeLevel(true, false, $packetYear->getID(), true),
  mth_packet::getActivePacketYear($packet),
  $packet->getLastSchoolName(),
);

$ageIssueInstructions = array(
  'noprevious' => str_replace(
    $instructionsFind,
    $instructionsReplace,
      $contentOptions['noprevious']
  ),
  'previous' => str_replace(
    $instructionsFind,
    $instructionsReplace,
      $contentOptions['previous']
  ),
);



$find = array(
  '[PARENT]',
  '[STUDENT]',
  '[GRADE_LEVEL]',
  '[YEAR]',
);

$replace = array(
  $parent->getPreferredFirstName(),
  $student->getPreferredFirstName(),
  $student->getGradeLevel(true, true),
  mth_packet::getActivePacketYear($packet),
);

$emailContent = core_setting::get('packetAgeIssueEmail', 'Packets');

$emailContentFilled = str_replace(
  $find,
  $replace,
  $emailContent->getValue()
);

function valid_form() {
  if (!core_loader::formSubmitable('packet-age-issue-form-' . $_GET['form'])) {
    core_notify::addError('Unable validate form please try again.');
    return false;
  }

  if (!isset($_POST['age-issues'])) {
    core_notify::addError('Please select if the student has prior school history or not.');
    return false;
  }

  if (strpos($_POST['specific_instructions'], '[BLANK]')) {
    core_notify::addError('Please replace the [BLANK] text in the specific instructions.');
    return false;
  }
  return true;
}

if (!empty($_GET['form']) && valid_form()) {
  $email = new core_emailservice();
  $success = $email->send(
      array($parent->getEmail()),
      core_setting::get('packetAgeIssueEmailSubject', 'Packets')->getValue(),
      str_replace(
        array(
          '[INSTRUCTIONS]',
        ),
        array(
          strip_tags($_POST['specific_instructions'], '<a><b><i><em><strong><br><ul><li><ol><u><s><p>'),
        ),
        $emailContentFilled
      ),
      null,
      [core_setting::getSiteEmail()->getValue()]
  );
  
  if (!$success) {
    core_notify::addError('Unable to send Missing Info Email to parent!');
  } else {
    $addSpecialInstructions = $_POST['specific_instructions'] ? "\n" . preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", strip_tags($_POST['specific_instructions'])) : "\n";
    $oldNotes = $packet->getAdminNotes() ? "\n\n" . $packet->getAdminNotes() : '';
    $packet->setAdminNotes(date('m/d/Y') . "- Age Issue\n- ". $ageIssueOptions[$_POST['age-issues'][0]] . $addSpecialInstructions . $oldNotes);
  }

  exit('<html><head><script>
   top.global_popup_iframe_close(\'mth-packet-age-issue\');
   top.document.getElementById(\'mth_packet_edit\').getElementsByTagName(\'iframe\')[0].contentWindow.location.reload();
    </script></head></html>');
}

core_loader::includeCKEditor();

cms_page::setPageTitle('Age Issue Packet');
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
    <h2>Age Issue on <?= $student->getPreferredFirstName() ?>'s Enrollment Packet</h2>
    <form action="?packet=<?= $packet->getID() ?>&form=<?= uniqid() ?>" method="post">
        <div class="form-group">
          <?php foreach ($ageIssueOptions as $ageOptionID => $ageOption) : ?>
              <div class="radio-custom radio-primary">
                  <input type="radio" name="age-issues[]" id="age_issue-<?= $ageOptionID ?>"
                    value="<?= $ageOptionID ?>"
                    <?= array_key_exists('age-issues', $_POST) && in_array($ageOptionID, $_POST['age-issues']) ? 'checked' : '' ?>>
                  <label for="age_issue-<?= $ageOptionID ?>">
                    <?= $ageOption ?>
                  </label>
              </div>
          <?php endforeach; ?>
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
              <?= str_replace(
                array('[INSTRUCTIONS]'),
                array('<span id="email-instructions"></span>'),
                $emailContentFilled
              ); ?>
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-round btn-primary btn-submit">Send</button>
            <button type="button" class="btn btn-round btn-secondary"
                    onclick="top.global_popup_iframe_close('mth-packet-age-issue')">Cancel
            </button>
        </p>
    </form>
    <script>
        CKEDITOR.config.removePlugins = "image,forms,youtube,iframe,print,stylescombo,table,tabletools,undo,specialchar,removeformat,pastefromword,pastetext,smiley,font,clipboard,selectall,format,blockquote,div,resize,elementspath,find,maximize,showblocks,sourcearea,scayt,colorbutton,about,wsc,justify,bidi,horizontalrule";
        CKEDITOR.config.removeButtons = "Subscript,Superscript,Anchor";
        var specific_instructions = $('#specific_instructions');
        specific_instructions.ckeditor();
        var ageIssueInstructions = <?= json_encode($ageIssueInstructions) ?>;
        var emailInstructions = $('#email-instructions');
        setInterval(function () {
            var hasNoPrevious = false;
            var hasPrevious = false;
            $('input[name="age-issues[]"]:checked').each(function () {
                if (this.value === 'noprevious') {
                    hasNoPrevious = true;
                    if(specific_instructions.val().indexOf('has-no-previous-school-instructions') < 0) {
                        const noPreviousString = "<div id='has-no-previous-school-instructions'>" + ageIssueInstructions[this.value] + "</div>";
                        CKEDITOR.instances.specific_instructions.insertHtml(noPreviousString, 'unfiltered_html');
                    }
                }
                if (this.value === 'previous') {
                    hasPrevious = true
                    if(specific_instructions.val().indexOf('has-previous-school-instructions') < 0) {
                        const previousString = "<div id='has-previous-school-instructions'>" + ageIssueInstructions[this.value] + "</div>";
                        CKEDITOR.instances.specific_instructions.insertHtml(previousString, 'unfiltered_html');
                    }
                }
            });
            if (!hasNoPrevious && specific_instructions.val().indexOf('has-no-previous-school-instructions') >= 0) {
                CKEDITOR.instances.specific_instructions.document.getById('has-no-previous-school-instructions').remove()
            }
            if (!hasPrevious && specific_instructions.val().indexOf('has-previous-school-instructions') >= 0) {
                CKEDITOR.instances.specific_instructions.document.getById('has-previous-school-instructions').remove()
            }
            emailInstructions.html(specific_instructions.val());
        }, 1000);
    </script>
    <script>
        $(function () {
            $('.btn-submit').click(function () {
                global_waiting();
            });
        });
    </script>
<?php

core_loader::printFooter();
