<?php
if(!$user = core_user::getCurrentUser())
{
    die('No Current user');
}

if(!($year = mth_schoolYear::getCurrent()))
{
    die('No Year selected');
}


if(!req_get::bool('note') || !($note = mth_intervention_notes::getById(req_get::int('note'))))
{
    die('Note not found');
}

if(req_get::bool('form'))
{
    if(!core_loader::formSubmitable('note_edit_form_' . req_get::txt('form')))
    {
        exit();
    }
    $note->setNotes(req_post::txt('content'));
    $note->setUserId($user->getID());
    if(!$note->save())
    {
        core_notify::addError('Error Editing Notes');
    } else
    {
        core_notify::addMessage('Note Saved');
    }
    echo '<script>';
    echo "parent.location.reload();";
    echo '</script>';
    exit;
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<form method="POST" action="?note=<?= $note->getID() ?>>&form=<?= time() ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Edit Note
            </h3>
        </div>
        <div class="card-block">
            <div class="form-group">
                <textarea rows="3" name="content" class="form-control"><?= $note->getNote(); ?></textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="savenote" id="add-note" class="btn btn-primary btn-round">Save</button>
        </div>
    </div>
</form>
<button type="button" class="btn btn-secondary btn-round iframe-close" onclick="closeModal()">Close</button>
<?php
core_loader::printFooter();
?>
<script>
    function closeModal() {
        parent.global_popup_iframe_close('editnotesPopup');
    }
</script>