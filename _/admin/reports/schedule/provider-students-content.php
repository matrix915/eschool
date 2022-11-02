<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');


$reportArr = [[
    'Person ID',
    'Last',
    'First',
    'Grade Level',
    'Age',
    'Birthdate',
    'Email',
    'City',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Phone'
]];
$providers = array();

$loadAll = true;
$statuses = [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST];
$persons = array();
if (req_get::bool('provider')) {
    $loadAll = false;
    foreach (req_get::int_array('provider') as $provider_id) {
        if (!($provider = mth_provider::get($provider_id))) {
            continue;
        }
        $providers[] = $provider->name();
        while ($schedule_period = mth_schedule_period::eachWithProvider($provider, $year, $statuses, false, true)) {
            $person = $schedule_period->schedule()->student();
            $persons[trim($schedule_period->getRawProviderCourse())][$person->getPersonID()] = $person;
        }
    }
}
if (req_get::bool('course')) {
    $loadAll = false;
    foreach (req_get::int_array('course') as $course_id) {
        if (!($course = mth_course::getByID($course_id))) {
            continue;
        }
        $providers[] = $course->title();
        while ($schedule_period = mth_schedule_period::eachWithCourse($course, $year, $statuses)) {
            $person = $schedule_period->schedule()->student();
            $persons[trim($schedule_period->getRawProviderCourse())][$person->getPersonID()] = $person;
        }
    }
}
if ($loadAll) {
    $filter = new mth_person_filter();
    $filter->setStatus(array(mth_student::STATUS_ACTIVE));
    $filter->setStatusYear(array($year->getID()));
    $persons = $filter->getStudents();
}else{
    $reportArr[0][] = 'Class';
}

foreach ($persons as $course => $coursePersons) {
    foreach($coursePersons as $person){
        if (!($parent = $person->getParent())) {
            continue;
        }
        $reportArr[] = array(
            $person->getID(),
            $person->getLastName(),
            $person->getPreferredFirstName(),
            $person->getGradeLevelValue($year->getID()),
            $person->getAge(),
            $person->getDateOfBirth('m/d/Y'),
            $person->getEmail(),
            $parent->getCity(),
            $parent->getPreferredLastName(),
            $parent->getPreferredFirstName(),
            $parent->getEmail(),
            $parent->getPhone(),
            $course
        );
    }
}
$file = 'Student Details for ' . ($providers ? implode(', ', $providers) : 'All Providers') . ' - ' . $year;

include ROOT . core_path::getPath('../report.php');