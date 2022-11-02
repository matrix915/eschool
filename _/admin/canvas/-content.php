<?php

if (req_get::bool('canvasErrorFlag')) {
    if (($canvas_error = mth_canvas_error::getByID(req_get::int('canvasErrorFlag')))) {
        $canvas_error->flag(true);
    }
    exit('flagged');
}
if (req_get::bool('canvasErrorUnFlag')) {
    if (($canvas_error = mth_canvas_error::getByID(req_get::int('canvasErrorUnFlag')))) {
        $canvas_error->flag(false);
    }
    exit('un-flagged');
}
if (req_get::bool('canvasErrorsClear')) {
    if (!mth_canvas_error::clear()) {
        core_notify::addError('Unable to clear errors!');
    }
    core_loader::redirect();
}

if (req_get::bool('flush')) {
    mth_canvas_user::flush();
    mth_canvas_enrollment::flush();
    mth_canvas_course::flush();
    exit(1);
}


if (!mth_canvas_user::count(NULL, false)) {
    core_notify::addError('There are no canvas users in the database. Either there are no active students, or you need to run a <a onclick=\"sync()\">Sync</a>!');
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Canvas Management');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>

<?php if (mth_canvas_error::count()): ?>
    <div class="core-notify-erros" style="padding: 1px 5% 20px;">
        <h2>Canvas API Errors</h2>
        <script>
            function mth_canvas_error_flag(canvas_error_id) {
                var flag = $('#mth_canvas_error-flag-' + canvas_error_id);
                if (flag.is('.flagged')) {
                    flag.removeClass('flagged');
                    $.get('?canvasErrorUnFlag=' + canvas_error_id);
                } else {
                    flag.addClass('flagged');
                    $.get('?canvasErrorFlag=' + canvas_error_id);
                }
            }
        </script>
        <style>
            .mth_canvas_error-flag {
                display: block;
                width: 10px;
                height: 10px;
                margin: auto;
                border-radius: 10px;
                border: solid 1px #36b;
            }

            .mth_canvas_error-flag:hover {
                background: rgba(51, 102, 187, .5);
            }

            .mth_canvas_error-flag.flagged {
                background: #36b;
            }

            .mth_schedule-collapse > div {
                max-width: 100%;
            }

            #main-content {
                overflow: auto;
            }

            .pre{
                border:none !important;
            }
        </style>
        
        
        <table class="formatted">
            <tr>
                <th></th>
                <th>Time</th>
                <th>Message</th>
                <th>Command</th>
                <th>Posted Data</th>
                <th>Full Response</th>
            </tr>
            <?php while ($canvas_error = mth_canvas_error::each()): ?>
                <tr>
                    <td>
                        <a onclick="mth_canvas_error_flag(<?= $canvas_error->id() ?>)"
                           id="mth_canvas_error-flag-<?= $canvas_error->id() ?>"
                           class="mth_canvas_error-flag <?= $canvas_error->flag() ? 'flagged' : '' ?>"
                           title="<?= $canvas_error->flag() ? 'Flagged' : 'Click to Flag' ?>"></a>
                    </td>
                    <td><?= $canvas_error->time('m/d/y g:i A') ?></td>
                    <td><?= $canvas_error->message() ?></td>
                    <td><?= $canvas_error->command() ?></td>
                    <td class="mth_schedule-collapse">
                        <div>
                            <div><?= $canvas_error->print_post_fields() ?></div>
                        </div>
                    </td>
                    <td class="mth_schedule-collapse">
                        <div>
                            <div><?php $canvas_error->print_response() ?></div>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <p><a href="?canvasErrorsClear=1">Clear Un-Flagged Errors</a></p>
    </div>
<?php endif; ?>
    <style>
        .mth_canvas_course-unpublished td {
            color: #999;
        }
    </style>
    <script>
        $(function () {
            $('#mth_canvas_course-table').dataTable({
                'aoColumnDefs': [{"bSortable": false, "aTargets": [3, 4]}],
                "bStateSave": true,
                "bPaginate": false,
                "aaSorting": [[0, 'asc'], [1, 'asc']]
            });
        });

        function sync() {
            global_popup_iframe('mth_canvas-update-popup', '/_/admin/canvas/sync');
        }

        function sync_course_only() {
            global_popup_iframe('mth_canvas-update-popup', '/_/admin/canvas/flush-no-sync');
        }

        function sync_analytics(){
            global_popup_iframe('mth_canvas-analytics-popup', '/_/admin/canvas/analytics');
        }

        function flush() {
            $.ajax({
                url: '?flush=1',
                success: function () {
                    toastr.success('User ID and Enrollment ID caches have been flushed. Starting Sync...');
                    setTimeout(sync, 1000);
                }
            });
        }

        function flush_only(){
            $.ajax({
                url: '?flush=1',
                success: function () {
                    toastr.success('User ID and Enrollment ID caches have been flushed. Starting Sync...');
                    setTimeout(sync_course_only, 1000);
                }
            });
        }

        function createEnrollments() {
            var studentCourses = $('.studentCB:checked');
            var observerCourses = $('.observerCB:checked');
            if (studentCourses.length < 1
             && observerCourses.length < 1
            ) {
                swal('','Please check at least one checkbox.','warning');
                return;
            }
            global_popup_iframe(
                'mth_canvas_enrollments-create_by_course-popup',
                '/_/admin/canvas/create-enrollments?' +
                (studentCourses.length > 0 ? studentCourses.serialize() : '') 
                + (observerCourses.length > 0 ? '&' + observerCourses.serialize() : '')
            );
        }
    </script>
    <div class="card">
        <div class="card-block higlight-links">
        <a onclick="sync()">Sync</a>
        <a onclick="swal('Sync','This will download and match up terms, courses, users, and enrollments. Nothing will be created or deleted.','info')">(?)</a>
        |
        <a onclick="flush()">Flush & Sync</a>
        <a onclick="swal('Flush & Sync','This will delete the user id and enrollment id caches, and then run the Sync. Users will be re-matched based on email address.','info')">(?)</a>
        |
        <a onclick="flush_only()">Flush ONLY</a>
        <a onclick="swal('Flush ONLY','This will delete the user id and enrollment id caches, sync courses mapping only.','info')">(?)</a>
        |
        <a onclick="sync_analytics()">Sync Analytics</a>
        <a onclick="swal('Sync Analytics','This will download students summaries analytics. eg. tardiness breakdown (late,missing assignments).','info')">(?)</a>
        </div>
    </div
<?= mth_canvas::url() ?><br>
    <div class="card">
        <div class="card-block">
            <div class="row">
                <table id="mth_canvas_course-table" class="table responsive">
                    <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Course</th>
                        <th>Approved Schedules</th>
                        <th style="text-align: center; padding-right: 10px;">
                            Create Student Enrollments<br>
                            <input type="checkbox" id="studentMasterCB"
                                onclick="$('.studentCB').prop('checked', studentMasterCBChecked = !window.studentMasterCBChecked)">
                        </th>
                        <th style="text-align: center; padding-right: 10px;">
                            Create Observer Enrollments<br>
                            <input type="checkbox" id="observerMasterCB"
                                onclick="$('.observerCB').prop('checked', observerMasterCBChecked = !window.observerMasterCBChecked)">
                        </th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($course = mth_canvas_course::each()): ?>
                        <tr class="mth_canvas_course-<?= $course->workflow_state() ?>">
                            <!-- canvas_course_id: <?= $course->id() ?> - mth_course_id: <?= $course->mth_course_id() ?> -->
                            <td><?= $course->mth_course()->subject() ?></td>
                            <td><?= $course->mth_course()->title() ?></td>
                            <td><?= $course->mth_course()->getID() ? mth_schedule_period::countWithCourse($course->mth_course(), NULL, array(mth_schedule::STATUS_ACCEPTED)) : 'Run Flush & Sync' ?></td>
                            <td style="text-align: center">
                                <?php if ($course->isAvailable()): ?>
                                    <input type="checkbox" name="studentCourses[]" value="<?= $course->mth_course_id() ?>"
                                        class="studentCB">
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center">
                                <?php if ($course->isAvailable()): ?>
                                    <input type="checkbox" name="observerCourses[]" value="<?= $course->mth_course_id() ?>"
                                        class="observerCB">
                                <?php endif; ?>
                            </td>
                            <td><?= $course->workflow_state() ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary btn-round" onclick="createEnrollments()">Create Selected Course Enrollments</button>
            </div>
        </div>
    </div>
    
    <p class="infoBox">This table only shows courses that have Canvas courses with matching SIS IDs.
        Get the course SIS IDs from the <a href="/_/admin/courses" target="_blank">Course Management page</a>.
        They will look something like this: <?= mth_schoolYear::getCurrent()->getStartYear() ?>-78.
        Enter these IDs into Canvas on the corresponding Canvas course Settings><b>SIS ID</b> field.
        Then click <a onclick="sync()">Sync</a> to update the courses mapping in infocenter.
    </p>
    <hr>
    <p class="higlight-links">
        <a onclick="global_popup_iframe('SSOpopup','/_/cas/toggle-canvas-authorization');">Toggle Single Sign On</a><br>
        <b>Warning:</b> Toggling Single Sign On will affect how users sign into canvas.
        They may be unable to sign in unless they have the proper user accounts.
    </p>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');