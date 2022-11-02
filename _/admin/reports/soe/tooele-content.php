<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');
/* @var $year mth_schoolYear */
$yearId = $year->getID();

$file = 'Tooele Master Report - ' . $year;
$reportArr = [[
    'Date Assigned to SoE',
    'Student Legal Last Name',
    'Student Legal First Name',
    'Legal Student Middle',
    'DOB',
    'Grade',
    'Year of Graduation',
    'Student District of Residence',
    $year . ' Status',
    $year . ' Status - Returning, New, or Transferred?',
    'Previous School of Enrollment'
]];
$currentLoad = true;

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$yearId]);
$filter->setSchoolOfEnrollment(\mth\student\SchoolOfEnrollment::Tooele);
$studentIds = $filter->getStudentIDs();
$stu = new mth_student();
if (req_get::bool('google') || req_get::bool('csv')) {
    $allStudents = $stu->getAllStudents(array('StudentID' => $studentIds));

    $packets = [];
    foreach (mth_packet::getStudentsPackets($studentIds) as $packet) {
        /** @var mth_packet $packet */
        if (!isset($packets[$packet->getStudentID()])) {
            $packets[$packet->getStudentID()] = $packet;
        }
    }

    foreach ($allStudents as $student) {
        $studentId = $student->getID();
        if (!(array_key_exists($studentId, $packets) && ($packet = $packets[$studentId]))) {
            core_notify::addError('Packet Missing for ' . $student);
            continue;
        }
        $parent = $student->getParent();
        $address = $parent->getAddress();
        $schoolDistrict =  $address->getSchoolDistrictOfR();
        $grade_level = $student->getGradeLevelValue($yearId);
        $level = sprintf('%02d', $grade_level);
        $reportArr[] = [
            ($packet ? $packet->dateAssignedToSoe(false, 'm/d/Y') : ''),
            $student->getLastName(),
            $student->getFirstName(),
            $student->getMiddleName(),
            $student->getDateOfBirth('m/d/Y'),
            $grade_level == 'K' ? 'K' : (string)$level,
            $year->getDateEnd('Y') + (12 - (intval($grade_level))),
            ($schoolDistrict ? $schoolDistrict : ''),
            ($student->isNewFromSOE(\mth\student\SchoolOfEnrollment::Tooele) ? 'New' : 'Return'),
            $student->getSOEStatus($year, $packet),
            $packet->getLastSchoolName()
        ];
    }


    if (req_get::bool('google')) {
        include ROOT . core_path::getPath('../report.php');
        exit();
    } elseif (req_get::bool('csv')) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '.csv"');
        $output = fopen('php://output', 'w');
        foreach ($reportArr as $data_item) {
            fputcsv($output, $data_item);
        }
        exit();
    }
}


if (req_get::is_set('ajax')) {
    // $draw = req_post::int('draw');
    // $row = req_post::int('start');
    // $rowPerPage = req_post::int('length');
    // $columnIndex = $_POST['order'][0]['column'];
    // $columnName = $_POST['columns'][$columnIndex]['data'];
    // $columnSortOrder = $_POST['order'][0]['dir'];
    // $searchValue = $_POST['search']['value'];

    // $stu->setPaginate(true);
    // $stu->setPage($row);
    // $stu->setLimit($rowPerPage);
    // $stu->setSortField($columnName);
    // $stu->setSortOrder($columnSortOrder);
    // $stu->setSearchValue($searchValue);
    $allCounts = $stu->getFilteredStudentCount(array('StudentID' => $studentIds));


    $packets = [];
    foreach (mth_packet::getStudentsPackets($studentIds) as $packet) {
        /** @var mth_packet $packet */
        if (!isset($packets[$packet->getStudentID()])) {
            $packets[$packet->getStudentID()] = $packet;
        }
    }

    if ($allCounts == 0) {
        $response = array(
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => []
        );

        echo json_encode($response);
        exit();
    }
    // var_dump(mth_student::getStudents(array('StudentID' => $studentIds)));exit;
    foreach (mth_student::getStudents(array('StudentID' => $studentIds)) as $student) {
        $studentId = $student->getID();
        if (!(array_key_exists($studentId, $packets) && ($packet = $packets[$studentId]))) {
            core_notify::addError('Packet Missing for ' . $student);
            // continue;
        }
        $parent = $student->getParent();
        // $address = $parent->getAddress();
        // $schoolDistrict =  $address->getSchoolDistrictOfR();
        $grade_level = $student->getGradeLevelValue($yearId);
        $level = sprintf('%02d', $grade_level);
        $data[] = [
            'date_soe' => ($student->date_assigned_to_soe ? date_format(date_create($student->date_assigned_to_soe), "m/d/Y") : ""),
            'last_name' => $student->getLastName(),
            'first_name' => $student->getFirstName(),
            'middle_name' => $student->getMiddleName(),
            'birthday' => ($student->date_of_birth ? date_format(date_create($student->date_of_birth), "m/d/Y") : ""),
            'grade_level' => $grade_level == 'K' ? 'K' : (string)$level,
            'graduation' => $year->getDateEnd('Y') + (12 - (intval($grade_level))),
            'district' => $student->school_district,
            'status' => ($student->isNewFromSOE(\mth\student\SchoolOfEnrollment::Tooele) ? 'New' : 'Return'),
            'status_year' => $student->getSOEStatus($year, $packet),
            'p_soe' => $packet->getLastSchoolName()
        ];
    }

    // $response = array(
    //     "draw" => $allCounts == 0 ? 0 : intval($draw),
    //     "recordsTotal" => $allCounts,
    //     "recordsFiltered" => $allCounts,
    //     "data" => $allCounts == 0 ? [] : $data
    // );

    echo json_encode($data);
    exit();
}




core_loader::includeBootstrapDataTables('css');

core_loader::isPopUp();
core_loader::printHeader();
$popup_id = isset($_popup_id) ? $_popup_id : 'reportPopup';
?>
<style>
    #mth-reports-tooele {
        font-size: 12px;
    }


    .dataTables_filter {
        float: none;
        text-align: left;
    }
</style>

<div class="iframe-actions">
    <button type="button" title="Send to Google" class="btn btn-round btn-secondary" onclick="window.open((location.search?location.search+'&':'?')+'google=1')">
        <i class="fa fa-google hidden-md-up"></i><span class="hidden-sm-down">Send to Google</span>
    </button>
    <button type="button" title="Download CSV" class="btn btn-round btn-secondary" onclick="location=(location.search?location.search+'&':'?')+'csv=1'">
        <i class="fa fa-download hidden-md-up"></i><span class="hidden-sm-down">Download CSV</span>
    </button>
    <button type="button" title="Close" class="btn btn-round btn-secondary" onclick="top.global_popup_iframe_close(<?= "'$popup_id'" ?>)">
        <i class="fa fa-close hidden-md-up"></i><span class="hidden-sm-down">Close</span>
    </button>
</div>
<h2><?= $file ?></h2>
<div class="card">
    <div class="card-block pl-0 pr-0">
        <table id="mth-reports-tooele" class='table display dataTable responsive'>
            <thead>
                <tr>
                    <th>Date Assigned to SoE</th>
                    <th>Student Legal Last Name</th>
                    <th>Student Legal First Name</th>
                    <th>Legal Student Middle</th>
                    <th>DOB</th>
                    <th>Grade</th>
                    <th>Year of Graduation</th>
                    <th>Student District of Residence</th>
                    <th><?php echo ($year . ' Status') ?></th>
                    <th><?php echo ($year . ' Status - Returning, New, or Transferred?') ?></th>
                    <th>Previous School of Enrollment</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>
<script>
    $(function() {
        var columndDefs = $.parseJSON('<?= isset($columnDefs) ? json_encode($columnDefs) : json_encode([]) ?>');
        sortDef = $.parseJSON('<?= isset($sortDef) ? json_encode($sortDef) : json_encode([]) ?>');

        var $table = $('#mth-reports-tooele').DataTable({
            // 'processing': true,
            // 'serverSide': true,
            bStateSave: false,
            bPaginate: true,
            pageLength: 25,
            'ajax': {
                'url': '?ajax=master',
                'type': 'POST',
                'dataType': 'json',
                success: function(response) {
                    $table.rows().remove();
                    $.each(response, function(index, studentObj) {
                        $table.row.add(studentObj);
                    });
                    $table.draw();
                }
            },
            aaSorting: [
                [1, 'asc']
            ],
            'columns': [{
                    data: 'date_soe'
                },
                {
                    data: 'last_name'
                },
                {
                    data: 'first_name'
                },
                {
                    data: 'middle_name'
                },
                {
                    data: 'birthday'
                },
                {
                    data: 'grade_level'
                },
                {
                    data: 'graduation'
                },
                {
                    data: 'district'
                },
                {
                    data: 'status'
                },
                {
                    data: 'status_year'
                },
                {
                    data: 'p_soe'
                }
            ]
        })
    });
</script>