<?php

if(!$student = mth_student::getByStudentID(req_get::int('student'))){
    die('No Student Selected');
}

$year = null;
if(req_get::is_set('year')){
    $year = mth_schoolYear::getByStartYear(req_get::int('year'));
}
if(!$year){
    ($year = mth_schoolYear::getCurrent()) || die('No year defined');
}

if(req_get::bool('delete_request_value')){
    if(($req = mth_resource_request::getById(req_get::int('delete_request_value')))){
        if($req->delete()){
            core_notify::addMessage('Request deleted.');
        }else{
            core_notify::addError('Unable to delete request.');
        }
      
    }else{
        core_notify::addError('Unable to find request.');
    }
    core_loader::redirect('?parent='.req_get::int('parent').'&year='.req_get::txt('year'));
    exit;
}
core_loader::isPopUp();
// core_loader::includeBootstrapDataTables('css');
core_loader::addClassRef('family-resources');
core_loader::printHeader();
?>
 <button type="button" class="iframe-close btn btn-round btn-secondary" onclick="closeHistory()">Close</button>
 <div class="card">
     <div class="card-header">
        <select onchange="changeYear(this)" title="School Year" autocomplete="off"  class="form-control">
            <?php foreach(mth_schoolYear::getSchoolYears(NULL,time()) as $each_year){ ?>
                <option value="<?=$each_year->getStartYear()?>"
                    <?=$each_year->getID()==$year->getID()?'selected':''?>><?=$each_year?></option>
            <?php } ?>
        </select>
     </div>
     <div class="card-block">
        <table class="table table-stripped responsive resource-tbl">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student First Name</th>
                    <th>Student Last name</th>
                    <th>Student Email</th>
                    <th>Parent First Name</th>
                    <th>Parent Last Name</th>
                    <th>Parent Email</th>
                    <th>Student Grade Level</th>
                    <th>Vendor</th>
                    <th>Date Requested</th>
                    <th><?=$year?> Student Status</th>
                    <th>Withdrawn /Graduated Date if applicable</th>
                </tr>
            </thead>
            <tbody>
                 <?php
                 $resource = new mth_resource_request();
                 $resource->whereStudentId([$student->getID()]);
                 $resource->whereYearId([$year->getID()]);
                 ?>
                <?php while($request = $resource->query()):?>
                <?php
                if(!($parent = $student->getParent())){
                    continue;
                }
                $status = in_array($student->getStatus($year),[mth_student::STATUS_WITHDRAW,mth_student::STATUS_GRADUATED])?$student->getStatusDate($year, 'm/d/Y'):'';
                $row =  [
                    $student->getID(),
                    $student->getFirstName(),
                    $student->getLastName(),
                    $student->getEmail(),
                    $parent->getPreferredFirstName(),
                    $parent->getPreferredLastName(),
                    $parent->getEmail(),
                    $student->getGradeLevel(),
                    $request->getResource(),
                    $request->createDate('Y-m-d H:i:s'),
                    $student->getStatusLabel($year),
                    $status
                ];
                ?>
                <tr>
                <?php foreach($row as $d):?>
                    <td><?=$d?></td>
                <?php endforeach;?>
                </tr>
                <?php endwhile;?>
            </tbody>
        </table>
    </div>
</div>      
<?php
// core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>
<script>
    function changeYear($this){
        location='?year='+$this.value+'&student='+'<?=req_get::int('student')?>';
    }
    function closeHistory(){
        top.global_popup_iframe_close('reportPopup');
    }
</script>