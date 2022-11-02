<?php

(mth_purchasedCourse::hasPurchasedCourse(mth_parent::getByUser()))
|| die('Only parents who have purchased courses can use this page.');

if (req_get::bool('student')) {
    $student = mth_student::getByStudentID(req_get::int('student'));
}

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form'));

    if (!isset($student)) {
        $student = mth_student::create();
    }
    $student->setName(req_post::txt('first_name'), req_post::txt('last_name'));
    $student->setEmail(req_post::txt('email'));
    $student->setGradeLevel(req_post::int('grade'), mth_schoolYear::getCurrent());
    $student->setParent(mth_parent::getByUser());

    if (!$student->saveChanges()) {
        core_notify::addError('Unable to save student. Please try again, or contact us if the problem persists.');
        core_loader::redirect('?student=' . $student->getID());
    }

    if (req_post::bool('assignToPurchasedCourse')) {
        $purchasedCourse = mth_purchasedCourse::get(req_post::int('assignToPurchasedCourse'));
        $purchasedCourse->set_student($student);
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <script>
            parent.updateStudentSelectors();
            parent.global_popup_iframe_close('mth_student-form-popup');
        </script>
    </head>
    </html>
    <?php
    die();
}

core_loader::includejQueryValidate();
core_loader::isPopUp();
core_loader::printHeader();
?>

    <h1>
        <?= $student ? 'Edit' : 'New' ?>
        Student
    </h1>

    <form action="?student=<?= req_get::int('student') ?>&form=<?= uniqid('mth_student-form-') ?>"
          id="mth_student-form" method="post">

        <?php if (req_get::bool('assignToPurchasedCourse')): ?>
            <input type="hidden" value="<?= req_get::int('assignToPurchasedCourse') ?>"
                   name="assignToPurchasedCourse">
        <?php endif; ?>

        <p>
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name"
                   value="<?= $student ? $student->getPreferredFirstName() : '' ?>" required>
        </p>

        <p>
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name"
                   value="<?= $student ? $student->getPreferredLastName() : '' ?>" required>
        </p>

        <p>
            <label for="email">Email</label>
            <input type="email" name="email" id="email"
                   value="<?= $student ? $student->getEmail() : '' ?>" required>
            <small><b>Note:</b> student email must be different from parent email.</small>
        </p>

        <p>
            <label for="grade">Grade</label>
            <select name="grade" required id="grade">
                <option></option>
                <?php foreach (mth_student::getAvailableGradeLevels() as $grade => $desc): ?>
                    <option value="<?= $grade ?>"
                        <?= $student && $student->getGradeLevel() == $grade ? 'selected' : '' ?>><?= $desc ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <input type="submit" value="Save">
            <input type="button" value="Cancel" onclick="parent.global_popup_iframe_close('mth_student-form-popup');">
        </p>

    </form>

    <script>
        $(function () {
            $('#mth_student-form').validate({
                rules: {
                    "email": {
                        remote: {
                            url: '/apply/validate-email.php',
                            data: {
                                'student[email]': function () {
                                    return $('#email').val();
                                },
                                'studentid': <?=$student ? $student->getID() : '""'?>
                            }
                        }
                    }
                }
            });
        });
    </script>
<?php
core_loader::printFooter();