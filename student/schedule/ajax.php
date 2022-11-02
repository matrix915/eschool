<?php
/* @var $parent mth_parent */
/* @var $student mth_student */


if (req_post::bool('schedule_period_fields')) {
    (($schedulePeriod = mth_schedule_period::getByID(req_post::int('schedule_period_fields')))
        && ($schedulePeriod->schedule()->student() == $student))
    || die('Schedule Period Not Found');

    $prev = &$_SESSION['mth_schedule_period-form-previous'][$schedulePeriod->id()];

    if (!isset($prev)) {
        $prev = array(
            'subject_id' => $schedulePeriod->subject_id(),
            'course_id' => $schedulePeriod->course_id(),
            'course_type' => $schedulePeriod->course_type(true),
            'mth_provider_id' => $schedulePeriod->mth_provider_id(),
            'provider_course_id' => $schedulePeriod->provider_course_id(),
            'tp_name' => $schedulePeriod->tp_name(),
            'tp_district' => $schedulePeriod->tp_district(),
            'tp_website' => $schedulePeriod->tp_website(),
            'tp_phone' => $schedulePeriod->tp_phone(),
            'tp_course' => $schedulePeriod->tp_course(),
            'tp_desc' => $schedulePeriod->tp_desc(NULL, false),
            'custom_desc' => $schedulePeriod->custom_desc(NULL, false),
            'template_course_description' => $schedulePeriod->template_course_description(NULL, false),
            'allow_above_max_grade_level' => $schedulePeriod->allow_above_max_grade_level(),
            'allow_below_min_grade_level' => $schedulePeriod->allow_below_min_grade_level(),
        );
    }
    $_POST = $_POST + $prev;

    $_SESSION['allow_above_max_grade_level'] = !empty($_POST['allow_above_max_grade_level']);
    $_SESSION['allow_below_min_grade_level'] = !empty($_POST['allow_below_min_grade_level']);

    if (req_post::txt('subject_id') != $prev['subject_id']) {
        $prev['course_id'] = $_POST['course_id'] = NULL;
    }
    if (!req_post::bool('course_id') || $prev['course_id'] != req_post::txt('course_id')) {
        $prev['course_type'] = $_POST['course_type'] = $_POST['tp_name'] = $_POST['tp_district'] = $_POST['tp_website']
            = $_POST['tp_phone'] = $_POST['tp_course'] = $_POST['tp_desc'] = $_POST['custom_desc'] =$_POST['template_course_description'] = NULL;
    }
    if (!req_post::bool('course_type') || $prev['course_type'] != req_post::int('course_type')) {
        $prev['mth_provider_id'] = $_POST['mth_provider_id'] = NULL;
    }
    if (!req_post::bool('mth_provider_id') || $prev['mth_provider_id'] != req_post::int('mth_provider_id')) {
        $prev['provider_course_id'] = $_POST['provider_course_id'] = NULL;
    }

    ($subject = mth_subject::getByID(req_post::int('subject_id'))) || exit('');

    if (req_post::bool('get_subject_options') && $subject) {
        $_POST['get_course_options'] = 1;
        if (count($courses = mth_course::getAll($subject, $schedulePeriod->schedule()->student_grade_level(), $schedulePeriod->provisional_provider_id())) == 1) {
            $_POST['course_id'] = key($courses);
        }
        ob_start();
        ?>
        <div class="col-md-6">
            <!-- <p><?= $subject->getDesc() ?></p> -->
            <?php if (in_array($subject->getName(), ['Tech', 'Entrepreneurship'])) : ?>
                <?= cms_page::getDefaultPageContent('Tech and Entrepreneurship Course Instructions', cms_content::TYPE_HTML) ?>
            <?php else : ?>
                <?= cms_page::getDefaultPageContent('Course Instructions', cms_content::TYPE_HTML) ?>
            <?php endif; ?>
            <label><input type="checkbox" name="allow_above_max_grade_level" id="allow_above_max_grade_level" onchange="schedulePeriod.allow_above_max_grade_level()"
                    <?= !empty($_POST['allow_above_max_grade_level']) ? 'CHECKED' : '' ?>>
                Show courses above grade level
            </label>
            <br>
            <label><input type="checkbox" name="allow_below_min_grade_level" id="allow_below_min_grade_level" onchange="schedulePeriod.allow_below_min_grade_level()"
                    <?= !empty($_POST['allow_below_min_grade_level']) ? 'CHECKED' : '' ?>>
                Show courses below grade level
            </label>
            <?php if (mth_course::getCount($subject, $schedulePeriod->schedule()->student_grade_level(), $schedulePeriod->provisional_provider_id()) > 0) : ?>

                <div id="course-fields" class="course-fields form-group">
                    <label for="course_id">Course</label>
                    <select name="course_id" required id="course_id" class="course_id-select form-control"
                            onchange="schedulePeriod.get_course_options()">
                        <?php if (mth_course::getCount($subject, $schedulePeriod->schedule()->student_grade_level(), $schedulePeriod->provisional_provider_id()) > 1) : ?>
                            <option></option>
                        <?php endif; ?>
                        <?php while ($course = mth_course::getEach($subject, $schedulePeriod->schedule()->student_grade_level(), false, $schedulePeriod->provisional_provider_id())) : ?>
                            <?php
                            if ($schedulePeriod->schedule()->applyDiplomaSeekingLimits()
                                && !$course->availableToDiplomaStudents($schedulePeriod->schedule()->student_grade_level())
                            ) {
                                continue;
                            }
                            if ($schedulePeriod->schedule()->student_grade_level() == 'K' && $course->maxGradeLevel() == 'OR K') {
                                continue;
                            }

                            $scheduleGradeLevel = $schedulePeriod->schedule()->student_grade_level() == 'K'  ? 0 : ( $schedulePeriod->schedule()->student_grade_level() == 'OR-K' ? -1 : $schedulePeriod->schedule()->student_grade_level() );
                            $alternativeStyling = ($scheduleGradeLevel > ($course->maxGradeLevel() == 'K' ? 0 : ( $course->maxGradeLevel() == 'OR K' ? -1 : $course->maxGradeLevel()))) ||
                                ($scheduleGradeLevel < ($course->minGradeLevel() == 'K' ? 0 : ($course->minGradeLevel() == 'OR K' ? -1 : $course->minGradeLevel()))) ? true : false;
                            ?>

                            <option value="<?= $course->getID() ?>" <?= req_post::txt('course_id') == $course->getID() ? 'selected' : '' ?>
                            style="<?= $alternativeStyling ? 'color: orange;' : '' ?>">
                                <?= ($alternativeStyling ? '* ' : '') . $course ?> 
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <div id="mth_schedule-mth_course-options">[:mth_schedule-mth_course-options:]</div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
    }

    if (!isset($course)) {
        $course = mth_course::getByID(req_post::txt('course_id'));
    }

    if (req_post::bool('get_course_options')) {
        $thisContent = '';
        if (
            $course
            && ($course->requireTP($schedulePeriod->schedule()->student_grade_level())
                || $course->requireDesc($schedulePeriod->schedule()->student_grade_level()))
        ) {
            if ($course->requireTP($schedulePeriod->schedule()->student_grade_level())) {
                $thisContent .= '[:mth_schedule-tp-fields:]';
                $_POST['get_tp_fields'] = 1;
            }
            if ($course->requireDesc($schedulePeriod->schedule()->student_grade_level())) {
                $thisContent .= '[:mth_schedule-custom_desc-fields:]';
                $_POST['get_custom_desc_fields'] = 1;
            }
        } elseif (($subject->showProviders() || ($course && $course->hasProviders()))
            && (mth_course::getCount($subject, $schedulePeriod->schedule()->student_grade_level(), $schedulePeriod->provisional_provider_id()) == 0
                || $course)
        ) {
            $_POST['get_course_type_options'] = 1;
            if ($course) {
                $course_types = $course->getCourseTypeOptions(
                    $schedulePeriod->schedule()->student_grade_level(),
                    $schedulePeriod->schedule()->applyDiplomaSeekingLimits()
                );
            } else {
                $course_types = mth_schedule_period::course_type_options();
            }

            if (count($course_types) == 1) {
                $_POST['course_type'] = key($course_types);
            }
            if ($provisionalId = $schedulePeriod->provisional_provider_id()) {
                $_POST['course_type'] = 1;
            }

            ob_start();
            ?>
            <div id="course_type-fields" class="form-group">
                <label for="course_type">Course Type</label>
                <select name="course_type" required id="course_type" class="course_type-select form-control"
                        onchange="schedulePeriod.get_course_type_options()">
                    <?php if (count($course_types) > 1) : ?>
                        <option></option>
                    <?php endif;

                    foreach ($course_types as $typeID => $type) :
                        if ($provisionalId && $typeID != 1) {
                            continue;
                        }
                        ?>
                        <option value="<?= $typeID ?>" <?= req_post::txt('course_type') == $typeID ? 'selected' : '' ?>><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="mth_schedule-course_type-options">[:mth_schedule-course_type-options:]</div>
            <?php
            $thisContent = ob_get_contents();
            ob_end_clean();
        } else {
            $thisContent = '';
        }
        if (isset($content)) {
            $content = str_replace('[:mth_schedule-mth_course-options:]', $thisContent, $content);
        } else {
            $content = $thisContent;
        }
    }

    if (req_post::bool('get_course_type_options')) {
        $thisContent = '';
        if (req_post::txt('course_type') == mth_schedule_period::TYPE_TP) {
            $thisContent .= '[:mth_schedule-tp-fields:]';
            $_POST['get_tp_fields'] = 1;
        } elseif (req_post::txt('course_type') == mth_schedule_period::TYPE_CUSTOM) {
            $thisContent .= '[:mth_schedule-custom_desc-fields:]';
            $_POST['get_custom_desc_fields'] = 1;
        } elseif (
            mth_provider::count($schedulePeriod->schedule()->student_grade_level(), $course) > 0
            && req_post::txt('course_type') == mth_schedule_period::TYPE_MTH
        ) {
            if (count($providers = mth_provider::all($schedulePeriod->schedule()->student_grade_level(), $course)) == 1) {
                $_POST['mth_provider_id'] = key($providers);
            }
            if ($provisionalId = $schedulePeriod->provisional_provider_id()) {
                $_POST['mth_provider_id'] = $provisionalId;
            }

            $_POST['get_provider_options'] = 1;
            ob_start();
            ?>
            <div id="mth_provider_id-fields" class="form-group">
                <label for="mth_provider_id">Provider</label>
                <select name="mth_provider_id" required id="mth_provider_id" class="mth_provider_id-select form-control"
                        onchange="schedulePeriod.get_provider_options()"
                        onfocus="schedulePeriod.storePreviousProvider()"
                >
                    <?php if (mth_provider::count($schedulePeriod->schedule()->student_grade_level(), $course, $schedulePeriod->schedule()->applyDiplomaSeekingLimits()) >= 1) : ?>
                        <option></option>
                    <?php endif; ?>
                    <?php while ($provider = mth_provider::each($schedulePeriod->schedule()->student_grade_level(), $course, $schedulePeriod->schedule()->applyDiplomaSeekingLimits())) : ?>
                        <?php
                        $provisionalId = $schedulePeriod->provisional_provider_id();
                        if (($provider->diplomanaOnly() && !$student->diplomaSeeking())
                            || ($provisionalId && ($provisionalId != $provider->id()))
                          /* Below covers a provider that has courses but ONLY has courses for diploma seeking students */
                            || (!$student->diplomaSeeking() && mth_provider_course::countWithoutDiplomaOnly($provider, $course) == 0 && mth_provider_course::count($provider, $course) > 0)
                        ) {
                            continue;
                        }
                        $schedulePeriodGradeLevel = $schedulePeriod->schedule()->student_grade_level() == 'K' ? 0 : ($schedulePeriod->schedule()->student_grade_level() == 'OR-K' ? -1 : $schedulePeriod->schedule()->student_grade_level());
                         $alternativeStyling = ($schedulePeriodGradeLevel > ($provider->max_grade_level() == 'K' ? 0 : ($provider->max_grade_level() == 'OR K' ? -1 : $provider->max_grade_level()))) ||
                            ($schedulePeriodGradeLevel < ($provider->min_grade_level() == 'K' ? 0 : ( $provider->min_grade_level() == 'OR K' ? -1 : $provider->min_grade_level()))) ? true : false;
                        ?>
                        <option value="<?= $provider->id() ?>" <?= req_post::int('mth_provider_id') == $provider->id() ? 'selected' : '' ?>
                            <?= ($provider->requiresMultiplePeriods() &&
                                in_array($schedulePeriod->period()->num(), $provider->multiplePeriods()) &&
                                !$schedulePeriod->provisional_provider_id())
                                ? 'data-ismultiple=true data-periods='. json_encode($provider->multiplePeriods())  : '' ?>
                        style="<?= $alternativeStyling ? 'color: orange;' : '' ?>"
                        ><?= ($alternativeStyling ? '* ' : '') . $provider ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="mth_schedule-provider-options">[:mth_schedule-provider-options:]</div>
            <?php
            $thisContent = ob_get_contents();
            ob_end_clean();
        } else {
            $thisContent = '';
        }
        if (isset($content)) {
            $content = str_replace('[:mth_schedule-course_type-options:]', $thisContent, $content);
        } else {
            $content = $thisContent;
        }
    }

    $provider = NULL;
    if (req_post::bool('mth_provider_id')) {
        $provider = mth_provider::get(req_post::int('mth_provider_id'));
    }

    if (req_post::bool('get_provider_options')) {
        $thisContent = '';
        if ($provider) {
            ob_start();
            if (mth_provider_course::count($provider, $course) > 0) {
                ?>
                <div class="form-group">
                    <label for="provider_course_id">Exact Course Name</label>
                    <select id="provider_course_id" name="provider_course_id" required class="form-control"
                            data-popup="<?= $provider->popup() ? 'true' : 'false' ?>"
                            data-popupcontent="<?= $provider->popup_content() ?>">
                        <option></option>
                        <?php while ($provider_course = mth_provider_course::each($provider, $course)) : ?>
                            <?php if ($provider_course->diplomanaOnly() && !$student->diplomaSeeking()) {
                                continue;
                            } ?>
                            <option value="<?= $provider_course->id() ?>" <?= req_post::int('provider_course_id') == $provider_course->id() ? 'selected' : '' ?>><?= $provider_course ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php
            } else {
                ?>
                <fieldset class="custom-provider_course-fields">
                    <legend>On-site District school
                        <small>(not a Charter school)</small>
                    </legend>
                    <div class="form-group">
                        <label for="tp_name">Name of On-site District school</label>
                        <input type="text" name="tp_name" required class="form-control" id="tp_name"
                               value="<?= req_post::txt('tp_name') ?>">
                    </div>
                    <div class="form-group">
                        <label for="tp_district">Name of School District</label>
                        <select id="tp_district" name="tp_district" required class="form-control">
                            <option></option>
                            <?php foreach (mth_schedule_period::tp_district_options() as $district) : ?>
                                <option <?= req_post::txt('tp_district') == $district ? 'selected' : '' ?>><?= $district ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tp_phone">School's phone number</label>
                        <input type="text" name="tp_phone" required class="form-control" id="tp_phone"
                               value="<?= req_post::txt('tp_phone') ?>">
                    </div>
                    <div class="form-group">
                        <label for="tp_course">Name of Course</label>
                        <input type="text" name="tp_course" required class="form-control" id="tp_course"
                               value="<?= req_post::txt('tp_course') ?>">
                    </div>
                </fieldset>
                <?php
            }
            $thisContent = ob_get_contents();
            ob_end_clean();
        } else {
            $thisContent = '';
        }
        if (isset($content)) {
            $content = str_replace('[:mth_schedule-provider-options:]', $thisContent, $content);
        } else {
            $content = $thisContent;
        }
    }

    if (req_post::bool('get_tp_fields')) {
        ob_start();
        ?>
        <fieldset class="tp-fields">
            <legend>3rd Party Provider</legend>
            <div class="form-group">
                <label for="tp_name">Name of Provider</label>
                <input type="text" name="tp_name" required class="form-control" id="tp_name"
                       value="<?= req_post::txt('tp_name') ?>">
            </div>
            <div class="form-group">
                <label for="tp_course">Name of Course</label>
                <input type="text" name="tp_course" required id="tp_course" class="form-control"
                       value="<?= req_post::txt('tp_course') ?>">
            </div>
            <div class="form-group">
                <label for="tp_phone">Phone</label>
                <input type="text" name="tp_phone" required id="tp_phone" class="form-control"
                       value="<?= req_post::txt('tp_phone') ?>">
            </div>
            <div class="form-group">
                <label for="tp_website">Website</label>

                <?php
                preg_match_all('/[^,\s\+]+/', req_post::raw('tp_website'), $match);

                $num_rows = count($match[0]);
                do {
                    ?>
                    <div class="input-group mb-3 third-party-sites">
                        <input <?= $num_rows == count($match[0]) ? "required" : "" ?>
                                value="<?= isset($match[0][$num_rows - 1]) ? $match[0][$num_rows - 1] : '' ?>"
                                name="tp_website[]" type="text" class="form-control" placeholder="Link"
                                aria-label="Link" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-success" onclick="addSiteField()" type="button"><i
                                        class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <?php
                    $num_rows--;
                } while ($num_rows > 0);
                ?>
            </div>
            <div class="form-group">
                <label for="tp_desc">Description of Course
                    <small style="display: inline">(optional, 250 characters max)</small>
                </label>
                <textarea name="tp_desc" class="form-control" rows="6"
                          id="tp_desc"><?= req_post::multi_txt('tp_desc') ?></textarea>
            </div>
        </fieldset>
        <?php
        $thisContent = ob_get_contents();
        ob_end_clean();
        if (isset($content)) {
            $content = str_replace('[:mth_schedule-tp-fields:]', $thisContent, $content);
        } else {
            $content = $thisContent;
        }
    }

    if (req_post::bool('get_custom_desc_fields')) {
        $periodCourse = mth_course::getByID($_POST['course_id']);
        ob_start();
        ?>
        <fieldset class="custom_desc-fields">
            <legend>Custom-built</legend>
            <div class="form-group">
                <label for="custom_desc">Provide a general description of the course, including name of curriculum used
                    <small style="display: inline">(500 characters max)</small>
                </label>
                <textarea name="custom_desc" required class="form-control" rows="6"
                          id="custom_desc"><?= req_post::bool('custom_desc') ? req_post::multi_txt('custom_desc') : $periodCourse->customCourseDescription() ?></textarea>
                <input type="hidden" name="template_course_description" id="template_course_description" value="<?= req_post::bool('template_course_description') ? req_post::multi_txt('template_course_description') : $periodCourse->customCourseDescription() ?>"/>
            </div>
        </fieldset>
        <?php
        $thisContent = ob_get_contents();
        ob_end_clean();
        if (isset($content)) {
            $content = str_replace('[:mth_schedule-custom_desc-fields:]', $thisContent, $content);
        } else {
            $content = $thisContent;
        }
    }

    $prev = array(
        'subject_id' => req_post::txt('subject_id'),
        'course_id' => req_post::txt('course_id'),
        'course_type' => req_post::txt('course_type'),
        'mth_provider_id' => req_post::txt('mth_provider_id'),
        'provider_course_id' => req_post::txt('provider_course_id'),
        'tp_name' => req_post::txt('tp_name'),
        'tp_district' => req_post::txt('tp_district'),
        'tp_website' => req_post::txt('tp_website'),
        'tp_phone' => req_post::txt('tp_phone'),
        'tp_course' => req_post::txt('tp_course'),
        'tp_desc' => req_post::txt('tp_desc'),
        'custom_desc' => req_post::txt('custom_desc'),
        'template_course_description' => req_post::txt('template_course_description'),
    );

    if (!empty($content)){
        echo $content;
    }
}
