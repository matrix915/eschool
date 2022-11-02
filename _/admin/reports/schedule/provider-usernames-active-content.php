<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');
$columnSort = [[]];

$getStudents = (req_get::txt('type') == 'Students' || req_get::txt('type') == 'Midyear Students');
$midYear = (req_get::txt('type') == 'Midyear Students');
$getBoth = (req_get::txt('type') == 'Both');
$formatId = req_get::int('format');

//parent headers by default
$headers = [
    'Person ID',
    'Date Packet was Accepted',
    'Last',
    'First',
    'Grade Level',
    'Mid-year',
    'Parent Email',
    'Username',
    $year . ' Student Status',
    'Withdrawn /Graduated Date if applicable',
    'Schedule Date Submitted',
    'Schedule Date Accepted / Status',
    'Birthdate'
];

if($getStudents)
{
    $headers = [
        'Person ID',
        'Date Packet was Accepted',
        'Last',
        'First',
        'Grade Level',
        'Mid-year',
        'Student Email',
        'Parent Email',
        'Parent First',
        'Parent Last',
        'Username',
        $year . ' Student Status',
        'Withdrawn /Graduated Date if applicable',
        'Schedule Date Submitted',
        'Schedule Date Accepted / Status',
        'Birthdate'
    ];
} elseif($getBoth)
{
    $headers = [
        'Person ID',
        'Date Packet was Accepted',
        'Last',
        'First',
        'Grade Level',
        'Mid-year',
        'Email',
        'Username',
        $year . ' Student Status',
        'Withdrawn /Graduated Date if applicable',
        'Schedule Date Submitted',
        'Schedule Date Accepted / Status',
        'Birthdate'
    ];
}

/** @var mth_parent[][]|mth_student[][] $persons */
$persons = [];
$loadAll = true;
$parent_data = [];
$student_schedules = [];
$student_applications = [];
$student_packets = [];

if(req_get::bool('provider'))
{
    $loadAll = false;
    foreach(req_get::int_array('provider') as $provider_id)
    {
        if(!($provider = mth_provider::get($provider_id)))
        {
            continue;
        }
        while($schedule_period = mth_schedule_period::eachWithProviderStudent($provider, $year, NULL, NULL, $midYear, true))
        {
            $person = $schedule_period->schedule()->student();
            if(is_null($person))
            {
                continue;
            }
            if ($getStudents) {
                $parent = $person->getParent();
                if($parent)
                {
                    $parent_data[$parent->getID()] = [
                        'email' => $parent->getEmail(),
                        'first_name' => $parent->getPreferredFirstName(),
                        'last_name' => $parent->getLastName(),
                    ];
                }
            } elseif ($getBoth) {
                if(($person_parent = $person->getParent()))
                {
                    $persons[$schedule_period->courseName()][$person_parent->getPersonID()] = $person_parent;
                }
            } else {
                $person = $person->getParent();
            }
            if(empty($person))
            {
                continue;
            }
            $student_schedules[$person->getID()] = $schedule_period->schedule();
            $persons[$schedule_period->courseName() . '||' . $schedule_period->getRawProviderCourse()][$person->getPersonID()] = $person;
        }
    }
}

if(req_get::bool('course'))
{
    $loadAll = false;
    foreach(req_get::int_array('course') as $course_id)
    {
        if(!($course = mth_course::getByID($course_id)))
        {
            continue;
        }
        while($schedule_period = mth_schedule_period::eachWithCourseStudent($course, $year, NULL, FALSE, $midYear))
        {
            $person = $schedule_period->schedule()->student();
            if(empty($person))
            {
                continue;
            }
            if($getStudents)
            {
                $parent = $person->getParent();
                if($parent)
                {
                    $parent_data[$parent->getID()] = [
                        'email' => $parent->getEmail(),
                        'first_name' => $parent->getPreferredFirstName(),
                        'last_name' => $parent->getLastName(),
                    ];
                }
            } elseif($getBoth)
            {
                if(($person_parent = $person->getParent()))
                {
                    $persons[$schedule_period->courseName()][$person_parent->getPersonID()] = $person_parent;
                }
            } else
            {
                $person = $person->getParent();
            }
            if(empty($person))
            {
                continue;
            }
            $student_schedules[$person->getID()] = $schedule_period->schedule();
            $persons[$schedule_period->courseName() . '||' . $schedule_period->getRawProviderCourse()][$person->getPersonID()] = $person;
        }
    }
}

if($loadAll)
{
    $filter = new mth_person_filter();
    $filter->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
    $filter->setStatusYear(array($year->getID()));
    /** @var mth_parent[]|mth_student[] $persons */

    if($getStudents)
    {
        $filter->setMidYear($midYear);
        $studentIds = $filter->getStudentIDs();
        $persons = mth_student::getStudents(array('StudentID' => $studentIds));
        if(!empty($studentIds))
        {
            foreach(mth_parent::getParentsByStudentIds($studentIds) as $parent)
            {
                /** @var mth_parent $parent */
                $parent_data[$parent->getID()] = [
                    'email' => $parent->getEmail(),
                    'first_name' => $parent->getPreferredFirstName(),
                    'last_name' => $parent->getLastName(),
                ];
            }

            foreach(mth_schedule::getSchedulesByStudentIDs($studentIds, $year) as $schedule)
            {
                $student_schedules[$schedule->student_id()] = $schedule;
            }

            if(!$midYear)
            {
                $appFilter = [
                    'StudentID' => $studentIds
                ];
                foreach(mth_application::getApplications($appFilter) as $application)
                {
                    /** @var mth_application $application */
                    if(!isset($student_applications[$application->getStudentID()]))
                    {
                        $student_applications[$application->getStudentID()] = $application;
                    }
                }
            }

            foreach(mth_packet::getStudentsPackets($studentIds, true) as $packet)
            {
                /** @var mth_packet $packet */
                if(!isset($student_packets[$packet->getStudentID()]))
                {
                    $student_packets[$packet->getStudentID()] = $packet;
                }
            }
        }

    } elseif($getBoth)
    {
        $persons = $filter->getAll();
        $studentIds = $filter->getStudentIDs();
        foreach(mth_schedule::getSchedulesByStudentIDs($studentIds, $year) as $schedule)
        {
            $student_schedules[$schedule->student_id()] = $schedule;
        }

        $appFilter = ['StudentID' => $studentIds];
        foreach(mth_application::getApplications($appFilter) as $application)
        {
            /** @var mth_application $application */
            if(!isset($student_applications[$application->getStudentID()]))
            {
                $student_applications[$application->getStudentID()] = $application;
            }
        }

        foreach(mth_packet::getStudentsPackets($studentIds, true) as $packet)
        {
            /** @var mth_packet $packet */
            if(!isset($student_packets[$packet->getStudentID()]))
            {
                $student_packets[$packet->getStudentID()] = $packet;
            }
        }
    } else
    {
        $persons = $filter->getParents();
    }
} else
{
    $headers[] = 'Course';
    $headers[] = 'Class';
}

$reportArr = [$headers];

/**
 * @param mth_person|mth_student|mth_parent $person
 * @param null|array $parentData
 * @param mth_schedule $student_schedule
 * @param mth_application $student_application
 * @param null|string $courseName
 */
$addRow = function(mth_person $person, $parentData = null, $student_schedule = null, $courseName = null, $providerCourse = null, $student_application = null, $student_packet = null)
use (&$reportArr, $year, $getStudents, &$columnSort, $formatId, $midYear)
{
    switch($formatId)
    {
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
        case 6:
            $username = strtolower($person->getLastName() . $person->getPreferredFirstName()) . $person->getDateOfBirth('Y');
            break;
    }
    $username = preg_replace('/&#?[a-z0-9]{2,8};|[^a-zA-Z0-9]/', '', $username);
    $date_requested = null;
    $status_label = null;
    $status_date = null;
    $_stat_date = null;
    $_date_requested = null;
    $student_birth_date = null;
    $isStudent = $person->getType() == 'student';

    if($isStudent)
    {
        if(!is_null($student_packet) || $student_packet = mth_packet::getStudentPacket($person))
        {
            $date_requested = $student_packet->getDateAccepted('m/d/Y');
            $_date_requested = $student_packet->getDateAccepted();
        }

        $status_label = $person->getStatusLabel($year);
        $status_date = in_array(
            $person->getStatus($year),
            [
                mth_student::STATUS_WITHDRAW,
                mth_student::STATUS_GRADUATED
            ]
        ) ? $person->getStatusDate($year, 'm/d/Y') : '';
        $_stat_date = $person->getStatusDate();
        $student_birth_date = $person->getDateOfBirth('m/d/Y');

        if(!$midYear)
        {
            $midYearStudent = !empty($student_application)
                ? $student_application->getSchoolYearID() == $year->getID() && $student_application->getMidyearApplication()
                : $person->isMidYear();
        } else
        {
            $midYearStudent = $midYear;
        }
    }

    if($getStudents)
    {
        $row = [
            $person->getPersonID(),
            $date_requested,
            $person->getLastName(),
            $person->getPreferredFirstName(),
            ($isStudent ? $person->getGradeLevelValue($year->getID()) : ''),
            ($isStudent ? ($midYearStudent ? 'Yes' : 'No') : ''),
            $person->getEmail(),
            !empty($parentData) && !empty($parentData['email']) ? $parentData['email'] : '',
            !empty($parentData) && !empty($parentData['first_name']) ? $parentData['first_name'] : '',
            !empty($parentData) && !empty($parentData['last_name']) ? $parentData['last_name'] : '',
            $username,
            $status_label,
            $status_date,
            (null != $student_schedule ? $student_schedule->date_submitted('m/d/Y') : ''),
            (null != $student_schedule ? ($student_schedule->isAcceptedOnly() ? $student_schedule->date_accepted('m/d/Y') : $student_schedule->status()) : ''),
            $student_birth_date
        ];

        $columnSort[] = [
            null, $_date_requested, null, null, null, null, null, null, null, null, null, null, $_stat_date
        ];
    } else
    {
        $row = [
            $person->getPersonID(),
            $date_requested,
            $person->getLastName(),
            $person->getPreferredFirstName(),
            ($isStudent ? $person->getGradeLevelValue($year->getID()) : ''),
            ($isStudent ? ($midYearStudent ? 'Yes' : 'No') : ''),
            $person->getEmail(),
            $username,
            $status_label,
            $status_date,
            (null != $student_schedule ? $student_schedule->date_submitted('m/d/Y') : ''),
            (null != $student_schedule ? ($student_schedule->isAcceptedOnly() ? $student_schedule->date_accepted('m/d/Y') : $student_schedule->status()) : '')
        ];
        $row[] = $isStudent ? $student_birth_date : $person->getDateOfBirth('m/d/Y');
        $columnSort[] = [
            null, $_date_requested, null, null, null, null, null, null, null, $_stat_date
        ];
    }
    if($courseName)
    {
        $row[] = $courseName;
        $row[] = $providerCourse;
    }

    $reportArr[] = $row;
};
if($loadAll)
{
    foreach($persons as $person)
    {
        $personId = $person->getID();
        if($person->getType() == 'student')
        {
            $studentId = $person->getID();
            $schedule = (isset($student_schedules[$studentId])) ? $student_schedules[$studentId] : null;
            $application = (isset($student_applications[$studentId])) ? $student_applications[$studentId] : null;
            $packet = (isset($student_packets[$studentId])) ? $student_packets[$studentId] : null;
            $parentData = (isset($parent_data[$person->getParentID()]))
                ? $parent_data[$person->getParentID()]
                : null;
            $addRow($person, $parentData, $schedule, null, null, $application, $packet);
        } else
        {
            $addRow($person);
        }
    }
} else
{
    foreach($persons as $courseName => $coursePersons)
    {
        foreach($coursePersons as $person)
        {
            $personId = $person->getID();
            $course = explode('||', $courseName);
            $schedule = (isset($student_schedules[$personId])) ? $student_schedules[$personId] : null;
            $parentData = $person->getType() == 'student' && (isset($parent_data[$person->getParentID()])) ? $parent_data[$person->getParentID()] : null;
            $addRow($person, ($getStudents ? $parentData : null), $schedule, $course[0], $course[1]);
        }
    }
}

$file = 'Usernames';

include ROOT . core_path::getPath('../report.php');
