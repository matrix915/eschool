<?php

use mth\yoda\courses;
use mth\yoda\assessment;
use mth\yoda\studentassessment;
use function GuzzleHttp\json_encode;

core_user::getUserLevel() || core_secure::loadLogin();

if (req_get::bool('assessment')) {
     $response = [];
     $teacher_assessment = assessment::getById(req_get::int('assessment'));
     $YEAR = mth_schoolYear::getByID(req_get::int('sy'));
     foreach (studentassessment::getSubmittedByAssessmentId(req_get::int('assessment'), req_get::int('sy')) as $log) {
          if ($log->getPerson()) {

               $response[] = [
                    'student' => $log->getPerson()->getName(),
                    'is_late' => $log->isLate(),
                    'grade' => $log->getGrade(),
                    'said' => $log->getID(),
                    'is_excused' => $log->isExcused(),
                    'reset' => $log->isReset()
               ];
          }
     }
     echo json_encode($response);
     exit;
}

if (req_get::bool('ungradedcount')) {
     $success = false;
     $count = 0;
     if ($assessment = assessment::getById(req_get::int('ungradedcount'))) {
          $count = $assessment->getUngradedCount();
          $success = true;
     }
     echo json_encode(['error' => (int) !$success, 'data' => $count]);
     exit;
}

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Homeroom');
cms_page::setPageContent('');
core_loader::printHeader('teacher');

$current_course_id = req_get::bool('course_id') ? req_get::int('course_id') : null;

?>
<style>
     .graded {
          display: none;
     }

     .active-row {
          background-color: #ffc107;
     }

     .fl-hidden {
          display: none;
     }
</style>
<?php if ($_courses = courses::getTeacherHomerooms(core_user::getCurrentUser())) : ?>
     <div class="form-group">
          <div class="input-group">
               <span class="input-group-addon">
                    <span class="checkbox-custom checkbox-primary">
                         <input type="checkbox" id="showprev" name="showprev">
                         <label for="showprev"></label>
                    </span>
                    &nbsp;Show Previous Year
               </span>
               <select class="form-control" id="course_control">
                    <?php foreach ($_courses  as $homeroom) : ?>
                         <option <?= $homeroom->getCourseId() == $current_course_id ? 'SELECTED' : '' ?> data-sy="<?= $homeroom->getSchoolYearId() ?>" value="<?= $homeroom->getCourseId() ?>">
                              <?= $homeroom->getName() ?> (<?= $homeroom->getSchoolYear() ?>)
                         </option>
                    <?php endforeach; ?>
               </select>
          </div>
     </div>
     <?php
          $assessment = new assessment();
          $active_course = is_null($current_course_id) && isset($_courses[0]) ? $_courses[0] : courses::getById($current_course_id);
          ?>
     <div class="row">
          <div class="col-md-6">
               <div class="panel panel-primary">
                    <div class="panel-heading">
                         <h4 class="panel-title">
                              Learning Logs
                         </h4>
                    </div>
                    <div class="panel-body pb-5">
                         <div class="checkbox-custom checkbox-primary">
                              <input type="checkbox" id="logswitch">
                              <label>Show All</label>
                         </div>
                    </div>
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
                                             ?>
                                   <tr class="<?= $ungraded > 0 ? 'undgraded' : 'graded' ?>" id="assessment_<?= $log->getID() ?>">
                                        <td><?= $log->getTitle() ?></td>
                                        <td><?= $log->getDeadline('M j') ?></td>
                                        <td>
                                             <button class="btn" title="View view-log" onclick="global_popup_iframe('yoda_assessment_view','/_/teacher/log?log=<?= $log->getID() ?>')"><i class="fa fa-search"></i></button>
                                             <button type="button" class="btn btn-primary loadlog" title="Load" data-id="<?= $log->getID() ?>">
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
<?php else : ?>
     <div class="alert alert-alt alert-primary">
          No Homeroom assigned
     </div>
<?php endif ?>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('filterljs', '/_/teacher/filterlog.js');
core_loader::printFooter('admin');
?>
<script>
     $(function() {
          function set_active_ll($this) {
               $('#lltable tr.active-row').removeClass('active-row');
               $this.closest('tr').addClass('active-row');
          }

          var FILTER = null;

          $('#logswitch').change(function() {
               if ($(this).is(':checked')) {
                    $('.graded').fadeIn();
               } else {
                    $('.graded').fadeOut();
               }
          });

          function showPrev() {
               if ($('#showprev').is(':checked')) {
                    $('#course_control').children('option:hidden').show();
               } else {
                    $('#course_control').children('option:not([data-sy="<?= mth_schoolYear::getCurrent()->getID() ?>"])').hide();
               }
          }

          showPrev();

          $('#showprev').change(function() {
               showPrev();
          });

          $("#lltable").on('click', '.loadlog', function() {
               set_active_ll($(this));
               var sy = $('#course_control').find(':selected').data('sy');
               var assessment_id = $(this).data('id');
               var $tbl = $('#sll_tbl').addClass('waiting');
               $.ajax({
                    'method': 'get',
                    url: '?assessment=' + assessment_id + '&sy=' + sy,
                    dataType: 'json',
                    success: function(response) {
                         $rows = '';
                         $.each(response, function(key, value) {
                              var graded = value.grade != null;
                              var class_name = graded ? "list-group-item-success" : '';
                              class_name = value.reset ? (class_name + ' ' + 'zero-item') : class_name;

                              $rows += '<a class="list-group-item ssl-row ' + class_name + '" onclick="gradeLearningLog(' + value.said + ')"><span class="search-content">' + value.student + '</span> ' + (value.is_late ? '<span class="badge badge-danger">Late</span> ' : '') +
                                   (value.reset ? '<span class="badge badge-danger">Needs Resubmit</span> ' : '') +
                                   (graded ? '<i class="fa fa-check-circle float-right green-500"></i>' : '') + '</a>'
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
          });



          $('#show-zeros-only').change(function() {
               FILTER && FILTER.execute();
          });

          $('#ssl_search').keyup(function() {
               FILTER && FILTER.execute();
          });

          $('#course_control').change(function() {
               var course = $(this).val();
               location.href = "?course_id=" + course;
          });
     });

     function gradeLearningLog(id) {
          global_popup_iframe('yoda_assessment_edit', '/_/teacher/log/grade?id=' + id);
     }

     function updateActiveLog(log) {
          getUngradedCount(log);
          $('.loadlog[data-id=' + log + ']').trigger('click');
     }

     function getUngradedCount(assessment_id) {
          $.ajax({
               url: '?ungradedcount=' + assessment_id,
               dataType: 'JSON',
               success: function(response) {
                    if (response.error == 0) {
                         $('#assessment_' + assessment_id + ' .ungraded_col').text(response.data);
                    }
               }
          });
     }
</script>