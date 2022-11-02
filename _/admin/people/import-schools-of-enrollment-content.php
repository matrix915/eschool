<?php
core_user::getUserID() === 1 || die('Must be perfomed by User 1');

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die();
    if ($_FILES['csv']['size'] > 0
        && $_FILES['csv']['error'] === UPLOAD_ERR_OK
        && ($handle = fopen($_FILES['csv']['tmp_name'], 'r'))
        && ($year = mth_schoolYear::getByID($_POST['year']))
    ) {
        $columns = fgetcsv($handle);
        $updated = 0;
        $notfound = 0;
        $invalid = 0;
        foreach ($columns as &$column) {
            switch (true) {
                case stripos($column, 'student') !== FALSE && stripos($column, 'email') !== FALSE:
                    $column = 'student email';
                    break;
                case stripos($column, 'school') !== FALSE:
                    $column = 'school';
                    break;
            }
        }
        if (!in_array('student email', $columns)
            || !in_array('school', $columns)
        ) {
            core_notify::addError('CSV must contain "student email" and  "school"');
            core_notify::addError('Submitted column headers: ' . implode(', ', $columns));
        } else {
            while (($row = fgetcsv($handle)) !== FALSE) {
                $importArr = array_combine($columns, $row);
                if (!$student = mth_student::getByEmail($importArr['student email'])) {
                    $notfound++;
                    continue;
                }
                if (!($SOE = \mth\student\SchoolOfEnrollment::get(trim($importArr['school'])))) {
                    $invalid++;
                    continue;
                }
                $student->setSchoolOfEnrollment($SOE, $year);
                $updated++;
            }
            core_notify::addMessage($updated . ' Students Updated');
            core_notify::addMessage($notfound . ' Students Not Found');
            core_notify::addMessage($invalid . ' Invalid Schools Provided (not updated)');
        }
    } else {
        core_notify::addError('No file uploaded or invalid year provided.');
    }
    header('Location: ' . core_path::getPath() . '?importRan=1');
    exit();
} else {
    core_notify::addError('This script will overwrite any previous Schools of Enrollment for the specified year');
}

core_loader::isPopUp();
core_loader::printHeader();
?>

    <form id="importForm" action="?form=<?= uniqid('school_import_form') ?>" method="post"
          enctype="multipart/form-data">
        <p>
            <label class="checkbox-block-label">
                School Year:
            </label>
            <select name="year">
                <?php $currentYear = mth_schoolYear::getCurrent() ?>
                <?php foreach (mth_schoolYear::getSchoolYears() as $year): /* @var $year mth_schoolYear */ ?>
                    <option
                        value="<?= $year->getID() ?>" <?= $year->getID() == $currentYear->getID() ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="csv">CSV file
                <small>(only "Student Email" and "School" headers will be used)</small>
            </label>
            <input type="file" name="csv" id="csv">
        </p>
        <p>
            <input type="submit" value="Submit">
        </p>
    </form>
<?php
core_loader::printFooter();