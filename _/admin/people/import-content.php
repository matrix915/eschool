<?php
core_user::getUserID() === 1 || die('Must be perfomed by User 1');

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die();

//  if(!empty($_POST['delete'])){
//    $search = new mth_person_filter();
//    $deletedStudents = array();
//    $deletedParents = array();
//    foreach ($search->getStudents() as $student) {
//      /* @var $student mth_student */
//      $deletedStudents[] = $student->delete(true);
//    }
//    foreach($search->getParents() as $parent){
//      /* @var $parent mth_parent */
//      if($parent->getUserID() 
//              && ($user = core_user::getUserById($parent->getUserID()))
//              && $user->isAdmin()){
//        continue;
//      }
//      $deletedParents[] = $parent->delete();
//    }
//    mth_phone::deleteOrphaned();
//    mth_address::deleteOrphaned();
//    core_notify::addMessage(count(array_filter($deletedStudents)).' Students and '.count(array_filter($deletedParents)).' Parentes deleted');
//  }

    if ($_FILES['csv']['size'] > 0
        && $_FILES['csv']['error'] === UPLOAD_ERR_OK
        && ($handle = fopen($_FILES['csv']['tmp_name'], 'r'))
    ) {
        $columns = fgetcsv($handle);
        $matches = array();
        $year = mth_schoolYear::getByID(req_post::int('school_year'));
        $importedParents = 0;
        $importedStudents = 0;
        foreach ($columns as &$column) {
            switch (true) {
                case stripos($column, 'parent') !== FALSE && stripos($column, 'first') !== FALSE:
                    $column = 'parent first';
                    break;
                case stripos($column, 'parent') !== FALSE && stripos($column, 'last') !== FALSE:
                    $column = 'parent last';
                    break;
                case stripos($column, 'secondary') !== FALSE && stripos($column, 'first') !== FALSE:
                    $column = 'secondary first';
                    break;
                case stripos($column, 'secondary') !== FALSE && stripos($column, 'last') !== FALSE:
                    $column = 'secondary last';
                    break;
                case stripos($column, 'parent') !== FALSE && stripos($column, 'email') !== FALSE:
                    $column = 'parent email';
                    break;
                case stripos($column, 'secondary') !== FALSE && stripos($column, 'email') !== FALSE:
                    $column = 'secondary email';
                    break;
                case stripos($column, 'phone') !== FALSE && stripos($column, 'student') === FALSE && stripos($column, 'secondary') === FALSE:
                    $column = 'parent phone';
                    break;
                case stripos($column, 'phone') !== FALSE && stripos($column, 'secondary') !== FALSE:
                    $column = 'secondary phone';
                    break;
                case stripos($column, 'student') !== FALSE && stripos($column, 'first') !== FALSE:
                    $column = 'student first';
                    break;
                case stripos($column, 'student') !== FALSE && stripos($column, 'last') !== FALSE:
                    $column = 'student last';
                    break;
                case stripos($column, 'student') !== FALSE && stripos($column, 'email') !== FALSE:
                    $column = 'student email';
                    break;
                case stripos($column, 'grade') != FALSE:
                    $column = 'grade';
                    break;
                case stripos($column, 'city') !== FALSE:
                    $column = 'city';
                    break;
                case stripos($column, 'address') !== FALSE || stripos($column, 'street') !== FALSE:
                    $column = 'street';
                    break;
                case stripos($column, 'zip') !== FALSE:
                    $column = 'zip';
                    break;
                case stripos($column, 'state') !== FALSE:
                    $column = 'state';
                    break;
                case stripos($column, 'gender') !== FALSE:
                    $column = 'gender';
                    break;
                case stripos($column, 'dob') !== FALSE
                    || (stripos($column, 'birth') !== FALSE
                        && (stripos($column, 'date') !== FALSE
                            || stripos($column, 'day') !== FALSE)):
                    $column = 'dob';
                    break;
                case stripos($column, 'school') !== FALSE && stripos($column, 'last') === FALSE:
                    $column = 'school';
                    break;
                case stripos($column, 'school') !== FALSE && stripos($column, 'last') !== FALSE:
                    $column = 'last school';
                    break;
                case stripos($column, 'grade') !== FALSE:
                    $column = 'grade';
                    break;
                case stripos($column, 'hisp') !== FALSE:
                    $column = 'hispanic';
                    break;
                case stripos($column, 'race') !== FALSE:
                    $column = 'race';
                    break;
                case stripos($column, 'language') !== FALSE && stripos($column, 'home') === FALSE:
                    $column = 'language';
                    break;
                case stripos($column, 'language') !== FALSE && stripos($column, 'home') !== FALSE:
                    $column = 'language home';
                    break;
                case stripos($column, 'sped') !== FALSE || (stripos($column, 'spec') !== FALSE && stripos($column, 'ed') !== FALSE):
                    $column = 'sped';
                    break;
                case stripos($column, 'district') !== FALSE || (stripos($column, 'dist') !== FALSE && stripos($column, 'residence') !== FALSE):
                    $column = 'district';
                    break;
            }
        }
        if (!in_array('parent first', $columns)
            || !in_array('parent last', $columns)
            || !in_array('parent email', $columns)
            || !in_array('student first', $columns)
            || !in_array('student last', $columns)
            || !in_array('grade', $columns)
        ) {
            core_notify::addError('CSV must contain "parent first", "parent last", "parent email", "student first", "student last", and "grade"');
            core_notify::addError('Submitted column headers: ' . implode(', ', $columns));
        } else {
            while (($row = fgetcsv($handle)) !== FALSE) {
                $importArr = array_combine($columns, $row);
                $importArr['parent email'] = strtolower($importArr['parent email']);
                if (!($parent = mth_parent::getByEmail($importArr['parent email']))) {
                    $parent = mth_parent::create();
                    $importedParents++;
                }
                $parent->setName($importArr['parent first'], $importArr['parent last']);
                $parent->setEmail($importArr['parent email']);
                if (!$parent->saveChanges()) {
                    die('Unable to create parent');
                }
                if (!empty($importArr['parent phone'])) {
                    if (!($phone = $parent->getPhone('Cell', true))) {
                        $phone = mth_phone::create($parent);
                        $phone->setName('Cell');
                    }
                    $phone->setNumber($importArr['parent phone']);
                    $phone->save();
                }
                if (!empty($importArr['street'])) {
                    if (!($address = $parent->getAddress())) {
                        $address = mth_address::create($parent);
                    }
                    $address->saveForm(array(
                        'street' => $importArr['street'],
                        'city' => $importArr['city'],
                        'state' => $importArr['state'],
                        'zip' => $importArr['zip']
                    ));
                }
                if (req_post::bool('create-users')) {
                    $parent->makeUser(req_post::bool('create-users-send-emails'));
                }
                $importArr['student email'] = strtolower($importArr['student email']);
                if (!($student = mth_student::getByEmail($importArr['student email']))) {
                    $student = mth_student::create();
                    $importedStudents++;
                }
                $student->setParent($parent);
                $student->setName($importArr['student first'], $importArr['student last']);
                if ($year) {
                    $student->setGradeLevel($importArr['grade'], $year);
                }
                $student->setEmail($importArr['student email']);
                if (!empty($importArr['dob'])) {
                    $student->setDateOfBirth(strtotime($importArr['dob']));
                }
                if (!empty($importArr['gender'])) {
                    $student->setGender($importArr['gender']);
                }
                if (!empty($importArr['school']) && $year
                    && ($SOE = \mth\student\SchoolOfEnrollment::get($importArr['school']))
                ) {
                    $student->setSchoolOfEnrollment($SOE, $year);
                }
                $student->saveChanges();

                if (!empty($importArr['city']) && $year) {
                    $application = mth_application::startApplication($student, $year);
                    $application->setCityOfResidence($importArr['city']);
                    $application->setStatus(!empty($_POST['give-status']) ? mth_application::STATUS_ACCEPTED : mth_application::STATUS_SUBMITTED);
                    $application->setDateSubmittedToToday();
                }
                $packet = mth_packet::create($student);
                if (!empty($importArr['secondary last'])) {
                    $packet->setSecondaryContact($importArr['secondary first'], $importArr['secondary last']);
                }
                if (!empty($importArr['secondary email'])) {
                    $packet->setSecondaryEmail($importArr['secondary email']);
                }
                if (!empty($importArr['secondary phone'])) {
                    $packet->setSecondaryPhone($importArr['secondary phone']);
                }
                if (!empty($importArr['sped'])) {
                    $packet->setSpecialEd(stripos($importArr['sped'], 'yes') !== false);
                }
                if (!empty($importArr['district'])) {
                    $packet->setSchoolDistrict($importArr['district']);
                }
                if (!empty($importArr['hispanic'])) {
                    $packet->setHispanic(stripos($importArr['hispanic'], 'yes') !== false);
                }
                if (!empty($importArr['race'])) {
                    $packet->setRace(array($importArr['race']));
                }
                if (!empty($importArr['language'])) {
                    $packet->setLanguage($importArr['language']);
                }
                if (!empty($importArr['language home'])) {
                    $packet->setLanguageAtHome($importArr['language home']);
                }
                if (!empty($importArr['last school'])) {
                    $packet->setLastSchoolType(1);
                    $packet->setLastSchoolName($importArr['last school']);
                }
                $packet->setFERPAagreement(false);
                $packet->photoPerm(false);
                $packet->dirPerm(false);
                if (req_post::bool('packet_status')) {
                    $packet->setStatus(req_post::txt('packet_status'));
                    if (mth_packet::STATUS_SUBMITTED) {
                        $packet->setSubmitDateToToday();
                    }
                }
                $packet->save();
            }
            core_notify::addMessage($importedStudents . ' Students and ' . $importedParents . ' Parents imported');
        }
    } else {
        core_notify::addError('No file uploaded');
    }
    header('Location: ' . core_path::getPath() . '?importRan=1');
    exit();
} elseif (empty($_GET['importRan'])) {
    core_notify::addError('This tool can cause duplicates! Use with caution!');
}

core_loader::isPopUp();
core_loader::printHeader();

$statusArr = mth_student::getAvailableStatuses();
?>

    <form id="importForm" action="?form=<?= uniqid('import_form') ?>" method="post" enctype="multipart/form-data">
        <!--  <p>
            <label class="checkbox-block-label">
              <input type="checkbox" name="delete" value="1">
              Delete Students and Parents (including applications and packets).<br>
              <small style="display: inline">This will not delete admin users, but will delete all of their students.</small>
            </label>
          </p>-->
        <p>
            <label class="checkbox-block-label">
                <input type="checkbox" name="create-users" value="1">
                Create user accounts for Imported Parents.
            </label>
            <label class="checkbox-block-label">
                <input type="checkbox" name="create-users-send-emails" value="1">
                If creating user accounts also send emails to create passwords
                <small>(if not sent they will need to use the forgot password link to get a password, or I can program
                    something to send them an email later)
                </small>
            </label>
        </p>
        <p>
            Import for school year
            <select name="school_year">
                <?php $nextYear = mth_schoolYear::getNext() ?>
                <?php foreach (mth_schoolYear::getSchoolYears() as $year): /* @var $year mth_schoolYear */ ?>
                    <option
                        value="<?= $year->getID() ?>" <?= $year->getID() == $nextYear->getID() ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            Application Status
            <select name="app_status">
                <?php foreach (mth_application::getAvailableStatuses() as $status): ?>
                    <option><?= $status ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            Packet Status
            <select name="packet_status">
                <?php foreach (mth_packet::getAvailableStatuses() as $status): ?>
                    <option><?= $status ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="csv">CSV file</label>
            <input type="file" name="csv" id="csv">
        </p>
        <p>
            <input type="submit" value="Submit">
        </p>
    </form>
<?php
core_loader::printFooter();