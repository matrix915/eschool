<?php

use mth\student\SchoolOfEnrollment;
use mth\yoda\assessment;
use mth\yoda\courses;
use mth\yoda\homeroom\Query;
use mth\yoda\messages;
use mth\yoda\studentassessment;

function secondsToTime($seconds)
{
    if (!$seconds) {
        return null;
    }
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a day(s), %h hour(s), %i minute(s)');
}

function getGrade($log)
{
    if ($log->isNA()) {
        return 'NA';
    }

    if ($log->isExcused()) {
        return 'Excused';
    }

    if ($log->getGrade() != null) {
        return $log->getGrade() . '%';
    }

    return 'Ungraded';
}

function loadmonthly($soe = [])
{
    $query = new Query();
    $messages = new messages();
    $selected_schoolYear = mth_schoolYear::getCurrent();
    $query->setYear([$selected_schoolYear->getID()]);
    $query->selectGrade();
    $query->setSOE($soe, [$selected_schoolYear->getID()]);
    $enrollments = $query->getAll(req_get::int('page'));
    $data = [];
    $learningLogSort = [];
    $selected_month = strtotime(req_get::txt('month'));
    $from = date('Y-m-01', $selected_month);
    $to = date('Y-m-t', $selected_month);

    foreach ($enrollments as $enrollment) {
        if (!$student = $enrollment->student()) {
            continue;
        }
        $stgrade = $enrollment->getAveGrade();
        $teacher = $enrollment->getTeacherObject();
        $learningLogs = [];
        if ($assessments = assessment::getByCourseRange($enrollment->getCourseId(), $from, $to)) {
            processAssessments($assessments, $student, $messages, $learningLogs, $learningLogSort);
        }

        $data[] = array_merge(
            [
                'Legal Student Name' => $student->getLastName() . ', ' . $student->getFirstName(),
                'Preferred Student Name' => $student->getPreferredLastName() . ', ' . $student->getPreferredFirstName(),
                'School of Enrollment' => $student->getSOEname($selected_schoolYear),
            ],
            $learningLogs,
            [
                'Current_Score' => is_null($stgrade) ? 'NA' : $stgrade . '%',
                'Current_Grade' => is_null($stgrade) ? 'NA' : ($stgrade >= 80 ? "Pass" : "Fail"),
                'HR_Teacher_Name' => $teacher ? $teacher->getName() : 'NA',
            ]
        );
    }
    $learningLogSort = sortAndStagger($learningLogSort);

    $columns = array_merge(['Legal Student Name', 'Preferred Student Name', 'School of Enrollment'], $learningLogSort, ['Current_Score', 'Current_Grade', 'HR_Teacher_Name']);

    fixColumns($data, $columns);
    return $data;
}

function loadyearly($first_sem = true, $second_sem = true, $soe = [])
{
    $query = new Query();
    $messages = new messages();
    $selected_schoolYear = mth_schoolYear::getCurrent();
    $query->setYear([$selected_schoolYear->getID()]);
    $query->selectGrade();
    $query->setSOE($soe, [$selected_schoolYear->getID()]);
    $enrollments = $query->getAll(req_get::int('page'));
    $data = [];
    $learningLogSort = [];
    $columnArray = ['Grade_Level', 'Current_Score', 'Current_Grade', 'HR_Teacher_Name'];
    $deadline = date('Y-m-d', time());

    if (!$first_sem || !$second_sem) {
        if ($first_sem) {
            $deadline = $selected_schoolYear->getFirstSemLearningLogsClose('Y-m-d');
        } else {
            $deadline = $selected_schoolYear->getLogSubmissionClose('Y-m-d');
        }
    }

    foreach ($enrollments as $enrollment) {
        if (!$student = $enrollment->student()) {
            continue;
        }
        $stgrade = $enrollment->getAveGrade();
        $teacher = $enrollment->getTeacherObject();
        $learningLogs = [];
        if ($assessments = assessment::getByCourseDeadline($enrollment->getCourseId(), $deadline)) {
            processAssessments($assessments, $student, $messages, $learningLogs, $learningLogSort, $first_sem, $second_sem);
        }
        if (!$first_sem || !$second_sem) {
            $semesterGrade = $enrollment->getGrade($first_sem ? 1 : 2);
            $homeroomArray = [
                'Grade_Level' => $student->getGradeLevel(),
                'Semester_Average' => is_null($semesterGrade) ? 'NA' : $semesterGrade . '%',
                'Semester_Grade' => is_null($semesterGrade) ? 'NA' : ($semesterGrade >= 80 ? "Pass" : "Fail"),
                'Year_Average' => is_null($stgrade) ? 'NA' : $stgrade . '%',
                'Year_Grade' => is_null($stgrade) ? 'NA' : ($stgrade >= 80 ? "Pass" : "Fail"),
                'HR_Teacher_Name' => $teacher ? $teacher->getName() : 'NA',
            ];
            $columnArray = ['Grade_Level', 'Semester_Average', 'Semester_Grade', 'Year_Average', 'Year_Grade', 'HR_Teacher_Name'];
        } else {
            $homeroomArray =
                [
                'Grade_Level' => $student->getGradeLevel(),
                'Current_Score' => is_null($stgrade) ? 'NA' : $stgrade . '%',
                'Current_Grade' => is_null($stgrade) ? 'NA' : ($stgrade >= 80 ? "Pass" : "Fail"),
                'HR_Teacher_Name' => $teacher ? $teacher->getName() : 'NA',
            ];
        }

        $data[] = array_merge(
            [
                'Legal Student Name' => $student->getLastName() . ', ' . $student->getFirstName(),
                'Preferred Student Name' => $student->getPreferredLastName() . ', ' . $student->getPreferredFirstName(),
                'School of Enrollment' => $student->getSOEname($selected_schoolYear),
            ],
            $learningLogs,
            $homeroomArray
        );
    }

    $learningLogSort = sortAndStagger($learningLogSort);

    $columns = array_merge(['Legal Student Name', 'Preferred Student Name', 'School of Enrollment'], $learningLogSort, $columnArray);
    fixColumns($data, $columns);
    return $data;
}

function processAssessments($assessments, $student, $messages, &$learningLogs, &$learningLogSort, $first_sem = true, $second_sem = true)
{
    $current_schoolYear = mth_schoolYear::getCurrent();
    foreach ($assessments as $log) {
        if (!$first_sem) {
            if (strtotime($log->getDeadline('Y-m-d')) <= strtotime($current_schoolYear->getFirstSemLearningLogsClose('Y-m-d'))) {
                continue;
            }
        }
        if (!$second_sem) {
            if (strtotime($log->getDeadline('Y-m-d')) > strtotime($current_schoolYear->getFirstSemLearningLogsClose('Y-m-d'))) {
                continue;
            }
        }
        if ($stlog = studentassessment::get($log->getID(), $student->getPersonID())) {
            $learningLogs[$log->getTitle()] = getGrade($stlog);
            if (req_get::bool('teacherComments')) {
                if ($message = $messages->getMessagesById($stlog->getMessageId())) {
                    $learningLogs[$log->getTitle() . ' Feedback'] = '"' . cleanStr(trim(strip_tags($message[0]->getContent()))) . '"';
                } else {
                    $learningLogs[$log->getTitle() . ' Feedback'] = '';
                }
            }
        } else {
            $learningLogs[$log->getTitle()] = 'NA';

            if (req_get::bool('teacherComments')) {
                $learningLogs[$log->getTitle() . ' Feedback'] = '';
            }
        }
        if (!isset($learningLogSort[$log->getTitle()])) {
            $learningLogSort[$log->getTitle()] = $log->getDeadline();
        }
    }
}
function cleanStr($value)
{
    $value = str_replace('Â', '', $value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = str_replace('"', "'", $value);
    return $value;
}

function sortAndStagger($learningLogSort)
{
    function dateSort($time1, $time2)
    {
        if (strtotime($time1) > strtotime($time2)) {
            return 1;
        } else if (strtotime($time1) < strtotime($time2)) {
            return -1;
        } else {
            return 0;
        }

    }
    uasort($learningLogSort, 'dateSort');

    $staggeredSort = [];
    foreach ($learningLogSort as $title => $date) {
        $staggeredSort[] = $title;
        if (req_get::bool('teacherComments')) {
            $staggeredSort[] = $title . ' Feedback';
        }
    }
    return $staggeredSort;
}

function fixColumns(&$data, $columns)
{
    if ($data === []) {
        return;
    }
    foreach ($data as $index => $student) {
        $tempStudent = [];
        foreach ($columns as $column) {
            if (isset($student[$column])) {
                $tempStudent[$column] = $student[$column];
            } else {
                $tempStudent[$column] = '';
            }
        }
        $data[$index] = $tempStudent;
    }
}

if (req_get::bool('stat')) {
    $submitted = studentassessment::getCurrentWeekAllSubmitted(req_get::int('stat'));
    $ungraded = studentassessment::getCurrentWeekAllUngraded(req_get::int('stat'));
    $totalstudents = courses::getStudentCount(req_get::int('stat'));

    header('Content-type: application/json');
    echo json_encode([
        'submitted' => $submitted,
        'ungraded' => $ungraded,
        'students' => $totalstudents,
    ]);
    exit();
}

if (req_get::bool('getTeacher')) {
    header('Content-type: application/json');
    $users = core_user::getUsersByLevel(mth_user::L_TEACHER);
    $userArr = array();
    foreach ($users as $user) {
        /* @var $user core_user */
        $userArr[$user->getID()] = array(
            'total_students' => courses::getTeacherStudentCount(mth_schoolYear::getCurrent()->getID(), $user->getID()),
            'name' => $user->getName(true),
            'last_login' => $user->getLastLogin('m/d/Y H:i'),
            'last_graded_log' => core_model::getDate(studentassessment::getLastGradedTime($user->getID()), 'm/d/Y H:i'),
            'average_grade_time' => secondsToTime(studentassessment::getAveGradeTime($user->getID())),
        );
    }
    echo json_encode($userArr);
    exit();
}

if (req_get::bool('getDates')) {
    header('Content-type: application/json');
    $response = [];
    if ($dates = assessment::getMonthlyLogDates()) {
        $response = $dates;
    }
    echo json_encode($response);
    exit();
}

if (req_get::bool('loadmonthly')) {
    $soe = explode(',', $_GET['soe']);
    $students = loadmonthly($soe);
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}

if (req_get::bool('loadyearly')) {
    $soe = explode(',', $_GET['soe']);
    $students = loadyearly(true, true, $soe);
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}

if (req_get::bool('first_sem')) {
    $soe = explode(',', $_GET['soe']);
    $students = loadyearly(true, false, $soe);
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}

if (req_get::bool('second_sem')) {
    $soe = explode(',', $_GET['soe']);
    $students = loadyearly(false, true, $soe);
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}

cms_page::setPageTitle('Homeroom Dashboard');
cms_page::setPageContent('');
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader('admin');
?>
<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title">Learning Statistics</h3>
        <!-- <div class="panel-actions">
               <select name="year" id="school_year" class="form-control">
                    <?php foreach (mth_schoolYear::getSchoolYears() as $year): /* @var $year mth_schoolYear */?>
																																	                                                 <option <?=mth_schoolYear::getCurrent()->getID() == $year->getID() ? 'SELECTED' : ''?> value="<?=$year->getID()?>">
																																	                                                 <?=$year?>
																																	                                                 </option>
																																	                    <?php endforeach;?>
               </select>
          </div> -->
    </div>
    <div class="panel-body">
        <div class="text-center">
            Total students: <span class="total_students">0</span> / Total Submitted: <span class="total_submitted">0</span> / Total Ungraded: <span class="total_ungraded">0</span>
        </div>
        <div class="chart-container" style="position: relative; height:3oopx;">
            <canvas id="yoda_stat"></canvas>
        </div>
    </div>
</div>

<div class="card card-primary">
    <div class="card-block">
        <h4 class="card-title">Homeroom Teachers</h4>
        <table class="table" id="teacher_tbl">
            <thead>
                <th>Name</th>
                <th>Total Students</th>
                <th>Last Login</th>
                <th>Last Graded Log</th>
                <th>Average Grade Time</th>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<div class="card card-primary">
    <div class="card-block">
        <h4 class="card-title">Learning Log Report</h4>
        <div class="mth_filter_block card container-collapse" id="school_assignment_filter_block">
            <div class="card-header">
                <h4 class="card-title mb-0" data-toggle="collapse" aria-hidden="true" href="#soe-filter-cont" aria-controls="soe-filter-cont">
                    <i class="panel-action icon md-chevron-right icon-collapse"></i> Filter
                </h4>
            </div>
            <div class="card-block collapse info-collapse" id="soe-filter-cont">
                <div class="row">
                    <div class="col">
                        <fieldset>
                            <legend style="font-size: 1.2rem;">School of Enrollment</legend>
                            <?php
$SoEs = SchoolOfEnrollment::getActive();
foreach ($SoEs as $SoE) {?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" class="soe" name="<?=$SoE->getShortName()?>" id="<?=$SoE->getShortName()?>" value="<?=$SoE->getId()?>">
                                    <label for="chang">
                                        <?=$SoE->getShortName()?>
                                    </label>
                                </div>
                            <?php }?>
                        </fieldset>
                    </div>
                    <div class="col">
                        <fieldset>
                            <div align="right" class="checkbox-custom checkbox-primary">
                                <input type="checkbox" id="teacherComments">
                                <label>
                                    Add Teacher Comments
                                </label>
                            </div>
                        </fieldset>
                    </div>

                </div>
            </div>
        </div>
        <div class="monthly-container">
            <div class="form-group">
                <label>Select Month to generate report</label>
                <div class="input-group">
                    <select class="form-control" id="logdates">
                        <option></option>
                    </select>
                    <span class="input-group-btn">
                        <button type="button" id="generate_monthly" class="btn btn-success">GENERATE REPORT</button>
                    </span>
                </div>

            </div>
        </div>
        <div class="yearly-container">
            <button class="btn btn-success" id="generate_yearly">GENERATE YEAR-TO-DATE REPORT</button>
            <?php
$current_year = mth_schoolYear::getCurrent();
if ($current_year->getFirstSemLearningLogsClose() != $current_year->getLogSubmissionClose()) {?>
                <button class="btn btn-success" id="generate_first_sem">GENERATE 1ST SEMESTER REPORT</button>
                <button class="btn btn-success" id="generate_second_sem">GENERATE 2ND SEMESTER REPORT</button>
            <?php }?>
        </div>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0/dist/Chart.min.js"></script>
<script>
    var ctx = document.getElementById('yoda_stat').getContext('2d');
    var statChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Total Students', 'Learning Logs'],
            datasets: [{
                    label: 'Students',
                    backgroundColor: 'rgb(75, 192, 192)',
                    data: [0, 0]
                },
                {
                    label: 'Submitted',
                    backgroundColor: 'rgb(54, 162, 235)',
                    data: [0, 0]
                },
                {
                    label: 'Ungraded',
                    backgroundColor: 'rgb(255, 99, 132)',
                    data: [0, 0]
                }
            ]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });

    getStats(<?=mth_schoolYear::getCurrent()->getID()?>);

    function getStats(school_year) {
        $.ajax({
            url: '?stat=' + school_year,
            dataType: 'JSON',
            success: function(response) {
                //update total students
                statChart.data.datasets[0].data[0] = response.students;
                $('.total_students').text(response.students);
                //update submitted
                statChart.data.datasets[1].data[1] = response.submitted;
                $('.total_submitted').text(response.submitted);
                //update ungraded
                statChart.data.datasets[2].data[1] = response.ungraded;
                $('.total_ungraded').text(response.ungraded);
                statChart.update();
            }
        });
    }

    function loadTeacher() {
        $tbl.addClass('waiting');
        $.ajax({
            url: '?getTeacher=1',
            dataType: 'JSON',
            success: function(data) {
                for (var uID in data) {
                    $DATATABLE.row.add({
                        name: data[uID].name,
                        total_students: data[uID].total_students,
                        last_login: data[uID].last_login,
                        last_graded_log: data[uID].last_graded_log,
                        average_grade_time: data[uID].average_grade_time
                    });
                }
                $DATATABLE.draw();
                $tbl.removeClass('waiting');
            }
        });
    }

    function loadDates() {
        $logdates.closest('div').addClass('waiting');
        $.ajax({
            url: '?getDates=1',
            dataType: 'JSON',

            success: function(data) {
                const currentDate = new Date()
                const currentMonth = currentDate.toLocaleString('default', {
                    month: 'long'
                })
                const currentYear = currentDate.getYear() + 1900
                for (var uID in data) {
                    let isSelected = ''
                    let date = data[uID]
                    if (date.includes(currentYear) && date.includes(currentMonth)) {
                        isSelected = 'selected'
                    }
                    $logdates.append('<option ' + isSelected + ' value="' + date + '">' + date + '</option>');
                }
                $logdates.closest('div').removeClass('waiting');
            }
        });
    }


    class PCSVFJSON {
        url = '';
        busy = false;
        page = 1;
        reset = true;
        active_page = 0;
        _done = function() {};
        _error = function() {};
        data = [];
        params = [];

        constructor(url, _done, _error) {
            this.url = url;
            this._done = _done;
            this._error = _error || this.error;
        }

        _reset() {
            this.busy = false;
            this.reset = true;
            this.page = 1;
            this.active_page = 0;
        }

        load(nextPage, data) {
            var data = data;
            var prevpage = '';
            var curpage = '';

            if (this.busy && !nextPage) {
                return;
            }
            this.busy = true;

            if (nextPage) {
                this.page += 1;
                prevpage = 'page=' + (this.page - 1);
                curpage = 'page=' + this.page;
                data = data.replace(prevpage, curpage);
            } else {
                this.page = 1;
                data += '&page=1';
                this.data = [];
            }

            this.params = data;
            this.request();
        }

        _setHeader(data) {
            if (this.data[0] == undefined) {
                this.data[0] = Object.keys(data[0]);
                return Object.keys(data[0]);
            }
            return this.data[0];
        }

        add(data) {
            //set Header
            var header = this._setHeader(data);
            for (var i in data) {
                this.data.push(this.setColumn(header, data[i]));
            }
        }

        setColumn(cols, row) {
            var _row = [];
            for (var i in cols) {
                if (row[cols[i]] != undefined) {
                    _row.push(row[cols[i]]);
                } else {
                    _row.push('NA');
                }

            }
            return _row;
        }

        request() {
            var self = this;
            $.ajax({
                url: self.url,
                data: self.params,
                method: 'get',
                cache: false,
                dataType: 'json',
                success: (res) => {
                    if (res.length === 0) {
                        self._done();
                        self._reset();
                    } else {
                        self.add(res);
                        self.load(true, self.params);
                    }
                },
                error: self.error
            });
        }

        getCSVContent() {
            var lineArray = [];
            this.data.forEach(function(infoArray, index) {
                var line ="";
                infoArray.map(item=>{
                        if (item.includes(',')) {
                            line +=  '"' + item.replace(/"/g,'""') + '"' + ",";
                        }else{
                            line +=  item + ",";
                        }
                    })
                lineArray.push(line);

            });
            var csvContent = lineArray.join("\r\n");
            return new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
        }
    }

    function downloadCSV(blob, csvname) {
        if (navigator.msSaveBlob) { // IE 10+
            navigator.msSaveBlob(blob, csvname);
        } else {
            var link = document.createElement("a");
            if (link.download !== undefined) { // feature detection
                // Browsers that support HTML5 download attribute
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", csvname);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    }

    function replaceTextChecker(originString, placeTxt, updateTxt){
        // console.log("subItemString_include: ", originString)
        let newString = originString.split(placeTxt);
        let newCombineString = "";
        newString.map((comItem, index) => {
            // console.log("comItem: ", index, comItem);
            if (index < newString.length - 1) {
                newCombineString += comItem + updateTxt
            } else if (index === newString.length - 1) {
                newCombineString += comItem
            }
        })
        return newCombineString;
    }

    function removeSpecialLetterIssue(csvData) {
    let newTopData = [];
    let newSubData = [];
    csvData.map(topItem => {
        newSubData = [];
        topItem.map(subItem => {
            if (subItem) {
                let subItemString = subItem.toString();
                if (subItemString.includes('&amp;')) {
                    subItemString = replaceTextChecker(subItemString, '&amp;', "&");
                }
                if (subItemString.includes('â€“')) {
                    subItemString = replaceTextChecker(subItemString, 'â€“', "-");
                }
                if (subItemString.includes('&quot;')) {
                    subItemString = replaceTextChecker(subItemString, '&quot;', '"');
                }
                if (subItemString.includes('&#039;')) {
                    subItemString = replaceTextChecker(subItemString, '&#039;', "'");
                }
                if (subItemString.includes('&lt;')) {
                    subItemString = replaceTextChecker(subItemString, '&lt;', "<");
                }
                if (subItemString.includes('&gt;')) {
                    subItemString = replaceTextChecker(subItemString, '&gt;', ">");
                }
                newSubData.push(subItemString);
            }
        })
        newTopData.push(newSubData)
    })
    return newTopData;
}

    $(function() {
        $tbl = $('#teacher_tbl');
        $logdates = $('#logdates');
        $generate_monthly = $('#generate_monthly');
        $monthly_container = $('.monthly-container');
        $generate_yearly = $('#generate_yearly');
        $yearly_container = $('.yearly-container');
        $teacher_comments = $('#teacherComments');

        MONTHLY_R = new PCSVFJSON('?loadmonthly=1', function() {
            $monthly_container.removeClass('waiting');

            MONTHLY_R.data = removeSpecialLetterIssue(MONTHLY_R.data);
            var blob = MONTHLY_R.getCSVContent();
            var csvname = $logdates.val() + '.csv';

            downloadCSV(blob, csvname);
        });

        YEARLY_R = new PCSVFJSON('?loadyearly=1', function() {
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            var date = new Date();
            $yearly_container.removeClass('waiting');

            YEARLY_R.data = removeSpecialLetterIssue(YEARLY_R.data);
            var csvname = 'From Start to ' + monthNames[date.getMonth()] + ' ' + date.getDate() + '.csv';
            var blob = YEARLY_R.getCSVContent();

            downloadCSV(blob, csvname);
        });

        FIRST_SEM = new PCSVFJSON('?first_sem=1', function() {
            $('#generate_first_sem').removeClass('waiting');

            FIRST_SEM.data = removeSpecialLetterIssue(FIRST_SEM.data);
            var date = new Date();
            var csvname = 'First Semester Report - ' + date.getDate() + '.csv';
            var blob = FIRST_SEM.getCSVContent();

            downloadCSV(blob, csvname);
        });

        SECOND_SEM = new PCSVFJSON('?second_sem=1', function() {
            $('#generate_second_sem').removeClass('waiting');

            SECOND_SEM.data = removeSpecialLetterIssue(SECOND_SEM.data);
            var date = new Date();
            var csvname = 'Second Semester Report - ' + date.getDate() + '.csv';
            var blob = SECOND_SEM.getCSVContent();

            downloadCSV(blob, csvname);
        });

        $DATATABLE = $tbl.DataTable({
            "bStateSave": true,
            columns: [{
                    data: 'name'
                },
                {
                    data: 'total_students'
                },
                {
                    data: 'last_login',
                    type: 'dateNonStandard'
                },
                {
                    data: 'last_graded_log'
                },
                {
                    data: 'average_grade_time'
                },
            ]
        });

        $generate_monthly.click(function() {
            let soe = $(".soe:checkbox:checked").length ? $(".soe:checkbox:checked") : $(".soe");
            let soe_array = [];
            $.each(soe, function(key, item) {
                soe_array.push(item.value);
            });
            if ($logdates.val() == '') {
                swal('', 'Please select a month to generate first.', 'warning');
            } else {
                $monthly_container.addClass('waiting');
                MONTHLY_R.load(false, 'month=' + $logdates.val() + '&teacherComments=' + ($teacher_comments.prop('checked') == true ? '1' : '0') + '&&soe=' + soe_array);
            }
        });

        $generate_yearly.click(function() {
            let soe = $(".soe:checkbox:checked").length ? $(".soe:checkbox:checked") : $(".soe");
            let soe_array = [];
            $.each(soe, function(key, item) {
                soe_array.push(item.value);
            });
            $yearly_container.addClass('waiting');
            YEARLY_R.load(false, '&teacherComments=' + ($teacher_comments.prop('checked') == true ? '1' : '0') + '&&soe=' + soe_array);
        });

        $('#generate_first_sem').click(function() {
            let soe = $(".soe:checkbox:checked").length ? $(".soe:checkbox:checked") : $(".soe");
            let soe_array = [];
            $.each(soe, function(key, item) {
                soe_array.push(item.value);
            });
            $('#generate_first_sem').addClass('waiting');
            FIRST_SEM.load(false, '&teacherComments=' + ($teacher_comments.prop('checked') == true ? '1' : '0') + '&&soe=' + soe_array);
        });

        $('#generate_second_sem').click(function() {
            let soe = $(".soe:checkbox:checked").length ? $(".soe:checkbox:checked") : $(".soe");
            let soe_array = [];
            $.each(soe, function(key, item) {
                soe_array.push(item.value);
            });
            $('#generate_second_sem').addClass('waiting');
            SECOND_SEM.load(false, '&teacherComments=' + ($teacher_comments.prop('checked') == true ? '1' : '0') + '&&soe=' + soe_array);
        });

        loadTeacher();
        loadDates();
    });
</script>