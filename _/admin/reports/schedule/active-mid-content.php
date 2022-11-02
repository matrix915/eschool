<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Active Schedules (Mid-year) - ' . $year;
$reportArr = [
    [
        'SoE',
        'Date Schedule was Accepted',
        'Student Legal Last',
        'Student Legal First',
        'Grade',
        'Period',
        'Course',
        'Course Code',
        'Teacher',
        'Course Type',
        'MTH Provider',
        'Provider Course',
        'District',
        'TP/District-School Name',
        'TP/District-School Course',
        'TP/District-School Phone',
        'TP Website',
        'TP Description',
        'Custom Description',
    ]
];
$filter = new mth_person_filter();
$filter->setStatus([mth_student::STATUS_ACTIVE]);
$filter->setStatusYear($year->getID());
$filter->setMidYear(true);

if(req_get::is_set('grade')){
    $filter->setGradeLevel(req_get::txt_array('grade'));
}

if(req_get::is_set('soe')){
    $filter->setSchoolOfEnrollment(req_get::int_array('soe'));
}

$statuses = [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST];
while ($schedule = mth_schedule::eachByStudentIds($year,$filter->getStudentIDs(), $statuses)) {
    ($student = $schedule->student()) || die('Missing student');
    ($parent = $student->getParent()) || die('Missing Parent');
    while ($schedulPeriod = $schedule->eachPeriod()) {
        $course = $schedulPeriod->course();
        if ($course) {
            $gradeLevel = $student->getGradeLevelValue($year->getID()) == 'K' ? 0 : $student->getGradeLevelValue($year->getID());
            $courseCode = mth_coursestatecode::getByGradeAndCourse($gradeLevel, $course);
        } else {
            $courseCode = null;
        }

        $reportArr[] = [
            $student->getSOEname($year, false),
            $schedule->date_accepted('m/d/Y'),
            $student->getLastName(),
            $student->getFirstName(),
            $student->getGradeLevelValue($year->getID()),
            $schedulPeriod->period()->num(),
            $schedulPeriod->courseName(),
            $courseCode ? $courseCode->state_code() : '',
            $courseCode ? $courseCode->teacher_name() : '',
            $schedulPeriod->course_type(),
            $schedulPeriod->mth_providerName(),
            $schedulPeriod->provider_courseTitle(),
            $schedulPeriod->tp_district(),
            $schedulPeriod->tp_name(),
            $schedulPeriod->tp_course(),
            $schedulPeriod->tp_phone(),
            $schedulPeriod->tp_website(),
            $schedulPeriod->tp_desc(NULL, false),
            $schedulPeriod->custom_desc(NULL, false),
        ];
    }
}

include ROOT . core_path::getPath('../report.php');