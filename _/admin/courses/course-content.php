<?php

if (req_get::bool('course_id')) {
    ($course = mth_course::getByID(req_get::int('course_id'))) || die('course not found');

    if (req_get::bool('delete') && $course->delete()) {
        core_notify::addMessage('Course Deleted');
        die('<html><head><script>top.location.reload(true);</script></head></html>');
    }
} else {
    $course = new mth_course();
}

$minAsInteger = $course->alternativeMinGradeLevel() == 'K' ? 0 : ( $course->alternativeMinGradeLevel() == 'OR K' ? -1 : $course->alternativeMinGradeLevel());
$altMinAsInteger = $course->alternativeMinGradeLevel() == 'K' ? 0 : ( $course->alternativeMinGradeLevel() == 'OR K' ? -1 : $course->alternativeMinGradeLevel());
$combinedMinGrade = $minAsInteger < $altMinAsInteger ? $minAsInteger : $altMinAsInteger;

$maxAsInteger = $course->alternativeMaxGradeLevel() == 'K' ? 0 : ( $course->alternativeMaxGradeLevel() == 'OR K' ? -1 : $course->alternativeMaxGradeLevel());
$altMaxAsInteger = $course->alternativeMaxGradeLevel() == 'K' ? 0 : ( $course->alternativeMaxGradeLevel() == 'OR K' ? -1 : $course->alternativeMaxGradeLevel());
$combinedMaxGrade = $maxAsInteger < $altMaxAsInteger ? $maxAsInteger : $altMaxAsInteger;

if (req_get::bool('form')) {
    $minGradeLevel = (req_post::txt('min_grade_level') == 'OR-K') ? -1 : (req_post::txt('min_grade_level') == 'K' ? 0 : req_post::txt('min_grade_level'));
    $maxGradeLevel = (req_post::txt('max_grade_level') == 'OR-K') ? -1 : (req_post::txt('max_grade_level') == 'K' ? 0 : req_post::txt('max_grade_level'));

    $alternative_min_grade_level = (req_post::txt('alternative_min_grade_level') == 'OR-K') ? -1 : (req_post::txt('alternative_min_grade_level') == 'K' ? 0 : req_post::txt('alternative_min_grade_level'));
    $alternative_max_grade_level = (req_post::txt('alternative_max_grade_level') == 'OR-K') ? -1 : (req_post::txt('alternative_max_grade_level') == 'K' ? 0 : req_post::txt('alternative_max_grade_level'));
    core_loader::formSubmitable(req_get::txt('form')) || die();

    $course->subjectID(req_post::int('subject_id'));
    $course->title(req_post::txt('title'));
    $course->allowOtherMTHproviders(req_post::bool('allow_other_mth'));
    $course->allowCustom(req_post::bool('allow_custom'));

    $course->customCourseDescription(req_post::bool('allow_custom') ? req_post::txt('custom_course_description') : '');
    $course->allowTP(req_post::bool('allow_tp'));
    $course->minGradeLevel($minGradeLevel);
    $course->maxGradeLevel($maxGradeLevel);
    $course->alternativeMinGradeLevel($alternative_min_grade_level);
    $course->alternativeMaxGradeLevel($alternative_max_grade_level);
    $course->diploma_valid(req_post::bool('diploma_valid'));
    $course->available(req_post::bool('available'));
    $course->set_allow_2nd_sem_change(req_post::int_array('allow_2nd_sem_change'));
    $course->isLaunchpadCourse(isset($_POST['launchpadCourse']));
    $course->sparkCourseId($_POST['sparkCourseId']);

    $stateCodes = req_post::txt_array('state_code');
    $teacherNames = req_post::txt_array('teacher');
    $combinedCodes = [];
    for ($i = $combinedMinGrade; $i <= $combinedMaxGrade; $i++) {
        $combinedCodes[$i] = [
            'state_code' => $stateCodes[$i],
            'teacher_name' => $teacherNames[$i]
        ];
    }
    $course->stateCodes($combinedCodes);

    if($course->archived(req_post::bool('archived'))) {
        $course->available(false);
    }

    if(req_post::bool('allowance')){
        $course->allowance(req_post::float('allowance'));
    }

    if ($course->save()) {
        core_notify::addMessage('Course Saved');
        core_loader::reloadParent();
    } else {
        core_notify::addError('Unable to save course!');
        core_loader::redirect(req_post::txt('button') == 'Save/New'
            ? '?subject=' . $course->subjectID()
            : '?course_id=' . $course->getID());
    }
}

core_loader::isPopUp();
core_loader::printHeader();

$selected_subject = null;
?>
    <script>
        function deleteCourse() {
            swal({
                title: "",
                text: "Are you sure you want to delete this course? This action cannot be done.",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-primary",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function () {
                    location.href = '?course_id=<?=$course->getID()?>&delete=1';
            });
        }
        function showArchivedWarning () {
          if ($('#archived').is(':checked')) {
            $('#archived_warning').show()
          } else {
            $('#archived_warning').hide()
          }
        }

    </script>
    <button  type="button"  class="iframe-close btn btn-secondary btn-round" onclick="top.location.reload(true)">Close</button>
    <h2><?= $course->getID() ? 'Edit' : 'New' ?> Course</h2>
<?php if ($course->getID()): ?>
    <small style="color: #999; margin-top: -20px; display: block">
        <?= mth_schoolYear::getCurrent() ? mth_schoolYear::getCurrent()->getStartYear() . '-' . $course->getID() : '' ?>
    </small>
    <p>
        Changes to this course will be reflected in any schedule with this course.
    </p>
<?php endif ?>
    <form action="?form=<?= uniqid('mth_course-form') ?><?= $course->getID() ? '&course_id=' . $course->getID() : '' ?>"
          method="post">
          <div class="card">
            <div class="card-block">
                <div class="form-group">
                    <label>
                    Subject
                    </label>
                    <select id="subject_id" name="subject_id" required class="form-control">
                        <option></option>
                        <?php while ($subject = mth_subject::getEach()): ?>
                        <?php $selected_subject = is_null($selected_subject) && ($course->subjectID() == $subject->getID() || req_get::int('subject') == $subject->getID())?strtolower($subject->getName()):$selected_subject ?>
                            
                            <option value="<?= $subject->getID() ?>"
                                <?= $course->subjectID() == $subject->getID() || req_get::int('subject') == $subject->getID() ? 'selected' : '' ?>><?= $subject->getName() ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="">Title</label>
                    <input type="text" name="title" value="<?= $course->title() ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="">Minimum Grade Level</label>
                    <select name="min_grade_level" id="min_grade_level" class="form-control">
                        <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                            <option value="<?= $grade_level ?>"
                                <?= $course->minGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="">Maximum Grade Level</label>
                    <select name="max_grade_level" id="max_grade_level" class="form-control">
                        <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                            <option value="<?= $grade_level ?>"
                                <?= $course->maxGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="">Alternative Minimum Grade Level</label>
                    <select name="alternative_min_grade_level" id="alternative_min_grade_level" class="form-control">
                        <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                            <option value="<?= $grade_level ?>"
                                <?= $course->alternativeMinGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="">Alternative Maximum Grade Level</label>
                    <select name="alternative_max_grade_level" id="alternative_max_grade_level" class="form-control">
                        <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                            <option value="<?= $grade_level ?>"
                                <?= $course->alternativeMaxGradeLevel() == $grade_level ? 'selected' : '' ?>><?= $grade_desc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if($selected_subject == 'tech'):?>
                <div class="form-group">
                    <label for="">Allowance ($)</label>
                    <input type="text" name="allowance" value="<?= $course->allowance() ?>" class="form-control" required>
                </div>
                <?php endif;?>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="diploma_valid" id="diploma_valid" value="1"
                            <?= $course->diploma_valid() ? 'checked' : '' ?>>
                    <label for="diploma_valid" >
                        Available for Diploma-seeking Students
                    </label>
                </div>
               
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="allow_other_mth" id="allow_other_mth" value="1"
                            <?= $course->allowOtherMTHproviders() ? 'checked' : '' ?>>
                    <label for="allow_other_mth" >
                        Allow Other MTH Providers: Allow non-mapped providers to be displayed for selection.
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="allow_custom" id="allow_custom" value="1"
                    <?= $course->allowCustom() ? 'checked' : '' ?>>
                    <label for="allow_custom">
                        Allow parents to custom-build this course.
                    </label>
                </div>
                <div class="form-group" id="custom_course_description_cont" <?= $course->allowCustom() ? '' : 'style="display:none"' ?>>
                    <label>Custom-built Course Description</label>
                    <textarea class="form-control" name="custom_course_description" id="custom_course_description"><?= $course->customCourseDescription() ?></textarea>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="allow_tp" id="allow_tp" value="1"
                            <?= $course->allowTP() ? 'checked' : '' ?>>
                    <label for="allow_tp" >
                        Allow the parent to enter a 3rd Party Provider for this course.
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <strong>2nd Semester</strong>
                        <!-- <small>(grades 9-12)</small> -->
                        <small>If this is the course, allow parents to change the following periods:</small>
                    </label>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="allow_2nd_sem_change[]" id="allow_2nd_sem_change-5" value="5"
                                <?= $course->allow_2nd_sem_change(5) ? 'checked' : '' ?> >
                        <label for="allow_2nd_sem_change-5" >
                            Period 5
                        </label>
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="allow_2nd_sem_change[]" id="allow_2nd_sem_change-6" value="6"
                                <?= $course->allow_2nd_sem_change(6) ? 'checked' : '' ?> >
                        <label for="allow_2nd_sem_change-6">
                            Period 6
                        </label>
                    </div>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="available" id="available" value="1"
                            <?= $course->available() ? 'checked' : '' ?>>
                    <label for="available">
                        This course is available for parents to select.
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="archived" id="archived" value="1"
                           onchange="showArchivedWarning()"
                        <?= $course->archived() ? 'checked' : '' ?>>
                    <label for="archived">
                        Archived
                    </label>
                </div>
                <div class="row input-group" style="margin: -11px 0px 0px 0px;">
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="launchpadCourse" id="launchpadCourse" value="1"
                            <?= $course->isLaunchpadCourse() ? 'checked' : '' ?>>
                        <label for="launchpadCourse">
                            Launchpad Course
                        </label>
                    </div>
                    <div class="spark-course-id" style="<?= !$course->isLaunchpadCourse() ? 'display: none;' : '' ?>">
                        <input class="form-control" 
                            style="width: 100%;margin: 0rem 1rem;" 
                            value="<?= $course->sparkCourseId() ?>" 
                            type="text" 
                            name="sparkCourseId" 
                            id="sparkCourseId" 
                            <?= $course->isLaunchpadCourse() ? 'required=true' : '' ?>>
                    </div>
                </div>
                <div id="archived_warning" style="<?= 'color: orange; display: ' . ($course->archived() ? 'block;' : 'none;')?>">
                    <?php if (count($courses = $course->getLinkedProviderCourses()) > 0): ?>
                    <strong>The following provider courses are linked to this course:</strong>
                    <ul>
                        <?php foreach($courses as $pc): ?>
                            <li><?= $pc->title ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif ?>
                </div>
                <div class="higlight-links">
                    <a id="state_course_code">State Course Code</a>
                </div>
                <div id="course_codes" class="container" style="display:none;">
                    <div class="row">
                        <div class="col-sm-3"></div>
                        <div class="col-sm text-center bold-text">State Code</div>
                        <div class="col-sm text-center bold-text">Teacher</div>
                    </div>

                    <?php
                    $stateCodesByGrade = $course->stateCodes();
                    for($i = $combinedMinGrade; $i <= $combinedMaxGrade; $i++): ?>
                        <div class="row">
                            <div class="col-sm-3" for="<?= 'state_code_'.$i ?>>"><?= $i == 0 ? 'Kindergarten' : 'Grade ' . $i ?></div>
                            <input class="col-sm form-control" type="text" id="<?= 'state_code_'.$i ?>" name="<?= 'state_code[' . $i . ']' ?>"
                                   value="<?= array_key_exists($i, $stateCodesByGrade) ? $stateCodesByGrade[$i]['code'] : '' ?>">
                            <input class="col-sm form-control" type="text" id="<?= 'teacher'.$i ?>" name="<?= 'teacher[' . $i . ']' ?>"
                                   value="<?= array_key_exists($i, $stateCodesByGrade) ? $stateCodesByGrade[$i]['teacher'] : '' ?>">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="card-footer">
                <button  type="submit" name="button" class="btn btn-round btn-primary" value="Save">Save</button>
                <button  type="submit" name="button" class="btn btn-round btn-success" value="Save/New" title="save and open empty form for new course">Save/New</button>
                <button type="button" class="btn btn-round btn-danger"  onclick="deleteCourse()">Delete</button>
            </div>
          </div>
    </form>
    <script>
        $('#launchpadCourse').click(function() {
            if( $('#launchpadCourse').is(':checked') ){
                $('#sparkCourseId').attr('required', true); 
                $('.spark-course-id').show();
            }
            else{
                $('#sparkCourseId').attr('required', false); 
                $('#sparkCourseId').val('');
                $('.spark-course-id').hide();
            }
        });

        $('#allow_custom').change(function() {
            if ($(this).is(':checked')) {
                $('#custom_course_description_cont').fadeIn();
            } else {
                $('#custom_course_description_cont').fadeOut();
            }
        });

        $('#state_course_code').click(function() {
          var codesSection = $('#course_codes')[0]
          if (codesSection.style.display === 'none') {
            $('#course_codes').show();
          } else {
            $('#course_codes').hide();
          }
        });

    </script>
<?php
core_loader::printFooter();