<?php
define('SETTING_CATEGORY', 'parent-link-enrollments');

if (req_get::is_set('person_id')) {
    req_get::bool('canvas_course_id') || die('<span class="error">No course ID set!</span>');
    req_get::bool('year_id') || die('<span class="error">No school year set!</span>');

    ($person = mth_person::getPerson(req_get::int('person_id'))) || die('<span class="error">Person not found!</span>');

    $settingName = 'person_ids-' . req_get::int('canvas_course_id') . '-' . req_get::int('year_id');

    $person_ids_setting = core_setting::get($settingName, SETTING_CATEGORY);
    if ($person_ids_setting) {
        $person_ids = array_unique(explode(';', $person_ids_setting->getValue()));
    } else {
        $person_ids = array();
    }

    $canvas_course = mth_canvas_course::getByID(req_get::int('canvas_course_id'), false);
    $canvas_user = mth_canvas_user::get($person, true);
    if (!$canvas_user->id()) {
        $canvas_user->push() || die('<span class="error">Unable to create canvas user account for ' . $person->getName() . '</span>');
    }
    $enrollment = mth_canvas_enrollment::get($canvas_user, $canvas_course, null, mth_canvas_enrollment::ROLE_STUDENT);
    if (req_get::bool('withdraw') && $enrollment && $enrollment->delete(false)) {
        if (($key = array_search($person->getPersonID(), $person_ids)) !== false) {
            unset($person_ids[$key]);
            core_setting::set($settingName, implode(';', $person_ids), core_setting::TYPE_TEXT, SETTING_CATEGORY);
        }
        exit($person->getName() . ' withdrawn from course ' . req_get::int('canvas_course_id'));
    } elseif (!req_get::bool('withdraw') && $enrollment && $enrollment->create(true)) {
        if (!in_array($person->getPersonID(), $person_ids)) {
            $person_ids[] = $person->getPersonID();
            core_setting::set($settingName, implode(';', $person_ids), core_setting::TYPE_TEXT, SETTING_CATEGORY);
        }
        exit('Enrollment to course ' . req_get::int('canvas_course_id') . ' created for ' . $person->getName());
    }
    if (($key = array_search($person->getPersonID(), $person_ids)) !== false) {
        unset($person_ids[$key]);
        core_setting::set($settingName, implode(';', $person_ids), core_setting::TYPE_TEXT, SETTING_CATEGORY);
    }
    exit('<span class="error">Unable to ' . (req_get::bool('withdraw') ? 'remove' : 'create') . ' enrollment for ' . $person->getName() . '</span>');
}

core_loader::isPopUp();
core_loader::printHeader();
?>
    <script>
        var mth_canvas_progress = {
            onStep: -1,
            progressStep: -1,
            stepCount: null,
            totalProgressSteps: null,
            course_id: null,
            init: function () {
                this.stepCount = parent.selected_person_ids.length - 1;
                this.totalProgressSteps = this.stepCount * 3 + 2;
                this.course_id = parent.courseID.val();
                this.year_id = parent.yearSelect.val();
                this.execute();
            },
            execute: function () {
                mth_canvas_progress.onStep++;
                mth_canvas_progress.progressStep += 2;
                mth_canvas_progress.updateProgressBar();
                var person_id = parent.selected_person_ids[mth_canvas_progress.onStep];
                $.ajax({
                    url: '?person_id=' + person_id + '&canvas_course_id=' + mth_canvas_progress.course_id + '&year_id=' + mth_canvas_progress.year_id<?=req_get::bool('withdraw') ? '+"&withdraw=1"' : ''?>,
                    success: function (data) {
                        mth_canvas_progress.postMessage(data);
                        mth_canvas_progress.progressStep++;
                        mth_canvas_progress.updateProgressBar();
                        if (mth_canvas_progress.onStep < mth_canvas_progress.stepCount) {
                            setTimeout(mth_canvas_progress.execute, 200);
                        }
                    },
                    error: function () {
                        mth_canvas_progress.postMessage('<span class="error">Unable to complete process because of an error!</span>');
                        mth_canvas_progress.complete();
                    }
                });
            },
            updateProgressBar: function () {
                var progress = (this.progressStep / this.totalProgressSteps) * 100;
                if (progress >= 100) {
                    mth_canvas_progress.postMessage('<b>Canvas synchronization completed!</b>');
                    $('#mth_canvas_progress_bar_complete').css('transition', '.5s');
                    setTimeout(mth_canvas_progress.complete, 500);
                }
                $('#mth_canvas_progress_bar_complete').css('width', progress + '%');
            },
            complete: function () {
                $('#mth_canvas_progress_close').fadeIn();
                $('#mth_canvas_progress_cancel').hide();
                $('#mth_canvas_progress_bar_complete').css({
                    'background': '#7FA3DB'
                });
            },
            postMessage: function (content) {
                $('#mth_canvas_progress_feedback').append('<p>' + content + '</p>');
            }
        };
        $(function () {
            mth_canvas_progress.init();
        });
    </script>
    <style>
        #mth_canvas_progress_bar {
            margin-top: 30px;
            height: 30px;
            border: solid 2px #7FA3DB;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, .1);
            overflow: hidden;
        }

        #mth_canvas_progress_bar_complete {
            box-shadow: 2px 0 10px rgba(0, 0, 0, .1), inset 2px 2px 10px rgba(0, 0, 0, .3);
            height: 30px;
            background: #7FA3DB url(/_/includes/img/progress.gif);
            opacity: 1;
            transition: width 1s;
        }

        .error {
            color: red;
        }
    </style>
    <div id="mth_canvas_progress_bar">
        <div id="mth_canvas_progress_bar_complete" style="width: 0"></div>
    </div>
    <div id="mth_canvas_progress_feedback">
        <p><b>Enrollment creation started...</b><br><?= mth_canvas::url() ?></p>
    </div>
    <p id="mth_canvas_progress_close" style="display: none;">
        <input type="button" onclick="parent.location.reload(true)" value="Close"><br>
        See the Canvas Management for any errors.
    </p>
    <p id="mth_canvas_progress_cancel">
        <input type="button" onclick="parent.location.reload(true)" value="Cancel"><br>
    </p>

<?php
core_loader::printFooter();