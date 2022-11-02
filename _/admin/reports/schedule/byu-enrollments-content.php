<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$byuProviderIds = req_get::int_array('provider');

$statuses = req_get::int_array('statuses');

$reportArr = array(array(
    'Last *',
    'First *',
    'Middle Initial ',
    'Net ID',
    'Phone Number',
    'Birthdate *',
    'Male *',
    'Female *',
    'Address 1',
    'Address 2',
    'City',
    'State',
    'Zip',
    'Foreign Post Code',
    'Country',
    'Student\'s Email*',
    'High School Name',
    'Course Name*',
    'Provider',
    'Parent Email',
    'Schedule Date Submitted',
    'Schedule Date Accepted / Status',
    'Semester',
    'Period',
    'Diploma Seeking',
    'Grade'
));
$reported = array();

$query = new \mth\schedule\query();
$query->setProviderIds($byuProviderIds)
    ->setStatuses($statuses)
    ->setSchoolYearIds([$year->getID()]);


foreach ($query->getAll() as $schedule) {
    if (
        !($student = $schedule->student())
        || !($parent = $student->getParent())
        || !($address = $parent->getAddress())
    ) {
        continue;
    }

    $providers = [];
    while ($period = mth_schedule_period::eachByProvider($schedule, $byuProviderIds)) {
        $course = $period->provider_course();
        $schedStatus = $schedule->status();
        $scheduleDateSubmittedCol = ($schedStatus !== 'started') ? $schedule->getSubmittedDate('m/d/Y') : '';
        $scheduleDateAcceptedStatusCol = ($schedStatus === 'Accepted')
            ? $schedule->getLastModified('m/d/Y')
            : $schedStatus;

        $reportArr[] = array(
            $student->getLastName(),
            $student->getFirstName(),
            substr($student->getMiddleName(), 0, 1),
            '',
            $parent->getPhone(),
            $student->getDateOfBirth('m/d/Y'),
            $student->getGender() === mth_student::GEN_MALE ? 'X' : '',
            $student->getGender() === mth_student::GEN_FEMALE ? 'X' : '',
            $address->getStreet(),
            $address->getStreet2(),
            $address->getCity(),
            $address->getState(),
            $address->getZip(),
            '',
            'USA',
            $student->getEmail(),
            'My Tech High',
            $course,
            $period->mth_provider(),
            $parent->getEmail(),
            $scheduleDateSubmittedCol,
            $scheduleDateAcceptedStatusCol,
            $period->second_semester() ? ($period->noChanges() ? 'No Update' : '2') : ($student->isMidYear() ? '2' : '1'),
            (int) filter_var($period->period(), FILTER_SANITIZE_NUMBER_INT),
            ($student->diplomaSeeking() ? 'Yes' : 'No'),
            $student->getGradeLevelValue()
        );
    }
}

$file = 'BYU IS enrollments - ' . $year;

include ROOT . core_path::getPath('../report.php');
