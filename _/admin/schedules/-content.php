<?php

if ( !isset($year) && !($year = mth_schoolYear::getCurrent()) ) {
    exit('The current school year is not defined');
}

if (req_get::bool('ajax')) {
    header('Content-type: application/json');

    if ( req_get::bool('year') ) {
        $year = mth_schoolYear::getByID(req_get::int('year'));
    }

    function compileSchedule($schedule, $student, $year)
    {
        $periods = array();
        return array(
            'id' => $schedule ? $schedule->id() : '',
            'student' => $student->getLastName().', '.$student->getFirstName(),
            'student_id' => $student->getID(),
            'status' => '<a onclick="editSchedule('.($schedule ? $schedule->id() : '').')" class="mth_schedule_status link">'. ($schedule ? $schedule->status() : 'Not Started') .'</a>',
            'status_raw' => ($schedule ? $schedule->status() : 'Not Started'),
            'periods' => $periods,
            'last_modified' => $schedule ? $schedule->getLastModified('m/d/Y') : '',
            'submitted' => $schedule ? $schedule->date_submitted('m/d/Y') : '',
            'status_id' => $schedule ? $schedule->status(true) : ''
        );

    }

    switch (req_get::txt('ajax')) {
        case 'getAll':
            //filter value
            $draw = req_post::int('draw');
            $row = req_post::int('start');
            $rowPerPage = req_post::int('length');
            $columnIndex = $_POST['order'][0]['column'];
            $columnName = $_POST['columns'][$columnIndex]['data'];
            $columnSortOrder = $_POST['order'][0]['dir'];
            $searchValue = $_POST['search']['value'];
            $status = req_get::txt('status');

            // fetching table entries instances
            $filter = new mth_person_filter();
            $status_arr = explode(",", $status);
            $filter->setScheduleStatus($status_arr);
            $filter->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE, mth_student::STATUS_WITHDRAW,mth_student::STATUS_GRADUATED,mth_student::STATUS_TRANSITIONED]);
            $filter->setStatusYear($year->getID(), mth_person_filter::FILTER_STATUS_YEAR_ANY);
            $filter->hasSchedule(true);

            if ( !in_array("66", $status_arr) ) {
                $filter->setHasScheduleOnly(true);
            } 

            $filter->setSearchValue($searchValue);
            if ( null != req_post::txt_array('provider') ) {
                $filter->setProviders(req_post::txt_array('provider'));
            }
            if ( null != req_post::int('type') && req_post::int('type') != 0 ) {
                $filter->setCourseType(req_post::int('type') );
            }
            if ( null != req_post::int('periods')) {
                $filter->setMinimumPeriodCount(req_post::int('periods') );
            }

            $totalRecords = $filter->getAllCountWithoutFilter();
            $counts = $filter->getAllCounts();
            $filter->setPaginate(true);
            $filter->setPage( $row );
            $filter->setLimit( $rowPerPage );
            $filter->setSortField($columnName);
            $filter->setSortOrder($columnSortOrder);
            $filter->setPaginate(true);
            $filter->setSearchValue($searchValue);
            $students = $filter->getStudents();
            $totalRecordsWithFilter = count($students);
            $returnArr = [];

            $schedules = mth_schedule::getSchedulesByStudentIDs($filter->getStudentIDs(), $year);
            $scheduleAssoc = [];
            foreach( $schedules as $schedule ) {
                $scheduleAssoc[$schedule->student_id()] = $schedule;
            }
            
            foreach ($students as $student) {
                $schedule = isset($scheduleAssoc[$student->getID()]) ? $scheduleAssoc[$student->getID()] : [];
                $returnArr[] = compileSchedule($schedule, $student, $year);
            }

            $response = array(
                'draw' => intval($draw),
                'iTotalRecords' => $totalRecordsWithFilter,
                'iTotalDisplayRecords' => $totalRecords,
                'aaData' => $returnArr,
                'counts' => $counts
            );
            echo json_encode($response);
            break;
        case 'get':
            $schedule = mth_schedule::getByID(req_get::int('schedule_id'));
            echo json_encode( compileSchedule( $schedule, $schedule->student(), $year ) );
            break;
    }
    exit();
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Schedules');
cms_page::setPageContent('');

core_loader::addCssRef('schedules', core_path::getPath('schedules/schedules-styles.css')->getString());
core_loader::addJsRef('schedules', core_path::getPath('schedules/schedules-scripts.js')->getString());

core_loader::printHeader('admin');
?>
    <div class="mth_filter_block card container-collapse" id="schedules_filter_block">
        <div class="card-header">
            <h4 class="card-title mb-0" data-toggle="collapse" aria-hidden="true" href="#soe-filter-cont" aria-controls="soe-filter-cont">
                <i class="panel-action icon md-chevron-right icon-collapse"></i> Filter
            </h4>
        </div>
        <div class="card-block collapse info-collapse" id="soe-filter-cont">
            <div class="row">
                <div class="col-md-3" id="type">
                    <fieldset>
                        <legend>Type</legend>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="type" class="type-0" value="0">
                            <label>All Types</label>
                        </div>
                        <?php $course_types = mth_schedule_period::course_type_options(); ?>
                        <?php foreach ($course_types as $typeID => $type) : ?>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="type" class="type-<?= $typeID ?>" value="<?= $typeID ?>">
                            <label><?= $type ?></label>
                        </div>
                        <?php endforeach; ?>
                    </fieldset>
                </div>
                <div class="col-md-4" id="provider">
                    <fieldset>
                        <legend>Curriculum Provider</legend>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" class="provider-0" id="all-providers" name="provider[]" value="0">
                            <label>
                                All Providers
                            </label>
                        </div>
                        <?php while ($provider = mth_provider::each()): ?>
                            <?php if ( $provider->archived() ) {
                                continue;
                            } ?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="provider-<?= $provider->id() ?>" name="provider[]" value="<?= $provider->id() ?>">
                                <label>
                                    <?= $provider->name() ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </fieldset>
                </div>
                <div class="col-md-3" id="periods">
                    <fieldset>
                        <legend>Quantity of Periods</legend>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="periods" value="1" class="grade_all grade_selector period-1">
                            <label>
                                One or more
                            </label>
                        </div>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="periods" value="2" class="grade_k-8 grade_selector period-2">
                            <label>
                                Two or more
                            </label>
                        </div>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="periods" value="3" class="grade_9-12 grade_selector period-3">
                            <label>
                                Three or more
                            </label>
                        </div>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="periods" value="4" class="grade_9-12 grade_selector period-4">
                            <label>
                                Four or more
                            </label>
                        </div>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="periods" value="5" class="grade_9-12 grade_selector period-5">
                            <label>
                                Five or more
                            </label>
                        </div>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="periods" value="6" class="grade_9-12 grade_selector period-6">
                            <label>
                                Six or more
                            </label>
                        </div>
                    </fieldset>
                </div>
                <div class="col">
                    <fieldset>
                        <button class="btn btn-round btn-primary btn-block btn-filter">Filter</button>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div id="mth_schedule-count" class="card-block">
            <select name="year" title="School Year">
                <?php while ($selYear = mth_schoolYear::each()) : ?>
                    <option value="<?= $selYear->getID() ?>" <?= $selYear == $year ? 'selected' : '' ?>><?= $selYear ?></option>
                <?php endwhile; ?>
            </select>
            <?php foreach (mth_schedule::getStatusCounts($year) as $status => $count) {
                if ($status == mth_schedule::STATUS_ACCEPTED) {
                    continue;
                } ?>
                <label onclick="updateFilters()">
                    <input type="checkbox" class="status-cb"
                        value="<?= $status ?>"
                        onclick="updateFilters()">
                    <?= mth_schedule::status_option_text($status) ?>
                    <small id="status_count_<?= $status ?>">( <?= number_format($count) ?> )</small>
                </label> &nbsp;
            <?php } ?>
            <label onclick="updateFilters()">
                <input type="checkbox" class="status-cb"
                    value="66"
                    onclick="updateFilters()">
                    Not Started
                <small id="status_count_66">( 0 )</small>
            </label> &nbsp;
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <button type="button" class="btn btn-info btn-round waves-effect waves-light waves-round" onclick="sendReminder()">Send Reminder</button>
        </div>
        <div class="card-block pl-0 pr-0">
            <div id="main-content">
                <table id="mth_schedule-table" class="table responsive">
                    <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>Student</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function sendReminder() {
            global_popup_iframe('mth_schedule_reminder', '/_/admin/schedules/reminder');
        }
    </script>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');