<?php

use mth\aws\ses;
use mth\yoda\studentassessment;

$current_year = mth_schoolYear::getCurrent()->getID();

if (isset($_GET['loadhomeroom'])) {
    $select = 'select ms.*,mp.* from yoda_teacher_assessments  as yta
left join yoda_student_homeroom as ysh on ysh.yoda_course_id=yta.course_id
inner join mth_student as ms on ms.student_id=ysh.student_id
inner join mth_person as mp on mp.person_id=ms.person_id
inner join mth_student_status as mss on mss.student_id=ms.student_id
where DATE_FORMAT(deadline, "%m/%d/%Y") = "' . $_GET['deadline'] . '" and ysh.school_year_id=' . $current_year . ' and mss.school_year_id=' . $current_year . ' and mss.status in(' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
and not exists(select * from yoda_student_assessments where assessment_id=yta.id and person_id=ms.person_id and draft is null)';
    $retval = [];
    if ($students = core_db::runGetObjects($select, 'mth_student')) {
        foreach ($students as $student) {

            if (!($parent = $student->getParent())) {
                error_log('send homeroom marker: Parent Missing for ' . $student);
                continue;
            }

            if (!($hr = $student->getHomeroomTeacher($current_year))) {
                error_log('send homeroom marker: Teacher Missing for ' . $student);
                continue;
            }

            $retval[] = [
                'fname' => $student->getPreferredFirstName(),
                'lname' => $student->getPreferredLastName(),
                'tname' => $hr->getName(),
                'temail' => $hr->getEmail(),
                'pemail' => $parent->getEmail(),
                'id' => $student->getID()
            ];
        }
    }
    echo json_encode($retval);
    exit();
}

if (isset($_GET['markunsubmitted'])) {

    //Create an Late submission entry for unsubmitted and mark as Resubmit Needed
    $sql =
        'INSERT into yoda_student_assessments (created_at,person_id,assessment_id,is_late,grade,reset)
        select now(),mp1.person_id,yta.id,1 as is_late,0 as grade,' . studentassessment::RESET . ' as reset from yoda_teacher_assessments  as yta
        left join yoda_student_homeroom as ysh on ysh.yoda_course_id=yta.course_id
        inner join mth_student as ms on ms.student_id=ysh.student_id
        inner join mth_person as mp1 on mp1.person_id=ms.person_id
        inner join mth_student_status as mss on mss.student_id=ms.student_id
        where DATE_FORMAT(deadline, "%m/%d/%Y") = "' . $_POST['deadline'] . '" and ysh.school_year_id=' . $current_year . '
        and mss.school_year_id=' . $current_year . ' and mss.status in(' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        and not exists(
            select * from yoda_student_assessments 
            where assessment_id=yta.id and person_id=ms.person_id
        )' . (!empty($_POST['selected_ids']) ? ' and ms.student_id in(' . implode(',', $_POST['selected_ids']) . ')' : '');

    core_db::runQuery($sql);

    //update draft logs  into late and mark as Resubmit Needed
    $sql = 'update yoda_student_assessments set updated_at=now(),is_late=1,grade=0,draft=null,reset=' . studentassessment::RESET . '
    where draft=1 and assessment_id in(
        select id from yoda_teacher_assessments where DATE_FORMAT(deadline, "%m/%d/%Y") = "' . $_POST['deadline'] . '"
        and draft=1
    )';

    core_db::runQuery($sql);
    exit;
}

if (isset($_GET['sendemail'])) {
    if (($_content = core_setting::get('missingLogContent', 'LearningLog'))
        && ($subject  = core_setting::get('missingLogSubject', 'LearningLog'))
    ) {
        $content = str_replace(
            '[STUDENT_FIRST]',
            $_POST['value'],
            $_content
        );

        $fromname = isset($_POST['fname']) ? $_POST['fname'] : 'Infocenter';
        $fromemail = isset($_POST['femail']) ? $_POST['femail'] : 'Infocenter';

        $ses = new core_emailservice();
        $ses->enableTracking(true);

        $result = $ses->send(
            [$_POST['to']],
            $subject,
            $content,
            [$fromemail, $fromname]
        );
        echo json_encode([
            'error' => ($result ? 0 : 1),
            'data' => [
                'id' => $_POST['id']
            ]
        ]);
    } else {
        echo json_encode([
            'error' => 1,
            'data' => [
                'id' => $_POST['id']
            ]
        ]);
    }
    exit();
}
core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Homeroom Auto-grader');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>
<style>
    #homeroom_table {
        font-size: 12px;
    }

    #homeroom_table td {
        padding: 2px;
        vertical-align: middle;
    }
</style>
<div class="card">
    <div class="card-block">
        <div class="form-group"><label>Deadline (mm/dd/yyyy)</label><input id="deadline" type="text" value="<?= date('m/d/Y') ?>" class="form-control"></div>
        <button class="btn btn-primary btn-round" id="filter">Filter</button>
    </div>
</div>
<div class="card" style="fixed;display:none;" id="log-control">
    <div class="card-block">
        <button style="display:inline" class="btn btn-round btn-success" onclick="markunsubmitted()" id="mark-unsubmitted">Mark unsubmitted to 0</button>
        &nbsp;<b> -->> <span style="color:red">SELECT/CHECK STUDENT(S) BELOW</span> -->> </b>&nbsp;
        <button style="display:inline" class="btn btn-round btn-primary" id="send-message" onclick="publish()">Notify Parents</button>
    </div>
</div>
<div class="card">
    <div class="card-header">
        Total Students: <span class="student_count_display"></span>
    </div>
    <div class="card-block pl-0 pr-0">
        <table id="homeroom_table" class="table responsive">
            <thead>
                <tr>
                    <th> <input type="checkbox" title="Un/Check All" class="check-all"></th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Parent Email</th>
                    <th>HR Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    vindex = 0;
    tobesend = [];
    errors = 0;
    parentcount = 0;

    function publish() {


        tobesend = [];

        $('.actionCB:checked').each(function() {
            var data = $(this).data();
            tobesend.push(data);
        });

        if (tobesend.length == 0) {
            swal('', 'Please select atleast 1 student to notify', 'warning');
            return;
        }

        global_waiting();

        vinterval = setInterval(function() {
            var item = tobesend[vindex++];
            if (typeof item != 'undefined') {
                _publish({
                    value: item.fname,
                    fname: item.tname,
                    femail: item.temail,
                    id: item.id,
                    to: item.pemail
                });
            } else {
                global_waiting_hide();
                clearInterval(vinterval);
                var message = "Done sending notification to parents.";
                var type = 'success';
                if (errors > 0) {
                    message += ' ' + errors + ' error(s) detected.';
                }

                if (errors == parentcount) {
                    type = 'error';
                    message = 'There seems to be an issue sending the notification.'
                }
                swal('', message, type);

            }
        }, 1000);
    }


    function _publish(item) {
        $.ajax({
            'url': '?sendemail=1',
            'type': 'post',
            'data': $.param(item),
            dataType: "json",
            success: function(response) {
                if (response.error == 0) {
                    var data = response.data;
                    $('#st' + data.id).find('.sent').fadeIn();
                } else {
                    $('#st' + response.data.id).find('.error').fadeIn();
                    errors++;
                }
            },
            error: function() {
                alert('there is an error occur when publishing');
            }
        });
    }

    function markunsubmitted() {
        global_waiting();
        var deadline = $('#deadline').val();
        let selected_ids = [];

        $('.actionCB:checked').each(function() {
            var data = $(this).data();
            selected_ids.push(data.id);
        });

        $.ajax({
            'url': '?markunsubmitted=1',
            'type': 'post',
            data: {
                deadline: deadline,
                selected_ids: selected_ids
            },
            success: function(response) {
                global_waiting_hide();
                if (selected_ids.length == 0) {
                    swal('', 'All unsubmitted logs for ' + deadline + ' is successfully auto-graded you may now notify parents.', 'success');
                } else {
                    swal('', 'All selected logs for ' + deadline + ' is successfully auto-graded you may now notify parents.', 'success');
                }
                $('#filter').click();
            },
            error: function() {
                swal('', 'Error occured during the process.', 'error');

            }
        });
    }


    $(function() {

        $('.check-all').change(function() {
            var check = $(this).is(':checked');
            $('.actionCB').prop("checked", check);
        });

        $('#filter').click(function() {
            var deadline = $('#deadline').val();
            $('#homeroom_table').addClass('waiting');
            $('#homeroom_table tbody').html('');
            $.ajax({
                url: '?loadhomeroom',
                data: {
                    deadline: deadline
                },
                method: 'get',
                dataType: 'JSON',
                success: function(response) {
                    $('.student_count_display').text(response.length);
                    if (response.length == 0) {
                        $('#log-control').fadeOut();
                    } else {
                        $('#log-control').fadeIn();
                    }
                    parentcount = response.length;
                    $.each(response, function(i, val) {
                        $('#st' + val.id).length == 0 && $('#homeroom_table tbody').append('<tr id="st' + val.id + '" ><td><input type="checkbox" data-id="' + val.id + '" data-tname="' + val.tname + '" data-fname="' + val.fname + '" data-pemail="' + val.pemail + '" data-temail="' + val.temail + '" class="actionCB"></td><td>' + val.lname + '</td><td><i class="fa fa-check sent"  style="display:none;color:green;"></i><i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>' + val.fname + '</td><td>' + val.pemail + '</td><td>' + val.temail + '</td><td><a href="#" onclick=\'global_popup_iframe("mth_student_learning_logs", "/_/user/learning-logs?student=' + val.id + '")\'>Learning Log</a></t></tr>');
                    });
                    $('#homeroom_table').removeClass('waiting');
                }
            });
        });

    });
</script>