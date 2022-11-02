<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$immunizationSettingsAllIds = mth_immunization_settings::getAllIds();

function findObject($searchValue, $studentImmunizations)
{
    return array_filter(
        $studentImmunizations,
        function ($e) use (&$searchValue) {
            if (strtolower( $e->title ) == strtolower( $searchValue) ) {
                return $e;
            }
        }
    );
}

function getVaccineValue($immunization)
{
    if ( $immunization->getExempt() ) {
        return 'EX';
    }

    if ( $immunization->getImmune() ) {
        return 'IM';
    }

    if ( $immunization->getNonapplicable() ) {
        return 'NA';
    }

    if ( $immunization->getDateAdministered('m/d/Y') ) {
        return $immunization->getDateAdministered('m/d/Y');
    }
}

function exemption($studentImmunizations, $immunizationSettingsAllIds)
{
    $hasExemption = false;
    $entryCount = count($immunizationSettingsAllIds);
    $checkedCount = 0;
    foreach ( $studentImmunizations as $immunization ) {
        if ( !in_array($immunization->getImmunizationId(), $immunizationSettingsAllIds)) {
            continue;
        }

        if ( $immunization->getExempt() ) {
            $hasExemption = true;
        }

        if ( $immunization->getExempt() ) {
            $checkedCount++;
        }

    }
    return [
        'exempt_all' => $entryCount == $checkedCount, 
        'has_exemption' => $hasExemption
    ];
}

$file = 'Immunizations Report - ' . $year;
$field = [
    "Date Packet Accepted",
    "SOE",
    "Student Last",
    "Student First",
    "Gender",
    "DOB",
    "Parent First Name",
    "Parent Last Name",
];

$immunizationTitle = [];
foreach (mth_immunization_settings::getAllTitles() as $title) {
    $field[] = $title;
    $immunizationTitle[] = $title;
}
$field[] = "Exemption Form";
$field[] = "Exempt for all?";
$field[] = "Date Exemption form issued";
$reportArr = array(
    $field
);
$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear($year->getID());

if(req_get::is_set('grade')){
    $filter->setGradeLevel(req_get::txt_array('grade'));
}

if(req_get::is_set('soe')){
    $filter->setSchoolOfEnrollment(req_get::int_array('soe'));
}

$statuses = array(mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST);

$students = $filter->getStudents();
foreach ($students as $student) {
    if( !$parent = $student->getParent() )
        continue;
    if( !$packet = mth_packet::getStudentPacket($student) ) 
        continue;
    $studentImmunizations = mth_student_immunizations::getByStudent($student->getID());
    $exemption = exemption($studentImmunizations, $immunizationSettingsAllIds);
    $reportArr[] = array(
        $packet->getDateAccepted('m/d/Y'),
        $student->getSOEname($year,false),
        $student->getLastName(),
        $student->getFirstName(),
        $student->getGender(),
        $student->getDateOfBirth('m/d/Y'),
        $parent->getFirstName(),
        $parent->getLastName()
    );
    end($reportArr);
    $key = key($reportArr);
    foreach ( $immunizationTitle as $field ) {
        $immunizationValue = array_values(findObject($field, $studentImmunizations));
        $reportArr[$key][] = $immunizationValue ? getVaccineValue($immunizationValue[0]) : NULL;
    }
    $reportArr[$key][] = $exemption['has_exemption'] ? 'Yes' : 'No';
    $reportArr[$key][] = $exemption['exempt_all'] ? 'Yes' : 'No';
    $reportArr[$key][] = $exemption['has_exemption'] ? $packet->getExemptionFormDate('m/d/Y') : '';
}

include ROOT . core_path::getPath('../report.php');