<?php
use mth\yoda\courses;
use mth\yoda\studentassessment;
use mth\yoda\assessment;
use mth\yoda\homeroom\Query;

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Year-to-date - ' . $year;



$endOfDay  = date("Y-m-d 23:59:59");

$log_title = assessment::getLogNamesByDeadline($year->getID(), $endOfDay);

$reportArr = [
     array_merge(
          [
               'Student',
               'Grade'
          ],
          $log_title ? $log_title : [],
          [
               'Current Score',
               'Current Grade',
               'Homeroom Teacher'
          ]
     )
];


$query = new Query();
$query->setYear([$year->getID()]);
$query->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING], $year->getID());
if ($enrollments = $query->getAll()) {
     foreach ($enrollments as $enrollment) {
          $stgrade = $enrollment->getGrade();

          if (!$student = $enrollment->student()) {
               continue;
          }

          if (!($teacher = $enrollment->getTeacher())) {
               core_notify::addError('Teacher Missing for ' . $student);
          }

          $record = [
               $student->getName(true, true),
               $student->getGradeLevelValue($year->getID()),
          ];

          $learning_logs = [];
          $LL = [];

          if ($assessments = assessment::getByCourseDeadline($enrollment->getCourseId(), $endOfDay)) {
               foreach ($assessments as $assessment) {
                    $title = $assessment->getTitle();
                    $LL[$title] = 'N/A';
                    if ($log = studentassessment::get($assessment->getID(), $student->getPersonID())) {
                         if ($log->isExcused()) {
                              $LL[$title] = 'EX';
                         } elseif ($log->getGrade() ==  null) {
                              $LL[$title] = $log->isNA() ? 'N/A' : 'Undgraded';
                         } else {
                              $LL[$title] = $log->getGrade();
                         }
                    }
               }
          }

          if($log_title){
               foreach($log_title as $_title){
                    $learning_logs[] = isset($LL[$_title])?$LL[$_title]:'';
               }
          }

          $result = [
               is_null($stgrade) ? 'NA' : $stgrade . '%',
               ($stgrade && $stgrade >= assessment::PASSED_GRADE ? 'Pass' : 'Fail'),
               $teacher->getName()
          ];

          $reportArr[] = array_merge($record, $learning_logs, $result);
     }
}

include ROOT . core_path::getPath('../report.php');
