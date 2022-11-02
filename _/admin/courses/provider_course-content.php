<?php
 /*used to for reference of the deployment 
 used in line 199*/
 $MTH_SITES = ['CO','UT'];

if (!empty($_GET['course_id'])) {
    $course = mth_provider_course::getByID($_GET['course_id']);
} else {
    $course = new mth_provider_course();
}

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die();

    $course->provider_id($_POST['provider_id']);
    $course->title($_POST['title']);
    $course->mapCourses(!empty($_POST['mth_course_id']) ? $_POST['mth_course_id'] : false);
    $course->available(!empty($_POST['available']));
    $course->diplomanaOnly(!empty($_POST['diploma_only']));
    $course->reduceTechAllowanceFunction(!empty($_POST['reduceTechAllowance']));
    $course->isLaunchpadCourse(isset($_POST['launchpadCourse']));
    $course->sparkCourseId($_POST['sparkCourseId']);

    if ($course->archived(req_post::bool('archived'))) {
        $course->available(false);
    }

    if ($course->save()) {
        core_notify::addMessage('Provider Course Saved');
        core_loader::reloadParent();
    } else {
        core_notify::addError('There were some errors saving this course');
        core_loader::redirect('?course_id=' . $course->id());
    }
}



core_loader::isPopUp();
core_loader::printHeader();
?>
    <style>
        #mth_course_id-list ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /* The main container */
        #mth_course_id-list {
            height: 200px;
            overflow: hidden;
            position: relative;
            margin: 0;
            padding: 0;
            border: solid 1px #ddd;
        }

        /* The main list */
        #mth_course_id-list > ul {
            height: 100%;
            overflow: auto;
        }

        /* Section headers, defined through "headlineSelector" */
        #mth_course_id-list > ul > li strong {
            display: block;
            padding: 5px;
            margin-top: -1px;
            background: #fff;
            background-color: rgba(255, 255, 255, .85);
            border-top: solid 1px #ddd;
            border-bottom: solid 1px #ddd;
        }

        #mth_course_id-list > ul > li > ul {
            padding-bottom: 5px;
        }

        #mth_course_id-list label {
            /* text-indent: -20px;
            margin-left: 30px; */
            margin-left: 10px;
            cursor: pointer;
        }

        #mth_course_id-list label span {
            display: none;
        }

        /* Section headers when "sticky", defined through "stickyClass" */
        #mth_course_id-list > ul > li.sticky strong {
            position: absolute;
            top: 0;
            z-index: 1;
        }

        #mth_course_id-selected-holder {
            color: #999;
            font-size: smaller;
            line-height: 22px;
            margin-bottom: 2px;
        }

        #mth_course_id-selected small {
            display: inline;
        }

        #mth_course_id-selected span {
            white-space: nowrap;
            border: solid 1px #eee;
            padding: 0 2px;
            margin-right: 2px;
            border-radius: 3px;
            cursor: pointer;
        }

    </style>
    
    <button  type="button"  class="iframe-close btn btn-secondary btn-round" onclick="top.location.reload(true)">Close</button>
    <h2><?= $course->id() ? 'Edit' : 'New' ?> Provider Course</h2>
<?php if ($course->id()): ?>
    <p>
        Changes to this course will be reflected in all schedules containing this course.
    </p>
<?php endif; ?>
    <form action="?form=<?= uniqid('mth_provider_course-form') ?>&course_id=<?= $course->id() ?>"
          method="post">
        <div class="card">
            <div class="card-block">
                <div class="form-group">
                    <label for="provider_id">Provider</label>
                    <select id="provider_id" name="provider_id" required class="form-control">
                        <option></option>
                        <?php while ($provider = mth_provider::each()): ?>
                            <option value="<?= $provider->id() ?>"
                                <?= $course->provider() == $provider || @$_GET['provider_id'] == $provider->id() ? 'selected' : '' ?>><?= $provider->name() ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" value="<?= $course->title() ?>" class="form-control" required>
                </div>
                <label for="mth_course_id[]">MTH Course</label>
                <div id="mth_course_id-selected-holder">Selected: <span id="mth_course_id-selected"></span></div>
                <div id="mth_course_id-list">
                    <ul>
                        <?php while ($subject = mth_subject::getEach()):
                            if ($subject->archived()) continue; ?>
                            <li>
                                <strong><?= $subject ?></strong>
                                <ul>
                                    <?php while ($mth_course = mth_course::getEach($subject)):
                                        if ($mth_course->archived()) continue; ?>
                                        <li>
                                            <div class="checkbox-custom checkbox-primary">
                                                <input type="checkbox" id="mth_course_id-<?= $mth_course->getID() ?>"
                                                        name="mth_course_id[]" <?= $course->mappedToCourse($mth_course) ? 'checked' : '' ?>
                                                        value="<?= $mth_course->getID() ?>">
                                                <label for="mth_course_id-<?= $mth_course->getID() ?>">
                                                    <span><?= $subject ?> - </span>
                                                    <?= $mth_course ?>
                                                </label>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="available" id="available" value="1"
                        <?= $course->available() ? 'checked' : '' ?>>
                    <label for="available">
                        This provider course is available for parents to select.
                    </label>
                </div>

                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="diploma_only" id="diploma_only" value="1"
                        <?= $course->diplomanaOnly() ? 'checked' : '' ?>>
                    <label for="diploma_only">
                        Only visible/available to Diploma-seeking Students
                    </label>
                </div>

                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="archived" id="archived" value="1"
                        <?= $course->archived() ? 'checked' : '' ?>>
                    <label for="archived">
                        Archived
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="reduceTechAllowance" id="reduceTechAllowance" value="1"
                        <?= $course->reduceTechAllowanceFunction() ? 'checked' : '' ?>>
                    <label for="reduceTechAllowance">
                        <?=  in_array($_SERVER['STATE'], $MTH_SITES, TRUE) ? 'Reduces Technology Allowance':'Reduces Supplemental Learning Funds' ?>
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
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-round btn-primary">Save</button>
            </div>
        </div>
    </form>
<?php
core_loader::addJsRef('stickySectionHeaders', '/_/mth_includes/jquery.stickysectionheaders.js');
core_loader::printFooter();
?>
<script>
    $(function () {
        $('#mth_course_id-list').stickySectionHeaders({
            stickyClass: 'sticky',
            headlineSelector: 'strong'
        });
        updateSelectedCourses();
        $('#mth_course_id-list input').click(updateSelectedCourses);

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

    });
    function updateSelectedCourses() {
        var selected = $('#mth_course_id-list input:checked');
        var selectedDisplay = $('#mth_course_id-selected');
        if (selected.length > 0) {
            selectedDisplay.html('');
            selected.each(function () {
                selectedDisplay.append('<span onclick="$(\'#' + this.id + '\').focus()">' + $(this).parent().html().replace(/<[^>]+>/g, '') + '</span> ');
            });
        } else {
            selectedDisplay.html('<small style="color:#ddd">(none)</small>');
        }
    }

</script>