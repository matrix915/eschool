<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');


$students = (req_get::txt('type') == 'Students');
$both = (req_get::txt('type') == 'Both');

//parent headers by default
$headers = [
    'Person ID',
    'Last',
    'First',
    'Grade Level',
    'Mid-year',
    'Parent Email',
    'Username',
    'Schedule Date Submitted',
    'Schedule Date Accepted / Status'
];

if ($students) {
    $headers = [
        'Person ID',
        'Last',
        'First',
        'Grade Level',
        'Mid-year',
        'Student Email',
        'Parent Email',
        'Username',
        'Schedule Date Submitted',
        'Schedule Date Accepted / Status'
    ];
} elseif ($both) {
    $headers = [
        'Person ID',
        'Last',
        'First',
        'Grade Level',
        'Mid-year',
        'Email',
        'Username',
        'Schedule Date Submitted',
        'Schedule Date Accepted / Status'
    ];
}

$statuses = [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST];
/** @var mth_parent[][]|mth_student[][] $persons */
$persons = [];
$loadAll = true;
$parent_emails = [];
$student_schedules = [];

if (req_get::bool('provider')) {
    $loadAll = false;
    foreach (req_get::int_array('provider') as $provider_id) {
        if (!($provider = mth_provider::get($provider_id))) {
            continue;
        }
        while ($schedule_period = mth_schedule_period::eachWithProvider($provider, $year, $statuses, false, true)) {
            $person = $schedule_period->schedule()->student();
            if ($students) {
                $parent = $person->getParent();
                $parent_emails[$parent->getID()] = $parent->getEmail();
            } elseif ($both) {
                $person_parent = $person->getParent();
                $persons[$schedule_period->courseName()][$person_parent->getPersonID()] = $person_parent;
            } else {
                $person = $person->getParent();
            }
            $student_schedules[$person->getID()] = $schedule_period->schedule();
            $persons[$schedule_period->courseName() . '||' . $schedule_period->getRawProviderCourse()][$person->getPersonID()] = $person;
        }
    }
}
if (req_get::bool('course')) {
    $loadAll = false;
    foreach (req_get::int_array('course') as $course_id) {
        if (!($course = mth_course::getByID($course_id))) {
            continue;
        }
        while ($schedule_period = mth_schedule_period::eachWithCourse($course, $year, $statuses)) {
            $person = $schedule_period->schedule()->student();
            if ($students) {
                $parent = $person->getParent();
                $parent_emails[$parent->getID()] = $parent->getEmail();
            } elseif ($both) {
                $person_parent = $person->getParent();
                $persons[$schedule_period->courseName()][$person_parent->getPersonID()] = $person_parent;
            } else {
                $person = $person->getParent();
            }
            $student_schedules[$person->getID()] = $schedule_period->schedule();
            $persons[$schedule_period->courseName() . '||' . $schedule_period->getRawProviderCourse()][$person->getPersonID()] = $person;
        }
    }
}
if ($loadAll) {
    $filter = new mth_person_filter();
    $filter->setStatus(array(mth_student::STATUS_ACTIVE));
    $filter->setStatusYear(array($year->getID()));
    /** @var mth_parent[]|mth_student[] $persons */
    if ($students) {
        $persons = $filter->getStudents();
        $studentIds = [];
        foreach ($persons as $student) {
            if (mth_student::isStudent($student->getPersonID())) {
                $studentIds[] = $student->getID();
            }
        }
        $schedulesBatch = mth_schedule::getSchedulesByStudentIDs($studentIds, $year);
        $student_schedules = [];
        foreach($schedulesBatch as $schedule) {
            $student_schedules[$schedule->student_id()] = $schedule;
        }

        $parents = mth_parent::getParentsByStudentIds($studentIds);
        foreach ($parents as $parent) {
            $parent_emails[$parent->getID()] = $parent->getEmail();
        }

    } elseif ($both) {
        $persons = $filter->getAll();
    } else {
        $persons = $filter->getParents();
    }
} else {
    $headers[] = 'Course';
    $headers[] = 'Class';
}

$reportArr = [$headers];

/**
 * @param mth_person|mth_student|mth_parent $person
 * @param null|string $parentEmail
 * @param null|string $courseName
 */
$addRow = function (mth_person $person, $parentEmail = null, $student_schedules = null, $courseName = null, $providerCourse = null) use (&$reportArr, $year, $students) {
    switch (req_get::int('format')) {
        default:
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
    $username = preg_replace('/&#?[a-z0-9]{2,8};|[^a-zA-Z0-9]/', '', $username);
    if ($students) {
        $row = [
            $person->getPersonID(),
            $person->getLastName(),
            $person->getPreferredFirstName(),
            ($person->getType() == 'student' ? $person->getGradeLevelValue($year->getID()) : ''),
            ($person->getType() == 'student' ? ($person->isMidYear() ? 'Yes' : 'No') : ''),
            $person->getEmail(),
            $parentEmail,
            $username,
            (null != $student_schedules ? $student_schedules->date_submitted('m/d/Y') : ''),
            (null != $student_schedules ? ($student_schedules->isAcceptedOnly() ? $student_schedules->date_accepted('m/d/Y') : $student_schedules->status()) : ''),
        ];
    } else {
        $row = [
            $person->getPersonID(),
            $person->getLastName(),
            $person->getPreferredFirstName(),
            ($person->getType() == 'student' ? $person->getGradeLevelValue($year->getID()) : ''),
            ($person->getType() == 'student' ? ($person->isMidYear() ? 'Yes' : 'No') : ''),
            $person->getEmail(),
            $username,
            (null != $student_schedules ? $student_schedules->date_submitted('m/d/Y') : ''),
            (null != $student_schedules ? ($student_schedules->isAcceptedOnly() ? $student_schedules->date_accepted('m/d/Y') : $student_schedules->status()) : '')
        ];
    }
    if ($courseName) {
        $row[] = $courseName;
        $row[] = $providerCourse;
    }

    $reportArr[] = $row;
};
if ($loadAll) {
    foreach ($persons as $person) {
        $schedule = (isset($student_schedules[$person->getID()])) ? $student_schedules[$person->getID()] : null;
        $parent = mth_student::isStudent($person->getPersonID()) && (isset($parent_emails[$person->getParentID()]))
           ? $parent_emails[$person->getParentID()]
           : null;
        $addRow($person, ($students ? $parent : null), $schedule);
    }
} else {
    foreach ($persons as $courseName => $coursePersons) {
        foreach ($coursePersons as $person) {
            $course = explode('||', $courseName);
            $schedule = (isset($student_schedules[$person->getID()])) ? $student_schedules[$person->getID()] : null;
            $addRow($person, ($students ? $parent_emails[$person->getParentID()] : null), $schedule, $course[0], $course[1]);
        }
    }
}

$file = 'Usernames';

include ROOT . core_path::getPath('../report.php');
