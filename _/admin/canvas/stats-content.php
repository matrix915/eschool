<?php

?>
<h4 style="margin-bottom: 0">Mapped Courses</h4>
<table class="formatted">
    <?php foreach (mth_canvas_course::wsCounts() as $ws => $count): ?>
        <tr>
            <th><?= ucwords(mth_canvas_course::workflow_state_label($ws)) ?></th>
            <td><?= number_format($count) ?>
        </tr>
    <?php endforeach; ?>
</table>
<h4 style="margin-bottom: 0">
    <a onclick="global_popup_iframe('mth_canvas_users-popup','/_/admin/canvas/users', true)">Users</a>
</h4>
<table class="formatted">
    <?php if (($updated = mth_canvas_user::count(NULL, false))): ?>
        <tr>
            <th>Up-to-date</th>
            <td><?= $updated ?></td>
        </tr>
    <?php endif; ?>
    <?php if (($toUpdate = mth_canvas_user::count(NULL, true))): ?>
        <tr>
            <th>To-be-updated</th>
            <td><?= $toUpdate ?></td>
        </tr>
    <?php endif; ?>
</table>
<h4 style="margin-bottom: 0">
    <a onclick="global_popup_iframe('mth_canvas_enrollments-popup','/_/admin/canvas/enrollments', true)">Enrollments</a>
</h4>
<table class="formatted">
    <?php if (($created = mth_canvas_enrollment::count(NULL, true))): ?>
        <tr>
            <th>Created</th>
            <td><?= $created ?></td>
        </tr>
    <?php endif; ?>
    <?php if (($toCreate = mth_canvas_enrollment::count(NULL, false))): ?>
        <tr>
            <th>To be created</th>
            <td><?= $toCreate ?></td>
        </tr>
    <?php endif; ?>
</table>