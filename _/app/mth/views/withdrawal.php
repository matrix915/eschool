<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use mth\yoda\assessment;
use mth\yoda\memcourse;

/**
 * Description of withdrawal
 *
 * @author abe
 */
class mth_views_withdrawal
{

    public static function getPDFcontent(mth_withdrawal $withdrawal)
    {
        $siteURL = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
        $student = $withdrawal->student();
        $parent = $student->getParent();
        $address = $parent->getAddress();
        $year = $withdrawal->letter_year();
        
        //transfered student
        $currentSEO = $student->TransfereeEffectiveDate();
        if($currentSEO->transferred){
            $dateEffect = $currentSEO->effectiveDateSoE;
        }

        $LogoStyle = '';
        $schedule = mth_schedule::get($student, $withdrawal->school_year(), true);
        if (
            $schedule
            && ($schedulePeriod = $schedule->getPeriod(1))
            && ($enrollment = memcourse::getStudentHomeroom($student->getID(), $year))
        ) {
            $grade = assessment::isPassing($enrollment->getGrade()) ? 'Pass' : 'Fail';
        } else {
            $grade = '-';
        }
        $header = 'eSchool-header2.png';
        $footer = 'eSchool-footer2.png';
        $header_type = $footer_type = \core\Response::TYPE_PNG;
        $schoolAddress = '<b>Provo School District - eSchool</b><br>
            280 West 940 North, Provo, Utah 84604<br>
            801-374-4810 (v); 801-374-4985 (f); eschool@provo.edu';
        $specialFields = '';
        $parentPhrase = 'I (parent/guardian) verify my intent to withdraw my student:';
        $SOE = $student->getWithdrawalSOE(false, $year);
        if ($SOE->getId() == \mth\student\SchoolOfEnrollment::GPA) {
            $header = 'GPA-head.jpg';
            $footer = 'GPA-foot.jpg';
            $header_type = $footer_type = \core\Response::TYPE_JPEG;
            $schoolAddress = '<b>' . $SOE->getLongName() . '</b><br>
            ' . $SOE->getAddresses(true);
        } elseif ($SOE->getId() == \mth\student\SchoolOfEnrollment::ALA) {
            $header = 'ALA-head.jpg';
            $footer = 'ALA-foot.jpg';
            $header_type = $footer_type = \core\Response::TYPE_JPEG;
            $schoolAddress = '<b>' . $SOE->getLongName() . '</b><br>
            ' . $SOE->getAddresses(true);
        } elseif ($SOE->getId() == \mth\student\SchoolOfEnrollment::ICSD) {
            $header = 'icsd-logo.jpg';
            $footer = 'ALA-foot.jpg';
            $LogoStyle = 'width:2in;';
            $header_type = $footer_type = \core\Response::TYPE_JPEG;
            $schoolAddress = '<b>' . $SOE->getLongName() . '</b><br>
            ' . $SOE->getAddresses(true);
        } elseif ($SOE->getId() == \mth\student\SchoolOfEnrollment::Nyssa) {
            $header = 'nyssa-header.png';
            $footer = 'nyssa-footer.png';
            $LogoStyle = 'width: 3in; margin-bottom: 1rem; margin-top: 1rem;';
            $header_type = $footer_type = \core\Response::TYPE_PNG;
            $schoolAddress = '<b>' . $SOE->getLongName() . '</b><br>
            ' . $SOE->getAddresses(true);
        } elseif ($SOE->getId() == \mth\student\SchoolOfEnrollment::Tooele) {
            $header = 'Tooele-header.png';
            $footer = 'Tooele-footer.png';
            $header_type = $footer_type = \core\Response::TYPE_PNG;
            $schoolAddress = '<b>' . $SOE->getLongName() . '</b><br>
            Attn: Linda Kirby<br>
            lkirby@tooeleschools.org<br>
            ' . $SOE->getAddresses(true) . '<br>
            ' . $SOE->getPhones(true);
            $specialFields = '<tr>
            <td>Date of enrollment:</td>
            <td>
            <span class="form_field">
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
            </span>
                        </td>
                        <td>Transfer code:</td>
                        <td><span class="form_field">
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
            </span>
            </td>
            </tr>';
            $parentPhrase = 'I (parent/guardian) verify my intent to withdraw my student and agree to file all required
            paperwork with the new school district (i.e. enrollment application or homeschooling affidavit):';
        } elseif ($SOE->getId() == \mth\student\SchoolOfEnrollment::Nebo) {
            $header = 'nebo-header.png';
            $footer = null;
            $header_type = $footer_type = \core\Response::TYPE_PNG;
            $schoolAddress = '<b>' . $SOE->getLongName() . '</b><br>
            ' . $SOE->getAddresses(true);
        }
        $graphics_paths = core_config::getSitePath() . '/_/mth_includes/school_of_enrollment_graphics/';
        $header_data = base64_encode(file_get_contents($graphics_paths . $header));
        $footer_data = $footer ? base64_encode(file_get_contents($graphics_paths . $footer)) : null;
        echo $currentSEO->transferred == 1 ? date('m/d/Y',strtotime($dateEffect)): $student->getStatusDate($withdrawal->school_year(), 'm/d/Y');
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Withdrawal</title>
            <style>
                body {
                    font-family: 'Ariel', sans-serif;
                    font-size: 10pt;
                    margin: 0;
                }

                p {
                    margin: .125in 0 .125in .4em;
                }

                td {
                    vertical-align: top;
                    white-space: nowrap;
                }

                .form_field {
                    display: block;
                    border-bottom: solid 1px #333;
                    font-weight: bold;
                    height: 1.33em;
                    padding: 0 .4em;
                    text-align: left;
                }

                #scheduleTable {
                    text-align: center;
                    margin-bottom: .125in;
                }

                #scheduleTable table {
                    margin: auto;
                    border-collapse: collapse;
                }

                #scheduleTable th,
                #scheduleTable td {
                    padding: 2px 5px;
                    border: solid 1px #000;
                }

                #scheduleTable th {
                    background: #FF0;
                }
            </style>
        </head>
        <body>
            <div style="height: 9in; page-break-inside: avoid;">
                <p style="text-align: center; margin-top: -.25in">
                    <img style="<?= $LogoStyle ? $LogoStyle : 'width:7.5in' ?>" src="data:<?= $header_type ?>;base64,<?= $header_data ?>"><br>
                    <b>Student Withdrawal Report</b>
                </p>
                <table style="border:none; border-collapse: separate; border-spacing: .4em;">
                    <tr>
                        <td>Student Name:</td>
                        <td><span class="form_field"><?= $student->getName() ?></span></td>
                        <td>Birthdate:</td>
                        <td><span class="form_field"><?= $student->getDateOfBirth('m/d/Y') ?></span></td>
                        <td>Grade:</td>
                        <td><span class="form_field"><?= $student->getGradeLevelValue() ?></span></td>
                    </tr>
                </table>
                <table style="border:none; border-collapse: separate; border-spacing: .4em;">
                    <tr>
                        <td>Address:</td>
                        <td>
                            <span class="form_field"><?= $address->getStreet() ?></span>
                            <?php if ($address->getStreet2()) : ?>
                                <span class="form_field"><?= $address->getStreet2() ?></span>
                            <?php endif; ?>
                            <span class="form_field"><?= $address->getCity() ?>
                                , <?= $address->getState() ?> <?= $address->getZip() ?></span>
                        </td>
                        <td>Phone:</td>
                        <td><span class="form_field"><?= $parent->getPhone() ?></span></td>
                        <td>Effective Withdrawal Date:</td>
                        <td><span class="form_field">
                           <?php 
                            echo $currentSEO->transferred== 1  ? date('m/d/Y',strtotime($dateEffect)): $student->getStatusDate($withdrawal->school_year(), 'm/d/Y');
                           ?>
                         
                        </span></td>
                    </tr>
                </table>
                <p>
                    How will your student continue their education?
                    <span class="form_field" style="display: inline"><?= $withdrawal->reason() ? $withdrawal->reason() : 'undeclared' ?></span>
                </p>
                <p>
                    New Public School Name (if applicable):
                    <span class="form_field" style="display: inline"><?= $withdrawal->new_school_name() ? $withdrawal->new_school_name() : 'undeclared' ?></span>
                </p>
                <p>
                    New School Address (if applicable):
                    <span class="form_field" style="display: inline"><?= $withdrawal->new_school_address() ? str_replace("\n", ', ', $withdrawal->new_school_address(false)) : 'undeclared' ?></span>
                </p>
                <p><?php echo $parentPhrase; ?></p>

                <table style="border:none; border-collapse: separate; border-spacing: .4em;">
                    <tr>
                        <td>Signature:</td>
                        <td>
                            <span class="form_field" style="width: 4in;">
                                <?php if ($withdrawal->sig_file_hash()) : ?>
                                    <img style="max-height: 3em; margin-top:-1em;" src="<?= !empty($_SERVER['HTTPS']) ? 'https' : 'http' ?>://<?= $_SERVER['HTTP_HOST'] ?>/_/mth_includes/mth_file.php?hash=<?= $withdrawal->sig_file_hash() ?>">
                                <?php else : ?>
                                    <?= $parent->getName() ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>Date:</td>
                        <td><span class="form_field"><?= $withdrawal->datetime() ? $withdrawal->datetime('m/d/Y') : date('m/d/Y') ?></span>
                        </td>
                    </tr>
                </table>
                <?php if ($schedule && $schedule->status(true) >= mth_schedule::STATUS_SUBMITTED && $schedule->status(true) != mth_schedule::STATUS_DELETED) : ?>
                    <p>Current Enrollment:</p>
                    <div style="text-align: center" id="scheduleTable">
                        <table style="margin: auto;">
                            <tr>
                                <th>Subject</th>
                                <th>Grade</th>
                            </tr>
                            <?php while ($schedulePeriod = $schedule->eachPeriod()) : if ($schedulePeriod->none()) {
                                                continue;
                                            } ?>
                                <tr>
                                    <td><?= $schedulePeriod->courseName() ?></td>
                                    <td style="text-align: center"><?= $grade ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                <?php endif; ?>
                <div style="border:solid 1px #000;">
                    <div style="margin-left: .4em; margin-top:.4em;">For School Use Only</div>
                    <table style="border: none; border-collapse: separate; border-spacing: .4em;">
                        <tr>
                            <td>Registrar:</td>
                            <td>
                                <span class="form_field">
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                </span>
                            </td>
                            <td>Administrator:</td>
                            <td><span class="form_field">
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                                </span></td>
                        </tr>
                        <?php echo $specialFields ?>
                    </table>
                </div>
                <p><b>Important notes to parents of students who are withdrawing:</b></p>
                <ul>
                    <li>If the student will now attend a public school (district / charter / online), please request student
                        records from:<br>
                        <div style="margin-left: 20px;"><?= $schoolAddress ?></div>
                    </li>
                    <li>If the student will be independently homeschooled, please file the required homeschool affidavit
                        with the local school district of residence.
                    </li>
                </ul>
            </div>
            <p style="text-align: center; margin:0">
                <?php if ($footer_data) : ?>
                    <img style="width:7.5in" src="data:<?= $footer_type ?>;base64,<?= $footer_data ?>">
                <?php endif; ?>
            </p>
        </body>

        </html>
<?php
        $content = ob_get_contents();
        ob_end_clean();
        //return $content;
        $option = new Options();
        $option->setIsRemoteEnabled(true);
        $dompdf = new Dompdf();
        $dompdf->setOptions($option);
        $dompdf->load_html($content);
        $dompdf->render();
        return $dompdf->output();
    }
}
