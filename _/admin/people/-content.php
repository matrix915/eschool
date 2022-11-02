<?php
use mth\student\SchoolOfEnrollment;

$fil = &$_SESSION['mth_people_filters'];
if (!is_object($fil)) {
    $fil = new req_array(array());
    $fil->set('status', array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
    $fil->set('year', array(mth_schoolYear::getCurrent()->getID()));
}
if (req_get::bool('type') || req_get::bool('continue')) {
    $limit = 500;
    $csv = FALSE;
    $returnArr = array();
    $statusYear = &$_SESSION['mth-people-search-statusYear'];

    if (!req_get::bool('continue')) {
        if (req_get::bool('csv')) {
            $limit = 9999999999999999999999;
            $csv = TRUE;
            req_get::remove('csv');
        }

        unset($_SESSION['mth-people-search-array']);
        $search = new mth_person_filter();

        $fil = req_get::req_array();

        if (req_get::bool('grade')) {
            $search->setGradeLevel(req_get::txt_array('grade'));
        }
        if (req_get::bool('status')) {
            $search->setStatus(req_get::int_array('status'));
        }
        if (req_get::bool('special_ed')) {
            $search->setSpecialEd(req_get::int_array('special_ed'));
        }
        if (req_get::bool('diploma_seeking')) {
            $search->setDiplomaSeeking(true);
        }
        if (req_get::bool('year')) {
            $search->setStatusYear(req_get::int_array('year'));
        }
        if (req_get::bool('midyear')) {
            $search->setMidYear(true);
        }
        if (req_get::bool('school')) {
            $search->setSchoolOfEnrollment(req_get::int_array('school'));
        }
        if (req_get::bool('section')) {
            $search->setHomeRoomSections(req_get::txt_array('section'));
        }
        if (req_get::bool('year')) {
            $statusYear = mth_schoolYear::getByID(max(req_get::int_array('year')));
        } else {
            $statusYear = mth_schoolYear::getCurrent();
        }

        if (req_get::bool('new')) {
            $search->setIsNew(true);
            unset($fil->returning);
        } elseif (req_get::bool('returning')) {
            $search->setIsNew(false);
        }

        if(req_get::bool('transferred')){
            $search->setTransferred();
        }

        if(req_get::bool('transferredpending')){
            $search->setTransferred(1);
        }

        if (req_get::bool('next_new')) {
            $search->setIsNewNext(true);
            unset($fil->next_returning);
        } elseif (req_get::bool('next_returning')) {
            $search->setIsNewNext(FALSE);
        }

        switch (req_get::txt('type')) {
            case 'parent':
                $people = $search->getParents();
                break;
            default :
                $people = $search->getStudents();
        }
    } else {
        $people = $_SESSION['mth-people-search-array'];
    }
    $previousYear = mth_schoolYear::getPrevious();
    $currentYear = mth_schoolYear::getCurrent();
    if (isset($search)) {
        mth_student_section::cache($search->getStudentIDs(), 1, $statusYear->getID());
    }
    $c = 0;
    if(!$csv) {
        foreach ($people as $key => $person) {
            /* @var $person mth_person */
            $c++;
            if ($c > $limit) {
                break;
            }
    
            $address = $person->getAddress();
            $grade_level = $person->getType() == 'student' ? $person->getGradeLevel(false, false, $statusYear->getID()) : '';
            $returnArr[$person->getPersonID()] = array(
                'last' => $person->getPreferredLastName(),
                'first' => $person->getPreferredFirstName(),
                'email' => $person->getEmail(),
                'phone' => (string)$person->getPhone(),
                'city' => $address ? $address->getCity() : '',
                'state' => $address ? $address->getState() : '',
                'id' => $person->getID(),
                'person_id' => $person->getPersonID(),
                'type' => ucfirst($person->getType()),
                'grade' => $grade_level ? $grade_level : '',
                'prevYear' => $person->getType() == 'student' && $previousYear ? (string)$person->getSchoolOfEnrollment(false, $previousYear) : '',
                'currYear' => $person->getType() == 'student' && $currentYear ? (string)$person->getSchoolOfEnrollment(false, $currentYear) : '',
                'gender' => $person->getGender(),
                'parent_id' => $person->getType() == 'student' ? $person->getParentID() : $person->getID(),
                'schedule_id' => $person->getType() == 'student' ? mth_schedule::getStudentScheduleID($person) : NULL,
                'status_date' => $person->getType() == 'student' ? $person->getStatusDate($statusYear, 'm/d/Y') : NULL,
                'section' => $person->getType() == 'student' ? mth_student_section::getSectionName($person->getID(), 1, $statusYear->getID()) : NULL
            );
            unset($people[$key]);
        }
    } else {
        $student_schedule_ids = mth_schedule::getAllStudentScheduleIds($statusYear);
        $section_names = mth_student_section::getAllSectionName(1, $statusYear->getID());
        $person_all_address = mth_student::getAllAddress();
        $grade_level = mth_student::getAllGradeLevelsByYearId($statusYear->getID());
        $prev_year = mth_student::getAllSchools($statusYear->getPreviousYear()->getID());
        $current_year = mth_student::getAllSchools($statusYear->getID());
        $student_all_status = mth_student::getAllStudentStatusByYear($statusYear->getID());
        $all_parents = mth_student::getAllParent();
        foreach ($people as $key => $person) {
            /* @var $person mth_person */
            $c++;
            if ($c > $limit) {
                break;
            }
            $prev_year_school = (isset($prev_year[$person->getID()]) && $prev_year[$person->getID()] ? $prev_year[$person->getID()] : 0);
            $cur_year_school = (isset($current_year[$person->getID()]) && $current_year[$person->getID()] ? $current_year[$person->getID()] : 0);
            $parentId = $person->getType() == 'student' ? $person->getParentID() : $person->getID();
            $returnArr[$person->getPersonID()] = array(
                'last' => $person->getPreferredLastName(),
                'first' => $person->getPreferredFirstName(),
                'email' => $person->getEmail(),
                'phone' => (string)$person->getPhone(),
                'city' => isset($person_all_address[$all_parents[$parentId]]['city']) ? $person_all_address[$all_parents[$parentId]]['city'] : '',
                'state' => isset($person_all_address[$all_parents[$parentId]]['state']) ? $person_all_address[$all_parents[$parentId]]['state'] : '',
                'person_id' => $person->getPersonID(),
                'type' => ucfirst($person->getType()),
                'grade' => isset($grade_level[$person->getID()]) ? $grade_level[$person->getID()] : '',
                'prevYear' => $person->getType() == 'student' ? SchoolOfEnrollment::get($prev_year_school)->getShortName() : '',
                'currYear' => $person->getType() == 'student' ? SchoolOfEnrollment::get($cur_year_school)->getShortName() : '',
                'gender' => $person->getType() == 'student' ? $person->getGender() : NULL,
                'status_date' => $person->getType() == 'student' ? core_model::getDate($student_all_status[$person->getID()], 'm/d/Y') : NULL,
                'section' => $person->getType() == 'student' && isset($section_names[$person->getID()]) ? $section_names[$person->getID()] : NULL
            );
            unset($people[$key]);
        }
    }

    $_SESSION['mth-people-search-array'] = $people;

    if ($csv) {
        $file = 'file_' . md5(serialize(req_get::req_array()));
        $h = fopen(ROOT . core_path::getPath() . '/csv/' . $file, 'w');
        $headerSet = false;
        foreach ($returnArr as $fields) {
            if (!$headerSet) {
                fputcsv($h, array_keys($fields));
                $headerSet = true;
            }
            fputcsv($h, array_map(array('req_sanitize', 'txt_decode'), $fields));
        }
        fclose($h);
        echo $file;
    } else {
        header('Content-type: application/json');
        echo json_encode($returnArr);
    }
    exit();
}

if (req_get::bool('setSOE')) {
    if (($SOE = SchoolOfEnrollment::get(req_get::int('setSOE'))) && req_post::bool('people')
        && ($year = mth_schoolYear::getByID(req_get::int('setSOEyear')))
    ) {
        $students = mth_student::getStudents(array('PersonID' => explode(',', req_post::txt('people'))));
        foreach ($students as $student) {
            /* @var $student mth_student */
            $student->setSchoolOfEnrollment($SOE, $year);
        }
    }
    exit('1');
}

if (req_get::bool('setHomeRoomSection')) {
    $year = mth_schoolYear::getCurrent();
    $period = mth_period::get(1);
    $students = mth_student::getStudents(array('PersonID' => explode(',', req_post::txt('people'))));
    foreach ($students as $student) {
        /* @var $student mth_student */
        mth_student_section::set($student, $period, $year, req_get::txt('setHomeRoomSection'));
    }
    exit('1');
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Master');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>
    <script type="text/javascript">
        $DataTable = null;
        $(function () {
           $DataTable =  $('#contentTable').DataTable({
                'aoColumnDefs': [{"bSortable": false, "aTargets": [0]}],
                "bStateSave": true,
                "bPaginate": false,
                'info': false,
                "aaSorting": [[1, 'asc']]
            });
            //updateTable();
        });
        function updateTable() {
            if (!updateTable.body)
                updateTable.body = $('body');
            updateTable.body.css({'min-height': updateTable.body.height() + 'px'});
            $('#masterCB').prop('disabled', true).css('cursor', 'wait');
            $('#loadingGraphic').show();
            var oSettings = $('#contentTable').dataTable().fnSettings();
            var iTotalRecords = oSettings.fnRecordsTotal();
            setCookie('SelectedPersonType', $('#personTypeSelection').val());
            for (i = 0; i <= iTotalRecords; i++) {
                $('#contentTable').dataTable().fnClearTable(false);
            }
            global_waiting();
            $.ajax({
                url: '?' + $('#filterBlock input').serialize(),
                success: addData
            });
            //$('#filterToggle').prop('checked', false);
        }

        function addData(data) {
            if (addData.stats === undefined) {
                addData.stats = {'Parent': 0, 'Student': 0, 'Total': 0};
            }
            var c = 0;
            for (var iID in data) {
                c++;
                 $DataTable.row.add([
                    '<input name="people[]" value="' + iID + '" class="peopleCB" type="checkbox"><small>' + iID + '</small>',
                    ' <a onclick="showEditForm(\'' + data[iID].type.toLowerCase() + '\',' + data[iID].id + ')" class="link family-' + data[iID].parent_id + '" title="' + data[iID].last + ', ' + data[iID].first + '">' + data[iID].last + ', ' + data[iID].first + '</a>',
                    '<a href="mailto:' + data[iID].email + '" target="_blank" title="' + data[iID].email + '">' + data[iID].email + '</a>',
                    data[iID].phone,
                    (data[iID].city ? data[iID].city + ', ' + data[iID].state : ''),
                    data[iID].type + (data[iID].type === 'Parent' ? '<a class="mth-family-parent"></a>' : ''),
                    (data[iID].schedule_id ? '<a onclick="editSchedule(' + data[iID].schedule_id + ')">' + data[iID].grade + '</a>' : data[iID].grade),
                    data[iID].status_date,
                    data[iID].prevYear,
                    data[iID].currYear,
                    data[iID].gender,
                    data[iID].section
                ], false);
                addData.stats[data[iID].type]++;
                addData.stats.Total++;
            }
           
            if (c < 500) {
                global_waiting_hide();
                $DataTable.draw().responsive.recalc();
                $('#masterCB').prop('disabled', false).css('cursor', 'pointer');
                $('#loadingGraphic').hide();
                updateTable.body.css({'min-height': '0px'});
                $('a.mth-family-parent').parents('tr').addClass('mth-parent');
                $('#contentTable tbody tr').mouseover(function () {
                    $('.' + $(this).find('td:nth-child(2) a').attr('class')).parents('tr').addClass('partOfFam');
                }).mouseout(function () {
                    $('.partOfFam').removeClass('partOfFam');
                });
                $('#master_stats').html('Total: ' + addData.stats.Total + ' &nbsp; ' + (addData.stats.Parent ? 'Parents: ' + addData.stats.Parent + ' &nbsp; ' : '') + (addData.stats.Student ? 'Students: ' + addData.stats.Student : ''));
                addData.stats = {'Parent': 0, 'Student': 0, 'Total': 0};
            } else {
                $.ajax({
                    url: '?continue=1',
                    success: addData
                });
            }
        }

        function showEditForm(type, id) {
            global_popup_iframe('mth_people_edit', '/_/admin/people/edit?' + type + '=' + id);
        }

        function changeCheckboxStatus() {
            $('.peopleCB').prop('checked', changeCheckboxStatus.checked = !changeCheckboxStatus.checked);
        }
        function filtersUpdated(runNow) {
            if ($('.gradeCB:checked').length < 1) {
                $('#grade-all').prop('checked', true);
            }
            if (!runNow) {
                clearTimeout(filtersUpdated.timer);
                filtersUpdated.timer = setTimeout('filtersUpdated(true)', 2000);
                $('#loadingGraphic').show();
                return;
            }
            clearTimeout(filtersUpdated.timer);
            updateTable();
            updateFilterDisplay();
        }
        function updateFilterDisplay() {
            var arr = [];
            $('#filterBlock input:checked').each(function () {
                arr.push($.trim($(this).parent().html().replace(/<[^>]*>/g, '')));
            });
            $('#filterToggle small').html(arr.join(', '));
        }
        function setSchoolOfEnrollment() {
            var ids = [];
            $('.peopleCB:checked').each(function () {
                ids.push(this.value);
            });

            swal({
                title: "",
                text: "You are about to set "+ids.length+ " student(s) to "+$('#schoolOfEnrollmentSelect option:selected').html()+" Is that what you really want to do?",
                type: "",
                showCancelButton: true,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: false,
                closeOnCancel: true,
                showLoaderOnConfirm: true
            },
            function () {
                $.ajax({
                        url: '?setSOE=' + $('#schoolOfEnrollmentSelect').val() + '&setSOEyear=' + $('#schoolOfEnrollmentYearSelect').val(),
                        data: 'people=' + ids.join(','),
                        type: 'POST',
                        success: function (data, textStatus, jqXHR) {
                            swal.close();
                            updateTable();
                            $('#schoolOfEnrollmentSelect').val('');
                        }
                    });
            });
          

        }
        function setHomeRoomSection() {
            var ids = [];
            $('.peopleCB:checked').each(function () {
                ids.push(this.value);
            });
            global_confirm(
                '<p>You are about to set <b>' + ids.length + '</b> student(s) to <b>' + $('#homeRoomSection option:selected').html() + '</b> for their Homeroom section. Is that what you really want to do?',
                function () {
                    $.ajax({
                        url: '?setHomeRoomSection=' + $('#homeRoomSection').val(),
                        data: 'people=' + ids.join(','),
                        type: 'POST',
                        success: function (data, textStatus, jqXHR) {
                            updateTable();
                            $('#homeRoomSection').val('');
                            global_popup_close('global_confirm_popup');
                        }
                    });
                },
                'Yes',
                'No',
                function () {
                    $('#homeRoomSection').val('');
                    global_popup_close('global_confirm_popup');
                });
        }
        function editSchedule(schedule_id) {
            global_popup_iframe('mth_schedule-edit-'+schedule_id, '/_/admin/schedules/schedule?schedule=' + schedule_id);
        }
        function getCSV() {
            global_waiting();
            $.ajax({
                url: '?csv=1&' + $('#filterBlock input').serialize(),
                success: function (data) {
                    global_waiting_hide();
                    location.href = '<?=core_path::getPath()?>/csv-file?file=' + data;
                }
            });
        }
    </script>
<div class="card container-collapse">
    <div class="card-header">
        <button type="button" onclick="filtersUpdated(true)" class="btn btn-round btn-success float-right" id="master_show_button">Load</button>
        <h4 data-toggle="collapse" aria-hidden="true" href="#filterBlock" aria-controls="filterBlock" id="filterToggle">
            Show: <small></small>
        </h4>
    </div>
    
    <div id="filterBlock" class="card-block collapse info-collapse">
        <div class="row">
            <div class="col">
                <fieldset>
                    <legend>Years</legend>
                    <?php foreach (mth_schoolYear::getSchoolYears() as $year): /* @var $year mth_schoolYear */ ?>
                        <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="year[]" value="<?= $year->getID() ?>" id="year-<?= $year->getID() ?>"
                                <?= in_array($year->getID(), $fil->int_array('year')) ? 'checked' : '' ?>>
                        <label for="year-<?= $year->getID() ?>">
                        <?= $year ?>
                        </label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            </div>
            <div class="col">
                <fieldset>
                    <legend>Type</legend>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="type" value="all" id="type-all" <?= !$fil->type || $fil->type == 'all' ? 'checked' : '' ?>>
                        <label for="type-all">
                            All
                        </label>
                    </div>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="type" value="student" id="type-student" <?= $fil->type == 'student' ? 'checked' : '' ?>>
                        <label for="type-student">
                           Students
                        </label>
                    </div>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="type" value="parent" id="type-parent" <?= $fil->type == 'parent' ? 'checked' : '' ?>>
                        <label for="type-parent">
                            Parents
                        </label>
                    </div>
                    <hr>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="midyear" value="1" id="midyear" <?= $fil->bool('midyear')? 'checked' : '' ?>>
                        <label for="midyear">
                            Mid-year Enrollment
                        </label>
                    </div>
                </fieldset>
            </div>
            <div class="col">
                <fieldset>
                    <legend>Grade Level</legend>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" id="grade-all" onclick="$('.gradeCB:checked').prop('checked',false);" <?= !$fil->bool('grade') ? 'checked' : '' ?>>
                        <label for="grade-all" onclick="$('.gradeCB:checked').prop('checked',false);">
                            All Grades
                        </label>
                    </div>
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade-<?= $grade_level ?>" onclick="$('#grade-all').prop('checked',false);" <?= in_array($grade_level, $fil->txt_array('grade')) ? 'checked' : '' ?> class="gradeCB">
                            <label for="grade-<?= $grade_level ?>" onclick="$('#grade-all').prop('checked',false);">
                                <?= $grade_desc ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            </div>
            <div class="col">
                <fieldset>
                    <legend>Status</legend>
                    <?php foreach (mth_student::getAvailableStatuses() as $statusNum => $status): ?>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="status[]" value="<?= $statusNum ?>" id="status-<?= $statusNum ?>" <?= in_array($statusNum, $fil->int_array('status')) ? 'checked' : '' ?>>
                            <label for="status-<?= $statusNum ?>">
                                <?= $status ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            </div>
            <div class="col">
                <fieldset>
                    <div>School of Enr.
                        <?php foreach (SchoolOfEnrollment::getActive() as $num => $school): ?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="school[]" value="<?= $num ?>" id="school-<?= $num ?>" <?= in_array($num, $fil->int_array('school')) ? 'checked' : '' ?>>
                                <label for="school-<?= $num ?>">
                                    <?= $school ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr>
                    <div>Section
                        <?php foreach (mth_student_section::names() AS $sectionName): $simple = str_replace(' ', '', $sectionName); ?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="section[]" value="<?= $sectionName ?>" id="section-<?= $simple ?>" <?= in_array($sectionName, $fil->txt_array('section')) ? 'checked' : '' ?>>
                                <label for="section-<?= $simple ?>">
                                    <?= $sectionName ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </div>
            <div class="col">
                <fieldset>
                    <legend>Other</legend>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="diploma_seeking" value="1" id="diploma_seeking" <?= $fil->diploma_seeking ? 'checked' : '' ?>>
                        <label for="diploma_seeking">
                        Diploma-seeking
                        </label>
                    </div>
                    <hr>
                    <div>Special Ed
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="special_ed[]" value="<?= mth_student::SPED_IEP ?>"
                                    id="special_ed-<?= mth_student::SPED_IEP ?>"
                                    <?= in_array(mth_student::SPED_IEP, $fil->int_array('special_ed')) ? 'checked' : '' ?>>
                            <label for="special_ed-<?= mth_student::SPED_IEP ?>">
                                <?= mth_student::SPED_LABEL_IEP ?>
                            </label>
                        </div>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="special_ed[]" value="<?= mth_student::SPED_504 ?>"
                                    id="special_ed-<?= mth_student::SPED_504 ?>"
                                    <?= in_array(mth_student::SPED_504, $fil->int_array('special_ed')) ? 'checked' : '' ?>>
                            <label for="special_ed-<?= mth_student::SPED_504 ?>">
                                <?= mth_student::SPED_LABEL_504 ?>
                            </label>
                        </div>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="special_ed[]" value="<?= mth_student::SPED_EXIT ?>"
                                    id="special_ed-<?= mth_student::SPED_EXIT ?>"
                                    <?= in_array(mth_student::SPED_EXIT, $fil->int_array('special_ed')) ? 'checked' : '' ?>>
                            <label for="special_ed-<?= mth_student::SPED_EXIT ?>">
                                <?= mth_student::SPED_LABEL_EXIT ?>
                            </label>
                        </div>
                    </div>
                    <?php if (mth_schoolYear::getPrevious()): ?>
                        <hr>
                        <div title="This checks student statuses from the previous year">For <?= mth_schoolYear::getCurrent() ?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="new" value="1" id="new" class="soestatus"
                                            <?= $fil->new ? 'checked' : '' ?>>
                                <label for="new">
                                    New
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="returning" value="1" id="returning"  class="soestatus"
                                        <?= $fil->returning ? 'checked' : '' ?>>
                                <label for="returning">
                                    Returning
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="transferred" value="1" id="transferred"  class="soestatus"
                                            <?= $fil->transferred ? 'checked' : '' ?>>
                                <label for="transferred">
                                    Transferred
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="transferredpending" value="1" id="transferredpending"  class="soestatus"
                                        <?= $fil->transferredpending ? 'checked' : '' ?>>
                                <label for="transferredpending">
                                    Transferred - Pending
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (mth_schoolYear::getNext() && mth_schoolYear::getNext() != mth_schoolYear::getCurrent()): ?>
                        <hr>
                        <div title="This checks student statuses from the current year">For <?= mth_schoolYear::getNext() ?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="next_new" value="1" id="next_new"
                                        onclick="$('#next_returning').prop('checked',false);"
                                        <?= $fil->next_new ? 'checked' : '' ?>>
                                <label for="next_new" onclick="$('#next_returning').prop('checked',false);">
                                    New
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="next_returning" value="1" id="next_returning"
                                        onclick="$('#next_new').prop('checked',false);"
                                        <?= $fil->next_returning ? 'checked' : '' ?>>
                                <label for="next_returning" onclick="$('#next_new').prop('checked',false);">
                                    Returning
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                </fieldset>
            </div>
        </div>
       
    </div>
</div>  
    <script>updateFilterDisplay();</script>

<?php if (core_user::getUserID() == 1): ?>
    <p>
        <a href="/_/admin/people/import">Import People</a> -
        <a href="/_/admin/people/import-schools-of-enrollment">Import Schools of Enrollment</a>
    </p>
<?php endif; ?>
   

    <p id="master_stats" ></p>

    <div class="card">
        <div class="card-header">
            <button onclick="getCSV()" class="btn btn-round btn-secondary">Download CSV</button>
        </div>
        <div class="card-block pl-0 pr-0">
            <table id="contentTable" class="table table-striped responsive">
                <thead>
                <th><input type="checkbox" onclick="changeCheckboxStatus()" id="masterCB"></th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Location</th>
                <th>Type</th>
                <th>Grade</th>
                <th>Status Date</th>
                <th><?= mth_schoolYear::getPrevious() ?></th>
                <th><?= mth_schoolYear::getCurrent() ?></th>
                <th>Gender</th>
                <th>Section</th>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-block">
            Set School Of Enrollment:
            <select id="schoolOfEnrollmentYearSelect">
                <?php while ($year = mth_schoolYear::each()): ?>
                    <option
                        value="<?= $year->getID() ?>" <?= $year == mth_schoolYear::getCurrent() ? 'selected' : '' ?>><?= $year ?></option>
                <?php endwhile; ?>
            </select>
            <select id="schoolOfEnrollmentSelect" onchange="setSchoolOfEnrollment()">
                <option></option>
                <?php foreach (SchoolOfEnrollment::getActive() as $num => $school): ?>
                    <option value="<?= $num ?>"><?= $school ?></option>
                <?php endforeach; ?>
            </select>
            &nbsp; | &nbsp;
            Set Homeroom Section for <?= mth_schoolYear::getCurrent() ?>:
            <select id="homeRoomSection" onchange="setHomeRoomSection()">
                <option></option>
                <?php foreach (mth_student_section::names() AS $sectionName): ?>
                    <option><?= $sectionName ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    $(function(){
        $('.soestatus').change(function(){
            $('.soestatus:checked').not(this).prop('checked',false);
        });
    });
</script>