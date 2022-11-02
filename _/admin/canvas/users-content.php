<?php
if (req_get::bool('pull')) {
    mth_canvas_user::pull();
    core_loader::redirect();
}

core_loader::includeDataTables();

core_loader::isPopUp();
core_loader::printHeader();
?>
    <input type="button" value="Close" class="iframe-close"
           onclick="parent.global_popup_iframe_close('mth_canvas_users-popup')">
    <script>
        $(function () {
            $('#canvas_user_table').dataTable({
                "bStateSave": true,
                "bPaginate": false
            });
        });
    </script>
    <h2>Canvas Users</h2>
    <a href="?pull=1">Pull</a>
    <table class="formatted" id="canvas_user_table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Person ID</th>
            <th>Canvas User ID</th>
            <th>To Be Pushed</th>
        </tr>
        </thead>
        <?php while ($canvas_user = mth_canvas_user::each(NULL, NULL)): ?>
            <tr>
                <td><?= $canvas_user->person()->getName() ?></td>
                <td><?= $canvas_user->person()->getEmail() ?></td>
                <td><?= $canvas_user->person()->getPersonID() ?></td>
                <td><?= $canvas_user->id() ?></td>
                <td><?= $canvas_user->to_be_pushed() ? 'To Be Pushed' : '' ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php
core_loader::printFooter();