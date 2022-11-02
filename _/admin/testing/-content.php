<?php

use mth\optout\Query;

if ($year = req_isset('y', 'int')) {
    $year = mth_schoolYear::getByStartYear($year);
} else {
    $year = mth_schoolYear::getCurrent();
}

function load_opt($year)
{
    //echo "HERE";
    $_sent = req_isset('sent', 'txt');
    $_gradelevel = req_isset('grade', 'txt_array');

    $query = new Query();
    $query->setYear([$year->getID()]);

    $optouts = $query->getAll(req_get::int('page'));
    $return = [];
    foreach ($optouts as $optout) {

        if (!($student = $optout->getStudent())) {
            continue;
        }

        $parent = $student->getParent();
        $grade_val = $student->getGradeLevelValue($year->getID());
        $is_sent = $optout->isSentToDropbox();

        if ($_sent != 'all' && (($is_sent && $_sent != 'yes') || (!$is_sent && $_sent != 'no'))) {
            continue;
        }

        if ($_gradelevel && !in_array($grade_val, $_gradelevel)) {
            continue;
        }

        $return[] = [
            'student_name' => $student->getName(true),
            'parent_name' => $parent->getName(true),
            'city' => $student->getAddress() ? $student->getAddress()->getCity() : '',
            'grade_level' => $grade_val,
            'school' => $student->getSchoolOfEnrollment(false, $year)->getShortName(),
            'optdate' => $optout->date_submitted('m/d/Y'),
            'sent' => $is_sent ? 'Yes' : 'No',
            'id' => $student->getID()
        ];
    }
    return ['count' => count($optouts), 'filtered' => $return];
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
    //echo "2";
    if (!(req_post::is_set($param) || req_get::is_set($param))) {
        return null;
    }

    $method = req_post::is_set($param) ? 'post' : 'get';

    return  call_user_func(array("req_$method", $type), $param);
}


if (req_get::bool('loaOptout')) {
    //echo "3";
    $students = load_opt($year);
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}

if (req_get::bool('stat')) {
    $optOutCount = mth_testOptOut::studentCount($year);
    $potentialOptOuts = mth_testOptOut::potentialStudentCount($year);
    $percent = round(($optOutCount / $potentialOptOuts) * 100);
    echo json_encode([
        'count' =>  number_format($optOutCount),
        'potential' => number_format($potentialOptOuts),
        'percent' => $percent
    ]);
    exit();
}

if (req_get::bool('send_to_dropbox')) {
    ($student = mth_student::getByStudentID(req_get::int('send_to_dropbox'))) || die('0');
    if (!$student->getSchoolOfEnrollment(true, $year)) {
        echo json_encode(['error' => 1, 'data' => $student . ' doesn\'t have a school of enrollment.']);
        exit();
    }
    if (!($optOut = mth_testOptOut::getByStudent($student, $year))) {
        echo json_encode(['error' => 1, 'data' => $student . ' doesn\'t opt out record.']);
        exit();
    }

    if ($optOut->send_to_dropbox(req_get::int('send_to_dropbox'))) {
        echo json_encode(['error' => 0, 'data' => 'Sent']);
        exit();
    }

    echo json_encode(['error' => 1, 'data' => 'Unable to sent to dropbox']);
    exit();
}
if (req_get::bool('delete')) {
    if (!($student = mth_student::getByStudentID(req_get::int('delete')))) {
        echo json_encode(['error' => 1, 'data' => 'Unable to find student']);
        exit();
    }
    if (!($optOut = mth_testOptOut::getByStudent($student, $year))) {
        echo json_encode(['error' => 1, 'data' => 'Unable to find opt out record']);
        exit();
    }
    if ($optOut->delete(req_get::int('delete'))) {
        echo json_encode(['error' => 0, 'data' => 'Deleted']);
        exit();
    }
    echo json_encode(['error' => 1, 'data' => 'Unable to delete ' . $student]);
    exit();
}

$grades = req_get::txt_array('grades');

$sent = req_get::is_set('sent') ? req_get::txt('sent') : 'no';

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('State Testing opt-outs');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
<div class="card container-collapse">
    <div class="card-header">
        <h4 class="card-title mb-0" data-toggle="collapse" aria-hidden="false" href="#filterBlock" aria-controls="filterBlock">
            <i class="panel-action icon md-chevron-right icon-collapse"></i> Filter
        </h4>
    </div>
    <div class="card-block info-collapse collapse" id="filterBlock">
        <div class="row">
            <div class="col-md-4">
                <fieldset>
                    <legend>Sent to Dropbox</legend>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="sent" value="all" <?= $sent == 'all' ? 'CHECKED' : '' ?>>
                        <label> All
                        </label>
                    </div>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="sent" value="yes" <?= $sent == 'yes' ? 'CHECKED' : '' ?>>
                        <label> Sent
                        </label>
                    </div>
                    <div class="radio-custom radio-primary">
                        <input type="radio" name="sent" value="no" <?= $sent == 'no' ? 'CHECKED' : '' ?>>
                        <label>
                            Not Sent
                        </label>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-4">
                <fieldset>
                    <legend>Grade Level</legend>
                    <div class="checkbox-custom checkbox-primary">

                        <input type="checkbox" id="grade-all" onclick="$('.gradeCB:checked').prop('checked',false);" <?= !req_get::is_set('grades') ? 'checked' : '' ?>>
                        <label for="grade-all">
                            All Grades
                        </label>
                    </div>
                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade-<?= $grade_level ?>" onclick="$('#grade-all').prop('checked',false);" <?= in_array($grade_level, $grades) ? 'checked' : '' ?> <?= in_array($grade_level, req_get::txt_array('grade')) ? 'checked' : '' ?> class="gradeCB">
                        <label for="grade-<?= $grade_level ?>" onclick="$('#grade-all').prop('checked',false);">
                            <?= $grade_desc ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </fieldset>
            </div>
            <div class="col-md-4">
                <select class="form-control" name="y">
                    <?php while ($eachYear = mth_schoolYear::each()) : ?>
                    <option value="<?= $eachYear->getStartYear() ?>" <?= $eachYear->getID() == $year->getID() ? 'selected' : '' ?>><?= $eachYear ?></option>
                    <?php endwhile; ?>
                </select>
                <hr>
                <button class="btn btn-primary btn-round" onclick="doFilter()">Filter</button>
            </div>
        </div>



    </div>
</div>

<div class="card">
    <div class="card-header">
        <p id="mth_testOptOut_year_filter">
            Opt-outs: <span id="mth_testOptOut_count">0</span> &nbsp;
            <span id="mth_testOptOut_percent">0</span>%
            <small>(of <span id="mth_testOptOut_percent_of">0</span>)</small>
        </p>
    </div>
    <div class="card-block pl-0 pr-0">
        <table id="mth_testOptOut_table" class="responsive table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" onclick="$('.optOutCB').prop('checked',window.cb = !window.cb)">
                    </th>
                    <th>Student</th>
                    <th>Parent</th>
                    <th>City</th>
                    <th>Grade</th>
                    <th>School</th>
                    <th>Opt-out Date</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <button class="btn btn-round btn-info" onclick="sendOptToDropbox()" type="button">Send to Dropbox</button>
        <button class="btn btn-round btn-danger" onclick="deleteOpt()" type="button">Delete</button>
    </div>
</div>




<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('homeroomsteacher', '/_/admin/testing/test.js');
core_loader::printFooter('admin');
?>
<script>
    $DataTable = null;
    $form = null;
    var PAGE_SIZE = <?= Query::PAGE_SIZE ?>;
    var error_sent = 0;


    $(function() {

        $form = $('#filterBlock');
        $table = $('#mth_testOptOut_table');

        $DataTable = $table.DataTable({
            pageLength: 25,
            aoColumnDefs: [{
                "bSortable": false,
                "aTargets": [0]
            }],
            aaSorting: [
                [1, 'asc']
            ],
            columns: [{
                    data: 'cb',
                    sortable: false
                },
                {
                    data: 'student_name'
                },
                {
                    data: 'parent_name'
                },
                {
                    data: 'city'
                },
                {
                    data: 'grade_level'
                },
                {
                    data: 'school'
                },
                {
                    data: 'optdate'
                },
                {
                    data: 'sent'
                },
            ],
            iDisplayLength: 25
        });

        Optout.setPageSize(PAGE_SIZE);
        doFilter();

    });

    function doFilter() {
        Optout.resetTable = true;
        Optout.active_page = ($DataTable.page.info()).page;
        var data = $form.find('input,select').serialize();
        Optout.loadStudents(false, data);
        stat();
    }



    function sendToDropBox() {
        var CBs = $('.optOutCB:checked');
        var YEAR = $('[name="y"]').val();

        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        sendToDropBox.completed = 0;
        sendToDropBox.timeout = 1;
        error_sent = 0;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.get('?y=' + YEAR + '&send_to_dropbox=' + thisCB.value, function(response) {
                    sendToDropBox.completed++;
                    var data = $.parseJSON(response);

                    if (data.error == 1) {
                        error_sent++;
                        toastr.error(data.data);
                    }

                    if (sendToDropBox.completed === CBs.length) {
                        if (error_sent > 0) {
                            swal('', 'Done, ' + error_sent + ' error(s) found', 'info');
                        } else {
                            swal('', 'Sent to Dropbox, no error found', 'success');
                        }
                        global_waiting_hide();
                        doFilter();
                    }
                });
            }, sendToDropBox.timeout * 500);
            sendToDropBox.timeout++;
        });
    }

    current_checked_index = 0;
    send_error = 0;
    checkedbox = [];
    selected_year = null;

    function sendOptToDropbox() {
        selected_year = $('[name="y"]').val();
        checkedbox = $('.optOutCB:checked').map(function(i, cb) {
            return $(cb).val();
        }).get();

        if (checkedbox.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();

        _send(checkedbox[current_checked_index]);
    }

    function _send(checked_value) {
        $.get('?y=' + selected_year + '&send_to_dropbox=' + checked_value, function(response) {
            var data = $.parseJSON(response);

            if (data.error == 1) {
                send_error++;
                toastr.error(data.data);
            }
            current_checked_index++;
            var nextrow = checkedbox[current_checked_index];

            if (nextrow == undefined) {
                if (send_error > 0) {
                    swal('', 'Done, ' + send_error + ' error(s) found', 'info');
                } else {
                    swal('', 'Sent to Dropbox, no error found', 'success');
                }

                current_checked_index = 0;
                send_error = 0;
                checkedbox = [];

                global_waiting_hide();
                doFilter();
            } else {
                _send(nextrow);
            }
        });
    }

    function stat() {
        var YEAR = $('[name="y"]').val();
        $.get('?y=' + YEAR + '&stat=1', function(response) {
            var data = $.parseJSON(response);
            $('#mth_testOptOut_count').text(data.count);
            $('#mth_testOptOut_percent').text(data.percent);
            $('#mth_testOptOut_percent_of').text(data.potential);
        });

    }


    function deleteOptOuts() {
        var CBs = $('.optOutCB:checked');
        var YEAR = $('[name="y"]').val();

        if (CBs.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        deleteOptOuts.completed = 0;
        deleteOptOuts.timeout = 1;
        CBs.each(function() {
            var thisCB = this;
            setTimeout(function() {
                $.get('?y=' + YEAR + '&delete=' + thisCB.value, function(response) {
                    var data = $.parseJSON(response);

                    if (data.error == 1) {
                        toastr.error(data.data);
                    }

                    deleteOptOuts.completed++;
                    if (deleteOptOuts.completed === CBs.length) {
                        global_waiting_hide();
                        doFilter();
                    }
                });
            }, deleteOptOuts.timeout * 500);
            deleteOptOuts.timeout++;
        });
    }

    function deleteOpt() {
        selected_year = $('[name="y"]').val();
        checkedbox = $('.optOutCB:checked').map(function(i, cb) {
            return $(cb).val();
        }).get();

        if (checkedbox.length < 1) {
            swal('', 'Select at least one student', 'warning');
            return;
        }
        global_waiting();
        _deleteOpt(checkedbox[current_checked_index]);
    }

    function _deleteOpt(checked_value) {
        $.get('?y=' + selected_year + '&delete=' + checked_value, function(response) {
            var data = $.parseJSON(response);

            if (data.error == 1) {
                toastr.error(data.data);
            }
            current_checked_index++;
            var nextrow = checkedbox[current_checked_index];

            if (nextrow == undefined) {
                current_checked_index = 0;
                checkedbox = [];

                global_waiting_hide();
                doFilter();
            } else {
                _deleteOpt(nextrow);
            }
        });
    }
</script>