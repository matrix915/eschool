<?php

/** @var $year mth_schoolYear */ ($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$course_id = req_get::int('course_id');
$course = req_get::txt('course');
$_popup_id = 'enrolledPopup';
$statuses = [
    mth_schedule::STATUS_SUBMITTED,
    mth_schedule::STATUS_ACCEPTED,
    mth_schedule::STATUS_CHANGE,
    mth_schedule::STATUS_RESUBMITTED,
    mth_schedule::STATUS_CHANGE_POST
];

$student_ids = mth_schedule::getEnrolledStudents($year->getID(), $course_id, $statuses);


$file = "Enrollment for $course - $year";
$reportArr = array(array(
    'Student First Name',
    'Student Last Name',
    'Student Email',
    'Grade',
    'Parent Email',
    'Address',
    'City',
    'State',
    'Zip',
    'Schedule Date Submitted',
    'Schedule Date Accepted / Status'
));

foreach (mth_student::getStudents(['StudentID' => $student_ids]) as $student) {
    $student_schedule = mth_schedule::get($student, $year);
    if (!($parent = $student->getParent())) {
        continue;
    }
    if (!($address = $student->getAddress())) {
        continue;
    }
    $reportArr[] = array(
        $student->getFirstName(),
        $student->getLastName(),
        $student->getEmail(),
        $student->getGradeLevelValue($year->getID()),
        $parent->getEmail(),
        $address->getStreet() . ($address->getStreet2() ? PHP_EOL . $address->getStreet2() : ''),
        $address->getCity(),
        $address->getState(),
        $address->getZip(),
        $student_schedule->date_submitted('m/d/Y'),
        ($student_schedule->isAcceptedOnly() ? $student_schedule->date_accepted('m/d/Y') : $student_schedule->status())
    );
}

/** @noinspection PhpIncludeInspection */
include ROOT . core_path::getPath('../report.php');
