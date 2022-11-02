<?php

use mth\yoda\courses;
use mth\yoda\homeroom\Query;

$selected_schoolYear = mth_schoolYear::getCurrent();

/**
 * check if $_REQUEST param is set
 *
 * @param string $param param name
 * @param string $type  method
 * @return int|array|string
 */
function req_isset($param, $type)
{
    if (!(req_post::is_set($param) || req_get::is_set($param))) {
        return null;
    }

    $method = req_post::is_set($param) ? 'post' : 'get';

    return  call_user_func(array("req_$method", $type), $param);
}

function load_homeroom($selected_schoolYear, $report = false)
{
    $COLUMNS = [
        'Student Last Name, First Name', 'Gender', 'Grade Level', 'Parent Email', 'Parent Phone', 'Parent Last Name, Parent Name'
        // ,'# of 0'
        , 'Homeroom Grade'
    ];
    $reportArr = $report ? [$COLUMNS] : [];
    $_grades =  req_isset('grades', 'int');
    $_gradelevel = req_isset('grade', 'int_array');
    $_homerooms = req_isset('homeroom', 'int_array');
    $_last_assigned = req_isset('last_assigned', 'int');

    $_zero_count = req_isset('zero_count', 'txt');
    $_zero_count = trim($_zero_count) == '' ? null : $_zero_count;

    $_zero_count_1st_sem = req_isset('zero_count_1st_sem', 'txt');
    $_zero_count_1st_sem = trim($_zero_count_1st_sem) == '' ? null : $_zero_count_1st_sem;

    $_zero_count_2nd_sem = req_isset('zero_count_2nd_sem', 'txt');
    $_zero_count_2nd_sem = trim($_zero_count_2nd_sem) == '' ? null : $_zero_count_2nd_sem;

    $_ex_count = req_isset('ex_count', 'txt');
    $_ex_count = trim($_ex_count) == '' ? null : $_ex_count;

    $days = !empty($_last_assigned) ? intval($_last_assigned) : 0;

    $query = new Query();
    $query->setYear([$selected_schoolYear->getID()]);
    $query->setTeacher(core_user::getCurrentUser()->getID());
    if ($_gradelevel) {
        $query->setGradeLevel($_gradelevel, $selected_schoolYear->getID());
    }
    if ($_homerooms) {
        $query->setHomerom($_homerooms);
    }

    $enrollments = $query->getAll(req_get::int('page'));
    $return = [];
    foreach ($enrollments as $enrollment) {

        $stgrade = $enrollment->getGrade();
        $assigned_date = $enrollment->getDateAssigned('m/d/Y');
        $zeros = $enrollment->getAllZeros();
        $first_semester_zeros = $enrollment->getFirstSemesterZeros();
        $second_semester_zeros = $enrollment->getSecondSemesterZeros();
        $ex = $enrollment->getExcuses();

        if ($_zero_count !== null && $_zero_count != $zeros) {
            continue;
        }

        if ($_zero_count_1st_sem !== null && $_zero_count_1st_sem != $first_semester_zeros) {
            continue;
        }

        if ($_zero_count_2nd_sem !== null && $_zero_count_2nd_sem != $second_semester_zeros) {
            continue;
        }

        if ($_ex_count !== null && $_ex_count != $ex) {
            continue;
        }

        if ($_grades && $stgrade > $_grades) {
            continue;
        }

        if (!$student = $enrollment->student()) {
            continue;
        }

        if ($student->isStatus(mth_student::STATUS_WITHDRAW, $selected_schoolYear)) {
            $enrollment->delete();
            continue;
        }

        $gradelevel = $student->getGradeLevelValue($selected_schoolYear->getID());


        if (!($parent = $student->getParent())) {
            core_notify::addError('Parent Missing for ' . $student);
            continue;
        }

        if ($_last_assigned && $_last_assigned < strtotime($enrollment->getDateAssigned())) { }


        if ($days != 0 && strtotime($assigned_date) < strtotime("-" . ($days + 1) . " days")) {
            continue;
        }




        $data = [
            'date_assigned' => $assigned_date,
            'student_name' => $student->getPreferredLastName() . ', ' . $student->getPreferredFirstName(),
            'gender' => $student->getGender(),
            'grade_level' => $gradelevel,
            'pemail' => $parent->getEmail(),
            'pphone' => (string) $parent->getPhone(),
            'parent_name' => $parent->getPreferredLastName() . ', ' . $parent->getPreferredFirstName(),
            'grade' =>  $stgrade === null ? 'NA' : $stgrade . '%',
            'first_semester_zeros' => $first_semester_zeros,
            'second_semester_zeros' => $second_semester_zeros,
            'zeros' => $zeros,
            'ex' => $ex,
            'slug' => $student->getSlug()
        ];

        if (!$report) {
            $data = array_merge($data, [
                'id' => $student->getID(),
                'notes' => 0,
                'homeroom' => $enrollment->getCourseId(),
                'parentid' => $parent->getID()
            ]);
        } else {
            $value_only = [];
            foreach ($data as $d) {
                $value_only[] = $d;
            }
            $data = $value_only;
        }

        $return[] = $data;
    }

    return ['count' => count($enrollments), 'filtered' => $return];
}

if (req_get::bool('loadHomeroom')) {
    $students = load_homeroom($selected_schoolYear);
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}


core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Homeroom');
cms_page::setPageContent('');
core_loader::printHeader('teacher');
?>
<style>
    #homeroom_table {
        font-size: 12px;
    }

    #homeroom_table td {
        padding: 2px;
        vertical-align: middle;
    }

    table.dataTable.dtr-inline.collapsed>tbody>tr[role="row"]>td:first-child:before,
    table.dataTable.dtr-inline.collapsed>tbody>tr[role="row"]>th:first-child:before {
        left: 0px !important;
    }

    .dataTables_info {
        display: none;
    }
</style>
<div class="card container-collapse">
    <div class="card-header">
        <h4 class="card-title mb-0" data-toggle="collapse" aria-hidden="true" href="#intervention-filter-cont" aria-controls="intervention-filter-cont">
            <i class="panel-action icon md-chevron-right icon-collapse"></i> Filter
        </h4>
    </div>
    <div class="card-block collapse info-collapse" id="intervention-filter-cont">
        <div class="row" id="filter_form">
            <div class="col-md-4">
                <fieldset class="block grade-levels-block">
                    <legend>Grade Level</legend>

                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="grade_selector" value="gAll">
                        <label>
                            All Grades
                        </label>
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="grade_selector" value="gKto8">
                        <label>
                            Grades OR K-8
                        </label>
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="grade_selector" value="g9to12">
                        <label>
                            Grades 9-12
                        </label>
                    </div>

                    <hr>
                    <div class="grade-level-list">
                        <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade => $name) { ?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="grade[]" value="<?= $grade ?>">
                                <label>
                                    <?= $name ?>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-4">
                <fieldset class="block">
                    <legend>Grade</legend>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="grades" value="0" CHECKED>
                        <label>All</label>
                    </div>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="grades" value="80">
                        <label>80% or less</label>
                    </div>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="grades" value="50">
                        <label>50% or less</label>
                    </div>
                </fieldset>
                <br>
                <div>
                    New to Homeroom in last <input type="number" name="last_assigned" style="width:50px;display:inline"> days
                </div>
                <?php
                if ($selected_schoolYear->getFirstSemLearningLogsClose('Y-m-d') != $selected_schoolYear->getLogSubmissionClose('Y-m-d')) :
                    ?>
                    <br>
                    <div>
                        <input type="number" min="0" name="zero_count_1st_sem" style="width:50px;display:inline"> # of Zeros in 1st semester
                    </div>
                    <br>
                    <div>
                        <input type="number" min="0" name="zero_count_2nd_sem" style="width:50px;display:inline"> # of Zeros in 2nd semester
                    </div>
                <?php
                else :
                    ?>
                    <br>
                    <div>
                        <input type="number" name="zero_count" style="width:50px;display:inline"> # of Zeros
                    </div>
                <?php
                endif;
                ?>
                <br>
                <div>
                    <input type="number" name="ex_count" style="width:50px;display:inline"> # of EX
                </div>
            </div>
            <div class="col-md-4">
                <fieldset class="block">
                    <legend>Homeroom</legend>
                    <div>
                        <?php
                        $_courses = courses::getTeacherHomerooms(core_user::getCurrentUser());
                        if ($_courses) {
                            foreach ($_courses  as $homeroom) { ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="homeroom[]" value="<?= $homeroom->getCourseId() ?>">
                                    <label>
                                        <?= $homeroom->getName() ?> (<?= $homeroom->getSchoolYear() ?>)
                                    </label>
                                </div>
                        <?php
                            }
                        } ?>
                    </div>
                </fieldset>
            </div>

        </div>
        <!-- <input type="button" value="Sync Users" id="pull-users">&nbsp;<span class="sync-status"></span> -->
        <hr>
        <button id="do_filter" class="btn btn-round btn-primary">Load</button>
        <!-- | <a  data-toggle="modal" data-target="#intervention_labels">Add/Edit Labels</a> -->
    </div>
</div>
<div class="card">
    <div class="card-header">
        Total Students: <span class="student_count_display"></span>
    </div>
    <div class="card-block pl-0 pr-0">
        <table id="homeroom_table" class="table responsive">
            <thead>
                <tr>
                    <th style="width:64px;"></th><!-- For arrow-->
                    <th> <input type="checkbox" title="Un/Check All" class="check-all"></th>
                    <th>Date Assigned</th>
                    <th>Student Last Name, First Name</th>
                    <th>Gender</th>
                    <th>Grade Level</th>
                    <th>Homeroom Grade</th>
                    <th># of Zeros in 1st semester</th>
                    <th># of Zeros in 2nd semester</th>
                    <th># of Zeros</th>
                    <th># of EX</th>
                    <th>Parent Email</th>
                    <th>Parent Phone</th>
                    <th>Parent Last Name, First Name</th>
                    <!-- <th># of 0</th> -->
                    <!-- <th>Notes</th> -->
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<div class="card">
    <div class="card-block">
        <button class="btn btn-round btn-primary" id="send-message">Send Message</button>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('homeroomsteacher', '/_/teacher/homeroom.js');
core_loader::addJsRef('gradeleveltool', core_config::getThemeURI() . '/assets/js/gradelevel.js');
core_loader::printFooter('admin');
?>
<script>
    $DataTable = null;
    var PAGE_SIZE = <?= Query::PAGE_SIZE ?>;

    $(function() {
        var $filter = $('#do_filter');
        var $form = $('#filter_form');

        $table = $('#homeroom_table');
        $DataTable = $table.DataTable({
            //bStateSave: true,
            pageLength: 25,
            columns: [{
                    data: 'arrow',
                    sortable: false
                },
                {
                    data: 'cb',
                    sortable: false
                },
                {
                    data: 'date_assigned'
                },
                {
                    data: 'student_name'
                },
                {
                    data: 'gender'
                },
                {
                    data: 'grade_level'
                },
                {
                    data: 'grade',
                    type: "num-fmt"
                },
                {
                    data: 'first_semester_zeros'
                },
                {
                    data: 'second_semester_zeros'
                },
                {
                    data: 'zeros'
                },
                {
                    data: 'ex'
                },
                {
                    data: 'parent_email'
                },
                {
                    data: 'parent_phone'
                },
                {
                    data: 'parent_name'
                },
                // { data: 'zeros' },
                // { data: 'notes', sortable: false },
            ],
            aaSorting: [
                [3, 'desc']
            ],
            iDisplayLength: 25
        });

        if ($('input[name ="zero_count"]').length) {
            $DataTable.column(7).visible(false);
            $DataTable.column(8).visible(false);
        } else {
            $DataTable.column(9).visible(false);
        }


        Homeroom.setPageSize(PAGE_SIZE);

        $filter.click(function() {
            $('.filter-status').show();
            Homeroom.resetTable = true;
            Homeroom.active_page = ($DataTable.page.info()).page;
            var data = $form.find('input,select').serialize();
            Homeroom.loadStudents(false, data);
        });

        $('#send-message').click(function() {
            if ($('.actionCB:checked').length == 0) {
                swal('', 'There is no student(s) selected.', 'error');
                return false;
            }
            global_popup_iframe('send_message_popup', '/_/teacher/send-message')
        });

        $('.check-all').change(function() {
            var check = $(this).is(':checked');
            $('.actionCB').prop("checked", check);
        });

    });
</script>