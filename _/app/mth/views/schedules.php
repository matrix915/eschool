<?php

use Dompdf\Dompdf;
use mth\yoda\courses;

set_include_path(ROOT . '/_/mth_includes/' . PATH_SEPARATOR . get_include_path());
require_once 'string_diff/diff.php';

/**
 * Description of schedules
 *
 * @author abe
 */
class mth_views_schedules {
  public static function scheduleDetails(mth_schedule $schedule) {
    if (!($student = $schedule->student())) {
      echo 'no student attached to schedule';
      return;
    }
    $string_diff = new Diff();
    $adjustmentClass = new stdClass();
    $adjustmentClass->mode = 'w'; //adjust by word
    ?>
      <script type="text/javascript">
          function editPeriod(schedulePeriodID) {
              global_popup_iframe('mth_schedule_period-edit', '/student/<?= $student->getSlug() ?>/schedule/period?schedule_period=' + schedulePeriodID);
          }

          function changeSecond(schedulePeriodID, duplicateOnly) {
              global_popup_iframe('mth_schedule_period-edit', '/student/<?= $student->getSlug() ?>/schedule/2nd-sem-change?schedule_period=' + schedulePeriodID + '&duplicateOnly=' + (duplicateOnly ? 1 : 0));
          }
      </script>
      <table class="table table-stripped mth_schedule-table responsive" id="mth_schedule-<?= $schedule->id() ?>-table">
          <thead>
          <th>
              Period
          </th>
          <th>
              Course
          </th>
          <th>
              Course Type
          </th>
          <th>
              Course/Provider/Notes
              <button class="btn btn-xs btn-round btn-info waves-effect waves-light waves-round float-right btn-custom-text" style="display: none;" id="show-button">Show Deleted Text</button>
              <button class="btn btn-xs btn-round btn-info waves-effect waves-light waves-round float-right btn-custom-text" style="display: none;" id="hide-button">Hide Deleted Text</button>
          </th>


          <!-- <?php if (core_path::getPath()->isAdmin() && $schedule->isAccepted()) : ?>
                <th>
                    Reimbursed
                </th>
                <?php endif; ?> -->
          </thead>
          <tbody>
          <?php while ($period = mth_period::each($schedule->student_grade_level())) : ?>
            <?php for ($second_semester = 0; $second_semester <= ($schedule->schoolYear()->getSecondSemOpen() < time() ? 1 : 0); $second_semester++) : ?>

              <?php $schedulPeriod = $second_semester
                ? mth_schedule_period::get($schedule, $period, true)
                : mth_schedule_period::create($schedule, $period); ?>

              <?php if ($second_semester && !$schedulPeriod->second_semester()) : ?>
                <?php if ($schedule->second_sem_change_available() && $schedulPeriod->allow_2nd_sem_change()) : ?>
                          <tr>
                              <td></td>
                              <td>
                                  <a onclick="changeSecond(<?= $schedulPeriod->id() ?>)" class="mth_schedule_second_sem_link">Update for 2nd Semester</a> |
                                  <a onclick="changeSecond(<?= $schedulPeriod->id() ?>, true)">No update</a>
                              </td>
                              <td></td>
                              <td></td>
                              <!-- <?php if (core_path::getPath()->isAdmin() && $schedule->isAccepted()) : ?>
                                    <td></td>
                                <?php endif; ?> -->
                          </tr>
                <?php endif; ?>
                <?php continue;
              endif; ?>
              <?php
              $custom_built_entrep = $schedulPeriod->course_type(true) == mth_schedule_period::TYPE_CUSTOM && (strtolower($schedulPeriod->subject())) == 'entrepreneurship' ? 'period-custom-entr' : '';
              $period_has_course = in_array($schedulPeriod->courseName(), ['None', 'Not Specified']) ? '' : 'period-has-course';
              $iseditablerow = $schedulPeriod->subject() && $schedulPeriod->editable();
              $event = $iseditablerow ? 'onclick="editPeriod(' . $schedulPeriod->id() . ');" style="cursor:pointer;"' : '';
              $has_tech_allowance = $schedulPeriod->subject() && strtolower($schedulPeriod->subject()->getName()) == 'tech' && $schedulPeriod->course() && (int) $schedulPeriod->course()->allowance() ? 'period-has-allowance' : '';
              ?>

                  <tr <?= $has_tech_allowance ? 'data-allowance="' . $schedulPeriod->course()->allowance() . '"' : '' ?> class="<?= $iseditablerow ? 'editable-row ' : '' ?>mth_schedule_period-row-<?= $schedulPeriod->require_change() ? 'requires_change' : ($schedulPeriod->require_change_date() ? ($schedule->isNewSubmission() ? 'normal' : 'changed') : 'normal') ?> period-<?= $schedulPeriod->period_number() ?> <?= $has_tech_allowance ?> <?= $period_has_course ?> <?= $custom_built_entrep ?>">
                      <td <?= $event ?>>
                        <?= $schedulPeriod->period() ?>
                        <?= $schedulPeriod->second_semester() ? '<small>(2nd Sem.)</small>' : '' ?>
                      </td>
                      <td <?= $event ?>>
                          <!-- <h5><?= $schedulPeriod->subject() ? $schedulPeriod->subject() : $schedulPeriod->period()->label() ?></h5> -->
                        <?php if ($schedulPeriod->subject()) : ?>
                            <a <?php if ($schedulPeriod->editable()) : ?> title="Change Course" <?php else : ?>class="mth_schedule_period-uneditable" <?php endif; ?>>
                              <?= req_sanitize::txt_decode($schedulPeriod->courseName()) ?>
                            </a>
                        <?php elseif ($schedulPeriod->editable()) : ?>
                            <a onclick="editPeriod(<?= $schedulPeriod->id() ?>);" title="Select Course"><?= $schedulPeriod->none() ? 'None' : 'Select Course' ?></a>
                        <?php else : ?>
                            None
                        <?php endif; ?>
                      </td>
                      <td <?= $event ?>>
                        <?= $schedulPeriod->course_type() ?>
                      </td>
                      <td class="mth_schedule-collapse" <?= core_path::getPath()->isAdmin() ? '' : $event ?>>
                        <?php if ($schedulPeriod->course_type(true) == mth_schedule_period::TYPE_CUSTOM || ($schedulPeriod->course() && $schedulPeriod->course()->requireDesc($student->getGradeLevelValue($schedule->schoolYear())))) :
                          ?>
                          <?php if ($schedule->isStatus(mth_schedule::STATUS_STARTED) || $schedule->isNewSubmission() || (!$schedulPeriod->require_change() && !$schedule->isNewSubmission() && $schedulPeriod->require_change_date())) : ?>
                          <?php if (empty($schedulPeriod->template_course_description())) {
                            $string_diff->skipDelete(true);
                            $string_diff->setInsertStyle('font-style: italic;');
                          } else {
                            $string_diff->skipDelete(false);
                            $string_diff->setInsertStyle('background-color: #CCFFCC; color: #757575;');
                            $string_diff->setDeleteStyle('background-color: #FFCCCC; text-decoration: line-through; color: #757575; display: none;');
                          }
                          //TODO Find a better way to handle the diff when parent adds something at the end of the template without breaking when the parent does not add anything.
                          $formatSuccessful = $string_diff->FormatDiffAsHtml((substr($schedulPeriod->template_course_description(), -1) != ' ' ? $schedulPeriod->template_course_description() . ' ' : $schedulPeriod->template_course_description())
                            , (substr($schedulPeriod->custom_desc(), -1) != ' ' ? $schedulPeriod->custom_desc() . ' ' : $schedulPeriod->custom_desc()), $adjustmentClass);//The spaces at the end are required to accurately compare the strings.
                          ?>
                            <div style="max-width: 500px;"><?= core_path::getPath()->isAdmin() && $formatSuccessful ? $adjustmentClass->html : $schedulPeriod->custom_desc() ?></div>
                            <script type="text/javascript">
                                $(function () {
                                    if ($('.deleted-text').length) {
                                        $('#show-button').show();
                                    }
                                })

                                $('#show-button').click(function () {
                                    $('.deleted-text').show();
                                    $('#show-button').hide();
                                    $('#hide-button').show();
                                })

                                $('#hide-button').click(function () {
                                    $('.deleted-text').hide();
                                    $('#hide-button').hide();
                                    $('#show-button').show();
                                })
                            </script>
                        <?php else: ?>
                            <div style="max-width: 500px;"><?= $schedulPeriod->custom_desc() ?></div>
                        <?php endif;
                        endif; ?>
                        <?php if ($schedulPeriod->course_type(true) == mth_schedule_period::TYPE_TP) : ?>
                            <div style="max-width: 500px;">
                              <?= $schedulPeriod->tp_name() ?><br>
                              <?= $schedulPeriod->tp_course() ?><br>
                              <?= $schedulPeriod->tp_phone() ?><br>

                              <?php
                              $string = $schedulPeriod->tp_website();
                              preg_match_all('/[^,\s]+/', $string, $match);
                              foreach ($match[0] as $url) :
                                ?>
                                  <a href="<?= $tp_website = empty(parse_url($url)['scheme']) ? 'http://' . $url : $url ?>" target="_blank" type="url"><?= $tp_website ?></a><br>
                              <?php endforeach; ?>
                              <?= $schedulPeriod->tp_desc() ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($schedulPeriod->course_type(true) == mth_schedule_period::TYPE_MTH) :
                          ?>
                            <?= $schedulPeriod->getRawProvider() ?>
                            <div style="max-width: 500px;">
                              <?php if ($schedulPeriod->getRawProviderCourse()) : ?>
                                <?= $schedulPeriod->getRawProviderCourse(); ?>
                              <?php else : ?>
                                <?= $schedulPeriod->tp_course() ?><br>
                                <?= $schedulPeriod->tp_name() ?><br>
                                <?= $schedulPeriod->tp_district() ?><br>
                                <?= $schedulPeriod->tp_phone() ?>
                              <?php endif; ?>
                            </div>
                        <?php endif; ?>
                      </td>

                      <!-- <?php if ($schedule->isAccepted() && core_path::getPath()->isAdmin()) : ?>
                            <td>
                            <div>
                                <?php ($schedulPeriod->reimbursed(NULL, false)) ? "$" . $schedulPeriod->reimbursed() : '' ?>
                            </div>
                            </td> -->
                    <?php endif; ?>
                  </tr>
                  <!-- <?= $schedulPeriod->id() ?> -->
            <?php endfor; ?>
          <?php endwhile; ?>
          </tbody>
      </table>
    <?php
  }

  public static function entireSchedule(mth_schedule $schedule) {
    if (!($student = $schedule->student())) {
      echo 'no student attached to schedule';
      return;
    }
    $string_diff = new Diff();
    $adjustmentClass = new stdClass();
    $adjustmentClass->mode = 'w'; //adjust by word
    ?>
      <script type="text/javascript">
          function editPeriod(schedulePeriodID) {
              global_popup_iframe('mth_schedule_period-edit', '/student/<?= $student->getSlug() ?>/schedule/period?schedule_period=' + schedulePeriodID);
          }

          function changeSecond(schedulePeriodID, duplicateOnly) {
              global_popup_iframe('mth_schedule_period-edit', '/student/<?= $student->getSlug() ?>/schedule/2nd-sem-change?schedule_period=' + schedulePeriodID + '&duplicateOnly=' + (duplicateOnly ? 1 : 0));
          }
      </script>
      <table class="formatted mth_schedule-table" id="mth_schedule-<?= $schedule->id() ?>-table">
        <?php while ($period = mth_period::each($schedule->student_grade_level())) : ?>
          <?php for ($second_semester = 0; $second_semester <= ($schedule->schoolYear()->getSecondSemOpen() < time() ? 1 : 0); $second_semester++) : ?>

            <?php $schedulPeriod = $second_semester
              ? mth_schedule_period::get($schedule, $period, true)
              : mth_schedule_period::create($schedule, $period); ?>

            <?php if ($second_semester && !$schedulPeriod->second_semester()) : ?>
              <?php if ($schedule->second_sem_change_available() && $schedulPeriod->allow_2nd_sem_change()) : ?>
                        <tr>
                            <td></td>
                            <td colspan="99">
                                <a onclick="changeSecond(<?= $schedulPeriod->id() ?>)" class="mth_schedule_second_sem_link">Update for 2nd Semester</a> |
                                <a onclick="changeSecond(<?= $schedulPeriod->id() ?>, true)">No update</a>
                            </td>
                        </tr>
              <?php endif; ?>
              <?php continue;
            endif; ?>
                <tr class="mth_schedule_period-row-<?= $schedulPeriod->require_change() ? 'requires_change' : ($schedulPeriod->require_change_date() ? 'changed' : 'normal') ?>">
                    <td>
                        <h3>
                          <?= $schedulPeriod->period() ?>
                          <?= $schedulPeriod->second_semester() ? '<small>(2nd Sem.)</small>' : '' ?>
                        </h3>
                    </td>
                    <td>
                        <h3><?= $schedulPeriod->subject() ? $schedulPeriod->subject() : $schedulPeriod->period()->label() ?></h3>
                      <?php if ($schedulPeriod->subject()) : ?>
                          <a <?php if ($schedulPeriod->editable()) : ?> onclick="editPeriod(<?= $schedulPeriod->id() ?>);" title="Change Course" <?php else : ?>class="mth_schedule_period-uneditable" <?php endif; ?>>
                            <?= req_sanitize::txt_decode($schedulPeriod->courseName()) ?>
                          </a>
                      <?php elseif ($schedulPeriod->editable()) : ?>
                          <a onclick="editPeriod(<?= $schedulPeriod->id() ?>);" title="Select Course"><?= $schedulPeriod->none() ? 'None' : 'Select Course' ?></a>
                      <?php else : ?>
                          None
                      <?php endif; ?>
                    </td>
                    <td class="mth_schedule-collapse">
                        <h3><?= $schedulPeriod->course_type() ?></h3>
                      <?php if ($schedulPeriod->course_type(true) == mth_schedule_period::TYPE_CUSTOM || ($schedulPeriod->course() && $schedulPeriod->course()->requireDesc($student->getGradeLevelValue($schedule->schoolYear())))) :
                        ?>
                      <?php if ($schedule->isStatus(mth_schedule::STATUS_STARTED) || $schedule->isNewSubmission()  || (!$schedulPeriod->require_change() && !$schedule->isNewSubmission() && $schedulPeriod->require_change_date())) : ?>
                        <?php if (empty($schedulPeriod->template_course_description())) {
                          $string_diff->skipDelete(true);
                          $string_diff->setInsertStyle('font-style: italic;');
                        } else {
                          $string_diff->skipDelete(false);
                          $string_diff->setInsertStyle('background-color: #CCFFCC; color: #757575;');
                          $string_diff->setDeleteStyle('background-color: #FFCCCC; text-decoration: line-through; color: #757575;');
                        }
                        //TODO Find a better way to handle the diff when parent adds something at the end of the template without breaking when the parent does not add anything.
                        $formatSuccessful = $string_diff->FormatDiffAsHtml((substr($schedulPeriod->template_course_description(), -1) != ' ' ? $schedulPeriod->template_course_description() . ' ' : $schedulPeriod->template_course_description())
                          , (substr($schedulPeriod->custom_desc(), -1) != ' ' ? $schedulPeriod->custom_desc() . ' ' : $schedulPeriod->custom_desc()), $adjustmentClass);//The spaces at the end are required to accurately compare the strings.
                        ?>
                          <div><?= core_path::getPath()->isAdmin() && $formatSuccessful ? $adjustmentClass->html : $schedulPeriod->custom_desc() ?></div>

                      <?php else: ?>
                          <div style="max-width: 500px;"><?= $schedulPeriod->custom_desc() ?></div>
                      <?php endif;
                      endif; ?>
                      <?php if ($schedulPeriod->course_type(true) == mth_schedule_period::TYPE_TP) : ?>
                          <div>
                            <?= $schedulPeriod->tp_name() ?><br>
                            <?= $schedulPeriod->tp_course() ?><br>
                            <?= $schedulPeriod->tp_phone() ?><br>
                            <?= $schedulPeriod->tp_website() ?><br>
                            <?= $schedulPeriod->tp_desc() ?>
                          </div>
                      <?php endif; ?>
                      <?php if ($schedulPeriod->course_type(true) == mth_schedule_period::TYPE_MTH && $schedulPeriod->course() && $schedulPeriod->course()->hasProviders($student->getGradeLevelValue($schedule->schoolYear()))) :
                        ?>
                        <?= $schedulPeriod->mth_provider() ?>
                          <div>
                            <?php if ($schedulPeriod->provider_course()) : ?>
                              <?= $schedulPeriod->provider_course(); ?>
                            <?php else : ?>
                              <?= $schedulPeriod->tp_course() ?><br>
                              <?= $schedulPeriod->tp_name() ?><br>
                              <?= $schedulPeriod->tp_district() ?><br>
                              <?= $schedulPeriod->tp_phone() ?>
                            <?php endif; ?>
                          </div>
                      <?php endif; ?>
                    </td>
                  <?php if ($schedule->isAccepted()) : ?>
                      <td>
                        <?php if (core_path::getPath()->isAdmin() && $schedulPeriod->reimbursed(NULL, false)) : ?>
                            <h3>Reimbursed</h3>
                            <div>
                                $<?= $schedulPeriod->reimbursed() ?>
                            </div>
                        <?php else : ?>
                            <h3></h3>
                        <?php endif; ?>
                      </td>
                  <?php endif; ?>
                </tr>
                <!-- <?= $schedulPeriod->id() ?> -->
          <?php endfor;
        endwhile; ?>
      </table>
    <?php
  }

  public static function sendToDropbox(mth_schedule $schedule) {
    $content = self::getPDFcontent($schedule, true);
    $dir_year = $schedule->date_accepted() ? mth_schoolYear::getByStartYear($schedule->date_accepted('Y')) : mth_schoolYear::getCurrent();

    if (!$dir_year) {
      core_notify::addError('Invalid year');
      return FALSE;
    }

    if (!($student = $schedule->student())) {
      core_notify::addError('Student not found.');
      return FALSE;
    }
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';

    $path = '/' . $dir_year . ($inputState == 'OR' ? '/Oregon' : '').'/Schedules/';
    return mth_dropbox::uploadFileFromString($path . ($student->getLastName() . ', ' . $student->getFirstName() . '(' . $student->getID() . ').pdf'), $content);
  }

  public static function getPDFcontent(mth_schedule $schedule, $output = false) {
    ($student = $schedule->student()) || die('Schedule student missing');
    ($parent = $student->getParent()) || die('Student\'s parent missing');
    ob_start();
    ?>
      <!DOCTYPE html>
      <html>

      <head>
          <style>
              body {
                  font-family: 'Open Sans', sans-serif;
                  font-size: 14px;
                  font-weight: 400;
              }

              small,
              th a {
                  color: #999;
                  text-decoration: none;
              }

              hr {
                  border: none;
              }

              table.formatted {
                  border: none;
                  border-collapse: collapse;
              }

              .mth_schedule-table h3 {
                  padding: 5px 10px;
                  margin: -5px -10px 5px;
                  background: #eee;
                  border-top: solid 2px #ddd;
                  border-bottom: solid 1px #ddd;
                  font-size: 1em;
                  height: 30px;
                  line-height: 30px;
                  font-weight: 700;
                  overflow: hidden;
                  text-overflow: ellipsis;
                  white-space: nowrap;
                  color: #666;
              }

              table.mth_schedule-table {
                  width: 100%;
              }

              tr {
                  display: table-row;
                  vertical-align: inherit;
                  border-color: inherit;
              }

              table.mth_schedule-table tr td,
              table.mth_schedule-table tr th {
                  padding: 5px 10px 15px;
                  background-color: #fff;
              }

              table.formatted th {
                  text-align: left;
                  border-bottom: solid 1px #eee;
              }

              .mth_schedule-table td,
              .mth_schedule-table th {
                  vertical-align: top;
              }
          </style>
      </head>

      <body>
      <div>
          <h1>
            <?= $student ?>
              <small><?= $schedule->schoolYear() ?></small>
          </h1>
      </div>
      <div style="overflow: hidden">
          <div style="display:inline-block;width:300px;">
              <h2 style="margin: 0">Student</h2>
              <div>
                  <a>
                    <?= $student ?>
                  </a>
              </div>
              <div><?= $student->getGender() ?></div>
              <div>
                <?= $student->getGradeLevel(true, false, $schedule->schoolYear()); ?>
                  <small>(<?= $schedule->schoolYear() ?>)</small>
              </div>
              <div>Diploma: <?= $student->diplomaSeeking() ? 'Yes' : 'No'; ?></div>
              <div><?= $student->getSchoolOfEnrollment(false, $schedule->schoolYear()) ?></div>
              <div>SPED: <?= $student->specialEd(true) ?></div>
          </div>
          <div style="display:inline-block; width:350px;">
              <h3 style="margin: 0;">Parent</h3>
              <a>
                <?= $parent ?>
              </a>
              <hr style="border-top: 1px dashed #8c8b8b;border-bottom: none">

            <?php if ($enrollment = courses::getStudentHomeroom($student->getID(), $schedule->schoolYear())) : ?>
              <?php $current_year = $schedule->schoolYear(); ?>
              <?php $homeroomgrade = $enrollment->getGrade(); ?>
                <div>Homeroom Grade: <span id="homeroomGradeHolder"><?= is_null($homeroomgrade) ? 'NA' : $homeroomgrade . '%' ?></span>
                  <?php if ($current_year->getFirstSemLearningLogsClose() == $current_year->getLogSubmissionClose()) : ?>
                      <span style="font-size: small;">/ # of Zeros: <?= $enrollment->getStudentHomeroomZeros() ?></span>
                  <?php endif; ?>
                </div>
              <?php if ($current_year->getFirstSemLearningLogsClose() != $current_year->getLogSubmissionClose()) : ?>
                    <div>Homeroom Grade 1st Sem: <span id="homeroomGradeHolder"><?= is_null($enrollment->getGrade(1)) ? 'NA' : $enrollment->getGrade(1) . '%' ?> /</span>
                        <span id="homeroomZeroCountHolder" style="font-size: small;"># of Zeros: <?= $enrollment->getStudentHomeroomZeros(1) ?></span>
                    </div>
                    <div>Homeroom Grade 2nd Sem: <span id="homeroomGradeHolder"><?= is_null($enrollment->getGrade(2)) ? 'NA' : $enrollment->getGrade(2) . '%' ?> /</span>
                        <span id="homeroomZeroCountHolder" style="font-size: small;"># of Zeros: <?= $enrollment->getStudentHomeroomZeros(2) ?></span>
                    </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
      </div>


      <hr style="border-top: 3px double #8c8b8b;">
      <?php mth_views_schedules::entireSchedule($schedule) ?>
      </body>

      </html>
    <?php
    $content = ob_get_contents();
    ob_end_clean();

    $dompdf = new Dompdf();
    $dompdf->load_html($content);
    $dompdf->render();

    if ($output) {
      return $dompdf->output();
    } else {
      $title = str_replace(' ', '-', $student . ' ' . $schedule->schoolYear());
      $dompdf->add_info('Title', $title);
      $dompdf->stream($title . ".pdf", array("Attachment" => 0));
    }
  }
}
