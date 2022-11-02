<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$statuses = array(mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST);

$students = (req_get::txt('type') == 'Students');
$both = (req_get::txt('type') == 'Both');

function getUsername($user, $year)
{
    switch (req_get::int('format')) {
        default:
        case 1:
            $username = strtolower($user->getLastName() . $user->getPreferredFirstName()) . $year->getStartYear();
            break;
        case 2:
            $username = strtolower($user->getLastName() . substr($user->getPreferredFirstName(), 0, 3)) . $year->getStartYear();
            break;
        case 3:
            $username = strtolower($user->getLastName() . $user->getPreferredFirstName());
            break;
        case 4:
            $username = strtolower($user->getLastName() . substr($user->getPreferredFirstName(), 0, 1));
            break;
        case 5:
            $username = strtolower($user->getLastName() . $user->getPreferredFirstName()) . 'mth';
            break;
    }
    return $username;
}
function schedulePeriodRow($both, $students, $year, $name, mth_schedule_period $schedule_period, mth_provider_course $course = NULL)
{
    if (
        !($schedule = $schedule_period->schedule())
        || !($student = $schedule->student())
        || !($parent = $student->getParent())
    ) {
        return false;
    }
    $user = $students || $both ? $student : $parent;

    $username = getUsername($user, $year);

    $row = array(
        $user->getID(),
        $name,
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
        $schedule_period->tp_phone(),
        $username
    );

    if ($both) {
        $row[] = getUsername($parent, $year);
    }

    return $row;
}
$headers = [
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
];

if ($both) {
    $headers = array_merge($headers, ['Student Username', 'Parent Username']);
} else {
    $headers[] = 'Username';
}

$reportArr = [$headers];


$providers = array();
while ($provider = mth_provider::each()) {
    if (req_get::bool('provider') && !in_array($provider->id(), req_get::int_array('provider'))) {
        continue;
    }
    $providers[] = $provider->name();
    if (mth_provider_course::count($provider) > 0) {
        while ($course = mth_provider_course::each($provider)) {
            while ($schedule_period = mth_schedule_period::eachWithProviderCourse($course, $year, $statuses, false, true)) {
                if (($row = schedulePeriodRow($both, $students, $year, $provider->name(), $schedule_period, $course))) {
                    $reportArr[] = $row;
                }
            }
        }
    } else {
        while ($schedule_period = mth_schedule_period::eachWithProvider($provider, $year, $statuses)) {
            if (($row = schedulePeriodRow($both, $students, $year, $provider->name(), $schedule_period))) {
                $reportArr[] = $row;
            }
        }
    }
}
$file = 'Schedules for ' . implode(', ', $providers) . ' - ' . $year;

include ROOT . core_path::getPath('../report.php');
