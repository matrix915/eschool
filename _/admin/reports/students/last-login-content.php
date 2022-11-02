<?php
/**
 * User: Rex
 * Date: 9/02/2017
 * Time: 02:43 AM PST
 */

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Last Login - '.$year;
$reportArr = array(array(
    'Parent or Student',
    'First Name',
    'Last Name',
    'Date / Time of Last Login'
));

// $columnDefs = [
//     ['type' => 'date', 'targets' => -1]
// ];

$columnSort = [[]];
$parents = [];


$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE,mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);

foreach ($filter->getStudents() as $student) {
    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        continue;
    }

    if(!in_array($parent->getID(),$parents)){
        $parents[] = $parent->getID();
        $reportArr[] = [
            'Parent',
            $parent->getPreferredFirstName(),
            $parent->getPreferredLastName(),
            $parent->getLastLogin('d M Y \a\t g a')
        ];
        $columnSort[] = [
            null,null,null,$parent->getLastLogin()
        ];
    }
    $has_account = $student->user()?true:false;

    $columnSort[] = [
        null,null,null,
        ($has_account?$student->getLastLogin():null)
    ];

   

    $reportArr[] = [
        'Student',
        $student->getFirstName(),
        $student->getLastName(),
        ($has_account?$student->getLastLogin('d M Y \a\t g a'):'No Yoda account')
    ];
}


/** @noinspection PhpIncludeInspection */
include ROOT . core_path::getPath('../report.php');