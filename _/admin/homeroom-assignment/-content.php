<?php

use mth\yoda\answers;
use mth\yoda\assessment;
use mth\yoda\courses;
use mth\yoda\questions;
use mth\yoda\studentassessment;

/**
 * Created by PhpStorm.
 * User: abe
 * Date: 6/23/17
 * Time: 4:09 PM
 */

if (
    !($selected_schoolYear_id = &$_SESSION[core_config::sessionVar()][__FILE__])
    || !($selected_schoolYear = mth_schoolYear::getByID($selected_schoolYear_id))
) {
    $selected_schoolYear = mth_schoolYear::getCurrent();
    $selected_schoolYear_id = $selected_schoolYear->getID();
}

$current_school_year = mth_schoolYear::getCurrent();
$previous_school_year = mth_schoolYear::getPrevious();

$homerooms = new courses($selected_schoolYear);

if (req_get::is_set('ajax')) {
    header('Content-type: application/json');
    switch (req_get::txt('ajax')) {
        case 'loadStudents':
            if (req_post::is_set('school_year_id')) {
                $selected_schoolYear = mth_schoolYear::getByID(req_post::int('school_year_id'));
                if (!$selected_schoolYear) {
                    exit('0');
                }
                $selected_schoolYear_id = $selected_schoolYear->getID();
            }

            $filter = new mth_person_filter();
            $filter->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
            $filter->setStatusYear([$selected_schoolYear_id]);
            if (req_post::is_set('district')) {
                $pq = new \mth\packet\query();
                $student_ids = $pq->setSchoolDistricts(req_post::txt_array('district'))->getStudentIds();
                if (empty($student_ids)) {
                    $student_ids = [0];
                }
            }
            if (req_post::is_set('grade')) {
                $filter->setGradeLevel(req_post::txt_array('grade'));
            }
            if (req_post::is_set('provider')) {
                $sq = new \mth\schedule\query();
                $sq->setProviderIds(req_post::int_array('provider'))
                    ->setStatuses([
                        mth_schedule::STATUS_SUBMITTED,
                        mth_schedule::STATUS_CHANGE,
                        mth_schedule::STATUS_RESUBMITTED,
                        mth_schedule::STATUS_ACCEPTED,
                        mth_schedule::STATUS_CHANGE_POST,
                    ])
                    ->setSchoolYearIds([$selected_schoolYear_id]);
                if (isset($student_ids)) {
                    $student_ids = array_intersect($student_ids, $sq->getStudentIds());
                } else {
                    $student_ids = $sq->getStudentIds();
                }
                if (empty($student_ids)) {
                    $student_ids = [0];
                }
            }
            if (req_post::is_set('homeroom')) {
                $homeroomIds = req_post::int_array('homeroom');
                $initialStudentId = $homerooms->getStudentIdsByCourseIds(req_post::int_array('homeroom'));
                $filterStudent = new mth_person_filter();
                $filterStudent->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
                $filterStudent->setStatusYear([$selected_schoolYear_id]);
                $filterStudent->setStudentIDs($initialStudentId);
                $hrStudentIds = $filterStudent->getStudentIDs();
                if (in_array(0, $homeroomIds)) {
                    $allHrStudentIds = $homerooms->getStudentIds(array_keys($homerooms->getHomerooms()), $selected_schoolYear);
                    $filter3 = new mth_person_filter();
                    $filter3->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
                    $filter3->setStatusYear([$selected_schoolYear_id]);
                    $hrStudentIds = array_merge($hrStudentIds, array_diff($filter3->getStudentIDs(), $allHrStudentIds));
                }
                if (isset($student_ids)) {
                    $student_ids = array_intersect($student_ids, $hrStudentIds);
                } else {
                    $student_ids = $hrStudentIds;
                }
                if (empty($student_ids)) {
                    $student_ids = [0];
                }
            }
            if (isset($student_ids)) {
                $filter->setStudentIDs($student_ids);
            }
            if (req_post::bool('new')) {
                $filter->setIsNew(true);
            } else if (req_post::bool('returning')) {
                $filter->setIsNew(false);
            }
            if (req_post::bool('special_ed')) {
                $filter->setSpecialEd(req_post::int_array('special_ed'));
            }
            $filter2 = new mth_person_filter();
            $filter2->setParentIDs($filter->getParentIDs());
            $filter2->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
            $filter2->setStatusYear([$selected_schoolYear_id]);
            $student_data = [];
            foreach ($filter2->getStudents() as $student) {
                $address = $student->getAddress();
                $student_data[] = [
                    'id' => $student->getID(),
                    'parent_id' => $student->getParentID(),
                    'parent_name' => $student->getParent()->getName(true),
                    'name' => $student->getName(true),
                    'gender' => $student->getGender(),
                    'grade_level' => $student->getGradeLevelValue($selected_schoolYear_id),
                    'city' => ($address ? $address->getCity() : ''),
                    'homeroom' => $homerooms->getStudentHomeroomName($student->getID(), $selected_schoolYear),
                    'year' => (string) $selected_schoolYear,
                ];
            }
            echo json_encode($student_data);
            break;
        case 'assignHomeroom':
            if (req_post::bool('student')) {
                $students = mth_student::getStudents(array('StudentID' => req_post::int_array('student')));
                $success = true;
                foreach ($students as $student) {
                    try {
                        if (
                            !($studentCourse = courses::getStudentHomeroom($student->getID(), $current_school_year))
                            || !($studentAssessments = studentassessment::getByPersonCourse($student->getPersonID(), $studentCourse->getCourseId()))
                        ) {
                            $homerooms->assignToStudent(req_post::int('homeroom_course_id'), $student, $current_school_year);
                        } else {
                            echo json_encode(['success' => '0', 'error' => 'Student is already assigned to a homeroom. Please transfer student.']);
                            exit();
                        }
                    } catch (Exception $e) {
                        error_log($e);
                        $success = false;
                    }
                }
                exit($success ? '1' : '0');
            } else {
                exit('0');
            }
            break;

        case 'transferHomeroom':
            if (req_post::bool('student')) {
                $new_course = req_post::int('homeroom_course_id');
                $students = mth_student::getStudents(array('StudentID' => req_post::int_array('student')));
                $success = true;
                foreach ($students as $student) {
                    try {
                        if (
                            ($old_course = courses::getStudentHomeroom($student->getID(), $current_school_year))
                            && ($old_logs = studentassessment::getByPersonCourse($student->getPersonID(), $old_course->getCourseId()))
                        ) {
                            foreach ($old_logs as $old_log) {
                                if (
                                    ($old_ass = $old_log->getAssessment())
                                    && ($new_ass = assessment::getByTitle($old_ass->getTitle(), $new_course))
                                ) {
                                    $old_log->set('assessment_id', $new_ass->getID());
                                    if (
                                        ($old_log->save())
                                        && ($old_answers = answers::getByStudentAssessmentId($old_log->getID()))
                                    ) {
                                        foreach ($old_answers as $old_answer) {
                                            if (
                                                ($old_question = $old_answer->getQuestion(true))
                                                && ($new_question = questions::getByData($old_question->getData(), $new_ass->getID()))
                                            ) {
                                                $old_answer->set('yoda_assessment_question_id', $new_question->getID());
                                                $old_answer->save();
                                                $homerooms->assignToStudent($new_course, $student, $current_school_year);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            echo json_encode(['success' => '0', 'error' => 'Homeroom Logs Not found']);
                            exit();
                        }
                    } catch (Exception $e) {
                        error_log($e);
                        $success = false;
                    }
                }
                echo json_encode(['success' => ($success ? '1' : '0'), 'error' => 'Student not found']);
                exit();
            } else {
                echo json_encode(['success' => '0', 'error' => 'Missing Student Parameter']);
                exit();
            }
            break;

        case 'addHomeroom':
            try {
                $hrManager->add(req_post::int('canvas_course_id'), req_post::txt('name'));
                exit('1');
            } catch (Exception $e) {
                error_log($e);
                exit('0');
            }
            break;

        case 'getHomerooms':
            try {
                $response = courses::getHomeroomsByYear($current_school_year);
                echo json_encode(['canvas_course_ids' => array_keys($response), 'names' => array_values($response)]);
            } catch (Exception $e) {
                error_log($e);
                echo '0';
            }
            break;
    }
    exit();
}

core_loader::includeBootstrapDataTables('css');

core_loader::addCssRef('school-assignment', '/_/admin/homeroom-assignment/homeroom-assignment.css');
cms_page::setPageTitle('Homeroom Assignment Manager');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>
    <div class="card" id="homeroom_assignment_filter_block">
        <div class="card-block">
            <div class="row">
                <div class="col-md-3 p-0">
                    <fieldset class="filter_section">
                        <legend>District of Residence</legend>
                        <?php foreach (mth_packet::getAvailableSchoolDistricts() as $district) {?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="district[]" value="<?=$district?>">
                                <label>
                                    <?=$district?>
                                </label>
                            </div>
                        <?php }?>
                    </fieldset>
                </div>
                <div class="col p-0">
                    <fieldset class="filter_section">
                        <legend>Grade Level</legend>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" class="grade_all grade_selector">
                            <label>
                                All Grades
                            </label>
                        </div>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" class="grade_k-8 grade_selector">
                            <label>
                                Grades OR K-8
                            </label>
                        </div>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" class="grade_9-12 grade_selector">
                            <label>
                                Grades 9-12
                            </label>
                        </div>
                        <hr>
                        <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade => $name) {?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="grade[]" value="<?=$grade?>">
                                <label>
                                    <?=$name?>
                                </label>
                            </div>
                        <?php }?>
                    </fieldset>
                </div>
                <div class="col-md-3 p-0">
                    <fieldset class="filter_section">
                        <legend>Curriculum Provider</legend>
                        <?php foreach (mth_provider::all() as $provider) {?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="provider[]" value="<?=$provider->id()?>">
                                <label>
                                    <?=$provider->name()?>
                                </label>
                            </div>
                        <?php }?>
                    </fieldset>
                </div>
                <div class="col-md-3 p-0">
                    <fieldset>
                        <select name="school_year_id" title="School Year" class="form-control" id="school_year">
                            <?php foreach (mth_schoolYear::getAll() as $schoolYear) {?>
                                <option value="<?=$schoolYear->getID()?>" <?=$selected_schoolYear_id == $schoolYear->getID() ? 'selected' : ''?>><?=$schoolYear->getName()?></option>
                            <?php }?>
                        </select>
                    </fieldset>
                    <fieldset>
                        <b>Special Ed</b>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="special_ed[]" value="<?=mth_student::SPED_IEP?>">
                            <label>
                                <?=mth_student::SPED_LABEL_IEP?>
                            </label>
                        </div>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="special_ed[]" value="<?=mth_student::SPED_504?>">
                            <label>
                                <?=mth_student::SPED_LABEL_504?>
                            </label>
                        </div>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="special_ed[]" value="<?=mth_student::SPED_EXIT?>">
                            <label>
                                <?=mth_student::SPED_LABEL_EXIT?>
                            </label>
                        </div>
                        <p>
                            For <?=$current_school_year?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="new" value="1" id="new" onclick="$('#returning').prop('checked',false);">
                                <label for="new" onclick="$('#returning').prop('checked',false);">
                                    New
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="returning" value="1" id="returning" onclick="$('#new').prop('checked',false);">
                                <label for="returning" onclick="$('#new').prop('checked',false);">
                                    Returning
                                </label>
                            </div>
                        </p>
                    </fieldset>
                </div>
                <div class="col-md-3 p-0" >

                    <fieldset class="filter_section">
                        <b>Homerooms <small>(<?=$previous_school_year?>)</small></b>
                        <?php foreach ($homerooms->eachHomeroomByYear($previous_school_year) as $homeroom) {?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="homeroom[]" value="<?=$homeroom->getCourseId()?>">
                                <label>
                                    <?=$homeroom->getName()?>
                                </label>
                            </div>
                        <?php }?>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="homeroom[]" value="0">
                            <label>
                                Unassigned
                            </label>
                        </div>
                    </fieldset>
                </div>
                <div class="col p-0">
                    <fieldset class="filter_section">
                        <b>Homerooms <small>(<?=$current_school_year?>)</small></b>
                        <?php foreach ($homerooms->eachHomeroomByYear($current_school_year) as $homeroom) {?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="homeroom[]" value="<?=$homeroom->getCourseId()?>">
                                <label>
                                    <?=$homeroom->getName()?>
                                </label>
                            </div>
                        <?php }?>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="homeroom[]" value="0">
                            <label>
                                Unassigned
                            </label>
                        </div>
                    </fieldset>
                </div>

            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-success btn-round float-right">Load</button>
        </div>
    </div>
    <div class="alert alert-alt alert-info bg-info">Note: Entire families will be returned if any of the children in the family meet the above parameters.</div>

    <div class="card">
        <div class="card-header">
            Total Students: <span class="student_count_display"></span>
        </div>
        <div class="card-block">
            <table class="table responsive" id="homeroom_assignment_table">
                <thead>
                    <tr>
                        <th><input type="checkbox" title="Un/Select All" id="cbSelector"></th>
                        <th></th>
                        <th>Student</th>
                        <th>Gender</th>
                        <th>Grade Level</th>
                        <th>Location</th>
                        <th>Homeroom</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <p>
        <label>
            Set <span class="school_year_display"><?=mth_schoolYear::getCurrent();?></span> Homeroom:
            <select id="homeRoomSelect">
            </select>
        </label>
        &nbsp;
        <a class="btn btn-primary" href="#" id="assign">Assign</a>
        or
        <a class="btn btn-pink" href="#" id="transfer">Transfer</a>
        <!-- <a id="add_homeroom_link" class="btn btn-primary btn-round" href="#">+ Add Homeroom</a> -->
    </p>
    <!-- Add Homeroom Modal -->
    <div id="add_homeroom" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="add_homeroom" aria-hidden="true">
        <div class="modal-dialog">

            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add <span class="school_year_display"><?=$selected_schoolYear?></span> Homeroom</h3>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>
                            Canvas Course ID:
                        </label>
                        <input name="canvas_course_id" class="form-control">

                    </div>
                    <div class="form-group">
                        <label>
                            Name:
                        </label>
                        <input name="name" class="form-control">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-round add-homeroom">+ Add Homeroom</button>
                    <button type="button" class="btn btn-secondary cancel btn-round" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Homeroom Modal -->
    <?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('school-assignment', '/_/admin/homeroom-assignment/homeroom-assignment.js');
core_loader::printFooter('admin');
