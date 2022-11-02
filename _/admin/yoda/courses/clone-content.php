<?php

use mth\yoda\courses;
use mth\yoda\assessment;

(req_get::bool('course') && ($course = courses::getById(req_get::int('course')))) || die('Unable to find course to clone');


if (req_get::bool('form')) {
     $ncourse = new courses();
     $ncourse->setInsert('name', req_post::txt('name'));
     $ncourse->setInsert('school_year_id', req_post::int('sy'));
     $ncourse->setInsert('teacher_user_id', req_post::int('teacher'));
     $ncourse->setInsert('workflow_state', 1);
     $ncourse->setInsert('type', courses::HOMEROOM);
     $ncourse->setInsert('mth_course_id', 0);
     if ($ncourse->save()) {
          $assessment = new assessment();
          foreach ($assessment->getByCourseId($course->getCourseId()) as $key => $log) {
               if (null !== req_post::int('keep_date_settings')) {
                    $log->clone_date = true;
               }

               if (!$log->clone(true, $ncourse->getCourseId(), false, req_post::int('sy'))) {
                    core_notify::addError('Unable to clone ' . $log->getTitle());
               }
          }
          core_notify::addMessage('Clone created successfully');
     } else {
          core_notify::addError('Unable to create clone');
     }
     core_loader::redirect('/_/admin/yoda/courses/edit?course=' . $ncourse->getCourseId());
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<form id="courseform" method="post" action='?form=<?= uniqid('yoda-grade-learning-log') ?><?= req_get::bool('course') ? '&course=' . req_get::int('course') : '' ?>'>
     <div class="card">
          <div class="card-block">
               <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $course->getName() ?>">
               </div>
               <div class="form-group">
                    <label>School Year</label>
                    <select class="form-control" name="sy">
                         <?php foreach (mth_schoolYear::getSchoolYears() as $year) : /* @var $year mth_schoolYear */ ?>
                              <option value="<?= $year->getID() ?>" <?= $course->getSchoolYearId() == $year->getID() ? 'SELECTED' : '' ?>>
                                   <?= $year ?>
                              </option>
                         <?php endforeach; ?>
                    </select>
               </div>
               <div class="form-group">
                    <label>Teacher</label>
                    <?php $users = core_user::getUsersByLevel(mth_user::L_TEACHER, ['first_name', 'last_name']); ?>
                    <select class="form-control" name="teacher">
                         <option></option>
                         <?php foreach ($users as $teacher) : /* @var $year mth_schoolYear */ ?>
                              <option value="<?= $teacher->getID() ?>" <?= $course->getTeacherUserID() ==  $teacher->getID() ? 'SELECTED' : '' ?>>
                                   <?= $teacher->getName() ?>
                              </option>
                         <?php endforeach; ?>
                    </select>
               </div>

               <div class="alert alert-info alert-alt">
                    Found <b><?= assessment::getLearningLogCount(req_get::int('course')) ?></b> learning log(s). Be sure to edit learning logs deadline after cloning this homeroom.
               </div>
               <div class="form-check">
                    <div class="checkbox-custom checkbox-primary">
                         <input type="checkbox" name="keep_date_settings" id="keepDateSettings">
                         <label>Keep date settings</label>
                    </div>
               </div>
          </div>
          <div class="card-footer">
               <button class="btn btn-round btn-primary" type="button" onclick="clone()">Clone</button>
               <button type="button" class="btn btn-round btn-secondary" onclick="closeCourse()">
                    <i class="fa fa-close"></i> Cancel
               </button>
          </div>
     </div>
</form>
<?php
core_loader::printFooter();
?>
<script>
     function closeCourse() {
          parent.global_popup_iframe_close('course_popup');
     }

     function clone() {
          global_waiting();
          $('#courseform').submit();
     }
</script>