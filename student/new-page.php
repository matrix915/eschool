<?php
/* @var $parent mth_parent */

if (!empty($_GET['formId'])) {
  core_loader::formSubmitable('newStudentApplication-' . $_GET['formId']) || die();

  $studentSlug = null;
  $success = [];
  $year = req_post::bool('y') ? mth_schoolYear::getByStartYear(req_post::int('y')) : mth_schoolYear::getApplicationYear();

  foreach ($_POST['student'] as $studentFields) {
    $studentFields = new req_array($studentFields);
    if (empty($studentFields['first_name'])) {
      continue;
    }
    $student = mth_student::create();
    $student->setName($studentFields['first_name'], $studentFields['last_name']);
    $student->setGradeLevel($studentFields['grade_level'], $year);
    $student->setParent($parent);
    $student->set_spacial_ed($studentFields['special_ed']);
    if (!$studentSlug) {
      $studentSlug = $student->getSlug();
    }
    $application = mth_application::startApplication($student, $year);
    $application->setCityOfResidence($_POST['city_of_residence']);
    $success[] = $application->submit($_POST['agrees_to_policies']);
  }

  if (count($success) != count(array_filter($success))) {
    core_notify::addError('There were some errors submitting the application, please check your student information.');
  }
  //header('location: /student/' . $studentSlug);
  header('location:/');
  exit();
}

cms_page::setPageTitle('New Student Application');

cms_page::setPageContent('<p>Our tuition-free, personalized distance education program is available to home-based students between the ages of 6-18 residing in Utah.</p>');

cms_page::setPageContent(
  'Student(s) agrees to adhere to all program policies and requirements, including participation in state testing.  Review details at <a href="http://mytechhigh.com/utah/" target="_blank">mytechhigh.com/utah</a>.',
  'Agree To Policies Text',
  cms_content::TYPE_LIMITED_HTML
);

cms_page::setPageContent(
  '<p>We are not yet ready to recieve applications for the comming year. Please contact us if you need assistance.</p>',
  'Year Not Available',
  cms_content::TYPE_HTML
);


core_loader::includejQueryValidate();

core_loader::printHeader('student');

$current_year = mth_schoolYear::getCurrent();
$nextYear = mth_schoolYear::getApplicationYear();
$midyearavailable = $current_year ? ($current_year->isMidYearAvailable() && $current_year->midYearAvailable()) : false;
$other_year = $nextYear != $current_year ? $current_year : null;

?>
<style>
  .hide-sped {
    display: none;
  }
</style>
<div class="page">
  <?= core_loader::printBreadCrumb('window'); ?>
  <div class="page-content container-fluid">

    <?php if ($nextYear || $other_year) : ?>
      <form method="post" id="application-form" action="?formId=<?= uniqid() ?>">
        <div class="card card-shadow">
          <div class="card-header p-20">
            <!-- <h4 class="card-title mb-0">
                    Apply for the <?= $nextYear ?> Program
                </h4> -->
            <select name="y" style="font-size: 18px; color:#424242;background:none;border:none;" id="select-program">
              <?php if ($nextYear) : ?>
                <option value="<?= $nextYear->getStartYear() ?>">Apply for the <?= $nextYear ?> Program</option>
              <?php endif; ?>
              <?php if ($midyearavailable && $other_year) : ?>
                <option value="<?= $other_year->getStartYear() ?>">Apply for the <?= $other_year ?> Program</option>
              <?php endif; ?>
            </select>
            <?= cms_page::getDefaultPageMainContent() ?>
          </div>
          <div class="card-block">
            <div class="row">
              <?php for ($s = 1; $s <= 10; $s++) : ?>
                <div class="col-md-6 stcont" style="<?= $s > 1 ? 'display:none;' : '' ?>" id="student-<?= $s ?>">
                  <?php if ($s > 1) : ?>
                    <button type="button" class="btn btn-sm btn-round btn-primary float-right mb-5 hidestudent"><i class="fa fa-close"></i></button>
                  <?php endif; ?>
                  <div class="form-group">
                    <label for="student-<?= $s ?>-first_name">Student First Name</label>
                    <input type="text" name="student[<?= $s ?>][first_name]" class="form-control" id="student-<?= $s ?>-first_name" required />
                  </div>
                  <div class="form-group">
                    <label for="student-<?= $s ?>-last_name">Student Last Name</label>
                    <input type="text" name="student[<?= $s ?>][last_name]" class="form-control" id="student-<?= $s ?>-last_name" required>
                  </div>
                  <div class="form-group">
                    <label for="student-<?= $s ?>-grade_level">Student Grade Level (age)</label>
                    <select name="student[<?= $s ?>][grade_level]" class="student-grade-level-select form-control" id="student-<?= $s ?>-grade_level" required>
                      <option></option>
                      <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                        <option value="<?= $grade_level ?>"><?= $grade_desc ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group sped-container">
                    <label for="student-<?= $s ?>-special_ed">Has student ever been diagnosed with a learning disability or ever qualified for Special Education Services through an IEP or 504 plan (including Speech Therapy)?</label>
                    <?php foreach (mth_student::getAvailableSpEd() as $sped => $label) : ?>
                      <?php if ($sped != mth_student::SPED_EXIT) : ?>
                        <div class="radio-custom radio-primary">
                          <input type="radio" class="sped" id="student-<?= $s ?>-special_ed-<?= $sped ?>" value="<?= $sped ?>" name="student[<?= $s ?>][special_ed]" <?= $sped == mth_student::SPED_NO ? 'checked' : '' ?> />


                          <label for="student-<?= $s ?>-special_ed-<?= $sped ?>">
                            <?= $label ?>
                            <?php if ($sped != mth_student::SPED_NO) : ?>
                              (additional documents will be required)
                            <?php endif; ?>
                          </label>
                        </div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    <div class="alert alert-info alert-alt sped-note hide-sped">
                      Please review and <a href="https://goo.gl/forms/lnCEYLTs98OcfKlD3" target="_blank">submit this form</a> as part of the standard application process.
                    </div>
                  </div>
                </div>
              <?php endfor; ?>
              <script>
                var c = 1;
              </script>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>State of Residence</label>
                  <?php $address = $parent->getAddress(); ?>
                  <input type="text" name="city_of_residence" id="city_of_residence" value="<?= $address ? $address->getCity() : '' ?>" class="form-control" required>
                </div>
                <div class="form-group">
                  <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="agrees_to_policies" id="agrees_to_policies" required>
                    <label for="agrees_to_policies"><?= cms_page::getDefaultPageContent('Agree To Policies Text', cms_content::TYPE_LIMITED_HTML) ?></label>
                  </div>
                  <label class="error" id="agrees_to_policies-error" for="agrees_to_policies"></label>
                </div>
                <button type="submit" class="btn btn-success btn-lg btn-round">Submit</button>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-primary btn-round btn-lg" onclick="c++; $('#student-'+c).fadeIn(); if(c>=10){ $(this).hide(); }">+ Add Student</button>
          </div>
        </div>
      </form>
    <?php
    else :
      echo cms_page::getDefaultPageContent('Year Not Available', cms_content::TYPE_HTML);
    endif;
    ?>
  </div>
</div>
<?php

core_loader::printFooter('student');
?>
<script type="text/javascript">
  $(function() {
    $('.student-grade-level-select').change(function() {
      $('.' + (this.id.replace('-grade_level', '')) + '-diploma_seeking-cb')
        .prop('disabled', (this.value === 'K' || Number(this.value) < 9));
    });
    $('#application-form').validate();
    var sped_no = <?= mth_student::SPED_NO ?>;
    $('.sped').change(function() {
      var $sped_note = $(this).closest('.sped-container').find('.sped-note');

      if ($(this).val() == sped_no) {
        $sped_note.addClass('hide-sped');
      } else {
        $sped_note.removeClass('hide-sped');
      }

    });
    $('.hidestudent').click(function() {
        let studentelements = $(this).closest('.stcont');
        studentelements.hide();
        document.getElementById(studentelements[0]['id'] + '-first_name').value = '';
    });
  });
</script>