<?php

use mth\yoda\courses;
use mth\yoda\studentassessment;
use mth\yoda\assessment;

core_user::getUserLevel() || core_secure::loadLogin();

$student = req_get::bool('st') ? mth_student::getByStudentID(req_get::int('st')) : null;
$homeroom = req_get::bool('hr') ? courses::getById(req_get::int('hr')) : null;

if (!$student || !$homeroom) {
     die('Unable to find student');
}

$studentPersonID = $student->getPersonID();

if (req_get::bool('bulkna')) {
     $success = 0;
     foreach (req_post::int_array('log') as $logId) {
          if(($student_assessment = studentassessment::get($logId, $studentPersonID)))
          {
              $student_assessment->set('reset', studentassessment::NA);
          } else
          {
              $student_assessment = new studentassessment();
              $student_assessment->setInsert('person_id', $studentPersonID);
              $student_assessment->setInsert('assessment_id', $logId);
              $student_assessment->setInsert('reset', studentassessment::NA);
          }

          if ($student_assessment->save()) {
               $success++;
          }
     }
     $logcount = count(req_post::int_array('log'));
     $allsuccess = $logcount == $success;

     if ($allsuccess) {
          core_notify::addMessage('Learning Log(s) successfully marked as NA');
     }

     echo json_encode(
          [
               'error' => $allsuccess ? 0 : 1,
               'data' => $allsuccess ? 'Learning Log(s) successfully marked as NA' : ('There are ' . $logcount - $success . ' unsuccessful process.')
          ]
     );
     exit();
}

if (req_get::bool('excuse')) {
    $logId = req_get::int('excuse');

    if(($student_assessment = studentassessment::get($logId, $studentPersonID)))
    {
        $student_assessment->set('excused', 1);
    } else
    {
        $student_assessment = new studentassessment();
        $student_assessment->setInsert('person_id', $studentPersonID);
        $student_assessment->setInsert('assessment_id', $logId);
        $student_assessment->setInsert('excused', 1);
    }

     if ($student_assessment->save()) {
          core_notify::addMessage('Learning Log marked as Excused');
     } else {
          core_notify::addError('Unable to mark Learning Log as Excused');
     }
     core_loader::redirect('?st=' . $student->getID() . '&hr=' . $homeroom->getCourseId());
}

if (req_get::bool('na')) {
    $logId = req_get::int('na');
    if(($student_assessment = studentassessment::get($logId, $studentPersonID)))
    {
        $student_assessment->set('reset', studentassessment::NA);
    }else
    {
        $student_assessment = new studentassessment();
        $student_assessment->setInsert('person_id', $studentPersonID);
        $student_assessment->setInsert('assessment_id', $logId);
        $student_assessment->setInsert('reset', studentassessment::NA);
    }
     if ($student_assessment->save()) {
          core_notify::addMessage('Learning Log marked as N/A');
     } else {
          core_notify::addError('Unable to mark Learning Log as N/A');
     }
     core_loader::redirect('?st=' . $student->getID() . '&hr=' . $homeroom->getCourseId());
}

core_loader::isPopUp();
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader();

$assesment = new assessment();
$logs = $assesment->getStudentLearningLogs($student, $homeroom->getSchoolYear());
?>
<style>
     .Submitted_status,
     .Resubmitted_status {
          color: #28a745 !important;
     }


     .ResubmitNeeded_status {
          color: red !important;
     }
</style>
<div class="log-header">
     <button type="button" class="float-right btn btn-round btn-default" onclick="closeLog()">
          <i class="fa fa-close"></i>
     </button>
     <?php
        $studentUser = core_user::getUserById($student->getUserID());
     ?>
     <h4 class="d-flex justify-content-start align-items-center">
         <a class="avatar avatar-lg avatar-cont ml-10" style="height:50px; background-image:url(<?= $studentUser
         && $studentUser->getAvatar()
             ? $studentUser->getAvatar() : (core_config::getThemeURI() . '/assets/portraits/default.png') ?>)">
         </a>
         <div class="ml-10 mt-5">
             <span class="blue-500"><?= $student ?>'s</span> Learning Logs
             <h5 class="mt-10 d-flex justify-content-start">
               <?= $student->getGradeLevel(true) . ' (' . $student->getAge() . ')' ?>
             </h5>
         </div>
     </h4>
</div>
<div class="row" style="margin-top: 90px;">
     <div class="col-md-12">
          <div class="card">
               <div class="card-header">
                    <div class="checkbox-custom checkbox-primary" style="display:inline;margin-right:20px;">
                         <input type="checkbox" id="hidesubmitted">
                         <label for="hidesubmitted">Hide Submitted Logs</label>
                    </div>
                    <div class="checkbox-custom checkbox-primary" style="display:inline">
                         <input type="checkbox" id="hidegraded">
                         <label for="hidegraded">Hide Graded Logs</label>
                    </div>
                    |
                    <button class="btn btn-secondary btn-sm" type="button" id="bulkna">
                         NA
                    </button>
               </div>
               <div class="card-block pl-0 pr-0">
                    <table class="table responsive" id="logstable">
                         <thead>
                              <th></th>
                              <th>Learning Log</th>
                              <th>Status</th>
                              <th>Due</th>
                              <th>Grade</th>
                              <th></th>
                         </thead>
                         <tbody>
                              <?php
                              $YEAR = $homeroom->getSchoolYear();
                              $gradesum = 0;
                              $assessmentcount = 0;
                              foreach ($logs as $key => $log) :
                                   $assessmentcount++;
                                   $student_assessment = studentassessment::get($log->getID(), $studentPersonID);
                                   $grade = $student_assessment ? $student_assessment->getGrade() : null;
                                   $status = $student_assessment ? $student_assessment->getStatus() : 'Not Submitted';
                                   $issubmitted =  $student_assessment && $student_assessment->isSubmitted();
                                   $draft =  $student_assessment && $student_assessment->isDraft() ? ' (Draft)' : '';
                                   $isexcused = $student_assessment && $student_assessment->isExcused();
                                   $isna = $student_assessment && $student_assessment->isNA();
                                   $is_resubmit_needed = $student_assessment && $student_assessment->isReset();
                                   $txt_status  = $status;

                                   $is_graded = $grade != null;
                                   $excempt_graded = $is_graded || $isexcused || $isna;
                                   $grade_str =   $isexcused || $isna ? 'N/A' : ($is_graded ? $grade . '%' : '');

                                   if (!$isexcused && $issubmitted) {
                                        if ($grade != null) {
                                             $txt_status = 'Graded';
                                        } else {
                                             $txt_status = $status . ' - ' . $student_assessment->getSubmittedDate('j M Y \a\t h:i A');
                                        }
                                   }

                                   $status_string = $txt_status . $draft;
                                   ?>
                                   <tr class="<?= $issubmitted ? 'll_submitted' : 'll_unsubmitted' ?><?= $excempt_graded && !$is_resubmit_needed ? ' ll_graded' : '' ?>">
                                        <td>
                                             <div class="checkbox-custom checkbox-primary"><input type="checkbox" name="log[]" class="ll_cb" value="<?= $log->getID() ?>" /><label></label></div>
                                        </td>
                                        <td>
                                             <?= $log->getTitle() ?>
                                        </td>
                                        <td>
                                             <?php if ($log->isEditable()) : ?>
                                                  <span class="logstatus <?= str_replace(' ', '', $status) ?>_status">
                                                       <?= $status_string ?>
                                                  </span>
                                             <?php else : ?>
                                                  <?= $status_string ?>
                                             <?php endif; ?>
                                             <?php if ($student_assessment && $student_assessment->isLate() && !$isexcused) : ?>
                                                  / <span class="badge badge-round badge-danger">Late</span>
                                             <?php endif; ?>
                                        </td>
                                        <td><?= $log->getDeadline('j M Y'); ?></td>
                                        <td><?= $grade_str ?></td>
                                        <td>
                                             <?php if ($student_assessment && $student_assessment->isSubmitted()) : ?>
                                                  <button class="btn btn-success" onclick="global_popup_iframe('yoda_assessment_edit','/_/teacher/log/grade?id=<?= $student_assessment->getID() ?>&single=1');">
                                                       Grade
                                                  </button>
                                             <?php endif; ?>
                                             <?php if (core_user::getCurrentUser()->isAdmin()) : ?>
                                                  <button class="btn btn-warning" onclick="excuse(<?= $log->getID() ?>)" type="button">
                                                       Excuse
                                                  </button>
                                             <?php endif; ?>
                                             <button class="btn btn-secondary" onclick="na(<?= $log->getID() ?>)" type="button">
                                                  NA
                                             </button>
                                        </td>
                                   </tr>
                              <?php
                              endforeach;
                              ?>
                         </tbody>
                    </table>
               </div>
          </div>
     </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>
<script>
     var student = <?= $student->getID() ?>;
     var hr = <?= $homeroom->getCourseId() ?>;

     function closeLog() {
          parent.global_popup_iframe_close('mth_student_learning_logs');
     }

     function updateActiveLog() {
          location.reload();
     }

     function excuse(assessment) {
          swal({
               title: "",
               text: "Are you sure you want to mark this learning log as Excused?",
               type: "warning",
               showCancelButton: true,
               confirmButtonClass: "btn-primary",
               confirmButtonText: "Yes",
               cancelButtonText: "Cancel",
               closeOnConfirm: true,
               closeOnCancel: true
          }, function() {
               location.href = '?excuse=' + assessment + '&st=<?= $student->getID() ?>&hr=<?= $homeroom->getCourseId() ?>';
          });
     }

     function na(assessment) {
          swal({
               title: "",
               text: "Are you sure you want to mark this learning log as N/A?",
               type: "warning",
               showCancelButton: true,
               confirmButtonClass: "btn-primary",
               confirmButtonText: "Yes",
               cancelButtonText: "Cancel",
               closeOnConfirm: true,
               closeOnCancel: true
          }, function() {
               location.href = '?na=' + assessment + '&st=<?= $student->getID() ?>&hr=<?= $homeroom->getCourseId() ?>';
          });
     }

     function _getHideSubmitted() {
          var hide_submitted = localStorage.getItem('hide_submitted') ?
               localStorage.getItem('hide_submitted') : 0;
          return Boolean(hide_submitted * 1);
     }

     function _getHideGraded() {
          var hide_graded = localStorage.getItem('hide_graded') ?
               localStorage.getItem('hide_graded') : 0;
          return Boolean(hide_graded * 1);
     }

     $(function() {
          dttable = $('#logstable').DataTable({
               columnDefs: [{
                    type: 'dateNonStandard',
                    targets: 3
               }, {
                    orderable: false,
                    targets: [0, -1]
               }],
               "bPaginate": true,
               "aaSorting": [
                    [3, 'desc']
               ],
               "pageLength": 10
          });

          function show_rows() {
               var hide_submitted = $('#hidesubmitted').is(':checked');
               var hide_graded = $('#hidegraded').is(':checked');

               show_all();
               $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                         var $tr = $(dttable.row(dataIndex).node());
                         if (hide_submitted) {
                              return $tr.hasClass('ll_unsubmitted');
                         }

                         if (hide_graded) {
                              return !$tr.hasClass('ll_graded');
                         }

                         return true;
                    }
               );

               dttable.draw();
          }

          function show_all() {
               $.fn.dataTable.ext.search.pop();
               dttable.draw();
          }

          function bulk_na(stringparam) {
               $.ajax({
                    url: '?bulkna=1&st=' + student + '&hr=' + hr,
                    type: 'POST',
                    data: stringparam,
                    dataType: 'JSON',
                    success: function(response) {
                         if (response.error == 1) {
                              toastr.error('There is an error marking logs to NA');
                         } else {
                              document.location.reload();
                         }
                    },
                    error: function() {
                         toastr.error('There is an error marking logs to NA');
                    }
               });
          }

          $('#hidesubmitted').prop('checked', _getHideSubmitted()).change(function() {
               var isChecked = $(this).is(':checked');
               localStorage.setItem('hide_submitted', isChecked ? 1 : 0);
               show_rows();
          });

          $('#hidegraded').prop('checked', _getHideGraded()).change(function() {
               var isChecked = $(this).is(':checked');
               localStorage.setItem('hide_graded', isChecked ? 1 : 0);
               show_rows();
          });

          $('#bulkna').click(function() {
               if ($('.ll_cb:checked').length == 0) {
                    toastr.warning('Please select at least 1 learning log');
                    return false;
               }

               swal({
                    title: "",
                    text: "Are you sure you want to mark learning log(s) as N/A?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-primary",
                    confirmButtonText: "Yes",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: true,
                    closeOnCancel: true
               }, function() {
                    bulk_na($('.ll_cb:checked').serialize());
               });
               return false;

          });

          show_rows();
     });
</script>