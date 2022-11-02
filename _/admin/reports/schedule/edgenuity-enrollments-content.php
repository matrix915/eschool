<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$engProviderIds = req_get::int_array('provider');


$statuses = req_get::int_array('statuses');

$reportArr = array(array(
    'Username',
    'Password',
    'Student First Name',
    'Student Middle Name',
    'Student Last Name',
    'Preferred Name',
    'DOB',
    'Gender',
    'Student Email',
    'Parent Phone',
    'School',
    'District',
    'School Mentor First Name',
    'School Mentor Last Name',
    'School Mentor Email',
    'School Mentor Phone',
    'Guardian Relationship',
    'Guardian Last Name',
    'Guardian First Name',
    'Guardian Email',
    'Guardian Phone',
    'Course 1 Needed',
    'Course 1 Start Date',
    'Course 1 End Date',
    'Course 1 Status',
    'Course 2 Needed',
    'Course 2 Start Date',
    'Course 2 End Date',
    'Course 2 Status',
    'Course 3 Needed',
    'Course 3 Start Date',
    'Course 3 End Date ',
    'Course 3 Status',
    'Course 4 Needed',
    'Course 4 Start Date',
    'Course 4 End Date',
    'Course 4 Status',
    'Course 5 Needed',
    'Course 5 Start Date',
    'Course 5 End Date',
    'Course 5 Status',
    'Course 6 Needed',
    'Course 6 Start Date',
    'Course 6 End Date',
    'Course 6 Status',
));
$reported = array();
$enrollments = [];
$student_courses = [];
foreach ($engProviderIds as $provider_id) {
    if (!($provider = mth_provider::get($provider_id))) {
        error_log('Missing BYU provider in byu-enrollments report');
        continue;
    }

    while ($course = mth_provider_course::each($provider)) {
        while ($schedule_period = mth_schedule_period::eachWithProviderCourse($course, $year, $statuses)) {
            if (!($schedule = $schedule_period->schedule())
                || !($student = $schedule->student())
                || !($parent = $student->getParent())
                || !($address = $parent->getAddress())
                || isset($reported[$course->id()][$schedule->id()])
            ) {
                continue;
            }
            /* @var $schedule mth_schedule */
            /* @var $student mth_student */
            /* @var $parent mth_parent */
            $enrollments[$student->getID()] = array(
                '',
                '',
                $student->getFirstName(),
                $student->getMiddleName(),
                $student->getLastName(),
                $student->getName(false,true),
                $student->getDateOfBirth('m/d/Y'),
                $student->getGender(),
                $student->getEmail(),
                $parent->getPhone(),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $parent->getLastName(),
                $parent->getFirstName(),
                $parent->getEmail(),
                $parent->getPhone(),
            );

            $student_courses[$student->getID()][] = [
                $course->title(),
                '',
                '',
                '',
            ];

            $reported[$course->id()][$schedule->id()] = true;
        }
    }
}
$empty_course = ['','','',''];
foreach($enrollments as $student_id=>$row){
    $courses = [];
    foreach($student_courses[$student_id] as $course){
        $courses = array_merge($courses,$course);
    }
    $student_row =  array_merge($row,$courses);

    $course_count = count($student_courses[$student_id]);
    if($course_count < 6){
        for($x = 0;$x<(6-$course_count);$x++){
            $student_row = array_merge($student_row,$empty_course);
        }
    }
    $reportArr[] = $student_row;
}

$file = 'Edgenuity enrollments - ' . $year;

include ROOT . core_path::getPath('../report.php');