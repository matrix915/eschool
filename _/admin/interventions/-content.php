<?php

include 'functions.php';

$selected_schoolYear = req_get::bool('y') ? mth_schoolYear::getByStartYear(req_get::int('y')) : mth_schoolYear::getCurrent();
$selected_schoolYear_id = $selected_schoolYear->getID();

$savingIntervention = false;//flag if there is still a running insert intervention
// if(req_get::is_set('csv')){

//     $students = load_interventions($selected_schoolYear,true);
//     $file = 'Intervention';
//     header('Content-type: text/csv');
//     header('Content-Disposition: attachment; filename="' . $file.$selected_schoolYear . '.csv"');

//     foreach ($students['filtered'] as $row) {
//         echo implode(',', array_map('prepForCSV', $row)) . "\n";
//     }

//     exit();
// }

if (req_get::bool('loadIntervention')) {
  $students = load_interventions($selected_schoolYear);
  header('Content-type: application/json');
  echo json_encode($students);
  exit();
}

if (req_get::is_set('ajax')) {
  header('Content-type: application/json');

  switch (req_get::txt('ajax')) {
    case 'send':
      if (req_get::is_set('student')) {
        $student =  mth_student::getByStudentID(req_get::int('student'));
        echo  json_encode(save(req_get::int('type'), $student, $selected_schoolYear));
      }
      break;
    case 'save-label':
      if (req_post::is_set('label')) {
        $label_id = req_post::int('label');
        $intervention_id =  req_post::int('intervention');
        if (!($student =  mth_student::getByStudentID(req_post::int('student')))) {
          exit(json_encode([
            'error' => 1,
            'message' => 'Was not able to find student\'s record'
          ]));
        }
        if(!$savingIntervention){//checks if it is still saving
          $savingIntervention = true;
          $intervention = save_intervention($student, $selected_schoolYear, 'id', [
            'intervention_id' => $intervention_id,
            'label_id' => $label_id
          ]);
          $savingIntervention = false;
          exit(json_encode($intervention));
        }
      }
      break;
    case 'label':
      if (req_post::is_set('label')) {

        $_label = req_post::int('label');
        $label = new mth_label();
        if ($_label) {
          $label->labelId($_label);
        }

        if (req_get::is_set('delete')) {
          if ($label->delete()) {
            exit(json_encode([
              'error' => 0,
              'message' => 'Label Deleted'
            ]));
          } else {
            exit(json_encode([
              'error' => 1,
              'message' => 'Unable to delete label'
            ]));
          }
        }

        $label->userId(core_user::getUserID());
        $label->name(req_post::txt('label_name'));
        if (!$label->save()) {
          exit(json_encode([
            'error' => 1,
            'message' => 'Unable to Save Label'
          ]));
        }
        exit(json_encode([
          'error' => 0,
          'message' => $label->getID()
        ]));
      } else {
        exit(json_encode(mth_label::all()->toArray()));
      }
      break;
  }
  exit();
}

core_loader::includeBootstrapDataTables('css');
core_loader::addCssRef('btndtrcss', 'https://cdn.datatables.net/buttons/1.5.6/css/buttons.dataTables.min.css');

cms_page::setPageTitle('Interventions');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>

  <script>
    var PAGE_SIZE = <?= \mth\intervention\Query::PAGE_SIZE ?>;
    var CURRENT_YEAR = <?= $selected_schoolYear->getStartYear() ?>;
  </script>

  <style>
    #first-notice {
      background: orange;
      color: #fff;
    }

    #final-notice {
      background: red;
      color: #fff;
    }

    .headsup-not,
    .probation-not,
    .consecutive-ex-not {
      padding: 5px;
    }

    .final-not {
      /* background:red; */
      padding: 5px;
      color: red;
    }

    .consecutive-ex-not {
      color: #43a047;
    }

    .probation-not {
      color: #2196f3;
    }

    .first-not {
      /* background:orange; */
      padding: 5px;
      color: orange;
    }

    #interventions_table {
      font-size: 12px;
    }

    #interventions_table td {
      padding: 2px;
      vertical-align: middle;
    }

    /* table.dataTable.dtr-inline.collapsed>tbody>tr[role="row"]>td:first-child{
    padding-right: 18px !important;
} */

    td a {
      color: #1e88e5 !important;
    }

    table.dataTable.dtr-inline.collapsed>tbody>tr[role="row"]>td:first-child:before,
    table.dataTable.dtr-inline.collapsed>tbody>tr[role="row"]>th:first-child:before {
      left: 0px !important;
    }

    .fixed-button-bar {
      position: fixed;
      bottom: 7px;
      right: 4px;
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
        <div class="col-md-3">
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
            <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="mid_year" value="1" id="mid_year">
              <label for="mid_year">
                Mid-year
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
          <!-- <br>
                <fieldset class="block">
                <legend>Days since last activity</legend>
                    <p>
                        <span>No activity in the last <input type="text" name="days" style="width:35px"> days</blockquote>
                        </p>
                </fieldset> -->
          <br>
          <fieldset class="block">
            <legend>Labels</legend>
            <div id="filter-labels"></div>
          </fieldset>
        </div>
        <div class="col-md-5">
          <fieldset class="block">
            <legend>Email Sent</legend>
            <?php foreach (mth_offensenotif::getTypes() as $key => $type) : ?>
              <div class="checkbox-custom checkbox-primary">
                <input type="checkbox" name="email[]" value="<?= $key ?>">
                <label><?= $type ?></label>
              </div>
            <?php endforeach; ?>
          </fieldset>
          <?php
          if ($selected_schoolYear->getFirstSemLearningLogsClose('Y-m-d') != $selected_schoolYear->getLogSubmissionClose('Y-m-d')) :
            ?>
            <br>
            <div>
              <input type="number" min="0" name="zero_count_1st_sem" style="width:50px;display:inline"> Minimum # of Zeros in 1st semester
            </div>
            <br>
            <div>
              <input type="number" min="0" name="zero_count_2nd_sem" style="width:50px;display:inline"> Minimum # of Zeros in 2nd semester
            </div>
          <?php
          else :
            ?>
            <br>
            <div>
              <input type="number" min="0" name="zero_count" style="width:50px;display:inline"> Minimum # of Zeros
            </div>
          <?php
          endif;
          ?>
          <br>
          <div>
            <input type="number" min="0" name="ex_count" style="width:50px;display:inline"> Minimum # of EX
          </div>
        </div>
      </div>
      <!-- <input type="button" value="Sync Users" id="pull-users">&nbsp;<span class="sync-status"></span> -->
      <hr>
      <button id="do_filter" class="btn btn-round btn-primary">Load</button> | <a data-toggle="modal" data-target="#intervention_labels">Add/Edit Labels</a>
    </div>
  </div>
  <!-- <div class="card">
    <div class="card-block">
        <button type="button"  id="dowloadcsv" class="btn btn-secondary btn-round">Download CSV</button>
    </div>
</div> -->
  <div class="card">
    <div class="card-header">
      Total Students: <span class="student_count_display"></span> |
      <select onchange="location.href=this.value">
        <?php while ($sy = mth_schoolYear::limit(mth_schoolYear::getCurrent())) : ?>
          <option <?= $sy->getStartYear() == $selected_schoolYear->getStartYear() ? 'SELECTED' : '' ?> value="?y=<?= $sy->getStartYear() ?>">
            <?= $sy ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="card-block pl-0 pr-0">
      <table id="interventions_table" class="table responsive">
        <thead>
          <tr>
            <!-- <th>
                        <input type="checkbox" title="Un/Check All" class="check-all">
                    </th> -->
            <th style="width:64px;"></th><!-- For arrow-->
            <th></th>
            <th>Email Sent</th>
            <th>Date Sent</th>
            <th>Due Date</th>
            <th>Label</th>
            <th>Student Last Name, First Name</th>
            <th>Gender</th>
            <th>Grade Level</th>
            <th>1st sem # of 0</th>
            <th>2nd sem # of 0</th>
            <th># of 0</th>
            <th># of EX</th>
            <th>Cons EX</th>
            <th>Grade</th>
            <th>Mid-Year</th>
            <th>Parent Email</th>
            <th>SOE</th>
            <th>Parent Phone</th>
            <th>Parent Last Name, First Name</th>
            <!-- <th>Last Activity</th> -->
            <th>Notes</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>


  <div class="fixed-button-bar">
    <span class="sync-status"></span>
    <button type="button" id="exceed-ex" class="btn btn-success btn-round">Exceed EX</button>
    <button type="button" id="missinglog-notice" class="btn btn-pink btn-round">Missing Log</button>
    <button type="button" id="headsup-notice" class="btn btn-secondary btn-round">Send Heads Up</button>
    <button type="button" id="first-notice" class="btn btn-warning btn-round">Send First Notice</button>
    <button type="button" id="final-notice" class="btn btn-danger btn-round">Send Final Notice</button>
    <button type="button" id="consecutive-ex" class="btn btn-success btn-round">Max EX</button>
    <button type="button" id="probation" class="btn btn-primary btn-round">Probation</button>
  </div>
  <!-- Select Label Modal -->
  <div class="modal fade" id="intervention_label_select" tabindex="-1" role="dialog" aria-labelledby="intervention_label_select" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Select Labels</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <select name="selected_label" class="form-control">
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-primary btn-round" id="update-intervention-label">Save</button>
          <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
        </div>
      </div>
    </div>
  </div>
  <!-- End Select Label Modal -->
  <!-- Change Student Email Modal -->
  <div class="modal fade" id="intervention_labels" tabindex="-1" role="dialog" aria-labelledby="intervention_labels" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Labels</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <table class="table tbl-stripped" id="label-tbl">
          </table>
          <div id="add-label-form">
            <input type="hidden" name="label" value="0">
            <div class="form-group">
              <input type="text" name="label_name" class="form-control">
            </div>
            <button type="button" id="add-label" class="btn btn-primary btn-round" data-loading-text="Adding.." autocomplete="off">Add Label</button>
            <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel/Close</button>
          </div>
          <div id="edit-label-form" style="display:none">
            <input type="hidden" name="elabel" value="0">
            <div class="form-group">
              <input type="text" name="elabel_name" class="form-control">
            </div>
            <button type="button" class="btn btn-round btn-primary" id="edit-label">Update</button>
            <button type="button" class="btn btn-round btn-secondary cancel-update" id="edit-label">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End Change Student Email Modal -->
  <script>
    const FIRST_NOTICE = <?= mth_offensenotif::TYPE_FIRST_NOTICE ?>;
    const FINAL_NOTICE = <?= mth_offensenotif::TYPE_FINAL_NOTICE ?>;
    const HEADSUP_NOTICE = <?= mth_offensenotif::TYPE_HEADSUP_NOTICE ?>;
    const EX_NOTICE = <?= mth_offensenotif::TYPE_CONSECUTIVE_EX ?>;
    const PROBATION_NOTICE = <?= mth_offensenotif::TYPE_PROBATION ?>;
    const MISSING_NOTICE = <?= mth_offensenotif::TYPE_MISSING ?>;
    const EXCEED_EX = <?= mth_offensenotif::TYPE_EXCEED_EX ?>;
  </script>
  <?php
  core_loader::includeBootstrapDataTables('js');
  core_loader::addJsRef('cdndtbtn', 'https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js');
  core_loader::addJsRef('cdndtbtnhtlm5', 'https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js');
  core_loader::addJsRef('cdndtbtnflash', 'https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js');
  core_loader::addJsRef('interventions', '/_/admin/interventions/interventions.js');

  core_loader::printFooter('admin');
