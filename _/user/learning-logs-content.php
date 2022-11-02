<?php

use mth\yoda\assessment;
use mth\yoda\questions;
use mth\yoda\answers;
use mth\yoda\studentassessment;
use mth\yoda\answersfile;
use mth\yoda\plgs;

if (isset($_GET['upload'])) {
  echo json_encode(answersfile::saveUploadedFiles(['attachment']));
  exit;
}

if (req_get::bool('deletefile')) {
  $deleted = ['error' => 1, 'data' => 'File not found'];

  if (($file = mth_file::getByHash(req_get::txt('deletefile'))) && $file->delete()) {
    if ($delete = answersfile::delete($file->id())) {
      $deleted = ['error' => 0, 'data' => 'Deleted'];
    } else {
      $deleted = ['error' => 1, 'data' => 'Error deleting file'];
    }
  }
  echo  json_encode($deleted);
  exit();
}

if (req_get::bool('select_plg')) {
  $grade_level = req_post::txt('grade_level');
  $subject = req_post::txt('subject');
  $school_year = req_post::int('school_year');
  $question_id = req_post::int('question_id');
  foreach (plgs::get($grade_level, $subject, $school_year) as $plg) {
    echo '<div class="checkbox-custom checkbox-primary"><input type="checkbox" class="plgname" name="question[' . $question_id . '][answer][]" value="' . $plg->getName() . '"><label>' . $plg->getName() . '</label></div>';
  }
  exit();
}

($student = mth_student::getByStudentID($_GET['student'])) || die('Student not found');
$YEAR = req_get::bool('y') ? mth_schoolYear::getByStartYear(req_get::int('y')) : mth_schoolYear::getCurrent();
$is_archive = req_get::bool('archive');

(mth_schedule::get($student, $YEAR, $is_archive)) || die('Schedule not found');

if (!$student->isEditable()) {
  core_notify::addError('Student Not Found');
  header('Location: /home');
  exit();
}

$_log = isset($_GET['log']) ? $_GET['log'] : null;

$schedule = $student->schedule($YEAR);

$assesment = new assessment();
$questions = new questions();
$studentassessment = new studentassessment();

$logs = $is_archive ? $assesment->getByCourseId(req_get::int('archive')) : $assesment->getStudentLearningLogs($student, $YEAR);
$active_id = !is_null($_log) ? $_log : ($logs ? $logs[0]->getID() : null);
$active_log = assessment::getById($active_id);

function createAssessment(&$studentassessment, $student, $active_id, $islate, $saveforlater)
{

  $studentassessment->setInsert('person_id', $student->getPersonID());
  $studentassessment->setInsert('assessment_id', $active_id);

  if ($saveforlater) {
    $studentassessment->setInsert('draft', 1);
  } else {
    $studentassessment->setInsert('draft');
    $studentassessment->setInsert('is_late', $islate);
  }
}

if (!empty($_GET['form'])) {
  core_loader::formSubmitable($_GET['form']) || die('Form is not submittable');

  if (!isset($_POST['question'])) {
    die('No questions');
  }

  $empty_answers = 0;

  foreach ($_POST['question'] as $_question) {
    $_answer = isset($_question['answer']) ? $_question['answer'] : null;
    if (!$_answer) {
      $empty_answers++;
    }
  }

  if (!is_array($_POST['question']) || $empty_answers == count($_POST['question'])) {
    die('Empty submission');
  }

  if (!$active_log->validForSubmission()) {
    core_notify::addError('You are not allowed to submit this learning log');
  } else {
    $success = true;
    $islate = $active_log->isDue() ? 1 : 0;
    $saveforlater = req_post::bool('save');


    if (req_post::int('student_assessment_id') != 0) {
      $studentassessment = studentassessment::getById(req_post::int('student_assessment_id'));

      if ($saveforlater) {
        $studentassessment->set('draft', 1);
      } else {
        if ($studentassessment->isDraft()) {
          $studentassessment->set('created_at', date('Y-m-d H:i:s'));
        }

        // $studentassessment->set('draft');
        $studentassessment->set('is_late', $islate);
        $need_to_resubmit = $studentassessment->isReset();

        //create new entry when
        if ($studentassessment->save()) {
          $studentassessment = new studentassessment();
          $studentassessment->setInsert('reset', $need_to_resubmit ? studentassessment::RESUBMITTED : null);
          createAssessment($studentassessment, $student, $active_id, $islate, $saveforlater);
        }
      }
    } else {
      createAssessment($studentassessment, $student, $active_id, $islate, $saveforlater);
    }


    if ($studentassessment->save()) {

      if (req_post::int('student_assessment_id') != 0 && ($studentassessment->isDraft() || $saveforlater)) {
        answers::delete(req_post::int('student_assessment_id'));
      }

      if (isset($_POST['question'])) {
        foreach ($_POST['question'] as $id => $question) {
          $answers = new answers();
          $_answer = isset($question['answer']) ? $question['answer'] : [];
          $_gradelevel = isset($question['grade_level']) ? $question['grade_level'] : 0;
          $answers->setInsert('yoda_student_asses_id', $studentassessment->getID());
          $answers->setInsert('yoda_assessment_question_id', $id);
          $answers->setInsert('type', $question['type']);
          $answers->setInsert('data', json_encode(['answer' => $_answer, 'grade_level' => $_gradelevel]));
          $sucess = $success && $answers->save();
        }
      }


      if (req_post::bool('file_ids')) {
        if (!($assign = answersfile::assignToAnswer($_POST['file_ids'], $studentassessment->getID()))) {
          error_log('Unable to Assign To Answer');
        }
      }
    } else {
      $success = false;
    }

    if ($success) {
      if ($saveforlater) {
        core_notify::addMessage('Your Weekly Learning Log has been saved as a DRAFT. Be sure to return later to finish it.');
      } else {
        core_notify::addMessage('Well done!  The Learning Log has been successfully submitted to the Homeroom teacher!');
      }

      exit('<!DOCTYPE html><html><script>
            
            if(parent.document.querySelector(".teacher_assistant")){
                location.href = "?student=' . $student->getID() . '";
            }else{
                parent.global_popup_iframe_close("mth_student_learning_logs");
                parent.location.reload();
            }
            </script></html>');
    } else {
      core_notify::addError('There is an error submitting learning log');
    }
  }



  core_loader::redirect('?student=' . $student->getID() . '&log=' . $active_id);
  exit();
} else {
  $answers = new answers();
}


$student_assessment = $studentassessment->get($active_id, $student->getPersonID());


core_loader::includejQueryUI();
core_loader::addCssRef('fileLoader', '/_/mth_includes/jQuery-File-Upload-10.31.0/css/jquery.fileupload.css');
core_loader::addCssRef('iosstyle', core_config::getThemeURI() . '/assets/css/ios.css');

core_loader::isPopUp();
core_loader::printHeader();


?>
<style>
  .log-header {
    background: rgba(255, 255, 255, 0.9);
    width: 100%;
    border-bottom: 2px solid #2196f3;
    padding: 10px;
    text-align: center;
  }

  .log-header {
    position: fixed;
    z-index: 99;
    background: rgba(255, 255, 255, 0.9);
    width: 100%;
    margin-top: -30px;
    border-bottom: 2px solid #2196f3;
    padding: 10px;
    text-align: center;
    left: 0px;
  }

  @media (max-width:400px) {
    .log-header {
      margin: 0px !important;
      top: 0px;

    }
  }

  .fileuploaded {
    background: #ccc;
    padding: 4px 8px;
    border-radius: 15px;
    margin-right: 4px;
    display: inline-block;
    margin-bottom: 10px;
  }

  .answered.checkbox-custom label::before {
    background-color: #ccc;
    border: 1px solid #9E9E9E;
  }
</style>
<script>
  function closeLog() {
    var isDraft = <?= $student_assessment && $student_assessment->isDraft() && !core_user::isUserAdmin() ? 1 : 0 ?>;
    if (isDraft) {
      top.swal({
          title: "",
          text: "Are you sure you want to exit without submitting the Learning Log?",
          type: "warning",
          showCancelButton: true,
          confirmButtonClass: "btn-warning",
          confirmButtonText: "Yes, I will submit it later.",
          cancelButtonText: "Return to Log",
          closeOnConfirm: true,
          closeOnCancel: true
        },
        function() {
          parent.global_popup_iframe_close('mth_student_learning_logs');
        });
    } else {
      parent.global_popup_iframe_close('mth_student_learning_logs');
    }
    // if($(parent.document.body).is('#mth_people_edit')){

    // }
  }
</script>
<div class="log-header">
  <button type="button" class="float-right btn btn-round btn-default" onclick="closeLog()">
    <i class="fa fa-close"></i>
  </button>
    <?php
    $student_canvas = mth_canvas_user::get($student);
    ?>
    <h4 class="d-flex justify-content-start align-items-center">
        <a class="avatar avatar-lg avatar-cont" style="height:50px;background-image:url(<?= $student_canvas && $student_canvas->avatar_url() ? $student_canvas->avatar_url() : (core_config::getThemeURI() . '/assets/portraits/default.png') ?>)">
        </a>
        <div class="ml-10 mt-5">
            <span class="mr-10 blue-500"><?= $student ?>'s</span> Learning Logs
            <h5 class="mt-0 d-flex justify-content-start"><?= $student->getGradeLevel(true) . ' (' . $student->getAge() . ')' ?></h5>
        </div>
    </h4>
</div>
<div class="row" style="margin-top: 90px;">
  <div class="col-md-4 d-none d-md-block">
    <div class="list-group">
      <?php foreach ($logs as $key => $log) : ?>
        <a class="list-group-item <?= $_log && $_log == $log->getID() ? 'active' : ($key == 0 && !$_log ? 'active' : ''); ?>" href="<?= $log->isEditable() ? '?student=' . $student->getID() . '&y=' . $YEAR->getStartYear() . '&log=' . $log->getID() . ($is_archive ? '&archive=' . req_get::int('archive') : '') : '#' ?>">
          <?= $log->getTitle() ?> - Due <?= $log->getDeadline('M j') ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if ($schedule) : ?>
      <div class="card">
        <div class="card-header">
          <h4 class="card-title mb-0">Schedule</h4>
        </div>
        <div class="card-block p-0">

          <table class="table table-stripped">
            <thead>
              <th>
                Period
              </th>
              <th>
                Course
              </th>
            </thead>
            <tbody>
              <?php while ($period = mth_period::each($schedule->student_grade_level())) : ?>
                <?php
                    if (!($schedulPeriod  = mth_schedule_period::get($schedule, $period, true))) {
                      continue;
                    }
                    ?>
                <tr>
                  <td> <?= $schedulPeriod->period() ?></td>
                  <td>
                    <?php if ($schedulPeriod->subject()) : ?>
                      <?= $schedulPeriod->courseName() ?>
                    <?php else : ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <div class="col">
    <?php

    if ($active_log) :
      $content = $active_log->getData();
      $showform = $active_log->validForSubmission();
      $grade = $student_assessment ? $student_assessment->getGrade() : null;
      if (strtotime(date('Y-m-d')) > strtotime($YEAR->getFirstSemLearningLogsClose('Y-m-d')) && strtotime($active_log->getDeadline('Y-m-d')) <= strtotime($YEAR->getFirstSemLearningLogsClose('Y-m-d'))) {
        $showform = false;
      }
      ?>
      <div class="panel panel-bordered panel-primary" data-id="<?= $active_id ?>" data-person="<?= $student->getPersonID() ?>">
        <div class="panel-heading">
          <h3 class="panel-title"><?= $active_log->getTitle(); ?>
            <?php if ($student_assessment && $student_assessment->isDraft()) : ?><small style="color:#fff">(Draft)</small> <?php endif; ?>
            <?php if ($student_assessment && $student_assessment->isExcused()) : ?>
              <span class="badge badge-round badge-success">Excused</span>
            <?php elseif ($grade != null) : ?>
              <span class="badge badge-round badge-<?= assessment::isPassing($grade) ? 'success' : 'danger' ?>"><?= $grade ?>%</span>
            <?php endif; ?>
            <span class="badge badge-round badge-danger" style="margin-left: 10px;"><?= $showform ? '' : 'Locked' ?></span>
          </h3>
          <?php if ($student_assessment) : ?>
            <div class="panel-actions panel-actions-keep">
              <span><?= $student_assessment->getSubmittedDate('j M Y \a\t h:i A') ?></span>
            </div>
          <?php endif; ?>
        </div>
        <div class="panel-body">
          <?php if (!core_user::isUserTeacherAbove()) : ?>
            <div class="card border border-primary">
              <div class="card-block">
                <?= $content ? $content->details : ''; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php
            mth_views_homeroom::getSubmissionView($active_id, $student_assessment, $student, $showform, $YEAR);
            ?>
        </div>
      </div>
    <?php endif;
    ?>

  </div>
</div>
<?php
core_loader::addJsRef('fileUploaderTransport', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.iframe-transport.js');
core_loader::addJsRef('fileUploader', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.fileupload.js');
core_loader::printFooter();
?>
<!-- <script src="https://cdn.ckeditor.com/ckeditor5/15.0.0/classic/ckeditor.js"></script> -->
<script src="https://cdn.ckeditor.com/4.14.0/basic/ckeditor.js"></script>
<style>
  strong {
    font-weight: bold;
  }

  .ck-editor__editable {
    min-height: 200px;
  }
</style>
<script>
  $(function() {
    var is_submitted = <?= $student_assessment && $student_assessment->isSubmitted() ? 1 : 0 ?>;

    CKEDITOR.config.removePlugins = 'about';
    CKEDITOR.config.disableNativeSpellChecker = false;

    var $logform = $('#logform');
    textEditors = [];

    $('.text-question').each(function() {
      // ClassicEditor
      //   .create(this, {
      //     toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote'],
      //   })
      //   .then(function(editor) {
      //     //console.log( 'success',editor );
      //     textEditors.push(editor);
      //   })
      //   .catch(function(error) {
      //     console.error('error', error);
      //   });
      var $this = $(this);
      textEditors.push(CKEDITOR.replace($this.attr('id')));
    });


    var theSubmitButton = $('#submitlog');
    var theSaveButton = $('#savelog');
    theSaveButton.click(function() {
      if (is_submitted == 1) {
        returnToLogWarning("This Learning Log has already been submitted. Are you sure you'd like to create a new draft and resubmit?", saveForLater);
      } else if (!validate_log().fail) {
        saveForLater();
      } else {
        setTimeout(function() {
          top.swal({
            title: "",
            text: "You are attempting to save an empty Learning Log.",
            type: "error",
            confirmButtonText: "Return to Log",
          });
        }, 100);
      }
      return false;
    });

    function saveForLater() {
      $logform.append('<input type="hidden" name="save" value=1>');
      $logform.submit();
    }

    function plg_checker($container) {
      var valid = true;
      $container.each(function() {
        if ($(this).find('[type="checkbox"]:checked').length == 0) {
          var question = $(this).find('.plg_title').text();
          swal('', question, 'warning');
          valid = false;
          return false;
        }
      });
      return valid;
    }

    function validate_log() {
      var fail = true;
      var empty = ''; //<p>&nbsp;</p>
      var isRequiredEmpty = false;

      textEditors.forEach(function(prop, index) {
        var content = $.trim(prop.getData());
        //var $target = $(prop.sourceElement);  //use this for ckeditor5
        var $target = $(prop.element.$); //use this for ckeditor4
        if ($target.hasClass("required-question") && content == empty) {
          isRequiredEmpty = true;
        }
        if (content != empty) {
          fail = fail && false;
        }
      });

      if ($('#logform [type="checkbox"]').length > 0) {
        fail = fail && $('#logform [type="checkbox"]:checked').length == 0;
      }

      $('.checklist-form').each(function() {
        if ($(this).find('[type="checkbox"].required-question').length > 0 && $(this).find('[type="checkbox"].required-question:checked').length == 0) {
          isRequiredEmpty = true;
        }
      });

      return {
        fail: fail,
        emptyfirstquestion: isRequiredEmpty,
        empty: empty
      };
    }

    function returnToLogWarning(text, _callback) {
      top.swal({
        title: "",
        text: text,
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes",
        cancelButtonText: "Return to Log",
        closeOnConfirm: true,
      }, function() {
        _callback();
      });
    }

    function submitLog() {
      var form_validate = validate_log();
      var empty = form_validate.empty;
      var fail = form_validate.fail;
      var emptyfirstquestion = form_validate.emptyfirstquestion;

      if (fail) {
        setTimeout(function() {
          top.swal({
            title: "",
            text: "You are attempting to submit an empty Learning Log. Please return and complete it before submitting.",
            type: "error",
            confirmButtonText: "Return to Log",
          });
        }, 100);
      } else if (emptyfirstquestion) {
        setTimeout(function() {
          top.swal('', 'Please fill the required field(s)', 'error');
        }, 500);
      }
      // else if($('.plg_container').length > 0){
      //     if(plg_checker($('.plg_container'))){
      //         global_waiting();
      //         $logform.submit();
      //     }
      // }
      else {
        global_waiting();
        $logform.submit();
      }
    }

    theSubmitButton.click(function() {

      if (is_submitted == 1) {
        returnToLogWarning("This Learning Log has already been submitted. Are you sure you'd like to resubmit?", submitLog);
        return false;
      }

      submitLog();

      return false;
    });

    function setUploadStatus(file, complete) {
      if (setUploadStatus.files === undefined) {
        setUploadStatus.files = {};
      }
      setUploadStatus.files[file] = complete;
      if (!complete) {
        theSubmitButton.prop('disabled', false);
      }
    }

    $logform.on('click', '.delete-file', function() {
      deletedfile = $(this).data('hash');
      global_waiting();
      $.ajax({
        url: '?deletefile=' + deletedfile,
        dataType: 'JSON',
        success: function(response) {
          if (response.error == 1) {
            alert(response.data);
          } else {
            global_waiting_hide();
            $('#file_' + deletedfile).fadeOut();
          }
        },
        error: function() {
          global_waiting_hide();
        }
      });
    });

    $('#attachment').fileupload({
      url: '?upload=attachment',
      dataType: 'text',
      dropZone: $(this).parents('div.file-block'),
      //20000000 bytes = 20mb
      maxFileSize: 20000000,
      done: function(e, data) {
        var response = $.parseJSON(data.result);
        if (response.error == 0) {
          $logform.append('<div class="fileuploaded" id="file_' + response.data.hash + '">' +
            ' <a class="mth_reimbursement-receipt-link">' + data.files[0].name + '</a>' +
            '<a class="badge badge-secondary badge-round delete-file" data-hash="' + response.data.hash + '" style="color:#fff"><i class="fa fa-times"></i></a>' +
            '<input type="hidden" name="file_ids[]" value="' + response.data.file_id + '"/>' +
            '</div>'
          );

        } else {
          top.swal('Upload Error', response.data);
        }

        $('#attachment_progress').hide();
        $('#attachment_progress .upload_progress-bar').css(
          'width',
          '0%'
        );
      },
      add: function(e, data) {
        if (data.originalFiles[0].size > 20000000) {
          top.swal('Upload Error', 'Uploaded file exceeds 20MB', 'error');
        } else {
          data.submit();
        }
      },
      progressall: function(e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        $('#attachment_progress').show();
        $('#attachment_progress .upload_progress-bar').css(
          'width',
          progress + '%'
        );
        if (progress < 10) {
          setUploadStatus('attachment', false);
        } else if (progress >= 100) {
          setUploadStatus('attachment', true);
        }
      }
    });
  });
</script>