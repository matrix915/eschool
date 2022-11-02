<?php

use mth\yoda\courses;
use mth\yoda\assessment;
use mth\yoda\homeroom\messages;

if (isset($_GET['unsubmitted'])) {
     $current_year = mth_schoolYear::getCurrent()->getID();
     //get current week unsubmitted log base on current week deadline
     $select = 'select ms.*,mp.* from yoda_teacher_assessments  as yta
     left join yoda_student_homeroom as ysh on ysh.yoda_course_id=yta.course_id
     inner join mth_student as ms on ms.student_id=ysh.student_id
     inner join mth_person as mp on mp.person_id=ms.person_id
     inner join mth_student_status as mss on mss.student_id=ms.student_id
     where date(deadline)=date(
          (SELECT yta_deadline.deadline 
          FROM `yoda_teacher_assessments` as yta_deadline 
          WHERE yta_deadline.deadline >= NOW()
          AND yta_deadline.course_id = ' . $_GET['unsubmitted'] . '
          ORDER BY ABS( DATEDIFF( yta_deadline.deadline, NOW() ) ) 
          LIMIT 1)
     )
     and ysh.yoda_course_id=' . $_GET['unsubmitted'] . ' and ysh.school_year_id=' . $current_year . ' and mss.school_year_id=' . $current_year . ' and mss.status in(' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
     and not exists(select * from yoda_student_assessments where assessment_id=yta.id and person_id=ms.person_id and draft is null)';
     $retval = [];

     if ($students = core_db::runGetObjects($select, 'mth_student')) {
          foreach ($students as $student) {

               if (!($parent = $student->getParent())) {
                    error_log('send homeroom marker: Parent Missing for ' . $student);
                    continue;
               }

               if (!($hr = $student->getHomeroomTeacher($current_year))) {
                    error_log('send homeroom marker: Teacher Missing for ' . $student);
                    continue;
               }

               $retval[] = [
                    'fname' => $student->getPreferredFirstName(),
                    'pfname' => $parent->getPreferredFirstName(),
                    'lname' => $student->getPreferredLastName(),
                    'tname' => $hr->getName(),
                    'pemail' => $parent->getEmail(),
                    'id' => $student->getID()
               ];
          }
     }
     echo json_encode($retval);
     exit();
}

(req_get::bool('course') && ($active_course = courses::getById(req_get::int('course')))) || die('Unable to find course to clone');
$assessment = new assessment();
$teacher = $active_course->getTeacher();

if (isset($_GET['publish'])) {
     if (empty(trim(req_post::txt('subject')))) {
          echo json_encode(['error' => 1, 'data' => ['id' => 0, 'msg' => 'Subject is required']]);
          exit();
     }

     if (empty(trim(req_post::html('content')))) {
          echo json_encode(['error' => 1, 'data' => ['id' => 0, 'msg' => 'Content is required']]);
          exit();
     }

     $student_name = trim(req_post::txt('fname'));
     $parent_name = trim(req_post::txt('pfname'));

     $content = str_replace(
          ['[PARENT_FIRST]', '[STUDENT_FIRST]', '[TEACHER_FULL_NAME]'],
          [$parent_name, $student_name, $teacher->getName()],
          req_post::html('content')
     );


     if ($teacher && messages::publish(
          $content,
          req_post::txt('subject'),
          [req_post::txt('pemail')],
          null,
          [$teacher->getEmail(), $teacher->getName()]
     )) {
          echo json_encode(['error' => 0, 'data' => ['id' => req_post::int('id'), 'msg' => 'Sent']]);
     } else {
          echo json_encode(['error' => 1, 'data' => ['id' => req_post::int('id'), 'msg' => 'Error Sending']]);
     }

     exit();
}


core_loader::isPopUp();
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader();
?>
<style>
     .active-row {
          background-color: #ffc107;
     }

     .fl-hidden {
          display: none;
     }
</style>
<div class="log-header">
     <button type="button" class="float-right btn btn-round btn-default" onclick="closeLog()">
          <i class="fa fa-close"></i>
     </button>
     <h4><span style="color:#2196f3"> <?= $active_course->getName(); ?></h4>
</div>
<div class="row" style="margin-top: 60px;">
     <div class="col">
          <div class="card">
               <div class="card-header">
                    Total Students: <span class="student_count_display"></span>
               </div>
               <div class="card-block pl-0 pr-0">
                    <table id="homeroom_table" class="table responsive">
                         <thead>
                              <tr>
                                   <th> <input type="checkbox" title="Un/Check All" class="check-all"></th>
                                   <th>Last Name</th>
                                   <th>First Name</th>
                                   <th>Parent Email</th>
                                   <th></th>
                              </tr>
                         </thead>
                         <tbody></tbody>
                    </table>
               </div>
          </div>
     </div>
     <div class="col">
          <form action="?form=<?= uniqid() ?>" method="post" id="announcement-form">
               <div class="form-group">
                    <label>From</label>
                    <input type="text" class="form-control" disabled name="to" value="<?= $teacher ? $teacher->getEmail() : '' ?>">
               </div>
               <div class="form-group">
                    <label>Subject</label>
                    <input type="text" class="form-control" required name="subject" value="<?= core_setting::get('homeroomreminder', 'Homeroom') ?>">
                    <!-- <input type="hidden" name="id" value=""> -->
               </div>
               <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" class="form-control" required id="announcement-content"><?= core_setting::get('homeroomremindercontent', 'Homeroom') ?></textarea>
               </div>

               <div class="card">
                    <div class="card-header">
                         <h4 class="card-title mb-0">Email Preview</h4>
                    </div>
                    <div id="email-preview" class="card-block cke-preview">
                         <?= core_setting::get('homeroomremindercontent', 'Homeroom') ?>
                    </div>
               </div>

               <p>
                    <button name="submit" type="button" onclick="publish()" class="publish-btn btn btn-success btn-round">Send</button>
                    <button class="btn btn-secondary btn-round" type="button" onclick="top.global_popup_close('send_message_popup')">Close</button>
               </p>
          </form>
     </div>
</div>
<?php
core_loader::includejQueryValidate();
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>
<script src="//cdn.ckeditor.com/4.10.1/full/ckeditor.js"></script>
<script>
     CKEDITOR.config.removePlugins = "iframe,print,format,pastefromword,pastetext,about,image,forms,youtube,iframe,print,stylescombo,flash,newpage,save,preview,templates";
     CKEDITOR.config.disableNativeSpellChecker = false;
     CKEDITOR.config.removeButtons = "Subscript,Superscript";

     tobesend = {};
     vindex = 0;
     errors = 0;

     CKEDITOR.replace('announcement-content');
     CKEDITOR.instances["announcement-content"].on('change', function() {
          $('#email-preview').html(this.getData());
     });

     function closeLog() {
          parent.global_popup_iframe_close('mth_student_learning_logs');
     }

     function publish() {
          if ($('.actionCB:checked').length == 0) {
               swal('', 'There is no student(s) selected.', 'error');
               return false;
          }

          tobesend = $('.actionCB:checked').map(function() {
               return $(this).data();
          }).get();

          CKEDITOR.instances["announcement-content"].updateElement();

          var $publishbtn = $('.publish-btn');
          global_waiting();

          _publish();
     }

     function _publish() {
          var item = tobesend[vindex];
          if (item == undefined) {
               global_waiting_hide();
               var message = "Done sending reminder to parents.";
               if (errors > 0) {
                    message += ' ' + errors + ' error(s) detected.';
               }

               if (errors == parentcount) {
                    message = 'There seems to be an issue sending the reminder.'
               }
               swal('', message, '');
          } else {
               $.ajax({
                    'url': '?publish=1&course=<?= $active_course->getCourseId() ?>',
                    'type': 'post',
                    'data': $('#announcement-form').serialize() + '&' + $.param(item),
                    dataType: "json",
                    success: function(response) {
                         vindex++;
                         if (response.error == 0) {
                              var data = response.data;
                              $('#st' + data.id).find('.sent').fadeIn();
                         } else {
                              $('#st' + response.data.id).find('.error').fadeIn();
                              errors++;
                         }

                         _publish();
                    },
                    error: function() {
                         global_waiting_hide();
                         alert('there is an error occur when publishing');
                    }
               });
          }
     }

     $(function() {

          $('.check-all').change(function() {
               var check = $(this).is(':checked');
               $('.actionCB').prop("checked", check);
          });

          function loadUnsubmitted() {
               global_waiting();
               $.ajax({
                    url: '?unsubmitted=<?= $active_course->getCourseId() ?>',
                    method: 'get',
                    dataType: 'JSON',
                    success: function(response) {
                         $('.student_count_display').text(response.length);
                         if (response.length == 0) {
                              $('#log-control').fadeOut();
                         } else {
                              $('#log-control').fadeIn();
                         }
                         parentcount = response.length;
                         $.each(response, function(i, val) {
                              $('#st' + val.id).length == 0 && $('#homeroom_table tbody').append('<tr id="st' + val.id + '" ><td><input type="checkbox" data-id="' + val.id + '" data-pfname="' + val.pfname + '" data-tname="' + val.tname + '" data-fname="' + val.fname + '" data-pemail="' + val.pemail + '" class="actionCB"></td><td>' + val.lname + '</td><td><i class="fa fa-check sent"  style="display:none;color:green;"></i><i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>' + val.fname + '</td><td>' + val.pemail + '</td><td><a href="#" onclick=\'global_popup_iframe("mth_student_learning_logs", "/_/user/learning-logs?student=' + val.id + '")\'>Learning Log</a></t></tr>');
                         });
                         global_waiting_hide();
                    }
               });
          }

          loadUnsubmitted();

     });
</script>