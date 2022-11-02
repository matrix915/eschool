<?php

use mth\yoda\courses;

if(!$user = core_user::getCurrentUser())
{
    die('No Current user');
}
$year = req_get::bool('y') ? mth_schoolYear::getByStartYear(req_get::int('y')) : mth_schoolYear::getCurrent();
if(!$year)
{
    die('No Year selected');
}


core_loader::includeBootstrapDataTables('css');

core_loader::isPopUp();
core_loader::printHeader();

$intervention_id = req_post::is_set('intervention') ? req_post::int('intervention') : req_get::int('intervention');

if(req_get::bool('delete'))
{
    if(($note = mth_intervention_notes::getById(req_get::int('delete'))) && $note->delete())
    {
        core_notify::addMessage('Note deleted');
    } else
    {
        core_notify::addError('Unable to delete note');
    }
    core_loader::redirect('?student=' . req_get::int('student') . '&intervention=' . $intervention_id . '&y=' . $year->getStartYear());
}

if(req_post::is_set('addnote'))
{
    $NOTES = new mth_intervention_notes();
    $NOTES->setInterventionId($intervention_id);
    $NOTES->setUserId($user->getID());
    $NOTES->setNotes(req_post::txt('note'));
    if(!$NOTES->save())
    {
        core_notify::addError('Error Adding Notes');
    } else
    {
        core_notify::addMessage('Notes Added');
    }
    core_loader::redirect('?student=' . req_get::int('student') . '&intervention=' . $intervention_id . '&y=' . $year->getStartYear());
}

if(!($student = mth_student::getByStudentID(req_get::int('student'))))
{
    core_notify::addError('Student Missing ' . $student);
    die();
}


if(!$INTERVENTION = mth_intervention::getByStudent($student, $year))
{

    if($enrollment = courses::getStudentHomeroom($student->getID(), $year))
    {

        $intervention = new mth_intervention();
        $intervention->schoolYear($year->getID());
        $intervention->grade($enrollment->getStudentHomeroomGrade());
        $intervention->zeroCount($enrollment->getStudentHomeroomZeros());
        $intervention->student($student->getID());

        if(!$intervention->save())
        {
            core_notify::addError('Missing Intervention record for ' . $student);
        } else
        {
            $intervention_id = $intervention->getID();
        }
        $INTERVENTION = $intervention;
    } else
    {
        core_notify::addError('Missing Homerrom for ' . $student);
    }
}
$notes_count = 0;
?>
<h2>Notes for <?= $student->getName() ?></h2>
<table id="mth-notes-table" class="table responsive">
    <thead>
    <th>Note</th>
    <th>Created By</th>
    <th>Date</th>
    <th></th>
    </thead>
    <tbody>
    <?php while($result = mth_intervention_notes::each($intervention_id)): ?>
        <?php
        $name = 'UNKNOWN';
        if($user = $result->user())
        {
            $name = $user->getName();
        }
        $notes_count++;
        ?>
        <tr>
            <td><?= $result->getNote() ?></td>
            <td><?= $name ?></td>
            <td data-sort="<?= $result->getCreatedDate() ?>"><?= $result->getCreatedDate('m/d/Y') ?></td>
            <td>
                <a class="btn btn-sm btn-warning edit-note mb-5" href="#" title="Edit"
                   onclick='editNote(<?= $result->getID() ?>)'>
                    <i class="fa fa-pencil"></i>
                </a>
                <a class="btn btn-sm btn-danger delete-note mb-5" title="Delete"
                   href="?student=<?= $student->getID() ?>&intervention=<?= $intervention_id ?>&delete=<?= $result->getID() ?>&y=<?= $year->getStartYear() ?>">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<hr>
<form method="POST" action="?student=<?= $student->getID() ?>&y=<?= $year->getStartYear() ?>">
    <label>Add Note</label>
    <div class="form-group">
        <textarea rows="3" name="note" class="form-control"></textarea>
    </div>

    <input type="hidden" name="intervention" value="<?= $intervention_id ?>">
    <br>
    <button type="submit" name="addnote" id="add-note" class="btn btn-primary btn-round">Add</button>
</form>
<button type="button" class="btn btn-secondary btn-round iframe-close" onclick="closeModal()">Close</button>

<h3>Logs</h3>
<table id="notice-table" class="table responsive">
    <thead>
    <th>Email Sent</th>
    <th>Date Sent</th>
    </thead>
    <tbody>
    <?php while($notice = mth_offensenotif::eachByIntervention($INTERVENTION, $year)): ?>
        <tr>
            <td><?= $notice->getTypeName() ?></td>
            <td data-sort="<?= $notice->getCreatedDate() ?>"><?= $notice->getCreatedDate('m/d/Y') ?></td>
        </tr>
    <?php endwhile; ?>
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
    function closeModal() {
        var $row = top.$DataTable.row('#student-' + top.selected_student);
        var old_data = $row.data();

        var new_notes = top.Interventions.setNotes({
            intervention: top.selected_intervention,
            notes: <?= $notes_count?>,
            id: top.selected_student
        });

        var new_data = $.extend({}, old_data, {
            notes: new_notes
        });

        $row.data(new_data).draw(false);
        top.global_popup_iframe_close('notesPopup');
    }

    function editNote(id) {
        global_popup_iframe("editnotesPopup", "/_/admin/interventions/notes/edit?note=" + id);
    }

    $(function () {
        top.selected_intervention = <?=$intervention_id?>;
        top.selected_student = <?=$student->getID()?>;


        $('#mth-notes-table').dataTable({
            "bPaginate": false,
            columnDefs: [
                {type: 'dateNonStandard', targets: -1},
                {orderable: false, targets: [3]},
            ],
            aaSorting: [[2, 'desc']],
            "info": false,
        });

        $('#notice-table').dataTable({
            "bPaginate": false,
            "searching": false,
            aaSorting: [[1, 'desc']]
        });

    });
</script>