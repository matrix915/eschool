<?php

use mth\yoda\courses;

$course = req_get::bool('course')?courses::getById(req_get::int('course')):new courses();

if(req_get::bool('form')){
     if($course->getCourseId()){
          $course->set('name',req_post::txt('name'));
          $course->set('school_year_id',req_post::int('sy'));
          $course->set('teacher_user_id',req_post::int('teacher'));
     }else{
          $course->setInsert('name',req_post::txt('name'));
          $course->setInsert('school_year_id',req_post::int('sy'));
          $course->setInsert('teacher_user_id',req_post::int('teacher'));
          $course->setInsert('workflow_state',1);
          $course->setInsert('type',courses::HOMEROOM);
          $course->setInsert('mth_course_id',0);
     }
    
     if($course->save()){
          core_notify::addMessage('Changes Saved.');
     }else {
          core_notify::addError('Unable to save changes.');
     }
     core_loader::redirect('?course='.$course->getCourseId());
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<form id="courseform" method="post" action='?form=<?= uniqid('yoda-grade-learning-log') ?><?=req_get::bool('course')?'&course='.req_get::int('course'):''?>'> 
     <div class="card">
          <div class="card-block">
               <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="name" class="form-control" value="<?=$course->getName()?>">
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
                    <?php $users = core_user::getUsersByLevel(mth_user::L_TEACHER, ['first_name', 'last_name']);?>
                    <select class="form-control" name="teacher">
                         <option></option>
                         <?php foreach ($users as $teacher) : /* @var $year mth_schoolYear */ ?>
                              <option value="<?= $teacher->getID() ?>" <?= $course->getTeacherUserID() ==  $teacher->getID() ? 'SELECTED' : '' ?>>
                                   <?= $teacher->getName() ?>
                              </option>
                         <?php endforeach; ?>
                    </select>
               </div>
          </div>
          <div class="card-footer">
               <button class="btn btn-round btn-primary" type="submit">Save</button>
               <button type="button" class="btn btn-round btn-secondary" onclick="closeCourse()">
                    <i class="fa fa-close"></i> Close
               </button>
          </div>
     </div>
</form>
<?php
core_loader::printFooter();
?>
<script>
     function closeCourse(){
          parent.global_popup_iframe_close('course_popup');
          parent.updateTable();
     }
</script>