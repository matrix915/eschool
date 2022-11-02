<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$statuses = req_get::bool('statuses') ? req_get::int_array('statuses') : [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST];

$reportArr = array(array(
    'Person ID',
    'Provider/ Tech Course',
    'Provider Course',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Student Last',
    'Student First',
    'Student Email',
    'Street',
    'Street Line 2',
    'City',
    'State',
    'Zip',
    'Grade',
    'Schedule Date Submitted',
    'Schedule Date Accepted / Status',
    'Period',
    'Sem.',
    'Subject',
    'Course',
    'Course Type',
    'District',
    'District-School Name',
    'District-School Course',
    'District-School Phone'
));


$providerIds = req_get::int_array('provider');

$providers = array();
$reported = array();

$query = new \mth\schedule\query();
$query->setProviderIds($providerIds)
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

    while ($schedule_period = mth_schedule_period::eachByProvider($schedule, $providerIds)) {
        $course = $schedule_period->course();
        if (
            !($schedule = $schedule_period->schedule())
            || !($student = $schedule->student())
            || !($parent = $student->getParent())
        ) {
            continue;
        }

        $reportArr[] = array(
            $parent->getID(),
            $schedule_period->getRawProvider(),
            $course ? $course->title() : '',
            $parent->getPreferredLastName(),
            $parent->getPreferredFirstName(),
            $parent->getEmail(),
            $student->getLastName(),
            $student->getFirstName(),
            $student->getEmail(),
            ($parent->getAddress() ? $parent->getAddress()->getStreet() : ''),
            ($parent->getAddress() ? $parent->getAddress()->getStreet2() : ''),
            ($parent->getAddress() ? $parent->getAddress()->getCity() : ''),
            ($parent->getAddress() ? $parent->getAddress()->getState() : ''),
            ($parent->getAddress() ? $parent->getAddress()->getZip() : ''),
            $student->getGradeLevelValue($schedule->schoolYear()),
            $schedule->date_submitted('m/d/Y'),
            ($schedule->isAcceptedOnly() ? $schedule->date_accepted('m/d/Y') : $schedule->status()),
            $schedule_period->period()->num(),
            $schedule_period->second_semester() ? '2' : '1',
            $schedule_period->subjectName(),
            $schedule_period->courseName(),
            $schedule_period->course_type(),
            $schedule_period->tp_district(),
            $schedule_period->tp_name(),
            $schedule_period->tp_course(),
            $schedule_period->tp_phone()
        );
    }
}

$file = 'Schedules for ' . implode(', ', $providers) . ' - ' . $year;

include ROOT . core_path::getPath('../report.php');
