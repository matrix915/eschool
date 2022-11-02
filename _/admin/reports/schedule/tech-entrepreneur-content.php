<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');
$file = 'Canvas Course Totals - ' . $year;

$reportArr = [
     [
          'Legal Student Name',
          'Student Email',
          'Parent Name',
          'Parent Email',
          'Course Name',
          'Schedule Status',
          'Last Schedule Status Change'
     ]
];    

$query = new \mth\schedule\query();
$query->isDirect()
     ->setSchoolYearIds([$year->getID()]);

foreach($query->getAll('sch.*,sp.schedule_period_id') as $schedule){
     if (!($student = $schedule->student())
         || !($parent = $student->getParent())
         || !$student->isPendingOrActive($year)
     ) {
         continue;
     }

     if(!($period = mth_schedule_period::getByID($schedule->getSchedulePeriodId()))){
          continue;
     }
     $subject = $period->subject()->getID();

     if(!in_array($subject,req_get::int_array('subjects'))){
          continue;
     }

     $reportArr[] = [
          $student->getName(true,true),
          $student->getEmail(),
          $parent->getName(),
          $parent->getEmail(),
          $period->courseName(),
          $schedule->status(),
          $schedule->getLastModified('m/d/Y')
     ];
}

include ROOT . core_path::getPath('../report.php');