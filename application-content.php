<?php

if (core_user::getUserID()) {
    header('Location: /home');
    exit();
}
$year = req_get::bool('y') ? mth_schoolYear::getByStartYear(req_get::int('y')) : mth_schoolYear::getApplicationYear();
$current_year = mth_schoolYear::getCurrent();
$midyearavailable = $year->getID() == $current_year->getID() ? ($current_year->midYearAvailable() && $current_year->isMidYearAvailable()) : false;

if (!empty($_GET['formId'])) {
    if (!core_loader::formSubmitable('newStudentApplication-' . $_GET['formId']) || $_GET['formId'] !== $_SESSION['applicationForm']) {
        exit('Not Allowed');
    }
    if (!mth_parent::validateEmailAddress($_POST['parent']['email'])) {
        if (core_user::validateEmail($_POST['parent']['email'])) {
            //There is a person associated with the parent's email address
            core_notify::addError('<b>There is already an account with that email address.</b>');
            core_notify::addError('Please login before trying to submit the application. Contact us if you need support.');
        } else {
            core_notify::addError('<b>Invalid email address</b>');
        }
    } else {
        $parent = mth_parent::create();
        $parent->setName($_POST['parent']['first_name'], $_POST['parent']['last_name']);
        $parent->setEmail($_POST['parent']['email']);
        $parent->makeUser();
        $phone = mth_phone::create($parent);
        $phone->setNumber($_POST['parent']['phone']['number']);
        $phone->setName('Home');

        foreach ($_POST['student'] as $studentFields) {
            $studentFields = new req_array($studentFields);
            if (empty($studentFields['first_name'])) {
                continue;
            }
            $student = mth_student::create();
            $student->setName($studentFields['first_name'], $studentFields['last_name']);
            $student->setGradeLevel($studentFields['grade_level'], $year);
            $student->setParent($parent);
            $student->set_spacial_ed($studentFields['special_ed']);
            $application = mth_application::startApplication($student, $year);
            $application->setCityOfResidence($_POST['city_of_residence']);
            $application->setReferredBy($_POST['referred_by']);
            $success[] = $application->submit($_POST['agrees_to_policies']);
        }

        if (count($success) != count(array_filter($success))) {
            core_notify::addError('There were some errors submitting the application, please check your student information when you login.');
        }
        header('location: /apply/thankyou');
        exit();
    }
    header('location: /');
    exit();
}
cms_page::setPageTitle('Welcome to My Tech High\'s Student Information Services');
cms_page::setPageContent('<p>Content below login form</p>', 'Below Login Form', cms_content::TYPE_HTML);

cms_page::setPageContent(
    '<p>Our tuition-free, personalized distance education program is available to home-based students between the ages of 5-18 residing in Utah.</p>
                          <p>If you already have an InfoCenter account <b>please <a href="/?login=1">login</a></b> before submitting an application.</p>',
    'Above Application Content',
    cms_content::TYPE_HTML
);

cms_page::setPageContent(
    'Student(s) agrees to adhere to all program policies and requirements, including participation in state testing.  Review details at <a href="http://mytechhigh.com/utah/" target="_blank">mytechhigh.com/utah</a>.',
    'Agree To Policies Text',
    cms_content::TYPE_LIMITED_HTML
);

cms_page::setPageContent(
    '<p>We are not yet ready to recieve applications for the comming year. Please contact us if you need assistance.</p>',
    'Year Not Available',
    cms_content::TYPE_HTML
);

core_loader::includejQueryValidate();


$_SESSION['applicationForm'] = uniqid();

core_loader::addClassRef('page-application layout-full');
core_loader::printHeader();
?>
<style>
    .hide-sped {
        display: none;
    }
    #main {
        background-color: #eef4f8;
        padding: 1rem;
    }
    .black {
        color:#1a1a1a !important;
    }
    .reduced-size-h3 {
        font-size: 1.5rem;
    }
    a {
        color: #4145FF !important;
    }
    input.error, select.error, input.error + label::before {
        border: 1px solid red!important;
    }

    label.error {
        display: block;
        margin-top: -1rem !important;
        color: #F00 !important;
    }

    label#general-required {
        display: none;
        color: #F00 !important;
        margin-bottom: 1rem;
    }

    .form__row {
        margin-bottom: 2.5rem !important;
    }

    /* Large devices (laptops/desktops, 992px and up) */
    @media only screen and (min-width: 992px) {
        .desktop {
            display: inline-block;
        }
        .mobile {
            display: none;
        }
    }

    /* Medium devices (landscape tablets, 768px and down) */
    @media only screen and (max-width: 992px) {
        .mobile {
            display: inline-block;
        }
        .desktop {
            display: none;
        }

        #referral {
            margin-right: 1rem; 
            margin-top: -1.5rem;
        }
    }
</style>
<link rel="stylesheet" href="/_/themes/remark/assets/css/new-theme.css">
<dev id="app">
    <main id="main" data-router-wrapper="">
        <div class="mt80" style="margin-top:0; padding-top: 6rem;">
            <div class="topCircle pen rel mln250 mrn250 m:m0">
                <div class="topCircle__graph abs top x y">
                    <svg data-name="O ·" xmlns="http://www.w3.org/2000/svg" width="1720" height="120" viewBox="0 0 1720 120" style="overflow: initial;">
                        <defs>
                            <style>
                            .cls-1234 {
                                fill: #4145ff;
                            }

                            .cls-2345 {
                                fill: none;
                                stroke: #4145ff;
                                stroke-width: 4px;
                            }
                            </style>
                        </defs>
                        <circle data-name="." class="cls-1234" cx="938" cy="65" r="3"></circle>
                        <circle data-name="." class="cls-1234" cx="921" cy="63" r="3"></circle>
                        <circle class="cls-2345" cx="860" cy="60" r="60"></circle>
                        <circle data-name="." class="cls-1234" cx="860" cy="60" r="3"></circle>
                    </svg>
                </div>
                <div class="topCircle__line abs top x">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="860" viewBox="0 0 1720 860">
                        <defs>
                            <style>
                            .cls-1, .cls-2 {
                                fill: none;
                                stroke-linecap: round;
                                stroke-width: 2px;
                                stroke-dasharray: 0.001 4;
                                opacity: 0.1;
                            }

                            .cls-1 {
                                stroke: #000;
                            }

                            .cls-2 {
                                stroke: #1a1a1a;
                            }
                            </style>
                        </defs>
                        <circle id="Ellipse" class="cls-1" cx="860" cy="860" r="860"></circle>
                    </svg>
                </div>
            </div>
        </div>
        <form action="javascript:alert('Refresh the page and try again!')" method="post" id="application-form" class="form df fw jcc x oh js-form" novalidate="true" style="margin-top: 3rem; height: auto; transform: translate(0px, -100px);">
            <div class="x df fw jcc">
                <div class="x s:col12 xl:col10">
                    <div class="text-center">
                        <h2 style="color:#4145FF;">InfoCenter</h2>
                    </div>
                    <div class="text-center">
                        <h2 class="black">Apply for the <?= $year ?> <?=$midyearavailable ? 'Mid-Year' : ''?> Program</h2>
                        <?php if($midyearavailable) :?>
                            <h3 class="black bold-text reduced-size-h3">Our mid-year program begins in <?= $year->getSecondSemStart('F') ?></h3>
                        <?php endif;?>
                    </div>
                    <div class="text-center black">
                        <?= cms_page::getDefaultPageContent('Above Application Content', cms_content::TYPE_HTML); ?>
                    </div>
                    <label id="general-required" class="x">Please complete all required fields.</label>
                    <div class="form__row x">
                        <input id="parent-first_name" class="x" type="text" name="parent[first_name]" required style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABHklEQVQ4EaVTO26DQBD1ohQWaS2lg9JybZ+AK7hNwx2oIoVf4UPQ0Lj1FdKktevIpel8AKNUkDcWMxpgSaIEaTVv3sx7uztiTdu2s/98DywOw3Dued4Who/M2aIx5lZV1aEsy0+qiwHELyi+Ytl0PQ69SxAxkWIA4RMRTdNsKE59juMcuZd6xIAFeZ6fGCdJ8kY4y7KAuTRNGd7jyEBXsdOPE3a0QGPsniOnnYMO67LgSQN9T41F2QGrQRRFCwyzoIF2qyBuKKbcOgPXdVeY9rMWgNsjf9ccYesJhk3f5dYT1HX9gR0LLQR30TnjkUEcx2uIuS4RnI+aj6sJR0AM8AaumPaM/rRehyWhXqbFAA9kh3/8/NvHxAYGAsZ/il8IalkCLBfNVAAAAABJRU5ErkJggg==&quot;); background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%; cursor: auto;">
                        <label for="parent-first_name" class="x">Parent First Name</label>
                    </div>
                    <div class="form__row x">
                        <input id="parent-last_name" class="x" type="text" name="parent[last_name]" required>
                        <label for="parent-last_name" class="x">Parent Last Name</label>
                    </div>
                    <div class="form__row x">
                        <input id="parent-phone-number" class="x" type="text" name="parent[phone][number]" required>
                        <label for="parent-phone-number" class="x">Parent Phone</label>
                    </div>
                    <div class="form__row x">
                        <input id="parent-email" class="x" type="email" name="parent[email]" required>
                        <label for="parent-email" class="x">Parent Email</label>
                    </div>
                    <div class="form__row x">
                        <input id="parent_email_again" class="x" type="email" name="parent_email_again" required>
                        <label for="parent_email_again" class="x">Parent Email Again</label>
                    </div>
                    <?php for ($s = 1; $s <= 10; $s++) : ?>
                        <div style="<?= $s > 1 ? 'display:none;' : '' ?>" id="student-<?= $s ?>" class="stcont">
                            <?php if ($s > 1) : ?>
                                <button style="margin-bottom: 3rem;" type="button" class="x hidestudent">Remove Student</button>
                            <?php endif; ?>
                            <div class="form__row x">
                                <input id="student-<?= $s ?>-first_name" class="x" type="text" name="student[<?= $s ?>][first_name]" required>
                                <label for="student-<?= $s ?>-first_name" class="x">Student First Name</label>
                            </div>
                            <div class="form__row x">
                                <input id="student-<?= $s ?>-last_name" class="x" type="text" name="student[<?= $s ?>][last_name]" required>
                                <label for="student-<?= $s ?>-last_name" class="x">Student Last Name</label>
                            </div>
                            <div class="form__row form__row--dropdown x">
                                <select class="student-grade-level-select x select-block | js-multiselect" id="student-<?= $s ?>-grade_level" name="student[<?= $s ?>][grade_level]" required>
                                    <option></option>
                                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc) : ?>
                                        <option value="<?= $grade_level ?>"><?= $grade_desc ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="subject" class="desktop">Student Grade Level (age) <b>as of September 1, <?= $year->getStartYear() ?></label>
                                <label for="subject" class="mobile">Student Grade Level <b>as of 09/01/<?= $year->getStartYear() ?></label>
                            </div>
                            <div class="x m:col10 mb20">
                                <p class="black">Has student ever been diagnosed with a learning disability or ever qualified
                                for Special Education Services through an IEP or 504 plan (including
                                Speech Therapy)?</p>
                            </div>
                            <fieldset id="group1" class="mb30 sped-container">
                                <?php foreach (mth_student::getAvailableSpEd() as $sped => $label) : ?>
                                    <?php if ($sped != mth_student::SPED_EXIT) : ?>
                                        <div class="form__radio x">
                                            <input type="radio" id="student-<?= $s ?>-special_ed-<?= $sped ?>" name="student[<?= $s ?>][special_ed]" class="sped" value="<?= $sped ?>" <?= $sped == mth_student::SPED_NO ? 'checked' : '' ?>>
                                            <label for="student-<?= $s ?>-special_ed-<?= $sped ?>" style="margin-bottom:0;">
                                                <?= $label ?>
                                                <?php if ($sped != mth_student::SPED_NO) : ?>
                                                    (additional documents will be required)
                                                <?php endif; ?>                                       
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <div class="alert alert-info alert-alt sped-note hide-sped" style="margin-bottom: 0; background-color: #D9F6FF; border-color: #4145FF;">
                                    Please review and <a href="https://goo.gl/forms/lnCEYLTs98OcfKlD3" target="_blank">submit this form</a> as part of the standard application process.
                                </div>
                            </fieldset>
                        </div>
                    <?php endfor; ?>
                    <script>
                        var c = 1;
                    </script>
                    <div class="add-student">
                        <button class="x add-student">Add Student</button>
                    </div>
                    <div class="form__row x" style="margin-top: 3rem; margin-bottom: 1rem;">
                        <input id="city_of_residence" class="x" type="text" name="city_of_residence" required>
                        <label for="city_of_residence" class="x">State of Residence</label>
                    </div>
                    <label class="error" style="float: left;" for="agrees_to_policies"></label>
                    <div class="form__row form__row--agree">
                        <div class="x agree df mv30" style="margin-bottom: 2rem;">
                            <div class="df">
                                <input id="agrees_to_policies" type="checkbox" name="agrees_to_policies" required>
                                <label for="agrees_to_policies" style="margin-left:0;" class="black">
                                    <?= cms_page::getDefaultPageContent('Agree To Policies Text', cms_content::TYPE_LIMITED_HTML) ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form__row x">
                        <input id="referred_by" class="x" type="text" name="referred_by">
                        <label for="referred_by" class="x" id="referral">If new to My Tech High, please tell us who referred you so we can thank them!</label>
                    </div>
                    <div class="df mhn5">
                        <div class="x s:col12 xl:col10">
                            <button type="submit" id="submit-application" class="button button--gradient button--radius x mb30" style="margin-bottom: 0;">
                                <span>Apply</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <footer class="page-copyright page-copyright-inverse text-center black" style="margin-top: 0;">
            <p>© 2021 <a target="_blank" href="https://www.mytechhigh.com/">My Tech High, Inc.</a></p>
            <p> 
                <a href="https://docs.google.com/document/d/1q-LZ8dTk5vgbsFXbo9eNN-cJagbYfz2LP6kuI7NGn2A/pub" target="_blank"> Terms of Use</a> |
                <a href="https://docs.google.com/document/d/1ZzNWCD8ri27Tl6UImxAXl0jV8vrwo_MgFvUtUrjbJWc/pub" target="_blank">Privacy &amp; COPPA Policy</a>
            </p>        
        </footer>
    </main>
</dev>
<?php
core_loader::printFooter();
?>
<script>
    $(function() {
        $('button.x.add-student').click(function(event) {
            event.preventDefault();
            c++;
            console.log(c);
            $('#student-'+c).fadeIn();
            if(c>=10){ 
                $(this).hide();
            }
        });

        var sped_no = <?= mth_student::SPED_NO ?>;
        $('.sped').change(function() {
            var $sped_note = $(this).closest('.sped-container').find('.sped-note');
            if ($(this).val() == sped_no) {
                $sped_note.addClass('hide-sped');
            } else {
                $sped_note.removeClass('hide-sped');
            }

        });

        $('#submit-application').click(function() {
            $(this).attr('disabled', 'disabled');

            if (!$("#application-form").valid()) {
                $('label#general-required').show();
                $(this).removeAttr("disabled");
            } else {
                $('label#general-required').hide();
                $("#application-form")[0].submit();
            }

        });

        $('#email').trigger('focus');
        $('.hidestudent').click(function() {
            let studentelements = $(this).closest('.stcont');
            studentelements.hide();
            document.getElementById(studentelements[0]['id'] + '-first_name').value = ''
        });
    });
    $('#application-form').attr({
        'action': '?formId=<?= $_SESSION['applicationForm'] ?>&y=<?= $year->getStartYear() ?>'
    }).validate({
        rules: {
            parent_email_again: {
                equalTo: "#parent-email"
            },
            "parent[email]": {
                remote: '/apply/validate-email.php'
            },
            "parent[phone][number]": {
                required: true,
                phoneUS: true
            }
        }
    });

    jQuery.extend(jQuery.validator.messages, {
        required: "",
    });

    $('input,select').change(function () {
        if( $('input.error').length == 0 && $('select.error').length == 0 ) {
            $('label#general-required').hide();
        }
    });
</script>