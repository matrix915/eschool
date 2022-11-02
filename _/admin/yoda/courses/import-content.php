<?php

use mth\yoda\courses;
use mth\yoda\assessment;
use mth\yoda\questions;
use mth\yoda\plgs;


if (req_get::bool('upload')) {
  $import = $_FILES[req_get::txt('upload')];
  //$content = file_get_contents($import['tmp_name']);
  $spreadsheet = new core_spreadsheet($import['tmp_name']);
  echo json_encode(['error' => 0, 'data' => $spreadsheet->checkListToArray(), 'plg_type' => req_get::txt('upload')]);
  exit;
}

if (req_get::bool('create_plg')) {
  $grade_level = req_post::txt('grade_level');
  $success = 0;
  $is_independent = req_post::txt('plg_type') == 'independent_import';
  foreach ($_POST['subjects'] as $subject => $plgs) {
    foreach ($plgs as $plg_name) {
      $plg = new plgs();
      if ($plg->setInsert('grade_level', $is_independent?'INDEPENDENT':$grade_level)
        ->setInsert('plg_name', $plg_name)
        ->setInsert('school_year_id', req_post::int('school_year'))
        ->setInsert('subject', trim($subject))
        ->setInsert('plg_type', ($is_independent? plgs::PLG_TYPE_INDEPENDENT : plgs::PLG_TYPE_DEPENDENT))
        ->save()
      ) {
        $success++;
      }
    }
  }

  echo json_encode(['error' => 0, 'data' => $success]);
  exit;
}



if (!req_get::bool('course') || !($course = courses::getById(req_get::int('course')))) {
  die('Homeroom not found');
}

$llcount = assessment::getLearningLogCount($course->getCourseId());
$school_year = $course->getSchoolYear();

if (req_get::bool('deleteplg')) {
  $success = plgs::deleteByYear($school_year, $course->getCourseId());
  $error =  (int)  !$success;
  echo json_encode(['error' => $error, 'data' => $error ? 'Unable to delete PLG cache because there are still existing homerooms for the selected school year.' : '']);
  exit;
}

if (req_get::bool('import')) {
  $assessment = new assessment();
  $success = 0;
  $subjects = plgs::distictSubjects($school_year);
  $plg_type = plgs::getFirstType($school_year);
  $is_dependent = $plg_type == plgs::PLG_TYPE_DEPENDENT;

  foreach ($assessment->getByCourseId($course->getCourseId()) as $key => $log) {
    $order = questions::getQuestionCountByAssessment($log->getID());
    $_question = new questions();
    

    foreach ($subjects as $subject) {
      $title = "Please select any of the $subject " . ($is_dependent ? "Power Learning Goals " : "") . "you worked on this week.";

      $data = $is_dependent?$title:json_encode([
        'title' => $title,
        'checklist' => plgs::getCheclistBySubjectAndYear($subject,$school_year , true)
      ]);

      if ($_question
        ->setInsert('data',  $data)
        ->setInsert('yoda_teacher_asses_id', $log->getID())
        ->setInsert('type', ($is_dependent?questions::PLG:questions::PLG_INDEPENDENT))
        ->setInsert('number', $order + 1)
        ->setInsert('plg_subject', $subject)
        ->save()
      ) {
        $success++;
      } else {
        $success--;
      }
    }
  }
  echo json_encode(['error' => 0, 'data' => $success]);
  exit;
}

core_loader::isPopUp();
core_loader::addCssRef('fileLoader', '/_/mth_includes/jQuery-File-Upload-10.31.0/css/jquery.fileupload.css');
core_loader::includejQueryUI();
core_loader::printHeader();

$plg_count = plgs::getPLGCount($school_year);
?>
<div>
  <button type="button" class="btn btn-round btn-secondary float-right" onclick="closeLog()" title="Close">
    <i class="fa fa-close"></i>
  </button>
  <?php if ($llcount > 0) : ?>
    <?php if ($plg_count > 0) : ?>
    <button class="btn btn-primary btn-round" id="import-existing">Import Existing Checklist Below</button>
  <?php else : ?>
    <div class="alert alert-info alert-alt text-center">
      <h4>There is no checklist uploaded for <?= $school_year ?></h4>
      <span class="fileinput-button btn btn-primary btn-round">
        <span class="button">Upload <b>gradelevel-subject</b> learning log checklist (*.xlxs)</span>
        <input type="file" name="dependent_import" id="importchecklist">
      </span>
      <br>
      or
      <br>
      <span class="fileinput-button btn btn-pink btn-round">
        <span class="button">Upload independent learning log checklist (*.xlxs)</span>
        <input type="file" name="independent_import" id="importchecklist1">
      </span>
    </div>
  <?php endif; ?>
<?php else : ?>
  <div class="alert alert-danger">Please create learning log(s) for this homeroom first before importing PLG(s).</div>
<?php endif; ?>
<?php if ($plg_count > 0) : ?>
  <button class="btn btn-danger btn-round" onclick="deletePLG()">DELETE PLG list</button>
<?php endif; ?>

</div>
<div class="mt-20">
  <span id="create_plg_label" style="display:none">Creating PLGS..</span>
  <div id="importchecklist_progress" class="progress progress-xs mt-10" style="display: none;">
    <div class="upload_progress-bar progress-bar progress-bar-warning progress-bar-indicating active" style="width: 0%;" role="progressbar">
    </div>
  </div>
</div>
<span id="import_plg_label" style="display:none">Importing PLGS to Homeroom..</span>

<?php if ($plg_count > 0) : ?>
  <table class="table mt-10">
    <thead>
      <th></th>
      <th>Subject</th>
      <th>PLG</th>
    </thead>
    <tbody>
      <?php foreach (plgs::getPLGs($school_year) as $_plg) : ?>
        <tr>
          <td><?= $_plg->getGradeLevel() ?></td>
          <td><?= $_plg->getSubject() ?></td>
          <td><?= $_plg->getName() ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>


<?php
core_loader::addJsRef('fileUploaderTransport', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.iframe-transport.js');
core_loader::addJsRef('fileUploader', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.fileupload.js');
core_loader::printFooter();
?>
<script>
  var course = <?= $course->getCourseId() ?>;
  var llcount = <?= $llcount ?>;
  checklist = {};
  var import_success = 0;
  var selected_homeroom = null;
  var selected_gradelevels = [];
  var subject_array = [];
  var current_subject_index = 0;
  var gradelevel_array = [];
  var current_gradelevel_index = 0;
  var $create_plg_label = $('#create_plg_label');
  var $import_plg_label = $('#import_plg_label');

  function closeLog() {
    parent.global_popup_iframe_close('import_popup');
  }

  function getSubjectId(subject) {
    return subject.split(' ').join('');
  }

  function reset() {
    import_success = 0;
    global_waiting_hide();
  }

  function add_selected(grade_level) {
    selected_gradelevels
  }

  function deletePLG() {
    swal({
      title: '',
      text: "Are you sure you want to delete PLG record below? Please note that by deleting the record below may affect another homeroom that has PLG on it. And you may end up duplicating PLG questions for the homeroom when you do an import again.",
      type: "warning",
      showCancelButton: true,
      confirmButtonClass: "btn-warning",
      confirmButtonText: "Yes, Delete",
      cancelButtonText: "Cancel",
      closeOnConfirm: true,
      closeOnCancel: true
    }, function() {
      _deletePLG();
    });
  }

  function _deletePLG() {
    $.ajax({
      url: '?deleteplg=1&course=' + course,
      dataType: 'json',
      success: function(response) {
        if (response.error == 1) {
          setTimeout(function() {
            swal('', response.data, 'error');
          }, 100);

        } else {
          location.reload();
        }
      },
      error: function() {
        swal('', 'Error Deleting PLG', 'error');
      }
    });
  }

  function create_plg(plg_type) {
    var current_grade_level = gradelevel_array[current_gradelevel_index];
    var gradelevel = checklist[current_grade_level];

    $.ajax({
      url: '?create_plg=1',
      type: 'POST',
      dataType: 'json',
      data: {
        subjects: gradelevel,
        grade_level: current_grade_level,
        plg_type: plg_type,
        school_year: <?= $school_year->getID() ?>
      },
      success: function(response) {
        if (Object.keys(checklist).length == (current_gradelevel_index + 1)) {
          current_gradelevel_index = 0;
          $create_plg_label.html('<div style="color:#4caf50"><i class="fa fa-check"></i> <b>PLG List Uploaded</b></div>');
          $import_plg_label.fadeIn();
          importCL();
        } else {
          current_gradelevel_index += 1;
          create_plg(plg_type);
        }
      },
      error: function() {
        console.log('Import checklist error.');
      }
    });

  }

  function importCL() {
    $.ajax({
      url: '?import=1&course=' + course,
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        $import_plg_label.html('<div style="color:#4caf50"><i class="fa fa-check"></i> <b>PLG Imported to Homeroom</b></div>');
        global_waiting_hide();
      },
      error: function() {
        swal('', 'Import checklist error.', 'error');
      }
    });
  }

  function createTable(grade_level, gradelevel_object) {
    $tbl = '<b>' + grade_level + '</b><table class="table">';
    $tbl += '<thead><th>Subject</th><th>PLG Count</th></thead><tbody>'
    for (subject in gradelevel_object) {
      $tbl += '<tr><td>' + subject + '</td><td>' + gradelevel_object[subject].length + '</td></tr>'
    }
    $tbl += '</tbody></table>';
    return $tbl;
  }

  function initUpload($input_file, url) {
    return $input_file.fileupload({
      url: url,
      dataType: 'text',
      //20000000 bytes = 20mb
      maxFileSize: 20000000,
      done: function(e, data) {
        var response = $.parseJSON(data.result);
        if (response.error == 0) {
          checklist = response.data;
          gradelevel_array = Object.getOwnPropertyNames(checklist);
          $create_plg_label.fadeIn();
          create_plg(response.plg_type);

        } else {
          top.swal('Import Error', response.data);
        }

        $import_progress.hide();
        $import_progress.find('.upload_progress-bar').css(
          'width',
          '0%'
        );
      },
      add: function(e, data) {
        global_waiting();

        if (data.originalFiles[0].size > 20000000) {
          top.swal('Upload Error', 'Uploaded file exceeds 20MB', 'error');
        } else {
          data.submit();
        }
      },
      progressall: function(e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        $import_progress.show();
        $import_progress.find('.upload_progress-bar').css(
          'width',
          progress + '%'
        );
        if (progress >= 100) {
          console.log('complete');
        }
      }
    });
  }

  $(function() {

    $import = $('#importchecklist');
    $independent_import = $('#importchecklist1');
    $import_progress = $('#importchecklist_progress');
    $import_existing = $('#import-existing');

    initUpload($import, '?upload=dependent_import');
    initUpload($independent_import, '?upload=independent_import');

    $import_existing.click(function() {
      $import_plg_label.fadeIn();
      importCL();
    });

  });
</script>