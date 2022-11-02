<?php
core_loader::redirect('/');

if (req_get::bool('order')) {
    $order = mth_wooCommerce_order::get_by_hash(req_get::txt('order'));

    if ($order) {
        mth_purchasedCourse::populateOrderPurchases($order);

        $parent = $order->mth_parent();
        if (!$parent->getUserID()) {
            $parent->makeUser(false);
        }
        if ($parent->getUserID() != core_user::getUserID()) {
            core_user::setCurrentUser(core_user::getUserById($parent->getUserID()));
            core_secure::setRememberMeCookie();
        }
        $order->customer()->setAddressFromOrder();
    }
    core_loader::redirect();
}

$parent = mth_parent::getByUser();

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || die();

    if (!$parent) {
        core_loader::redirect();
    }
    $success = array();
    foreach (req_post::int_array('mth_purchasedCourse_student') as $purchasedCourseID => $studentID) {
        if (($purchasedCourse = mth_purchasedCourse::get($purchasedCourseID))
            && !$purchasedCourse->student_canvas_enrollment_id()
        ) {
            $purchasedCourse->set_student_id($studentID);
            $success[] = $purchasedCourse->createCanvasEnrollments();
        }
    }
    if (count($success) > 0 && count($success) == count(array_filter($success))) {
        core_notify::addMessage('Your enrollments have been created!');
    } else {
        core_notify::addError('There were some errors. Please try again or contact us for support.');
    }
    core_loader::redirect();
}

function printStudentSelector(mth_purchasedCourse $purchasedCourse)
{
    if ($purchasedCourse->student_canvas_enrollment_id()) {
        echo $purchasedCourse->mth_student();
    } else {
        $parent = $purchasedCourse->mth_parent();
        ?>
        <select class="mth_purchasedCourse-StudentSelector"
                id="mth_purchasedCourse-StudentSelector-<?= $purchasedCourse->id() ?>"
                name="mth_purchasedCourse_student[<?= $purchasedCourse->id() ?>]"
                onchange="studentSelectorChanged(this)" required>
            <option value="">Select one...</option>
            <?php while ($student = $parent->eachStudent()): ?>
                <option value="<?= $student->getID() ?>"
                    <?= $purchasedCourse->mth_student_id() == $student->getID() ? 'selected' : '' ?>><?= $student ?></option>
            <?php endwhile; ?>
            <option value="{NEW}">(Add New Student)</option>
        </select>
        <?php
    }
}

function printStudentList(mth_parent $parent)
{
    ?>
    <div>
        <div style="width: 30%; font-weight: 700;">Name</div>
        <div style="width: 50%; font-weight: 700;">Email</div>
        <div style="width: 10%; font-weight: 700;">Grade</div>
    </div>
    <?php while ($student = $parent->eachStudent()): ?>
    <div>
        <div style="width: 30%"><a onclick="editStudent(<?= $student->getID() ?>)"><?= $student ?></a></div>
        <div style="width: 50%"><?= $student->getEmail() ?></div>
        <div style="width: 10%"><?= $student->getGradeLevel(true, true) ?></div>
    </div>
<?php endwhile; ?>
    <div>
        <div><a onclick="editStudent('NEW')"><i>Add New Student</i></a></div>
        <div></div>
        <div></div>
    </div>
    <?php
}

if (req_get::bool('loadStudentList')) {
    if ($parent) {
        exit(printStudentList($parent));
    }
    exit('No account found');
}

if (req_get::bool('setStudent')) {
    foreach (req_get::int_array('setStudent') as $purchasedCourseId => $studentID) {
        if (($purchasedCourse = mth_purchasedCourse::get($purchasedCourseId))) {
            $purchasedCourse->set_student_id($studentID);
        }
    }
    exit();
}

if (req_get::bool('getStudentSelector')) {
    if (($purchasedCourse = mth_purchasedCourse::get(req_get::int('getStudentSelector')))) {
        exit(printStudentSelector($purchasedCourse));
    } else {
        exit('ERROR');
    }
}

$showRegButton = false;

core_loader::includejQueryValidate();

cms_page::setPageTitle('Purchased Course Registration');
cms_page::setPageContent('');
cms_page::setPageContent('<p>We did not find any purchased courses for you. Please make sure you used a valid registration link from your receipt email. Contact us if you need support.</p>', 'No Purchased Courses', cms_content::TYPE_HTML);
core_loader::printHeader();
?>

<?php if (is_object($parent) && $purchasedCourse = mth_purchasedCourse::eachOfParent($parent)): ?>

    <script>
        $(function () {
            $('#mth_purchasedCourse-registration').validate();
        });
        function editStudent(student_id, assignToPurchasedCourse) {
            global_popup_iframe('mth_student-form-popup', '/reg/student?student=' + student_id + '&assignToPurchasedCourse=' + (assignToPurchasedCourse ? assignToPurchasedCourse : ''));
        }
        function studentSelectorChanged(select) {
            var purchasedCourseID = select.id.replace('mth_purchasedCourse-StudentSelector-', '');
            if (select.value === '{NEW}') {
                editStudent('NEW', purchasedCourseID);
            } else if (select.value) {
                $.ajax({url: '?setStudent[' + purchasedCourseID + ']=' + select.value});
            }
        }
        function updateStudentSelectors() {
            $('.mth_purchasedCourse-StudentSelector').each(function () {
                var select = $(this);
                var purchasedCourseID = this.id.replace('mth_purchasedCourse-StudentSelector-', '');
                select.parent().load('?getStudentSelector=' + purchasedCourseID);
            });
            $('#studentList').load('?loadStudentList=1');
        }
    </script>
    <style>
        .third-block {
            min-height: 200px;
        }

        #core-global-user-menu {
            display: none;
        }

        html {
            margin-top: 0 !IMPORTANT;
        }
    </style>
    <h2>Welcome, <?= $parent->getPreferredFirstName() ?></h2>

    <div>
        <?= cms_page::getDefaultPageMainContent() ?>
    </div>

    <div class="content-left">
        <h3>Your Students:</h3>
        <div class="div-table" id="studentList">
            <?php printStudentList($parent); ?>
        </div>
        <br>

    </div>

    <div class="content-right">
        <h3>Your Contact Information:</h3>
        <p><?= $parent->getAddress() ?><br>
            <?= $parent->getPhone() ?><br>
            <?= $parent->getEmail() ?></p>
    </div>

    <hr>

    <form id="mth_purchasedCourse-registration" method="post"
          action="?form=<?= uniqid('mth_purchasedCourse-registration-') ?>">

        <div style="overflow: auto">
            <h2>Purchased Courses for <?= mth_schoolYear::getCurrent() ?></h2>
            <?php do { ?>
                <div class="third-block">
                    <div class="third-block-content">
                        <h3><?= $purchasedCourse->course_title() ?></h3>
                        <p>Student: <span><?php printStudentSelector($purchasedCourse) ?></span></p>
                        <?php if ($purchasedCourse->student_canvas_enrollment_id()): ?>
                            <p style="color: green">Registration Complete</p>
                        <?php else: $showRegButton = true; endif; ?>
                    </div>
                </div>
            <?php } while ($purchasedCourse = mth_purchasedCourse::eachOfParent($parent)); ?>
        </div>

        <p>
            <?php if ($showRegButton): ?>
                <input type="submit" value="Register">
            <?php endif; ?>
        </p>

    </form>

<?php else: ?>
    <p>We did not find any purchased courses for you. Please make sure you used a valid registration link from your
        receipt email. Contact us if you need support.</p>
<?php endif; ?>

<?php
core_loader::printFooter();