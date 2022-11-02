<?php

if (!empty($_GET['delete'])) {
  if (!empty($_GET['packets'])) {
    $failures = FALSE;
    $packet_only = isset($_GET['mode']) && $_GET['mode'] == 1;
    foreach (mth_packet::get($_GET['packets']) as $packet) {
      /* @var $packet mth_packet */
      if (!$packet->delete($packet_only)) {
        core_notify::addError('Unable to delete packet for ' . $packet->getStudent());
        $failures = true;
      }
    }
    if (!$failures) {
      core_notify::addMessage('Packets deleted');
    }
  } else {
    core_notify::addError('No packets selected!');
  }
  header('Location: /_/admin/packets');
  exit();
}

$statuses = &$_SESSION[__FILE__]['statuses'];
if (req_get::bool('statuses')) {
  $statuses = req_get::txt_array('statuses');
}

$get_age_issue = req_get::bool('age_issue');

if (empty($statuses)) {
  $statuses = array(
    // mth_packet::STATUS_MISSING,
    // mth_packet::STATUS_NOT_STARTED,
    mth_packet::STATUS_RESUBMITTED,
    // mth_packet::STATUS_STARTED,
    mth_packet::STATUS_SUBMITTED
  );
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Packets');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>
<style type="text/css">
  #app_edit_popup {
    padding: 0;
  }

  #contentTable_info {
    display: none;
  }

  .packet-status-MissingInfo,
  .PassedDue {
    color: red;
  }

  .packet-status-Submitted {
    color: green;
  }

  .packet-has-age-issue {
    color: #ff9800;
  }

  .packet-status-Resubmitted {
    color: #990099;
  }

  #mth_packet_reminder {
    height: 90%;
  }

  #mth_packet_reminder iframe {
    height: 100%;
  }

  span.hidden {
    display: none;
  }

  #packet_stats label {
    display: inline;
    margin-left: 10px;
  }

  small {
    color: #666;
  }
</style>

<div class="card">
  <div class="card-block">
    <form>
      <p id="packet_stats">
        Total Packets: <span class="tally-Total"></span>
        |
        <?php $total = 0;
        foreach (mth_packet::getStatusCounts() as $status => $count) : ?>
          <label>
            <input type="checkbox" value="<?= $status ?>" <?= in_array($status, $statuses) ? 'checked' : '' ?> name="statuses[]">
            <?= $status ?> (<?= $count ?>)
            <?php $total += $count ?>
          </label>
        <?php endforeach; ?>
        |
        <label><input type="checkbox" name="age_issue" value="1" <?= $get_age_issue ? 'CHECKED' : '' ?>>Age Issue <span class="age-ssue-count"></span></label>
        <script>
          $('.tally-Total').html(<?= $total ?>);
        </script>
        <button type="submit" class="btn btn-round btn-primary float-right">Filter</button>
      </p>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-block pl-0 pr-0">
    <table id="contentTable" class="table responsive higlight-links">
      <thead>
        <tr>
          <th><input type="checkbox" onclick="changeCheckboxStatus()" id="masterCB"></th>
          <th>Submitted</th>
          <th>Status</th>
          <th>Deadline</th>
          <th>Student</th>
          <th>Grade Level</th>
          <th>Parent</th>
          <th>City</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $age_issue_count = 0;
        while ($packet = mth_packet::each(NULL, $statuses)) :
          $student = $packet->getStudent();
          if (!$student) {
            $packet->delete();
            continue;
          }
          if (!($application = mth_application::getStudentApplication($student))) {
            continue;
          }
          $gradelevel_value = $student->getGradeLevelValue($application->getSchoolYearID());
          $has_age_issue = !$packet->isRightAge($student);

          if ($has_age_issue) {
            $age_issue_count += 1;
          }

          if (!$get_age_issue && $has_age_issue) {
            continue;
          }



          ?>
          <tr id="packet-<?= $packet->getID() ?>">
            <td><input name="packets[]" value="<?= $packet->getID() ?>" class="packetCB" type="checkbox"></td>
            <td><?= $packet->getDateLastSubmittedOrSubmitted('m/d/Y') ?></td>
            <td class="<?= $has_age_issue ? 'packet-has-age-issue' : ('packet-status-' . str_replace(' ', '', $packet->getStatus())) ?>">
              <span class="hidden"><?= $packet->isSubmitted() ? '0' : ($packet->getStatus() == mth_packet::STATUS_MISSING ? '1' : ($packet->getStatus() == mth_packet::STATUS_STARTED ? '2' : '3')) ?></span>
              <?= $packet->getStatus() ?>
              <?= $has_age_issue ? '(Age Issue)' : '' ?>
            </td>
            <td data-sort="<?= $packet->getDeadline() ?>" class="<?= $packet->isPassedDue() ? 'PassedDue' : '' ?>">
              <?= $packet->getDeadline('m/d/Y') ?>
            </td>
            <td>
              <a onclick="showEditForm(<?= $packet->getID() ?>)" title="Edit/View Packet" class="link">
                <?= $student->getName(true) ?>
              </a>
            </td>
            <td><?= $gradelevel_value ?>
              <small>(<?= $application->getSchoolYear(true) ?>) </small>
            </td>
            <td>
              <a onclick="global_popup_iframe('mth_people_edit','/_/admin/people/edit?parent=<?= $student->getParentID() ?>')">
                <?= ($parent = $student->getParent()) ? $parent->getName(true) : '' ?>
              </a>
            </td>
            <td><?= $application ? $application->getCityOfResidence() : '' ?></td>
            <td>
              <?php if ($application) : ?>
                    <a onclick="showApplicationEditForm(<?= $application->getID() ?>)" class="link">Application</a>
                    <br>
                    <small <?= $application->isReturning($student) ? '' : 'style="color:#36b37e;" ' ?>><?= $application->getDateSubmitted('m/d/Y') ?></small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer">
    <button type="button" onclick="sendReminder()" class="btn btn-info btn-round">Send Reminder</button>
    <button onclick="deletePackets()" type="button" class="btn btn-danger btn-round">Delete</button>
    <button onclick="deletePacketsOnly()" type="button" class="btn btn-warning btn-round">Remove Packet</button>
    (Auto-reminder sent <?= core_setting::get('packetDeadlineReminder', 'Packets') ?> days before deadline)
  </div>
</div>




<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
  $(function() {
    $('.age-ssue-count').html('(<?= $age_issue_count ?>)');
  });
</script>
<script type="text/javascript">
  $DataTable = null;
  var selected_packet = null;
  $(function() {
    $DataTable = $('#contentTable').dataTable({
      'aoColumnDefs': [{
        "bSortable": false,
        "aTargets": [0, 8]
      }],
      "bPaginate": false,
      "aaSorting": [
        [1, 'asc'],
        [4, 'asc']
      ]
    });
  });

  function showApplicationEditForm(applicationID) {
    global_popup_iframe('mth_application_edit', '/_/admin/applications/edit?app=' + applicationID);
  }

  function showEditForm(packetID) {
    selected_packet = packetID;
    global_popup_iframe('mth_packet_edit', '/_/admin/packets/edit?packet=' + packetID);
  }

  function changeCheckboxStatus() {
    $('.packetCB').prop('checked', changeCheckboxStatus.checked = !changeCheckboxStatus.checked);
  }

  function sendReminder() {
    global_popup_iframe('mth_packet_reminder', '/_/admin/packets/reminder?' + $('.packetCB:checked').serialize());
  }

  // text: "This will also delete students if they were not previously active, and Parents also if they have no other students. This action cannot be undone.",
  function deletePackets() {
    swal({
        title: "",
        text: "This will also delete students if they were not previously active. This action cannot be undone.",
        type: "warning",
        showCancelButton: !0,
        confirmButtonClass: "btn-warning",
        confirmButtonText: "Delete Packets & Students",
        cancelButtonText: "Cancel",
        closeOnConfirm: !1,
        closeOnCancel: true
      },
      function() {
        window.location = '?delete=1&' + $('.packetCB:checked').serialize();
      });
  }

  function deletePacketsOnly() {
    window.location = '?mode=1&delete=1&' + $('.packetCB:checked').serialize();
  }
</script>