<?php
if(!$userId = core_user::getUserID())
{
    die('No Current user');
}

if(req_get::bool('form'))
{
    $note = new mth_intervention_notes();
    $note->setInterventionId(req_post::int('interventionid'));
    if(!core_loader::formSubmitable('note_edit_form_' . req_get::txt('form')))
    {
        exit;
    }
    $note->setNotes(req_post::txt('noteContent'));
    $note->setUserId($userId);
    if(!$note->save())
    {
        core_notify::addError('Error Editing Notes');
    } else
    {
        core_notify::addMessage('Note Saved');
    }

    echo '<script>parent.location.reload()</script>';
    exit;
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<form method="POST" action="?form=<?= time() ?>" id="new-note">
    <input type="hidden" class="form-control" id="interventionid" name="interventionid"
           value="<?=req_get::int('interventionid')?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Note</h3>
        </div>
        <div class="card-block">
            <div class="form-group">
                <textarea rows="3" id="noteContent" name="noteContent" class="form-control"></textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="createNote" id="createNote" class="btn btn-primary btn-round">Save</button>
        </div>
    </div>
</form>
<button type="button" class="btn btn-secondary btn-round iframe-close" onclick="closeModal()">Cancel</button>
<?php
core_loader::printFooter();
?>
<script>
    function closeModal() {
        parent.global_popup_iframe_close('createNotesPopup');
    }
</script>