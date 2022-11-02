<?php
($parent = mth_parent::getByUser()) || core_secure::loadLogin();
($year = mth_schoolYear::getCurrent()) || die('No year defined');
$optOut = mth_testOptOut::getByParentYear($parent, $year);
$address = $parent->getAddress();

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || die();

    if (!req_post::bool('student')) {
        core_loader::redirect();
    }

    if (!$optOut) {
        $optOut = new mth_testOptOut();
        $optOut->set_school_year_id($year->getID());
        $optOut->set_parent($parent);
        $optOut->save_sig_file(req_post::raw('signatureContent'));
        $optOut->set_in_attendance(req_post::bool('in_attendance'));
    }
    $optOut->set_student_ids(req_post::int_array('student'));

    if ($optOut->submit()) {
        core_notify::addMessage('Your opt-out request has been received.');
    } else {
        core_notify::addError('Unable to submit the opt-out request. Please make sure you fill all required fields.');
    }
    core_loader::redirect();
}

if ($optOut && !core_notify::hasNotifications()) {
    core_notify::addMessage('<b>Your opt-out form for ' . $year . ' has been received.</b> Submitted ' . $optOut->date_submitted('l, F j, Y') . '.');
}

core_loader::includejQueryValidate();
core_loader::addHeaderItem('
<!--[if lt IE 9]>
<script type="text/javascript" src="/_/mth_includes/jSignature/libs/flashcanvas.min.js"></script>
<![endif]-->');
core_loader::addJsRef('jSignature', '/_/mth_includes/jSignature/libs/jSignature.min.js');

cms_page::setPageTitle('State Testing Opt-out Form');

core_loader::printHeader('student');
?>
    <style>
        .disabled {
            color: #ccc;
        }

        ol li {
            margin-bottom: 1em;
        }

        h1#page-title {
            display: none;
        }
    </style>
<div class="page">
    <div class="page-header page-header-bordered">
        <h1 class="page-title"><?= cms_page::getDefaultPageTitleContent() ?></h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active"><?= cms_page::getDefaultPageTitleContent() ?></li>
        </ol>
        <div class="page-header-actions">
        <a class="btn btn-secondary btn-round" href="/">Close</a>
        </div>
    </div>
    <div class="page-content container-fluid">  
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><?= $year ?> Parental Exclusion from State Assessments</h4>
            </div>
            <div class="card-block">
                <div class="row">
                    <div class="col-md-6">
                        <p>The data obtained in these assessments may be utilized by teachers and administrators to review student
                            progress toward learning targets, plan instruction, provide teacher
                            feedback, provide important achievement and accountability data to students, parents, and other
                            stakeholders, and summative assessments allow for public reporting about
                            school quality.</p>
                        <ul>
                            <?php if($address->getState() == 'OR'){?>
                                <li>English Language Arts</li>
                                <li>Math</li>
                            
                            <?php }else{ ?>
                                <li>AAPPL Assessment of Performance toward Proficiency in Languages</li>
                            <li>Acadience Reading</li>
                            <li>ACT</li>
                            <li>CTE Skill Certificate Assessments – REQUIRED FOR CERTIFICATION</li>
                            <li>DLM Dynamic Learning Maps English Language Arts (ELA)</li>
                            <li>DLM Dynamic Learning Maps Mathematics</li>
                            <li>DLM Dynamic Learning Maps Science</li>
                            <li>Early Literacy Alternate Assessment</li>
                            <li>Early Mathematics Alternate Assessment</li>
                            <li>Early Mathematics Assessment</li>
                            <li>General Financial Literacy Assessment</li>
                            <li>High School Civics Exam – REQUIRED FOR GRADUATION</li>
                            <li>High School Core Benchmarks English Language Arts (ELA)</li>
                            <li>High School Core Benchmarks Mathematics</li>
                            <li>High School Core Benchmarks Science</li>
                            <li>RISE Benchmark Modules English Language Arts (ELA)</li>
                            <li>RISE Benchmark Modules Mathematics </li>
                            <li>RISE Benchmark Modules Writing</li>
                            <li>RISE Benchmarks Modules Science</li>
                            <li>RISE Interim English Language Arts (ELA)</li>
                            <li>RISE Interim Mathematics</li>
                            <li>RISE Interim Science</li>
                            <li>RISE Summative English Language Arts (ELA)</li>
                            <li>RISE Summative Mathematics</li>
                            <li>RISE Summative Science</li>
                            <li>RISE Summative Writing</li>
                            <li>Utah Aspire Plus English</li>
                            <li>Utah Aspire Plus Mathematics</li>
                            <li>Utah Aspire Plus Reading</li>
                            <li>Utah Aspire Plus Science</li>
                            <?php } ?>
                        </ul>
                        <p>As a parent/guardian, I do not want my child to participate in the above assessments during
                            the <?= $year ?> school year.
                            This form must be returned annually to your local school.</p>
                    </div>
                    <div class="col-md-6">
                        <form action="?form=<?= uniqid('mth_testOptOut_form-') ?>" method="post" id="mth_testOptOut_form">
                            <h4 class="card-title">Select the student(s) you would like to opt-out:</h4>
                            <?php $students = $parent->getStudents(); ?>
                            <?php foreach ($students as $student): /* @var $student mth_student */ ?>
                                <?php
                                if(!$student->isPendingOrActive()){
                                    continue;
                                }
                                $disabled = false;
                                $message = '';
                                //$checked = !$optOut && count($students) == 1;
                                $checked = false;
                                if ($optOut && in_array($student->getID(), $optOut->get_student_ids())) {
                                    $disabled = true;
                                    $message = '(Already submitted)';
                                    $checked = true;
                                }
                                if (!$student->getStatus($year)) {
                                    $disabled = true;
                                    $message = '(Needs approved application and enrollment packet)';
                                    $checked = false;
                                }
                                ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="student[]"
                                            class="cbstudent"
                                            value="<?= $student->getID() ?>" <?= $disabled ? 'disabled' : '' ?>
                                            id="student-<?= $student->getID() ?>" <?= $checked ? 'checked' : '' ?>>
                                    <label class="<?= $disabled ? 'disabled' : '' ?>"
                                        for="student-<?= $student->getID() ?>">
                                        <?= $student ?> - <?= $student->getGradeLevel(true, false, $year->getID()) ?> <?= $message ?> 
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <label for="student[]" class="error"></label>
                            <input type="hidden" value="1" id="in_attendance-1" name="in_attendance">
                            <hr>
                            <?php if ($optOut): ?>
                                <div id="existing-signature-container">
                                    <div >  
                                    <label>
                                        Signature
                                        <small style="display: inline">(Signed <?= $optOut->date_submitted('l, F j, Y') ?>)</small>
                                    </label>
                                    <img src="/_/mth_includes/mth_file.php?hash=<?= $optOut->sig_file_hash() ?>"
                                        style="max-width:300px;">
                                    </div>
                                    <br><?= $parent ?><br>
                                    <?= $parent->getPhone() ?>
                                </div>
                            <?php else: ?>
                                <div class="p" id="signature-form">
                                    <label>Signature
                                        <small style="display: inline">(use the mouse to sign) - <a
                                                onclick="sigdiv.jSignature('reset')">Clear</a></small>
                                    </label>
                                    <div style="height: 0; overflow: hidden;"><input type="text" name="signatureContent"
                                                                                    id="signatureContent" required></div>
                                    <div id="signature"></div>
                                    <label for="signatureContent" class="error"></label>
                                    <br><?= $parent ?><br>
                                    <?= $parent->getPhone() ?>
                                </div>
                            <?php endif; ?>
                            <p style="text-align: center;">
                                <button type="button" onclick="captureSignatureAndSubmitForm()" class="btn btn-primary btn-round submit-btn" style="display:none">
                                    Submit
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
           
           
        </div>
    </div>
</div>
   
<?php
core_loader::printFooter('student');
?>
<script>
    //$.validator.setDefaults({debug:true});
    var sigdiv = $("#signature");
    $(function () {
        sigdiv.jSignature();
        $sigform = $('#signature-form');
        $sigimage = $('#existing-signature-container');

        $('.cbstudent').change(function(){
            if($('.cbstudent:not(:disabled)').is(':checked')){
                $('.submit-btn').fadeIn();
                $sigform.length != 0 && $sigform.fadeIn();
                $sigimage.length != 0 && $sigimage.fadeIn();
            }else{
                $('.submit-btn').fadeOut();
                $sigform.length != 0 && $sigform.fadeOut();
                $sigimage.length != 0 && $sigimage.fadeOut();
            }
        });

        $sigform.length != 0 && $sigform.hide();
        $sigimage.length != 0 && $sigimage.hide();
    });

    var mth_testOptOut_form = $('#mth_testOptOut_form');
    mth_testOptOut_form.validate({
        rules: {
            "student[]": {
                required: true
            }
        },
        messages: {
            "student[]": "Please select at least one student"
        }
    });

    function captureSignatureAndSubmitForm() {
        if (sigdiv.length > 0 && sigdiv.jSignature('isModified')) {
            $('#signatureContent').val(sigdiv.jSignature("getData", "svgbase64")[1]);
        } else {
            $('#signatureContent').val('');
        }
        mth_testOptOut_form.submit();
    }
</script>