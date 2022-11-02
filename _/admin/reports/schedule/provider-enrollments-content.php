<?php
($year = $_SESSION['mth_reports_school_year']) || die('No current year');

$provider_ids = req_get::int_array('provider');
$statuses = array(mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST);

$reportArr = array(array(
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
    'Phone',
    'Username',
    'Provider',
    'Class(es)'
));

$username = '';

function format_username($format, $person, $year)
{
    $username = '';
    switch ($format) {
        case 1:
            $username = strtolower($person->getLastName() . $person->getPreferredFirstName()) . $year->getStartYear();
            break;
        case 2:
            $username = strtolower($person->getLastName() . substr($person->getPreferredFirstName(), 0, 3)) . $year->getStartYear();
            break;
        case 3:
            $username = strtolower($person->getLastName() . $person->getPreferredFirstName());
            break;
        case 4:
            $username = strtolower($person->getLastName() . substr($person->getPreferredFirstName(), 0, 1));
            break;
        case 5:
            $username = strtolower($person->getLastName() . $person->getPreferredFirstName()) . 'mth';
            break;
    }
    return $username;
}

$query = new \mth\schedule\query();
$query->setProviderIds($provider_ids)
    ->setStatuses($statuses)
    ->setSchoolYearIds([$year->getID()]);

foreach ($query->getAll() as $schedule) {
    if (!($student = $schedule->student())
        || !($parent = $student->getParent())
    ) {
        continue;
    }

    $periods = [];
    $providers = [];
    while ($period = mth_period::each()) {
        if (($sched_p = mth_schedule_period::get($schedule, $period)) &&
            $sched_p->getRawProvider() !== null &&
            in_array($sched_p->getRawProvider(true), $provider_ids)) {
            $periods[] = $sched_p->courseName();
            if (!in_array($sched_p->getRawProvider(), $providers)) {
                $providers[] = $sched_p->getRawProvider();
            }
        }
    }

    $reportArr[] = [
        $student->getPersonID(),
        $student->getLastName(),
        $student->getPreferredFirstName(),
        $student->getGradeLevelValue($year->getID()),
        $student->getAge(),
        $student->getDateOfBirth('m/d/Y'),
        $student->getEmail(),
        $parent->getCity(),
        $parent->getLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone(),
        format_username(req_get::int('format'), $student, $year),
        implode(' / ', $providers),
        implode(' / ', $periods)
    ];
}

$file = 'Student Details and Usernames by Provider';

include ROOT . core_path::getPath('../report.php');