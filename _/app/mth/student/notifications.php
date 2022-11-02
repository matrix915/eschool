<?php

/**
 * student notifications
 *
 * @author abe
 */
class mth_student_notifications
{
  const NONE = 'No notifications';
  const APPLICANT = '%s application received';
  const PACKET = '<a href="/student/%2$s/packet">Submit %1$s\'s Enrollment Packet</a>';
  const PACKET_MISSING = '<a href="/student/%2$s/packet">Submit missing information for %1$s\'s Enrollment Packet</a>';
  const PACKET_WAITING = 'Pending Enrollment Packet approval';
  const SCHEDULE = '<a href="/student/%2$s/schedule/%3$s">Submit %1$s\'s %3$s Schedule</a>';
  const RE_SCHEDULE = '<a href="/student/%2$s/schedule/%3$s">Resubmit %1$s\'s %3$s Schedule</a>';
  const SCHEDULE_WAITING = 'Pending Schedule approval';
  const SCHEDULE_CLOSE_WAITING = 'Enrolled - Waiting for Schedule to be reviewed';
  const SCHEDULE_CLOSED = 'Enrolled for %s - waiting for Schedule Builder to open';
  const SCHEDULE_2ND_SEM = '<a href="/student/%s/schedule/%s">2nd Semester Schedule Update Available</a>';
  const REAPPLY = '<a href="/student/%s?reapply=%s">Re-apply for %s school year</a>';
  const REENROLL = 'Declare Intent to Re-enroll for %2$s program: <a href="/student/%1$s?IntentToReEnroll=Yes" class="btn btn-sm btn-primary">Yes</a> <a href="/student/%1$s?IntentToReEnroll=No" style="color:#757575" data-year="%3$s" class="btn btn-sm btn-default intent-no">No</a>';
  const CREATE_ACCOUNT = '<a href="/student/%2$s/account">Create %1$s\'s Yoda account</a>';
  const WITHDRAWAL_LETER = '<a href="/student/%1$s/withdrawal">Sign Withdrawal Letter</a>';

  public static function getStudentNotifications(mth_student $student)
  {
    $notificationsArr = array();
    $reEnrollYear = mth_schoolYear::getYearReEnrollOpen();
    $packet = mth_packet::getStudentPacket($student);
    $openSchYear = mth_schoolYear::getOpenReg();
    $schedule = mth_schedule::get($student, $openSchYear);

    if ($openSchYear && $student->isPendingOrActive($openSchYear)) {
      if ((!$schedule || !$schedule->isSubmited()) && ($packet && $packet->isAccepted())) {
        if ($schedule && $schedule->isToChange()) {
          $notificationsArr[] = sprintf(self::RE_SCHEDULE, $student->getPreferredFirstName(), $student->getSlug(), $openSchYear);
        } else {
          $notificationsArr[] = sprintf(self::SCHEDULE, $student->getPreferredFirstName(), $student->getSlug(), $openSchYear);
        }
      } elseif ($schedule && !$schedule->isAccepted()) {
        $notificationsArr[] = self::SCHEDULE_WAITING;
      }
    }

    if (
      !$notificationsArr
      && $openSchYear != ($curYear = mth_schoolYear::getCurrent())
      && ($schedule = mth_schedule::get($student, $curYear))
      && $schedule->isToChange()
    ) {
      $notificationsArr[] = sprintf(self::RE_SCHEDULE, $student->getPreferredFirstName(), $student->getSlug(), $curYear);
    }

    $status = $student->getStatus(mth_schoolYear::getCurrent());
    if (!$status) {
      $status = $student->getStatus(mth_schoolYear::getNext());
    }

    $hasPendingApplication = $student->getStudentApplication() && $student->getStudentApplication()->getStatus() == mth_application::STATUS_SUBMITTED;

    switch ($status) {
      case mth_student::STATUS_ACTIVE:
        if (($year = mth_schoolYear::get2ndSemOpenReg())
          && ($schedule = mth_schedule::get($student, $year))
          && $schedule->second_sem_change_available()
          && $schedule->isToChange()
        ) {
          $notificationsArr[] = sprintf(self::SCHEDULE_2ND_SEM, $student->getSlug(), $schedule->schoolYear()->getName());
          break;
        }
        if ( $reEnrollYear
           /* Used to let QA test re-enrollment and has been coomented out for the moment */
           /* (($reEnrollYear) || (preg_match('/(viviend)*(@codev\.com)/', core_user::getUserEmail()) && $reEnrollYear = mth_schoolYear::getNext()))*/
          && !$student->getStatus($reEnrollYear)
          && !$student->isGraduated()
          && !$student->isActive($reEnrollYear->getNextYear())
          && $student->getGradeLevel() != 12
        ) {
          $notificationsArr[] = sprintf(self::REENROLL, $student->getSlug(), $reEnrollYear, $reEnrollYear);
        }

        if (empty($notificationsArr) && ($next_status = $student->getStatus(mth_schoolYear::getNext())) && $next_status == mth_student::STATUS_PENDING) {
          if ($packet && $packet->isMissingInfo()) {
            $notificationsArr[] = sprintf(self::PACKET_MISSING, $student->getPreferredFirstName(), $student->getSlug());
          } elseif ($packet && !$packet->isAccepted()) {
            $notificationsArr[] = self::PACKET_WAITING;
          } else {
            $notificationsArr[] = sprintf(self::SCHEDULE_CLOSED, mth_schoolYear::getNext());
          }
        }
        break;
      case mth_student::STATUS_PENDING:
        if (empty($notificationsArr)) {
          if ($schedule && !$schedule->isAccepted()) {
            $notificationsArr[] = self::SCHEDULE_CLOSE_WAITING;
          } else {
            if ($student->getStatus(mth_schoolYear::getCurrent()) != mth_student::STATUS_PENDING) {
              $notificationsArr[] = sprintf(self::SCHEDULE_CLOSED, mth_schoolYear::getNext());
            }
            else if ($packet && ($packet->isMissingInfo())) {
              $notificationsArr[] = sprintf(self::PACKET_MISSING, $student->getPreferredFirstName(), $student->getSlug());
            } else if ($packet && !$packet->isAccepted()) {
              $notificationsArr[] = self::PACKET_WAITING;
            } else {
              $notificationsArr[] = sprintf(self::SCHEDULE_CLOSED, mth_schoolYear::getCurrent());
            }
          }
        }
        break;
      default:
        $application = mth_application::getStudentApplication($student);
        if ($student->isWithdrawn() && ($year = mth_schoolYear::getNext()) && ($curyear = mth_schoolYear::getCurrent()) && ($withdrawal_form = mth_withdrawal::getByStudent($student->getID(), $curyear->getID())) && $withdrawal_form->isActive() && !$hasPendingApplication) {
          if ($withdrawal_form->isStatusNotified()) {
            $notificationsArr[] = sprintf(self::WITHDRAWAL_LETER, $student->getSlug());
          } elseif ($withdrawal_form->sent_to_dropbox() || $withdrawal_form->isUndeclared()) {
            $same_school_year_notif = false;
            if (time() < $curyear->getApplicationClose()) {
              $notificationsArr[] = sprintf(self::REAPPLY, $student->getSlug(), $curyear->getStartYear(), $curyear);
              $same_school_year_notif = true;
            } elseif ($student->isPending($year) && !$openSchYear) {
              $notificationsArr[] = self::SCHEDULE_CLOSED;
            } else {
              $notificationsArr[] = sprintf(self::REAPPLY, $student->getSlug(), $year->getStartYear(), $year);
            }

            if ($curyear->isMidYearAvailable() && !$same_school_year_notif && time() >= $curyear->getMidyearOpen() && time() <= $curyear->getMidyearClose()) {
              $notificationsArr[] = sprintf(self::REAPPLY, $student->getSlug(), $curyear->getStartYear(), $curyear);
            }
          }
        }elseif(!$openSchYear && !$schedule && $student->isPending($year = mth_schoolYear::getNext()) && $student->wasWithdrawn(mth_schoolYear::getCurrent())){
          $notificationsArr[] = sprintf(self::SCHEDULE_CLOSED, $year);
        } elseif (($application) && !$application->isAccepted()) {
          $notificationsArr[] = sprintf(self::APPLICANT, $application->getSchoolYear());
        } elseif ($packet && $packet->isMissingInfo()) {
          $notificationsArr[] = sprintf(self::PACKET_MISSING, $student->getPreferredFirstName(), $student->getSlug());
        } elseif ($packet && !$packet->isSubmitted()) {
          $notificationsArr[] = sprintf(self::PACKET, $student->getPreferredFirstName(), $student->getSlug());
        }
        elseif ($packet && !$packet->isAccepted()) {
          $notificationsArr[] = self::PACKET_WAITING;
        } elseif (
          !$student->getStatus($reEnrollYear)
          && !$student->isGraduated()
          && null != $reEnrollYear
          && $student->isActive($reEnrollYear->getPreviousYear())
          && !$student->isActive($reEnrollYear->getNextYear())
          && $reEnrollYear->getReEnrollDeadline() > time()
          //&& $student->getGradeLevel() != 12 so that grade12 will be accommodated.
        ) {
          $notificationsArr[] = sprintf(self::REENROLL, $student->getSlug(), $reEnrollYear, $reEnrollYear);
        } elseif (
          !$student->isGraduated()
          && !$student->hadGraduated()
          && ($curyear = mth_schoolYear::getCurrent())
          && !$student->wasWithdrawn($curyear)
          && ($student->hasPendingOrActiveStatus() || $student->hasBeenWithdrawn($curyear->getID()))
          && !$notificationsArr
          && !$hasPendingApplication
        ) {
          $same_school_year_notif = false;
          if (time() < $curyear->getApplicationClose()) {
            $notificationsArr[] = sprintf(self::REAPPLY, $student->getSlug(), $curyear->getStartYear(), $curyear);
            $same_school_year_notif = true;
          } else {
            $notificationsArr[] = sprintf(self::REAPPLY, $student->getSlug(), $curyear->getNext()->getStartYear(), $curyear->getNext());
          }

          if ($curyear->isMidYearAvailable() && !$same_school_year_notif && time() >= $curyear->getMidyearOpen() && time() <= $curyear->getMidyearClose()) {
            $notificationsArr[] = sprintf(self::REAPPLY, $student->getSlug(), $curyear->getStartYear(), $curyear);
          }
        }
        elseif ( empty($application) ) {
          $notificationsArr[] = sprintf(self::APPLICANT, $curyear);
        }
        break;
    }
    if (empty($notificationsArr)) {
      $notificationsArr[] = self::NONE;
    }
    return $notificationsArr;
  }

  /**
   * Student Summaries only distinguish between Active and Pending (for whatever reasons) and Not Enrolled (graduated, withdrawn, not accepted, etc):
   *
   * @param array $students
   * @param boolean $formmated
   * @return array|string
   */
  public function getStudentsSummaries($students = array(), $formatted = false)
  {

    $summary = [
      mth_student::STATUS_LABEL_ACTIVE => 0,
      mth_student::STATUS_LABEL_PENDING => 0,
      mth_student::STATUS_LABEL_NOT_ENROLLED => 0
    ];

    foreach ($students as $student) {
      $status = $student->getStatus(mth_schoolYear::getCurrent());
      switch ($status) {
        case mth_student::STATUS_ACTIVE:
          $summary[mth_student::STATUS_LABEL_ACTIVE] += 1;
          break;
        case mth_student::STATUS_PENDING:
          $summary[mth_student::STATUS_LABEL_PENDING] += 1;
          break;
        default:
          // case mth_student::STATUS_LABEL_WITHDRAW:
          // case mth_student::STATUS_LABEL_GRADUATED:
          $summary[mth_student::STATUS_LABEL_NOT_ENROLLED] += 1;
      }
    }

    if ($formatted) {
      $return_array = [];
      foreach ($summary as $key => $value) {
        $return_array[] = $value . ' - ' . $key;
      }
      return implode(' | ', $return_array);
    }
    return $summary;
  }
}
