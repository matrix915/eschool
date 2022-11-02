<?php
use Dompdf\Dompdf;
use Dompdf\Options;
/**
 * Description of testOptOut
 *
 * @author abe
 */
class mth_views_testOptOut
{

    public static function get2016PDFcontent(mth_testOptOut $form, mth_student $student)
    {
        if (!($year = $form->school_year()) || !$student->getSchoolOfEnrollment(true, $form->school_year())
            || !($parent = $student->getParent())
        ) {
            return false;
        }
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>
                <?= $student->getPreferredFirstName() ?>'s State Testing Opt-Out Form - <?= $form->school_year() ?>
            </title>
            <style>
                body, html {
                    font-family: 'Arial', sans-serif;
                    font-size: 12pt;
                    margin: 0;
                    width: 11in;
                    height: 8.5in;
                }

                div {
                    position: absolute;
                }

                #form_overlay {
                    top: 0;
                    left: 0;
                    z-index: 9999;
                    width: 11in;
                }

                #form_overlay img {
                    width: 100%;
                }

                #title_school_year {
                    top: 1.25in;
                    left: 3.13in;
                    font-weight: bold;
                }

                #school_year {
                    top: 5.72in;
                    left: 5.5in;
                    font-size: 8pt;
                }

                #student_name {
                    top: 6.04in;
                    left: 1.43in;
                }

                #student_id {
                    top: 6.04in;
                    left: 7.43in;
                }

                #parent_signature {
                    top: 6.21in;
                    left: 2in;
                    width: 4in;
                    height: .5in;
                }

                #parent_signature img {
                    max-height: .5in;
                }

                #parent_signature_date {
                    top: 6.32in;
                    left: 7.46in;
                }

                #parent_name {
                    top: 6.6in;
                    left: 2.05in;
                }

                #parent_phone {
                    top: 6.87in;
                    left: 2.45in;
                }

                #student_grade_level {
                    top: 7.15in;
                    left: 2in;
                }

                #school_of_enrollment {
                    top: 7.43in;
                    left: 2in;
                }

            </style>
        </head>
        <body>
       
        <div id="form_overlay">
            <img
                src="<?= $_SERVER['DOCUMENT_ROOT'] ?>/_/mth_includes/testing_exemption_forms/2016ParentalExclusionForm-<?= $student->getGradeLevelValue($year) < 7 ? 'Elementary' : 'Secondary' ?>.png">
        </div>
        <div id="title_school_year">
            <?= $form->school_year() ?>
        </div>
        <div id="school_year"><?= $form->school_year() ?></div>
        <div id="student_name"><?= $student ?></div>
        <div id="student_id"><?= str_pad($student->getID(), 10, '0', STR_PAD_LEFT) ?></div>
        <div id="parent_signature">
            <img src="data:image/png;base64,<?= base64_encode($form->sig_file_contents()) ?>">
        </div>
        <div id="parent_signature_date"><?= $form->date_submitted('n/j/Y') ?></div>
        <div id="parent_name"><?= $parent ?></div>
        <div id="parent_phone"><?= $parent->getPhone() ?></div>
        <div id="student_grade_level"><?= $student->getGradeLevelValue($year) ?></div>
        <div id="school_of_enrollment"><?= $student->getSOEname($year) ?></div>
        </body>
        </html>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        $dompdf = new Dompdf();
        $dompdf->set_paper('letter', 'landscape');
        $dompdf->load_html($content);
        $dompdf->render();
        return $dompdf->output();
    }

    public static function get2018PDFcontent(mth_testOptOut $form, mth_student $student)
    {
        if (!($year = $form->school_year()) || !$student->getSchoolOfEnrollment(true, $form->school_year())
            || !($parent = $student->getParent())
        ) {
            return false;
        }
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>
                <?= $student->getPreferredFirstName() ?>'s State Testing Opt-Out Form - <?= $form->school_year() ?>
            </title>
            <style>
                body, html {
                    font-family: 'Arial', sans-serif;
                    font-size: 12pt;
                    margin: 0;
                    width: 11in;
                    height: 8.5in;
                }

                div {
                    position: absolute;
                }

                #form_overlay {
                    top: 0;
                    left: 0;
                    z-index: 9999;
                    width: 11in;
                }

                #form_overlay img {
                    width: 100%;
                }

                #title_school_year {
                    top: 1.39in;
                    left: 4.8in;
                    font-weight: bold;
                    font-size:14pt;
                }

                #school_year {
                    top: 2.73in;
                    left: 7.45in;
                    font-size:11pt;
                }

                 #secondaryschool_year {
                    top: 3.13in;
                    left: 7.45in;
                    font-size:11pt;
                }

                #student_name {
                    top: 7.35in;
                    left: 1.43in;
                }

                #student_id {
                    top: 7.35in;
                    left: 7.43in;
                }

                #parent_signature {
                    top: 7.63in;
                    left: 2in;
                    width: 4in;
                    height: .5in;
                }

                #parent_signature img {
                    max-height: .5in;
                }

                #parent_signature_date {
                    top: 7.8in;
                    left: 7.43in;
                }

                #parent_name {
                    top: 7.54in;
                    left: 2.2in;
                }

                #parent_phone {
                    top: 7.54in;
                    left: 7.7in;
                }

                #student_grade_level {
                    top: 8in;
                    left: 8.5in;
                }

                #school_of_enrollment {
                    top: 8in;
                    left: 1.6in;
                }

            </style>
        </head>
        <body>
        <?php $prefix = $student->getGradeLevelValue($year) < 7?'':'secondary';?>
        <div id="form_overlay">
            <img
                src="<?= $_SERVER['DOCUMENT_ROOT'] ?>/_/mth_includes/testing_exemption_forms/2018ParentalExclusionForm-<?= $student->getGradeLevelValue($year) < 7 ? 'Elementary' : 'Secondary' ?>.png">
        </div>
        <div id="title_school_year">
            <?= $form->school_year() ?>
        </div>
        <div id="<?=$prefix?>school_year"><?= $form->school_year() ?></div>
        <div id="student_name"><?= $student ?></div>
        <div id="student_id"><?= str_pad($student->getID(), 10, '0', STR_PAD_LEFT) ?></div>
        <div id="parent_signature">
            <img src="data:image/png;base64,<?= base64_encode($form->sig_file_contents()) ?>">
            <!-- <img src="<?= $_SERVER['DOCUMENT_ROOT'] ?>/_/admin/pdf-creator/1/Signature-on-file.png"/> -->
        </div>
        <div id="parent_signature_date"><?= $form->date_submitted('n/j/Y') ?></div>
        <div id="parent_name"><?= $parent ?></div>
        <div id="parent_phone"><?= $parent->getPhone() ?></div>
        <div id="student_grade_level"><?= $student->getGradeLevelValue($year) ?></div>
        <div id="school_of_enrollment"><?= $student->getSOEname($year) ?></div>
        </body>
        </html>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        $dompdf = new Dompdf();
        $dompdf->set_paper('letter', 'landscape');
        $dompdf->load_html($content);
        $dompdf->render();
        return $dompdf->output();
    }

    public static function get2020PDFcontent(mth_testOptOut $form, mth_student $student)
    {
        if (!($year = $form->school_year()) || !$student->getSchoolOfEnrollment(true, $form->school_year())
            || !($parent = $student->getParent())
        ) {
            return false;
        }
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>
                <?= $student->getPreferredFirstName() ?>'s State Testing Opt-Out Form - <?= $form->school_year() ?>
            </title>
            <style>
                body, html {
                    font-family: 'Arial', sans-serif;
                    font-size: 12pt;
                    margin: 0;
                    width: 11in;
                    height: 8.5in;
                }

                div {
                    position: absolute;
                }

                #form_overlay {
                    top: 0;
                    left: 0;
                    z-index: 99999;
                    width: 11in;
                }

                #form_overlay img {
                    width: 100%;
                }

                #title_school_year {
                    top: 1.29in;
                    left: 5.17in;
                    font-style: italic;
                    font-size:13pt;
                    letter-spacing: 1.1;
                }

                #school_year {
                    top: 2.64in;
                    left: 7.09in;
                    font-size:9.5pt;
                }

                 #secondaryschool_year {
                    top: 2.64in;
                    left: 7.09in;
                    font-size:9.5pt;
                }

                #student_name {
                    top: 6.83in;
                    left: 1.43in;
                }

                #student_id {
                    top: 6.83in;
                    left: 7.43in;
                }

                #parent_signature {
                    top: 7.13in;
                    left: 2in;
                    width: 4in;
                    height: .5in;
                }

                #parent_signature img {
                    max-height: .5in;
                }

                #parent_signature_date {
                    top: 7.25in;
                    left: 7.43in;
                }

                #parent_name {
                    top: 7.05in;
                    left: 2.2in;
                }

                #parent_phone {
                    top: 7.04in;
                    left: 7.7in;
                }

                #student_grade_level {
                    top: 7.45in;
                    left: 8.5in;
                }

                #school_of_enrollment {
                    top: 7.48in;
                    left: 1.6in;
                }

            </style>
        </head>
        <body>
        <?php $prefix = $student->getGradeLevelValue($year) < 7?'':'secondary';?>
        <div id="form_overlay" >
            <img
                src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2020ParentalExclusionForm-<?= $student->getGradeLevelValue($year) < 7 ? 'Elementary' : 'Secondary' ?>.png">
        </div>
        <div id="title_school_year">
            <?= $form->school_year()->getLongName() ?>
        </div>
        <div id="<?=$prefix?>school_year"><?= $form->school_year()->getLongName() ?></div>
        <div id="student_name"><?= $student ?></div>
        <div id="student_id"><?= str_pad($student->getID(), 10, '0', STR_PAD_LEFT) ?></div>
        <div id="parent_signature">
            <img src="data:image/png;base64,<?= base64_encode($form->sig_file_contents()) ?>">
            <!-- <img src="<?= $_SERVER['DOCUMENT_ROOT'] ?>/_/admin/pdf-creator/1/Signature-on-file.png"/> -->
        </div>
        <div id="parent_signature_date"><?= $form->date_submitted('n/j/Y') ?></div>
        <div id="parent_name"><?= $parent ?></div>
        <div id="parent_phone"><?= $parent->getPhone() ?></div>
        <div id="student_grade_level"><?= $student->getGradeLevelValue($year) ?></div>
        <div id="school_of_enrollment"><?= $student->getSOEname($year) ?></div>
        </body>
        <body>
            <div>
                <img style="width: 100%;"
                    src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2020ParentalExclusionForm-Descriptions_Page_One.png">
            </div>
        </body>
        <body>
            <div>
                <img style="width: 100%;"
                    src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2020ParentalExclusionForm-Descriptions_Page_Two.png">
            </div>
        </body>
        </html>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        $option = new Options();
        $option->setIsRemoteEnabled(true);
        $dompdf = new Dompdf();
        $dompdf->setOptions($option);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->loadHtml($content);
        $dompdf->render();
        return $dompdf->output();
    }
    
    public static function get2022PDFcontent(mth_testOptOut $form, mth_student $student)
    {
        if (!($year = $form->school_year()) || !$student->getSchoolOfEnrollment(true, $form->school_year())
            || !($parent = $student->getParent())
        ) {
            return false;
        }
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
            <head>
                <title>
                    <?= $student->getPreferredFirstName() ?>'s State Testing Opt-Out Form - <?= $form->school_year() ?>
                </title>
                <style>
                    body, html {
                        font-family: 'Arial', sans-serif;
                        font-size: 12pt;
                        margin: 0;
                        width: 11in;
                        height: 8.5in;
                    }

                    div {
                        position: absolute;
                    }

                    #form_overlay {
                        top: 0;
                        left: 0;
                        z-index: 99999;
                        width: 11in;
                    }

                    #form_overlay img {
                        width: 100%;
                    }

                    #elementary_student_name {
                        top: 6.56in;
                        left: 1.7in;
                    }

                    #elementary_student_id {
                        top: 6.56in;
                        left: 8.17in;
                    }

                    #elementary_parent_name {
                        top: 6.83in;
                        left: 2.63in;
                    }

                    #elementary_parent_phone {
                        top: 6.83in;
                        left: 7.05in;
                    }

                    #elementary_parent_signature {
                        top: 6.8in;
                        left: 2in;
                        width: 4in;
                        height: .5in;
                    }

                    #elementary_parent_signature img {
                        max-height: .5in;
                    }

                    #elementary_parent_signature_date {
                        top: 7.08in;
                        left: 8.74in;
                    }

                    #elementary_school_of_enrollment {
                        top: 7.34in;
                        left: 1.9in;
                    }

                    #elementary_student_grade_level {
                        top: 7.34in;
                        left: 8.9in;
                    }

                    #secondary_student_name {
                        top: 6.73in;
                        left: 1.7in;
                    }

                    #secondary_student_id {
                        top: 6.73in;
                        left: 8.17in;
                    }

                    #secondary_parent_name {
                        top: 7in;
                        left: 2.7in;
                    }

                    #secondary_parent_phone {
                        top: 7in;
                        left: 7.2in;
                    }

                    #secondary_parent_signature {
                        top: 7in;
                        left: 2in;
                        width: 4in;
                        height: .5in;
                    }

                    #secondary_parent_signature img {
                        max-height: .5in;
                    }

                    #secondary_parent_signature_date {
                        top: 7.25in;
                        left: 8.74in;
                    }

                    #secondary_school_of_enrollment {
                        top: 7.52in;
                        left: 1.9in;
                    }

                    #secondary_student_grade_level {
                        top: 7.52in;
                        left: 8.9in;
                    }
                </style>
            </head>
            <body>
                <?php $prefix = $student->getGradeLevelValue($year) < 7?'elementary':'secondary';?>
                <div id="form_overlay" >
                    <img
                        src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2022ParentalExclusionForm-<?= $student->getGradeLevelValue($year) < 7 ? 'Elementary' : 'Secondary' ?>.png">
                </div>
                <div id="<?=$prefix?>_student_name" class="data-value"><?= $student ?></div>
                <div id="<?=$prefix?>_student_id" class="data-value"><?= str_pad($student->getID(), 10, '0', STR_PAD_LEFT) ?></div>
                <div id="<?=$prefix?>_parent_name" class="data-value"><?= $parent ?></div>
                <div id="<?=$prefix?>_parent_phone" class="data-value"><?= $parent->getPhone() ?></div>
                <div id="<?=$prefix?>_parent_signature" class="data-value">
                    <img src="data:image/png;base64,<?= base64_encode($form->sig_file_contents()) ?>">
                </div>
                <div id="<?=$prefix?>_parent_signature_date" class="data-value"><?= $form->date_submitted('n/j/Y') ?></div>
                <div id="<?=$prefix?>_school_of_enrollment" class="data-value"><?= $student->getSOEname($year) ?></div>
                <div id="<?=$prefix?>_student_grade_level" class="data-value"><?= $student->getGradeLevelValue($year) ?></div>
            </body>
            <body>
                <div>
                    <img style="width: 100%;"
                        src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2022ParentalExclusionForm-Descriptions_Page_One.png">
                </div>
            </body>
            <body>
                <div>
                    <img style="width: 100%;"
                        src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2022ParentalExclusionForm-Descriptions_Page_Two.png">
                </div>
            </body>
            <body>
                <div>
                    <img style="width: 100%;"
                        src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2022ParentalExclusionForm-Descriptions_Page_Three.png">
                </div>
            </body>
            <body>
                <div>
                    <img style="width: 100%;"
                        src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/2022ParentalExclusionForm-Descriptions_Page_Four.png">
                </div>
            </body>
        </html>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        $option = new Options();
        $option->setIsRemoteEnabled(true);
        $dompdf = new Dompdf();
        $dompdf->setOptions($option);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->loadHtml($content);
        $dompdf->render();
        return $dompdf->output();
    }

public static function getOregonPDFcontent(mth_testOptOut $form, mth_student $student)
{
    if (!($year = $form->school_year()) || !$student->getSchoolOfEnrollment(true, $form->school_year())
        || !($parent = $student->getParent())
    ) {
        return false;
    }
    ob_start();
    ?>
    
    <!DOCTYPE html>
    <html>
    <head>
        <title>
            <?= $student->getPreferredFirstName() ?>'s State Testing Opt-Out Form - <?= $form->school_year() ?>
        </title>
        <style>
            body, html {
                font-family: 'Arial', sans-serif;
                font-size: 11pt;
                margin: 0;
                width:8.5in ;
                height: 11in;
            }

            div {
                position: absolute;
            }

            #form_overlay {
                top: 0;
                left: 0;
                z-index: -1;
                width:8.5in;
            }

            #form_overlay img {
                width: 100%;
            }

            #title_school_year {
                top: 0.53in;
                left: 5.5in;
                font-style: bold;
                font-family: 'Arial';
                font-size:18pt;
                color:#26466E;
                background:#fff; /*it will cover the original values*/
            }

            #title_school_year_bottom{
                top: 0.51in;
                left: 3.7in;
                font-style: bold;
                font-family: 'Arial';
                font-size:18pt;
                color:#26466E;
                background:#fff; /*it will cover the original values*/
            }

            #school_year {
                top: 2.64in;
                left: 7.09in;
                font-size:9.5pt;
            }

             #secondaryschool_year {
                top: 2.64in;
                left: 7.09in;
                font-size:9.5pt;
            }

            #student_name_last {
                top: 1.782in;
                left: 2.6in;
            }
            #student_name_first {
                top: 2.155in;
                left: 2.6in;
            }

            #student_grade_level {
                top: 2.525in;
                left: 2.6in;
            }
            #school_of_enrollment {
                top: 2.90in;
                left: 2.6in;
            }
            #school_year_start_end{
                top: 3.48in;
                left: 5.42in;
                font-size:12.5pt;
                font-style:bold;
                background:#fff;
            }

            #checkMark1{
                top: 3.8in;
                left: 0.7in;
            }
            #checkMark2{
                top: 4.2in;
                left: 0.7in;
            }

            #january{
                top: 4.78in;
                left: 1.23in;
                font-style: bold;
                background:#fff;
            }

            #march{
                top: 4.78in;
                left: 4.3in;
                font-style: bold;
                background:#fff;
            }

            #schoolYearSmall{
                top:5.19in;
                left: 3.33in;
                background:#fff;
            }

            #student_id {
                top: 6.83in;
                left: 7.43in;
            }

            #parent_signature {
                top: 6.2in;
                left:3in;
                width: 3.5in;
                height: .5in;
            }

            #parent_signature img {
                max-height: .5in;
            }

            #parent_signature_date {
                top: 6.195in;
                left: 6.5in;
            }

            #parent_name {
                top:6.8in;
                left: 3.2in;
            }
        </style>
    </head>
    <body>
    <?php $prefix = $student->getGradeLevelValue($year); ?>
    <div id="form_overlay" >
        <img src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/Oregon_opt_out_P1.jpg">
    </div>
    
    <div id="<?=$prefix?>school_year"> </div>
    <div id="student_name_last"><?= $student->getLastName()?></div>
    <div id="student_name_first"><?= $student->getFirstName()?></div>
    <div id="student_grade_level"><?= $student->getGradeLevelValue($year) ?></div>
    <div id="school_of_enrollment"><?= $student->getSOEname($year) ?></div>
    
    <div id="checkMark1">
        <img style="width:20px; height:20px"
        src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/checkImage.png">

    </div>
    <div id="checkMark2">
        <img style="width:20px; height:20px"
        src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/checkImage.png">

    </div>
   
    <div id="parent_signature">
        <img src="data:image/png;base64,<?= base64_encode($form->sig_file_contents()) ?>">
    </div>
    <div id="parent_signature_date"><?= $form->date_submitted('n/j/Y') ?></div>
    <div id="parent_name"><?= $parent ?></div>
    </body>
    <?php /* no page 2 needed
    <body>
        <div id="form_overlay" >
            <img src="<?= INFOCENTER_URI ?>_/mth_includes/testing_exemption_forms/notice_opt_out_form_oregon_p1.png">
        </div>
        <div id="title_school_year_bottom">2022-23</div>
    </body>*/?>
    </html>
    <?php
    $content = ob_get_contents();
    ob_end_clean();
    
    $option = new Options();
    $option->setIsRemoteEnabled(true);
    $dompdf = new Dompdf();
    $dompdf->setOptions($option);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->loadHtml($content);
    $dompdf->render();
    return $dompdf->output();
    }
}
