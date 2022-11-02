<?php

if (empty($year)) {
  $year = mth_schoolYear::getCurrent();
}

$applications = array();

if (!empty($_GET['form'])) {
  core_loader::formSubmitable('applicationBatch-' . $_GET['form']) || die();

  $doMidYear = false;
  if (isset($_POST['midYearActive'])) {
    $doMidYear = $_POST['midYearActive'] === 'on';
  }

  foreach ($_POST['applications'] as $applicationID) {
    if (!($application = mth_application::getApplicationByID($applicationID))) {
      continue;
    }
    if (!$application->accept($doMidYear)) {
      core_notify::addError('There was an error accepting the application for ' . $application->getStudent());
      break;
    }
  }
  header('location: /_/admin/applications');
  exit();
}

if (!empty($_GET['delete'])) {
  if (($application = mth_application::getApplicationByID($_GET['delete']))
    && (!mth_withdrawal::getByStudent($application->getStudentID(), $year->getID()) || mth_withdrawal::setActiveValue($application->getStudentID(), $year->getID(), 1))
    && $application->delete()
  ) {
    core_notify::addMessage('Application Deleted');
  } else {
    core_notify::addError('Unable to delete application');
  }
  header('location: /_/admin/applications');
  exit();
}

if (!empty($_GET['stackdelete'])) {
  if (is_array($_GET['stackdelete'])) {
    $error = 0;
    foreach ($_GET['stackdelete'] as $app) {
      if (!(($application = mth_application::getApplicationByID($app))
        && (!mth_withdrawal::getByStudent($application->getStudentID(), $year->getID()) || mth_withdrawal::setActiveValue($application->getStudentID(), $year->getID(), 1))
        && $application->delete())) {
        $error += 1;
      }
    }
    if ($error > 0) {
      core_notify::addError("$error records that are not deleted");
    } else {
      core_notify::addMessage('Applications Deleted');
    }
  } else {
    core_notify::addError('Unable to Delete');
  }
  exit();
}

if (!empty($_POST['stackhide'])) {
  if (is_array($_POST['stackhide'])) {
    $error = 0;
    foreach ($_POST['stackhide'] as $app) {
      if (!(($application = mth_application::getApplicationByID($app))
        && $application->hideSiblings())) {
        $error += 1;
      }
    }
    if ($error > 0) {
      core_notify::addError("$error records that are not hidden");
    } else {
      core_notify::addMessage('Applications hidden');
    }
  } else {
    core_notify::addError('Unable to Hide');
  }
  exit();
}

if (!empty($_POST['unhide'])) {
  if (is_array($_POST['unhide'])) {
    $error = 0;
    foreach ($_POST['unhide'] as $app) {
      if (!(($application = mth_application::getApplicationByID($app))
        && $application->unhideSiblings())) {
        $error += 1;
      }
    }
    if ($error > 0) {
      core_notify::addError("$error records that are not hidden");
    } else {
      core_notify::addMessage('Applications hidden');
    }
  } else {
    core_notify::addError('Unable to Hide');
  }
  exit();
}

if (!empty($_GET['stackmove'])) {
  if (is_array($_GET['stackmove']) && ($school_year = mth_schoolYear::getByStartYear($_GET['sy']))) {

    $error = 0;
    foreach ($_GET['stackmove'] as $app) {
      if ($application = mth_application::getApplicationByID($app)) {
        $application->setSchoolYear($school_year);
        $application->getStudent()->fixYearGradeLevel($school_year);
      } else {
        $error += 1;
      }
    }
    if ($error > 0) {
      core_notify::addError("$error records that are not moved");
    } else {
      core_notify::addMessage('Applications moved');
    }
  } else {
    core_notify::addError('Unable to move applications');
  }
  exit();
}

if (req_get::bool('moveToNextYear')) {
  if (!mth_schoolYear::getCurrent() || !mth_schoolYear::getNext()) {
    core_loader::redirect();
  }
  while ($application = mth_application::eachSubmittedApplication(mth_schoolYear::getCurrent())) {
    $application->setSchoolYear(mth_schoolYear::getNext());
    $application->getStudent()->populateNextYearGradeLevel();
  }
  core_loader::redirect();
}

$_GET['f'] = 1;

$fil = &$_SESSION['mth_application_filters'];
if (!is_object($fil)) {
  $fil = new req_array(array());
}
if (req_get::is_set('f')) {
  $fil = req_get::req_array();
}

if(req_get::bool('loadApplications')) {
    $applications = loadApplications();
    header('Content-type: application/json');
    echo json_encode($applications);
    exit();
}

function loadApplications() {
  error_reporting(E_ALL ^ E_WARNING); // TODO: better way to silence warnings in JSON output?
  $_statuses = [
    [
      'name'=> 'Sibling',
      'class'=> 'has_siblings',
      'id'=> 1
    ],
    [
      'name'=> 'New',
      'class'=> 'normal',
      'id'=> 2,
    ],
    [
      'name'=> 'Returning',
      'class'=> 'returning',
      'id'=> 3
    ]
  ];

  $applications = (new mth_application_query())
    ->setSPED(req_get::int_array('special_ed'))
    ->setGradeLevel(req_get::txt_array('grade'))
    ->setSchoolYear(req_get::txt_array('year'))
    ->joinEmailVerifier(req_get::int_array('verify'))
    ->setNoParentFilter()
    ->setStatus([mth_application::STATUS_SUBMITTED])
    ->setHidden(req_get::bool('showhidden') ? 1 : 0)
    ->getAll(req_get::int('page'));

  $appList = [];
  foreach ($applications as $application) {
    $student = $application->getStudent();
    if (!$student) {
      $application->delete();
      continue;
    }
    $current_year = mth_schoolYear::getCurrent();
    $next_year = mth_schoolYear::getNext();
    $parent = $student->getParent();
    $school_year = $application->getSchoolYear();
    $returningStudent = $student->isReturnStudent(mth_schoolYear::getByID($school_year));
    $hasSiblings = $student->hasActiveSiblings([$current_year, $school_year]);
    $status = $returningStudent ? $_statuses[2] : $hasSiblings ? $_statuses[0] : $_statuses[1];
    $appList[] = [
      'id'=> $application->getID(),
      'status' => $status,
      'date_submitted' => $application->getDateSubmitted('m/d/Y'),
      'school_year' => (string) $school_year,
      'student_id' => $application->getStudentID(),
      'student_name' => $student->getName(true),
      'grade_level' => ($student->getGradeLevelValue($application->getSchoolYearID())),
      'diploma' => $student->diplomaSeeking() ? 'Yes' : 'No',
      'special_ed' => $student->specialEd(true),
      'parent_id' => $student->getParentID(),
      'parent_name' => $parent ? $parent->getName(true) : 'No Parent',
      'city' => $application->getCityOfResidence(),
      'verified' => (($parent = $student->getParent()) && ($_verified = mth_emailverifier::getByUserId($parent->getUserID())) && $_verified->isVerified()),
    ];
  }
  return ['count' => count($appList), 'applications' => $appList, 'current_year_id' => (string) $current_year, 'next_year_id' => (string) $next_year,];
}

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Applications');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
<style type="text/css">
    #app_edit_popup {
        padding: 0;
    }

    #application-table_info {
        display: none;
    }

    .mth_application-has_siblings td {
        background-color: #ddf !IMPORTANT;
    }

    .mth_application-returning td {
        background-color: #FFddaa !IMPORTANT;
    }

    form table small {
        display: inline;
        color: #666;
    }

    #filterBlock {
        overflow: auto;
    }

    #filterBlock fieldset {
        margin-top: 0;
        float: left;
        margin-right: 10px;
        overflow: auto;
        padding: 10px 2%;
        max-width: 50%;
    }

    #filterBlock fieldset label {
        display: inline-block;
    }

    small {
        color: #999;
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
</style>
<?php
$currentYear = mth_schoolYear::getCurrent();
?>

    <input type="hidden" name="f" value="1">
    <button class="btn btn-round btn-success mb-20 master_show_button" onclick="filterApps()">Show</button>
<div class="card container-collapse">
  <?php if ($currentYear->isMidYearAvailable()) : ?>
    <?php if (mth_schoolYear::midYearAvailable()) : ?>
          <div class="p-3 alert-success">
              <h4>Accepting Mid-Year Applications</h4>
          </div>
    <?php else : ?>
          <div class="p-3 alert-danger">
              <h4> Not Accepting Mid-year Applications</h4>
          </div>
    <?php endif; ?>
  <?php endif; ?>
    <div class="card-header">
        <h4 class="card-title mb-0" data-toggle="collapse" aria-hidden="true" href="#application-filter-cont"
            aria-controls="application-filter-cont">
            <i class="panel-action icon md-chevron-right icon-collapse"></i> Filter
        </h4>
    </div>
    <div class="card-block collapse info-collapse" id="application-filter-cont">
        <div class="row">
            <div class="col">
                <fieldset>
                    <h4 class="card-header">Grade Level</h4>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" id="grade-all"
                               onclick="$('.gradeCB:checked').prop('checked',false);" <?= !$fil->bool('grade') ? 'checked' : '' ?>>
                        <label for="grade-all" onclick="$('.gradeCB:checked').prop('checked',false);">
                            All Grades
                        </label>
                    </div>
                  <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                      <div class="checkbox-custom checkbox-primary">
                          <input type="checkbox" name="grade[]" value="<?= $grade_level ?>"
                                 id="grade-<?= $grade_level ?>"
                                 onclick="$('#grade-all').prop('checked',false);" <?= in_array($grade_level, $fil->txt_array('grade')) ? 'checked' : '' ?>
                                 class="gradeCB">
                          <label for="grade-<?= $grade_level ?>" onclick="$('#grade-all').prop('checked',false);">
                            <?= $grade_desc ?>
                          </label>
                      </div>
                  <?php endforeach; ?>
                </fieldset>
                <fieldset>
                    <h4 class="card-header">School Year</h4>
                  <?php
                  $currentYear = mth_schoolYear::getCurrent();
                  $nextYear = mth_schoolYear::getNext();
                  ?>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="yearCB" name="year[]" value="<?= $currentYear->getID() ?>"
                               id="year-<?= $currentYear->getID() ?>" <?= in_array($currentYear->getID(), $fil->txt_array('year')) ? 'checked' : '' ?>
                               onclick="$('#year-all').prop('checked',false);">
                        <label for="year-<?= $currentYear ?>">
                          <?= $currentYear ?>
                        </label>
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="yearCB" name="year[]" value="<?= $nextYear->getID() ?>"
                               id="year-<?= $nextYear->getID() ?>" <?= in_array($nextYear->getID(), $fil->txt_array('year')) ? 'checked' : '' ?>
                               onclick="$('#year-all').prop('checked',false);">
                        <label for="year-<?= $nextYear ?>">
                          <?= $nextYear ?>
                        </label>
                    </div>
                </fieldset>
            </div>
            <div class="col">
                <h4 class="card-header">Special Ed</h4>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="special_ed[]" value="<?= mth_student::SPED_IEP ?>"
                           id="special_ed-<?= mth_student::SPED_IEP ?>" <?= in_array(mth_student::SPED_IEP, $fil->int_array('special_ed')) ? 'checked' : '' ?>>
                    <label for="special_ed-<?= mth_student::SPED_IEP ?>">
                      <?= mth_student::SPED_LABEL_IEP ?>
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="special_ed[]" value="<?= mth_student::SPED_504 ?>"
                           id="special_ed-<?= mth_student::SPED_504 ?>" <?= in_array(mth_student::SPED_504, $fil->int_array('special_ed')) ? 'checked' : '' ?>>
                    <label for="special_ed-<?= mth_student::SPED_504 ?>">
                      <?= mth_student::SPED_LABEL_504 ?>
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="special_ed[]" value="<?= mth_student::SPED_EXIT ?>"
                           id="special_ed-<?= mth_student::SPED_EXIT ?>" <?= in_array(mth_student::SPED_EXIT, $fil->int_array('special_ed')) ? 'checked' : '' ?>>
                    <label for="special_ed-<?= mth_student::SPED_EXIT ?>">
                      <?= mth_student::SPED_LABEL_EXIT ?>
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="special_ed[]" value="<?= mth_student::SPED_NO ?>"
                           id="special_ed-<?= mth_student::SPED_NO ?>" <?= in_array(mth_student::SPED_NO, $fil->int_array('special_ed')) ? 'checked' : '' ?>>
                    <label for="special_ed-<?= mth_student::SPED_NO ?>">
                        None
                    </label>
                </div>
                <h4 class="card-header">Status</h4>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="status[]"
                           value="1" <?= in_array(1, $fil->int_array('status')) ? 'checked' : '' ?>
                           id="sibling-status">
                    <label for="sibling-status">
                        Sibling
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="status[]"
                           value="2" <?= in_array(2, $fil->int_array('status')) ? 'checked' : '' ?> id="new-status">
                    <label for="new-status">
                        New
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="status[]"
                           value="3" <?= in_array(3, $fil->int_array('status')) ? 'checked' : '' ?>
                           id="returning-status">
                    <label for="returning-status">
                        Returning
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" id="showhidden" name="showhidden"
                           value="1" <?= $fil->bool('showhidden') ? 'CHECKED' : '' ?>>
                    <label>Hidden</label>
                </div>
                <h4 class="card-header">Parent Email Status</h4>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="verify[]"
                           value="1" <?= in_array(1, $fil->int_array('verify')) ? 'checked' : '' ?> id="verified">
                    <label for="verified-status">
                        Verified
                    </label>
                </div>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="verify[]"
                           value="0" <?= in_array(0, $fil->int_array('verify')) ? 'checked' : '' ?> id="unverified">
                    <label for="notverified-status">
                        Not Verified
                    </label>
                </div>
                <button class="btn btn-round btn-success mt-20" onclick="filterApps()">Filter</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <form action="?form=<?= uniqid() ?>" method="post">
        <div class="card-header">
            <span id="application_count"><?= count($applications) ?></span> Applications -
            <button type="submit" class="btn btn-round btn-primary">Accept</button>
            <button type="button" class="btn btn-round btn-danger" onclick="stackDelete()">Delete</button>
            <div class="btn-group">
                <button type="button" class="btn btn-round btn-primary" onclick="stackHide()">Hide</button>
                <button type="button" class="btn btn-round btn-info" onclick="stackunHide()">Unhide</button>
            </div>
          <?php if ($currentYear->isMidYearAvailable()) : ?>
              <input type="checkbox" id="midYearActive"
                     name="midYearActive" <?= mth_schoolYear::midYearAvailable() ? 'checked' : '' ?>>
              <label>Accept Applications as Mid-year</label>
          <?php endif; ?>
        </div>
        <div class="card-block pl-0 pr-0" id="application-table-container">
            <table id="application-table" class="table responsive">
                <thead>
                <tr>
                    <th>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" onclick="changeCheckboxStatus()" id="masterCB"
                                   title="Select/Deselect all">
                            <label></label>
                        </div>
                    </th>
                    <th>Submitted</th>
                    <th>Year</th>
                    <th>Student</th>
                    <th>Grade Level</th>
                    <th>Diploma</th>
                    <th>SPED</th>
                    <th>Parent</th>
                    <th>State</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button style="display:none" id="moveToCurYear" type="button" class="btn btn-round btn-warning"
                    onclick="stackMove(<?= mth_schoolYear::getCurrent()->getStartYear() ?>)">
                Move <?= mth_schoolYear::getNext() ?> Applications to <?= mth_schoolYear::getCurrent() ?></button>
            <span id="moveToNextYear" style="display: none">
                <button type="button" class="btn btn-round btn-primary"
                        onclick="stackMove(<?= mth_schoolYear::getNext()->getStartYear() ?>)">Move <?= mth_schoolYear::getCurrent() ?> Applications to <?= mth_schoolYear::getNext() ?></button>
            </span>
        </div>
    </form>
</div>

<?php
$nextYear = mth_schoolYear::getNext();
if (
  $currentYear
  && $nextYear
  && isset($years[$currentYear->getID()])
) {
  ?>
    <script>
        $('#moveToNextYear').show();
    </script>
  <?php
}
if (
  $currentYear
  && $nextYear
  && $currentYear->getID() != $nextYear->getID()
  && isset($years[$nextYear->getID()])
) {
  ?>
    <script>
        $('#moveToCurYear').show();
    </script>
  <?php
}
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('ApplicationTable', '/_/admin/applications/applications.js');
core_loader::printFooter('admin');
?>
<script src="/_/includes/datatable-custom.js"></script>
<script type="text/javascript">
    var $DataTable = null;
    var $filters = null;
    var PAGE_SIZE = <?= mth_application_query::PAGE_SIZE ?>;
    var error_sent = 0;

    $(function () {
        $filters = $('#application-filter-cont');
        $table = $('#application-table');

        $DataTable = $table.DataTable({
            pageLength: 25,
            dom: '<"top"<"top"lp>f>tri',
            'aoColumnDefs': [{
                "bSortable": false,
                "aTargets": [0, 11]
            },
                {
                    type: 'non-empty-date',
                    targets: 1
                }
            ],
            "aaSorting": [
                [1, 'asc']
            ],
            columns: [
                {data: 'cb', sortable: false},
                {data: 'submitted'},
                {data: 'year'},
                {data: 'student'},
                {data: 'grade_level'},
                {data: 'diploma'},
                {data: 'special_ed'},
                {data: 'parent'},
                {data: 'city'},
                {data: 'status'},
                {data: 'verified'},
                {data: 'actions', sortable: false},
            ],
            iDisplayLength: 25
        });

        ApplicationTable.setPageSize(PAGE_SIZE);
        filterApps();
    });

    function filterApps() {
        ApplicationTable.resetTable = true;
        ApplicationTable.active_page = ($DataTable.page.info()).page;
        var data = $filters.find('input').serialize();
        ApplicationTable.loadApplications(false, data);
    }

    function deleteApplication(applicationID) {
        deleteApplication.applicationID = applicationID;
        swal({
                title: "",
                text: "Are you sure you want to delete this application? This action cannot be undone!",
                type: "warning",
                showCancelButton: !0,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: !1,
                closeOnCancel: true
            },
            function () {
                location = '/_/admin/applications?delete=' + deleteApplication.applicationID;
            });
    }

    function stackMove(school_year) {
        var checked = $('[name="applications[]"]:checked').map(function () {
            return this.value * 1;
        }).get();

        if (checked.length == 0) {
            swal('', 'Please select atleast 1 record', 'warning');
            return;
        }

        swal({
                title: "",
                text: "Are you sure you want to move these applications?",
                type: "warning",
                showCancelButton: !0,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function () {
                $.ajax({
                    url: '/_/admin/applications',
                    type: 'GET',
                    data: {
                        stackmove: checked,
                        sy: school_year
                    },
                    success: function () {
                        location = '/_/admin/applications';
                    },
                    error: function () {
                        swal('', 'There is an error moving records.', 'warning');
                    }
                });
            });
    }

    function stackDelete() {
        var checked = $('[name="applications[]"]:checked').map(function () {
            return this.value * 1;
        }).get();

        if (checked.length == 0) {
            swal('', 'Please select atleast 1 record', 'warning');
            return;
        }


        swal({
                title: "",
                text: "Are you sure you want to delete these applications? This action cannot be undone!",
                type: "warning",
                showCancelButton: !0,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function () {
                $.ajax({
                    url: '/_/admin/applications',
                    type: 'GET',
                    data: {
                        stackdelete: checked
                    },
                    success: function () {
                        location = '/_/admin/applications';
                    },
                    error: function () {
                        swal('', 'There is an error deleting records.', 'warning');
                    }
                });
            });
    }

    function stackHide() {
        var checked = $('[name="applications[]"]:checked').map(function () {
            return this.value * 1;
        }).get();

        if (checked.length == 0) {
            swal('', 'Please select atleast 1 record', 'warning');
            return;
        }

        swal({
                title: "",
                text: "Are you sure you want to hide these applications?",
                type: "warning",
                showCancelButton: !0,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function () {
                $.ajax({
                    url: '/_/admin/applications',
                    type: 'POST',
                    data: {
                        stackhide: checked
                    },
                    success: function () {
                        location = '/_/admin/applications';
                    },
                    error: function () {
                        swal('', 'There is an error hiding records.', 'warning');
                    }
                });
            });
    }

    function stackunHide() {
        var checked = $('[name="applications[]"]:checked').map(function () {
            return this.value * 1;
        }).get();

        if (checked.length == 0) {
            swal('', 'Please select atleast 1 record', 'warning');
            return;
        }

        $.ajax({
            url: '/_/admin/applications',
            type: 'POST',
            data: {
                unhide: checked
            },
            success: function () {
                location = '/_/admin/applications';
            },
            error: function () {
                swal('', 'There is an error hiding records.', 'warning');
            }
        });
    }

    function showEditForm(applicationID) {
        global_popup_iframe('mth_application_edit', '/_/admin/applications/edit?app=' + applicationID);
    }

    function changeCheckboxStatus() {
        $('.applicationCB').prop('checked', changeCheckboxStatus.checked = !changeCheckboxStatus.checked);
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
</script>