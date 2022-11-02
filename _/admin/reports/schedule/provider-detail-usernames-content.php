<?php
($year = $_SESSION['mth_reports_school_year']) || die('No year set');


$students = (req_get::txt('type') == 'Students');
$both = (req_get::txt('type') == 'Both');

//parent headers by default
$headers = [
    'Person ID',
    'Last',
    'First',
    'Email',
    'City',
    'Phone',
    'Username'
];

if($students){
    $headers = [
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
        'Username'
    ];
}elseif($both){
    $headers = [
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
        'Username'
    ];
}

$statuses = [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST];
/** @var mth_parent[][]|mth_student[][] $persons */
$persons = [];
$loadAll = true;
$parents = [];

if (req_get::bool('provider')) {
    $loadAll = false;
    foreach (req_get::int_array('provider') as $provider_id) {
        if (!($provider = mth_provider::get($provider_id))) {
            continue;
        }
        while ($schedule_period = mth_schedule_period::eachWithProvider($provider, $year, $statuses, false, true)) {
            $person = $schedule_period->schedule()->student();
            if ($students || $both) {
                $parent = $person->getParent();
                $parents[$parent->getID()] = [
                    'city' => $parent->getCity(),
                    'email' => $parent->getEmail(),
                    'lastname' => $parent->getLastName(),
                    'firstname' => $parent->getPreferredFirstName(),
                    'phone' => $parent->getPhone()
                ];

                if($both){
                    $persons[$schedule_period->courseName().'||'.$schedule_period->getRawProviderCourse()][$parent->getPersonID()] = $parent;
                }

            }else{
                $person = $person->getParent();
            }
            $persons[$schedule_period->courseName().'||'.$schedule_period->getRawProviderCourse()][$person->getPersonID()] = $person;
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
            if ($students || $both) {
                $parent = $person->getParent();
                $parents[$parent->getID()] = [
                    'city' => $parent->getCity(),
                    'email' => $parent->getEmail(),
                    'lastname' => $parent->getLastName(),
                    'firstname' => $parent->getPreferredFirstName(),
                    'phone' => $parent->getPhone()
                ];
                if($both){
                    $persons[$schedule_period->courseName().'||'.$schedule_period->getRawProviderCourse()][$parent->getPersonID()] = $parent;
                }
            }else{
                $person = $person->getParent();
            }
            $persons[$schedule_period->courseName().'||'.$schedule_period->getRawProviderCourse()][$person->getPersonID()] = $person;
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
        foreach ($persons as $person){
            $parent = $person->getParent();
            $parents[$parent->getID()] = [
                'city' => $parent->getCity(),
                'email' => $parent->getEmail(),
                'lastname' => $parent->getLastName(),
                'firstname' => $parent->getPreferredFirstName(),
                'phone' => $parent->getPhone()
            ];
        }
    }elseif($both){
        $persons = $filter->getAll();
    }
    else {
        $persons = $filter->getParents();
    }
}else{
    $headers[] = 'Course';
    $headers[] = 'Class';
}

$reportArr = [$headers];

function getParent($students,$both,$person,$parents)
{
    $parent  = null;
     /**
     * USE PARENT ID directly to parent object if:
     * filter is both(student,parent) and person is a student
     * =======================================
     * USE PARENT ID through student object if:
     * filter is student (parent filter is not applicable since it is not using
     * $parent array) though $parent is null
     * 
     */
    if($students || $both){
        $parent = ($both && $person->getType() != 'student')?
        $parents[$person->getID()]:
        $parents[$person->getParentID()];
    }

    return $parent;
}
/**
 * @param mth_person|mth_student|mth_parent $person
 * @param null|string $parentEmail
 * @param null|string $courseName
 */
$addRow = function(
    mth_person $person,
    $parent = null,
    $courseName=null,
    $providerCourse = null 
    ) use (&$reportArr,$year,$students,$both,$loadAll){

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
            $username = strtolower($person->getLastName() . $person->getPreferredFirstName()) .'mth';
            break;
    }

    if($students || $both){
        $row = [
            $person->getID(),
            $person->getLastName(),
            $person->getPreferredFirstName(),
            ($person->getType() == 'student' ? $person->getGradeLevelValue($year->getID()) : ''),
            $person->getAge(),
            $person->getDateOfBirth('m/d/Y'),
            $person->getEmail(),
            $parent['city'],
            $parent['lastname'],
            $parent['firstname'],
            $parent['email'],
            $parent['phone'],
            $username
        ];
    }else{
        $row = [
            $person->getPersonID(),
            $person->getLastName(),
            $person->getPreferredFirstName(),
            $person->getEmail(),
            $person->getCity(),
            $person->getPhone(),
            $username
        ];
    }

    if($courseName){
        $row[] = $courseName;
        $row[] = $providerCourse;
    }
    
    $reportArr[] = $row;
};
if($loadAll){
    foreach($persons as $person){
        $parent  = getParent($students,$both,$person,$parents);
        $addRow($person,$parent);
    }
}else{
    foreach ($persons as $courseName => $coursePersons) {
        foreach($coursePersons as $person){
            $course = explode('||',$courseName);
            $parent  = getParent($students,$both,$person,$parents);
        
            $addRow($person,$parent,$course[0],isset($course[1])?$course[1]:null);
        }
    }
}

$file = 'Student Details and Usernames';

include ROOT . core_path::getPath('../report.php');