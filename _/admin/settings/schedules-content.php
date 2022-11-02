<?php

if (req_get::bool('set_settings')) {
  $name = req_post::txt('name');
  $value = req_post::txt('value');
  $type = req_post::txt('type');
  $category = req_post::txt('category');
  $skipNotification = req_post::bool('skipNotification');
  $result = false;
  if (core_setting::set($name, $value, $type, $category)) {
    $result = true;
  }
  $response = json_encode(['response'=>$result, 'skipNotification'=>$skipNotification]);
  echo $response;
  exit;
}

cms_page::setPageTitle('Schedule Settings');
core_loader::printHeader('admin');

$settings = core_setting::get('allow_none', 'schedule_period');

?>
<div class="nav-tabs-horizontal nav-tabs-inverse">
  <?php
  $current_header = 'schedules';
  include core_config::getSitePath() . "/_/admin/settings/header.php";
  ?>
  <div class="tab-content p-20 higlight-links">
    <div class="row">
      <div class="col-md-12">
        <h4>Schedule Settings</h4>
        <div class="checkbox-custom checkbox-primary">
          <input id="schedule_period_settings" class="advance_settings" data-category="schedule_period" data-type="Bool" type="checkbox" name="allow_none" <?= $settings->getValue() ? 'CHECKED' : '' ?>>
          <label>Enable option "None"</label>
        </div>
        <div class="col-md-6 period-settings">
          <?php while ($period = mth_period::each()) :
            $periodSettingName = 'allow_none_period_' . $period->num();
            if (($period->num() != 1) && ($setting = core_setting::get($periodSettingName, 'schedule_period'))) : ?>
              <?=$setting->getTypeHmtl(); ?>
            <?php endif; ?>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="checkbox-custom checkbox-primary">
          <input id="diploma_seeking_question_settings" class="advance_settings" data-category="Diploma_seeking_question" data-type="Bool" type="checkbox" name="AllowDiplomaSeekingQuestion" <?= core_setting::get('AllowDiplomaSeekingQuestion', 'Diploma_seeking_question')->getValue() ? 'CHECKED' : '' ?>>
          <label>Enable Diploma Seeking Question</label>
        </div>
        <div class="col-md-6 diploma-seeking-question-settings">
          <div class="radio-custom radio-primary">
              <input type="radio" class="diploma_seeking_question" value="1" data-category="Diploma_seeking_question" data-type="Bool" name="DiplomaSeekingQuestionDefault" <?= core_setting::get('DiplomaSeekingQuestionDefault', 'Diploma_seeking_question')->getValue() ? 'checked' : '' ?>>
              <label>Set Default to Yes</label>
          </div>
          <div class="radio-custom radio-primary">
              <input type="radio" class="diploma_seeking_question" value="0" data-category="Diploma_seeking_question" data-type="Bool" name="DiplomaSeekingQuestionDefault" <?= !core_setting::get('DiplomaSeekingQuestionDefault', 'Diploma_seeking_question')->getValue() ? 'checked' : '' ?>>
              <label>Set Default to No</label>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
core_loader::printFooter('admin');
?>
<script>
  function toggleElementVisibility() {
    if (!$('#schedule_period_settings').is(':checked')) {
      $('.period-settings').hide();
    } else {
      $('.period-settings').show();
    }
  }

  function diplomaSeekingDisplay() {
    if ($('#diploma_seeking_question_settings').is(':checked')) {
        $('.diploma-seeking-question-settings').show();
      } else {
        $('.diploma-seeking-question-settings').hide();
      }
  }

  $(function() {
    diplomaSeekingDisplay();
    $('#schedule_period_settings').change(function(){
      if (!$('#schedule_period_settings').is(':checked')) {
        $('.period-settings .advance_settings').prop('checked',false).addClass('skipNotification').change();
      } else {
          $('.period-settings .advance_settings').prop('checked',false).removeClass('skipNotification')
      }
    });

    $('#diploma_seeking_question_settings').change(function() {
      diplomaSeekingDisplay();
    });
    
    $('.diploma_seeking_question').change(function() {
      var name = $(this).attr('name');
      var data = $(this).data();
      $.ajax({
        url: '?set_settings=1',
        type: 'POST',
        data: {
          name: name,
          value: $(this).val(),
          type: data.type,
          category: data.category
        },
        success: function(response) {
            response = JSON.parse(response)
          if (response['response']) {
            if(!response['skipNotification']) {
                toastr.success('Saved');
            }
          } else {
            toastr.error('Unable to save changes.');
          }
        },
        error: function() {
          toastr.error('Unable to save changes.');
        }
      });
    });

    $('.advance_settings').change(function() {
      toggleElementVisibility();
      var ischeck = $(this).is(':checked') ? 1 : 0;
      var name = $(this).attr('name');
      var data = $(this).data();
      var skipNotification = $(this).closest(".skipNotification").length;
      $.ajax({
        url: '?set_settings=1',
        type: 'POST',
        data: {
          name: name,
          value: ischeck,
          type: data.type,
          category: data.category,
          skipNotification: skipNotification
        },
        success: function(response) {
            response = JSON.parse(response)
          if (response['response']) {
            if(!response['skipNotification']) {
                toastr.success('Saved');
            }
          } else {
            toastr.error('Unable to save changes.');
          }
        },
        error: function() {
          toastr.error('Unable to save changes.');
        }
      });
    });

    toggleElementVisibility();
  });
</script>