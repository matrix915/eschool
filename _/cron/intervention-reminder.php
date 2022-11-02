<?php
require_once realpath(dirname(__FILE__) . '/../..').'/_/app/inc.php';

define('DATE_FILE', core_config::getSitePath() . '/_/cron/intervention_reminder');

if (!is_file(DATE_FILE)) {
    file_put_contents(DATE_FILE, '0');
}

$today = date('Ymd');
$lastRun = date('Ymd', file_get_contents(DATE_FILE));
$debug = isset($argv[1]) && $argv[1]?$argv[1]:false;

($debug || $today != $lastRun) || die('Cron Already Running..');

file_put_contents(DATE_FILE, time());

while($result = mth_intervention::each('resolve!=1')){
    if(!($student = $result->getStudent())){
        if($debug){
            print "- Mp Student Found \r\n";
        }
        
        continue;
    }

    if($debug){print "#### {$student->getID()} :: {$student->getName()}\r\n";}
    if(!($schedule = mth_schedule::get($student))){
        if($debug){print "- No Schedule Found \r\n";}
       continue;
    }

    if (!($enrollment = mth_canvas_enrollment::getBySchedulePeriod($schedule->getPeriod(1)))) {
        if($debug){print "- No Enrollment Found \r\n";}
        $result->resolve(1);
        $result->save();
        continue;
    }

    if($enrollment->getGrade() >= 80){
        if($debug){
            print "- Grade is now atleast 80%  {$enrollment->getGrade()}\r\n";
        }
        $result->resolve(1);
        $result->save();
        continue;
    }

    if($label = $result->getLabel()){
        print "- has a label  \r\n";
        $result->resolve(1);
        $result->save();
        continue;
    }

    if(!($notif = mth_offensenotif::getLatestNotif($result))){
        if($debug){print "- No Notification Sent  \r\n";}
        $result->resolve(1);
        $result->save();
        continue;
    }

    if(!$notif->isPastDue()){
        if($debug){print "- NOT Past DUE  {$notif->getCreatedDate('M j Y')}\r\n";}
        continue;
    }

    $notif->send($student,true);
    if($debug){
        print "===================================\r\n";
        print "send notif sent from:{$notif->getCreatedDate('M j Y')} \r\n";
        print "===================================\r\n";
    }

}
