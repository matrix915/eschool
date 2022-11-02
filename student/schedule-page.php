<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $pathArr array */

if (!empty($pathArr[3]) && in_array($pathArr[3], array('ajax', 'period', '2nd-sem-change', 'change', 'unlock'))) {
  include core_config::getSitePath() . '/student/schedule/' . $pathArr[3] . '.php';
  exit();
}

$schedule = false;
if (
  !empty($pathArr[3])
  && ($year = mth_schoolYear::getByStartYear($pathArr[3]))
  && !($schedule = mth_schedule::get($student, $year))
) {
  if (($year = mth_schoolYear::getOpenReg()) && $student->canSubmitSchedule()) {
    $schedule = mth_schedule::create($student, $year);
  } else {
    core_notify::addError('No schedule found for ' . $year);
    core_loader::redirect('/student/' . $student->getSlug());
  }
}

if (req_get::bool('pdf')) {

  if ($schedule) {
    header('Content-type: application/pdf');
    echo mth_views_schedules::getPDFcontent($schedule);
  } else {
    core_notify::addError('No schedule found.');
    core_loader::redirect('/student/' . $student->getSlug());
  }

  exit();
}

if (req_get::bool('enable2ndSem') && $schedule) {
  if ($schedule->enable2ndSemChanges(false)) {
    core_notify::addMessage('Schedule set to allow 2nd semester changes');
  } else {
    core_notify::addError('We were unable to enable 2nd semester updates. Please try again later or contact us.');
  }
  core_loader::redirect();
}

if (!empty($_GET['submit']) && $schedule) {
    if ($schedule->submit()) {
        core_notify::addMessage($student->getPreferredFirstName() . '\'s ' . $schedule->schoolYear() . ' Schedule has been submitted');
    } else {
        $invalidPeriods = $schedule->invalidPeriods();
        core_notify::addError('Unable to submit the Schedule.  Please make sure all Periods are Set.');
        core_notify::addError('Incomplete Period(s): ' . implode(', ', $invalidPeriods));
    }
    header('Location: ' . core_path::getPath());
    exit();
}

if (!empty($_GET['resetProvisionalProvider']) && $schedule) {
    foreach ($schedule->allPeriods() as $period) {
        if ($period->provisional_provider_id()) {
            $period->resetProvisionalProvider();
        }
    }

  header('Location: ' . core_path::getPath());
  exit();
}

if (!empty($_GET['resubmit']) && $schedule) {
  if ($schedule->resubmit()) {
    core_notify::addMessage($student->getPreferredFirstName() . '\'s ' . $schedule->schoolYear() . ' Schedule has been resubmitted');
  } else {
    $invalidPeriods = $schedule->invalidPeriods();
    core_notify::addError('Unable to resubmit the Schedule. Please make sure all Periods are set or updated.');
    core_notify::addError('Incomplete Period(s): ' . implode(', ', $invalidPeriods));
  }
  header('Location: ' . core_path::getPath());
  exit();
}

if(!empty($_GET['diplomaSeeking'])) {
  $student->diplomaSeeking($_GET['diploma_seeking']);
  mth_schedule_period::resetPeriodForDiplomaSeeking($schedule->id(), $_GET['diploma_seeking']);
  $schedule->setStatus(mth_schedule::STATUS_STARTED);
  $schedule->save();
  core_loader::redirect();
  exit();
}

$diploma_seeking_value = true;
if ( $student->diplomaSeeking() != NULL ) {
  if($student->diplomaSeeking()) {
    $diploma_seeking_value = true;
  } else {
    $diploma_seeking_value = false;
  }
} else {
 if ( core_setting::get('DiplomaSeekingQuestionDefault', 'Diploma_seeking_question')->getValue() ) {
  $diploma_seeking_value = true;
 } else {
  $diploma_seeking_value = false;
 }
}

$should_answer_diploma_question = false;
if ( core_setting::get('AllowDiplomaSeekingQuestion', 'Diploma_seeking_question')->getValue()
  && ($student->getGradeLevelValue() >= 9 && $student->getGradeLevelValue() <= 12)
  && $student->isNewFromDiplomaSeeking($year) 
  && $schedule->status(true) <= mth_schedule::STATUS_SUBMITTED
  && $student->diplomaSeeking() == NULL ) { 
    $should_answer_diploma_question = true;
}

if ( !empty($_GET['updateDiplomaSeeking'])
    && core_setting::get('AllowDiplomaSeekingQuestion', 'Diploma_seeking_question')->getValue()
    && ($student->getGradeLevelValue() >= 9 && $student->getGradeLevelValue() <= 12)
    && $student->isNewFromDiplomaSeeking($year) 
    && $schedule->status(true) <= mth_schedule::STATUS_SUBMITTED) {
      $should_answer_diploma_question = true;
} 

cms_page::setPageTitle('Schedule');
cms_page::setPageContent('');
core_loader::printHeader('student');
?>
<div class="page">
  <?= core_loader::printBreadCrumb('window'); ?>
  <div class="page-content container-fluid">
    <?php if( $should_answer_diploma_question ):?>
      <div class="card diploma-seeking-card">
        <div class="card-block">
          <div class="form-group">
            <form method="post" id="diploma-seeking-form">
              <label for="diploma_seeking">
                <b> Does this student plan to complete the requirements to earn a Utah high school diploma (schedule flexibility is limited)?</b>
              </label>
              <div class="radio-custom radio-primary">
                <input type="radio" id="diploma_seeking-yes" value="1" name="diploma_seeking" class="diploma_seeking-cb" <?= $diploma_seeking_value ? "checked" : "" ?>>
                <label for="diploma_seeking-yes">Yes</label>
              </div>
              <div class="radio-custom radio-primary">
                <input type="radio" id="diploma_seeking-no" value="0" name="diploma_seeking" class="diploma_seeking-cb" <?= !$diploma_seeking_value ? "checked" : "" ?>>
                <label for="diploma_seeking-no">
                  No
                </label>
              </div>
              <?php if ( $student->diplomaSeeking() != NULL ): ?>
              <button type="button" class="btn btn-default btn-round btn-lg" onclick="location.href=window.location.href.split('?')[0]">
                Cancel
              </button>
              <?php endif; ?>
              <button type="button" class="btn btn-success btn-round btn-lg" onclick="submitDiplomaSeeking(<?= $student->diplomaSeeking() ?>)">
                Submit
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php else:?>
      <div class="card">
        <?= cms_page::getDefaultPageMainContent() ?>
        <div class="card-header">
          <?php if (($eachSchedule = mth_schedule::eachOfStudent($student->getID()))) : ?>

            <div class="float-right" id="schedule-select">
              View Schedule for:
              <select onchange="location.href=this.value" class="form-control">
                <?php do { ?>
                  <option <?= $schedule && $schedule->id() == $eachSchedule->id() ? 'selected' : '' ?> value="/student/<?= $student->getSlug() ?>/schedule/<?= $eachSchedule->schoolYear() ?>">
                    <?= $eachSchedule->schoolYear() ?>
                  </option>
                <?php } while ($eachSchedule = mth_schedule::eachOfStudent($student->getID())); ?>
              </select>
            </div>
          <?php endif; ?>
          <h3 class="card-title"><?= $schedule ? $schedule->schoolYear() : '' ?> Schedule</h3>
          <p class="card-subtitle">
            <?= $student ?>
          </p>
          <button type="button" class="btn btn-primary btn-round" onclick="window.open('?pdf=1')">View PDF</button>
        </div>
        <div class="card-block">
          <?php if ($schedule && $student->getSOEname($schedule->schoolYear())) : ?>
            <h4 style="margin-bottom: 0">School of Enrollment:</h4>
            <p><?= $student->getSOEnameAndAddress($schedule->schoolYear()) ?></p>
          <?php endif; ?>
          <?php if ($student->getGradeLevelValue() > 8) : ?>
            <p>
              <label>Diploma Seeking:</label> 
              <?= core_setting::get('AllowDiplomaSeekingQuestion', 'Diploma_seeking_question')->getValue()
                  && ($student->getGradeLevelValue() >= 9 && $student->getGradeLevelValue() <= 12)
                  && $student->isNewFromDiplomaSeeking($year) 
                  && $schedule->status(true) <= mth_schedule::STATUS_SUBMITTED ? '<a href="?updateDiplomaSeeking=1">'. ($student->diplomaSeeking() ? 'Yes' : 'No').'</a>' : ($student->diplomaSeeking() ? 'Yes' : 'No')?>
            </p>
          <?php endif; ?>
        </div>
        <?php if (!$schedule && $year && !$student->isPendingOrActive($year)) : ?>
          <div class="card-block">
            <p>You cannot build a schedule for <?= $student->getPreferredFirstName() ?>.</p>
          </div>
        <?php elseif (!$schedule && !$year) : ?>
          <div class="card-block">
            <p>Registration is not open.</p>
            <?php if (($nextYear = mth_schoolYear::getNext())) : ?>
              <p>Please check back <?= $nextYear->getDateRegOpen('F j, Y'); ?></p>
            <?php endif; ?>
          </div>
        <?php elseif (!$schedule->isSubmited() || $schedule->isToChange()) : ?>
          <div class="card-block">
            <?php mth_views_schedules::scheduleDetails($schedule) ?>
          </div>
          <div class="card-footer">
            <?php if ($schedule->isToChange()) : ?>
              <button type="button" class="btn btn-success btn-round btn-lg" onclick="submitConfirm(function(){location.href='?resubmit=true';})">
                Resubmit Schedule
              </button>
            <?php endif; ?>
            <?php if (!$schedule->isPending()) : ?>
              <button type="button" class="btn btn-success btn-round btn-lg" onclick="submitConfirm(function(){location.href='?submit=true';})">
                Submit Schedule
              </button>
            <?php endif; ?>
            <?php
              $hasProvisionalProvider = false;
              $multiplePeriods = [];
              foreach ($schedule->allPeriods() as $period) {
                  if ($period->provisional_provider_id()) {
                      $hasProvisionalProvider = true;
                      $provider = mth_provider::get($period->provisional_provider_id());
                      $multiplePeriods = $provider->multiplePeriods();
                  }
              }
              if ($hasProvisionalProvider) : ?>
                <button type="button" id="reset_button" class="btn btn-warning btn-round btn-lg"
                        data-periods=<?= json_encode($multiplePeriods) ?>
                >
                    Reset Course Options
                </button>
            <?php endif; ?>
          </div>

        <?php elseif ($schedule && $schedule->isSubmited()) : ?>
          <div class="card-block">
            <?php if ($schedule->isPending()) : ?>
              <div class="alert  alert-alt alert-success">
                <?= $student->getPreferredFirstName() ?>'s Schedule has been submitted.
              </div>
            <?php endif; ?>

          <?php mth_views_schedules::scheduleDetails($schedule); ?>
        </div>
        <?php if ($schedule->isAccepted()) : ?>
          <!-- <div class="card-footer">
                            <a class="btn btn-pink btn-round btn-lg" href="https://docs.google.com/forms/d/1gdrgPEPSCXSMwST09lnudaP6KiiV79mtIkKFAeziMJE/viewform" target="_blank">
                                Request a Change
                            </a>

                        </div> -->
        <?php endif; ?>
        <div class="card-footer">
          <?php if ($schedule && $schedule->isNewSubmission()) : ?>
            <button type="button" class="btn btn-pink btn-round btn-lg" onclick="global_popup_iframe('mth_student_schedule-change','/student/<?= $student->getSlug() ?>/schedule/unlock?schedule=<?= $schedule->id() ?>')">
              Request Schedule Change
            </button>
            <?php ?>
          <?php elseif ($student->isActive() && $schedule->isAcceptedOnly()) : ?>
            <button type="button" class="btn btn-round btn-pink btn-lg" onclick="global_popup_iframe('mth_student_schedule-change','/student/<?= $student->getSlug() ?>/schedule/change?schedule=<?= $schedule->id() ?>')">
              Request Schedule Change
            </button>
          <?php elseif ($schedule->isResubmitted() || $schedule->isUpdatesRequired()) : ?>
            <a class="btn btn-secondary btn-lg  btn-round disabled" style="color:#fff">
              Request Schedule Change
            </a>
            <span>(You currently have a pending schedule change or required update.) </span>
          <?php endif; ?>
        </div>
        <?php else : ?>
          <div class="card-block">
            <div class="alert  alert-alt alert-danger">
              There was an error accessing <?= $student->getPreferredFirstName() ?>'s Schedule. Please contact us for
              support.
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
core_loader::printFooter('student');
?>
<script>
  $(function() {
    var is_submitted = <?= $schedule && $schedule->isSubmited() ? 'true' : 'false'; ?>;
    var is_started = <?= $schedule && $schedule->alterable() ? 'true' : 'false' ?>;
    var is_custom_set = <?= req_get::bool('custom_set') ? 'true' : 'false' ?>;
    var is_active_period = <?= req_get::bool('aperiod') ? req_get::int('aperiod') : 0 ?>;
    <?php $period7setting = core_setting::get('allow_none_period_7', 'schedule_period'); ?>
    var notRequired = <?= !empty($period7setting) ? $period7setting->getValue() : 'true' ?>;
    if (notRequired && is_active_period == 7 && $('.editable-row.period-7.period-has-course').length > 0 && is_started) {
      //UT ONLY
      swal('', 'Remember that a class in optional Period 7 will affect your student\'s Technology Allowance. Please refer to the Commonly Asked Questions in Parent Link\'s Section 18. Technology Allowance for specifics.', '');
    }

    $('#reset_button').click(function () {
      const periods = $(this).data('periods').join(', ')
      swal({
        title: 'Note',
        text: `You are about to reset Periods ${periods} and will need to make a new selection.`,
        type: 'info',
        showCancelButton: !0,
        confirmButtonClass: 'btn-primary',
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel',
        closeOnConfirm: !1,
        closeOnCancel: true
      }, function(){location.href='?resetProvisionalProvider=true';
        });
    })

    if (is_active_period && $('.editable-row.period-' + is_active_period + '.period-has-allowance').length > 0 && is_started) {
      var allowance = $('.period-has-allowance').data('allowance');
      swal('', 'NOTE:  The tech class you have selected will reduce your student\'s Technology Allowance.  See Parent Link for additional details, if needed.', '');
    }

    if (is_custom_set) {
      swal({
          title: "",
          text: "You have selected a Custom-built Entrepreneurship course. Please note that the focus must be on learning to start and operate a business (not on learning a specific skill that might one day become a business).\n\nBe sure to have the student provide an on-going business update in his/her Weekly Learning Log. We love to see their entrepreneurial progress!",
          type: "",
          showCancelButton: false,
          confirmButtonClass: "btn-primary",
          confirmButtonText: "OK",
          closeOnConfirm: true
        },
        function() {
          window.history.pushState({}, document.title, window.location.href.split("?")[0]);
        });
    }

  });
  function submitDiplomaSeeking(old_value = null) {
    var message = "No changes made.";
    var new_value = $("input[name=diploma_seeking]:checked").val();
    if ( old_value != null ) {
      if(new_value != old_value) {
        if(new_value == 1) {
          message = "You are switching from a non-Diploma seeking path to a Diploma seeking path. Schedule flexibility is limited and courses only available for non-diploma seeking students will be removed from your schedule.";
        } else {
          message = "You are switching from Diploma seeking to a non-Diploma seeking path. Those courses only available to Diploma-seeking students will be removed from your schedule.";
        }
      }
      swal({
        title: "Note",
        text: message,
        type: "info",
        showCancelButton: !0,
        confirmButtonClass: "btn-primary",
        confirmButtonText: "Continue",
        cancelButtonText: "Cancel",
        closeOnConfirm: !1,
        closeOnCancel: true
      }, function(result) {
        if (result) {
          if(new_value != old_value) {
            location.href='?diplomaSeeking=1&diploma_seeking='+new_value;
          } else {
            location.href=window.location.href.split('?')[0];
          }
        }
      });
    }else {
      location.href='?diplomaSeeking=1&diploma_seeking='+new_value;
    }
  }

  function submitConfirm(_callback) {
    swal({
      title: "Note",
      text: "You are about to submit your child’s personalized schedule for approval.  Please be sure the schedule is accurate and reflects the educational plan for the upcoming year.  Contact My Tech High to request changes, if necessary.",
      type: "info",
      showCancelButton: !0,
      confirmButtonClass: "btn-primary",
      confirmButtonText: "Submit",
      cancelButtonText: "Cancel",
      closeOnConfirm: !1,
      closeOnCancel: true
    }, _callback);
  }

</script>