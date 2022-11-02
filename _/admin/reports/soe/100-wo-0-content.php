<?php

($year = $_SESSION['mth_reports_school_year']) || die('No current year');
$SOEs = [];
foreach(req_get::int_array('soe') as $schoolOfEnrollmentId){

    $SOEs[$schoolOfEnrollmentId] = \mth\student\SchoolOfEnrollment::get($schoolOfEnrollmentId);
}
array_filter($SOEs);
$soes = array_keys($SOEs);

$file = 'Any Homeroom Grade w/o late and w/o zeros of '.implode(', ',$SOEs);

$reportArr = array(array(
    'Student First Name',
    'Student Last Name',
    'Grade',
    'Homeroom Grade',
    'Zero Count',
    'Late',
    'Parent Name',
    'Parent Email',
    'Parent Phone',
    $year.' SoE'
));

$notCached = 0;
$students = [];

while($enrollment = mth_canvas_enrollment::eachHomeRoomEnrollment($year)){
   
    if (!$enrollment->gradeCached()) {
        $notCached++;
        continue;
    }

    if($enrollment->getLateCount()>0){
        continue;
    }

    if( $enrollment->getZeroCount()>0){
        continue;
    }

    if (!($user = $enrollment->canvas_user())) {
        continue;
    }

    $student = $user->person();
    if (!$student || $student->getType()=='parent') {
        continue;
    }

    if($student->getStatus($year) !== mth_student::STATUS_ACTIVE || !$enrollment->isActive()){
        continue;
    }
    
    if(!in_array($student->getSchoolOfEnrollment(true,$year),$soes)){
        continue;
    }

    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        continue;
    }

    if(in_array($enrollment->canvas_user_id(),$students)){
        continue;
    }
    $students[] = $enrollment->canvas_user_id();

    $reportArr[] = array(
        $student->getFirstName(),
        $student->getLastName(),
        $student->getGradeLevelValue($year->getID()),
        $enrollment->getGrade() . '%',
        $enrollment->getZeroCount(),
        $enrollment->getLateCount(),
        $parent->getName(true),
        $parent->getEmail(),
        $parent->getPhone(),
        $student->getSOEname($year)
    );
}


if ($notCached) {
    core_notify::addError('Grades not cached for ' . $notCached . ' Homeroom enrollments');
}

include ROOT . core_path::getPath('../report.php');