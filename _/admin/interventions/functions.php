<?php

use mth\student\SchoolOfEnrollment;
use mth\yoda\homeroom\Query;
use mth\yoda\courses;

function load_interventions(mth_schoolYear $year, $report = false)
{
    $COLUMNS = [
        'Latest Email Sent', 'Date Sent', 'Due Date', 'Label', 'Student Last Name, First Name', 'Gender', 'Grade Level', 'Parent Email', 'Mid-Year', 'Parent Phone', 'Parent Last Name, First Name', '# of 0', '# of EX', 'Homeroom Grade'
    ];

    $reportArr = $report ? [$COLUMNS] : [];

    $_grades =  req_isset('grades', 'int');
    $_gradelevel = req_isset('grade', 'int_array');
    $_mid_year = req_isset('mid_year', 'int');
    $_labels = req_isset('labels', 'int_array');
    $_email = req_isset('email', 'int_array');
    $_zero_count = req_isset('zero_count', 'txt');
    $_zero_count = trim($_zero_count) == '' ? null : $_zero_count;
    $_zero_count_1st_sem = req_isset('zero_count_1st_sem', 'txt');
    $_zero_count_1st_sem = trim($_zero_count_1st_sem) == '' ? null : $_zero_count_1st_sem;
    $_zero_count_2nd_sem = req_isset('zero_count_2nd_sem', 'txt');
    $_zero_count_2nd_sem = trim($_zero_count_2nd_sem) == '' ? null : $_zero_count_2nd_sem;

    $_ex_count = req_isset('ex_count', 'txt');
    $_ex_count = trim($_ex_count) == '' ? null : $_ex_count;


    $query = new Query();
    $query->setYear([$year->getID()]);
    if ($_gradelevel) {
        $query->setGradeLevel($_gradelevel, $year->getID());
    }

    $query
        ->selectGrade()
        ->selectZeros()
        ->selectFirstSemZeros()
        ->selectSecondSemZeros()
        ->selectEx()
        ->selectGradeLevel()
        ->selectOffenseNotif()
        ->selectInterventions()
        ->selectSOE()
        ->selectConsecutiveEx();

    $query->setStatus([mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING], $year->getID());
    $return = [];

    if ($enrollments = $query->getAll(req_get::int('page'))) {
        foreach ($enrollments as $enrollment) {
            $stgrade = $enrollment->getAveGrade();
            $zeros = $enrollment->getZeroCount();
            $first_sem_zeros = $enrollment->getFirstSemZeroCount();
            $second_sem_zeros = $enrollment->getSecondSemZeroCount();
            $ex = $enrollment->getExCount();
            $gradelevel = $enrollment->getGradeLevel();
            $notiftype = $enrollment->getLatestNotifType();
            $notifdatesent = $enrollment->getLatestNotifDateSent('m/d/Y');
            $notifduedate = $enrollment->getLatestNotifDueDate('m/d/Y');
            $notifcount = $enrollment->notifCount();
            $label = $enrollment->getLabel();
            $label_id = $enrollment->getLabelId();
            $notescount = $enrollment->getNotesCount();
            $interventionid = $enrollment->getInterventionId();
            $consecutive_ex = $enrollment->getConsecutiveEX();
            $soe = SchoolOfEnrollment::get($enrollment->getSOE());

            // if(is_null($stgrade)){
            //     continue;
            // }

            if ($_zero_count !== null && $_zero_count > $zeros) {
                continue;
            }

            if ($_zero_count_1st_sem !== null && $_zero_count_1st_sem > $first_sem_zeros) {
                continue;
            }

            if ($_zero_count_2nd_sem !== null && $_zero_count_2nd_sem > $second_sem_zeros) {
                continue;
            }

            if ($_ex_count !== null && $_ex_count > $ex) {
                continue;
            }

            if ($_grades && $stgrade > $_grades) {
                continue;
            }

            if (!$student = $enrollment->student()) {
                continue;
            }

            if ($_mid_year && !$student->isMidYear()) {
                continue;
            }

            if (!($parent = $student->getParent())) {
                //core_notify::addError('Parent Missing for ' . $student);
                continue;
            }

            if ($_labels && (!$label_id || !in_array($label_id, $_labels))) {
                $filter_label = !$label_id && in_array(0, $_labels);
                if (!$filter_label) {
                    continue;
                }
            }

            if ($_email &&  !in_array($notiftype, $_email)) {
                continue;
            }


            $data = array(
                'email_sent' => mth_offensenotif::getTypes($notiftype),
                'date_sent' =>  $notifdatesent,
                'due_date' => $notifduedate,
                'label' => $label ? $label : null,
                'student_name' => $student->getPreferredLastName() . ', ' . $student->getPreferredFirstName(),
                'gender' => $student->getGender(),
                'grade_level' => $gradelevel,
                'pemail' => $parent->getEmail(),
                'mid_year' => ($student->isMidYear() ? 'Yes' : 'No'),
                'pphone' => (string) $parent->getPhone(),
                'parent_name' => $parent->getPreferredLastName() . ', ' . $parent->getPreferredFirstName(),
                'first_sem_zero_count' => $first_sem_zeros,
                'second_sem_zero_count' => $second_sem_zeros,
                'zero_count' => $zeros,
                'ex' => $ex,
                'grade' => is_null($stgrade) ? 'NA' : $stgrade,
                'soe' => $soe ? $soe->getShortName() : '',
                'hr' => $enrollment->getCourseId(),
                'consecutive_ex' => $consecutive_ex > 0 ? 'YES' : 'NO'
            );

            if (!$report) {
                $data = array_merge($data, [
                    'notif_type' => $notiftype,
                    'notice_count' => $notifcount,
                    'id' => $student->getID(),
                    'label' => $label ? [
                        'id' =>  $label_id,
                        'name' => $label
                    ] : null,
                    'notes' => $notescount,
                    'intervention' => $interventionid
                ]);
            } else {
                $value_only = [];
                foreach ($data as $d) {
                    $value_only[] = $d;
                }
                $data = $value_only;
            }
            $return[] = $data;
        }
    }
    return ['count' => count($enrollments), 'filtered' => $return];
}

/**
 * check if $_REQUEST param is set
 *
 * @param string $param param name
 * @param string $type  method
 * @return void
 */
function req_isset($param, $type)
{
    if (!(req_post::is_set($param) || req_get::is_set($param))) {
        return null;
    }

    $method = req_post::is_set($param) ? 'post' : 'get';

    return  call_user_func(array("req_$method", $type), $param);
}
/**
 * set $_REQUEST school year
 *
 * @return int
 */
function set_year()
{
    $year = '';
    if (req_post::is_set('school_year_id')) {
        $year = req_post::int('school_year_id');
    }

    if (req_get::is_set('school_year_id')) {
        $year = req_get::int('school_year_id');
    }

    return $year;
}
/**
 * Set School Year Selected or Current
 *
 * @param int $year
 * @param mth_schoolYear $selected_schoolYear
 * @param int $selected_schoolYear_id
 * @return boolean
 */
function is_year_set($year, mth_schoolYear &$selected_schoolYear, &$selected_schoolYear_id)
{
    $selected_schoolYear = mth_schoolYear::getByID($year);
    if (!$selected_schoolYear) {
        exit('0');
    }
    $selected_schoolYear_id = $selected_schoolYear->getID();
}

function prepForCSV($value)
{
    $value = req_sanitize::txt_decode($value);
    $quotes = false;
    if (strpos($value, '"') !== false) {
        $value = str_replace('"', '""', $value);
        $quotes = true;
    }
    if (!$quotes && (strpos($value, ',') !== false || strpos($value, "\n") !== false)) {
        $quotes = true;
    }
    if ($quotes) {
        $value = '"' . trim($value) . '"';
    }
    return $value;
}

function save_intervention(mth_student $student, mth_schoolYear $school_year, $getby = 'student', $params = [])
{

    if (!($enrollment = courses::getStudentHomeroom($student->getID(), $school_year))) {
        return [
            'error' =>  1,
            'message' => 'Unable to find User Enrollment'
        ];
    }

    $intervention = new mth_intervention();

    if ($getby == 'student') {
        if ($i_record = mth_intervention::getByStudent($student)) {
            $intervention->id($i_record->id());
        }
    } else if ($getby == 'id') {
        if ($i_record = mth_intervention::getByID($params['intervention_id'])) {
            $intervention->id($params['intervention_id']);
        }
    }

    $intervention->schoolYear($school_year->getID());
    $intervention->grade($enrollment->getStudentHomeroomGrade());
    $intervention->zeroCount($enrollment->getStudentHomeroomZeros());
    $intervention->student($student->getID());
    $intervention->resolve(0);

    if (isset($params['label_id'])) {
        $intervention->label($params['label_id']);
    }

    if (!$intervention->save()) {
        return [
            'error' => 1,
            'message' => "Unable to save {$student->getPreferredFirstName()} {$student->getPreferredLastName()} from intervention"
        ];
    }
    return ['error' => 0, 'message' => $intervention->id()];
}

function save($notice_type, mth_student $student, mth_schoolYear $school_year)
{
    $offense =  new mth_offensenotif();
    $_type = $notice_type;
    $offense->notifType($_type);
    $offense->studentId($student->getID());
    $offense->schoolYear($school_year->getID());

    $intervention = save_intervention($student, $school_year);
    if ($intervention['error'] == 1) {
        return $intervention;
    }

    $offense->interventionId($intervention['message']);

    if (!$offense->send($student)) {
        return ['error' => 1, 'message' => 'Error Sending Email'];
    }

    if (!$offense->save()) {
        return ['error' => 1, 'message' => "Unable to save message to {$student->getPreferredFirstName()} {$student->getPreferredLastName()}"];
    }


    return ['error' => 0, 'message' => 'success'];
}
