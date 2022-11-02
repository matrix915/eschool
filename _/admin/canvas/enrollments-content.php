<?php
if (req_get::bool('pull')) {
    mth_canvas_enrollment::pull();
    core_loader::redirect();
}

core_loader::includeDataTables();

core_loader::isPopUp();
core_loader::printHeader();
?>
    <input type="button" value="Close" class="iframe-close"
           onclick="parent.global_popup_iframe_close('mth_canvas_enrollments-popup')">
    <script>
        $(function () {
            $('#canvas_enrollment_table').dataTable({
                "bStateSave": true,
                "bPaginate": false
            });
        });
    </script>
    <h2>Canvas Enrollments</h2>
    <a href="?pull=1">Pull</a>
    <table class="formatted" id="canvas_enrollment_table">
        <thead>
        <tr>
            <th>Person Name</th>
            <th>Email</th>
            <th>Canvas User ID</th>
            <th>Course Name</th>
            <th>Canvas Course ID</th>
            <th>Enrollment ID</th>
        </tr>
        </thead>
        <?php while ($enrollment = mth_canvas_enrollment::each(NULL, NULL)): ?>
            <?php if (!($canvas_user = $enrollment->canvas_user())) {
                continue;
            } ?>
            <?php if (!($canvas_course = $enrollment->canvas_course())) {
                continue;
            } ?>
            <tr>
                <td><?= $canvas_user->person()->getName() ?></td>
                <td><?= $canvas_user->person()->getEmail() ?></td>
                <td><?= $canvas_user->id() ?></td>
                <td><?= $canvas_course->mth_course()->title() ?></td>
                <td><?= $canvas_course->id() ?></td>
                <td><?= $enrollment->id() ? $enrollment->id() : 'To be created' ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php
core_loader::printFooter();