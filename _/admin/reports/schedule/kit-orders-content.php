<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$statuses = req_get::bool('statuses') ? req_get::int_array('statuses') : [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST];

$reportArr = array(array(
    'Student First Legal Name',
    'Student Last Legal Name',
    'Student Email',
    'Grade',
    'Parent Email',
    'Address',
    'City',
    'State',
    'Zip',
    'Schedule Date Submitted',
    'Schedule Date Accepted / Status',
    'Provider/ Tech Course',
//    'Person ID',
//    'Provider Course',
//    'Parent Last',
//    'Parent First',
//    'Period',
//    'Sem.',
//    'Subject',
//    'Course',
//    'Course Type',
//    'District',
//    'District-School Name',
//    'District-School Course',
//    'District-School Phone',
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

        $address = $parent->getAddress();

        $reportArr[] = array(
            $student->getFirstName(),
            $student->getLastName(),
            $student->getEmail(),
            $student->getGradeLevelValue($schedule->schoolYear()),
            $parent->getEmail(),
            ($address ? $address->getStreet() . ($address->getStreet2() ?  " " . $address->getStreet2() : '') : ''),
            ($address ? $address->getCity() : ''),
            ($address ? $address->getState() : ''),
            ($address ? $address->getZip() : ''),
            $schedule->date_submitted('m/d/Y'),
            ($schedule->isAcceptedOnly() ? $schedule->date_accepted('m/d/Y') : $schedule->status()),
            $schedule_period->mth_provider(),
//            $parent->getID(),
//            $course ? $course->title() : '',
//            $parent->getPreferredLastName(),
//            $parent->getPreferredFirstName(),
//            $schedule_period->period()->num(),
//            $schedule_period->second_semester() ? '2' : '1',
//            $schedule_period->subjectName(),
//            $schedule_period->courseName(),
//            $schedule_period->course_type(),
//            $schedule_period->tp_district(),
//            $schedule_period->tp_name(),
//            $schedule_period->tp_course(),
//            $schedule_period->tp_phone(),
        );
    }
}

$file = 'Kit Orders for ' . implode(', ', $providers) . ' - ' . $year;

include ROOT . core_path::getPath('../report.php');
