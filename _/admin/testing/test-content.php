<?php
// $student = mth_student::getByStudentID(15558);//3683 //

// $optOut = mth_testOptOut::getByStudent($student, mth_schoolYear::getPrevious());


// header('Content-type: application/pdf');
// echo mth_views_testOptOut::get2020PDFcontent($optOut, $student);
// exit();
// $scount = 0;
// echo 'NOW:'.date('Y-m-d').'<br>';

if (($year = mth_schoolYear::get2ndSemOpenReg()) 
    && ($deadline = core_setting::get('2ndSemUpdatesRequiredReminder', 'Schedules')) 
    && date('Y-m-d',strtotime('-' . abs($deadline->getValue()) . ' days', $year->getSecondSemClose()))  ==  date('Y-m-d')
    && ($emailContent = core_setting::get('2ndSemUpdatesRequiredReminderEmail', 'Schedules'))
    && ($emailSubject = core_setting::get('2ndSemUpdatesRequiredReminderSubject', 'Schedules'))
) {
    while ($schedule = mth_schedule::eachOfYear($year)) {
        if ($schedule->second_sem_change_available() 
        && $schedule->isToChange() 
        && ($student = $schedule->student()) 
        && ($parent = $student->getParent())) {
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
               echo $student.'<br>';
            $scount++;
        }
        if ($scount >= 10 && !core_config::isProduction()) {
            break;
        }
    }
}