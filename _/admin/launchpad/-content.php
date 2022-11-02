<?php

include 'functions.php';



$current_school_year = mth_schoolYear::getCurrent();
$current_year_id = $current_school_year->getID();

$first_sem_start = "";
$first_sem_start_ob = mth_sparkSetting::getByKey('first_sem_start', $current_year_id);
if ($first_sem_start_ob) {
    $first_sem_start = $first_sem_start_ob->value;
}

$second_sem_start = "";
$second_sem_start_ob = mth_sparkSetting::getByKey('second_sem_start', $current_year_id);
if ($second_sem_start_ob) {
    $second_sem_start = $second_sem_start_ob->value;
}
$sem_end = "";
$sem_end_ob = mth_sparkSetting::getByKey('sem_end', $current_year_id);
if ($sem_end_ob) {
    $sem_end = $sem_end_ob->value;
}

if (!empty($_POST['start_date1'])) {

    $start_date1 = $_POST['start_date1'];
    $start_date2 = $_POST['start_date2'];
    $close_date = $_POST['close_date'];

    $current_school_year = mth_schoolYear::getCurrent();
    $current_year_id = $current_school_year->getID();


    $test = mth_sparkSetting::saveSemDate($start_date1, $start_date2, $close_date, $current_year_id);

    core_notify::addMessage('success');
    core_loader::redirect();
}


// $enroll_provider_course = get_provider_course(1);
// echo json_encode($enroll_provider_course);
// exit;
$course_list_url = "https://tech.sparkeducation.com/api/courses/list";
$spark_res = spark_get_api("GET", $course_list_url);

$sparkMap = [];
foreach ($spark_res['data'] as $key => $value) {
    $spark_id = $value['id'];
    $sparkMap[$value['id']] = $value['name'];
}

$dashboard = get_dashboard_data($first_sem_start, $second_sem_start, $sem_end, $sparkMap);


if (!empty($_POST['sync_test'])) {
    $today = date('Y-m-d');

    if (strtotime($today) >= strtotime($sem_end)) {
        // execute end event
        end_year();
        core_notify::addMessage('success');
        core_loader::redirect();
    } elseif (strtotime($today) >= strtotime($second_sem_start)) {
        // execute second semester event
        $enroll_provider_course = get_provider_course(2);
        $wrong_course_ids =  register_user_second($enroll_provider_course, 1, $sparkMap);
    } elseif (strtotime($today) >= strtotime($first_sem_start)) {
        // execute first semester event
        $enroll_provider_course = get_provider_course(1);
        $wrong_course_ids = register_user($enroll_provider_course, 0, $sparkMap);
    }

    if (count($wrong_course_ids) > 0) {
        core_notify::addError('Sync Failed: Incorrect Spark course ID: ' . implode(", ", $wrong_course_ids));
    } else {
        core_notify::addMessage('success');
    }
    core_loader::redirect();
}

if (req_get::bool('get_launchpad_course')) {
    $courses = get_launchpad_course();
    header('Content-type: application/json');
    echo json_encode($courses);
    exit();
}


cms_page::setPageTitle('Launchpad Sync');
cms_page::setPageContent('');
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader('admin');
?>

<style>
    .border-td-0 td {
        border: 0 !important;
    }

    .even-striped tr:nth-child(even) {
        background-color: rgba(238, 238, 238, .3);
    }

    .line-h-47 {
        line-height: 47px;
    }

    .right-border {
        border-right: 1px solid #000000
    }

    .grey-bg {
        background: #FAFAFA;
    }

    .white-bg {
        background: #FFFFFF;
    }

    .rounded-8 {
        border-radius: 8px !important;
    }

    .top {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: baseline;
    }

    .dataTables_paginate {
        margin-left: 25px !important;
    }

    .btn-gradient-danger {
        background: linear-gradient(90deg, #730D07 0%, rgba(117, 13, 7, 0) 100%),
            linear-gradient(0deg, #D23C33, #D23C33) !important;
        color: white;
        border-radius: 8px;
        padding: 0.429rem 3rem;
    }

    .page-link {
        font-size: 14px;
        padding: 8px;
        border-radius: 28px;
        line-height: 11px;
        border: unset;
        color: rgb(65, 69, 255);
        font-weight: 700;
    }

    .page-item.active .page-link {
        background-color: rgba(0, 0, 0, 0.12);
        border-radius: 50%;
        color: rgb(65, 69, 255);
    }

    .page-link:focus,
    .page-link:hover {
        color: rgb(65, 69, 255);
        font-weight: 700;
        background-color: unset;
    }

    .table-header>tr>th:after,
    .table-header>tr>th:before {
        display: none !important;
    }

    .sorting_asc .sorting_asc_icon,
    .sorting_desc .sorting_desc_icon {
        display: inline-block !important;
    }

    .sorting_asc .sorting_desc_icon,
    .sorting_desc .sorting_asc_icon {
        display: none;
    }

    .page-item.next>.page-link {
        color: black
    }

    .page-item.previous>.page-link {
        color: black
    }

    .page-item.next.disabled .page-link {
        color: #bdbdbd;
    }

    .page-item.previous.disabled .page-link {
        color: #bdbdbd;
    }
</style>
<div class="row grey-bg pt-4">
    <div class="col-md-5">
        <div class="card-block p-0">
            <form id="sync-form" action="" method="post">
                <table class="table mb-0 border-td-0 white-bg rounded-8">
                    <tbody class="even-striped">
                        <tr>
                            <td>
                                <div class="row line-h-47">
                                    <div class="col-sm-6 right-border bold-text">1st Semester Sync</div>
                                    <div class="col-sm-6 d-flex align-items-center">
                                        <input type="text" name="start_date1" id="start_date1" placeholder="Start Date" class="form-control" value="<?php echo $first_sem_start; ?>">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="row line-h-47">
                                    <div class="col-sm-6 right-border bold-text">2nd Semester Sync</div>
                                    <div class="col-sm-6 d-flex align-items-center">
                                        <input type="text" name="start_date2" id="start_date2" placeholder="Start Date" class="form-control" value="<?php echo $second_sem_start; ?>">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="row line-h-47">
                                    <div class="col-sm-6 right-border bold-text">End of Year</div>
                                    <div class="col-sm-6 d-flex align-items-center">
                                        <input type="text" name="close_date" id="close_date" placeholder="Close Date" class="form-control" value="<?php echo $sem_end; ?>">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="d-flex mt-2">
                    <button type="submit" class="btn btn-primary btn-round waves-effect waves-light waves-round waves-effect waves-light waves-round ml-2 mr-2">Save</button>
                    <button type="reset" class="btn btn-secondary btn-round waves-effect waves-light waves-round waves-effect waves-light waves-round">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-1"></div>

    <div class="col-md-6">
        <div class="card-block p-0">
            <table class="table table-striped mb-0 border-td-0 white-bg rounded-8">
                <thead>
                    <tr>
                        <td></td>
                        <td>Pending</td>
                        <td>Enrolled</td>
                        <td class="bold-text">Total</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="bold-text">1st Semester</td>
                        <td><?php echo $dashboard['first_pending']; ?></td>
                        <td><?php echo $dashboard['first_enrolled']; ?></td>
                        <td class="bold-text"><?php echo $dashboard['first_total']; ?></td>
                    </tr>
                    <tr>
                        <td class="bold-text">2nd Semester</td>
                        <td><?php echo $dashboard['second_pending']; ?></td>
                        <td><?php echo $dashboard['second_enrolled']; ?></td>
                        <td class="bold-text"><?php echo $dashboard['second_total']; ?></td>
                    </tr>
                    <tr>
                        <td class="bold-text">Students</td>
                        <td><?php echo $dashboard['pending_student']; ?></td>
                        <td><?php echo $dashboard['enrolled_student']; ?></td>
                        <td class="bold-text"><?php echo $dashboard['total_student']; ?></td>
                    </tr>
                    <tr>
                        <td class="bold-text">Removed</td>
                        <td><?php echo $dashboard['pending_removed']; ?></td>
                        <td><?php echo $dashboard['enrolled_removed']; ?></td>
                        <td class="bold-text"><?php echo $dashboard['total_removed']; ?></td>
                    </tr>
                </tbody>

            </table>
        </div>
    </div>

    <div class="col-md-12" style="margin-top: 70px;">
        <div class="card-block pl-0 pr-0" id="launchpad-table-container">
            <table id="launchpad-table" class="table responsive">
                <thead class="table-header">
                    <tr>
                        <th>
                            <div class="d-flex justify-content-between align-items">
                                <span class="bold-text">Spark ID</span>
                                <i class="fa fa-caret-up sorting_asc_icon d-none" aria-hidden="true"></i>
                                <i class="fa fa-caret-down sorting_desc_icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th>
                            <div class="d-flex justify-content-between align-items">
                                <span class="bold-text">Provider</span>
                                <i class="fa fa-caret-up sorting_asc_icon d-none" aria-hidden="true"></i>
                                <i class="fa fa-caret-down sorting_desc_icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th>
                            <div class="d-flex justify-content-between align-items">
                                <span class="bold-text">Course Name</span>
                                <i class="fa fa-caret-up sorting_asc_icon d-none" aria-hidden="true"></i>
                                <i class="fa fa-caret-down sorting_desc_icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th>
                            <div class="d-flex justify-content-between align-items">
                                <span class="bold-text">Launchpad Course</span>
                                <i class="fa fa-caret-up sorting_asc_icon d-none" aria-hidden="true"></i>
                                <i class="fa fa-caret-down sorting_desc_icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th>
                            <div class="d-flex justify-content-between align-items">
                                <span class="bold-text">1st Semester</span>
                                <i class="fa fa-caret-up sorting_asc_icon d-none" aria-hidden="true"></i>
                                <i class="fa fa-caret-down sorting_desc_icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th>
                            <div class="d-flex justify-content-between align-items">
                                <span class="bold-text">2nd Semester</span>
                                <i class="fa fa-caret-up sorting_asc_icon d-none" aria-hidden="true"></i>
                                <i class="fa fa-caret-down sorting_desc_icon" aria-hidden="true"></i>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <form action="" id="sync-period-form" method="post">
        <input type="hidden" name="sync_test" value="llll">
    </form>
</div>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('LaunchpadTable', '/_/admin/launchpad/launchpad.js');
core_loader::printFooter('admin');
?>
<script>
    $(function() {
        $('#sync-form input[type="text"]').datepicker({
            minDate: "-20Y",
            maxDate: "+100Y",
            changeMonth: true,
            changeYear: true
        });
    });

    var $DataTable = null;
    var $filters = null;
    var error_sent = 0;

    $(function() {
        $table = $('#launchpad-table');

        $DataTable = $table.DataTable({
            pageLength: 25,
            buttons: [{
                text: 'Manually Sync',
                className: 'btn-gradient-danger',
                action: function(e, dt, node, config) {
                    $("#sync-period-form").submit();
                    // alert('Button activated');
                }
            }],
            lengthMenu: [
                [25, 50, 100, -1],
                [25, 50, 100, 'All'],
            ],
            stateSave: true,
            "language": {
                "paginate": {
                    "previous": "&lt;",
                    "next": "&gt;",
                },
                sLengthMenu: "Show _MENU_"
            },
            dom: '<"top"B<"top"lp>>',
            order: [
                [2, 'asc']
            ],
            columns: [{
                    data: 'sparkID',
                },
                {
                    data: 'provider'
                },
                {
                    data: 'course'
                },
                {
                    data: 'launchpadCourse'
                },
                {
                    data: 'semester1'
                },
                {
                    data: 'semester2'
                }
            ]
        });
        filterApps();
    });

    function filterApps() {
        LaunchpadTable.resetTable = true;
        LaunchpadTable.active_page = ($DataTable.page.info()).page;
        LaunchpadTable.loadCourse();
    }
</script>