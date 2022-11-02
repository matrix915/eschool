<?php

$schoolYear = &$_SESSION['mth_reports_school_year'];

if (req_get::bool('setSchoolYearID')) {
  $schoolYear = mth_schoolYear::getByID(req_get::int('setSchoolYearID'));
  exit($schoolYear ? $schoolYear->getID() : '0');
}

if (!$schoolYear) {
  $schoolYear = mth_schoolYear::getCurrent();
}

cms_page::setPageTitle('Reports');
cms_page::setPageContent('');
core_loader::printHeader('admin');
$person = core_user::getCurrentUser();
?>
<style>
  #reportPopup,
  #enrolledPopup {
    width: 90%;
    height: 90%;
  }

  .new a {
    font-weight: bold;
    color: red;
  }
</style>
<script>
  function showReport(path) {
    global_popup_iframe('reportPopup', '<?= core_path::getPath() ?>' + path);
  }
</script>
<p>
  School Year:
  <select onchange="global_waiting();
      $.get('?setSchoolYearID=' + this.value, function () {
        global_waiting_hide();
      })">
    <?php while ($eachYear = mth_schoolYear::each()) : ?>
      <option value="<?= $eachYear->getID() ?>" <?= $eachYear->getID() == $schoolYear->getID() ? 'selected' : '' ?>>
        <?= $eachYear ?></option>
    <?php endwhile; ?>
  </select>
</p>
<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-block"><a onclick="showReport('/stats-tables/all')" class="link">Statistics Report</a></div>
    </div>
    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Misc</h3>
      <a class="list-group-item" onclick="showReport('/app/pending')">Pending Applicants</a>
      <a class="list-group-item" onclick="showReport('/app/referred')">Applicants Referred By</a>
      <a class="list-group-item" onclick="showReport('/packet/previous-school')">Last School Attended</a>
      <!--    <a class="list-group-item" onclick="showReport('/students/eschools')">eSchool Students</a>-->
      <a class="list-group-item" onclick="global_popup('test_opt_out_modal')">
        Testing Opt-out report</a>
      <a class="list-group-item" onclick="showReport('/students/dibels');">
        DIBELS Reading Test report</a>
      <a class="list-group-item" data-toggle="modal" data-target="#byu_enrollments">
        BYU IS enrollments</a>
      <a class="list-group-item" data-toggle="modal" data-target="#eng_enrollments">
        Edgenuity enrollments</a>
      <a class="list-group-item" onclick="showReport('/students/new')">New Active Students</a>
      <a class="list-group-item" onclick="showReport('/students/new-inactive')">New Inactive Students</a>
      <a class="list-group-item" onclick="showReport('/students/new-parents')">New Parents</a>
      <a class="list-group-item" onclick="global_popup('lemi_enrollments')">
        LEMI Enrollments
      </a>
      <a class="list-group-item" onclick="showReport('/schedule/ascend-enrollments');">
        Ascend Enrollments
      </a>
      <a class="list-group-item" data-toggle="modal" data-target="#hr_account_request">
        HR Resources Account Request
      </a>
      <a class="list-group-item" onclick="showReport('/students/military');">
        Active Military
      </a>
      <a class="list-group-item" onclick="showReport('/packet/ethnicity ');">
        Ethnicity
      </a>
      <a class="list-group-item" onclick="showReport('/packet/income ');">
        Income
      </a>
      <!-- <a class="list-group-item" onclick="showReport('/students/mega');">
                    MEGA 
                </a> -->
      <a class="list-group-item" onclick="showReport('/students/completion');">
        Completion Certificates
      </a>
      <a class="list-group-item" onclick="showReport('/students/sped');">
        Special Ed
      </a>
      <a class="list-group-item" onclick="showReport('/students/notes');">
        Notes
      </a>
      <a class="list-group-item" onclick="global_popup('immunizations_report')">
        Immunizations report
      </a>
    </div>

    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Intent to Re-enroll</h3>
      <a class="list-group-item" onclick="showReport('/re-enroll/not-returning')">Not Returning</a>
      <a class="list-group-item" onclick="showReport('/re-enroll/students-not-submitted')">Not Submitted</a>
    </div>
  </div>
  <div class="col">
    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Courses</h3>
      <a class="list-group-item" onclick="showReport('/course/ids');">Course SIS IDs</a>
      <a class="list-group-item" onclick="showReport('/course/provider-students');">Users CSV</a>
      <a class="list-group-item" onclick="showReport('/course/tech-entrepreneur')">Enrollments CSV</a>
      <a class="list-group-item" onclick="showReport('/course/course-csv');">Course CSV</a>
      <a class="list-group-item" data-toggle="modal" data-target="#enrollment_counts">Enrollment Counts</a>
      <a class="list-group-item" data-toggle="modal" data-target="#canvas_count_totals">Canvas Course Totals</a>
      <a class="list-group-item" data-toggle="modal" data-target="#course_virtual">Virtual Makerspace Order Report</a>
      <a class="list-group-item" data-toggle="modal" data-target="#kit-orders">Kit Orders</a>
    </div>

    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Schedules</h3>
      <a class="list-group-item" onclick="global_popup('student_schedules')">
        Active Schedules</a>
      <a class="list-group-item" onclick="global_popup('student_schedules_mid')">
        Active Schedules (Mid-year)
      </a>
      <a class="list-group-item" onclick="showReport('/schedule/not-submitted');">
        Students without a Submitted Schedule</a>
      <a class="list-group-item" onclick="showReport('/schedule/updates-required');">
        Updates Required + Unlocked</a>
      <a class="list-group-item" onclick="showReport('/schedule/reimbursements');">
        Reimbursement Amounts on Schedule Records</a>
      <a class="list-group-item" onclick="showReport('/schedule/2nd-sem');">
        Schedules Unlocked for 2nd Semester Changes</a>
      <a class="list-group-item" onclick="showReport('/schedule/not-unlocked');">
        Students not Unlocked for 2nd Sem</a>
    </div>

    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Providers</h3>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_schedules">Schedules</a>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_schedules_username">Schedules and Username</a>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_enrollment_counts">Enrollment Counts</a>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_usernames">Username Generator</a>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_usernames_active">Username Generator (Active/Pending)</a>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_students">Student Details</a>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_detail_usernames">Student Details and Username</a>
      <a class="list-group-item" data-toggle="modal" data-target="#provider_by_student">Enrollments by Providers</a>
    </div>

    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">PLG</h3>
      <a class="list-group-item" onclick="showReport('/v2/plg/science');">Science</a>
      <a class="list-group-item" onclick="showReport('/v2/plg/english');">English</a>
      <a class="list-group-item" onclick="showReport('/v2/plg/math');">Math</a>
      <a class="list-group-item" onclick="showReport('/v2/plg/socialstudies');">Social Studies</a>
    </div>

  </div>
  <div class="col">
    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Students</h3>
      <a class="list-group-item" onclick="showReport('/students/80');">
        Grade 80% or less</a>
      <a class="list-group-item" onclick="showReport('/students/50');">
        Grade 50% or less</a>
      <a class="list-group-item" onclick="showReport('/students/withdrawn')">
        Withdrawn
      </a>
      <a class="list-group-item" onclick="showReport('/packet/no-packet')">
        Students without Packets
      </a>

      <a class="list-group-item" onclick="showReport('/students/7th-graders')">
        7th Graders</a>

      <a class="list-group-item" onclick="global_popup('student_information')">
        Student Information
      </a>

      <a class="list-group-item" onclick="showReport('/students/last-login')">
        Last Login
      </a>
    </div>

    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Reimbursements</h3>
      <a class="list-group-item" onclick="showReport('/reimbursements/product-sn')">Product Serial Numbers</a>
      <a class="list-group-item" onclick="showReport('/reimbursements/updates-required')">Updates Required</a>
      <a class="list-group-item" onclick="showReport('/reimbursements/approved-reimbursement')">Approved Reimbursement</a>
    </div>
    <div id="student_schedules_mid" style="display: none;width:50%;padding:10px;">
      <div class="row">
        <div class="col">
          <h4>Active Schedules (Mid-year)</h4>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <legend>Grade Level</legend>
          <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade_level => $label) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade<?= $grade_level ?>">
              <label for="grade<?= $grade_level ?>">
                <?= $label ?>
              </label>
            </div>
          <?php } ?>
        </div>
        <div class="col">
          <legend>School Of Enrollment</legend>
          <?php foreach (\mth\student\SchoolOfEnrollment::getActive() as $schoolOfEnrollment) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="soe[]" value="<?= $schoolOfEnrollment->getId() ?>">
              <label>
                <?= $schoolOfEnrollment->getLongName() ?>
              </label>
            </div>
          <?php } ?>
        </div>
      </div>
      <div style="margin-bottom: 30px;">
        <button type="button" class="btn-round btn btn-primary" onclick="showReport('/schedule/active-mid?' + $('#student_schedules_mid').find('input').serialize());">Get Student Information</button>
        <button type="button" class="btn-round btn btn-secondary" onclick="global_popup_close('student_schedules_mid')">Cancel/Close</button>

      </div>
    </div>
    <div id="student_schedules" style="display: none;width:50%;padding:10px;">
      <div class="row">
        <div class="col">
          <h4>Active Schedules</h4>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <legend>Grade Level</legend>
          <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade_level => $label) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade<?= $grade_level ?>">
              <label for="grade<?= $grade_level ?>">
                <?= $label ?>
              </label>
            </div>
          <?php } ?>
        </div>
        <div class="col">
          <legend>School Of Enrollment</legend>
          <?php foreach (\mth\student\SchoolOfEnrollment::getActive() as $schoolOfEnrollment) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="soe[]" value="<?= $schoolOfEnrollment->getId() ?>">
              <label>
                <?= $schoolOfEnrollment->getLongName() ?>
              </label>
            </div>
          <?php } ?>
        </div>
      </div>
      <div style="margin-bottom: 30px;">
        <button type="button" class="btn-round btn btn-primary" onclick="showReport('/schedule/active-export?' + $('#student_schedules').find('input').serialize());">Get Student Information</button>
        <button type="button" class="btn-round btn btn-secondary" onclick="global_popup_close('student_schedules')">Cancel/Close</button>

      </div>
    </div>
    <div id="test_opt_out_modal" style="display: none;width:50%;padding:10px;">
      <div class="row">
        <div class="col">
          <h4>Test Opt-out Report</h4>
        </div>
      </div>
      <div class="row" id="test_opt_out_form">
        <div class="col">
          <legend>Grade Level</legend>
          <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade_level => $label) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade<?= $grade_level ?>">
              <label for="grade<?= $grade_level ?>">
                <?= $label ?>
              </label>
            </div>
          <?php } ?>
        </div>
        <div class="col">
          <legend>School Of Enrollment</legend>
          <?php foreach (\mth\student\SchoolOfEnrollment::getActive() as $schoolOfEnrollment) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="soe[]" value="<?= $schoolOfEnrollment->getId() ?>">
              <label>
                <?= $schoolOfEnrollment->getLongName() ?>
              </label>
            </div>
          <?php } ?>
        </div>
      </div>
      <div style="margin-bottom: 30px;">
        <button type="button" class="btn-round btn btn-primary" onclick="showReport('/students/testing?' + $('#test_opt_out_form').find('input').serialize());">Get Student Information</button>
        <button type="button" class="btn-round btn btn-secondary" onclick="global_popup_close('test_opt_out_modal')">Cancel/Close</button>
      </div>
    </div>
    <div id="student_information" style="display: none;width:50%;padding:10px;">
      <div class="row">
        <div class="col">
          <h4>Student Information</h4>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <legend>Grade Level</legend>
          <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade_level => $label) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade<?= $grade_level ?>">
              <label for="grade<?= $grade_level ?>">
                <?= $label ?>
              </label>
            </div>
          <?php } ?>
        </div>
        <div class="col">
          <legend>District of Residence</legend>
          <?php foreach (mth_packet::getAvailableSchoolDistricts() as $district) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="district[]" value="<?= $district ?>">
              <label>
                <?= $district ?>
              </label>
            </div>
          <?php } ?>
        </div>
      </div>
      <div style="margin-bottom: 30px;">
        <button type="button" class="btn-round btn btn-primary" onclick="showReport('/students/info?' + $('#student_information').find('input').serialize());">Get Student Information</button>
        <button type="button" class="btn-round btn btn-secondary" onclick="global_popup_close('student_information')">Cancel/Close</button>

      </div>
    </div>

    <div id="lemi_enrollments" style="display: none;width:50%;padding:10px;">
      <div class="row">
        <div class="col">
          <h4>LEMI Enrollments</h4>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <legend>Grade Level</legend>
          <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade_level => $label) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="grade_level[]" value="<?= $grade_level ?>" id="grade<?= $grade_level ?>">
              <label for="grade<?= $grade_level ?>">
                <?= $label ?>
              </label>
            </div>
          <?php } ?>
        </div>
      </div>
      <div style="margin-bottom: 30px;">
        <button type="button" class="btn-round btn btn-primary" onclick="showReport('/schedule/lemi-enrollments?' + $('#lemi_enrollments').find('input').serialize());">Get Students</button>
        <button type="button" class="btn-round btn btn-secondary" onclick="global_popup_close('lemi_enrollments')">Cancel/Close</button>
      </div>
    </div>
    <div id="immunizations_report" style="display: none;width:50%;padding:10px;">
      <div class="row">
        <div class="col">
          <h4>Immunization Report</h4>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <legend>Grade Level</legend>
          <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade_level => $label) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade<?= $grade_level ?>">
              <label for="grade<?= $grade_level ?>">
                <?= $label ?>
              </label>
            </div>
          <?php } ?>
        </div>
        <div class="col">
          <legend>School Of Enrollment</legend>
          <?php foreach (\mth\student\SchoolOfEnrollment::getActive() as $schoolOfEnrollment) { ?>
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="soe[]" value="<?= $schoolOfEnrollment->getId() ?>">
              <label>
                <?= $schoolOfEnrollment->getLongName() ?>
              </label>
            </div>
          <?php } ?>
        </div>
      </div>
      <div style="margin-bottom: 30px;">
        <button type="button" class="btn-round btn btn-primary" onclick="showReport('/students/immunizations?' + $('#immunizations_report').find('input').serialize());">Get Student Information</button>
        <button type="button" class="btn-round btn btn-secondary" onclick="global_popup_close('immunizations_report')">Cancel/Close</button>
      </div>
    </div>

    <div class="list-group list-group-bordered">
      <h3 class="list-group-item active mt-0">Schools of Enrollment</h3>
      <a class="soe-link list-group-item" data-value="students">Students</a>
      <a class="list-group-item" onclick="showReport('/students/tooele')">
        Tooele Students (Forms)
      </a>
      <a class="list-group-item" onclick="showReport('/students/nebo')">
        ALC - Nebo Students (Forms)
      </a>
      <a class="list-group-item" onclick="showReport('/students/nyssa')">
        Nyssa Students (Forms)
      </a>
      <a class="list-group-item" onclick="showReport('/soe/tooele')">
        Tooele Students Master
      </a>
      <a class="list-group-item" onclick="showReport('/soe/nebo')">
        ALC - Nebo Students Master
      </a>
      <a class="list-group-item" onclick="showReport('/soe/gpa')">
        GPA Students Master
      </a>
      <a class="list-group-item" onclick="showReport('/soe/icsd-master')">
          ICSD/SEA Students Master
      </a>
      <a class="list-group-item" onclick="showReport('/soe/nyssa')">
        Nyssa Students Master
      </a>
      <a class="list-group-item" onclick="showReport('/soe/tooele-diploma')">
        Tooele Diploma-seeking
      </a>
      <a class="list-group-item" onclick="showReport('/soe/nebo-diploma')">
        ALC - Nebo Diploma-seeking
      </a>
      <a class="list-group-item" onclick="showReport('/soe/icsd-diploma')">
          ICSD/SEA Diploma-seeking
      </a>
      <a class="list-group-item" onclick="showReport('/soe/icsd')">
        ICSD/SEA Students (Forms)
      </a>
      <a class="list-group-item" onclick="showReport('/students/nebo-enrollment')">
        ALC - Nebo Enrollment Report
      </a>
      <a class="list-group-item" onclick="showReport('/soe/capitol-hill')">
        Capitol Hill Report
      </a>
      <a class="list-group-item" onclick="showReport('/soe/tooele-skyward')">
        Tooele Students - Skyward
      </a>
    </div>
  </div>
</div>


<!-- MODALS -->
<div id="soe_student_select" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="soe_student_select" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Select Schools Of Enrollment</h3>
      </div>
      <div class="modal-body">
        <?php foreach (\mth\student\SchoolOfEnrollment::getActive() as $schoolOfEnrollment) { ?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="soe[]" value="<?= $schoolOfEnrollment->getId() ?>">
            <label>
              <?= $schoolOfEnrollment->getLongName() ?>
            </label>
          </div>
        <?php } ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-round btn-primary" id="get-soe-report">Get Report</button>
        <button type="button" class="btn btn-round btn-secondary" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Enrollment Count Modal -->
<div id="enrollment_counts" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="enrollment_counts" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-body">
        <?php while ($subject = mth_subject::getEach()) : ?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="subject[]" id="count_subject-<?= $subject->getID() ?>" class="count_subject" value="<?= $subject->getID() ?>">
            <label for="count_subject-<?= $subject->getID() ?>">
              <?= $subject->getName() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <div class="alert alert-info alert-alt">
          These enrollment count reports include the following Schedule status types: <b>Submitted, Accepted, Updates Required, Resubmitted and Unlocked.</b>
        </div>
      </div>
      <div class="modal-footer">

        <button type="button" class="btn btn-round btn-primary" data-dismiss="modal" onclick="showReport('/schedule/course-counts?' + $('.count_subject').serialize())">Get Counts</button>
        <button type="button" class="btn btn-round btn-secondary" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>
<!-- END Enrollment Count Modal -->

<!-- Provider Schedules Modal -->
<div id="provider_schedules" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_schedules" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Schedule Periods by Provider</h3>
      </div>
      <div class="modal-body">
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="provider[]" id="provider_schedule-<?= $provider->id() ?>" class="provider_schedule" value="<?= $provider->id() ?>">
            <label for="provider_schedule-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <hr>
        <h4>Statuses</h4>
        <?php foreach (mth_schedule::status_options() as $stat_id => $_stat_option) : ?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" class="provider_schedule" value="<?= $stat_id ?>" name="statuses[]" <?= in_array($stat_id, [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST]) ? 'CHECKED' : '' ?>>
            <label><?= $_stat_option ?></label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/provider-schedules?' + $('.provider_schedule').serialize())">Get Schedule Periods</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>
<!-- END Provider Schedules Modal -->

<!-- Courses VM Order Report Modal -->
<div id="course_virtual" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="course_virtual" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Virtual Makerspace Orders by Provider</h3>
      </div>
      <div class="modal-body">
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="provider[]" id="course_virtual-<?= $provider->id() ?>" class="course_virtual" value="<?= $provider->id() ?>">
            <label for="course_virtual-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <hr>
        <h4>Statuses</h4>
        <?php foreach (mth_schedule::status_options() as $stat_id => $_stat_option) : ?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" class="course_virtual" value="<?= $stat_id ?>" name="statuses[]" <?= in_array($stat_id, [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST]) ? 'CHECKED' : '' ?>>
            <label><?= $_stat_option ?></label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/course/virtual-makerspace-order?' + $('.course_virtual').serialize())">Get Virtual Makerspace Orders</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>
<!-- END Courses VM Order Report Modal -->

<div id="hr_account_request" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="hr_account_request" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Homeroom Resources Account Request</h3>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>School Year</label>
          <select class="resource_item form-control" name="year">
            <?php while ($sy = mth_schoolYear::each()) : ?>
              <option value="<?= $sy->getID() ?>" <?= $sy->getID() == $schoolYear->getID() ? 'selected' : '' ?>>
                <?= $sy ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Student Status</label>
          <select class="resource_item form-control" name="status">
            <option></option>
            <?php foreach (mth_student::getAvailableStatuses() as $stat_id => $student_status) : ?>
              <?php if ($stat_id == mth_student::STATUS_PENDING) {
                  continue;
                } ?>
              <option value="<?= $stat_id ?>"><?= $student_status ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php while ($resource = mth_resource_settings::each(true)) : ?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="resource[]" id="resource-<?= $resource->getID() ?>" class="resource_item" value="<?= $resource->getID() ?>">
            <label for="resource-<?= $resource->getID() ?>">
              <?= $resource->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/students/hrresource-request?' + $('.resource_item').serialize())">Get HR Account Request</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="provider_schedules_username" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_schedules_username" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Schedule Periods by Provider</h3>
      </div>
      <div class="modal-body">

        <div class="form-group">
          <label>For</label>
          <select class="provider_schedules_username form-control" name="type">
            <option>Students</option>
            <option>Parents</option>
            <option>Both</option>
          </select>
        </div>

        <h5>Providers</h5>
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
            <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="provider[]" id="provider_schedules_username-<?= $provider->id() ?>" class="provider_schedules_username" value="<?= $provider->id() ?>">
            <label for="provider_schedules_username-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <div class="form-group">
          Format
          <select class="provider_usernames form-control" name="format">
            <option value="1">[last][first][year]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="2">[last][first3first][year]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 3)) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="3">[last][first]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) ?>)
            </option>
            <option value="4">[last][firstinitial]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 1)) ?>)
            </option>
            <option value="5">[last][first]mth
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . 'mth' ?> )
            </option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/provider-schedules-username?' + $('.provider_schedules_username').serialize())">Get Schedule Periods</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="provider_enrollment_counts" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_enrollment_counts" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Provider Enrollment Counts</h3>
      </div>
      <div class="modal-body">
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
            <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" name="provider[]" id="count_provider-<?= $provider->id() ?>" class="count_provider" value="<?= $provider->id() ?>">
            <label for="count_provider-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-round btn-primary" data-dismiss="modal" onclick="showReport('/schedule/provider-counts?' + $('.count_provider').serialize());">Get Counts</button>
        <button type="button" class="btn btn-round btn-secondary" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="provider_usernames" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_usernames" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Username Generator</h3>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>For</label>
          <select class="provider_usernames form-control" name="type">
            <option>Students</option>
            <option>Parents</option>
            <option>Both</option>
          </select>
        </div>

        <h5>Providers</h5>
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="provider[]" id="provider_usernames-<?= $provider->id() ?>" class="provider_usernames" value="<?= $provider->id() ?>">
            <label for="provider_usernames-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <h5>Tech & Entrepreneurship Courses</h5>
        <?php foreach (mth_course::getTechCourses() as $course) :
            if ($course->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="course[]" id="provider_usernames-c<?= $course->getID() ?>" class="provider_usernames" value="<?= $course->getID() ?>">
            <label for="provider_usernames-c<?= $course->getID() ?>">
              <?= $course->title() ?>
            </label>
          </div>
        <?php endforeach; ?>
        <?php
        ?>
        <div class="form-group">
          Format
          <select class="provider_usernames form-control" name="format">
            <option value="1">[last][first][year]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="2">[last][first3first][year]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 3)) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="3">[last][first]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) ?>)
            </option>
            <option value="4">[last][firstinitial]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 1)) ?>)
            </option>
            <option value="5">[last][first]mth
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . 'mth' ?> )
            </option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/provider-usernames?' + $('.provider_usernames').serialize());">Get Usernames</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="provider_usernames_active" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_usernames_active" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Username Generator</h3>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>For</label>
          <select class="provider_usernames_active form-control" name="type">
            <option>Students</option>
            <option>Midyear Students</option>
            <option>Parents</option>
            <option>Both</option>
          </select>
        </div>

        <h5>Providers</h5>
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="provider[]" id="provider_usernames_active-<?= $provider->id() ?>" class="provider_usernames_active" value="<?= $provider->id() ?>">
            <label for="provider_usernames_active-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <h5>Tech & Entrepreneurship Courses</h5>
        <?php foreach (mth_course::getTechCourses() as $course) :
            if ($course->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="course[]" id="provider_usernames_active-c<?= $course->getID() ?>" class="provider_usernames_active" value="<?= $course->getID() ?>">
            <label for="provider_usernames_active-c<?= $course->getID() ?>">
              <?= $course->title() ?>
            </label>
          </div>
        <?php endforeach; ?>
        <div class="form-group">
          Format
          <select class="provider_usernames_active form-control" name="format">
            <option value="1">[last][first][year]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="2">[last][first3first][year]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 3)) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="3">[last][first]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) ?>)
            </option>
            <option value="4">[last][firstinitial]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 1)) ?>)
            </option>
            <option value="5">[last][first]mth
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . 'mth' ?> )
            </option>
            <option value="6">[last][first][birthyear]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . $schoolYear->getStartYear() ?> )
            </option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/provider-usernames-active?' + $('.provider_usernames_active').serialize());">Get Usernames</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="provider_detail_usernames" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_detail_usernames" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Student Details and Username Generator</h3>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>For</label>
          <select class="provider_detail_usernames form-control" name="type">
            <option>Students</option>
            <option>Parents</option>
            <option>Both</option>
          </select>
        </div>

        <h5>Providers</h5>
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="provider[]" id="provider_detail_usernames-<?= $provider->id() ?>" class="provider_detail_usernames" value="<?= $provider->id() ?>">
            <label for="provider_detail_usernames-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <h5>Tech & Entrepreneurship Courses</h5>
        <?php foreach (mth_course::getTechCourses() as $course) :
            if ($course->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="course[]" id="provider_detail_usernames-c<?= $course->getID() ?>" class="provider_detail_usernames" value="<?= $course->getID() ?>">
            <label for="provider_detail_usernames-c<?= $course->getID() ?>">
              <?= $course->title() ?>
            </label>
          </div>
        <?php endforeach; ?>

        <div class="form-group">
          Format
          <select class="provider_detail_usernames form-control" name="format">
            <option value="1">[last][first][year]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="2">[last][first3first][year]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 3)) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="3">[last][first]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) ?>)
            </option>
            <option value="4">[last][firstinitial]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 1)) ?>)
            </option>
            <option value="5">[last][first]mth
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . 'mth' ?> )
            </option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/provider-detail-usernames?' + $('.provider_detail_usernames').serialize());">Get Usernames</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="provider_students" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_students" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Student Details</h3>
      </div>
      <div class="modal-body">
        <h5>Providers</h5>
        <?php while ($provider = mth_provider::each()) :
            if ($provider->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="provider[]" id="provider_students-<?= $provider->id() ?>" class="provider_students" value="<?= $provider->id() ?>">
            <label for="provider_students-<?= $provider->id() ?>">
              <?= $provider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <h5>Tech & Entrepreneurship Courses</h5>
        <?php foreach (mth_course::getTechCourses() as $course) :
            if ($course->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="course[]" id="provider_students-c<?= $course->getID() ?>" class="provider_students" value="<?= $course->getID() ?>">
            <label for="provider_students-c<?= $course->getID() ?>">
              <?= $course->title() ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-round btn-primary" data-dismiss="modal" onclick="showReport('/schedule/provider-students?' + $('.provider_students').serialize());">Get Student Details</button>
        <button type="button" class="btn btn-round btn-secondary" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="provider_by_student" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="provider_by_student" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Student Details and Username Generator</h3>
      </div>
      <div class="modal-body">

        <h5>Providers</h5>
        <?php while ($sprovider = mth_provider::each()) :
            if ($sprovider->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="provider[]" id="provider_by_student-<?= $sprovider->id() ?>" class="provider_by_student" value="<?= $sprovider->id() ?>">
            <label for="provider_by_student-<?= $sprovider->id() ?>">
              <?= $sprovider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <h5>Tech & Entrepreneurship Courses</h5>
        <?php foreach (mth_course::getTechCourses() as $course) :
            if ($course->archived()) continue;?>
            <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="course[]" id="provider_by_student-c<?= $course->getID() ?>" class="provider_by_student" value="<?= $course->getID() ?>">
            <label for="provider_by_student-c<?= $course->getID() ?>">
              <?= $course->title() ?>
            </label>
          </div>
        <?php endforeach; ?>
        <div class="form-group">
          Format
          <select class="provider_by_student form-control" name="format">
            <option value="1">[last][first][year]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="2">[last][first3first][year]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 3)) . $schoolYear->getStartYear() ?>
              )
            </option>
            <option value="3">[last][first]
              (<?= strtolower($person->getLastName() . $person->getFirstName()) ?>)
            </option>
            <option value="4">[last][firstinitial]
              (<?= strtolower($person->getLastName() . substr($person->getFirstName(), 0, 1)) ?>)
            </option>
            <option value="5">[last][first]mth
              (<?= strtolower($person->getLastName() . $person->getFirstName()) . 'mth' ?> )
            </option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/provider-enrollments?' + $('.provider_by_student').serialize());">Get Usernames</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>
<div id="canvas_count_totals" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="canvas_count_totals" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Canvas Course Totals</h3>
      </div>
      <div class="modal-body">
        <h5>Subjects</h5>
        <?php
        $tech_subject = mth_subject::getByStrings(['tech', 'entrepreneur']);
        if (count($tech_subject) > 0) :
          ?>
          <?php foreach ($tech_subject as $_subject) : ?>
            <div class="checkbox-primary checkbox-custom">
              <input type="checkbox" name="subjects[]" id="cct_subject-<?= $_subject->getID() ?>" class="canvas_count_totals" value="<?= $_subject->getID() ?>">
              <label for="cct_subject-<?= $_subject->getID() ?>">
                <?= $_subject->getName() ?>
              </label>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/tech-entrepreneur?' + $('.canvas_count_totals').serialize());">Get Report</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="byu_enrollments" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="byu_enrollments" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">BYU IS ENROLLMENT</h3>
      </div>
      <div class="modal-body">
        <h5>Providers</h5>
        <?php while ($bprovider = mth_provider::getProviderByName('BYU%')) : ?>
          <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="provider[]" id="provider_by_student-<?= $bprovider->id() ?>" class="provider_by_student" value="<?= $bprovider->id() ?>">
            <label for="provider_by_student-<?= $bprovider->id() ?>">
              <?= $bprovider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <h4>Statuses</h4>
        <?php foreach (mth_schedule::status_options() as $stat_id => $_stat_option) : ?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" class="provider_by_student" value="<?= $stat_id ?>" name="statuses[]" <?= in_array($stat_id, [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST]) ? 'CHECKED' : '' ?>>
            <label><?= $_stat_option ?></label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/byu-enrollments?' + $('.provider_by_student').serialize());">Get Report</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<div id="eng_enrollments" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="eng_enrollments" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edgenuity Enrollments</h3>
      </div>
      <div class="modal-body">
        <h5>Providers</h5>
        <?php while ($eprovider = mth_provider::getProviderByName('%Edgenuity%')) : ?>
          <div class="checkbox-primary checkbox-custom">
            <input type="checkbox" name="provider[]" id="eng_enrollments-<?= $eprovider->id() ?>" class="eng_enrollments" value="<?= $eprovider->id() ?>">
            <label for="eng_enrollments-<?= $eprovider->id() ?>">
              <?= $eprovider->name() ?>
            </label>
          </div>
        <?php endwhile; ?>
        <hr>
        <h4>Statuses</h4>
        <?php foreach (mth_schedule::status_options() as $stat_id => $_stat_option) : ?>
          <div class="checkbox-custom checkbox-primary">
            <input type="checkbox" class="eng_enrollments" value="<?= $stat_id ?>" name="statuses[]" <?= in_array($stat_id, [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST]) ? 'CHECKED' : '' ?>>
            <label><?= $_stat_option ?></label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/edgenuity?' + $('.eng_enrollments').serialize());">Get Report</button>
        <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Kit Orders Modal -->
<div id="kit-orders" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="kit-orders" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Schedule Periods by Provider</h3>
            </div>
            <div class="modal-body">
                <?php while ($provider = mth_provider::each()) :
                    if ($provider->archived()) continue;?>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="provider[]" id="kit-order-<?= $provider->id() ?>" class="kit-order" value="<?= $provider->id() ?>">
                        <label for="kit-order-<?= $provider->id() ?>">
                            <?= $provider->name() ?>
                        </label>
                    </div>
                <?php endwhile; ?>
                <hr>
                <h4>Statuses</h4>
                <?php foreach (mth_schedule::status_options() as $stat_id => $_stat_option) : ?>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="kit-order" value="<?= $stat_id ?>" name="statuses[]" <?= in_array($stat_id, [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST]) ? 'CHECKED' : '' ?>>
                        <label><?= $_stat_option ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-round" data-dismiss="modal" onclick="showReport('/schedule/kit-orders?' + $('.kit-order').serialize())">Get Schedule Periods</button>
                <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
            </div>
        </div>
    </div>
</div>
<!-- END Kit Orders Modal -->

<!-- END MODALS -->

<script>
  $(function() {
    var soe_report = 'students';
    $('.soe-link').click(function() {
      soe_report = $(this).data('value');
      $('#soe_student_select').modal('show');
    });

    $('#get-soe-report').click(function() {
      $('#soe_student_select').modal('hide');
      showReport('/soe/' + soe_report + '?' + $('#soe_student_select').find('input').serialize());
    });
  });
</script>
<?php
core_loader::printFooter('admin');
