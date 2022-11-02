<?php

use mth\aws\ses;

$count = 0;
if (($year = mth_schoolYear::get2ndSemOpenReg())) {
    while ($schedule = mth_schedule::eachOfYear($year)) {
        if ($schedule->second_sem_change_available() && !$schedule->isToChange() && ($student = $schedule->student()) && ($parent = $student->getParent())) {
            if(preg_match('/(viviend)*(@codev\.com)/', $parent->getEmail())) {
                $schedule->enable2ndSemChanges();
                $count++;
            }
        }
        if ($count >= 20 && !core_config::isProduction()) {
            break;
        }
    }
}

if (($year = mth_schoolYear::get2ndSemOpenReg())
    && date('Y-m-d', $year->getSecondSemOpen()) == date('Y-m-d')
    && ($emailContent = core_setting::get('scheduleUnlockFor2ndSemEmail', 'Schedules'))
    && ($emailSubject = core_setting::get('scheduleUnlockFor2ndSemEmailSubject', 'Schedules'))
) {
    $sched_count = 0;
    $email_batch_id = uniqid();
    while ($schedule = mth_schedule::eachOfYear($year)) {
        if (
            $schedule->second_sem_change_available()
            && $schedule->isToChange()
            && ($student = $schedule->student())
            && ($parent = $student->getParent())
            && preg_match('/(viviend)*(@codev\.com)/', $parent->getEmail())
        ) {
            $emaillog = new mth_emaillogs;
            $emaillog->emailBatchId($email_batch_id);
            $emaillog->studentId($student->getID());
            $emaillog->parentId($parent->getID());
            $emaillog->schoolYearId($year->getID());
            $emaillog->status(2);
            $emaillog->type($emailContent->getName());
            $emaillog->emailAddress($parent->getEmail());
            $emaillog->create();
            try {
                $email = new core_emailservice();
                $result = $email->send(
                    [$parent->getEmail()],
                    $emailSubject->getValue(),
                    str_replace(
                        array(
                            '[PARENT]',
                            '[STUDENT]'
                        ),
                        array(
                            $parent->getPreferredFirstName(),
                            $student->getPreferredFirstName()
                        ),
                        $emailContent->getValue()
                    )
                );
            } catch (Exception $e) {
                $emaillog->errorMessage($e->getMessage());
                $emaillog->save();
            }
        }
        $sched_count++;
    }
}

$currentYear = mth_schoolYear::getCurrent();

if (req_get::bool('student_id')) {
  if (!($year = mth_schoolYear::getYearReEnrollOpen())) {
    echo json_encode(['error' => 1, 'data' => req_get::int('student_id'), 'message' => 'Intent for Re-Enrollment is not open']);
    exit;
  }


  if (!($student = mth_student::getByStudentID(req_get::int('student_id')))) {
    echo json_encode(['error' => 1, 'data' => req_get::int('student_id'), 'message' => 'Student not found']);
    exit;
  }

  if (
    !($emailContent = core_setting::get('reEnrollReminderEmailContent', 'Re-enroll'))
    || !($emailSubject = core_setting::get('reEnrollReminderEmailSubject', 'Re-enroll'))
  ) {
    echo json_encode(['error' => 1, 'data' => req_get::int('student_id'), 'message' => 'No reminder email content and subject set']);
    exit;
  }

  if (!($reEnrollNoticeSentTo = core_setting::get('reEnrollNoticeSentTo-' . $year->getID(), 'Re-enroll'))) {
    $reEnrollNoticeSentTo = core_setting::init('reEnrollNoticeSentTo-' . $year->getID(), 'Re-enroll', '');
  }

  if (!($reEnrollReminderSentTo = core_setting::get('reEnrollReminderSentTo-' . $year->getID(), 'Re-enroll'))) {
    $reEnrollReminderSentTo = core_setting::init('reEnrollReminderSentTo-' . $year->getID(), 'Re-enroll', '');
  }

  $sentToArr = explode(';', $reEnrollReminderSentTo->getValue());
  $sentNoticeArr = explode(';', $reEnrollNoticeSentTo->getValue());

  $sending_status = ['error' => 1, 'data' => $student->getID(), 'message' => 'Unable to send reminder'];

  $ses = new core_emailservice();
  $ses->enableTracking(true);

  $parent = $student->getParent();
  $to = $parent->getEmail();

  $subject =  str_replace(
    array(
      '[PARENT]',
      '[STUDENT]',
      '[SCHOOL_YEAR]',
      '[DEADLINE]'
    ),
    array(
      $student->getParent()->getPreferredFirstName(),
      $student->getPreferredFirstName(),
      $year->getName(),
      $year->getReEnrollDeadline('F j')
    ),
    $emailSubject->getValue()
  );

  $content = str_replace(
    array(
      '[PARENT]',
      '[STUDENT]',
      '[SCHOOL_YEAR]',
      '[DEADLINE]'
    ),
    array(
      $student->getParent()->getPreferredFirstName(),
      $student->getPreferredFirstName(),
      $year->getName(),
      $year->getReEnrollDeadline('F j')
    ),
    $emailContent->getValue()
  );

  if (!core_config::isProduction()) {
    $to = [$to[0]];
  }

  if ($ses->send(
    is_array($to) ? $to : [$to],
    $subject,
    $content
  )) {
    if (!in_array(req_get::int('student_id'), $sentNoticeArr)) {
      $sentNoticeArr[] = $student->getID();
      $reEnrollNoticeSentTo->update(implode(';', $sentNoticeArr));
    }
    if (!in_array(req_get::int('student_id'), $sentToArr)) {
      $sentToArr[] = $student->getID();
      $reEnrollReminderSentTo->update(implode(';', $sentToArr));
    }
    $sending_status = ['error' => 0, 'data' => $student->getID(), 'message' => 'success'];
  }
  echo json_encode($sending_status);
  exit;
}

cms_page::setPageTitle('Re-enroll Reminder');
cms_page::setPageContent('');
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader('admin');

if ($year = mth_schoolYear::getYearReEnrollOpen()) :

  $filter = new mth_person_filter();
  $filter->setStatus(array(mth_student::STATUS_ACTIVE));
  $filter->setStatusYear(array($currentYear->getID()));
  $filter->setExcludeStatusYear(array($year->getID()));
  $filter->setGradeLevel(['K', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);
  $students = $filter->getStudents();
?>

  <div class="card">
    <div class="card-header">
      Total Students: <span class="student_count_display"><?= count($students) ?></span>
    </div>
    <div class="card-block pl-0 pr-0">
      <table id="homeroom_table" class="table responsive">
        <thead>
          <tr>
            <th> <input type="checkbox" title="Un/Check All" class="check-all"></th>
            <th>Student</th>
            <th>Grade Level</th>
            <th>Parent</th>
            <th>Parent Email</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student) : ?>
            <?php if (!$student->getParent()) {
              continue;
            } ?>
            <tr id="st<?= $student->getID() ?>">
              <td><input type="checkbox" class="actionCB" value="<?= $student->getID() ?>"></td>
              <td><?= $student->getName(true) ?></td>
              <td><?= $student->getGradeLevel() ?></td>
              <td><?= $student->getParent()->getName(true) ?></td>
              <td>
                <?= $student->getParent()->getEmail() ?><br>

                <i class="fa fa-check sent" style="display:none;color:green;"></i>
                <span class="success-message" style="display:none;color:green"></span>

                <i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>
                <span class="error-message" style="display:none;color:red"></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-block">
      <button class="btn btn-round btn-primary" id="send-message" onclick="publish()">Send Reminder</button>
    </div>
  </div>
<?php else : ?>
  <div class="alert alert-warning alert-alt bg-info">
    <h4>Re-enroll is not yet open :)</h4>
  </div>
<?php endif; ?>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
  vindex = 0;
  tobesend = [];
  errors = 0;
  parentcount = 0;

  function publish() {
    tobesend = [];

    $('.actionCB:checked').each(function() {
      var data = $(this).val();
      tobesend.push(data);
    });

    if (tobesend.length == 0) {
      swal('', 'Please select atleast 1 student for the reminder', 'warning');
      return;
    }

    global_waiting();

    vinterval = setInterval(function() {
      var student_id = tobesend[vindex++];
      if (typeof student_id != 'undefined') {
        _publish(student_id);
      } else {
        global_waiting_hide();
        clearInterval(vinterval);
        

        var message = "Done sending notification to parents.";
        var type = 'success';

        if (errors > 0) {
          message += ' ' + errors + ' error(s) detected.';
        }
        
        // if(errors == parentcount){
        //     type = 'error';
        //     message = 'There seems to be an issue sending the notification.'
        // }
        swal('', message, type);

        vindex = 0;
        errors = 0;
        $('.actionCB').attr('checked',false);
      }
    }, 1000);
  }


  function _publish(item) {
    $.ajax({
      'url': '?student_id=' + item,
      'type': 'get',
      data: null,
      dataType: "json",
      success: function(response) {
        if (response.error == 0) {
          $('#st' + response.data).find('.sent').fadeIn();
          $('#st' + response.data).find('.success-message').text("Sent").fadeIn();

        } else {
          $('#st' + response.data).find('.error').fadeIn();
          $('#st' + response.data).find('.error-message').text(response.message).fadeIn();
          errors++;
        }
      },
      error: function() {
        alert('there is an error occur when sending reminder');
      }
    });
  }
  $(function() {
    $('.check-all').change(function() {
      var check = $(this).is(':checked');
      $('.actionCB').prop("checked", check);
    });

    $('#homeroom_table').DataTable({
      stateSave: false,
      "paging": false,
      "searching": false,
      "info": false,
      "columnDefs": [{
        orderable: false,
        targets: [0]
      }, ],
      "aaSorting": [
        [1, 'asc']
      ],
    });
  });
</script>