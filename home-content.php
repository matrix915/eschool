<?php

use mth\student\SchoolOfEnrollment;

if (!core_user::getUserID()) {
  core_loader::redirect('/');
}
$user = core_user::getCurrentUser();

if (isset($_GET['announcement_count'])) {
  $announcements = count(mth_announcements::getAllAnnouncements(false));
  echo ($announcements < $user->getRedAnnouncements()) ? 0 : ($announcements - $user->getRedAnnouncements());
  exit();
}

if (isset($_GET['red_notifications'])) {
  $notifications = $user->getRedNotifications();
  $response = "[]";
  if ($notifications) {
    $response = json_encode(explode('|', $notifications));
  }
  echo $response;
  exit();
}

if (isset($_GET['red_notification'])) {
  $user->setRedNotifications($_GET['red_notification']);
  exit();
}

if (isset($_GET['unread_notification'])) {
  $user->setUnreadNotification($_GET['unread_notification']);
  exit();
}

if (isset($_GET['upcomingevents'])) {
  $data = [];
  while ($event = mth_events::getUpcoming()) {
    $_data = [
      'name' => $event->eventName(),
      'start' => $event->startDate(),
      'color' => $event->color(),
      'content' => $event->content(),
      'event' => $event->id()
    ];
    if ($event->endDate() != '0000-00-00 00:00:00') {
      $_data['end'] = $event->endDate();
    }
    $data[] = $_data;
  }
  echo json_encode($data);
  exit();
}

$parent = mth_parent::getByUser();
$viewonly = $parent ? $parent->isObserver() : true;

$openSchYear = mth_schoolYear::getOpenReg();
if (!$parent || mth_purchasedCourse::hasPurchasedCourse($parent)) {
  core_loader::redirect('/reg');
}




cms_page::setPageTitle('Parent Home');
cms_page::setPageContent('<p>This site will allow you to manage your students, their applications, enrollments, and schedules.</p>');

core_loader::printHeader('student');

?>
<style>
  .calendar-event {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .alert-danger .checkbox-custom label::before {
    border: 1px solid #e0e0e0;
  }

  .read-item {
    display: inline-block;
  }

  /**
    * Medium and BELOW
    */
  @media (max-width:1024px) {
    .profile-action {
      padding-top: 30px;
    }
  }

  @media (max-width:1024px) and (min-width:768px) {
    .contact-info-card {
      min-height: 255px;
    }
  }

  .graduated {
    display: none;
  }
</style>
<div class="page parent-profile-page">
  <div class="page-content container-fluid">
    <div class="row">
      <div class="col-md-8 profile-box">
        <div class="card">
          <a href="/_/user/profile">
            <div class="cover-photo">
            </div>
          </a>
          <div class="card-block wall-person-info">
            <a class="avatar bg-white img-bordered person-avatar avatar-cont" href="/_/user/profile">
            </a>
            <h2 class="person-name">
              <a href="/_/user/profile"><?= $parent->getName() ?></a>
            </h2>
            <div class="card-text">
              <a class="blue-grey-400"><span><?= $parent->getEmail() ? $parent->getEmail() : '&nbsp;' ?></span></a>
            </div>
            <div class="profile-action">
              <a class="btn btn-default btn-round  mr-10" href="/_/user/profile">
                Edit Profile
              </a>
              <a class="btn btn-primary btn-round  mr-10" href="?logout=1">
                Logout
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 calendar-box">
        <div class="card p-20 contact-info-card">
          <h4 class="card-title">
            Coming Up
            <a class="float-right" href="/_/user/calendar"><i class="fa fa-calendar"></i></a>
          </h4>
          <div class="card-block p-0">
            <div class="list-group list-group-full mb-0 upcoming-events">
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- End Row -->
    <!-- LInk boxes -->
    <div class="row">
      <div class="col-lg-3 col-md-6">
        <div class="card card-shadow">
          <div class="card-block">
            <a class="btn btn-primary btn-round btn-block link-btn parent-handbook" href="#" target="_blank">
              <i class="fa fa-book float-left" aria-hidden="true"></i>
              <strong style="font-weight:bold">New Parent Link</strong><br>
              <small>Check your email for login instructions</small>
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card card-shadow">
          <div class="card-block">
            <a class="btn btn-success btn-round btn-block link-btn parent-announcement" href="/_/user/announcement">
              <i class="fa fa-link float-left" aria-hidden="true"></i>
              <strong style="font-weight:bold">Announcements</strong>
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card card-shadow">
          <div class="card-block">
            <a class="btn  btn-danger  btn-round btn-block link-btn students-homeroom" href="/_/user/grades-messages">
              <!-- btn-secondary disabled-->
              <i class="fa fa-dashboard float-left" aria-hidden="true"></i>
              <strong style="font-weight:bold">Homeroom</strong>
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card card-shadow">
          <div class="card-block">
            <?php if (($os = core_setting::get('oldreimbursement', 'advance')) && $os->getValue()) : ?>
              <a class="btn btn-info btn-block btn-round link-btn reimbursement-item" href="/forms/reimbursement">
                <i class="fa fa-tag float-left" aria-hidden="true"></i>
                <strong style="font-weight:bold">Reimbursements</strong>
              </a>
            <?php endif; ?>
            <?php if (($ms = core_setting::get('mustangreimbursement', 'advance')) && $ms->getValue()) : ?>
              <a class="btn btn-info btn-block btn-round link-btn reimbursement-item" href="<?= MUSTANG_URI ?>">
                <i class="fa fa-tag float-left" aria-hidden="true"></i>
                <strong style="font-weight:bold">Reimbursements<br />Direct Orders</strong>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <!-- End Link Boxes -->
    <div class="panel container-collapse parent-link-collapse">
      <div class="panel-heading " data-toggle="collapse" aria-hidden="true" href="#parent-links" aria-controls="parent-links">
        <div class="pr-20 pl-20 pt-2 float-left">
          <a href="#" class="avatar avatar-lg avatar-cont" style="height:50px;background-image:url(<?= core_config::getThemeURI() . '/assets/photos/mth-logo.png' ?>)">
            <span class="badge badge-pill badge-danger up m-0 plink-badge" style="display:none"></span>
          </a>
        </div>
        <h3 class="panel-title">
          More Quick Links
        </h3>

        <div class="panel-actions panel-actions-keep">
          <i class="panel-action icon md-chevron-down icon-collapse profile-child-control"></i>
        </div>
      </div>
      <div class="panel-body collapse info-collapse show" id="parent-links">
        <div class="links mt-20">
          <div class="row">

            <div class="col-md-6">
              <p class="mb-10 text-nowrap">
                <a href="https://www.mytechhigh.com/livechat/" target="_blank">
                  <i class="fa fa-comment mr-5"></i>
                  <span class="text-break" style="margin-left:-1px;">Q&A Help
                    <span>
                </a>
              </p>
              <p class="mb-10 text-nowrap">
                <a href="/_/user/calendar" target="_blank">
                  <i class="fa fa-calendar mr-5"></i>
                  <span class="text-break">Check out the Calendar
                    <span>
                </a>
              </p>
              <p class="mb-10 text-nowrap">
                <a href="https://www.mytechhigh.com/consultation/" target="_blank">
                  <i class="fa fa-address-book mr-5"></i>
                  <span class="text-break">Share a student success story
                    <span>
                </a>
              </p>
              <p class="mb-10 text-nowrap">
                <a href="/forms/test-opt-out">
                  <i class="fa fa-flag mr-5"></i>
                  <span class="text-break" style="margin-left:-1px;">
                    Submit a State Testing Opt-out Form
                    <span>
                </a>
              </p>
            </div>
            <div class="col-md-6">

              <p class="mb-10 text-nowrap">
                <a href="https://www.mytechhigh.com/consultation/" target="_blank">
                  <i class="fa fa-graduation-cap mr-5"></i>
                  <span class="text-break" style="margin-left:-5px;">
                    Request a Transcript (grades 9-12)
                    <span>
                </a>
              </p>
              <p class="mb-10 text-nowrap">
                <a href="http://mytechhigh.com/withdraw" target="_blank">
                  <i class="fa fa-exclamation-circle mr-5"></i>
                  <span class="text-break">Initiate the Withdrawal process
                    <span>
                </a>
              </p>
              <p class="mb-10 text-nowrap">
                <a href="https://docs.google.com/document/d/1bxOu5kfwsnlJ2pC5gFTZURd_do2kpLR38wdAVu-Uadw/edit" target="_blank">
                  <i class="fa fa-users mr-5"></i>
                  <span class="text-break" style="margin-left:-2px;">
                    Begin the Special Ed referral process
                    <span>
                </a>
              </p>
              <p class="mb-10 text-nowrap">
                <a href="https://www.mytechhigh.com/consultation/" target="_blank">
                  <i class="fa fa-refresh mr-5"></i>
                  <span class="text-break">Request a 1-1 Consultation
                    <span>
                </a>
              </p>
            </div>
          </div>
        </div>
        <div role="alert" class="alert dark alert-icon alert-danger mt-20 p-20 plink-notif" style="display:none">
          <p><a href="/_/user/announcement" style="color:#fff"><i class="icon md-notifications mr-10" aria-hidden="true"></i>Important Parent Link Announcement</a></p>
        </div>
      </div>
    </div>
    <div class="alert alert-alt alert-info">
      <div class="checkbox-custom checkbox-primary">
        <input type="checkbox" value="1" name="showgraduates" id="showgraduates">
        <label>Show Graduated</label>
      </div>
    </div>

    <?php $STUDENTS = $parent->getAllStudents();
    // $summary = mth_student_notifications::getStudentsSummaries($STUDENTS,true);
    $valid_students = 0;
    $has_new_active_student = 0;
    foreach ($STUDENTS as $student) :
      if (in_array($student->getStatus(), [mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]) || in_array($student->getStatus(mth_schoolYear::getNext()), [mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING])) {
        $valid_students++;
      }
      $notifications = mth_student_notifications::getStudentNotifications($student);
      $student_canvas = mth_canvas_user::get($student);
      $is_graduated = $student->hadGraduated();
      //active/pending condition
      // if (in_array($student->getStatus(mth_schoolYear::getByID(mth_schoolYear::getCurrent()->getID())), [mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING])) :
      //     $has_new_active_student = 1;
      ?>
      <div class="panel <?= $is_graduated ? 'graduated graduated-list' : '' ?>">
        <div class="panel-heading">
          <div class="pr-20 pl-20 pt-15 pb-10 float-left">
            <a class="avatar avatar-lg avatar-cont" style="height:50px;background-image:url(<?= $student_canvas && $student_canvas->avatar_url() ? $student_canvas->avatar_url() : (core_config::getThemeURI() . '/assets/portraits/default.png') ?>)">
              <?php
                if (count($notifications) == 1 && in_array(mth_student_notifications::NONE, $notifications)) {
                  $notifications = [];
                }
                ?>

            </a>
          </div>
          <h3 class="panel-title">

            <?= $student ?>
            <br><small><?= $student->getGradeLevel(true,) ?></small>
            <?php if (($school = $student->getSOEname(mth_schoolYear::getCurrent())) && $school != SchoolOfEnrollment::get(SchoolOfEnrollment::Unassigned)) : ?>
              <small>: <?= $school ?> </small>
            <?php endif; ?>
          </h3>
        </div>
        <div class="panel-body student-collapse">
          <?php if ($is_graduated) : ?>
            <b style="color:#4CAF50"><i class="fa fa-graduation-cap"></i> GRADUATED / COMPLETED</b>
          <?php endif; ?>
          <div data-info-type="email" class="text-nowrap">
            <?php if ($student->getEmail()) : ?>
              <span class="text-break">
                <a><?= $student->getEmail() ?></a>
                <span>
                  <?php if (!$viewonly) : ?>
                    <a class="ml-10" href="#" data-toggle="modal" data-target="#changeEmail" data-slug="<?= $student->getSlug() ?>" data-email="<?= $student->getEmail() ?>"><i class="icon md-edit"></i> <small>(Edit Email)</small></a>
                  <?php endif; ?>
                <?php endif; ?>
          </div>


          <?php if (!empty($notifications) && !$viewonly) : ?>
            <br>
            <div role="alert" class="alert dark alert-icon alert-danger">

              <i class="icon md-notifications" aria-hidden="true"></i>
              <h4>Notifications</h4>
              <ul class="student-notification-list">
                <?php foreach ($notifications as $notification) : ?>
                  <li><?= $notification ?></li>
                <?php endforeach; ?>
              </ul>

            </div>
          <?php endif; ?>


        </div>
        <?php $nextYear = mth_schoolYear::getNext();
          // $hasPacket = (( //($student->getStatus() || ($nextYear && $student->getStatus($nextYear))) &&
          // $packet = mth_packet::getStudentPacket($student))
          //     //&& $packet->getDateAccepted()
          // )
          //     || (!$student->getStatus()
          //     && ($nextYear)
          //     && !$student->getStatus($nextYear)
          //     && ($app = mth_application::getStudentApplication($student))
          //     && $app->isAccepted());

          $still_active_on_current_year = $student->isActive(mth_schoolYear::getCurrent()) && ($packet = mth_packet::getStudentPacket($student));
          $packet = mth_packet::getStudentPacket($student, true);
          $view_packet = $still_active_on_current_year || (
            $packet
            && ($app = mth_application::getStudentApplication($student))
            && $app->isAccepted());



          $schedule = mth_schedule::eachOfStudent($student->getID());
          $schedSlug = $schedule ? "/student/{$student->getSlug()}/schedule/{$schedule->schoolYear()}" : '';

          $cansubmitsched = false;
          if ($openSchYear && $student->isPendingOrActive($openSchYear) && ( $packet && $packet->getStatus() == mth_packet::STATUS_ACCEPTED) ) {
            $schedSlug = "/student/{$student->getSlug()}/schedule/{$openSchYear}";
            $cansubmitsched = true;
          }

          ?>
        <div class="panel-footer pt-20">
          <a class="btn  btn-round  waves-effect waves-light waves-round children-action <?= $view_packet && !$viewonly ? 'btn-mth-blue' : 'btn-secondary disabled' ?>" href="/student/<?= $student->getSlug() ?>/packet" style="color:#fff">Enrollment Packet</a>
          <a class="btn  btn-round  waves-effect waves-light waves-round children-action <?= ($schedule || $cansubmitsched) && !$viewonly ? 'btn-mth-orange' : 'btn-secondary disabled' ?>" style="color:#fff" href="<?= $schedSlug ?>">Schedule</a>
          <!-- <a class="btn btn-round btn-primary children-action" data-toggle="collapse" aria-expanded="true" href="#additional-info-<?= $student->getSlug() ?>" aria-controls="additional-info-<?= $student->getSlug() ?>">Additional Info</a> -->

        </div>
      </div>
      <!-- End Student Container -->
      <?php
        //endif;
        ?>

    <?php endforeach; ?>
    <?php if (!$viewonly) : ?>
      <div class="card card-shadow">
        <div class="card-block">
          <a class="btn btn-default btn-round btn-block waves-effect waves-light waves-round" href="/student/new">
            <i class="icon md-account-add float-left" aria-hidden="true"></i>Add New Student
          </a>
        </div>
      </div>
    <?php endif; ?>
    <!-- End New Student -->
  </div>
  <!-- End Page Content -->
</div>
<!-- End Page -->


<!-- Change Student Email Modal -->
<div class="modal fade" id="changeEmail" tabindex="-1" role="dialog" aria-labelledby="changeEmail" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="changeEmailForm" method="GET">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="changeEmailTitle">Email</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">

          <div class="form-group">
            <input type="email" class="form-control" id="updateEmail" name="updateEmail" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel</button>
          <button type="submit" id="update" class="btn btn-primary btn-round" data-loading-text="Updating.." autocomplete="off">Update</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- End Change Student Email Modal -->
<!-- View Modal EVent -->
<div class="modal fade" id="viewEvent" aria-hidden="true" aria-labelledby="viewEvent" role="dialog" tabindex="-1" data-show="false">
  <div class="modal-dialog modal-simple">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" aria-hidden="true" data-dismiss="modal">×</button>
        <h4 class="modal-title event-name">Event Name</h4>
      </div>
      <div class="modal-body">
        <p class="content"></p>
        <span class="event-date"></span>
      </div>
    </div>
  </div>
</div>
<!-- End View Modal Event -->
<!-- Feedback modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="feedbackModal" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="feedbackModalForm" method="POST">
      <div class="modal-content">
        <div class="modal-body">
          <input type="hidden" name="school_year" />
          <div class="form-group">
            <label>
              We’re sorry to hear that you won’t be returning next year. In an effort to help us improve our program, please share the main reason(s) for your decision. Thank you!
            </label>
            <textarea class="form-control" rows="6" name="reason_txt"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-round" data-loading-text="Updating.." autocomplete="off">Submit</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- End Feedback modal -->
<?php
core_loader::includejQueryValidate();
core_loader::addJsRef('momentjs', core_config::getThemeURI() . '/vendor/calendar/moment.min.js');
core_loader::printFooter('student');

?>
<script type="text/javascript">
  $(function() {

    // $('.summary-content').html(summary);

    var parent_link = 'https://mytechhighhelp.zendesk.com/hc/en-us';

    $('.intent-no').click(function() {
      var intent_link = $(this).attr('href');
      var sy = $(this).data('year');
      var $modal = $("#feedbackModal");

      $modal.find('form').attr('action', '');
      $modal.find('[name="lischool_yearnk"]').val('');

      $modal.modal('show');

      $modal.find('form').attr('action', intent_link);
      $modal.find('[name="lischool_yearnk"]').val(sy);

      return false;
    });

    $('.parent-handbook').click(function() {
      window.open(parent_link, '_blank');
      return false;
    });

    $('#changeEmail').on('show.bs.modal', function(e) {
      var $btn = $(e.relatedTarget);
      updateSlug = $btn.data('slug');
      updateEmail = $btn.data('email');
      $('#updateEmail').val(updateEmail);
    });

    $('#changeEmailForm').validate({
      rules: {
        updateEmail: {
          required: true,
          email: true
        }
      }
    });

    $('#update').click(function() {
      var newEmail = $('#updateEmail').val();
      swal({
          title: "Are you sure?",
          text: "You want to set the student\'s email to " + newEmail,
          type: "warning",
          showCancelButton: !0,
          confirmButtonClass: "btn-warning",
          confirmButtonText: "Yes",
          cancelButtonText: "Cancel",
          closeOnConfirm: !1,
          closeOnCancel: true
        },
        function() {
          location = '/student/' + updateSlug + '?updateEmail=' + encodeURIComponent(newEmail);
        });
      return false;
    });


    //getAnnouncement();
    function getAnnouncement() {
      $.ajax({
        url: '?announcement_count=1',
        success: function(response) {

          response > 0 && $('.plink-badge').text(response).fadeIn() && $('.plink-notif').show();
        }
      });
    }

    function removeLinkAction($elem) {
      $elem.removeClass('btn-primary btn-success btn-danger btn-info')
        .addClass('btn-secondary')
        .addClass('disabled')
        .attr('href', '#');
    }

    var profile_pic = '<?= $user && $user->getAvatar() ? $user->getAvatar() : (core_config::getThemeURI() . '/assets/portraits/default.png') ?>';
    $('.person-avatar').css('background-image', 'url(' + profile_pic + ')');

    var valid_students = <?= $valid_students ?>;
    if (valid_students == 0) {
      $('#parent-links .links').html('<div class="alert bg-info mt-20">Look here for additional information when your student’s enrollment is complete.</div>');
      $('.calendar-box').remove();
      $('.profile-box').removeClass('col-md-8').addClass('col-md-12');
      removeLinkAction($('.link-btn:not(".parent-handbook"):not(".parent-announcement")'));
    }

    <?php if ($viewonly) : ?>
      removeLinkAction($('.link-btn.reimbursement-item'));
      $('.parent-link-collapse').remove();
    <?php endif; ?>

    var events = {};
    $.ajax({
      url: '?upcomingevents=1',
      dataType: 'JSON',
      success: function(data) {
        if (data.length > 0) {
          $.each(data, function(key, value) {
            var start_date = moment(value.start).format('MMMM DD');
            events[value.event] = value;
            var $_event = '<a class="list-group-item calendar-event p-5"  data-event="' + value.event + '">' +
              '<i class="md-circle  mr-10" aria-hidden="true" style="color:' + value.color + '"></i>' +
              start_date + ' - ' + value.name +
              '</a>';
            $('.upcoming-events').append($_event);
          });
        }
      },
      error: function() {

      }
    });

    $('.upcoming-events').on('click', '.calendar-event', function() {
      var event = events[$(this).data('event')];
      var dates = [moment(event.start).format('MM/DD/YYYY')];
      if (event.end) {
        dates.push(moment(event.end).format('MM/DD/YYYY'));
      }
      $modal = $("#viewEvent");
      $modal.find('.event-name').html(event.name);
      $modal.find('.content').html(event.content);
      $modal.find('.event-date').text(dates.join(' to '));
      $modal.modal("show");
      return false;
    });

    $('#showgraduates').change(function() {
      if ($(this).is(':checked')) {
        $('.graduated-list').removeClass('graduated');
      } else {
        $('.graduated-list').addClass('graduated');
      }
    });
  });
</script>