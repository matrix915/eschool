<?php

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die();

    if ($_FILES['csv_file']['size'] > 0
        && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK
        && ($handle = fopen($_FILES['csv_file']['tmp_name'], 'r'))
    ) {
        ini_set("auto_detect_line_endings", true);
        $columns = fgetcsv($handle);
        foreach ($columns as &$column) {
            if (stripos($column, 'subject') !== FALSE) {
                $column = 'mth_subject';
            } elseif (stripos($column, 'mth') !== FALSE && stripos($column, 'course') !== FALSE) {
                $column = 'mth_course';
            } elseif (stripos($column, 'course') !== FALSE) {
                $column = 'course';
            } elseif (stripos($column, 'provider') !== FALSE) {
                $column = 'provider';
            }
        }
        if (!in_array('mth_subject', $columns)
            || !in_array('mth_course', $columns)
            || !in_array('course', $columns)
            || !in_array('provider', $columns)
        ) {
            core_notify::addError('CSV file must contain "Provider", "Course Title", "MTH Subject", "MTH Course Title" on the first line');
            core_notify::addError('Submitted column headers: "' . implode('", "', $columns) . '"');
            header('Location: ' . core_path::getPath());
            exit();
        }
        $missingProviders = array();
        $missingMTHcourses = array();
        $failed = array();
        $importCount = 0;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $importArr = array_combine($columns, $row);
            if (!($provider = mth_provider::getByName($importArr['provider']))) {
                if (!in_array($importArr['provider'], $missingProviders)) {
                    $missingProviders[] = $importArr['provider'];
                }
                continue;
            }
            if (!($course = mth_course::getByTitle($importArr['mth_course']))
                || !($subject = mth_subject::getByName($importArr['mth_subject']))
                || $course->subjectID() != $subject->getID()
            ) {
                if (!in_array($importArr['mth_subject'] . ' - ' . $importArr['mth_course'], $missingMTHcourses)) {
                    $missingMTHcourses[] = $importArr['mth_subject'] . ' - ' . $importArr['mth_course'];
                }
                continue;
            }
            if (!$providerCourse = mth_provider_course::getByTitle($provider, $importArr['course'])) {
                $providerCourse = new mth_provider_course();
            }
            $providerCourse->provider($provider);
            $providerCourse->title($importArr['course']);
            $providerCourse->addToMap($course);
            $providerCourse->available(true);
            if (!$providerCourse->save()) {
                core_notify::addError('<b>Unable to save provider course</b> - ' . $importArr['provider'] . ' - ' . $importArr['course'] . ' - ' . $importArr['mth_course']);
                continue;
            }
            $importCount++;
        }
        core_notify::addMessage($importCount . ' Provider Courses Imported');
        if (!empty($missingProviders)) {
            core_notify::addError('<b>The following providers were not found:</b> <br>' . implode('<br>', $missingProviders));
        }
        if (!empty($missingMTHcourses)) {
            core_notify::addError('<b>The following MTH courses were not found:</b> <br>' . implode('<br>', $missingMTHcourses));
        }
    }
    exit('<html><script>top.location.href="/_/admin/courses"</script></html>');
}

core_loader::isPopUp();
core_loader::printHeader();
?>
    <button  type="button" class="btn btn-round btn-secondary iframe-close" onclick="top.global_popup_iframe_close('mth_provider_course-import_popup')">Close</button>
    <form id="importForm" action="?form=<?= uniqid('provider_course_import_form') ?>" method="post"
          enctype="multipart/form-data">
        <p>
            <label for="csv_file">CSV File</label>
            <input id="csv_file" name="csv_file" type="file">
            <div class="alert alert-info bg-info">
                First row will be used as the column headers, and expects "Provider", "Course Title", "MTH Subject",
                "MTH Course Title"
            </div>
        </p>
        <p>
            <button type="submit" class="btn btn-primary btn-round">Import</button>
        </p>
    </form>
<?php
core_loader::printFooter();