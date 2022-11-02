<?php

use mth\yoda\courses;
use mth\yoda\assessment;

if(req_get::bool('clone')){
     $success = false;
     if($assessment = assessment::getById(req_get::int('clone'))){
          $success = $assessment->clone();
          if($success){
               core_notify::addMessage('New copy of a '.$assessment->getTitle().' has been created.');
          }
     }
     echo json_encode(['error'=>(int) !$success]);
     exit;
}

if(req_get::bool('delete')){
     $success = false;
     if($assessment = assessment::getById(req_get::int('delete'))){
          $success = $assessment->delete();
          if($success){
               core_notify::addMessage($assessment->getTitle().' has been deleted.');
          }
     }
     echo json_encode(['error'=>(int) !$success]);
     exit;
}

(req_get::bool('course') && ($active_course = courses::getById(req_get::int('course')))) || die('Unable to find course to clone');
$assessment = new assessment();

core_loader::isPopUp();
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader();
?>
<style>
.active-row{
     background-color:#ffc107;
}

.fl-hidden{
     display:none;
}
</style>
<div class="log-header">
     <button type="button" class="float-right btn btn-round btn-default" onclick="closeLog()">
          <i class="fa fa-close"></i>
     </button>
     <h4><span style="color:#2196f3"> <?= $active_course->getName() ?></h4>
</div>
<div class="row" style="margin-top: 60px;">
     <div class="col-md-6">
          <div class="panel panel-primary">
               <div class="panel-heading">
                    <h4 class="panel-title">
                         Learning Logs
                    </h4>
                    <div class="panel-actions panel-actions-keep">
                         <button class="btn btn-floating btn-primary btn-sm" type="button" onclick="global_popup_iframe('yoda_assessment_edit','/_/teacher/log/edit?course=<?= $active_course->getCourseId() ?>')">
                              <i class="fa fa-plus" aria-hidden="true"></i>
                         </button>
                         <button class="btn btn-floating btn-primary btn-sm" title="Adjust Deadlines" type="button" onclick="deadline(<?= $active_course->getCourseId() ?>)">
                              <i class="fa fa-calendar" aria-hidden="true"></i>
                         </button>
                    </div>
               </div>
               <div class="panel-body p-0 pt-10 pb-10">
                    <table class="table" id="lltable">
                         <thead>
                              <th>
                                   Title
                              </th>
                              <th>
                                   Deadline
                              </th>
                              <th>
                                   Actions
                              </th>
                              <th>
                                   Ungraded
                              </th>
                         </thead>
                         <tbody>
                              <?php foreach ($assessment->getByCourseId($active_course->getCourseId()) as $key => $log) : ?>
                                   <?php
                                   $ungraded = $log->getUngradedCount();
                                   $deadline = $log->getDeadline();
                                   $schoolYear = mth_schoolYear::getByDate(core_model::getDate($deadline, false));
                                   $yearID = $schoolYear->getID();
                                   ?>
                                   <tr class="<?= $ungraded > 0 ? 'undgraded' : 'graded' ?>" id="assessment_<?= $log->getID() ?>">
                                        <td><?= $log->getTitle() ?></td>
                                        <td><?= $log->getDeadline('M j') ?></td>
                                        <td>
                                             <button class="btn" title="Edit" onclick="global_popup_iframe('yoda_assessment_edit','/_/teacher/log/edit?log=<?= $log->getID() ?>')"><i class="fa fa-edit"></i></button>
                                             <button class="btn delete" title="Delete" data-id="<?= $log->getID() ?>"><i class="fa fa-trash"></i></button>
                                             <button class="btn replicate" title="Replicate" data-id="<?= $log->getID() ?>"><i class="fa fa-copy"></i></button>
                                             <button class="btn" title="View view-log" onclick="global_popup_iframe('yoda_assessment_view','/_/teacher/log?log=<?= $log->getID() ?>')"><i class="fa fa-search"></i></button>
                                             <button type="button" class="btn btn-primary loadlog" title="Load" data-id="<?= $log->getID() ?>" data-year="<?= $yearID ?>">
                                                  <i class="fa fa-arrow-right"></i>
                                             </button>
                                        </td>
                                        <td class="ungraded_col"><?= $ungraded ?></td>
                                   </tr>
                              <?php endforeach; ?>
                         </tbody>
                    </table>
               </div>
          </div>
     </div>
     <div class="col-md-6">
          <div class="panel panel-primary">
               <div class="panel-heading">
                    <h4 class="panel-title">
                         Student Learning Logs
                    </h4>
               </div>
               <div class="panel-body pb-5 pt-5">
                    <div class="form-group">
                         <input type="text" class="form-control" id="ssl_search">
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                         <input type="checkbox" id="show-zeros-only">
                         <label>Show Needs Resubmission</label>
                    </div>
               </div>
               <div class="list-group list-group-bordered" id="sll_tbl">
               </div>
          </div>
     </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('homeroomjs', core_config::getThemeURI() . '/assets/js/Homeroom.js');
core_loader::addJsRef('filterljs', '/_/teacher/filterlog.js');
core_loader::printFooter();
?>
<script>
     function closeLog() {
          parent.global_popup_iframe_close('mth_student_learning_logs');
     }
     function set_active_ll($this){
          $('#lltable tr.active-row').removeClass('active-row');
          $this.closest('tr').addClass('active-row');
     }
     function gradeLearningLog(id){
          global_popup_iframe('yoda_assessment_edit','/_/teacher/log/grade?id='+id);
     }

     function updateActiveLog(log){
          getUngradedCount(log);
          $('.loadlog[data-id='+log+']').trigger('click');
     }

     function updateHomeroomList(){
          location.reload();
     }

     function getUngradedCount(assessment_id){
          $.ajax({
               url: '/_/teacher?ungradedcount='+assessment_id,
               dataType: 'JSON',
               success: function(response){
                    if(response.error == 0){
                         $('#assessment_'+assessment_id+' .ungraded_col').text(response.data);
                    }
               }
          });
     }

     function deadline(course_id){
          global_popup_iframe('edit_deadline','/_/admin/yoda/courses/adjust-deadline?course='+course_id);
     }
     $(function() {
          var FILTER = null;

          $('#lltable').DataTable({
               columnDefs: [{
                    "orderable": false,
                    "targets": [0, 1, 2]
               }, ],
               "bPaginate": true,
               "pageLength": 50,
               "aaSorting": [
                    [2, 'desc']
               ],
          });

          $("#lltable").on('click', '.loadlog', function() {
               set_active_ll($(this));
               var assessment_id = $(this).data('id');
               var assessment_year = $(this).data('year');
               var $tbl = $('#sll_tbl').addClass('waiting');

               $.ajax({
                    'method': 'get',
                    url: '/_/teacher?assessment=' + assessment_id + '&sy=' + assessment_year,
                    dataType: 'json',
                    success: function(response) {
                         $rows = '';
                         $.each(response, function(key, value) {
                              var graded = value.grade != null;
                              var class_name = graded ? "list-group-item-success" : '';
                              class_name = value.reset ? (class_name + ' ' + 'zero-item') : class_name;

                              $rows += '<a class="list-group-item ssl-row ' + class_name + '" onclick="gradeLearningLog(' + value.said + ')"><span class="search-content">' + value.student + '</span> ' 
                              + (value.is_excused ? '<span class="badge badge-success">Excused</span>' : '') 
                              +(value.is_late ? '<span class="badge badge-danger">Late</span>' : '')
                              +(value.reset ? ' <span class="badge badge-danger">Needs Resubmit</span>' : '')
                              +(graded ? '<i class="fa fa-check-circle float-right green-500"></i>' : '') + '</a>'
                         });
                         $('#sll_tbl').html($rows);
                         $tbl.removeClass('waiting');

                         FILTER = new FilterSll(
                              $("#sll_tbl"),
                              $('#show-zeros-only'),
                              $('#ssl_search')
                         );

                         FILTER.execute();

                    }
               });
          }).on('click','.delete', function(){
               var id = $(this).data('id');
               LearningLog.delete(id);
          }).on('click','.replicate', function(){
               var id = $(this).data('id');
               LearningLog.replicate(id);
          });

          $('#show-zeros-only').change(function(){
               FILTER && FILTER.execute();
          });

          $('#ssl_search').keyup(function(){
               FILTER && FILTER.execute();
          });       

     });
</script>