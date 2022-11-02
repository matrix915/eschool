<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $pathArr array */

if (req_get::bool('pdf') && ($withdrawal = mth_withdrawal::getByStudent($student->getID()))) {
    header('Content-type: application/pdf');
    echo mth_views_withdrawal::getPDFcontent($withdrawal);
    exit();
}


$form = new mth_views_forms_withdrawal($student);
if ($form->submitted()) {
    if (!$form->success()) {
        echo 'There was a problem saving your submission. Please try again.';
        exit();
    }
    echo '1';
    exit();
}

cms_page::setPageTitle('Student Withdrawal Form');
cms_page::setPageContent('');
core_loader::printHeader('student');

core_loader::includejQueryUI();
core_loader::includejQueryValidate();
core_loader::addJsRef('jSignature', '/_/mth_includes/jSignature/libs/jSignature.min.js');
?>
<div class="page">
    <?= core_loader::printBreadCrumb('window');?>
    <div class="page-content container-fluid">  
        <div class="card">
            <?= cms_page::getDefaultPageMainContent() ?>
            <div class="card-block">
            <?php if (($withdrawal = mth_withdrawal::getByStudent($student->getID()))
                && $withdrawal->submitted()
                ): ?>

                    <p>Thank you. Your submission has been received.</p>

                <?php else: ?>
                    <?php $form->print_html(''); ?>
                    <p><b>Important notes to parents of students who are withdrawing:</b></p>
                    <ul>
                        <li>If the student will now attend a public school (district / charter / online), please request student
                            records from:
                            <div style="margin-left: 20px">
                                <?php if ($student->getSchoolOfEnrollment(true, $withdrawal->letter_year()) == \mth\student\SchoolOfEnrollment::eSchool): ?>
                                    <b>Provo School District - eSchool</b><br>
                                    280 West 940 North, Provo, Utah 84604<br>
                                    801-374-4810 (v); 801-374-4985 (f); eschool@provo.edu
                                <?php else: ?>
                                    <b><?= $student->getSOEname($withdrawal->letter_year()) ?></b><br>
                                    <?= $student->getSOEaddress(true, $withdrawal->letter_year()) ?>
                                <?php endif; ?>
                            </div>
                        </li>
                        <li>If the student will be independently home schooled, please file the required homeschool affidavit
                            with the local school district of residence.
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
core_loader::printFooter('student');