<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
($schedulePeriod = mth_schedule_period::getByID($_GET['schedule_period'])) || die('Schedule period not found');

($period = $schedulePeriod->period()) || die('Invalid period');

($schedule = $schedulePeriod->schedule())
    || die('Unable to build the schedule at the moment.');

($schedulePeriod->editable()) || die('You cannot edit this period.');

if (req_get::bool('delete')) {
    if ($schedulePeriod->second_semester() && $schedulePeriod->delete()) {
        core_notify::addMessage('2nd Semester change removed');
    } else {
        core_notify::addError('Unable to delete period');
    }
    core_loader::reloadParent();
}

($sCount = mth_subject::getCount($period, $schedulePeriod->provisional_provider_id())) || die('No subjects found for this period');

unset($_SESSION['mth_schedule_period-form-previous'][$schedulePeriod->id()]);

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die();

    $schedulePeriod->subject_id($_POST['subject_id']);
    $provider_id = isset($_POST['mth_provider_id']) ? $_POST['mth_provider_id'] : 0;
    $provider_course_id = isset($_POST['provider_course_id']) ? $_POST['provider_course_id'] : 0;

    $schedulePeriod->course_id(@$_POST['course_id']);
    $schedulePeriod->course_type(TRUE, @$_POST['course_type']);
    $schedulePeriod->mth_provider_id($provider_id);
    $schedulePeriod->provider_course_id($provider_course_id);

    $schedulePeriod->tp_name(@$_POST['tp_name']);
    $schedulePeriod->tp_course(@$_POST['tp_course']);
    $schedulePeriod->tp_phone(@$_POST['tp_phone']);
    $schedulePeriod->tp_website(@implode(',', $_POST['tp_website']));
    $schedulePeriod->tp_desc(@$_POST['tp_desc']);
    $schedulePeriod->tp_district(@$_POST['tp_district']);

    $schedulePeriod->custom_desc(@$_POST['custom_desc']);
    $schedulePeriod->template_course_description(@$_POST['template_course_description']);
    $schedulePeriod->allow_above_max_grade_level(req_post::bool('allow_above_max_grade_level'));
    $schedulePeriod->allow_below_min_grade_level(req_post::bool('allow_below_min_grade_level'));

    $schedulePeriod->saveOtherPeriods = true;

    if (!$schedulePeriod->save()) {
        core_notify::addError('Unable to set this period. Please try again later.');
    } elseif ($schedulePeriod->require_change()) {
        core_notify::addError('You didn\'t make a change to this period.');
    } elseif (!$schedulePeriod->validate()) {
        core_notify::addError('You have some invalid or missing values!');
    } else {
        core_notify::addMessage('Period ' . $period->num() . ' set');
        if (core_user::isUserAdmins()) {
            core_loader::reloadParent();
        } else {
            $custom_built_entrep = $schedulePeriod->course_type(true) == mth_schedule_period::TYPE_CUSTOM && (strtolower($schedulePeriod->subject())) == 'entrepreneurship';
            exit('<!DOCTYPE html><html><script>
            parent.global_popup_iframe_close("mth_schedule_period-edit");
            if(parent.global_waiting){
                parent.global_waiting();
              }
                var old_parent_url =  parent.location.href;
                var new_url = old_parent_url.indexOf("?") > 0 ?old_parent_url.substring(0,old_parent_url.indexOf("?")):old_parent_url;
                parent.location.href=new_url+"?aperiod=' . $schedulePeriod->period_number() . ($custom_built_entrep ? '&custom_set=1' : '') . '";
            </script></html>');
        }
    }
    core_loader::redirect('?schedule_period=' . $schedulePeriod->id());
}

core_loader::includejQueryValidate();

cms_page::setPageContent(
    '<p>
        Instructions:
        <ol>
        <li>Select Course Title.</li>
        <li>If Other, select Course Type (My Tech High Direct, Custom-built, or 3rd Party Provider).</li>
        <li><a href="https://docs.google.com/spreadsheets/d/1w9iVx23loL535JvEYPurHKYYNVt7HPKcJdgBFnLR9gU/edit#gid=0" target="_blank">Review details for all My Tech High Direct Providers here.</a></li>
        <li>Select or provide exact course name or required details / description.</li>
        <li>Click "Set" to save each Period.</li>
        <li>When finished, click "Submit Schedule" for review. You will be notified if changes are needed.</li>
    </ol>
    </p>',
    'Tech and Entrepreneurship Course Instructions',
    cms_content::TYPE_HTML
);

cms_page::setPageContent(
    '<p>
        Instructions:
        <ol>
            <li>Select Course Title.</li>
            <li>Select Course Type (My Tech High Direct, Custom-built, or 3rd Party Provider).</li>
            <li><a href="https://docs.google.com/spreadsheets/d/1w9iVx23loL535JvEYPurHKYYNVt7HPKcJdgBFnLR9gU/edit#gid=0" target="_blank">Review details for all My Tech High Direct Providers here.</a></li>
            <li>Select or provide exact course name or required details / description.</li>
            <li>Click "Set" to save each Period.</li>
            <li>When finished, click "Submit Schedule" for review. You will be notified if changes are needed.</li>
        </ol>
    </p>',
    'Course Instructions',
    cms_content::TYPE_HTML
);

core_loader::isPopUp();
core_loader::printHeader();
?>
<script>
    function addSiteField() {
        var newSiteDev = document.createElement("dev");
        newSiteDev.className += "input-group mb-3 third-party-sites";

        var linkInput = document.createElement("input");
        linkInput.className += "form-control";
        linkInput.name = "tp_website[]";
        linkInput.type = "text";
        linkInput.placeholder = "Link";
        newSiteDev.appendChild(linkInput);

        var buttonDev = document.createElement("dev");
        buttonDev.className += "input-group-append";

        var buttonElem = document.createElement("button");
        buttonElem.className += "btn btn-success";
        buttonElem.type = "button";
        buttonElem.setAttribute('onclick', 'addSiteField()');

        var icon = document.createElement("i");
        icon.className += "fas fa-plus";

        buttonElem.appendChild(icon);

        buttonDev.appendChild(buttonElem);

        newSiteDev.appendChild(buttonDev);
        document.getElementsByClassName('third-party-sites')[0].parentElement.append(newSiteDev);
    }
    var schedulePeriod = {
      swalOptions: {
        title: '',
        text: 'I understand the courses and providers preceded by * are above or below my child\'s grade level.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-warning',
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        closeOnConfirm: true,
        closeOnCancel: true
      },
      allow_above_max_grade_level: function () {
        if ($('#allow_above_max_grade_level').is(':checked')) {
          swal(
            this.swalOptions,
            function (isConfirm) {
              if (isConfirm) {
                schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_above_max_grade_level=1&get_subject_options');
              } else {
                $('#allow_above_max_grade_level').prop('checked', false)
              }
            })
        } else {
          schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_above_max_grade_level=0&get_subject_options');
        }
      },
      allow_below_min_grade_level: function () {
        if ($('#allow_below_min_grade_level').is(':checked')) {
          swal(
            this.swalOptions,
            function (isConfirm) {
              if (isConfirm) {
                schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_below_min_grade_level=1&get_subject_options');
              } else {
                $('#allow_below_min_grade_level').prop('checked', false)
              }
            })
        } else {
          schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_below_min_grade_level=0&get_subject_options');
        }
      },
      swalOptions: {
        title: '',
        text: 'I understand the courses and providers preceded by * are above or below my child\'s grade level.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-warning',
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        closeOnConfirm: true,
        closeOnCancel: true
      },
      allow_above_max_grade_level: function () {
        if ($('#allow_above_max_grade_level').is(':checked')) {
          swal(
            this.swalOptions,
            function (isConfirm) {
              if (isConfirm) {
                schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_above_max_grade_level=1&get_subject_options');
              } else {
                $('#allow_above_max_grade_level').prop('checked', false)
              }
            })
        } else {
          schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_above_max_grade_level=0&get_subject_options');
        }
      },
      allow_below_min_grade_level: function () {
        if ($('#allow_below_min_grade_level').is(':checked')) {
          swal(
            this.swalOptions,
            function (isConfirm) {
              if (isConfirm) {
                schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_below_min_grade_level=1&get_subject_options');
              } else {
                $('#allow_below_min_grade_level').prop('checked', false)
              }
            })
        } else {
          schedulePeriod.update('mth_schedule-mth_subject-options', 'allow_below_min_grade_level=0&get_subject_options');
        }
      },
        get_subject_options: function() {
          schedulePeriod.update('mth_schedule-mth_subject-options', 'get_subject_options');
        },
        get_course_options: function() {
          schedulePeriod.update('mth_schedule-mth_course-options', 'get_course_options');
        },
        get_course_type_options: function() {
          schedulePeriod.update('mth_schedule-course_type-options', 'get_course_type_options');
        },
        get_provider_options: function() {
          $('#mth_provider_id').blur()
          const selected = $('#mth_provider_id').find('option:selected')
          const requiresMultiplePeriods = selected.data('ismultiple')
          const periods = selected.data('periods')
          if (requiresMultiplePeriods !== undefined && periods.length > 0) {
            const periodsString = periods.join(', ')
            swal({
                title: '',
                text: `This provider is required for Periods ${periodsString}`,
                type: 'warning',
                showCancelButton: true,
                confirmButtonClass: 'btn-warning',
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel',
                closeOnConfirm: true,
                closeOnCancel: true
              },
              function(isConfirm) {
                if (isConfirm) {
                  schedulePeriod.update('mth_schedule-provider-options', 'get_provider_options');
                } else {
                  $('#mth_provider_id').val(window.previousProvider)
                }
              })
          } else {
            schedulePeriod.update('mth_schedule-provider-options', 'get_provider_options');
          }
        },
        get_tp_fields: function() {
          schedulePeriod.update('mth_schedule-course_type-options', 'get_tp_fields');
        },
        get_custom_desc_fields: function() {
          schedulePeriod.update('mth_schedule-custom_desc-fields', 'get_custom_desc_fields');
        },
      storePreviousProvider: function() {
        window.previousProvider = $('#mth_provider_id').find('option:selected')[0].value;
      },
        update: function(containerID, get) {
            $('#schedulePeriodLoading').show();
            $.ajax({
                url: '/student/student-<?= $student->getID() ?>/schedule/ajax',
                context: document.getElementById(containerID),
                data: $('#schedulePeriodForm').serialize() + '&' + get + '=1&schedule_period_fields=<?= $schedulePeriod->id() ?>',
                type: 'POST',
                success: function(data) {
                    this.innerHTML = data;
                    $('#schedulePeriodLoading').hide();
                }
            });
        }
    };
</script>

<form action="?form=<?= uniqid('schedulePeriod_') ?>&schedule_period=<?= $schedulePeriod->id() ?>" method="post" id="schedulePeriodForm">

    <?php if (!empty($_GET['admin'])) : ?>
        <div>
            <?= $student ?>
            <?= $schedule->schoolYear() ?>
        </div>
    <?php endif; ?>
    <img src="/_/includes/img/loading.gif" alt="Loading..." id="schedulePeriodLoading" style="display: none; position: fixed; top: 5px; right: 5px;">
    <h3>Period <?= $period->num() ?>
        <small><?= $schedulePeriod->second_semester() ? '(2nd Semester)' : '' ?></small>
    </h3>

    <?php if ($sCount > 1 || ($sCount == 1 && !$period->required())) : ?>
        <div class="form-group">
            <select id="subject_id" name="subject_id" class="form-control" required onchange="schedulePeriod.get_subject_options()">
                <option value="">Select</option>
                <?php while ($subject = mth_subject::getEach($period, false, $schedulePeriod->provisional_provider_id())) :
                    if (!$subject->available()) continue; ?>
                    <option value="<?= $subject->getID() ?>" <?= $schedulePeriod->subject() == $subject ? 'selected' : '' ?>><?= $subject->getName() ?></option>
                <?php endwhile; ?>
                <?php if (!$period->required()) : ?>
                    <option value="NONE" <?= !$schedulePeriod->subject() ? 'selected' : '' ?>>(None)</option>
                <?php endif; ?>
            </select>
        </div>
    <?php elseif (($subject = mth_subject::getEach($period))) : ?>
        <h3><?= $subject->getName() ?></h3>
        <div class="form-group">
            <input name="subject_id" id="subject_id" type="hidden" value="<?= $subject->getID() ?>" class="form-control">
        </div>
    <?php endif; ?>

    <div id="mth_schedule-mth_subject-options" class="row"></div>
    <div class="iframe-actions" style="padding-right:20px">
        <button type="submit" class="btn btn-round btn-primary btn-lg">Set</button>
        <button type="button" onclick="closePeriod()" class="btn btn-round btn-secondary btn-lg">Cancel</button>
    </div>
</form>
<script></script>
<script type="text/javascript">

    function closePeriod() {
        <?php if (core_user::isUserAdmins()) : ?>
            parent.location.reload(true);
        <?php else : ?>
            var old_parent_url = parent.location.href;
            var new_url = old_parent_url.indexOf("?") > 0 ?
                old_parent_url.substring(0, old_parent_url.indexOf("?")) :
                old_parent_url;
            parent.location.href = new_url;
        <?php endif; ?>
    }
    $(function() {
        $('#schedulePeriodForm').validate({
            rules: {
                tp_desc: {
                    maxlength: 250
                },
                custom_desc: {
                    maxlength: 500
                }
            }
        });
        schedulePeriod.get_subject_options();

        $('#schedulePeriodForm').on('change', '#provider_course_id', function() {
            if ($(this).data('popup')) {
                var content = $(this).data('popupcontent');
                swal('', content, 'info');
            }
        });
    });
</script>
<?php
core_loader::printFooter();
