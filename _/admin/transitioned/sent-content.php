<?php

$year = req_get::bool('y') ? mth_schoolYear::getByStartYear(req_get::int('y')) : mth_schoolYear::getCurrent();

function getAll($year, $report = false)
{
  $search = new mth_person_filter();
  $search->setGradeLevel([12]);
  $search->setStatus([mth_student::STATUS_TRANSITIONED]);
  $search->setStatusYear([$year->getID()]);
  $students = $search->getStudents();
  $returnArr = [];
  foreach ($students  as $key => $person) {
    $address = $person->getAddress();
    if ($report) {
      $returnArr[] = [
        $person->getPreferredFirstName(),
        $person->getPreferredLastName(),
        $person->getEmail(),
        $address->getStreet(),
        $address->getCity(),
        $address->getState(),
        $address->getZip()
      ];
    } else {
      $returnArr[] = array(
        'last' => $person->getPreferredLastName(),
        'first' => $person->getPreferredFirstName(),
        'email' => $person->getEmail(),
        'city' => $address ? $address->getCity() : '',
        'state' => $address ? $address->getState() : '',
        'zip' => $address ? $address->getZip() : '',
        'street' => $address ? $address->getStreet() : '',
        'id' => $person->getID(),
        'person_id' => $person->getPersonID(),
      );
    }
  }
  return $returnArr;
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

if (req_get::bool('getall')) {
  $returnArr = getAll($year);
  header('Content-type: application/json');
  echo json_encode($returnArr);
  exit();
}

if (req_get::bool('csv')) {
  $header = [['First Name', 'Last Name', 'Email', 'Street', 'City', 'State', 'Zip']];
  $rows = getAll($year, true);
  $data = array_merge($header, $rows);
  $file = 'Transitioned ' . $year;
  header('Content-type: text/csv');
  header('Content-Disposition: attachment; filename="' . $file . $year . '.csv"');
  foreach ($data as $row) {
    echo implode(',', array_map('prepForCSV', $row)) . "\n";
  }
  exit();
}



if (req_get::bool('transition')) {
  header('Content-type: application/json');
  $student_id = req_post::int('person_id');
  if ($student = mth_student::getByStudentID($student_id)) {
    if ($transition = mth_transitioned::getOrCreate($student, $year)) {
      $address = $student->getParent()->getAddress();
      $inputState = $address ? $address->getState() : 'UT';
      if (!$transition->sendToDropbox(null, null, $inputState)) {
        echo json_encode(['error' => 1, 'data' => ['id' => $student_id, 'msg' => 'Unable to send the withdrawal letter for ' . ($student ? $student->getName() : 'the selected student') . ' to Dropbox']]);
      } else {
        echo json_encode(['error' => 0, 'data' => ['id' => $student_id]]);
      }
    } else {
      echo json_encode(['error' => 1, 'data' => ['id' => $student_id, 'msg' => 'Student Not Found']]);
    }
  } else {
    echo json_encode(['error' => 1, 'data' => ['id' => $student_id, 'msg' => 'Student Not Found']]);
  }
  exit();
}

core_loader::includeBootstrapDataTables('css');
core_loader::addCssRef('btndtrcss', 'https://cdn.datatables.net/buttons/1.5.6/css/buttons.dataTables.min.css');
cms_page::setPageTitle('Transitions');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>

<div class="nav-tabs-horizontal nav-tabs-inverse">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link" href="/_/admin/transitioned">
        Active
      </a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link active" href="/_/admin/transitioned/sent">
        Transitioned
      </a>
    </li>
  </ul>
  <div class="tab-content p-20">
    <div class="tab-pane active" role="tabpanel">
      <select onchange="location.href='?y='+this.value" title="School Year">
        <?php while ($eachYear = mth_schoolYear::each()) : ?>
          <option value="<?= $eachYear->getStartYear() ?>" <?= $eachYear->getID() == $year->getID() ? 'selected' : '' ?>><?= $eachYear ?></option>
        <?php endwhile; ?>
      </select>
      <hr>
      <table id="transitioned-table" class="table table-striped responsive">
        <thead>
          <th><input type="checkbox" onclick="changeCheckboxStatus()" id="masterCB"></th>
          <th>Name</th>
          <th>Email</th>
          <th>Address</th>
          <th>Location</th>
          <th>Zip</th>
        </thead>
        <tbody></tbody>
      </table>

      <button class="btn btn-primary btn-round transition-btn">Resend To Dropbox</button>
    </div>
  </div>
</div>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('cdndtbtn', 'https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js');
core_loader::addJsRef('cdndtbtnhtlm5', 'https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js');
core_loader::addJsRef('cdndtbtnflash', 'https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js');
core_loader::printFooter('admin');
?>
<script>
  $DataTable = null;
  tinterval = null;
  $(function() {
    $DataTable = $('#transitioned-table').DataTable({
      'aoColumnDefs': [{
        "bSortable": false,
        "aTargets": [0]
      }],
      "bStateSave": true,
      "bPaginate": false,
      "aaSorting": [
        [1, 'asc']
      ],
      dom: 'Bfrtip',
      buttons: [{
        extend: 'csv',
        text: 'Download CSV',
        exportOptions: {
          columns: [1, 2, 3, 4, 5],
          modifier: {
            search: 'none'
          }
        }
      }]
    });
    loadSeniors();
    $('.download-csv').click(function() {
      location.href = '?csv=1';
    });

    $('.transition-btn').click(function() {
      var $this = $(this).attr('disabled', 'disabled');
      global_waiting();
      var tobetransition = $('.peopleCB:checked').map(function() {
        return $(this).val()
      }).get();
      if (tobetransition.length > 0) {
        transition(tobetransition);
      } else {
        swal('', 'Please select at least 1 student.', 'warning');
        $this.removeAttr('disabled');
        global_waiting_hide();
      }
    });
  });

  function loadSeniors() {
    global_waiting();
    $.ajax({
      url: '?getall=1<?= req_get::bool('y') ? '&y=' . req_get::int('y') : '' ?>',
      success: addData
    });
  }

  function showEditForm(type, id) {
    global_popup_iframe('mth_people_edit', '/_/admin/people/edit?' + type + '=' + id);
  }

  function addData(data) {
    $.each(data, function(key, student) {
      $DataTable.row.add([
        '<input name="people[]" value="' + student.id + '" class="peopleCB" type="checkbox">',
        ' <a onclick="showEditForm(\'student\',' + student.id + ')" class="link" title="' + student.last + ', ' + student.first + '">' + student.last + ', ' + student.first + '</a>',
        '<a href="mailto:' + student.email + '" target="_blank" title="' + student.email + '">' + student.email + '</a>',
        student.street,
        (student.city ? student.city + ', ' + student.state : ''),
        student.zip
      ], false);
    });

    global_waiting_hide();
    $DataTable.draw().responsive.recalc();
  }

  function transition(tobetransition) {
    vindex = 0;
    tinterval = setInterval(function() {
      var item = tobetransition[vindex++];
      if (typeof item != 'undefined') {
        _transition(item);
      } else {
        clearInterval(tinterval);
        vindex = 0;
        $('.transition-btn').removeAttr('disabled');
        global_waiting_hide();
        location.reload();
      }
    }, 1000);
  }

  function _transition(person_id) {
    $.ajax({
      url: '?transition=1<?= req_get::bool('y') ? '&y=' . req_get::int('y') : '' ?>',
      type: 'post',
      datType: 'JSON',
      data: {
        person_id: person_id
      },
      success: function() {

      },
      error: function() {
        console.log('error');
      }
    });
  }

  function changeCheckboxStatus() {
    $('.peopleCB').prop('checked', changeCheckboxStatus.checked = !changeCheckboxStatus.checked);
  }
</script>