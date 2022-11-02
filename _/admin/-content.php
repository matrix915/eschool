<?php
function studentStats($schoolYear)
{
    if ($schoolYear && ($statusArr = mth_student::getStatusCounts($schoolYear))) : ?>
        <?php $parentArr = mth_student::getStatusParentCounts($schoolYear); ?>
        <div class="card-header text-center">
            <h4 class="card-title mb-0"><?= $schoolYear ?> Student & Parents</h4>
        </div>
        <div class="card-block p-0">
        <table class="table table-striped responsive dashboard-tbl">
            <thead>
                <tr>
                    <th></th>
                    <th>Students</th>
                    <th>SPED</th>
                    <th>Parents</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= mth_student::STATUS_LABEL_PENDING ?></td>
                    <td><?= @number_format($statusArr[mth_student::STATUS_PENDING][0]); ?></td>
                    <td><?= @number_format($statusArr[mth_student::STATUS_PENDING][1]); ?></td>
                    <td><?= @number_format($parentArr[mth_student::STATUS_PENDING]); ?></td>
                </tr>
                <tr>
                    <td><?= mth_student::STATUS_LABEL_ACTIVE ?></td>
                    <td><?= @number_format($statusArr[mth_student::STATUS_ACTIVE][0]); ?></td>
                    <td><?= @number_format($statusArr[mth_student::STATUS_ACTIVE][1]); ?></td>
                    <td><?= @number_format($parentArr[mth_student::STATUS_ACTIVE]); ?></td>
                </tr>
                <tr style="border-bottom: solid 3px #666;border-top: solid 3px #666;">
                    <td class="bold-text">Total</td>
                    <td class="bold-text"><?= number_format(mth_student::getStudentCount($schoolYear)); ?></td>
                    <td><?= number_format(mth_student::getStudentCount($schoolYear, true)); ?></td>
                    <td><?= number_format(mth_student::getParentCount($schoolYear)); ?></td>
                </tr>
            <?php if (@$statusArr[mth_student::STATUS_WITHDRAW]) : ?>
                <tr>
                    <td><?= mth_student::STATUS_LABEL_WITHDRAW ?></td>
                    <td><?= number_format($statusArr[mth_student::STATUS_WITHDRAW][0]); ?></td>
                    <td><?= number_format($statusArr[mth_student::STATUS_WITHDRAW][1]); ?></td>
                    <td><?= number_format($parentArr[mth_student::STATUS_WITHDRAW]); ?></td>
                </tr>
            <?php endif; ?>
            <?php if (@$statusArr[mth_student::STATUS_GRADUATED]) : ?>
                <tr>
                    <td><?= mth_student::STATUS_LABEL_GRADUATED ?></td>
                    <td><?= number_format($statusArr[mth_student::STATUS_GRADUATED][0]); ?></td>
                    <td><?= number_format($statusArr[mth_student::STATUS_GRADUATED][1]); ?></td>
                    <td><?= number_format($parentArr[mth_student::STATUS_GRADUATED]); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    <?php endif;
} (mth_schoolYear::getNext()) || core_notify::addError('The next school year is not defined, errors could occure without the next school year being available.');

if(req_get::bool('application_count')){
    if($appCount = mth_application::getCount()){
        $appHidden = 0;
    ?>
    <table class="table">
        <tr class="bold-text">
            <td>Submitted</td>
            <td><?= number_format($appCount) ?></td>
        </tr>
        <?php if($appHidden = mth_application::getHiddenCount()):?>
        <tr class="bold-text">
            <td>Hidden</td>
            <td>-<?= number_format($appHidden) ?></td>
        </tr>
        <?php endif;?>
        <tr class="bold-text">
            <td>Total</td>
            <td><?=$appCount-$appHidden?></td>
        </tr>
    </table>
    <?php
    }
    exit();
}

if(req_get::bool('packet_count')){
    if($packetStats = mth_packet::getStatusCounts()){
    ?>
    <table class="table">
        <?php $total = 0; ?>
        <?php foreach ($packetStats as $status => $count) : ?>
        <?php $total += $count; ?>
            <tr>
                <th><?= $status ?></th>
                <td><?= number_format($count) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="bold-text">
            <td>Total</td>
            <td><?= number_format($total) ?></td>
        </tr>
    </table>
    <?php
    }
    exit();
}

if(req_get::bool('schedule_count')){
    if($scheduleStats = mth_schedule::getStatusCounts()){
        ?>
        <table class="table">
        <?php $total = mth_schedule::getNotStartedCount(); ?>
        <tr>
            <th>Not Started</th>
            <td><?= number_format($total) ?></td>
        </tr>
        <?php foreach ($scheduleStats as $status => $count) : if ($status == mth_schedule::STATUS_ACCEPTED) {
            continue;
        }
        $total += $count;
        ?>
        <tr>
            <th><?= mth_schedule::status_option_text($status) ?></th>
            <td><?= number_format($count) ?></td>
        </tr>
        <?php endforeach; ?>

            <tr class="bold-text">
                <td>Total</td>
                <td><?= number_format($total) ?></td>
            </tr>
        </table>
    <?php
    }
    exit();
}

if(req_get::bool('prevprevyear')){
    if(!($year = mth_schoolYear::getYearReEnrollOpen())){
        studentStats(mth_schoolYear::getPrevious()->getPreviousYear());
    }
    exit();
}

if(req_get::bool('prevyear')){
    studentStats(mth_schoolYear::getPrevious());
    exit();
}

if(req_get::bool('curyear')){
    studentStats(mth_schoolYear::getCurrent());
    exit();
}

if(req_get::bool('nextyear')){
    if($year = mth_schoolYear::getNext()){
        studentStats($year);
    }
    exit();
}


if(req_get::bool('grade_count')){
?>
    <?php $gradeAgeReport = mth_student_report::getGradeAge(mth_schoolYear::getCurrent()) ?>
    <?php $groupedGradeAgeReport = $gradeAgeReport->groupGradeAgeByLabel(); ?>
    <div class="div-table">
        <div>
            <div style="width: 30%; font-weight: 700;"><?= $gradeAgeReport->label_heading ?></div>
            <div
                style="width: 20%; font-weight: 700; text-align: right;"><?= $gradeAgeReport->extra1_heading ?></div>
            <div
                style="width: 20%; font-weight: 700; text-align: right;"><?= $gradeAgeReport->total_heading ?></div>
            <div
                style="width: 20%; font-weight: 700; text-align: right;"><?= $gradeAgeReport->percent_heading ?></div>
        </div>
        <?php if(count($groupedGradeAgeReport)> 0): foreach($groupedGradeAgeReport as $item): ?>
        <?php $gradeAgeAverage = mth_student_report::getGradeAgeAverage($item); ?>
            <div>
                <div style="width: 30%"><?= $item[0]->label ?></div>
                <div style="width: 20%; text-align: right"><?= $gradeAgeAverage['extra1']/$gradeAgeAverage['counter'] ?></div>
                <div style="width: 20%; text-align: right"><?= $gradeAgeAverage['total'] ?></div>
                <div style="width: 20%; text-align: right"><?= $gradeAgeAverage['percent'] ?>%</div>
            </div>
        <?php endforeach; endif;?>
        <div>
            <div style="width: 30%; text-align: right">Average:</div>
            <div
                style="width: 20%; font-weight: 700; text-align: right;"><?= $gradeAgeReport->end_extra1 ?></div>
            <div
                style="width: 20%; font-weight: 700; text-align: right;"><?= $gradeAgeReport->end_total ?></div>
            <div style="width: 20%; "></div>
        </div>
    </div>
<?php
exit;
}


if(req_get::bool('school_count')){
    mth_student_report::printReport(mth_student_report::getGender(mth_schoolYear::getCurrent())) ?>
    <div style="margin-bottom: 5px;"></div>
    <?php mth_student_report::printReport(mth_student_report::getSPED(mth_schoolYear::getCurrent())) ?>
    <div style="margin-bottom: 5px;"></div>
    <?php mth_student_report::printReport(mth_student_report::getSchoolOfEnrollment(mth_schoolYear::getCurrent())) ?>
    <div style="margin-bottom: 5px;"></div>
    <?php
    if ( ($report = mth_student_report::getPreviousSchoolOfEnrollment(mth_schoolYear::getCurrent()))) {
        mth_student_report::printReport($report);
    }
    exit;
}

if(req_get::bool('district_count')){
    mth_student_report::printReport(mth_student_report::getDistrictOfResidence(mth_schoolYear::getCurrent()));
    exit;
}

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Dashboard');
cms_page::setPageContent('<p>This page can be programmed to show an overview of things that need to be done</p>');

core_loader::printHeader('admin');



?>

    <style>
        .fourth-block {
            min-height: 300px;
        }
    </style>
 <?php if (! ($accessToken = core_setting::get(DROPBOX_TOKEN_VAR, 'DropBox')) || $accessToken->getValue() == '') : ?>
<div class="alert dark alert-alt alert-warning" role="alert">
    <a class="btn btn-info btn-round btn-sm" href="/_/admin/settings/dropbox-start" target="_blank">Authorize</a> 
   
        The site has not been authorized to send files to your DropBox account.
  
</div>
<?php endif; ?>
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header text-center">
                <a href="/_/admin/years" class="float-right"><i class="fa fa-gear"></i></a>
                <h4 class="card-title mb-0">School Year</h4>
            </div>
            <div class="card-block p-0">
            <table class="table">
                <tr class="bold-text">
                    <td>Current</td>
                    <td><?= ($currrent = mth_schoolYear::getCurrent()) ?></td>
                </tr>
                <?php if ( ($next = mth_schoolYear::getNext()) != $currrent) : ?>
                    <tr>
                        <tD>Next</td>
                        <td><?php if ($next) {
                                echo $next;
                            } else { ?><a href="/_/admin/years"><i>Set</i></a><?php 
                                                                            } ?></td>
                    </tr>
                <?php endif; ?>
            </table>
            <?php if (!$next) : ?>
                <p style="color: red">Set <?= $currrent->getStartYear() + 1 ?>
                    -<?= substr($currrent->getStartYear(), -2) + 2 ?> to ensure continued functionality.</p>
            <?php elseif (!$next->getNextYear()) : ?>
                <p style="color: red">Set <?= $next->getStartYear() + 1 ?>-<?= substr($next->getStartYear(), -2) + 2 ?>
                    to ensure continued functionality.</p>
            <?php endif; ?>
            </div>
        </div>
    </div>
 
    <div class="col-md-3">
        <div class="card">
            <div class="card-header text-center">
                <a href="/_/admin/applications" class="float-right"><i class="fa fa-gear"></i></a>
                <h4 class="card-title mb-0">Applications</h4>
            </div>
            <div class="card-block p-0 application_stat">
                <div class="text-center">Loading..</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-header text-center">
                <a href="/_/admin/packets" class="float-right"><i class="fa fa-gear"></i></a>
                <h4 class="card-title mb-0">Packets</h4>
            </div>
            <div class="card-block p-0 packet_stat">
            <div class="text-center">Loading..</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-header text-center">
                <a href="/_/admin/schedules" class="float-right"><i class="fa fa-gear"></i></a>
                <h4 class="card-title mb-0">Schedules</h4>
            </div>
            <div class="card-block p-0 schedule_stat">
            <div class="text-center">Loading..</div>
            </div>
        </div>
    </div>
</div>
<div class="row year-stat">
    <div class="col-md-4">
        <div class="card">
        <div class="text-center first-col">Loading..</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
        <div class="text-center second-col">Loading..</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
        <div class="text-center third-col">Loading..</div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header"> <a href="/_/admin/canvas" class="btn btn-round btn-primary"><i class="fa fa-gear"></i> Manage Canvas</a></div>
    <div class="card-block">
        <div class="row">
            <div class="col-md-4 p-0">
                <div class="card grade_stat">
                <div class="text-center">Loading..</div>
                </div>
            </div>
            <div class="col-md-4 p-0 school_stat">
            <div class="text-center">Loading..</div>
            </div>
            <div class="col-md-4 p-0 district_stat">
            <div class="text-center">Loading..</div>
            </div>
        </div>
        <hr>
        <p><a href="/_/admin/php-info">PHP Info</a></p>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    $(function(){
        $('.dashboard-tbl').DataTable({
            stateSave: false,
            "ordering": false,
            "paging": false,
            "searching": false,
            "info": false
        });
       
        $last_column = '';
        function year_stat(col,stat){
            var $year_cont =  $('.year-stat');
            $.ajax({
                url: '?'+stat+'=1',
                type: 'GET',
                dataType: 'html',
                success: function(content){
                    if(stat == 'nextyear' && content!=''){
                        year_stat('.first-col','prevyear');
                        year_stat('.second-col','curyear');
                    }else if(stat == 'nextyear' && content == ''){
                        year_stat('.first-col','prevprevyear');
                        year_stat('.second-col','prevyear');
                        year_stat('.third-col','curyear');
                    }
                    $year_cont.find(col).html(content);
                }
            });
        }

        function request_stat($container,stat){
            $.ajax({
                url: '?'+stat+'=1',
                type: 'GET',
                dataType: 'html',
                success: function(content){
                    $container.html(content);
                }
            });
        }
        request_stat($('.application_stat'),'application_count');
        request_stat($('.packet_stat'),'packet_count');
        request_stat($('.schedule_stat'),'schedule_count');
        year_stat('.third-col','nextyear');
       
        request_stat($('.grade_stat'),'grade_count');
        request_stat($('.school_stat'),'school_count');
        request_stat($('.district_stat'),'district_count');
    });
</script>