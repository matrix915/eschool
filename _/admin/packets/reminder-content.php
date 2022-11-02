<?php

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die();

    $failures = false;
    foreach (mth_packet::get($_POST['packets']) as $packet) {
        /* @var $packet mth_packet */
        $student = $packet->getStudent();
        $parent = $student->getParent();
        if ( !$parent ) {
            continue;
        }
        $email = new core_emailservice();
        $success = $email->send(
            array($parent->getEmail()),
            $_POST['subject'],
            str_replace(
                array(
                    '[PARENT]',
                    '[STUDENT]',
                    '[DEADLINE]',
                    '[LINK]'
                ),
                array(
                    $parent->getPreferredFirstName(),
                    $student->getPreferredFirstName(),
                    $packet->getDeadline('F j, Y'),
                    '<a href="'.$_SERVER['SERVER_NAME'].'/student/'.$student->getSlug().'/packet'.'">'.$_SERVER['SERVER_NAME'].'/student/'.$student->getSlug().'/packet'.'</a>'
                ),
                $_POST['content']),
            null,
            [core_setting::getSiteEmail()->getValue()]
        );

        if (!$success) {
            core_notify::addError('Unable to send email to ' . $parent);
            $failures = true;
        }
    }
    if (!$failures) {
        core_notify::addMessage('Message sent succesfully');
    }
    exit('<html><script>top.location=top.location</script></html>');
}

core_loader::includeCKEditor();

cms_page::setPageTitle('Send Reminder');
core_loader::isPopUp();
core_loader::printHeader();

if (!empty($_GET['packets'])):
    ?>
    <form action="?form=<?= uniqid('reminder_email_form_') ?>" method="post">
        <fieldset>
            <legend>To:
                <small style="display: inline; color: #ccc;">parent (student)</small>
            </legend>
            <?php foreach (mth_packet::get($_GET['packets']) as $packet): /* @var $packet mth_packet */ ?>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" value="<?= $packet->getID() ?>" name="packets[]" checked>
                    <label for="packet-<?= $packet->getID() ?>">
                        <?= $packet->getStudent()->getParent() ?> (<?= $packet->getStudent()->getPreferredFirstName() ?>)
                        
                    </label>
                </div>
               
            <?php endforeach; ?>
        </fieldset>
        <fieldset class="form-group">
            <legend>Subject</legend>
            <input type="text" class="form-control" name="subject" value="<?= core_setting::get('ManualPacketReminderSubject', 'Packets')->getValue(); ?>">
        </fieldset>
        <textarea name="content" id="emailContent">
            <?= core_setting::get('ManualPacketReminderContent', 'Packets')->getValue(); ?>
        </textarea>
        <script>
            CKEDITOR.config.removePlugins = "image,forms,youtube,iframe,print,stylescombo,table,tabletools,undo,specialchar,removeformat,pastefromword,pastetext,smiley,font,clipboard,selectall,format,blockquote,div,resize,elementspath,find,maximize,showblocks,sourcearea,scayt,colorbutton,about,wsc,justify,bidi,horizontalrule";
            CKEDITOR.config.removeButtons = "Subscript,Superscript,Anchor";
            $('#emailContent').ckeditor();
        </script>
        <table style="font-size: 10px; color: #999">
            <tr>
                <td>[PARENT]</td>
                <td>Parent's first name</td>
            </tr>
            <tr>
                <td>[STUDENT]</td>
                <td>Student's first name</td>
            </tr>
            <tr>
                <td>[DEADLINE]</td>
                <td>The packet deadline in the format: <?= date('F j, Y') ?></td>
            </tr>
            <tr>
                <td>[LINK]</td>
                <td>The link for the parent to access student's packet</td>
            </tr>
        </table>
        <br>
        <p>
            <button type="submit" class="btn btn-primary btn-round">Send</button>
            <button type="button" onclick="top.global_popup_iframe_close('mth_packet_reminder')" class="btn btn-round btn-secondary" >Cancel</button>
        </p>
    </form>
    <?php
else:
    ?>
    <div class="alert  bg-info">Please select which packets you would like to send a reminder for.</div>
    <p>
        <button type="button" onclick="top.global_popup_iframe_close('mth_packet_reminder')" class="btn btn-round btn-secondary">Close</button>
    </p>
    <?php
endif;
core_loader::printFooter();