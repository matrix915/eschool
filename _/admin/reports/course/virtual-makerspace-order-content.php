<?php

$title = 'KiwiCo';

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$statuses = req_get::bool('statuses')
    ? req_get::int_array('statuses')
    : [
        mth_schedule::STATUS_STARTED,
        mth_schedule::STATUS_SUBMITTED,
        mth_schedule::STATUS_ACCEPTED,
        mth_schedule::STATUS_CHANGE,
        mth_schedule::STATUS_RESUBMITTED,
        mth_schedule::STATUS_CHANGE_POST,
        mth_schedule::STATUS_CHANGE_PENDING
    ];
$providerIds = req_get::int_array('provider');
$providers = array();
$reported = array();
$file = 'Virtual Makerspace Order Report - ' . $year;

$reportArr = array(
    array(
        'shipping_street1',
        'shipping_street2',
        'shipping_city',
        'shipping_state',
        'shipping_zipcode',
        'shipping_method',
        'billing_street1',
        'billing_street2',
        'billing_city',
        'billing_state',
        'billing_zipcode',
        'customer_email',
        'parent_name',
        'parent_lastname',
        'parent_email',
        'child_name',
        'child_lastname',
        'child_birthday',
        'sku',
        'qty',
        'storeId',
        'couponCode',
        'PO_number',
        'Schedule Submitted Date',
        'Schedule Date/Status',
    )
);

$query = new \mth\schedule\query();
$query->setProviderIds($providerIds)
    ->setStatuses($statuses)
    ->setSchoolYearIds([$year->getID()]);

$studentIds = $query->getStudentIds();

$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING]);
$filter->setStatusYear([$year->getID()]);
$filter->setStudentIDs($studentIds);

$schedules = [];
foreach($query->getAll() as $schedule) {
    $schedules[$schedule->student_id()] = $schedule;
}

$students = [];
foreach($filter->getStudents() as $student) {
    $students[$student->getID()] = $student;
}

$parents = [];
foreach($filter->getParents() as $parent) {
    $parents[$parent->getID()] = $parent;
}

$providerCourses = [];
foreach(mth_provider_course::allByProviderIds($providerIds) as $course) {
    $providerCourses[$course->id()] = $course;
}

foreach($schedules as $schedule) {
    if(!(array_key_exists($schedule->student_id(), $students)
            && ($student = $students[$schedule->student_id()]))
        || !(array_key_exists($student->getParentID(), $parents)
            && ($parent = $parents[$student->getParentID()])))
    {
        continue;
    }
    while ($schedulePeriod = mth_schedule_period::eachByProvider($schedule, $providerIds)) {
        $address = $parent->getAddress();

        /** @var mth_provider_course $providerCourse */
        $providerCourse = (array_key_exists($schedulePeriod->provider_course_id(), $providerCourses) ? $providerCourses[$schedulePeriod->provider_course_id()] : NULL);
        $reportArr[] = array(
            $address->getStreet(),
            $address->getStreet2(),
            $address->getCity(),
            $address->getState(),
            $address->getZip(),
            '',
            '',
            '',
            '',
            '',
            '',
            'kiwico@mytechhigh.com',
            $parent->getFirstName(),
            $parent->getLastName(),
            $parent->getEmail(),
            $student->getFirstName(),
            $student->getLastName(),
            '',
            '6',
            '',
            $providerCourse->title(),
            '',
            '',
            (!$schedule->isStatus(0) ? $schedule->date_submitted('m/d/Y') : ''),
            ($schedule->isAcceptedOnly() ? $schedule->date_accepted('m/d/Y') : $schedule->status()),
        );
    }
}

include ROOT . core_path::getPath('../report.php');
