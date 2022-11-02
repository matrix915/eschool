<?php
$year = mth_schoolYear::getCurrent();
if (req_get::bool('getall')) {
  $search = new mth_person_filter();
  $search->setGradeLevel([12]);
  $search->setStatus([mth_student::STATUS_ACTIVE]);
  $search->setStatusYear([$year->getID()]);
  $search->setSchoolOfEnrollment([\mth\student\SchoolOfEnrollment::Nebo, \mth\student\SchoolOfEnrollment::Tooele, \mth\student\SchoolOfEnrollment::ICSD, \mth\student\SchoolOfEnrollment::GPA]);
  $students = $search->getStudents();
  $returnArr = [];
  foreach ($students  as $key => $person) {
    $address = $person->getAddress();
    if (!$person->diplomaSeeking()) {
      $returnArr[] = array(
        'last' => $person->getPreferredLastName(),
        'first' => $person->getPreferredFirstName(),
        'email' => $person->getEmail(),
        'city' => $address ? $address->getCity() : '',
        'state' => $address ? $address->getState() : '',
        'id' => $person->getID(),
        'person_id' => $person->getPersonID(),
        'currYear' => $person->getType() == 'student' && $year ? (string) $person->getSchoolOfEnrollment(false, $year) : '',
      );
    }
  }
  header('Content-type: application/json');
  echo json_encode($returnArr);
  exit();
}
if (req_get::bool('affidavit')) {

  $student_id = req_get::int('student_id');
  if ($student = mth_student::getByStudentID($student_id)) {
    $transition = mth_transitioned::getOrCreate($student, $year);
    ob_start();
    echo mth_views_transitioned::getNewAffidavit($transition, true);
    $content = ob_get_contents();
    ob_end_clean();
    $dompdf = new Dompdf\Dompdf();
    $dompdf->load_html($content);
    $dompdf->render();
    header('Content-type: application/pdf');
    echo $dompdf->output();
  }
  exit();
}
if (req_get::bool('transition')) {
  header('Content-type: application/json');
  $student_id = req_post::int('person_id');
  if ($student = mth_student::getByStudentID($student_id)) {
    $student->setStatus(mth_student::STATUS_TRANSITIONED, $year);
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';
    if ($transition = mth_transitioned::getOrCreate($student, $year)) {
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
cms_page::setPageTitle('Transitions');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>

<div class="nav-tabs-horizontal nav-tabs-inverse">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link active" href="/_/admin/transitioned">
        Active
      </a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" href="/_/admin/transitioned/sent">
        Transitioned
      </a>
    </li>
  </ul>
  <div class="tab-content p-20">
    <div class="tab-pane active" role="tabpanel">
      <table id="transitioned-table" class="table table-striped responsive">
        <thead>
          <th><input type="checkbox" onclick="changeCheckboxStatus()" id="masterCB"></th>
          <th>Name</th>
          <th>Email</th>
          <th>Location</th>
          <th><?= mth_schoolYear::getCurrent() ?></th>
        </thead>
        <tbody></tbody>
      </table>
      <br>
      <button class="btn btn-primary btn-round transition-btn">Transition</button>
    </div>
  </div>
</div>

<?php
core_loader::includeBootstrapDataTables('js');
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
      ]
    });
    loadSeniors();

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
      url: '?transition=1',
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

  function loadSeniors() {
    global_waiting();
    $.ajax({
      url: '?getall=1',
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
        (student.city ? student.city + ', ' + student.state : ''),
        student.currYear
      ], false);
    });

    global_waiting_hide();
    $DataTable.draw().responsive.recalc();
  }

  function changeCheckboxStatus() {
    $('.peopleCB').prop('checked', changeCheckboxStatus.checked = !changeCheckboxStatus.checked);
  }
</script>