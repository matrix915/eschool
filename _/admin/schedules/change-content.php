<?php

($schedule = mth_schedule::getByID($_GET['schedule'])) || die('Schedule not found');
($student = $schedule->student()) || die('Schedule student missing');
($parent = $student->getParent()) || die('Student\'s parent missing');

if (!empty($_GET['form'])) {
  core_loader::formSubmitable($_GET['form']) || die();
  $success = false;

  if (isset($_POST['mth_schedule_period'])) {
    if ($schedule->requireChanges($_POST['mth_schedule_period'])) {
      core_notify::addMessage('Schedule has been set to require changes.');
    } else {
      core_notify::addError('Unable to set schedule for changes.');
      core_notify::addError('No email sent.');
      header('Location: ' . core_path::getPath() . '?schedule=' . $schedule->id());
      exit();
    }
  } elseif (isset($_POST['special'])) {
    $schedule->setStatus($schedule->isAccepted()
      ? mth_schedule::STATUS_CHANGE_POST
      : mth_schedule::STATUS_CHANGE);
    core_notify::addMessage('Schedule has been set to require changes.');
  }

  ( core_setting::get("schedulebcc", 'Schedules')->getValue() ? $bcc = explode(",", core_setting::get("schedulebcc", 'Schedules')->getValue()) : '' );
  $email = new core_emailservice();
  $success = $email->send(
    array($parent->getEmail()),
    cms_content::sanitizeText($_POST['subject']),
    cms_content::sanitizeAndFixHTML($_POST['content']),
    null,
    $bcc,
    [core_setting::getSiteEmail()->getValue()]
  );
  
  if (!$success) {
    core_notify::addError('Unable to send email!');
  }
  exit('<html><script>
    if(parent.updateSchedule){
      parent.updateSchedule(' . $schedule->id() . '); 
      parent.global_popup_iframe_close("mth_schedule-edit-' . $schedule->id() . '");
    }else{
      parent.location.reload(true);
    }
      </script></html>');
}

if (req_get::bool('secondSem')) {
  if (($oldSchedulePeriod = mth_schedule_period::get($schedule, mth_period::get(req_get::int('secondSem')), true))
    && !$oldSchedulePeriod->second_semester()
  ) {
    $oldSchedulePeriod->duplicateTo2ndSem(true);
  }
  core_loader::redirect('?schedule=' . $schedule->id());
}

$find = array(
  '[PARENT]',
  '[STUDENT]',
  '[SCHOOL_YEAR]',
  '[LINK]'
);
$link = 'http' . (core_secure::usingSSL() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/student/' . $student->getSlug() . '/schedule/' . $schedule->schoolYear()->getName();
$replace = array(
  $parent->getPreferredFirstName(),
  $student->getPreferredFirstName(),
  $schedule->schoolYear()->getName(),
  '<a href="' . $link . '">' . $link . '</a>'
);

$subject = str_replace(
  $find,
  $replace,
  $schedule->isAccepted()
    ? core_setting::get('scheduleUnlockEmailSubject', 'Schedules')
    : core_setting::get('scheduleChangeEmailSubject', 'Schedules')
);
$content = str_replace(
  $find,
  $replace,
  $schedule->isAccepted()
    ? core_setting::get('scheduleUnlockEmail', 'Schedules')
    : core_setting::get('scheduleChangeEmail', 'Schedules')
);

//core_loader::includeCKEditor();

core_loader::isPopUp();
core_loader::printHeader();
?>
<script>
  $(function() {
    $('#mth_schedule-change-form').submit(function() {
      if ($('input.updates-required-cb:checked').length === 0) {
        swal('', 'You need to select at least one period or other item to change.', 'warning');
        return false;
      }

      return true;
    });
  });
</script>
<form action="?form=<?= uniqid('mth_schedule-change-form-') ?>&schedule=<?= $schedule->id() ?>" method="post" id="mth_schedule-change-form">
  <fieldset>
    <legend>Require changes
      <small>to the following periods:</small>
    </legend>
    <?php while ($schedulPeriod = $schedule->eachPeriod()) : ?>
      <?php
      if ($schedulPeriod->period_number() == 1 || $schedulPeriod->hasDefault()) {
        continue;
      }
      ?>
      <div class="checkbox-custom checkbox-primary">
        <input type="checkbox" name="mth_schedule_period[<?= $schedulPeriod->id() ?>]" value="<?= $schedulPeriod->id() ?>" data-label="<?= $schedulPeriod->period() ?> 
                           <?= $schedulPeriod->second_semester() ? '(2nd Semester)' : '' ?>" id="mth_schedule_period-<?= $schedulPeriod->id() ?>" class="mth_schedule_periods-to_change updates-required-cb" <?= $schedule->isPendingUnlock() && $schedulPeriod->require_change() ? 'CHECKED' : '' ?>>

        <label for="mth_schedule_period-<?= $schedulPeriod->id() ?>">
          <?= $schedulPeriod->period() ?>
          <?= $schedulPeriod->second_semester() ? '(2nd Semester)' : '' ?>
          <br>
          <small style="display: inline"><?= $schedulPeriod ?></small>
          <?php if (!$student->isMidYear($schedule->schoolYear()) && $schedulPeriod->second_sem_change_available() && !$schedulPeriod->second_semester()
            && (!($secondSemPeriod = mth_schedule_period::get($schedule, $schedulPeriod->period(), true))
              || !$secondSemPeriod->second_semester())
          ) : ?>
            <br><a href="?schedule=<?= $schedule->id() ?>&secondSem=<?= $schedulPeriod->period()->num() ?>">Enable
              2nd Sem. Change</a>
          <?php endif; ?>
        </label>
      </div>

    <?php endwhile; ?>
    <div class="checkbox-custom checkbox-primary">
      <input type="checkbox" class="special updates-required-cb" name="special[]" value="cfa" data-label="Please complete the College Enrollment Assessment and then resubmit your schedule.
       <br><a href='https://docs.google.com/forms/d/e/1FAIpQLSdjcjmosE4z4O1mwTCkDwQAaxEhuLuyviZdjjNK7waBcd0PhA/viewform?usp=sf_link' target='_blank'>College for America</a>
       <br><a href='https://docs.google.com/forms/d/e/1FAIpQLSdYwIs0WsJ8VKta_ihxuJpsZzf-A7-RjO-JsVHwB6uqv6BqeQ/viewform?usp=sf_link' target='_blank'>Snow College</a>" />
      <label>CfA or Snow College</label>
    </div>
  </fieldset>
  <fieldset class="form-group">
    <legend>Email Subject</legend>
    <input type="text" name="subject" class="form-control" value="<?= $subject ?>">
  </fieldset>
  <textarea name="content" id="emailContent"><?= $content ?></textarea>
  <!--suppress JSAnnotator -->

  <br>
  <p>
    <button type="submit" class="btn btn-round btn-primary">Send</button>
    <button type="button" class="btn btn-round btn-secondary" onclick="top.global_popup_iframe_close('mth_schedule-change')">Cancel</button>
  </p>
</form>
<?php
core_loader::printFooter();
?>
<script src="//cdn.ckeditor.com/4.10.0/basic/ckeditor.js"></script>
<script>
  CKEDITOR.config.allowedContent = true;
  CKEDITOR.config.removePlugins = "image,forms,youtube,iframe,print,stylescombo,table,tabletools,undo,specialchar,removeformat,pastefromword,pastetext,smiley,font,clipboard,selectall,format,blockquote,resize,elementspath,find,maximize,showblocks,sourcearea,scayt,colorbutton,about,wsc,justify,bidi,horizontalrule";
  CKEDITOR.config.removeButtons = "Subscript,Superscript,Anchor";

  var selectedPeriods = [];
  $(function() {
    // $('#emailContent').ckeditor();
    var emailContent = CKEDITOR.replace('emailContent');

    emailContent.setData(emailContent.getData().replace(/(<p>)?\s*\[PERIOD_LIST\]\s*(<\/p>)?/, '<div><!--LIST_START--><!--LIST_END--></div>'));

    function getItem(isAccepted, itemtext) {
      if (isAccepted) {
        return "<ul><li>" + itemtext + "</li></ul>";
      }
      return "<strong>" + itemtext + "</strong>:<ul><li></li></ul>";
    }

    function updateList() {
      $('.mth_schedule_periods-to_change:checked').each(function() {
        if (selectedPeriods.indexOf(this.value) == -1) {
          emailContent.setData(
            emailContent.getData().replace(
              /<!--LIST_START-->((\n|\r|.)*)<!--LIST_END-->/,
              "<!--LIST_START-->$1<div style=\"margin-left:20px;\">" +
              getItem(<?= $schedule->isAccepted() ? "true" : "false" ?>, $(this).data('label')) +
              "</div><!--LIST_END-->"
            )
          );
          selectedPeriods.push(this.value);
        }
      });

    }

    updateList();

    $('.mth_schedule_periods-to_change').change(function() {
      updateList();
    });

    $('.special').change(function() {
      $('.special').each(function() {
        emailContent.setData(
          emailContent.getData().replace(
            /<!--LIST_START-->((\n|\r|.)*)<!--LIST_END-->/,
            "<!--LIST_START-->$1<div style=\"margin-left:40px;\"><ul><li>" +
            $(this).data('label') +
            "</li></ul></div><!--LIST_END-->"
          )
        );
      });
    });
  });
</script>