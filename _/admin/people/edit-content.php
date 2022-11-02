<?php

use mth\student\SchoolOfEnrollment;
use mth\yoda\courses;
use mth\yoda\memcourse;

$isStudent = false;
$isParent = false;
$type = null;
if (!empty($_GET['parent'])) {
  $person = mth_parent::getByParentID($_GET['parent']);
  $isParent = true;
  $type = 'parent';
} elseif (!empty($_GET['student'])) {
  $person = mth_student::getByStudentID($_GET['student']);
  $isStudent = true;
  $type = 'student';
} elseif (!empty($_GET['new'])) {
  switch ($_GET['new']) {
    case 'parent':
      $person = mth_parent::create();
      $type = 'parent';
      $isParent = true;
      break;
    case 'student':
      $person = mth_student::create();
      $type = 'student';
      $isStudent = true;
  }
}

if (!$person) {
  exit('Unable to edit person.<script>setTimeout("top.global_popup_iframe_close(\'mth_people_edit\')",1000)</script>');
}

if (!empty($_GET['form'])) {
  if (!core_loader::formSubmitable('edit-person-' . $_GET['form'])) {
    exit();
  }
  $person->setName($_POST['first_name'], $_POST['last_name'], $_POST['middle_name'], $_POST['preferred_first_name'], $_POST['preferred_last_name']);
  $person->setGender($_POST['gender']);
  $person->setDateOfBirth(strtotime($_POST['date_of_birth']));
  $person->setEmail($_POST['email']);

  $person->setSchoolDistrict($_POST['school_district']);

  if ($person->errorUpdatingCanvasLoginEmail() && core_setting::get('AccountAuthorizationConfigID', 'Canvas')) {
    core_notify::addError('Unable to update the canvas login email address. ' . $person->getPreferredFirstName() . ' will not be able to login to canvas until it is updated manually.');
  }

  if ($isStudent) {
    foreach ($_POST['grade_level'] as $yearID => $grade_level) {
      if (!($year = mth_schoolYear::getByID($yearID))) {
        continue;
      }
      $person->setGradeLevel($grade_level, $year);
    }
    foreach ($_POST['status'] as $yearID => $status) {
      if (!($year = mth_schoolYear::getByID($yearID))) {
        continue;
      }
      if ($status == mth_student::STATUS_WITHDRAW) {
        if ($status != $person->getStatus($year) && $withdrawal = mth_withdrawal::getByStudent($person->getID(), $year->getID())) {
          $withdrawal->reset();
          mth_withdrawal::setActiveValue($person->getID(), $year->getID(), 0);
        }
      } elseif ($year == mth_schoolYear::getNext() && $person->getStatus($year) === mth_student::STATUS_WITHDRAW) {
         mth_withdrawal::delete($_GET['student'], $year->getID());
         $packet = mth_packet::getStudentPacket($person);//if edited and the status is set not to withdrawal
         $packet->restore(); //restore status of the enrollment packet. if withdrawal is soft widthdrawal in the database
      }
      $person->setStatus($status, $year);
    }
    foreach ($_POST['school_of_enrollment'] as $yearID => $soe_id) {
      if (
        !($year = mth_schoolYear::getByID($yearID))
        || !($SOE = SchoolOfEnrollment::get($soe_id))
      ) {
        continue;
      } 
      $transferred = 0;
      if($person->getSOEname($year)) {
          $transferred = 1;
      }
      $person->setSchoolOfEnrollment($SOE, $year, $transferred);
    }
    $person->set_spacial_ed($_POST['special_ed']);
    $person->diplomaSeeking($_POST['diploma_seeking']);

    if (
      req_post::is_set('school_district')
      && ($packet = mth_packet::getStudentPacket($person))
    ) {
      $packet->setSchoolDistrict(req_post::txt('school_district'));
      if ($person->isPendingOrActive() && $packet->getStatus() == mth_packet::STATUS_NOT_STARTED) {
        $packet->setStatus(mth_packet::STATUS_ACCEPTED);
      }
      $packet->save();
    }
  }

  if (isset($_POST['familynote'])) {
    if ($person->note()) {
      $person->note()->setNote($_POST['familynote']);
    } else {
      mth_familynote::create($person, $_POST['familynote']);
    }
  }

  foreach ($_POST['phone'] as $phoneForm) {
    if (!empty($phoneForm['id'])) {
      $phone = mth_phone::getPhone($phoneForm['id']);
    } elseif (!empty($phoneForm['number'])) {
      $phone = mth_phone::create($person);
    } else {
      continue;
    }
    $phone->saveForm($phoneForm);
  }

  mth_address::saveAddressForm($_POST['address']);

  core_notify::addMessage('Changes saved!');
  header('location:/_/admin/people/edit?' . $type . '=' . $person->getID());
  exit();
}

if (req_get::bool('deleteStudent')) {
  if (!$isStudent) {
    core_loader::reloadParent();
  }
  if ($person->delete(true)) {
    core_notify::addMessage('Student deleted!');
  } else {
    core_notify::addMessage('Unable to delete student!');
  }
  core_loader::reloadParent();
}

if (req_get::bool('restore')) {
  if (!$isStudent) {
    core_loader::reloadParent();
  }
  if ($person->restore()) {
    $year = mth_schoolYear::getCurrent();
    mth_withdrawal::setActiveValue($_GET['student'], $year->getID(), 0, true);
    core_notify::addMessage('Student Reinstated!');
  } else {
    core_notify::addError('Unable to restore student!');
  }
  header('location:/_/admin/people/edit?' . $type . '=' . $person->getID());
  exit();
}

if (!empty($_GET['deleteAddress'])) {
  if (($address = mth_address::getAddress($_GET['deleteAddress']))) {
    if ($address->delete()) {
      core_notify::addMessage('Address deleted');
    } else {
      core_notify::addError('Unable to delete address');
    }
  } else {
    core_notify::addError('Unable to find address');
  }
  header('location:/_/admin/people/edit?' . $type . '=' . $person->getID());
  exit();
}

if (!empty($_GET['deletePhone'])) {
  if (($phone = mth_phone::getPhone($_GET['deletePhone']))) {
    if ($phone->delete()) {
      core_notify::addMessage('Phone deleted');
    } else {
      core_notify::addError('Unable to delete phone');
    }
  } else {
    core_notify::addError('Unable to find phone');
  }
  header('location:/_/admin/people/edit?' . $type . '=' . $person->getID());
  exit();
}

if (req_get::bool('removeOptOut')) {
  if (
    $isStudent
    && ($optout = mth_testOptOut::getByStudent($person, mth_schoolYear::getCurrent()))
    && $optout->delete($person->getID())
  ) {
    core_notify::addMessage('Opt-out removed');
  }
  core_loader::redirect('?' . $type . '=' . $person->getID());
}

core_loader::includejQueryValidate();

// core_loader::addCssRef('people-edit-content-style', '/_/admin/people/people-edit-content.css');

cms_page::setPageTitle('Edit Person');
core_loader::isPopUp();
core_loader::printHeader();

?>
  <style>
    #editPersonForm table.formatted {
      width: 100%;
    }

    .mth_address label {
      color: #ccc;
    }

    .graduated {
      display: none;
    }

    a.graduated-list {
      background-color: #e2e0e0 !important;
    }
  </style>
  <script>
    function showEditForm(type, id) {
      top.global_popup_iframe('mth_people_edit', '/_/admin/people/edit?' + type + '=' + id);
    }

    function showNotes(selected_intervention, selected_student) {
      top.global_popup_iframe('notesPopup', '/_/admin/people/notes?intervention=' + selected_intervention + '&student=' + selected_student);
    }

    function deleteAddress(addressID) {
      deleteAddress.addressID = addressID;
      global_confirm('Are you sure you want to delete this address? This action cannot be undone!',
        function() {
          location = '?<?= $type ?>=<?= $person->getID(); ?>&deleteAddress=' + deleteAddress.addressID;
        });
    }

    function deletePhone(phoneID) {
      deletePhone.phoneID = phoneID;
      global_confirm('Are you sure you want to delete this phone? This action cannot be undone!',
        function() {
          location = '?<?= $type ?>=<?= $person->getID(); ?>&deletePhone=' + deletePhone.phoneID;
        });

    }

    function editSchedule(schedule_id) {
      top.global_popup_iframe('mth_schedule-edit-' + schedule_id, '/_/admin/schedules/schedule?schedule=' + schedule_id);
    }
  </script>
  <div class="pop-out-header p-20">
    <button type="button" class="btn btn-secondary btn-round float-right" onclick="if(top.updateTable){ top.updateTable(); } top.global_popup_iframe_close('mth_people_edit')">Close</button>
    <h2 class="mt-0"><?= $person ?></h2>
  </div>
  <div class="pop-out-body pt-80">

    <?php if ($isParent) : ?>
      <div class="row parent-links">
        <div class="col-sm-6">
          <div class="list-group list-group-bordered">
            <h3 class="list-group-item active mt-0">Children</h3>
            <?php if (!$person->isObserver()) : ?>
              <a class="list-group-item list-group-item-primary blue-600" onclick="top.global_popup_iframe('observer_popout', '/_/admin/people/observer?parent=<?= $person->getID() ?>')"><i class="fa fa-user"></i>&nbsp;<b>Add Observer</b></a>
            <?php endif; ?>
            <div class="list-group-item">
              <div class="checkbox-custom checkbox-primary">
                <input type="checkbox" value="1" name="showgraduates" class="showgraduates">
                <label>Show Graduated/Transitioned</label>
              </div>
            </div>
            <?php foreach ($person->getAllStudents() as $student) : ?>
              <a class="list-group-item <?= $student->hadGraduated() ? 'graduated graduated-list' : '' ?>" onclick="showEditForm('student',<?= $student->getID() ?>)"><?= $student ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="list-group list-group-bordered">

            <h3 class="list-group-item active mt-0">Reimbursements</h3>
            <?php $has_reimbursements = false; ?>
            <?php foreach (mth_schoolYear::getSchoolYears(NULL, time()) as $each_year) { ?>
              <?php if (($reimbursement = mth_reimbursement::each($person, NULL, $each_year))) : ?>
                <?php $has_reimbursements = true; ?>
                <a class="list-group-item" onclick="top.global_popup_iframe('mth_reimbursement-show','/_/admin/reimbursements/family?parent=<?= $person->getID() ?>&year=<?= $each_year->getStartYear() ?>')"><?= $each_year ?></a>
              <?php endif; ?>
            <?php } ?>
            <?php if (!$has_reimbursements) : ?>
              <a class="list-group-item" onclick="top.global_popup_iframe('mth_reimbursement-show','/_/admin/reimbursements/family?parent=<?= $person->getID() ?>&year=<?= mth_schoolYear::getCurrent()->getStartYear() ?>')"><?= mth_schoolYear::getCurrent() ?></a>
            <?php endif; ?>

          </div>

          <div class="list-group list-group-bordered">
            <h3 class="list-group-item active mt-0">HR Resource Request</h3>
            <?php foreach (mth_schoolYear::getSchoolYears() as $each_year) { ?>
              <?php if (($resource = mth_resource_request::get($person, $each_year))) : ?>
                <a class="list-group-item" onclick="top.global_popup_iframe('mth_resource-show','/_/admin/resources/family?parent=<?= $person->getID() ?>&year=<?= $each_year->getStartYear() ?>')"><?= $each_year ?></a>
              <?php endif; ?>
            <?php } ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($isStudent) : ?>
      <div class="row person-links">
        <div class="col-sm-6">

          <?php if ($isStudent && ($parent = $person->getParent())) : ?>
            <div class="list-group list-group-bordered">
              <h3 class="list-group-item active mt-0">Parent/Guardian</h3>
              <a class="list-group-item" onclick="showEditForm('parent',<?= $parent->getID() ?>)"><?= $parent ?></a>
              <?php if ($observer = $person->getObserver()) : ?>
                <a class="list-group-item" onclick="showEditForm('parent',<?= $observer->getID() ?>)"><?= $observer ?> (OBSERVER)</a>
              <?php endif; ?>
            </div>

            <div class="list-group list-group-bordered">
              <h3 class="list-group-item active mt-0">Siblings</h3>
              <div class="list-group-item">
                <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" value="1" name="showgraduates" class="showgraduates">
                  <label>Show Graduated/Transitioned</label>
                </div>
              </div>
              <?php foreach ($parent->getAllStudents() as $student) : if ($student->getID() == $person->getID()) {
                      continue;
                    } ?>
                <a class="list-group-item  <?= $student->hadGraduated() ? 'graduated graduated-list' : '' ?>" onclick="showEditForm('student',<?= $student->getID() ?>)"><?= $student ?></a>
              <?php endforeach; ?>
            </div>
            <div class="list-group list-group-bordered">
              <h3 class="list-group-item active mt-0">Notes and Interventions</h3>
              <?php
                  while ($intervention = mth_intervention::getAllByStudent($person)) :
                    ?>
                <a class="list-group-item" onclick="showNotes(<?= $intervention->getID() ?>,<?= $person->getID() ?>)"><?= $intervention->getSchoolYear() ?></a>
              <?php
                  endwhile;
                  ?>

            </div>
            <?php if ($hr = courses::getStudentHomeroom($person->getID(), mth_schoolYear::getCurrent())) : ?>
              <div class="list-group list-group-bordered">
                <h3 class="list-group-item active mt-0">Learning Logs</h3>
                <a class="list-group-item" onclick="global_popup_iframe('mth_student_learning_logs', '/_/teacher/homeroom?st=<?= $person->getID() ?>&hr=<?= $hr->getCourseId() ?>')">
                  <?= $hr->getName() ?> <?= $hr->getTeacher() ? "({$hr->getTeacher()->getName()})" : "" ?>
                </a>
              </div>
            <?php elseif ($mhr = memcourse::getStudentHomeroom($person->getID(), mth_schoolYear::getCurrent())) : ?>
              <div class="list-group list-group-bordered">
                <h3 class="list-group-item active mt-0">Learning Logs <small>(Archives)</small></h3>
                <a class="list-group-item" onclick="global_popup_iframe('mth_student_learning_logs', '/_/user/learning-logs?student=<?= $person->getID() ?>&archive=<?= $mhr->getCourseId() ?>')">
                  <?= $mhr->getName() ?> <?= $mhr->getTeacher() ? "({$mhr->getTeacher()->getName()})" : "" ?>
                </a>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <div class="col-sm-6">
          <?php if (($packet = mth_packet::getStudentPacket($person))) : ?>
            <div class="list-group list-group-bordered">
              <h3 class="list-group-item active mt-0">Packet</h3>
              <a class="list-group-item" onclick="top.global_popup_iframe('mth_packet_edit','/_/admin/packets/edit?packet=<?= $packet->getID() ?>')">
                <?= $packet ?>
              </a>
            </div>
          <?php endif; ?>
          <?php if (($scheduleIDs = mth_schedule::getStudentScheduleIDs($person, false, false, 'DESC'))) : ?>
            <div class="list-group list-group-bordered">
              <h3 class="list-group-item active mt-0">Schedules</h3>
              <?php foreach ($scheduleIDs as $yearID => $schedule_id) : ?>
                <a class="list-group-item" onclick="editSchedule(<?= $schedule_id ?>)" style="display: block;">
                  <?php
                        $_sched = mth_schedule::getByID($schedule_id);
                        ?>
                  <?= mth_schoolYear::getByID($yearID) ?> (<?= $_sched ? ($_sched->getLastModified('M. j, Y/') . $_sched->status()) : '' ?>) <?= $_sched ? $_sched->displayDateSubmitted($_sched->status(), 'M. j, Y') : '' ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($isStudent) : ?>

            <?php if (($application = mth_application::getStudentApplication($person))) : ?>
              <div class="list-group list-group-bordered">
                <h3 class="list-group-item active mt-0">Application</h3>
                <a class="list-group-item" onclick="top.global_popup_iframe('people-edit-application','/_/admin/applications/edit?app=<?= $application->getID() ?>')">
                  <?= $application ?>
                </a>
              </div>
            <?php endif; ?>

            <div class="list-group list-group-bordered">
              <h3 class="list-group-item active mt-0">HR Resource Request</h3>
              <?php foreach (mth_schoolYear::getSchoolYears() as $each_year) { ?>
                <?php if (($st_resource = mth_resource_request::getOneByStudent($person, $each_year))) : ?>
                  <a class="list-group-item" onclick="top.global_popup_iframe('reportPopup','/_/admin/resources/student?student=<?= $person->getID() ?>&year=<?= $each_year->getStartYear() ?>')"><?= $each_year ?></a>
                <?php endif; ?>
              <?php } ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <form id="editPersonForm" action="?<?= $type ?>=<?= $person->getID(); ?>&form=<?= time() ?>" method="post">
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-block">
              <table class="formatted">
                <tr>
                  <th>Preferred First</th>
                  <td><input type="text" name="preferred_first_name" class="form-control" value="<?= $person->getPreferredFirstName() ?>" class="borderLess"></td>
                </tr>
                <tr>
                  <th>Preferred Last</th>
                  <td><input type="text" name="preferred_last_name" class="form-control" value="<?= $person->getPreferredLastName() ?>" class="borderLess"></td>
                </tr>
                <tr>
                  <th>Legal First Name</th>
                  <td><input type="text" name="first_name" class="form-control" value="<?= $person->getFirstName() ?>" class="borderLess">
                  </td>
                </tr>
                <tr>
                  <th>Legal Middle Name</th>
                  <td><input type="text" name="middle_name" class="form-control" value="<?= $person->getMiddleName() ?>" class="borderLess"></td>
                </tr>
                <tr>
                  <th>Legal Last Name</th>
                  <td><input type="text" name="last_name" class="form-control" value="<?= $person->getLastName() ?>" class="borderLess">
                  </td>
                </tr>
                <tr>
                  <th>Gender</th>
                  <td>
                    <select name="gender" class="borderLess form-control">
                      <option></option>
                      <option <?= $person->getGender() === mth_person::GEN_FEMALE ? 'selected' : '' ?>><?= mth_person::GEN_FEMALE ?></option>
                      <option <?= $person->getGender() === mth_person::GEN_MALE ? 'selected' : '' ?>><?= mth_person::GEN_MALE ?></option>
                    </select>
                  </td>
                </tr>
                <tr>
                  <th>Date of Birth</th>
                  <td style="line-height: 35px;">
                    <div class="input-group">
                      <input type="text" name="date_of_birth" style="max-width: 50%" value="<?= $person->getDateOfBirth() ? date('M j, Y', $person->getDateOfBirth()) : '' ?>" class="borderLess form-control">
                      <span class="input-group-addon">
                        <?= $person->getDateOfBirth() ? '(' . $person->getAge() . ')' : '' ?>
                      </span>
                    </div>
                  </td>
                </tr>
                <?php if ($isStudent) : ?>
                  <?php if (($prevYear = mth_schoolYear::getPrevious())) : ?>
                    <tr>
                      <th><?= $prevYear ?> Grade Level</th>
                      <td>
                        <select name="grade_level[<?= $prevYear->getID() ?>]" class="borderLess form-control">
                          <option></option>
                          <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                            <option value="<?= $grade_level ?>" <?= $person->getGradeLevel(false, false, $prevYear->getID()) == $grade_level ? 'selected' : '' ?>>
                              <?= $grade_desc ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php if (($currentYear = mth_schoolYear::getCurrent())) : ?>
                    <tr>
                      <th><?= $currentYear ?> Grade Level</th>
                      <td>
                        <select name="grade_level[<?= $currentYear->getID() ?>]" class="borderLess form-control">
                          <option></option>
                          <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                            <option value="<?= $grade_level ?>" <?= $person->getGradeLevel(false, false, $currentYear->getID()) == $grade_level ? 'selected' : '' ?>>
                              <?= $grade_desc ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php if (($nextYear = mth_schoolYear::getNext()) && $currentYear != $nextYear) : ?>
                    <tr>
                      <th><?= $nextYear ?> Grade Level</th>
                      <td>
                        <select name="grade_level[<?= $nextYear->getID() ?>]" class="borderLess form-control">
                          <option></option>
                          <?php
                          $person_next_grade = $person->getGradeLevel(false, false, $currentYear->getID()) == 'OR-K' ? 1 : $person->getGradeLevel(false, false, $nextYear->getID());
                          foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                            <option value="<?= $grade_level ?>" <?= $person_next_grade == $grade_level ? 'selected' : '' ?>>
                              <?= $grade_desc ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php
                    $was_withdrawn = false;
                    if (($previousYear = mth_schoolYear::getPrevious())) :
                      $was_withdrawn = $person->isStatus(mth_student::STATUS_WITHDRAW, $previousYear);
                      ?>
                    <tr>
                      <th><?= $previousYear ?> Status</th>
                      <td style="line-height: 35px;">
                        <select name="status[<?= $previousYear->getID() ?>]" class="borderLess mth_student-status-select form-control">
                          <option></option>
                          <?php foreach (mth_student::getAvailableStatuses() as $status => $statusDesc) : ?>
                            <option value="<?= $status ?>" <?= $person->isStatus($status, $previousYear) ? 'selected' : '' ?>><?= $statusDesc ?></option>
                          <?php endforeach; ?>
                        </select>
                        <?= $person->getStatusDate($previousYear, '(m/d/Y)') ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php if (($currentYear = mth_schoolYear::getCurrent())) : ?>
                    <tr>
                      <th><?= $currentYear ?> Status</th>
                      <td style="line-height: 35px;">
                        <select name="status[<?= $currentYear->getID() ?>]" class="borderLess mth_student-status-select form-control">
                        <?php if ($person->isStatus(mth_student::STATUS_WITHDRAW, $currentYear)) : ?>
                            <option value="<?= mth_student::STATUS_WITHDRAW ?>"><?= mth_student::statusLabel(mth_student::STATUS_WITHDRAW) ?></option>
                          <?php else : ?>  
                            <option></option>
                            <?php foreach (mth_student::getAvailableStatuses() as $status => $statusDesc) : ?>
                              <option value="<?= $status ?>" <?= $person->isStatus($status, $currentYear) ? 'selected' : '' ?>><?= $statusDesc . ($was_withdrawn && (in_array($status, [mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING])) ? ' (Re-Apply)' : '') ?></option>
                            <?php endforeach; ?>
                            <?php endif; ?> 
                        </select>
                        <?= $person->getStatusDate($currentYear, '(m/d/Y)') ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php if (($nextYear = mth_schoolYear::getNext()) && $currentYear != $nextYear) : ?>
                    <tr>
                      <th><?= $nextYear ?> Status</th>
                      <td style="line-height: 35px;">
                        <select name="status[<?= $nextYear->getID() ?>]" class="borderLess form-control">
                          <option></option>
                          <?php foreach (mth_student::getAvailableStatuses() as $status => $statusDesc) : ?>
                            <option value="<?= $status ?>" <?= $person->isStatus($status, $nextYear) ? 'selected' : '' ?>><?= $statusDesc ?></option>
                          <?php endforeach; ?>
                        </select>
                        <?= $person->getStatusDate($nextYear, '(m/d/Y)') ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php if (($previousYear = mth_schoolYear::getPrevious())) : ?>
                    <tr>
                      <th><?= $previousYear ?> School of Enrollment</th>
                      <td>
                        <select name="school_of_enrollment[<?= $previousYear->getID() ?>]" class="borderLess form-control">
                          <option></option>
                          <?php foreach (SchoolOfEnrollment::getAll() as $num => $school) : ?>
                            <option value="<?= $num ?>" <?= $person->getSchoolOfEnrollment(true, $previousYear) == $num ? 'selected' : '' ?>><?= $school ?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <?php if (($currentYear = mth_schoolYear::getCurrent())) : ?>
                    <tr>
                      <th><?= $currentYear ?> School of Enrollment</th>
                      <td>
                        <select name="school_of_enrollment[<?= $currentYear->getID() ?>]" class="borderLess form-control">
                          <option></option>
                          <?php foreach (SchoolOfEnrollment::getActive() as $num => $school) : ?>
                            <option value="<?= $num ?>" <?= $person->getSchoolOfEnrollment(true, $currentYear) == $num ? 'selected' : '' ?>><?= $school ?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <th>Special Ed</th>
                    <td>
                      <select name="special_ed" class="borderLess form-control">
                        <?php foreach (mth_student::getAvailableSpEd() as $sped => $label) : ?>
                          <option <?= $person->specialEd() == $sped ? 'selected' : '' ?> value="<?= $sped ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <th>Diploma-seeking</th>
                    <td>
                      <select name="diploma_seeking" class="borderLess form-control">
                        <?php 
                          if ( core_setting::get('AllowDiplomaSeekingQuestion', 'Diploma_seeking_question')->getValue() && $person->getGradeLevel(false, false, $currentYear->getID()) >= 9 ) :
                        ?>
                        <option <?= $person->diplomaSeeking() === NULL ? 'selected' : '' ?> value="0">Undecided</option>
                        <?php 
                          endif;
                        ?>
                        <option <?= $person->diplomaSeeking() === '0' ? 'selected' : '' ?> value="0">No</option>
                        <option <?= $person->diplomaSeeking() === '1' ? 'selected' : '' ?> value="1">Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <th>Opt-out?</th>
                    <td style="padding-left: 17px;">
                      <?= ((mth_testOptOut::getByStudent($person, mth_schoolYear::getCurrent()))
                          ? 'Yes | <a href="?' . $type . '=' . $person->getID() . '&removeOptOut=1">Remove</a>' : 'No'); ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </table>
            </div>
            <div class="card-footer">
              <?php if ($isParent) : ?>
                <div class="form-group">
                  <label>Note</label>
                  <textarea class="form-control" rows="6" name="familynote"><?= $person->note() ? $person->note()->getNote() : '' ?></textarea>
                </div>
                <?php if (core_user::canMasquerade()) : ?>
                  <a href="#" class="btn btn-pink masquerade" data-e="<?= $person->getUserID() ?>">Masquerade</a>
                <?php endif; ?>
              <?php elseif ($isStudent && $person->isStatus(mth_student::STATUS_WITHDRAW, $currentYear) && ($archive = mth_archive::get($person, mth_schoolYear::getCurrent(), true))) : ?>
                <a href="?student=<?= $person->getID() ?>&restore=1" class="btn btn-pink" onclick="return confirm(\'Are you sure you want to reinstate this student.\')">Reinstate </a>
              <?php endif; ?>
              <button type="submit" class="btn btn-primary btn-round float-right">Save</button>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-block">
              <table class="formatted">
                <tr>
                  <th>Email</th>
                  <td><input type="email" value="<?= $person->getEmail() ?>" name="email" class="borderLess form-control"></td>
                </tr>
                <tr>
                  <th>Phone</th>
                  <td class="phone-td">
                    <?php
                    $lastNum = 0;
                    include $_SERVER['DOCUMENT_ROOT'] . '/_/mth_forms/phone.php';
                    foreach ($person->getPhoneNumbers() as $num => $phone) :
                      ?>
                      <div class="sub-item"> <?= $phone ?>
                        <a onclick="$(this).parent().hide(); $('#mth_phone-phone-<?= $num ?>').fadeIn();">edit</a>
                        <a onclick="deletePhone(<?= $phone->getID() ?>)">delete</a>
                      </div>
                    <?php
                      printPhoneFields('phone[' . $num . ']', false, $phone);
                      $lastNum = $num;
                    endforeach;
                    $num = $lastNum + 1;
                    echo '<a onclick="$(this).hide(); $(\'#mth_phone-phone-' . $num . '\').fadeIn();" class="new-item-link btn btn-pink btn-round">new</a>';
                    printPhoneFields('phone[' . $num . ']');
                    ?>
                    <script>
                      $('.mth_phone').hide();
                    </script>
                  </td>
                </tr>
                <?php if ($isStudent && ($parent = $person->getParent())) : ?>
                  <tr>
                    <th><?= $parent->getPreferredFirstName() ?>'s Phone
                      <small>(parent)</small>
                    </th>
                    <td>
                      <?php
                        foreach ($parent->getPhoneNumbers() as $num => $phone) {
                          echo '<div class="sub-item">' . $phone . '</div>';
                        }
                        ?>
                    </td>
                  </tr>
                <?php endif; ?>
                <?php if ($isStudent || ($isParent && !$person->isObserver())) : ?>
                  <tr>
                    <th>
                      Address
                      <small>(Affects <?= $isParent ? 'children' : 'parent' ?>)</small>
                    </th>
                    <td>
                      <?php
                        include $_SERVER['DOCUMENT_ROOT'] . '/_/mth_forms/address.php';
                        printAddressFields('address', false, $isParent ? $person : $person->getParent(), $isParent ? null : $person);
                        ?>
                      <script>
                        $('.mth_address input').addClass('borderLess');
                      </script>
                    </td>
                  </tr>
                <?php endif; ?> 
                
                <!-- <tr>
                    <th>Packet</th>
                    <td>
                      
                    </td>
                  </tr> -->               

              </table>
            </div>
            <div class="card-footer text-right">
              <button type="submit" class="btn btn-primary btn-round">Save</button>
              <?php if ($isStudent && $person->canBeDeleted()) : ?>
                <script>
                  // text: "If the parent has no other student the parent will also be deleted. Also, any schedules, packet, or application of this student will all be deleted. This action cannot be undone.",
                  function deleteStudent(student_id) {
                    swal({
                        title: "Are you sure you want to delete this student?",
                        text: "This action cannot be undone.",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonClass: "btn-warning",
                        confirmButtonText: "Yes",
                        cancelButtonText: "Cancel",
                        closeOnConfirm: !1,
                        closeOnCancel: true
                      },
                      function() {
                        location.href = '?student=' + student_id + '&deleteStudent=1'
                      });
                  }
                </script>
                <button type="button" class="btn btn-danger btn-round" onclick="deleteStudent(<?= $person->getID() ?>)">Delete Student</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </form>



  </div>
  <div style="color:#ddd; clear: both">
    <?= ucfirst($person->getType()) ?> ID: <?= $person->getID() ?>
    &nbsp; - &nbsp; Person ID: <?= $person->getPersonID() ?>
    <?php if ($isStudent) : ?>
      &nbsp; - &nbsp; Parent ID: <?= $person->getParentID() ?>
      &nbsp; - &nbsp; Parent Person ID: <?= $person->getParent()->getPersonID() ?>
    <?php endif; ?>
  </div>
  <script type="text/javascript">
    $('.mth_student-status-select').change(function() {
      if (this.value === '<?= mth_student::STATUS_WITHDRAW ?>') {
        swal("Withdrawn", "Marking this student as Withdrawn will delete their schedule and packet. Change it back before saving to avoid deletetion.", "warning");
      }
    });
    $('.masquerade').click(function() {
      var e = $(this).data('e');
      top.location.href = '/_/admin/users?e=' + e;
      return false;
    });
  </script>
  <?php
  core_loader::printFooter();
  ?>
  <script>
    $(function() {
      $('.showgraduates').change(function() {
        if ($(this).is(':checked')) {
          $('.graduated-list').removeClass('graduated');
        } else {
          $('.graduated-list').addClass('graduated');
        }
      });
    });

    $(document).ready(function() {
      addEventListener('submit', (event) => {
        let countyAddress = $('select[id="county_state_dynamic_change"]').val();
        let schoolDistrict = $('select[id="school_district_state_dynamic_change"]').val();
        // console.log("here.", countyAddress, schoolDistrict);

        if(countyAddress == ""){
          $('#edit_people_address_county_require_label').addClass('edit_people_address_require_label_here');
          $('#county_state_dynamic_change').addClass('edit_people_address_county_blank_dropdown_border');
          event.preventDefault();
        }else{
          $('#edit_people_address_county_require_label').removeClass('edit_people_address_require_label_here');
          $('#county_state_dynamic_change').removeClass('edit_people_address_county_blank_dropdown_border');
        }
        if(schoolDistrict == ""){
          $('#edit_people_address_school_district_require_label').addClass('edit_people_address_require_label_here');
          $('#school_district_state_dynamic_change').addClass('edit_people_address_county_blank_dropdown_border');
          event.preventDefault();
        }else{
          $('#edit_people_address_school_district_require_label').removeClass('edit_people_address_require_label_here');
          $('#school_district_state_dynamic_change').removeClass('edit_people_address_county_blank_dropdown_border');
        }

      });
    });
  </script>

  <style>
    .edit_people_address_require_label_here::after {
      content: "This field is required.";
      color: #cccccc;
    }

    .edit_people_address_county_blank_dropdown_border{
      border: 1px solid #2196f3;
    }
  </style>