<?php

if (req_get::bool('y')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('y'));
}

if (empty($year)) {
    $year = mth_schoolYear::getCurrent();
}

if (!mth_wooCommerce::isOnline()) {
    core_notify::addError('Unable to connect to WooCommerce!');
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Purchased Courses');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
    <style>
        tr.NotRegistered td {
            color: red;
        }

        tr.Registered td {
            color: green;
        }
    </style>
    
    <div class="card">
        <div class="card-header">
            <p id="dateSelect">
            <select onchange="location.href='?y='+this.value">
                <?php while ($eachYear = mth_schoolYear::each()): ?>
                    <option
                        value="<?= $eachYear->getStartYear() ?>" <?= $year->getID() == $eachYear->getID() ? 'selected' : '' ?>>
                        <?= $eachYear ?>
                    </option>
                <?php endwhile; ?>
            </select>
            </p>
        </div>
        <div class="card-block">
            <table id="mth_purchasedCourses_table" class="table responsive">
                <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Course</th>
                    <th>Parent</th>
                    <th>Student</th>
                    <th>Registered</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($purchasedCourse = mth_purchasedCourse::each($year)): ?>
                    <tr class="<?= $purchasedCourse->student_canvas_enrollment_id() ? 'Registered' : 'NotRegistered' ?>">
                        <td>
                            <a href="https://mytechhigh.com/wp-admin/post.php?post=<?= $purchasedCourse->order_id() ?>&action=edit"
                            target="_blank">
                                #<?= $purchasedCourse->order_id() ?>
                            </a>
                        </td>
                        <td>
                            <?= $purchasedCourse->date_purchased('m/d/Y') ?>
                        </td>
                        <td><?= $purchasedCourse->mth_course() ?></td>
                        <td>
                            <a onclick="global_popup_iframe('mth_people_edit','/_/admin/people/edit?parent=<?= $purchasedCourse->mth_parent_id() ?>')">
                                <?= $purchasedCourse->mth_parent() ?>
                            </a>
                        </td>
                        <td>
                            <a onclick="global_popup_iframe('mth_people_edit','/_/admin/people/edit?student=<?= $purchasedCourse->mth_student_id() ?>')">
                                <?= $purchasedCourse->mth_student() ?>
                            </a>
                        </td>
                        <td><?= $purchasedCourse->student_canvas_enrollment_id() ? 'Registered' : 'Not Registered' ?></td>
                        <td><?= $purchasedCourse->date_registered('m/d/Y') ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            Until the user clicks the link in the email taking them to this site they will not appear on
            this list.
            You can resend the emails through <a href="https://mytechhigh.com/wp-admin/edit.php?post_type=shop_order">wooCommerce</a>.
        </div>
    </div>
    
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    $(function () {
        $('#mth_purchasedCourses_table').dataTable({
            "bStateSave": true,
            "bPaginate": false,
            "aaSorting": [[0, 'desc']]
        });
        // $('#dateSelect').css('margin-bottom', '-50px');
    });
</script>