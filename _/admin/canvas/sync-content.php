<?php
if (req_get::bool('term')) {
     $page = req_get::int('term');
     $result = mth_canvas_term::single_mapping($page);
     $result['page'] = $page+=1;
     echo  json_encode($result);
     exit();
}

if (req_get::bool('user')) {
     $page = req_get::int('user');
     $result = mth_canvas_user::singlePull($page);
     $result['page'] = $page+=1;
     echo  json_encode($result);
     exit();
}

if (req_get::bool('resetuser')) {
     echo mth_canvas_user::deleteUnmatched() ? 1 : 0;
     exit();
}

if (req_get::bool('course')) {
     $page = req_get::int('course');
     $result = mth_canvas_course::single_update_mapping($page);
     $result['page'] = $page+=1;
     echo  json_encode($result);
     exit();
}

if (req_get::bool('enrollment')) {
     $page = req_get::int('enrollment');
     $result = mth_canvas_enrollment::single_pull_course_enrollments(req_get::int('canvas_id'), $page);
     $result['page'] = $page+=1;
     $result['canvas_id'] = req_get::int('canvas_id');
     echo  json_encode($result);
     exit();
}

if (req_get::bool('push_user')) {
     echo (($success = mth_canvas_user::pushUserAccounts(false)) === TRUE)?1:0;
     exit();
}

if (req_get::bool('count')) {
     mth_canvas_user::count(NULL, true, true);
     mth_canvas_user::count(NULL, false, true);
     exit('User Counts updated...');
}

core_loader::isPopUp();
core_loader::printHeader();

$ppyear = mth_schoolYear::getPrevious()->getPreviousYear();
$pyear = mth_schoolYear::getPrevious();
$cyear = mth_schoolYear::getCurrent();
?>
<style>
     tr.active {
          color: #3f51b5;
          font-weight: bold;
     }
</style>
<div class="panel panel-primary panel-line">
     <div class="panel-body">
          <div class="progress progress-sm">
               <div class="sync-progress progress-bar progress-bar-indicating active" style="width: 5%;" role="progressbar"></div>
          </div>
          <table class="table">
               <tr id="term_row">
                    <td>Canvas Term Mapping</td>
                    <td class="cstatus">
                    </td>
               </tr>
               <tr id="user_row">
                    <td>Download Canvas Users</td>
                    <td class="cstatus"></td>
               </tr>
               <tr id="course_row">
                    <td>Canvas Course Mapping</td>
                    <td class="cstatus"></td>
               </tr>
               <tr id="ppcourse_row">
                    <td>
                         Download <?= $ppyear ?> Canvas enrollments
                    </td>
                    <td class="cstatus"></td>
               </tr>
               <tr id="pcourse_row">
                    <td>Download <?= $pyear ?> Canvas enrollments</td>
                    <td class="cstatus"></td>
               </tr>
               <tr id="ccourse_row">
                    <td>Download Current Canvas enrollments</td>
                    <td class="cstatus"></td>
               </tr>
               <tr id="push_user_row">
                    <td>Update Canvas Users</td>
                    <td class="cstatus"></td>
               </tr>
               <tr id="count_row">
                    <td>Update User Count</td>
                    <td class="cstatus"></td>
               </tr>
          </table>
     </div>
     <div class="panel-footer pt-20">
          <div class="footer-close" style="display:none">
          <button type="button" onclick="parent.location.reload(true)" class="btn btn-round btn-secondary">Close</button>
          See the Canvas Management for any errors.
          </div>
     </div>
</div>
<?php
core_loader::printFooter();
?>
<script>
     <?php
     function getCourses($year)
     {
          $courses = [];
          mth_canvas_course::each($year, true);
          while ($course = mth_canvas_course::each($year)) {
               $courses[] = $course->canvas_course_id();
          }
          return $courses;
     }

     ?>
     var current_progress = 0;
     $term = $('#term_row');
     $user = $('#user_row');
     $course = $('#course_row');
     $ppcourse = $('#ppcourse_row');
     $pcourse = $('#pcourse_row');
     $ccourse = $('#ccourse_row');
     $push_user = $('#push_user_row');
     $count_row = $('#count_row');
     $sync_bar = $('.sync-progress');

     ppcourse = <?= json_encode(getCourses($ppyear)) ?>;
     pcourse = <?= json_encode(getCourses($pyear)) ?>;
     ccourse = <?= json_encode(getCourses($cyear)) ?>;

     function success($target, message) {
          current_progress+=10;
          $sync_bar.css('width',current_progress+'%');
          $target.find('.cstatus').html('<span style="color:green;"><i class="fa fa-check"></i> ' + message + '</span>');
          $target.removeClass('active');
     }

     function error($target, message) {
          current_progress+=10;
          $sync_bar.css('width',current_progress+'%');
          $target.find('.cstatus').html('<span style="color:red"><i class="fa fa-exclamation-circle" ></i> ' + message + '</span>');
          $target.removeClass('active');
     }

     function progress($target) {
          $target.addClass('active');
          $target.find('.cstatus').html('<i class="icon md-refresh icon-spin"></i> Processing');
     }

     function countUser(){
          progress($count_row);
          $.ajax({
               url: '?count=1',
               type: 'GET',
               success: function(response) {
                    success($count_row, 'Done');
                    $sync_bar.css('width','100%');
                    setTimeout(function(){
                         $sync_bar.parent().fadeOut();
                    },5000);
                    $('.footer-close').show();
               },
               error: function(){
                    error($count_row, 'Server Error');
               }
          });
     }

     function pushUser(){
          progress($push_user);
          $.ajax({
               url: '?push_user=1',
               type: 'GET',
               success: function(response) {
                    if(response == 1){
                         success($push_user, 'Done');
                    }else{
                         error($push_user, 'Process Error');
                    }
                    countUser();
               },
               error: function(){
                    error($push_user, 'Server Error');
                    countUser();
               }
          });
     }

     var CENROLLMENTS = {
          current_cindex: 0,
          start: function(){
               progress($ccourse);
               this.each(1);
          },
          get: function(page,canvas_id){
               $.ajax({
                    'url': '?enrollment=' + page+'&canvas_id='+canvas_id,
                    type: 'GET',
                    dataType: 'JSON',
                    success: function(response) {
                         if (response.error) {
                              error($ccourse, 'Process Error');
                              pushUser();
                         } else {
                              if (response.result > 0) {
                                   CENROLLMENTS.get(response.page,response.canvas_id); //keep pulling enrollments for current course
                              } else {
                                   CENROLLMENTS.each(1); //look for another course for ppenrollment array
                              }
                         }
                    },
                    error: function() {
                         error($ccourse, 'Server Error');
                    }
               });
          },
          each: function(page){
               if(ccourse[CENROLLMENTS.current_cindex] != undefined){
                    CENROLLMENTS.get(page,ccourse[CENROLLMENTS.current_cindex]);
                    CENROLLMENTS.current_cindex += 1;
               }else{
                    success($ccourse, 'Done');
                    pushUser();
               }
          }
     };

     var PENROLLMENTS = {
          current_cindex: 0,
          start: function(){
               progress($pcourse);
               this.each(1);
          },
          get: function(page,canvas_id){
               $.ajax({
                    'url': '?enrollment=' + page+'&canvas_id='+canvas_id,
                    type: 'GET',
                    dataType: 'JSON',
                    success: function(response) {
                         if (response.error) {
                              error($pcourse, 'Process Error');
                              CENROLLMENTS.start();
                         } else {
                              if (response.result > 0) {
                                   PENROLLMENTS.get(response.page,response.canvas_id); //keep pulling enrollments for current course
                              } else {
                                   PENROLLMENTS.each(1); //look for another course for ppenrollment array
                              }
                         }
                    },
                    error: function() {
                         error($pcourse, 'Server Error');
                    }
               });
          },
          each: function(page){
               if(pcourse[PENROLLMENTS.current_cindex] != undefined){
                    PENROLLMENTS.get(page,pcourse[PENROLLMENTS.current_cindex]);
                    PENROLLMENTS.current_cindex += 1;
               }else{
                    success($pcourse, 'Done');
                    CENROLLMENTS.start();
               }
          }
     };

     var PPENROLLMENTS = {
          current_cindex: 0,
          start: function(){
               progress($ppcourse);
               this.each(1);
          },
          get: function(page,canvas_id){
               $.ajax({
                    'url': '?enrollment=' + page+'&canvas_id='+canvas_id,
                    type: 'GET',
                    dataType: 'JSON',
                    success: function(response) {
                         if (response.error) {
                              error($ppcourse, 'Process Error');
                              PENROLLMENTS.start();
                         } else {
                              if (response.result > 0) {
                                   PPENROLLMENTS.get(response.page,response.canvas_id); //keep pulling enrollments for current course
                              } else {
                                   PPENROLLMENTS.each(1); //look for another course for ppenrollment array
                              }
                         }
                    },
                    error: function() {
                         error($ppcourse, 'Server Error');
                    }
               });
          },
          each: function(page){
               if(ppcourse[PPENROLLMENTS.current_cindex] != undefined){
                    PPENROLLMENTS.get(page,ppcourse[PPENROLLMENTS.current_cindex]);
                    PPENROLLMENTS.current_cindex += 1;
               }else{
                    success($ppcourse, 'Done');
                    PENROLLMENTS.start();
               }
          }
     };

     var COURSE = {
          start: function(){
               progress($course);
               this.get(1);
          },
          get: function(page){
               $.ajax({
                    'url': '?course=' + page,
                    type: 'GET',
                    dataType: 'JSON',
                    success: function(response) {
                         if (response.error) {
                              error($course, 'Process Error');
                              PPENROLLMENTS.start();
                         } else {
                              if (response.result > 0) {
                                   COURSE.get(response.page);
                              } else {
                                   success($course, 'Done');
                                   PPENROLLMENTS.start();
                              }
                         }
                    },
                    error: function() {
                         error($course, 'Server Error');
                    }
               });
          }
     }

     var CUSER = {
          reset: function() {
               $.ajax({
                    'url': '?resetuser=1',
                    type: 'GET',
                    success: function(response) {
                         CUSER.get(1);
                    },
                    error: function() {
                         error($user, 'Server Error');
                    }
               });
          },
          start: function(){
               progress($user);
               CUSER.reset();
          },
          get: function(page){
               $.ajax({
                    'url': '?user=' + page,
                    type: 'GET',
                    dataType: 'JSON',
                    success: function(response) {
                         if (response.error) {
                              error($user, 'Process Error');
                              COURSE.start();
                         } else {
                              if (response.result > 0) {
                                   CUSER.get(response.page);
                              } else {
                                   success($user, 'Done');
                                   COURSE.start();
                              }
                         }
                    },
                    error: function() {
                         error($user, 'Server Error');
                    }
               });
          }
     };



     var TERM = {
          get: function(page) {
               $.ajax({
                    'url': '?term=' + page,
                    type: 'GET',
                    dataType: 'JSON',
                    success: function(response) {
                         if (response.error) {
                              error($term, 'Process Error');
                              CUSER.start();
                         } else {
                              if (response.result > 0) {
                                   TERM.get(response.page);
                              } else {
                                   success($term, 'Done');
                                   CUSER.start();
                              }
                         }
                    },
                    error: function() {
                         error($term, 'Server Error');
                    }
               });
          },
          start: function() {
               progress($term);
               this.get(1);
          }
     }



     $(function() {
          TERM.start();
     })
</script>