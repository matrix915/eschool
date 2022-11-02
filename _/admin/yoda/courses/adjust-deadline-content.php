<?php

use mth\yoda\assessment;
use mth\yoda\courses;


if (!req_get::bool('course') || !($course = courses::getById(req_get::int('course')))) {
     die('course not found');
}

if (req_get::bool('form')) {
     $assessment = new assessment();
     $format = 'Y-m-d H:i:s';
     $first_deadline = date($format, strtotime(req_post::txt('deadline_date') . ' ' . req_post::txt('deadline_time')));
     $current_deadline = null;
     foreach ($assessment->getByCourseId($course->getCourseId()) as $key => $log) {
          $current_deadline = !$current_deadline ? $first_deadline : assessment::adjustDeadline($current_deadline);
          $log->set('deadline', $current_deadline)->save();
     }
     core_notify::addMessage('Deadlines modified. Please check.');

     exit('<!DOCTYPE html><html><script>
     parent.location.reload();
    </script></html>');
}

core_loader::isPopUp();
core_loader::addCssRef('timecss', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.css');
core_loader::printHeader();
core_loader::includejQueryUI();
?>
<form id="adjustform" method="post" action='?form=<?= uniqid('yoda-form-adjust') ?><?= '&course=' . $course->getCourseId() ?>'>
     <div class="card">
          <div class="card-block">
               <div class="alert alert-warning alert-alt"><b>Adjust homeroom deadlines</b>. By executing this process it will adjust all learning log's deadline from this homeroom base on first deadline.</div>
               <div class="form-group">
                    <label>First Deadline</label>
                    <div class='input-group date'>
                         <input type='text' class="form-control" autocomplete="off" name="deadline_date" id='deadline_d' required />
                         <input type='text' class="form-control" autocomplete="off" name="deadline_time" id='deadline_t' required />
                         <span class="input-group-addon">
                              <span class="fa fa-calendar"></span>
                         </span>
                    </div>
                    <label class="error" for="deadline_d"></label>
                    <label class="error" for="deadline_t"></label>
               </div>
          </div>
          <div class="card-footer">
               <button class="btn btn-round btn-primary" onclick="submit_a()" type="button">Save</button>
               <button type="button" class="btn btn-round btn-secondary" onclick="closeAdjust()">
                    <i class="fa fa-close"></i> Close
               </button>
          </div>
     </div>
</form>
<?php
core_loader::addJsRef('momentjs', core_config::getThemeURI() . '/vendor/calendar/moment.min.js');
core_loader::addJsRef('calendarjs', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js');

core_loader::addJsRef('timepickercdn', "https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.js");
core_loader::includejQueryValidate();
core_loader::printFooter();
?>
<script>
     function closeAdjust() {
          parent.global_popup_iframe_close('edit_deadline');
     }

     function submit_a() {
          swal({
                    title: "",
                    text: "Are you sure you want to overwrite <?= $course->getName() ?>'s learning log deadlines.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-warning",
                    confirmButtonText: "Yes, Overwrite",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: true,
                    closeOnCancel: true
               },
               function() {
                    $form.submit();
               });
     }

     $(function() {
          $('#deadline_d').datepicker();
          $('#deadline_t').timepicker({
               'step': function(i) {
                    return (i != 48) ? 30 : 29;
               }
          });
          $form = $('#adjustform');
          $form.validate();
     });
</script>