<?php

if (req_get::bool('course')) {
    $course = mth_course::getByID(req_get::int('course'));
    $role = req_get::int('role') ? req_get::int('role') : mth_canvas_enrollment::ROLE_STUDENT;
    if (!$course) {
        exit('No course found with the ID ' . req_get::int('course'));
    }
    if (($success = mth_canvas_enrollment::createCourseEnrollments($course, $role,null,$course->subject()->inPeriod(1))) === TRUE) {
        exit($course->title() . ' enrollments created');
    } else {
        core_loader::redirect('?course=' . req_get::int('course') . '&role=' . req_get::int('role'));
    }
}

req_get::bool('studentCourses') || req_get::bool('observerCourses') || die('No course IDs provided');

core_loader::isPopUp();
core_loader::printHeader();
?>
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
            transition: width 50s;
        }

        .error {
            color: red;
        }
    </style>
    <script>
        var mth_canvas_progress = {
            onStep: 0,
            onRole: <?=mth_canvas_enrollment::ROLE_STUDENT?>,
            stepCount: null,
            studentCourses: <?=json_encode((array)req_get::int_array('studentCourses'))?>,
            observerCourses: <?=json_encode((array)req_get::int_array('observerCourses'))?>,
            progress: 0,
            execute: function () {
                this.stepCount = this.studentCourses.length + this.observerCourses.length;
                if (this.onRole ===<?=mth_canvas_enrollment::ROLE_STUDENT?> && this.studentCourses[this.onStep] !== undefined) {
                    var courseID = this.studentCourses[this.onStep];
                } else {
                    this.onRole = <?=mth_canvas_enrollment::ROLE_OBSERVER?>;
                    var courseID = this.observerCourses[this.onStep - this.studentCourses.length];
                }
                this.onStep++;
                this.updateProgressBar();
                $.ajax({
                    url: '?course=' + courseID + '&role=' + this.onRole,
                    success: function (data) {
                        mth_canvas_progress.postMessage(data);
                        if (mth_canvas_progress.onStep < mth_canvas_progress.stepCount) {
                            setTimeout(function () {
                                mth_canvas_progress.execute();
                            }, 500);
                        } else {
                            mth_canvas_progress.postMessage('<b>Process complete!</b>');
                            $('#mth_canvas_progress_bar_complete').css('transition', '.5s');
                            setTimeout(mth_canvas_progress.complete, 500);
                            $('#mth_canvas_progress_bar_complete').css('width', '100%');
                        }
                    },
                    error: function () {
                        mth_canvas_progress.onStep--;
                        mth_canvas_progress.postMessage('...');
                        mth_canvas_progress.execute();
                    }
                });
            },
            updateProgressBar: function () {
                this.progress = (this.onStep / this.stepCount) * 100;
                if (this.progress >= 100) {
                    this.progress = 95;
                }
                $('#mth_canvas_progress_bar_complete').css('width', this.progress + '%');
            },
            complete: function () {
                $('#mth_canvas_progress_close').fadeIn();
                $('#mth_canvas_progress_bar_complete').css({
                    'background': '#7FA3DB'
                });
            },
            postMessage: function (content) {
                $('#mth_canvas_progress_feedback').append('<p>' + content + '</p>');
            }
        };
        $(function () {
            mth_canvas_progress.execute();
        });
    </script>

    <div id="mth_canvas_progress_bar">
        <div id="mth_canvas_progress_bar_complete" style="width: 0"></div>
    </div>
    <div id="mth_canvas_progress_feedback">
        <p><b>Canvas enrollment creation started...</b><br><?= mth_canvas::url() ?></p>
    </div>
    <p id="mth_canvas_progress_close" style="display: none">
        <input type="button" onclick="parent.location.reload(true)" value="Close"><br>
        See the Canvas Management for any errors.
    </p>
<?php
core_loader::printFooter();