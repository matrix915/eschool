<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use mth\yoda\courses;
use mth\student\SchoolOfEnrollment;
use mth\yoda\assessment;
use mth\yoda\answers;
use mth\yoda\questions;
use mth\yoda\answersfile;
use mth\yoda\plgs;

/**
 * MTH Homeroom View Template
 *
 * @author Rex
 */
class mth_views_homeroom
{
  /**
   * Get HTML Content for PDF Unoffical Progress Report
   * @param mth_student $student
   * @param mth_schoolYear $year
   * @return string
   */
  public static function getPDFcontent(mth_student $student, mth_schoolYear $year)
  {
    $attr = self::getAttr($student, $year);
    $schedule = $student->schedule($year);
    $enrollment = courses::getStudentHomeroom($student->getID(), $year);
    $first_sem = $enrollment && $enrollment->getGrade(1) != 0 ? (assessment::isPassing($enrollment->getGrade(1)) ? 'Pass' : 'Fail') : '';
    $second_sem = $enrollment && $enrollment->getGrade(2) != 0 ? (assessment::isPassing($enrollment->getGrade(2)) ? 'Pass' : 'Fail') : '';
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>

    <head>
      <title>UNOFFICIAL Progress Report</title>
      <style>
        body {
          font-family: 'Ariel', sans-serif;
          font-size: 10pt;
          margin: 0;
        }

        .table-header {
          background-color: rgb(204, 204, 204);
          font-weight: bold;
        }

        .card-table td {
          border: 1px solid #9e9e9e;
          padding: 10px;
          vertical-align: top;
        }

        .card-table {
          width: 100%;
          border-collapse: collapse;
          border: none;
        }
      </style>
    </head>

    <body>
      <div style="height: 9in; page-break-inside: avoid;">
        <p style="text-align: center; margin-top: -.25in">
          <?php if ($attr['header_data']) : ?>
            <img style="width:7.5in" src="data:<?= $attr['header_type'] ?>;base64,<?= $attr['header_data'] ?>">
          <?php endif; ?>
        </p>
        <table style="" class="card-table">
          <tr class="table-header">
            <td colspan="2">
              <h2>UNOFFICIAL Progress Report <?= $year ?></h2>
            </td>
          </tr>
          <tr>
            <td><b>Student Name:</b></td>
            <td><?= $student->getName() ?></td>
          </tr>
          <tr>
            <td><b>Grade Level:</b></td>
            <td><?= $student->getGradeLevel(true, false, $year->getID()) ?></td>
          </tr>
          <tr>
            <td><b>DOB:</b></td>
            <td><?= $student->getDateOfBirth('m-d-Y') ?></td>
          </tr>
          <tr>
            <td><b>School of Enrollment:</b></td>
            <td><?= '<b>' . $student->getSOEname($year) . '</b><br>' . $student->getSOEaddress(true, $year) . '<br>' . $student->getSOEphones(true, $year); ?></td>
          </tr>
        </table>
        <br>
        <table style="" class="card-table">
          <tr class="table-header">
            <td>
              Period
            </td>
            <td>
              Course
            </td>
            <td>
              1st Semester
            </td>
            <td>
              2nd Semester
            </td>
          </tr>
          <?php while ($period = mth_period::each($schedule->student_grade_level())) : ?>
            <?php
                  if (!($schedulPeriod = mth_schedule_period::get($schedule, $period, true))) {
                    continue;
                  }

                  if ($schedulPeriod->courseName() == "None") {
                    continue;
                  }
                  ?>
            <tr>
              <td>
                <b><?= $schedulPeriod->period() ?></b>
              </td>
              <td>
                <?php
                      if ($schedulPeriod->subject()) {
                        echo $schedulPeriod->courseName();
                      }
                      ?>
              </td>
              <td>
                <?= $first_sem ?>
              </td>
              <td>
                <?= $second_sem ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </table>
        <br>
        <table style="" class="card-table">
          <tr class="table-header">
            <td>
              Grading Scale
            </td>
          </tr>
          <tr>
            <td>
              80% - 100% = Pass
            </td>
          </tr>
          <tr>
            <td>
              0% - 79% = Fail
            </td>
          </tr>
        </table>
      </div>
    </body>

    </html>
    <?php
        $content = ob_get_contents();
        ob_end_clean();
        //return $content;
        $option = new Options();
        $option->setIsRemoteEnabled(true);
        $dompdf = new Dompdf();
        $dompdf->setOptions($option);
        $dompdf->load_html($content);
        $dompdf->render();
        return $dompdf->output();
      }

      /**
       * Get PDF parts attributres
       * @param mth_student $student
       * @param mth_schoolYear $year
       * @return array
       */
      public static function getAttr(mth_student $student, mth_schoolYear $year)
      {

        $graphics_paths = core_config::getSitePath() . '/_/mth_includes/school_of_enrollment_graphics/';
        $header = null;
        $header_type = $footer_type = \core\Response::TYPE_PNG;

        $soe = $student->getSchoolOfEnrollment(true, $year);
        if ($soe == SchoolOfEnrollment::GPA) {
          $header = 'GPA-head.jpg';
          $header_type = $footer_type = \core\Response::TYPE_JPEG;
        } elseif ($soe == SchoolOfEnrollment::ALA) {
          $header_type = $footer_type = \core\Response::TYPE_JPEG;
          $header = 'ALA-head.jpg';
        } elseif ($soe == SchoolOfEnrollment::Tooele) {
          $header = 'Tooele-header.png';
        } elseif ($soe == SchoolOfEnrollment::eSchool) {
          $header = 'eSchool-header2.png';
        } elseif ($soe == SchoolOfEnrollment::Nebo) {
          $header = 'nebo-header.png';
        }

        return [
          'header_data' => $header ? base64_encode(file_get_contents($graphics_paths . $header)) : null,
          'header_type' => $header_type
        ];
      }

      /**
       * Get Learning Log Submission Content View/Form
       * @param int $assessment_id
       * @param object $student_assessment
       * @param mth_student $student
       * @param boolean $show_form
       * @param  object $school_year most like mth_schoolYear class
       * @return void
       */
      public static function getSubmissionView($assessment_id, $student_assessment, mth_student $student = null, $show_form = false, $school_year = null)
      {
        if ($show_form) {
          self::viewForm($assessment_id, $student_assessment, $student, $school_year);
        } else {
          self::viewDetails($assessment_id, $student_assessment);
        }
      }

      /**
       * View Learning Log Submission Details Only
       * @param int $assessment_id
       * @param object $student_assessment
       * @return string html content
       */
      public static function viewDetails($assessment_id, $student_assessment)
      {
        if (!$student_assessment) {
          echo '<div class="alert bg-info">Learning log is not yet submitted</div>';
        }

        $questions = new questions();
        foreach ($questions->getByTeacherAssesId($assessment_id) as $qnum => $question) :
          $answer = $student_assessment ? answers::getByAssessmentQuestion($student_assessment, $question) : null;
          $q_data = $question->getData();
          $plgs = [];
          $plg_gradelevel = '';
          ?>
      <div class="card">
        <div class="card-header">
          <h4 class="card-title mb-0">
            <?= $question->isChecklistType() ? (json_decode($q_data))->title : $q_data; ?>
          </h4>
        </div>
        <div class="card-block answer-container">
          <?php
                if ($answer) :
                  if (is_array($answer->getData())) :
                    $plgs = $answer->getData();
                    $plg_gradelevel = $answer->getGradeLevel();
                    foreach ($answer->getData() as $checklist) :
                      ?>
                <div>
                  <i class="fa fa-check text-success"></i>
                  <?= $checklist ?>
                </div>
          <?php
                    endforeach;
                  else :
                    echo $answer->getData();
                  endif;
                endif;
                ?>
        </div>
        <?php if ($question->isPLGgroup()) : ?>
          <div class="card-footer">
            <button class="btn btn-primary plg-edit-btn" type="button" data-gradelevel="<?= $plg_gradelevel ?>" data-plgs="<?= implode('|', $plgs) ?>" data-answerid="<?= $answer ? $answer->getID() : 0 ?>" data-subject="<?= $question->getSubject() ?>">
              <i class="fa-edit"></i>
            </button>
          </div>
        <?php endif; ?>
      </div>
    <?php
        endforeach;
        if ($student_assessment) {
          foreach (answersfile::getByStudentAssessmentId($student_assessment->getID()) as $file) {
            if ($file && $file->getFile()) {
              echo self::file_renderer($file->getFile()) . '<br>';
            }
          }
        }
      }

      /**
       * File renderer
       * @param object $file
       * @param boolean $download
       * @return string
       */
      public static function file_renderer($file, $download = true)
      {
        if ($download) {
          return '<a href="/_/mth_includes/mth_file.php?hash=' . $file->hash() . '">' . $file->name() . '</a>';
        }
        $url = '/_/user/fileviewer?file=' . $file->hash();
        return '<a onclick="top.global_popup_iframe(\'fileviewer\',\'' . $url . '\')" href="#">' . $file->name() . '</a>';
      }

      /**
       * Get Past selected questions::PLG type of question
       * @param int $student_id
       * @param int $assessment_id
       * @return array
       */
      public static function getPastSelectedSpecial($student_id, $assessment_id = null)
      {
        $answers = [];
        foreach (answers::getPastSelectedSpecial($student_id, $assessment_id) as $answer) {
          if ($answer) {
            $data = json_decode($answer);
            if (isset($data->grade_level) || !$data->grade_level) {
              if (!isset($answers[$data->grade_level])) {
                $answers[$data->grade_level] = [];
              }

              if (!is_null($data->answer)) {
                $answers[$data->grade_level] = array_unique(array_merge($answers[$data->grade_level], $data->answer));
              }
            }
          }
        }
        return $answers;
      }

      /**
       * Get Html content for Learning log Form
       * @param int $assessment_id
       * @param object $student_assessment
       * @param mth_student $student
       * @param object $school_year most likely mth_schoolYear instance
       * @return string html content
       */
      public static function viewForm($assessment_id, $student_assessment, mth_student $student, $school_year = null)
      {
        $questions = new questions();
        $past_special_answers = self::getPastSelectedSpecial($student->getID(), $assessment_id);
         function lowercaseAndCharacterLimit($str) {
            return trim(strtolower(substr($str, 0, 50)));
         }
        ?>
    <form name="logsform" id="logform" method="post" action='?form=<?= uniqid('yoda-submit-learning-log') ?>&student=<?= $student->getID() ?>&log=<?= $assessment_id ?>'>
      <input type="hidden" name="student_assessment_id" value="<?= $student_assessment ? $student_assessment->getID() : 0 ?>">
      <?php foreach ($questions->getByTeacherAssesId($assessment_id) as $qnum => $question) :
            $_answer = $student_assessment ? answers::getByAssessmentQuestion($student_assessment, $question) : null;
            ?>
        <input type="hidden" value="<?= $question->getType() ?>" name="question[<?= $question->getID() ?>][type]">
        <?php
              if ($question->isChecklist()) : //checklist question
                $data = json_decode($question->getData());
                ?>
          <div class="card">
            <div class="card-header">
              <h4 class="card-title mb-0"><?= req_sanitize::txt_utf($data->title) ?>
                <?= $question->isRequired() ? '<span style="color:red;">*</span>' : ''; ?></h4>
            </div>
            <div class="card-block checklist-form">
              <?php
                      foreach ($data->checklist as $key => $item) :
                        $id = "checklist_{$question->getID()}_$key";
                        ?>
                <div class="checkbox-custom checkbox-primary">>
                  <input type="checkbox" id="<?= $id ?>" class="checklist <?= $question->isRequired() ? ' required-question' : '' ?>" value="<?= $item->list ?>" name="question[<?= $question->getID() ?>][answer][]" <?= $_answer && in_array($item->list, $_answer->getData()) ? 'CHECKED' : '' ?>>
                  <label for="<?= $id ?>">
                    <?= $item->list ?>
                  </label>
                </div>
              <?php
                      endforeach; //end checklist loop
                      ?>
            </div>
          </div>
        <?php elseif ($question->isPLGgroup() && !is_null($school_year)) : ?>
          <div class="card plg_container">
            <div class="card-header">
              <?php if ($question->isPlgIndependent()) : ?>
                <?php $data = json_decode($question->getData()); ?>
                <span class="plg_title"><?= req_sanitize::txt_utf($data->title) ?></span>
                <input type="hidden" class="select_grade" name="question[<?= $question->getID() ?>][grade_level]" value="INDEPENDENT" data-subject="<?= $question->getSubject() ?>" data-year="<?= $school_year->getID() ?>" data-question="<?= $question->getID() ?>">
              <?php else : ?>
                <span class="plg_title"><?= $question->getData() ?></span>
                <select class="float-right select_grade" name="question[<?= $question->getID() ?>][grade_level]" data-subject="<?= $question->getSubject() ?>" data-year="<?= $school_year->getID() ?>" data-question="<?= $question->getID() ?>">
                  <option></option>
                  <?php foreach (plgs::distictGradeLevels($school_year) as $grade_level) : ?>
                    <option value="<?= $grade_level ?>" <?= $_answer && $_answer->getGradeLevel() == $grade_level ? 'SELECTED' : '' ?>><?= $grade_level ?></option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>
            <div class="card-block plg_checklist">
              <?php if ($question->isPlgIndependent()) : ?>
                <?php
                          $data = json_decode($question->getData());
                          foreach ($data->checklist as $key => $item) :
                            $id = "checklist_{$question->getID()}_$key";
                            ?>
                  <div class="checkbox-custom checkbox-primary">>
                    <input type="checkbox" id="<?= $id ?>" class="plgname" value="<?= $item->list ?>" name="question[<?= $question->getID() ?>][answer][]" <?= $_answer && in_array($item->list, $_answer->getData()) ? 'CHECKED' : '' ?>>
                    <label for="<?= $id ?>">
                      <?= $item->list ?>
                    </label>
                  </div>
                <?php
                          endforeach; //end checklist loop
                          ?>
              <?php else : ?>
                <?php if ($_answer && $_answer->getGradeLevel()) {
                    $plgs = array_map('lowercaseAndCharacterLimit', $_answer->getData());
                            foreach (plgs::get($_answer->getGradeLevel(), $question->getSubject(), $school_year->getID()) as $plg) {
                       $selected = in_array(lowercaseAndCharacterLimit($plg->getName()), $plgs) ? 'CHECKED' : '';
                              echo '<div class="checkbox-custom checkbox-primary"><input type="checkbox" class="plgname" name="question[' . $question->getID() . '][answer][]" value="' . $plg->getName() . '" ' . $selected . '><label>' . $plg->getName() . '</label></div>';
                            }
                          } ?>
              <?php endif; ?>

            </div>
          </div>
        <?php
              else : //text question
                $textid = "question_{$question->getID()}";
                ?>
          <div class="form-group">
            <h4><?= req_sanitize::txt_utf($question->getData()) ?> <?= $question->isRequired() ? '<span style="color:red;">*</span>' : ''; ?></h4>

            <textarea id="question<?= $question->getID() ?>" class="form-control text-question<?= $question->isRequired() ? ' required-question' : '' ?>" name="question[<?= $question->getID() ?>][answer]" id="<?= $textid ?>" required><?= $_answer ? $_answer->getData() : '' ?></textarea>
          </div>
        <?php
              endif; //end if ischecklist
              ?>
      <?php endforeach; //end getByTeacherAssesId
          ?>
      <?php
          if ($student_assessment) :
            foreach (answersfile::getByStudentAssessmentId($student_assessment->getID()) as $file) :
              if (!($_file = $file->getFile())) {
                continue;
              }
              ?>
          <div class="fileuploaded" id="file_<?= $_file->hash() ?>">
            <?= self::file_renderer($_file) ?>
            <a class="badge badge-secondary badge-round delete-file" data-hash="<?= $_file->hash() ?>" style="color:#fff"><i class="fa fa-times"></i></a>
            <input type="hidden" name="file_ids[]" value="<?= $file->getID() ?>">
          </div>
      <?php
            endforeach;
          endif;
          ?>
    </form>
    <div class="file-block">
      <p>
        <span class="fileinput-button btn btn-secondary btn-round">
          <span class="button">Upload File <small>(Maximum of 20MB)</small></span>
          <input type="file" name="attachment" id="attachment">
        </span>
      </p>
      <div id="attachment_progress" class="progress progress-xs" style="display: none;">
        <div class="upload_progress-bar progress-bar progress-bar-warning progress-bar-indicating active" style="width: 0%;" role="progressbar">
        </div>
      </div>
    </div>
    <hr>
    <div class="panel panel-primary panel-line">
      <div class="panel-heading">
        <h4 class="panel-title">
          <small>Ready to submit Log for</small> <?= $student ?>?
        </h4>
      </div>
      <div class="panel-body">
        <button class="btn btn-round btn-warning btn-lg" type="submit" id="submitlog">Submit</button>
        <button class="btn btn-round btn-primary btn-lg" type="button" id="savelog">Save for later</button>
      </div>
    </div>
    <script>
      var selected_plg = JSON.parse('<?= json_encode($past_special_answers) ?>');

      function convert_object(obj) {
        return Object.keys(obj).map(function(key) {
          return obj[key];
        });
      }

      function check_each_plg(grade_level, $target) {


        if (Object.keys(selected_plg).length == 0) {
          return false;
        }

        if (grade_level == '') {
          return false;
        }
        var PLGs = selected_plg[grade_level] != undefined ? convert_object(selected_plg[grade_level]) : [];

        if (PLGs.length > 0) {
          var answers = PLGs;

          $target.closest('.plg_container').find('.plgname').each(function() {
            var $this = $(this);
            var plg = $this.val().substring(0, 50).toLowerCase().trim();
            if (answers.map(e=>e.substring(0, 50).toLowerCase().trim()).indexOf(plg) != -1) {
              $this.closest('.checkbox-custom').addClass('answered');
            }
          });
        }
      }

      function check_plg() {
        $('.select_grade').each(function() {
          $select = $(this);
          var grade_level = $select.val();
          check_each_plg(grade_level, $select);
        });
        $('.select_grade').each(function() {
          $select = $(this);
          var grade_level = $select.val();
          check_each_plg(grade_level, $select);
        });
      }

      $(function() {

        check_plg();

        $('.plg_container').on('change', '.select_grade', function() {
          var $this = $(this);
          var grade_level = $this.val();
          var subject = $this.data('subject');
          var school_year = $this.data('year');
          var question_id = $this.data('question');

          $.ajax({
            'url': '?select_plg=1',
            type: 'POST',
            data: {
              grade_level: grade_level,
              subject: subject,
              school_year: school_year,
              question_id: question_id
            },
            success: function(html_response) {
              $this.closest('.plg_container').find('.plg_checklist').html(html_response);
              setTimeout(function() {
                check_each_plg($this.val(), $this);
              }, 100);
            }
          });
        });
      });
    </script>
<?php
  }
}
