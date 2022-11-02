<?php
require_once 'app/inc.php';
require_once 'admin/launchpad/functions.php';

use mth\yoda\assessment;
use mth\yoda\studentassessment;


define('DATE_FILE', core_config::getSitePath() . '/_/cron_last_run');


if (!is_file(DATE_FILE)) {
    file_put_contents(DATE_FILE, '0');
}

$today = date('Ymd');
$lastRun = date('Ymd', file_get_contents(DATE_FILE));

($today != $lastRun) || die();

file_put_contents(DATE_FILE, time());

//clearing out sessions
$expiration = 86400; // in seconds
core_db::runQuery(sprintf("DELETE FROM sessions WHERE accessed < UNIX_TIMESTAMP(NOW())-%d", $expiration));


// Lanchunpad course cronjob
mth_sparkSetting::cron_test(13, 'cron init');

$current_school_year = mth_schoolYear::getCurrent();
$current_year_id = $current_school_year->getID();

mth_sparkSetting::cron_test($current_year_id, 'cron start');


$first_sem_start = "";
$first_sem_start_ob = mth_sparkSetting::getByKey('first_sem_start', $current_year_id);
if ($first_sem_start_ob) {
    $first_sem_start = $first_sem_start_ob->value;
}

$second_sem_start = "";
$second_sem_start_ob = mth_sparkSetting::getByKey('second_sem_start', $current_year_id);
if ($second_sem_start_ob) {
    $second_sem_start = $second_sem_start_ob->value;
}
$sem_end = "";
$sem_end_ob = mth_sparkSetting::getByKey('sem_end', $current_year_id);
if ($sem_end_ob) {
    $sem_end = $sem_end_ob->value;
}

$today = date('Y-m-d');


$course_list_url = "https://tech.sparkeducation.com/api/courses/list";
$spark_res = spark_get_api("GET", $course_list_url);

$sparkMap = [];
foreach ($spark_res['data'] as $key => $value) {
    $spark_id = $value['id'];
    $sparkMap[$value['id']] = $value['name'];
}

if (strtotime($today) >= strtotime($sem_end)) {
    // execute end event
    end_year();
} elseif (strtotime($today) >= strtotime($second_sem_start)) {
    // execute second semester event
    $enroll_provider_course = get_provider_course(2);
    register_user_second($enroll_provider_course, 1, $sparkMap);
} elseif (strtotime($today) >= strtotime($first_sem_start)) {
    // execute first semester event
    $enroll_provider_course = get_provider_course(1);
    register_user($enroll_provider_course, 0, $sparkMap);
}

mth_sparkSetting::cron_test($current_year_id, 'cron end');
// launchpad end

/**
 * Info Changes Email Notification
 * List of changes can be found on /_/admin/info-changes page
 * List can be cleared on info-changes page
 */
$log = mth_log::getLog();
if (!empty($log)) {
    $emailContent = '
          <p>The following changes have been made since the last time the change log was cleared:</p>';
    $pp = NULL;
    foreach ($log as $logItem) {
        /* @var $logItem mth_log */
        if ($logItem->getPersonID() != $pp) {
            if (!$logItem->getPerson()) {
                $logItem->setNotified();
                continue;
            }
            if ($pp != NULL) {
                $emailContent .= '
          </table>';
            }
            $emailContent .= '
          <h3>' . $logItem->getPerson()->getName() . '</h3>
          <table>';
            $pp = $logItem->getPersonID();
        }
        $emailContent .= '
            <tr><td>' . $logItem->getField() . '</td><td>' . $logItem->getNewValue() . '</td></tr>';
    }
    $link = 'http://' . $_SERVER['HTTP_HOST'] . '/_/admin/info-changes';
    $emailContent .= '
          </table>
          <p>Visit <a href="' . $link . '">' . $link . '</a> to view and clear the notifications</p>';
          
    $email = new core_emailservice();
    $email->send(
        array(core_setting::getSiteEmail()->getValue()),
        'Contact Info Changes',
        $emailContent
    );
  
}

//if (core_config::isProduction()) :
/**
 * FIRST PACKET REMINDER
 * Email reminder for Parents to Finished thier kid(s) packet
 */
$packets = mth_packet::getUnSubmitted(strtotime(core_setting::get('packetDeadlineReminder', 'Packets') . ' days'));
foreach ($packets as $packet) {
   if (!($student = $packet->getStudent()) || !$student->getParent() || $packet->isMissingInfo()) {
     continue;
   }
   /* @var $packet mth_packet */
   $email = new core_emailservice();
   $find = [
     '[PARENT]',
     '[STUDENT]',
     '[DEADLINE]',
     '[YEAR]',
   ];
   $replace = [
     $packet->getStudent()->getParent()->getPreferredFirstName(),
     $packet->getStudent()->getPreferredFirstName(),
     $packet->getDeadline('l, F j'),
     mth_packet::getActivePacketYear($packet),
   ];
   $email->send(
     [($packet->getStudent()->getParent())->getEmail()],
     str_replace(
       $find,
       $replace,
       core_setting::get('packetAutoReminderEmailSubject', 'Packets')
     ),
     str_replace(
       $find,
       $replace,
       core_setting::get('packetAutoReminderEmail', 'Packets')
     ),
     null,
     [core_setting::getSiteEmail()->getValue()]
   );
}

/**
 * SECOND PACKET REMINDER
 * Email reminder for Parents to Finished thier kid(s) packet
 */
$packets = mth_packet::getUnSubmitted(strtotime(core_setting::get('packetDeadlineReminderTwo', 'Packets') . ' days'));
foreach ($packets as $packet) {
   if (!($student = $packet->getStudent()) || !$student->getParent() || $packet->isMissingInfo()) {
     continue;
   }

   $find = array(
     '[PARENT]',
     '[STUDENT]',
     '[DEADLINE]',
     '[YEAR]',
   );
   $replace = array(
     $student->getParent()->getPreferredFirstName(),
     $student->getPreferredFirstName(),
     $packet->getDeadline('l, F j'),
     mth_packet::getActivePacketYear($packet),
   );
   $email = new core_emailservice();
   $email->send(
     [($student->getParent())->getEmail()],
     str_replace(
       $find,
       $replace,
       core_setting::get('packetAutoReminderEmailSubjectTwo', 'Packets')
     ),
     str_replace(
       $find,
       $replace,
       core_setting::get('packetAutoReminderEmailTwo', 'Packets')
     )
   );
}
//endif; //end of isProduction condition block


/**
 * SECOND SEMESTER ENABLER
 * This will enable students schedule's second semester
 */
$count = 0;
if (($year = mth_schoolYear::get2ndSemOpenReg())) {
    while ($scheudle = mth_schedule::eachOfYear($year)) {
        if ($scheudle->second_sem_change_available() && !$scheudle->isToChange()) {
            $scheudle->enable2ndSemChanges();
            $count++;
        }
        if ($count >= 10 && !core_config::isProduction()) {
            break;
        }
    }
}

/**
 * SECOND SEMESTER SCHEDULE UNLOCK
 * This will send email to the parent's whos students have second semester schedule update
 */
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

/**
 * SECOND SEMESTER Reminder
 * This will send reminder for students schedule's second semester
 */
$scount = 0;
if (($year = mth_schoolYear::get2ndSemOpenReg())
    && ($deadline = core_setting::get('2ndSemUpdatesRequiredReminder', 'Schedules'))
    && date('Y-m-d', strtotime('-' . abs($deadline->getValue()) . ' days', $year->getSecondSemClose()))  ==  date('Y-m-d')
    && ($emailContent = core_setting::get('2ndSemUpdatesRequiredReminderEmail', 'Schedules'))
    && ($emailSubject = core_setting::get('2ndSemUpdatesRequiredReminderSubject', 'Schedules'))
) {
    $email_batch_id = uniqid();
    while ($schedule = mth_schedule::eachOfYear($year)) {
        if (
            $schedule->second_sem_change_available()
            && $schedule->isToChange()
            && ($student = $schedule->student())
            && ($parent = $student->getParent())
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
                            '[STUDENT]',
                            '[DEADLINE]'
                        ),
                        array(
                            $parent->getPreferredFirstName(),
                            $student->getPreferredFirstName(),
                            $year->getSecondSemClose('F j')
                        ),
                        $emailContent->getValue()
                    )
                );
            } catch (Exception $e) {
                $emaillog->errorMessage($e->getMessage());
                $emaillog->save();
            }

            $scount++;
        }
    }
}

/**
 * MARK GRADUATED
 * This will marke all Grade 12 students as graduates
 * When year of reenrollment is open
 */
$currentYear = mth_schoolYear::getCurrent();
if (($year = mth_schoolYear::getYearReEnrollOpen())
    && (!($reEnrollGraduationsProcessed = core_setting::get('reEnrollGraduationsProcessed', 'Re-enroll'))
        || $reEnrollGraduationsProcessed->getValue() != $year->getID())
) {
    $filter = new mth_person_filter();
    $filter->setStatus(array(mth_student::STATUS_ACTIVE));
    $filter->setStatusYear(array($currentYear->getID()));
    $filter->setExcludeStatusYear(array($year->getID()));
    $filter->setGradeLevel(array(12));

    foreach ($filter->getStudents() as $student) {
        /* @var $student mth_student */
        $student->setStatus(mth_student::STATUS_GRADUATED, $year);
    }
    core_setting::set('reEnrollGraduationsProcessed', $year->getID(), core_setting::TYPE_INT, 'Re-enroll');
}

//if (core_config::isProduction()) :
/**
 * RE-ENROLLMENT NOTICE
 * Send Re-enrollment email for parents notifiying them that next school year re-enrollment is open
 */
// if (($year = mth_schoolYear::getYearReEnrollOpen())
//     && (!($reEnrollNoticeSent = core_setting::get('reEnrollNoticeSent', 'Re-enroll'))
//         || $reEnrollNoticeSent->getValue() != $year->getID())
//     && ($emailContent = core_setting::get('reEnrollEmailContent', 'Re-enroll'))
//     && ($emailSubject = core_setting::get('reEnrollEmailSubject', 'Re-enroll'))
// ) {

//     if (!($reEnrollNoticeSentTo = core_setting::get('reEnrollNoticeSentTo-' . $year->getID(), 'Re-enroll'))) {
//         $reEnrollNoticeSentTo = core_setting::init('reEnrollNoticeSentTo-' . $year->getID(), 'Re-enroll', '');
//     }

//     $filter = new mth_person_filter();
//     $filter->setStatus(array(mth_student::STATUS_ACTIVE));
//     $filter->setStatusYear(array($currentYear->getID()));
//     $filter->setExcludeStatusYear(array($year->getID()));
//     $filter->setGradeLevel(['K', 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);
//     $sentToArr = explode(';', $reEnrollNoticeSentTo->getValue());

//     $count = 0;
//     foreach ($filter->getParents() as $parent) {
//         /* @var $parent mth_parent */
//         if (in_array($parent->getID(), $sentToArr)) {
//             continue;
//         }
//         $email = new core_emailservice();
//         $result = $email->send(
//             [$parent->getEmail()],
//             str_replace(
//                 array(
//                     '[PARENT]',
//                     '[SCHOOL_YEAR]',
//                     '[DEADLINE]'
//                 ),
//                 array(
//                     $parent->getPreferredFirstName(),
//                     $year->getName(),
//                     $year->getReEnrollDeadline('F j')
//                 ),
//                 $emailSubject->getValue()
//             ),
//             str_replace(
//                 array(
//                     '[PARENT]',
//                     '[SCHOOL_YEAR]',
//                     '[DEADLINE]'
//                 ),
//                 array(
//                     $parent->getPreferredFirstName(),
//                     $year->getName(),
//                     $year->getReEnrollDeadline('F j')
//                 ),
//                 $emailContent->getValue()
//             )
//         );

//         if ($result) {
//             $sentToArr[] = $parent->getID();
//         }
//         $count++;

//         if ($count >= 200 || (core_config::isDevelopment() && $count >= 10)) {
//             break;
//         }
//     }
//     $reEnrollNoticeSentTo->update(implode(';', $sentToArr));
//     if ($count < 200) {
//         core_setting::set('reEnrollNoticeSent', $year->getID(), core_setting::TYPE_INT, 'Re-enroll');
//     }
// }
// /**
//  * RE-ENROLLMENT REMINDER
//  * Will send reminder to parents that did not re-enroll their kid(s)
//  */
// elseif (
//     $year
//     && ($deadline = core_setting::get('reEnrollReminderDays', 'Re-enroll'))
//     && strtotime('-' . abs($deadline->getValue()) . ' days', $year->getReEnrollDeadline()) <= time()
//     && (!($reEnrollReminderSent = core_setting::get('reEnrollReminderSent', 'Re-enroll'))
//         || $reEnrollReminderSent->getValue() != $year->getID())
//     && ($emailContent = core_setting::get('reEnrollReminderEmailContent', 'Re-enroll'))
//     && ($emailSubject = core_setting::get('reEnrollReminderEmailSubject', 'Re-enroll'))
// ) {

//     if (!($reEnrollReminderSentTo = core_setting::get('reEnrollReminderSentTo-' . $year->getID(), 'Re-enroll'))) {
//         $reEnrollReminderSentTo = core_setting::init('reEnrollReminderSentTo-' . $year->getID(), 'Re-enroll', '');
//     }

//     $filter = new mth_person_filter();
//     $filter->setStatus(array(mth_student::STATUS_ACTIVE));
//     $filter->setStatusYear(array($currentYear->getID()));
//     $filter->setExcludeStatusYear(array($year->getID()));

//     $sentToArr = explode(';', $reEnrollReminderSentTo->getValue());

//     $count = 0;
//     foreach ($filter->getStudents() as $student) {
//         /* @var $student mth_student */
//         if (in_array($student->getID(), $sentToArr)) {
//             continue;
//         }
//         $email = new core_emailservice();
//         $result = $email->send(
//             [$student->getParent()->getEmail()],
//             str_replace(
//                 array(
//                     '[PARENT]',
//                     '[STUDENT]',
//                     '[SCHOOL_YEAR]',
//                     '[DEADLINE]'
//                 ),
//                 array(
//                     $student->getParent()->getPreferredFirstName(),
//                     $student->getPreferredFirstName(),
//                     $year->getName(),
//                     $year->getReEnrollDeadline('F j')
//                 ),
//                 $emailSubject->getValue()
//             ),
//             str_replace(
//                 array(
//                     '[PARENT]',
//                     '[STUDENT]',
//                     '[SCHOOL_YEAR]',
//                     '[DEADLINE]'
//                 ),
//                 array(
//                     $student->getParent()->getPreferredFirstName(),
//                     $student->getPreferredFirstName(),
//                     $year->getName(),
//                     $year->getReEnrollDeadline('F j')
//                 ),
//                 $emailContent->getValue()
//             )
//         );

//         if ($result) {
//             $sentToArr[] = $student->getID();
//         }
//         $count++;

//         if ($count >= 200 || (core_config::isDevelopment() && $count >= 10)) {
//             break;
//         }
//     }
//     $reEnrollReminderSentTo->update(implode(';', $sentToArr));
//     if ($count < 200) {
//         core_setting::set('reEnrollReminderSent', $year->getID(), core_setting::TYPE_INT, 'Re-enroll');
//     }
// }
//endif; //end of is production condition block

//refresh courses/users from canvas
if (!mth_canvas_term::getCurrentTerm()) {
    mth_canvas_term::update_mapping();
}
if (core_setting::get('LastCanvasCommand') != 'courses') {
    mth_canvas_course::update_mapping();
    core_setting::set('LastCanvasCommand', 'courses');
} else {
    mth_canvas_user::pull();
    core_setting::set('LastCanvasCommand', 'users');
}

// $filter = new mth_person_filter();
// $filter->setStatus(array(mth_student::STATUS_WITHDRAW));
// $filter->setStatusYear(array($currentYear->getID()));
// foreach ($filter->getStudents() as $student) {
//     if (($schedule = mth_schedule::get($student, $currentYear))) {
//         $schedule->delete();
//     }
// }

/**
 * Testing
 * This script will update the student who did not re-enroll into withdrawn status
 */
if (($year = mth_schoolYear::getYearReEnrollOpen())
    && date('Y-m-d', strtotime($year->getReEnrollDeadline('Y-m-d') . ' +1 day')) == date('Y-m-d')
) {
    if (null != $year->getPreviousYear()) {
        $filter = new mth_person_filter();
        $filter->setStatus(array(mth_student::STATUS_ACTIVE));
        $filter->setStatusYear(array($year->getPreviousYear()->getID()));

        $email_start = new core_emailservice();
        $email_start->send(
                        ['infocenter@mytechhigh.com', 'esublette@mytechhigh.com', 'viviend@codev.com'],
                        'Automatic Withdrawal Run Start',
                        'Hello, this email is to notify MyTech High IT department that automatic withdrawal script started running.'
                    );
        foreach ($filter->getStudents() as $student) {
            if (
                !$student->isStatus(mth_student::STATUS_ACTIVE, $year)
                && !$student->isStatus(mth_student::STATUS_PENDING, $year)
                && !$student->isStatus(mth_student::STATUS_GRADUATED, $year)
                && !$student->isStatus(mth_student::STATUS_TRANSITIONED, $year)
                && !$student->isStatus(mth_student::STATUS_WITHDRAW, $year)
                && !$student->isGraduated()
            ) {
                $student->setStatus(mth_student::STATUS_WITHDRAW, $year, $year->getReEnrollDeadline('Y-m-d'));
                $withdrawal = new mth_withdrawal;
                $withdrawal->setStudentId($student->getID());
                $withdrawal->setSchoolYearId($year->getID());
                $withdrawal->setAutomaticallyWithdrawn(true);
                $withdrawal->setActive(false);
                $withdrawal->save();
            }
        }

        $email_start = new core_emailservice();
        $email_start->send(
                        ['infocenter@mytechhigh.com', 'esublette@mytechhigh.com', 'viviend@codev.com'],
                        'Automatic Withdrawal Run End',
                        'Hello, this email is to notify MyTech High IT department that automatic withdrawal script ended gracefully.'
                    );
    }
}

$YEAR = mth_schoolYear::getCurrent();
if (strtotime(date('Y-m-d')) > strtotime($YEAR->getFirstSemLearningLogsClose('Y-m-d'))) {
    $student_assessment_ids = array();
    foreach (assessment::getByDeadline($YEAR, 1) as $key => $teachers_assessment) {
        foreach (studentassessment::getByAssessmentId($teachers_assessment->getID()) as $log) {
            if ($log->getStatus() == studentassessment::STATUS_RESET) {
                array_push($student_assessment_ids, $log->getID());
            }
        }
    }
    studentassessment::bulkUpdateStatusToSubmitted($student_assessment_ids);
}

if (strtotime(date('Y-m-d')) > strtotime($YEAR->getLogSubmissionClose('Y-m-d'))) {
    $student_assessment_ids = array();
    foreach (assessment::getByDeadline($YEAR, 2) as $key => $teachers_assessment) {
        foreach (studentassessment::getByAssessmentId($teachers_assessment->getID()) as $log) {
            if ($log->getStatus() == studentassessment::STATUS_RESET) {
                array_push($student_assessment_ids, $log->getID());
            }
        }
    }
    studentassessment::bulkUpdateStatusToSubmitted($student_assessment_ids);
}