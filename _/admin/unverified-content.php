<?php

use function GuzzleHttp\json_encode;

if (req_get::bool('verify')) {
  $ses = new  mth\aws\ses();
  $type = req_post::is_set('verification_type') ? req_post::int('verification_type') : mth_emailverifier::TYPE_AFTERAPPLICATION;
  $email = trim(req_post::txt('email'));
  $user_id = trim(req_post::txt('user_id'));
  $activation_code = $ses->generateActivationCode();
  if ($ses->sendActivationEmail($email, $activation_code)) {
    mth_emailverifier::insert($email, $user_id, $type, $activation_code);
    echo json_encode(['error' => 0, 'data' => ['id' => $user_id]]);
  } else {
    echo json_encode(['error' => 1, 'data' => ['msg' => 'Unable to verify ' . $email, 'id' => $user_id]]);
  }
  exit();
}

if (req_get::bool('unverified_application')) {
  $unverified = [];
  $pending = [];
  $new_applicant = [];
  $unverfied_ids = [];
  $new_applicat_ids = [];
  $pending_ids = [];

  $current_year = mth_schoolYear::getCurrent()->getID();
  $next_year = mth_schoolYear::getNext()->getID();

  $applications = (new mth_application_query())
    ->setStatus([mth_application::STATUS_SUBMITTED, mth_application::STATUS_ACCEPTED])
    ->setSchoolYear([$current_year, $next_year])
    //->setHidden(false)
    ->getAll();

  // Prepare some values to cut down on SQL queries
  $userIdsToCheckEmailVerification = [];
  foreach ($applications as $application) {
    if ($st = $application->getStudent()) {
      $parent = $st->getParent();
      if (!$parent) {
        continue;
      }

      $userIdsToCheckEmailVerification[] = $parent->getUserID();
    }
  }
  $emailVerifications = mth_emailverifier::getBatchByUserId($userIdsToCheckEmailVerification);
  $emailVerificationsByUserId = [];
  foreach ($emailVerifications as $emailVerification) {
    $emailVerificationsByUserId[$emailVerification->getUserId()] = $emailVerification;
  }
  unset($emailVerifications); // free up memory

  foreach ($applications as $application) {
    if ($st = $application->getStudent()) {
      $parent = $st->getParent();
      if (!$parent) {
        continue;
      }
      //      $_verified = mth_emailverifier::getByUserId($parent->getUserID()); // TODO: LOL
      $_verified = isset($emailVerificationsByUserId[$parent->getUserID()]) ? $emailVerificationsByUserId[$parent->getUserID()] : '';
      if (!$application->getStudent()->statusYearCount() && $_verified && !$_verified->isVerified() && !in_array($parent->getUserID(), $new_applicat_ids)) {
        $new_applicat_ids[] = $parent->getUserID();
        $new_applicant[] = [
          'user_id' => $parent->getUserID(),
          'email' => preg_replace('#<script(.*?)>(.*?)</script>#is', '', $parent->getEmail()),
          'name' => $parent->getName(),
          'isv' => $_verified->isVerified(true),
          'appyear' => $application->getSchoolYear(),
          'appstatus' => $application->getStatus(),
          'verification_type' => $_verified->getType(),
          'date_verified' => date('m/d/Y', strtotime('+1 day', $_verified->getDateCreated()))
        ];
      } elseif ((
          ($st->statusYearCount()
            && (

              (isset($st->getStatuses()[$current_year]) && $st->getStatuses()[$current_year] != mth_student::STATUS_WITHDRAW)  //student not withdrawn for current year
              || (isset($st->getStatuses()[$next_year])  && $st->getStatuses()[$next_year] != mth_student::STATUS_WITHDRAW)  //student not withdrawn for next school year
              || (!isset($st->getStatuses()[$next_year]) && !isset($st->getStatuses()[$current_year]) && $application->isSubmitted()) //student submitted application but not enrolled current and next school year
              || (isset($st->getStatuses()[$current_year]) && $st->getStatuses()[$current_year] == mth_student::STATUS_WITHDRAW && $application->getSchoolYear(true)->getID() == $next_year)   //student withdrawn current school year but submitted application for next school year
            )) || !$st->statusYearCount())
        && (!$_verified || !$_verified->isVerified()) && !in_array($parent->getUserID(), $unverfied_ids)
      ) {
        $unverfied_ids[] = $parent->getUserID();
        if ($_verified &&  !$_verified->isVerified()) {
          if (!in_array($parent->getUserID(), $pending_ids)) {
            $pending[] =  [
              'user_id' => $parent->getUserID(),
              'email' => preg_replace('#<script(.*?)>(.*?)</script>#is', '', $parent->getEmail()),
              'name' => $parent->getName(),
              'isv' => $_verified->isVerified(true),
              'verification_type' => $_verified->getType(),
              'date_verified' => date('m/d/Y', strtotime('+1 day', $_verified->getDateCreated()))
            ];
            $pending_ids[] = $parent->getUserID();
          }
        } else {
          $unverified[] = [
            'user_id' => $parent->getUserID(),
            'email' => preg_replace('#<script(.*?)>(.*?)</script>#is', '', $parent->getEmail()),
            'name' => $parent->getName()
          ];
        }
      }
    }
  }

  echo json_encode([
    'unverified' => $unverified,
    'new_applicant' => $new_applicant,
    'pending' => $pending
  ]);
  exit;
}

if (req_get::bool('unverified')) {
  $search = new mth_person_filter();
  $search->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
  $search->setStatusYear(
    [
      mth_schoolYear::getCurrent()->getID()
    ]
  );
  $search->setObserver(true);

  $people = $search->getParents();
  $unverified = [];
  $pending = [];
  $new_applicant = [];
  $unverfied_ids = [];
  $new_applicat_ids = [];
  $pending_ids = [];

  foreach ($people as $person) {
    $verified = mth_emailverifier::getByUserId($person->getUserID());
    if ($verified) {
      if (!$verified->isVerified()) {
        $pending[] =  [
          'user_id' => $person->getUserID(),
          'email' => preg_replace('#<script(.*?)>(.*?)</script>#is', '', $person->getEmail()),
          'name' => $person->getName(),
          'isv' => $verified->isVerified(true),
          'verification_type' => $verified->getType(),
          'date_verified' => date('m/d/Y', strtotime('+1 day', $verified->getDateCreated()))
        ];
        $pending_ids[] = $person->getUserID();
      }
    } else {
      $unverfied_ids[] = $person->getUserID();
      $unverified[] = [
        'user_id' => $person->getUserID(),
        'email' => preg_replace('#<script(.*?)>(.*?)</script>#is', '', $person->getEmail()),
        'name' => $person->getName()
      ];
    }
  }

  echo json_encode([
    'unverified' => $unverified,
    'new_applicant' => $new_applicant,
    'pending' => $pending
  ]);
  exit;
}


core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Unverified');
cms_page::setPageContent('This page shows email addresses that has not yet been verified');
core_loader::printHeader('admin');
?>
<style>
  .dataTables_info {
    display: none;
  }
</style>
<div class="unverified_container">
  <div class="card">
    <div class="card-header">
      <button class="btn btn-primary btn-round verify-btn" type="button" onclick="verify()"><span class="verify-txt">Send Verification</span> <span class="unverified_count"></span></button>
      Verification Not Sent To This Users
    </div>
    <div class="card-block" id="unverified_tbl">
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      (<span class="pending_count"></span>) Parents that has Pending Verification. Please note resend verification one at a time to avoid SES flooding.
    </div>
    <div class="card-block">
      <table class="table" id="pending_tbl">
        <thead>
          <th></th>
          <th>Email</th>
          <th>Due Date</th>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      (<span class="new_applicant_count"></span>) New Parents that has Pending Verification. Please note resend verification one at a time to avoid SES flooding.
    </div>
    <div class="card-block">
      <table class="table" id="new_applicant_tbl">
        <thead>
          <th></th>
          <th>Email</th>
          <th>Application</th>
          <th>Due Date</th>
        </thead>
        <tbody>

        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script type="text/javascript">
  var vindex = 0;
  unverified = [];
  $(function() {
    function load_new_applicant(data) {
      $('.new_applicant_count').text(data.length);
      var $tbl = $('#new_applicant_tbl');
      data.length > 0 && data.map(function(a) {
        $tbl.find("#nparent-" + a.user_id).length == 0 && $tbl.find('tbody').append(' <tr id="nparent-' + a.user_id + '">' +
          '<td data-isv="' + a.isv + '">' +
          '<a href="#" class="nresend" data-email="' + a.email + '" data-userid="' + a.user_id + '" data-type="' + a.verification_type + '">Resend</a>' +
          '<i class="fa fa-check sent" style="display:none;color:green;"></i>' +
          '<i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>' +
          '</td>' +
          '<td>' +
          a.name +
          '(' + a.email + ')' +
          '</td>' +
          '<td>' +
          a.appyear + '<small>(' + a.appstatus + ')</small>' +
          '</td>' +
          '<td>' +
          a.date_verified +
          '</td></tr>');
      });
    }

    function load_pending(data) {
      $('.pending_count').text(data.length);
      var $tbl = $('#pending_tbl');
      data.length > 0 && data.map(function(b) {
        $tbl.find('#pparent-' + b.user_id).length == 0 && $tbl.find('tbody').append('<tr id="pparent-' + b.user_id + '"><td data-isv="' + b.isv + '"><a href="#" class="resend" data-email="' + b.email + '" data-userid="' + b.user_id + '" data-type="' + b.verification_type + '">Resend</a>' +
          '<i class="fa fa-check sent" style="display:none;color:green;"></i><i class="fa fa-exclamation-circle error" style="display:none;color:red"></i></td><td>' +
          b.name +
          '(' + b.email + ')' +
          '</td>' +
          '<td>' +
          b.date_verified +
          '</td></tr>');
      });
    }

    function load_unverified(data) {
      var $tbl = $('#unverified_tbl');
      unverified = data;
      $('.unverified_count').text(data.length);
      data.length > 0 && data.map(function(c) {
        $tbl.find('#parent-' + c.user_id).length == 0 && $tbl.append(' <div id="parent-' + c.user_id + '"><i class="fa fa-check sent" style="display:none;color:green;"></i><i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>' + c.name +
          '(' + c.email + ')' +
          '</div>');
      });
    }


    function load_all() {
      global_waiting();
      $.ajax({
        url: '?unverified=1',
        type: 'get',
        dataType: 'JSON',
        success: function(response) {
          load_new_applicant(response.new_applicant);
          load_pending(response.pending);
          load_unverified(response.unverified);
          load_all_app();
        },
        error: function() {
          alert('Error loading unverified');
          global_waiting_hide();
        }
      });;
    }

    function load_all_app() {
      $.ajax({
        url: '?unverified_application=1',
        type: 'get',
        dataType: 'JSON',
        success: function(response) {
          load_new_applicant(response.new_applicant);
          load_pending(response.pending);
          load_unverified(response.unverified);
          global_waiting_hide();
        },
        error: function() {
          global_waiting_hide();
          alert('Error loading unverified');
        }
      });
    }

    load_all();


    $('.unverified_container').on('click', '.resend', function() {
      var $this = $(this);
      var data = $this.data();
      $this.removeAttr('href').text('Sending..');
      $.ajax({
        'url': '?verify=1',
        'type': 'post',
        'data': {
          'user_id': data.userid,
          'email': data.email,
          'verification_type': data.type
        },
        dataType: "json",
        success: function(response) {
          var data = response.data;
          if (response.error == 0) {
            $('#pparent-' + data.id).find('.sent').fadeIn();
          } else {
            $('#pparent-' + data.id).find('.error').fadeIn();
          }
          $this.hide();
        },
        error: function() {
          alert('there is an error occur when verifying');
        }
      });
      return false;
    });

    $('.unverified_container').on('click', '.nresend', function() {
      var $this = $(this);
      var data = $this.data();
      $this.removeAttr('href').text('Sending..');
      $.ajax({
        'url': '?verify=1',
        'type': 'post',
        'data': {
          'user_id': data.userid,
          'email': data.email,
          'verification_type': data.type
        },
        dataType: "json",
        success: function(response) {
          var data = response.data;
          if (response.error == 0) {
            $('#nparent-' + data.id).find('.sent').fadeIn();
          } else {
            $('#nparent-' + data.id).find('.error').fadeIn();
          }
          $this.hide();
        },
        error: function() {
          alert('There is an error occur when verifying');
        }
      });
      return false;
    });
  });

  function verify() {
    $('.verify-btn').attr('disabled', 'disabled').find('.verify-txt').text('Sending..');
    vinterval = setInterval(function() {
      var item = unverified[vindex++];
      if (typeof item != 'undefined') {
        _verify(item);
      } else {
        $('.verify-btn').prop('disabled', false);
        $('.verify-btn').find('.verify-txt').text('Send Verification');
        // console.log('DONE');
        clearInterval(vinterval);
      }
    }, 2000);
  }

  function _verify(item) {

    $.ajax({
      'url': '?verify=1',
      'type': 'post',
      'data': {
        'user_id': item.user_id,
        'email': item.email
      },
      dataType: "json",
      success: function(response) {
        var data = response.data;
        if (response.error == 0) {
          $('#parent-' + data.id).find('.sent').fadeIn();
        } else {
          $('#parent-' + data.id).find('.error').fadeIn();
        }
        $('.verify-count').text(unverified.length - vindex);
      },
      error: function() {
        alert('there is an error occur when verifying');
      }
    });
  }
</script>