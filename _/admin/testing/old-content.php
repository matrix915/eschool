<?php

if (req_get::bool('y')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('y'));
}
if (empty($year)) {
    $year = mth_schoolYear::getCurrent();
}

if (req_get::bool('send_to_dropbox')) {
    ($student = mth_student::getByStudentID(req_get::int('send_to_dropbox'))) || die('0');
    if (!$student->getSchoolOfEnrollment(true, $year)) {
        core_notify::addError($student . ' doesn\'t have a school of enrollment.');
        die('0');
    }
    ($optOut = mth_testOptOut::getByStudent($student, $year)) || die('0');
    if ($optOut->send_to_dropbox(req_get::int('send_to_dropbox'))) {
        echo 1;
        exit();
    }
    die('0');
}
if (req_get::bool('delete')) {
    ($student = mth_student::getByStudentID(req_get::int('delete'))) || die('0');
    ($optOut = mth_testOptOut::getByStudent($student, $year)) || die('0');
    if ($optOut->delete(req_get::int('delete'))) {
        echo 1;
        exit();
    }
    die('0');
}

$grades = req_get::txt_array('grades');

$sent = req_get::is_set('sent')?req_get::txt('sent'):'no';

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('State Testing opt-outs');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
    <style>
        /* #mth_testOptOut_table_info {
            display: none;
        }

        tr.dropbox-sent td {
            color: #1181DE;
        }

        #mth_testOptOut_year_filter {
            position: relative;
            z-index: 4;
            margin-right: 200px;
        }

         #filterBlock {
            overflow: auto;
            max-height: 0;
            transition: max-height 1s;
            margin-top: 0;
            padding: 0 0 0 10px;
            border-top: none;
        }

        #filterBlock fieldset {
            float: left;
            margin-right: 10px;
            opacity: 0;
            transition: opacity 1s;
            min-height: 300px;
            padding: 10px 2%;
        }

         #filterToggle {
            display: none;
        }

        #filterToggle:checked + #filterBlock {
            max-height: 1000px;
        }

        #filterToggle:checked + #filterBlock fieldset {
            opacity: 100;
        }

        small {
            color: #999;
        }

        label[for="filterToggle"] {
            cursor: pointer;
            position: relative;
            font-size: 18px;
            border: solid 1px #ddd;
            padding: 10px;
            border-bottom: none;
        }

        label[for="filterToggle"]:hover {
            text-decoration: underline;
        } */
    </style>
    
<?php
$optOutCount = mth_testOptOut::studentCount($year);
$potentialOptOuts = mth_testOptOut::potentialStudentCount($year);
$percent = round(($optOutCount / $potentialOptOuts) * 100);
?>
    <div class="card container-collapse">
        <div class="card-header">
            <h4 class="card-title mb-0"  data-toggle="collapse" aria-hidden="false" href="#filterBlock" aria-controls="filterBlock">
                <i class="panel-action icon md-chevron-right icon-collapse" ></i> Filter
            </h4>
        </div>
        <div class="card-block info-collapse collapse" id="filterBlock">
            <div class="row">
                <div class="col-md-4">
                    <fieldset>
                        <legend>Sent to Dropbox</legend>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="sent" value="all" <?= $sent=='all'?'CHECKED':''?> >
                            <label> All
                            </label>
                        </div>
                        <div class="radio-custom radio-primary">
                            <input type="radio" name="sent" value="yes" <?= $sent=='yes'?'CHECKED':''?>>
                            <label> Sent
                            </label>
                        </div>
                        <div class="radio-custom radio-primary">
                        <input type="radio" name="sent" value="no" <?= $sent=='no'?'CHECKED':''?>> 
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
                           
                                <input type="checkbox" id="grade-all"
                                    onclick="$('.gradeCB:checked').prop('checked',false);"
                                    <?= !req_get::is_set('grades') ? 'checked' : '' ?>>
                            <label for="grade-all">
                            All Grades
                            </label>
                        </div>
                        <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="grade[]" value="<?= $grade_level ?>" id="grade-<?= $grade_level ?>"
                                        onclick="$('#grade-all').prop('checked',false);"
                                        <?= in_array($grade_level,$grades)?'checked':''?>
                                        <?= in_array($grade_level, req_get::txt_array('grade')) ? 'checked' : '' ?>
                                        class="gradeCB">
                                <label for="grade-<?= $grade_level ?>" onclick="$('#grade-all').prop('checked',false);">
                                   <?= $grade_desc ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary btn-round" onclick="doFilter()">Filter</button>
                </div>
            </div>
            
            
           
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <p id="mth_testOptOut_year_filter">
                <select onchange="doFilter()">
                    <?php while ($eachYear = mth_schoolYear::each()): ?>
                        <option value="<?= $eachYear->getStartYear() ?>"
                            <?= $eachYear->getID() == $year->getID() ? 'selected' : '' ?>><?= $eachYear ?></option>
                    <?php endwhile; ?>
                </select> &nbsp;
                Opt-outs: <span id="mth_testOptOut_count"><?= number_format($optOutCount) ?></span> &nbsp;
                <span id="mth_testOptOut_percent"><?= $percent ?></span>%
                <small>(of <span id="mth_testOptOut_percent_of"><?= number_format($potentialOptOuts) ?></span>)</small>
            </p>
        </div>
        <div class="card-block pl-0 pr-0">
            <table id="mth_testOptOut_table" class="responsive table">
                <thead>
                <tr>
                    <th>
                        <input type="checkbox"
                            onclick="$('.optOutCB').prop('checked',window.cb = !window.cb)">
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
                <?php while ($optOut = mth_testOptOut::each($year)): while ($student = $optOut->eachStudent()): ?>
                    <?php 
                        $is_sent = $optOut->sent_to_dropbox($student->getID())? 'yes' : 'no';
                        $grade_val = $student->getGradeLevelValue($year->getID());
                        $allowed_sent = $sent=='all' || $is_sent == $sent;
                        $allowed_grades = count($grades)==0 || in_array($grade_val, $grades);
                    ?>
                    <?php if($allowed_sent && $allowed_grades):?>
                    <tr class="dropbox-<?= $optOut->sent_to_dropbox($student->getID()) ? 'sent' : 'not-sent' ?>">
                        <td>
                            <?php // if($student->getSchoolOfEnrollment(true, $year)): ?>
                            <input type="checkbox" class="optOutCB" value="<?= $student->getID() ?>">
                            <?php // endif; ?>
                        </td>
                        <td>
                            <?= $student->getPreferredLastName() ?>, <?= $student->getPreferredFirstName() ?>
                        </td>
                        <td>
                            <?= $student->getParent()->getPreferredLastName() ?>,
                            <?= $student->getParent()->getPreferredFirstName() ?>
                        </td>
                        <td>
                            <?= $student->getAddress() ? $student->getAddress()->getCity() : '' ?>
                        </td>
                        <td><?= $grade_val ?></td>
                        <td><?= $student->getSchoolOfEnrollment(false, $year) ?></td>
                        <td><?= $optOut->date_submitted('m/d/Y') ?></td>
                        <td><?= ucfirst($is_sent) ?></td>
                    <?php endif;?>
                    </tr>
                <?php endwhile; endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button class="btn btn-round btn-info"   onclick="sendToDropBox()" type="button">Send to Dropbox</button>
            <button class="btn btn-round btn-danger"  onclick="deleteOptOuts()" type="button">Delete</button>
        </div>
    </div>
    

    
    
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    $(function () {
        $('#mth_testOptOut_table').dataTable({
            'aoColumnDefs': [{"bSortable": false, "aTargets": [0]}],
            "bStateSave": true,
            "bPaginate": false,
            "aaSorting": [[1, 'asc']]
        });
        
    });

    function doFilter(){
            var grades = $('[name="grade[]"]:checked').map(function(){return $(this).val();}).get();

            var sent = $('[name="sent"]:checked').val();
            var year = $('#mth_testOptOut_year_filter select').val();

            location.href = setParams(grades,sent,year);
    }

    function setParams(grades,sent,year){
        var params = {};
        if(grades.length>0){
            params['grades'] = grades;
        }
        if(year!=''){
            params['y'] = year;
        }
        params['sent'] = sent;
        return '?'+$.param(params);
    }

    function sendToDropBox() {
        var CBs = $('.optOutCB:checked');
        if (CBs.length < 1) {
            swal('','Select at least one student','warning');
            return;
        }
        global_waiting();
        sendToDropBox.completed = 0;
        sendToDropBox.timeout = 1;
        CBs.each(function () {
            var thisCB = this;
            setTimeout(function () {
                $.get('?y=<?=$year->getStartYear()?>&send_to_dropbox=' + thisCB.value, function () {
                    sendToDropBox.completed++;
                    if (sendToDropBox.completed === CBs.length) {
                        location.href = '?y=<?=$year->getStartYear()?>';
                    }
                });
            }, sendToDropBox.timeout * 500);
            sendToDropBox.timeout++;
        });
    }
    function deleteOptOuts() {
        var CBs = $('.optOutCB:checked');
        if (CBs.length < 1) {
            swal('','Select at least one student','warning');
            return;
        }
        global_waiting();
        deleteOptOuts.completed = 0;
        deleteOptOuts.timeout = 1;
        CBs.each(function () {
            var thisCB = this;
            setTimeout(function () {
                $.get('?y=<?=$year->getStartYear()?>&delete=' + thisCB.value, function () {
                    deleteOptOuts.completed++;
                    if (deleteOptOuts.completed === CBs.length) {
                        location.href = '?y=<?=$year->getStartYear()?>';
                    }
                });
            }, deleteOptOuts.timeout * 500);
            deleteOptOuts.timeout++;
        });
    }
</script>