<?php

if (!empty($_GET['subject_courses'])) {
    if (!($subject = mth_subject::getByID($_GET['subject_courses']))) {
        die('<p>Subject not found</p>');
    }
    $year = mth_schoolYear::getCurrent();
    ?>
    <table class="formatted">
        <?php while ($course = mth_course::getEach($subject)):
            if(!$_SESSION['show_archived_items'] && $course->archived()) continue;?>
            <tr class="<?= ($course->available() ? 'mth_course-available' : 'mth_course-unavailable')
                . ($course->archived() ? ' archived' : '') ?>">
                <td><a onclick="editCourse(<?= $course->getID() ?>)"><?= $course->title() ?></a></td>
                <td style="white-space: nowrap; color:#999;">
                    <small><?= $year ? $year->getStartYear() . '-' . $course->getID() : '' ?></small>
                </td>
                <td style="white-space: nowrap">
                    <small>
                        (grades <?= $course->minGradeLevel() . ($course->minGradeLevel() != $course->maxGradeLevel() ? '-' . $course->maxGradeLevel() : '') ?>
                        )
                    </small>
                </td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <td>
                <a onclick="global_popup_iframe('mth_course-edit','/_/admin/courses/course?subject=<?= $subject->getID() ?>')"><i>
                        New Course</i></a></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <?php
}


if (!empty($_GET['provider_courses'])) {
    if (!($provider = mth_provider::get($_GET['provider_courses']))) {
        die('Provider not found');
    }
    ?>
    <table class="formatted">
        <?php while ($course = mth_provider_course::each($provider)):
            if(!$_SESSION['show_archived_items'] && $course->archived()) continue;?>
            <tr class="<?= ($course->available() ? 'mth_course-available' : 'mth_course-unavailable')
            . ($course->archived() ? ' archived' : '') ?>">
                <td><a class="<?= ($course->unarchived_mth_course_ids() === [] && $course->mth_course_ids()) ? 'archived_linked' : '' ?>"
                       title="<?= ($course->unarchived_mth_course_ids() === [] && $course->available() && $course->mth_course_ids()) ? 'Exclusively mapped to an archived course' : '' ?>"
                       onclick="editProviderCourse(<?= $course->id() ?>)"><?= $course->title() ?></a></td>
                <td class="mth_provider_course-map">
                    <?php while ($mth_course = $course->eachCourse()): ?>
                        <small class="<?= $mth_course->archived() ? ' archived_linked' : '' ?>"><?= $mth_course->title() ?></small>
                    <?php endwhile; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <td>
                <a onclick="global_popup_iframe('mth_provider_course-edit','/_/admin/courses/provider_course?provider_id=<?= $provider->id() ?>')"><i>New
                        Course</i></a></td>
        </tr>
    </table>
    <?php
}


if (!empty($_GET['course_options_in_subject'])) {
    if (!($subject = mth_subject::getByID($_GET['course_options_in_subject']))) {
        die('<option value="">Subject not found</option>');
    }
    while ($course = mth_course::getEach($subject)) {
        ?>
        <option value="<?= $course->getID() ?>"
            <?= $course->getID() == @$_GET['slected_course'] ? 'selected' : '' ?>><?= $course->title() ?></option>
        <?php
    }
}