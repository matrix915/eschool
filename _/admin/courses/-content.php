<?php


core_loader::addCssRef('courseCSS', core_path::getPath() . '/courses.css');

cms_page::setPageTitle('Course Management');
cms_page::setPageContent('');
core_loader::printHeader('admin');

$_SESSION['show_archived_items'] = req_get::bool('show_archived_items');
?>
    <div class="alert bg-primary alert-info">    
        Changes to Subjects, Courses, and Providers <b>will be reflected in all schedules</b> with those items.
        If something needs to be changed it may be better to mark the current item as unavailable and add a new item.
        This would keep past schedules showing what the parent originally selected.
        If the number of unavailable items becomes annoying we can hide them by default with an option to show them.
    </div>

    <div class="card">
        <div class="card-block">
            <form>
                <label><input type="checkbox" name="show_archived_items" value="1" <?= $_SESSION['show_archived_items'] ? 'CHECKED' : '' ?>>
                    Show Archived Items
                </label>
                <button type="submit" class="btn btn-round btn-primary float-right">Filter</button>
            </form>
        </div>
    </div>

    <div class="row higlight-links">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <a class="float-right" onclick="global_popup_iframe('state_code-import_popup','<?= core_path::getPath() ?>/state_code_import')">
                        Import State Course Codes
                    </a>
                    <h3 class="card-title mb-0">Subjects</h3>
                </div>
                <div class="card-block p-0">
                    <table class="formatted table">
                        <?php while ($subject = mth_subject::getEach()):
                            if(!$_SESSION['show_archived_items'] && $subject->archived()) continue;?>
                            <tr class="mth_subject-row <?= ($subject->available() ? ' mth_course-available ' : ' mth_course-unavailable ')
                            . ($subject->archived() ? ' archived' : '') ?>"
                                id="mth_subject-<?= $subject->getID() ?>">
                                <td>
                                    <div>
                                        <a title="Show Courses" class="mth_subject-course_toggle"
                                        onclick="showHideCourses(<?= $subject->getID() ?>,'subject')"><?= $subject ?></a>
                                    </div>
                                </td>
                                <td class="hidden-sm-down">
                                    <p><?php
                                        $desc = $subject->getDesc();
                                        echo strlen($desc)>30?substr($desc,0,30):$desc;
                                    ?></p>
                                </td>
                                <td>
                                    <small>Periods <?= implode(',', $subject->getPeriods()) ?></small>
                                </td>
                                <td><a onclick="editSubject(<?= $subject->getID() ?>)"><i class="fa fa-gear"></i></a></td>
                            </tr>
                            <tr id="mth_subject-<?= $subject->getID() ?>-courses" class="mth_subject-courses"
                                style="display: none">
                                <td colspan="5">
                                    <div class="mth_course-container"></div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                <div class="card-footer">
                    <a onclick="editSubject(0)"><i>New Subject</i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <a class="float-right" onclick="global_popup_iframe('mth_provider_course-import_popup','<?= core_path::getPath() ?>/provider_course_import')">
                        Import Provider Courses
                    </a>
                    <h3 class="card-title mb-0">Providers</h3>
                   
                </div>
                <div class="card-block p-0">
                    <table class="formatted table" id="mth_provider-table">
                    <?php while ($provider = mth_provider::each()):
                        if(!$_SESSION['show_archived_items'] && $provider->archived()) continue;?>
                        <tr class="mth_subject-row mth_provider-<?= ($provider->available() ? 'available' : 'unavailable')
                        . ($provider->archived() ? ' archived' : '') ?>"
                            id="mth_provider-<?= $provider->id() ?>">
                            <td>
                                <div>
                                    <a title="Show Courses" class="mth_subject-course_toggle mth_subject-course"
                                    onclick="showHideCourses('<?= $provider->id() ?>','provider')">
                                        <?= $provider->name() ?>
                                    </a></div>
                            </td>
                            <td class="hidden-sm-down">
                                <small><?= $provider->led_by() ?></small>
                            </td>
                            <td class="hidden-sm-down">
                                <small>grades <?= $provider->gradeSpan() ?> </small>
                            </td>
                            <td><a onclick="editProvider(<?= $provider->id() ?>)"><i class="fa fa-gear"></i></a></td>
                        </tr>
                        <tr id="mth_provider-<?= $provider->id() ?>-courses" class="mth_subject-courses"
                            style="display: none">
                            <td colspan="5">
                                <div class="mth_course-container"></div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </table>
                </div>
                <div class="card-footer">
                    <a onclick="editProvider(0)"><i>New Provider</i></a>
                </div>
            </div>
        </div>
    </div>
<?php
core_loader::addJsRef('coursesJS', core_path::getPath() . '/courses.js');
core_loader::printFooter('admin');