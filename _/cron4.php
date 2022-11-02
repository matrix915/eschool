<?php
require_once 'app/inc.php';

define('DATE_FILE', core_config::getSitePath() . '/_/cron4_last_run');

if (!is_file(DATE_FILE)) {
    file_put_contents(DATE_FILE, '0');
}

$today = date('Ymd');
$lastRun = date('Ymd', file_get_contents(DATE_FILE));
$year = isset($argv[1])?$argv[1]:0;
$debug = isset($argv[2]) && $argv[2]?$argv[2]:false;

($debug || $today != $lastRun) || die('Cron Already Running..');

file_put_contents(DATE_FILE, time());



$currentYear = $year==0?mth_schoolYear::getCurrent():mth_schoolYear::getByStartYear($year);

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE]);
$filter->setStatusYear(array($currentYear->getID()));
$students = $filter->getStudents();
if($debug){
    print "Found ".count($students)." students of $currentYear\r\n";
}


foreach ($students as $key=>$student) {

    if (($schedule = mth_schedule::get($student, $currentYear)) 
    	&& ($enrollment = mth_canvas_enrollment::getBySchedulePeriod($schedule->getPeriod(1)))
    	&& $enrollment->id()
    ) {
        if($debug){
            print "$key Getting student:".$student->getID()."\r\n";
        }
      
      $zero = $enrollment->zeroCount();
      $grade = $enrollment->grade();
    }
    else{
        if($debug){
            print "$key NO ENROLLMENT student:".$student->getID()."\r\n";
        }
    }
}