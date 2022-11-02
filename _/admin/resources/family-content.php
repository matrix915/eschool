<?php

if(!$parent = mth_parent::getByParentID(req_get::int('parent'))){
    die('No Parent Selected');
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
            <?php foreach(mth_schoolYear::getSchoolYears() as $each_year){ ?>
                <option value="<?=$each_year->getStartYear()?>"
                    <?=$each_year->getID()==$year->getID()?'selected':''?>><?=$each_year?></option>
            <?php } ?>
        </select>
     </div>
     <div class="card-block">
        <table class="table table-stripped responsive resource-tbl">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Requests</th>
                    <th>Requested</th>
                </tr>
            </thead>
            <tbody>
                <?php while($req = mth_resource_request::get($parent,$year)):?>
                <tr>
                    <td><?=$req->student()?></td>
                    <td>
                        <?=$req->getResource()?>
                    </td>
                    <td>
                        <?=$req->createDate('m/d/Y')?>
                    </td>
                    <td>
                        <a class="btn btn-danger remove-vendor" data-id="<?=$req->getID()?>" title="Delete" href="#"><i class="fa fa-trash"></i></a>
                    </td>
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
        location='?year='+$this.value+'&parent='+'<?=req_get::int('parent')?>';
    }
    function closeHistory(){
        top.global_popup_iframe_close('mth_resource-show');
    }

    $(function(){
        $('.remove-vendor').click(function(){
            var req_id = $(this).data('id');
            swal({
                title: "",
                text: "Are you sure you want to delete this request?",
                type: "warning",
                showCancelButton: !0,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function () {
                location.href = '?delete_request_value=' + req_id+'&parent='+'<?=req_get::int('parent')?>'+'&year='+<?=$year->getStartYear();?>
            });
        });
    });
</script>