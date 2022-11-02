<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use mth\yoda\assessment;
use mth\yoda\studentassessment;
use mth\yoda\answersfile;
use mth\yoda\questions;
use mth\yoda\answers;
use mth\yoda\messages;

class mth_views_learninglog
{
  public static function getView($assessment, $show_form = false)
  {
    if ($show_form) {
      self::viewForm($assessment);
    } else {
      self::viewDetails($assessment);
    }
  }

  public static function viewForm($assessment)
  {
    $questions = new questions();
?>
    <div class="col-md-5">
      <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
          <h3 class="panel-title"> Details</h3>
        </div>
        <div class="panel-body">
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?= $assessment->getTitle() ?>" required />
            <input type="hidden" name="id" value="<?= $assessment->getID() ?>" />
          </div>
          <div class="form-group">
            <label>Deadline</label>
            <div class='input-group date'>
              <input type='text' class="form-control" autocomplete="off" name="deadline_date" id='deadline_d' value="<?= $assessment->getDeadline('m/d/Y') ?>" required />
              <input type='text' class="form-control" autocomplete="off" name="deadline_time" id='deadline_t' value="<?= $assessment->getDeadline('g:ia') ?>" required />
              <span class="input-group-addon">
                <span class="fa fa-calendar"></span>
              </span>
            </div>
            <label class="error" for="deadline_d"></label>
            <label class="error" for="deadline_t"></label>
          </div>
          <div class="form-group">
            <label>Details</label>
            <textarea class="form-control" id="details-content" name="details"><?php $data = $assessment->getData();
                                                                                echo $data ? $data->details : ''; ?></textarea>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-7">
      <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
          <h3 class="panel-title">Questions</h3>
        </div>
        <div class="panel-body" id="learning-log-question-container">
          <div id="questions-container">
            <?php foreach ($questions->getByTeacherAssesId($assessment->getID()) as $qnum => $question) : ?>
              <?php
              $_data = $question->getData();
              $checklist = $question->isChecklistType();
              $is_required = $question->isRequired();
              $data =  $checklist ? (array) json_decode($_data, true) : $_data;
              $optional = $question->isPLGgroup();
              echo self::questionTemp($data,  $checklist, $qnum, $question->getID(), $is_required, $optional);
              ?>
            <?php endforeach; ?>
          </div>
          <button class="btn btn-info btn-round" type="button" data-toggle="modal" data-target="#add-question-modal">Add Question</button>
        </div>
      </div>
      <!-- add question modal -->
      <div class="modal" tabindex="-1" role="dialog" id="add-question-modal">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Add Question</h4>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Question</label>
                <textarea class="form-control" rows="4" id="question-content"></textarea>
              </div>
              <div class="checkbox-custom checkbox-primary">
                <input type="checkbox" id="is-checklist">
                <label>Is Checklist</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" id="add-question">Add</button>
            </div>
          </div>
        </div>
      </div>
      <!-- END add question modal -->
    </div>
  <?php
  }

  /**
   * Checklist Template
   * @param int $row
   * @param string $value
   * @return void
   */
  public static function checklistTemp($row, $value = '')
  {
    ?>
      <div class="form-group checklist-item">
          <div class="input-group">
              <input type="text" class="form-control" name="questions[<?= $row ?>][list][]" value="<?= $value ?>">
              <span class="input-group-btn">
          <button type="button" class="btn btn-warning delete-checklist-item"><i class="fa fa-trash"></i></button>
        </span>
          </div>
      </div>
    <?php
  }

  /**
   * Question Template
   * @param array|string $data
   * @param boolean $is_checklist
   * @param integer $qnum
   * @return void
   */
  public static function questionTemp(
    $data,
    $is_checklist,
    $qnum,
    $id = null,
    $is_required = false,
    $optional = false
  ) {
    ?>
      <div class="card question-item-container">
          <input type="hidden" value="<?= $id ? $id : 0 ?>" name="questions[<?= $qnum ?>][id]">
        <?php if ($is_checklist) : ?>
            <div class="card-header">
                <div class="form-group">
            <textarea class="form-control" name="questions[<?= $qnum ?>][title]"><?= req_sanitize::txt_utf($data['title']); ?></textarea>
                </div>
            </div>
        <?php endif; ?>

          <div class="card-block question-container">
            <?php if ($is_checklist) : ?>
                <div class="checklist-container">
                  <?php
                  if (isset($data['checklist']) && count($data['checklist']) > 0) :
                    foreach ($data['checklist'] as $key => $item) :
                      ?>
                      <?= self::checklistTemp($qnum, $item['list']); ?>
                    <?php
                    endforeach;
                  endif;
                  ?>
                </div>
                <button type="button" class="btn btn-info add-checklist">Add</button>
            <?php else : ?>
                <div class="form-group">
            <textarea class="form-control" rows="6" name="questions[<?= $qnum ?>][data]"><?= $data; ?></textarea>
                </div>
            <?php endif; ?>
              <button class="btn btn-danger delete-question" type="button">Delete Question</button>
            <?php if (!$optional) : ?>
                <div class="checkbox-custom checkbox-primary float-right">
            <input type="checkbox" name="questions[<?= $qnum ?>][required]" <?= $is_required ? "CHECKED" : "" ?>>
                    <label>Required</label>
                </div>
            <?php endif; ?>
          </div>
      </div>
    <?php
  }

  public static function viewDetails($assessment)
  {
    $questions = new questions();
    ?>
      <div class="col-md-6">
          <div class="panel panel-bordered panel-primary">
              <div class="panel-heading">
                  <h3 class="panel-title"> Details</h3>
              </div>
              <div class="panel-body" style="padding: 10px;">
                <?= ($content = $assessment->getData()) ? $content->details : '' ?>
              </div>
          </div>
      </div>
      <div class="col-md-6">
          <div class="panel panel-bordered panel-primary">
              <div class="panel-heading">
                  <h3 class="panel-title">Questions</h3>
              </div>
              <div class="panel-body">
                <?php foreach ($questions->getByTeacherAssesId($assessment->getID()) as $qnum => $question) : ?>
                  <?php $q_data = $question->getData(); ?>
                    <div class="card">
                        <div class="card-block">
                          <?php if ($question->isChecklistType()) : ?>
                            <?php
                            $data = json_decode($q_data);
                            echo req_sanitize::txt_utf($data->title);
                            foreach ($data->checklist as $key => $item) :
                              ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" disabled>
                                    <label>
                                      <?= $item->list ?>
                                    </label>
                                </div>
                            <?php endforeach;
                            ?>
                          <?php else : ?>
                            <?= $q_data; ?>
                          <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
              </div>
          </div>
      </div>
    <?php
  }

  public static function getStudentLogsPDFView(mth_student $student, assessment $assessment, mth_schoolYear $year) {
    if (!($studentID = $student->getID()) || !($learninglogs = $assessment->getStudentLearningLogs($student, $year))) {
      return false;
    }
    $year = $year ?: mth_schoolYear::getCurrent();
    $personID = $student->getPersonID();
    ob_start();
    ?>
      <!DOCTYPE html>
      <html>
      <style>
          body {
              font-family: 'Ariel', sans-serif;
              font-size: 9.5pt;
              margin: 0;
          }
      </style>
      <body>
      <div>
      <div class="log-header">
          <h2 style="display: flex; justify-content: flex-start; align-items: center;">
              <div class="ml-10 mt-5">
                <?= $student ?>'s Learning Logs
                  <h5 class="mt-0"
                      style="display: flex; justify-content: flex-start;"><?= $year . ' - ' . $student->getGradeLevel(true, false, $year) . ' (' . $student->getAge() . ')' ?></h5>
              </div>
          </h2>
      </div>
      <?php foreach ($learninglogs as $key => $active_log): ?>
        <?php $assessmentID = $active_log->getID();
        $student_assessment = studentassessment::get($assessmentID, $personID);
        $title = $active_log->getTitle();
        if (is_null($student_assessment)) :
          $title .= ' - Not Submitted';
        elseif ($student_assessment->isNA()) :
          $title .= ' - N/A';
        elseif (($submittedDate = $student_assessment->getSubmittedDate('j M Y \a\t h:i A'))) :
          $title .= ' - ' . $student_assessment->getStatus() . ($student_assessment->isDraft() ? ' (Draft)' : '') . ' - ' . $submittedDate;
        else :
          $title .= ' - ' . $student_assessment->getStatus();
        endif; ?>
          <h3 class="panel-title">
            <?= $title ?>
          </h3>
        <?php if (!is_null($student_assessment) && !$student_assessment->isNA()) : ?>
              <div class="card border border-primary">
                  <div class="card-block">
                    <?php
                    $questions = new questions();
                    foreach ($questions->getByTeacherAssesId($assessmentID) as $qnum => $question) :
                      $answer = $student_assessment ? answers::getByAssessmentQuestion($student_assessment, $question) : null;
                      $q_data = $question->getData();
                      ?>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                  <?= $question->isChecklistType() ? utf8_encode((json_decode($q_data))->title) : utf8_encode($q_data); ?>
                                </h4>
                            </div>
                            <div class="card-block answer-container">
                              <?php
                              if ($answer) :
                                if (is_array($answer->getData())) :
                                  foreach ($answer->getData() as $checklist) :
                                    ?>
                                      <div>
                                          <i class="fa fa-check text-success"></i>
                                        <?= utf8_encode($checklist) ?>
                                      </div>
                                  <?php
                                  endforeach;
                                else : ?>
                                  <?= utf8_encode($answer->getData()) ?>
                                <?php endif;
                              endif;
                              ?>
                            </div>
                        </div>
                    <?php
                    endforeach;
                    ?>
                  </div>
              </div>
          <?php if (($feedbacks = messages::getAllFromAssessment($personID, $assessmentID))): ?>
                  <div class="panel panel-bordered panel-primary">
                      <div class="panel-heading" data-toggle="panel-collapse">
                          <h3 class="panel-title">Feedback(s)</h3>
                      </div>
                      <div class="panel-body">
                          <ul class="list-group list-group-bordered">
                            <?php foreach ($feedbacks as $f): ?>
                                <li class="list-group-item">
                                  <?= $f->getDate('j M Y \a\t h:i A') . ' - ' . $f->getContent() ?>
                                </li>
                            <?php endforeach; ?>
                          </ul>
                      </div>
                  </div>
          <?php endif; ?>
          <?php if (($assessmentfiles = answersfile::getByStudentAssessmentId($student_assessment->getID()))): ?>
                  <div class="panel panel-bordered panel-primary">
                      <div class="panel-heading" data-toggle="panel-collapse">
                          <h3 class="panel-title">Files(s)</h3>
                      </div>
                    <?php foreach ($assessmentfiles as $file) : ?>
                      <?= $file->getFile() ? "<div>" . $file->getFile()->name() . "</div>" : '' ?>
                    <?php endforeach; ?>
                  </div>
          <?php endif; ?>
          <?php if ($key != count($learninglogs) - 1) : ?>
                  <p style="page-break-after: always;">&nbsp;</p>
          <?php endif;
        endif;
      endforeach; ?>
      </div>
      </body>
      </html>
    <?php
    $content = ob_get_contents();
    ob_end_clean();
    $option = new Options();
    $option->setIsRemoteEnabled(true);
    $dompdf = new Dompdf();
    $dompdf->setOptions($option);
    $dompdf->loadHtml($content);
    $dompdf->render();
    return $dompdf->output();
  }
}
