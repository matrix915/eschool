<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 11/2/16
 * Time: 11:28 AM
 */

$reportArr = array(array(
    'Student Last',
    'Student First',
    'Student Email',
    'Parent Last',
    'Parent First',
    'Parent Email',
));

foreach(mth_student::getStudentsWithoutPackets() as $student){
    if( !$student->isPendingOrActive()
        || !($parent = $student->getParent())
    ){
        continue;
    }
    $reportArr[] = array(
        $student->getLastName(),
        $student->getFirstName(),
        $student->getEmail(),
        $parent->getPreferredLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail()
    );
}


$file = 'Students Without Packets';

include ROOT . core_path::getPath('../report.php');