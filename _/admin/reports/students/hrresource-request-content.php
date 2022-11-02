<?php
/** @var mth_schoolYear $year */
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$header = [
    'Student ID',
    'Student First Name',
    'Student Last name',
    'Student Email',
    'Parent First Name',
    'Parent Last Name',
    'Parent Email',
    'Student Grade Level',
    'Vendor',
    'Date Requested',
    $year.' Student Status',
    'Withdrawn /Graduated Date if applicable',
    'Cost',
    'Student Birthdate'
];

$reportArr = [
    $header
];

$columnDefs = [
    ['type' => 'date', 'targets' => [9,11]]
];
$sortDef =  [[11, 'asc']];

// $vendor = [];
// while($resource = mth_resource_settings::each()){
//     $vendor[$resource->getID()] = $resource->name();
// }

// $reportArr[] = array_merge($header,$vendor);
$resource = new mth_resource_request();
if(req_get::bool('resource')){
    $resource->whereResourceId(req_get::int_array('resource'));
}

$resource->whereYearId([req_get::int('year')]);

if(req_get::bool('status')){
    $resource->whereStudenStatus([req_get::int('status')],[req_get::int('year')]);
}


$providers = [];

while($request = $resource->query()){
    if(!($student = $request->student())){
        continue;
    }

    if(!($parent = $student->getParent())){
        continue;
    }
    if(!in_array( $request->getResource(), $providers)){
        $providers[] =  $request->getResource();
    }
    
    $status = in_array($student->getStatus($year),[mth_student::STATUS_WITHDRAW,mth_student::STATUS_GRADUATED])?$student->getStatusDate($year, 'm/d/Y'):'';
    $cost = $request->getResource() && $request->getResource()->cost()?'$'.$request->getResource()->cost():'';
    $reportArr[] =  [
        $student->getID(),
        $student->getFirstName(),
        $student->getLastName(),
        $student->getEmail(),
        $parent->getPreferredFirstName(),
        $parent->getPreferredLastName(),
        $parent->getEmail(),
        $student->getGradeLevel(),
        $request->getResource(),
        $request->createDate('m/d/Y H:i:s'),
        $student->getStatusLabel($year),
        $status,
        $cost,
        $student->getDateOfBirth('m/d/Y')
    ];

    // $selected = [];
    // foreach($vendor as $key=>$value){
    //     $selected[] =$request->resource_id()==$key?'YES':'NO';
    // }
    

    // $reportArr[] = array_merge($row,$selected,[
    //     $request->createDate('Y-m-d H:i:s')
    // ]);
}
/** @noinspection PhpIncludeInspection */
$file = 'HR Account Request - ' . ($providers ? implode(', ', $providers) : 'All Providers') . ' - ' . $year;

include ROOT . core_path::getPath('../report.php');