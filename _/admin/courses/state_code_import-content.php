<?php

if (!empty($_GET['export'])) {
    ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=CourseStateCodes.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Course ID', 'Grade', 'State Code', 'Teacher', 'Subject', 'Course Name'));

    while ($subject = mth_subject::getEach()) {
        while ($course = mth_course::getEach($subject)) {
            if ($course->archived()) {
                continue;
            }
            $minAsInteger = $course->alternativeMinGradeLevel() == 'K' ? 0 : ( $course->alternativeMinGradeLevel() == 'OR K' ? -1 : $course->alternativeMinGradeLevel());
            $altMinAsInteger = $course->alternativeMinGradeLevel() == 'K' ? 0 : ($course->alternativeMinGradeLevel() == 'OR K' ? -1 : $course->alternativeMinGradeLevel());
            $combinedMinGrade = $minAsInteger < $altMinAsInteger ? $minAsInteger : $altMinAsInteger;

            $maxAsInteger = $course->alternativeMaxGradeLevel() == 'K' ? 0 : ( $course->alternativeMaxGradeLevel() == 'OR K' ? -1 : $course->alternativeMaxGradeLevel());
            $altMaxAsInteger = $course->alternativeMaxGradeLevel() == 'K' ? 0 : ( $course->alternativeMaxGradeLevel() == 'OR K' ? -1 : $course->alternativeMaxGradeLevel());
            $combinedMaxGrade = $maxAsInteger < $altMaxAsInteger ? $maxAsInteger : $altMaxAsInteger;
            for ($i = $combinedMinGrade; $i <= $combinedMaxGrade; $i++) {
                $stateCode = mth_coursestatecode::getByGradeAndCourse($i, $course);
                $row = [
                    'Course ID' => $stateCode === null ? (mth_schoolYear::getCurrent()->getDateBegin('Y') . '-' . $course->getID()) :
                        (mth_schoolYear::getByID($stateCode->school_year_id())->getDateBegin('Y') . '-' . $stateCode->course_id()),
                    'Grade' => $i == 0 ? 'K' : $i,
                    'State Code' => $stateCode === null ? '' : $stateCode->state_code(),
                    'Teacher' => $stateCode === null ? '' : $stateCode->teacher_name(),
                    'Subject' => $subject,
                    'Course Name' => $course,
                ];
                fputcsv($output, $row);
            }
        }
    }

    ob_flush();
    fclose($output);
    die;
}

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die();

    if ($_FILES['csv_file']['size'] > 0
        && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK
        && ($handle = fopen($_FILES['csv_file']['tmp_name'], 'r'))
    ) {
        ini_set("auto_detect_line_endings", true);
        $columns = fgetcsv($handle);
        foreach ($columns as &$column) {
            if (stripos($column, 'grade') !== false) {
                $column = 'grade';
            } elseif (stripos($column, 'course') !== false && stripos($column, 'id') !== false) {
                $column = 'mth_course';
            } elseif (stripos($column, 'state') !== false && stripos($column, 'code') !== false) {
                $column = 'state_code';
            } elseif (stripos($column, 'teacher') !== false) {
                $column = 'teacher_name';
            }
        }

        if (!in_array('grade', $columns)
            || !in_array('mth_course', $columns)
            || !in_array('state_code', $columns)
            || !in_array('teacher_name', $columns)
        ) {
            core_notify::addError('CSV file must contain "Course ID" and "Grade", as well as "State Code", "Teacher", or both on the first line');
            core_notify::addError('Submitted column headers: "' . implode('", "', $columns) . '"');
            header('Location: ' . core_path::getPath());
            exit();
        }

        $currentYear = mth_schoolYear::getCurrent()->getDateBegin('Y');
        $createdCount = $createdFailed = $updatedCount = $updatedFailed = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $importArr = array_combine($columns, $row);
            $courseIdParts = explode('-', $importArr['mth_course']);
            $partialYear = $courseIdParts[0];
            $courseId = $courseIdParts[1];

            if (strtolower($importArr['grade']) === 'k') {
                $importArr['grade'] = 0;
            }
            if (!is_numeric($importArr['grade'])) {
                core_notify::addMessage($importArr['mth_course'] . ' ' . $importArr['grade'] . ' is not a valid grade');
                continue;
            }
            if (!$course = mth_course::getByID($courseId)) {
                core_notify::addMessage('Invalid course ID ' . $courseId);
                continue;
            }

            $existingStateCode = mth_coursestatecode::getByGradeAndCourse($importArr['grade'], mth_course::getByID($courseId));
            if (!$importArr['state_code'] && !$importArr['teacher_name'] &&$existingStateCode === null) {
                continue;
            }

            if ($existingStateCode === null ||
                (($existingStateCode->state_code() !== $importArr['state_code'] || $existingStateCode->teacher_name() !== $importArr['teacher_name'])
                    && $partialYear < $currentYear)) {
                $stateCode = new mth_coursestatecode();
                $stateCode->grade($importArr['grade']);
                $stateCode->state_code($importArr['state_code']);
                $stateCode->teacher_name($importArr['teacher_name']);
                $stateCode->subject_id($course->subjectID());
                $stateCode->course_id($courseId);
                if ($stateCode->save()) {
                    $createdCount++;
                } else {
                    $createdFailed++;
                }
            } else if ($existingStateCode->state_code() !== $importArr['state_code'] || $existingStateCode->teacher_name() !== $importArr['teacher_name']) {
                $existingStateCode->teacher_name($importArr['teacher_name']);
                $existingStateCode->state_code($importArr['state_code']);
                if ($existingStateCode->save()) {
                    $updatedCount++;
                } else {
                    $updatedFailed++;
                }
            }
        }
        if ($updatedCount) core_notify::addMessage($updatedCount . ' State codes updated');
        if ($updatedFailed) core_notify::addMessage($updatedFailed . ' State codes update failed');
        if ($createdCount) core_notify::addMessage($createdCount . ' State codes created');
        if ($createdFailed) core_notify::addMessage($createdFailed . ' State codes create failed');
        if (!$updatedCount && !$updatedFailed && !$createdCount && !$createdFailed) {
            core_notify::addMessage('No changes made!');
        }
    }
    exit('<html><script>top.location.href="/_/admin/courses"</script></html>');
}

core_loader::isPopUp();
core_loader::printHeader();
?>
<button  type="button" class="btn btn-round btn-secondary iframe-close" onclick="top.global_popup_iframe_close('state_code-import_popup')">Close</button>
<form id="importForm" action="?form=<?= uniqid('state_code_import_form') ?>" method="post"
      enctype="multipart/form-data">
    <p>
        <label for="csv_file">CSV File</label>
        <input id="csv_file" name="csv_file" type="file">
        <div class="alert alert-info bg-info">
            First row will be used as the column headers, and expects "Course ID", "Grade", and "State Code"
        </div>
    </p>
    <p>
        <button type="submit" class="btn btn-primary btn-round" onclick="global_waiting()">Import</button>
    </p>
</form>

<form action="?export=1" method="post">
    <button type="submit" class="btn btn-warning btn-round">Export Current State Codes</button>
</form>

<?php
core_loader::printFooter();
