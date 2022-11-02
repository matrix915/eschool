<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Active Schedules - ' . $year;
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

if (req_get::is_set('grade')) {
    $filter->setGradeLevel(req_get::txt_array('grade'));
}

if (req_get::is_set('soe')) {
    $filter->setSchoolOfEnrollment(req_get::int_array('soe'));
}
$statuses = [mth_schedule::STATUS_ACCEPTED, mth_schedule::STATUS_CHANGE, mth_schedule::STATUS_CHANGE_POST];
$data = [];

if (req_get::bool('google') || req_get::bool('csv')) {
    $filter->setSearchValue('');
    $filter->setPaginate(false);
    while ($schedule = mth_schedule::eachByStudentIds($year, $filter->getStudentIDs(), $statuses)) {
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

            // $reportArr[] = [
            //     $student->getSOEname($year, false),
            //     $schedule->date_accepted('m/d/Y'),
            //     $student->getLastName(),
            //     $student->getFirstName(),
            //     $student->getGradeLevelValue($year->getID()),
            //     $schedulPeriod->period()->num(),
            //     $schedulPeriod->courseName(),
            //     $courseCode ? $courseCode->state_code() : '',
            //     $courseCode ? $courseCode->teacher_name() : '',
            //     $schedulPeriod->course_type(),
            //     $schedulPeriod->mth_providerName(),
            //     $schedulPeriod->provider_courseTitle(),
            //     $schedulPeriod->tp_district(),
            //     $schedulPeriod->tp_name(),
            //     $schedulPeriod->tp_course(),
            //     $schedulPeriod->tp_phone(),
            //     $schedulPeriod->tp_website(),
            //     $schedulPeriod->tp_desc(NULL, false),
            //     $schedulPeriod->custom_desc(NULL, false),
            // ];
            $reportArr[] = [
                $student->getSOEname($year, false),
                $schedule->date_accepted('m/d/Y'),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $student->getLastName()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $student->getFirstName()),
                $student->getGradeLevelValue($year->getID()),
                $schedulPeriod->period()->num(),
                $schedulPeriod->courseName(),
                $courseCode ? $courseCode->state_code() : '',
                $courseCode ? str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $courseCode->teacher_name()) : '',
                $schedulPeriod->course_type(),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->mth_providerName()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->provider_courseTitle()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->tp_district()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->tp_name()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->tp_course()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->tp_phone()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->tp_website()),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->tp_desc(NULL, false)),
                str_replace(['&#039;', '&rsquo;', '&eacute;', '&amp;', '&iacute', '&quot;', '&auml;', '&euml;', '&ntilde;', '&aacute;', '&rdquo;', '&ldquo;', '&mdash;', '&egrave;', '&reg;', '&bull;', '&ndash;', '&nbsp;', '&ntilde;', '&lsquo;', '&sup2;', '&trade;', '&hellip;', '&frac12;', '&pi;'], ["'", "'", "é", "&", "í", '"', "ä", "ë", "ñ", "Á", '”', '“', '—', 'è', '®', '•', '–', ' ', "ñ", "‘", "²", "™", "…", "½", "π"], $schedulPeriod->custom_desc(NULL, false)),
            ];
        }
    }
    if (req_get::bool('google')) {
        include ROOT . core_path::getPath('../report.php');
        exit();
    } else {

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '.csv"');
        $output = fopen('php://output', 'w');
        foreach ($reportArr as $data_item) {
            fputcsv($output, $data_item);
        }
        exit();
    }
}

if (req_get::is_set('ajax')) {

    
    while ($schedule = mth_schedule::eachByStudentIds($year, $filter->getStudentIDs(), $statuses)) {
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

            $grade_level = $student->getGradeLevelValue($year->getID());
            $level = sprintf('%02d', $grade_level);

            $data[] = [
                'soe' => $student->getSOEname($year, false),
                'date_accepted' => $schedule->date_accepted('m/d/Y'),
                'last_name' => $student->getLastName(),
                'first_name' => $student->getFirstName(),
                'grade' => $grade_level == 'K'||$grade_level == 'OR-K' ? $grade_level : (string)$level,
                'period' => $schedulPeriod->period()->num(),
                'course_name' => $schedulPeriod->courseName(),
                'state_code' => $courseCode ? $courseCode->state_code() : '',
                'teacher_name' => $courseCode ? $courseCode->teacher_name() : '',
                'course_type' => $schedulPeriod->course_type(),
                'provider_name' => $schedulPeriod->mth_providerName(),
                'course_title' => $schedulPeriod->provider_courseTitle(),
                'district' => $schedulPeriod->tp_district(),
                'tp_name' => $schedulPeriod->tp_name(),
                'tp_course' => $schedulPeriod->tp_course(),
                'tp_phone' => $schedulPeriod->tp_phone(),
                'tp_website' => $schedulPeriod->tp_website(),
                'tp_desc' => $schedulPeriod->tp_desc(NULL, false),
                'custom_desc' => $schedulPeriod->custom_desc(NULL, false),
            ];
        }
    }
   

    echo json_encode($data);
    exit();
}

// include ROOT . core_path::getPath('../report.php');

core_loader::includeBootstrapDataTables('css');

core_loader::isPopUp();
core_loader::printHeader();
$popup_id = isset($_popup_id) ? $_popup_id : 'reportPopup';
?>
<style>
    #mth-reports-active-table {
        font-size: 12px;
    }


    .dataTables_filter {
        float: none;
        text-align: left;
    }
</style>

<div class="iframe-actions">
    <button type="button" title="Send to Google" class="btn btn-round btn-secondary" onclick="window.open((location.search?location.search+'&':'?')+'google=1')">
        <i class="fa fa-google hidden-md-up"></i><span class="hidden-sm-down">Send to Google</span>
    </button>
    <button type="button" title="Download CSV" class="btn btn-round btn-secondary" onclick="location=(location.search?location.search+'&':'?')+'csv=1'">
        <i class="fa fa-download hidden-md-up"></i><span class="hidden-sm-down">Download CSV</span>
    </button>
    <button type="button" title="Close" class="btn btn-round btn-secondary" onclick="top.global_popup_iframe_close(<?= "'$popup_id'" ?>)">
        <i class="fa fa-close hidden-md-up"></i><span class="hidden-sm-down">Close</span>
    </button>
</div>
<h2><?= $file ?></h2>
<div class="card">
    <div class="card-block pl-0 pr-0">
        <table id="mth-reports-active-table" class='table display dataTable responsive'>
            <?php foreach ($reportArr as $row_key => $row) : ?>

                <thead>
                    <tr>
                        <?php foreach ($row as $value) : ?>
                            <th><?= $value ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
            <?php endforeach; ?>

        </table>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>
<script>
    $(function() {
        // var columndDefs = $.parseJSON('<?= isset($columnDefs) ? json_encode($columnDefs) : json_encode([]) ?>');
        // sortDef = $.parseJSON('<?= isset($sortDef) ? json_encode($sortDef) : json_encode([]) ?>');

        var $table = $('#mth-reports-active-table').DataTable({
            bStateSave: true,
            bPaginate: true,
            pageLength: 25,
            'ajax': {
                'url': '?ajax=active-students',
                'type': 'POST',
                'dataType': 'json',
                success: function(response) {
                    $table.rows().remove();
                    $.each(response, function(index, studentObj) {
                        $table.row.add(studentObj);
                    });
                    $table.draw();
                }
            },
            aaSorting: [
                [2, 'asc']
            ],
            'columns': [{
                    data: 'soe'
                },
                {
                    data: 'date_accepted'
                },
                {
                    data: 'last_name'
                },
                {
                    data: 'first_name'
                },
                {
                    data: 'grade'
                },
                {
                    data: 'period'
                },
                {
                    data: 'course_name'
                },
                {
                    data: 'state_code'
                },
                {
                    data: 'teacher_name'
                },
                {
                    data: 'course_type'
                },
                {
                    data: 'provider_name'
                },
                {
                    data: 'course_title'
                },
                {
                    data: 'district'
                },
                {
                    data: 'tp_name'
                },
                {
                    data: 'tp_course'
                },
                {
                    data: 'tp_phone'
                },
                {
                    data: 'tp_website'
                },
                {
                    data: 'tp_desc'
                },
                {
                    data: 'custom_desc'
                }
            ]

        })

    });
</script>