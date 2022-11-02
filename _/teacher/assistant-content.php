<?php

use mth\yoda\courses;
use mth\yoda\homeroom\Query;

$selected_schoolYear = mth_schoolYear::getCurrent();

/**
 * check if $_REQUEST param is set
 *
 * @param string $param param name
 * @param string $type  method
 * @return void
 */
function req_isset($param, $type)
{
    if (!(req_post::is_set($param) || req_get::is_set($param))) {
        return null;
    }

    $method = req_post::is_set($param) ? 'post' : 'get';

    return  call_user_func(array("req_$method", $type), $param);
}

function prepForCSV($value)
{
    $value = req_sanitize::txt_decode($value);
    $quotes = false;
    if (strpos($value, '"') !== false) {
        $value = str_replace('"', '""', $value);
        $quotes = true;
    }
    if (!$quotes && (strpos($value, ',') !== false || strpos($value, "\n") !== false)) {
        $quotes = true;
    }
    if ($quotes) {
        $value = '"' . trim($value) . '"';
    }
    return $value;
}

function load_homeroom($selected_schoolYear, $report = false)
{
    $COLUMNS = [
        'Student Last Name, First Name', 'Gender', 'Grade Level', 'Homeroom Grade', '# of Zeros', '# of EX'
        // ,'Parent Email'
        // ,'Parent Phone'
        // ,'Parent Last Name, First Name'
    ];
    $return = $report ? [$COLUMNS] : [];
    $_grades =  req_isset('grades', 'int');
    $_gradelevel = req_isset('grade', 'int_array');
    $_homerooms = req_isset('homeroom', 'int_array');
    $_last_assigned = req_isset('last_assigned', 'int');
    $_zero_count = req_isset('zero_count', 'txt');
    $_zero_count = trim($_zero_count) == '' ? null : $_zero_count;

    $_ex_count = req_isset('ex_count', 'txt');
    $_ex_count = trim($_ex_count) == '' ? null : $_ex_count;


    $days = !empty($_last_assigned) ? intval($_last_assigned) : 0;

    $query = new Query();
    $query->setYear([$selected_schoolYear->getID()]);

    $assistant_object = $_SESSION['assistant'];
    if ($assistant_object) {
        if(empty($assistant_object['values'])){
            return ['count' => 0, 'filtered' => []];
        }

        if ($assistant_object['type'] ==  mth_assistant::TYPE_SCHOOL) {
            $query->setSchool($assistant_object['values']);
        } elseif ($assistant_object['type'] ==  mth_assistant::TYPE_PROVIDER) {
            $query->setProvider($assistant_object['values'], $selected_schoolYear->getID());
        } elseif ($assistant_object['type'] ==  mth_assistant::TYPE_IEP) {
            $query->setSped($assistant_object['values']);
        }
    }else{
        return ['count' => 0, 'filtered' => []];
    }


    if ($_homerooms) {
        $query->setHomerom($_homerooms);
    }

    if($_gradelevel){
        $query->setGradeLevel($_gradelevel,$selected_schoolYear->getID());
    }

    $query
    ->selectGrade()
    ->selectZeros()
    ->selectEx()
    ->selectGradeLevel();

    if ($enrollments = $query->getAll(req_get::int('page'))) {
        foreach ($enrollments as $enrollment) {

            $stgrade = $enrollment->getAveGrade();
            $zeros = $enrollment->getZeroCount();
            $ex = $enrollment->getExCount();
            $gradelevel = $enrollment->getGradeLevel();

            $assigned_date = $enrollment->getDateAssigned('m/d/Y');

            if($_zero_count!==null && $_zero_count > $zeros){
                continue;
            }

            if($_ex_count!==null && $_ex_count > $ex){
                continue;
            }

            if($_grades && ($stgrade!=null && $stgrade > $_grades)){
                continue;
            }

            if (!$student = $enrollment->student()) {
                continue;
            }

            if ($student->isStatus(mth_student::STATUS_WITHDRAW, $selected_schoolYear)) {
                $enrollment->delete();
                continue;
            }

        

            if (!($parent = $student->getParent())) {
                core_notify::addError('Parent Missing for ' . $student);
                continue;
            }

            // if ($_last_assigned && $_last_assigned < strtotime($enrollment->getDateAssigned())) { }


            if ($days != 0 && strtotime($assigned_date) < strtotime("-" . ($days + 1) . " days")) {
                continue;
            }

            $data = [
                'student_name' => $student->getPreferredLastName() . ', ' . $student->getPreferredFirstName(),
                'gender' => $student->getGender(),
                'grade_level' => $gradelevel,
                'grade' => $stgrade === null ? 'NA' : $stgrade . '%',
                'zeros' => $zeros,
                'ex' => $ex
            ];

            if (!$report) {
                $data = array_merge($data, [
                    'date_assigned' => $assigned_date,
                    'id' => $student->getID(),
                    'notes' => 0,
                    'homeroom' => $enrollment->getCourseId(),
                    'parentid' => $parent->getID(),
                    'slug' => $student->getSlug(),
                    'pemail' => $parent->getEmail(),
                    'pphone' => (string) $parent->getPhone(),
                    'parent_name' => $parent->getPreferredLastName() . ', ' . $parent->getPreferredFirstName()
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
    }

    return ['count' => count($enrollments), 'filtered' => $return];
}
if (req_get::bool('csv')) {
    $students = load_homeroom($selected_schoolYear, true);
    $file = 'GradeReport';
    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename="' . $file . $selected_schoolYear . '.csv"');

    foreach ($students['filtered'] as $row) {
        echo implode(',', array_map('prepForCSV', $row)) . "\n";
    }

    exit();
}

if (req_get::bool('loadHomeroom')) {
    $students = load_homeroom($selected_schoolYear);
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}

core_loader::includeBootstrapDataTables('css');
core_loader::addCssRef('btndtrcss','https://cdn.datatables.net/buttons/1.5.6/css/buttons.dataTables.min.css');
cms_page::setPageTitle('Homeroom');
cms_page::setPageContent('');
core_loader::printHeader('teacher');
?>

<?php
$assistant = &$_SESSION['assistant'];

if (!$assistant) {
    if ($assistant_a = core_user::getAssistantObject()) {
        $assistant_value = [];
        foreach ($assistant_a as $index => $_assistant) {
            $assistant_value[] = $_assistant->getValue();
        }

        $assistant['type'] = isset($assistant_a[0]) ? $assistant_a[0]->getType() : null;
        if (!is_null($assistant['type'])) {
            $assistant['values'] =  $assistant_value;
        }
    }
}
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

<div class="alert alert-info">
    <?php if($assistant):?>
        Hi <?=mth_assistant::getTypeLabel($assistant['type'])?> you are assigned to students under <b><?=implode('</b>, <b>',mth_assistant::getArrayValues($assistant['type'],$assistant['values']))?></b> 
    <?php endif;?>
</div>
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
                            Grades K-8
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
                    New to Homeroom in last <input type="text" class="form-control" name="last_assigned" style="width:50px;display:inline"> days
                </div>
                <div>
                    <input type="number" name="zero_count" style="width:50px;display:inline"> # of Zeros
                </div>
                <br>
                <div>
                    <input type="number" name="ex_count" style="width:50px;display:inline"> # of EX
                </div>
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
        Total Students: <span class="student_count_display"></span> |
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
                    <th># of Zeros</th>
                    <th># of EX</th>
                    <th>Parent Email</th>
                    <th>Parent Phone</th>
                    <th>Parent Last Name, First Name</th>
                    <!-- <th>Notes</th> -->
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('cdndtbtn','https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js');
core_loader::addJsRef('cdndtbtnhtlm5','https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js');
core_loader::addJsRef('cdndtbtnflash','https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js');
core_loader::addJsRef('homeroomsteacher', '/_/teacher/homeroom.js');
core_loader::addJsRef('gradeleveltool',core_config::getThemeURI().'/assets/js/gradelevel.js');
core_loader::printFooter('admin');
?>
<script>
    function getCSV() {
        location.href = '?csv=1&' + $('.filter-status').find('input,select').serialize();
    }
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
                // { data: 'notes', sortable: false },
            ],
            aaSorting: [
                [3, 'desc']
            ],
            iDisplayLength: 25,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    text: 'Download CSV',
                    exportOptions: {
                        columns: [2,3,4,5,6,7,8,9,10,11],
                        modifier: {
                            search: 'none'
                        }
                    }
                }
            ]
        });

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

        $filter.trigger('click');

    });
</script>

