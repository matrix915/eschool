<?php
core_loader::includeBootstrapDataTables('css');

core_loader::isPopUp();
core_loader::printHeader();
if(!$student = mth_student::getByStudentID(req_get::int('student'))){
    die('Unable to get Student');
}
$intervention_id = req_get::int('intervention');

if(!$INTERVENTION  = mth_intervention::getByID($intervention_id)){
    die('Unable to get intervention');
}

if(req_get::bool('delete')){
    if(($note = mth_intervention_notes::getById(req_get::int('delete'))) && $note->delete()){
        core_notify::addMessage('Note deleted');
    }else{
        core_notify::addError('Unable to delete note');
    }
    core_loader::redirect('?student='.req_get::int('student').'&intervention='.$intervention_id);
}
?>
<button type="button" class="btn btn-secondary btn-round iframe-close" onclick="closeModal()">Close</button>
<h2>Notes for <?= $student->getName()?></h2>
<table id="mth-notes-table" class="table responsive">
    <thead>
        <th>Note</th>
        <th>Created By</th>
        <th>Date</th>
        <th></th>
    </thead>
    <tbody>
        <?php while($result = mth_intervention_notes::each($intervention_id)):?>
            <?php
                $name = 'UNKNOWN';
                if($user = $result->user())
                {
                    $name = $user->getName();
                }
            ?>
            <tr>
                <td><?= $result->getNote()?></td>
                <td><?= $name?></td>
                <td data-sort="<?=$result->getCreatedDate()?>"><?= $result->getCreatedDate('m/d/Y')?></td>
                <td>
                    <a class="btn btn-sm btn-warning edit-note mb-5" href="#" title="Edit"
                       onclick='editNote(<?= $result->getID() ?>)'>
                        <i class="fa fa-pencil"></i>
                    </a>
                    <a class="btn btn-sm btn-danger delete-note mb-5" title="Delete"
                       href="?student=<?= $student->getID() ?>&intervention=<?= $intervention_id ?>&delete=<?= $result->getID() ?>">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endwhile;?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-round btn-sm" id="new_note">Add</button>
                </td>
            </tr>
    </tbody>
</table>
<hr>
<h3>Logs</h3>
<table id="notice-table" class="table responsive">
    <thead>
        <th>Email Sent</th>
        <th>Date Sent</th>
    </thead>
    <tbody>
        <?php while($notice = mth_offensenotif::eachByIntervention($INTERVENTION,$INTERVENTION->getSchoolYear())):?>
                <tr>
                    <td><?= $notice->getTypeName()?></td>
                    <td data-sort="<?=$notice->getCreatedDate()?>"><?= $notice->getCreatedDate('m/d/Y')?></td>
                </tr>
        <?php endwhile;?>
    </tbody>
</table>
<h3>Teacher Notes</h3>
<table id="teacher-notes-table">
    <tbody>
    <tr>
        <td><?= $student->getTeacherNotes($INTERVENTION->schoolYear()) ?></td>
    </tr>
    </tbody>
</table>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>
<script>
    function editNote(id){
        global_popup_iframe("editnotesPopup", "/_/admin/interventions/notes/edit?note="+id);
    }
    $('#new_note').click(function (){
        global_popup_iframe("editnotesPopup", "/_/admin/interventions/notes/create?interventionid=<?=$intervention_id?>");
    })
    function closeModal(){
        parent.global_popup_iframe_close('notesPopup');
    }
    $(function(){
        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
                'dateNonStandard-asc': function (a, b) {
                var x = Date.parse(a);
                var y = Date.parse(b);
                if (x == y) { return 0; }
                if (isNaN(x) || x < y) { return 1; }
                if (isNaN(y) || x > y) { return -1; }
                },
                'dateNonStandard-desc': function (a, b) {
                var x = Date.parse(a);
                var y = Date.parse(b);
                if (x == y) { return 0; }
                if (isNaN(y) || x < y) { return -1; }
                if (isNaN(x) || x > y) { return 1; }
                }
        });

        $('#mth-notes-table').dataTable({
                "bPaginate": false,
                columnDefs: [
                    { type: 'dateNonStandard', targets: -1 }
                ],
                aaSorting: [[2, 'desc']],
                "info": false
        });

        $('#notice-table').dataTable({
                "bPaginate": false,
                "searching": false,
                aaSorting: [[1, 'desc']]
        });
        
    });
</script>