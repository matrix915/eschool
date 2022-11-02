<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use mth\yoda\memcourse;
use mth\yoda\courses;
use mth\yoda\assessment;

/**
 * Description of Transition
 *
 * @author Rex
 */
class mth_views_transitioned
{
  public static function getNewAffidavit(mth_transitioned  $transition, $html = false)
  {
    $student =  $transition->student();
    $parent = $student->getParent();
    $address = $parent->getAddress();
    $packet = mth_packet::getStudentPacket($student);
    //$year =  $transition->school_year();
    if (!$html) {
      ob_start();
    }
?>
    <!DOCTYPE html>
    <html>

    <head>
      <title>Home School Affidavit Letter</title>
      <style>
        body {
          font-family: 'Ariel', sans-serif;
          font-size: 9.5pt;
          margin: 0;
        }

        .form_field {
          border-bottom: solid 1px #333;
          font-weight: bold;
          height: 1.33em;
          padding: 0 .4em;
          text-align: left;
        }
      </style>

    </head>

    <body>
      <div style="height: 9in; page-break-inside: avoid;">
        <div>
          <?= $parent ?>
        </div>
        <div>
          <?= $address->getStreet(); ?><?= ($address->getStreet2()) ? ' ' . $address->getStreet2() . ', ' : ', ' ?>
          <?= $address->getCity() ?>,
          <?= $address->getZip() ?>
          </td>
        </div>
        <p>
          <?= //'05/18/' . $year->getDateEnd('Y') 
            $transition->datetime() ? $transition->datetime('m/d/Y') : date('m/d/Y')
          ?>
        </p>
        <p>
            I, <?= $parent ?>(Parent/guardian of <?= $student->getFirstName() ?> <?= $student->getLastName() ?>), declare my intent to homeschool my student(s). I understand and agree:
        </p>
        <ol>
            <li>I am solely responsible for the education of my school age minor.</li>
            <li>I am solely responsible for selecting instructional materials and textbooks.</li>
            <li>I am solely responsible for setting the time, place, and method of instruction.</li>
            <li>I am solely responsible for testing or otherwise evaluating the home school instruction my student receives.</li>
            <li>If my student(s) is home schooled, he/she may only earn school district credit consistent with district policies.</li>
        </ol>
        <p>
            I accept full responsibility for my student(s) and understand that he/she may not qualify for a high school diploma.
        </p>
        <p>
            For students with IEPs or identified through child find: My decision to homeschool does not in
            any way imply that the school district did not provide a free and appropriate public education
            and I understand and agree that my student has no individual right to receive some or all of the
            special education and related services he/she would receive if enrolled in a public school,
            unless I have arranged for dual enrollment consistent with state law, Section 53A-11-102.5 and
            Utah State Board of Education rule, R277-438.
        </p>
        <p>
            I have read this agreement and understand my obligations as a homeschool parent.
        </p>
          <p>
            <?= $parent ?>
          </p>
        <?php
        $_signature = core_config::getSitePath() . '/_/admin/pdf-creator/1/Signature-on-file.png';
        $signature = base64_encode(file_get_contents($_signature));
        $sig_type = \core\Response::TYPE_PNG;
        ?>
        <div style="width: 4in;">
          <img style="max-height: 2em; margin-top:-1em;" src="data:<?= $sig_type ?>;base64,<?= $signature ?>">
        </div>
        <table class="bold-text" style="margin-top:1em;">
          <tr>
            <td>
              Sworn before me on this
            </td>
            <td>
              <div class="form_field">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
            </td>
            <td>
              day of
            </td>
            <td>
              <div class="form_field"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>
            </td>
            <td>,20</td>
            <td>
              <div class="form_field">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
            </td>
            <td>.</td>
          </tr>
        </table>
        <p style="margin-bottom: 5em;"><span class="form_field"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span>
          <br><strong>Notary</strong>
        </p>
        <div style="margin-top: 0.4em;border-bottom:1px dashed #000;margin-bottom:1em">&nbsp;</div>
        <strong>Certificate of Exemption</strong>
        <p style="margin-bottom:3em;">The above named minors are excused from attendance.</p>
        <table style="width:100%">
          <tr>
            <td style="border: 1px solid #000;
    background: #000;">
            </td>
            <td style="border: 1px solid #000;
    background: #000;">
            </td>
          </tr>
          <tr>
            <td>
              District signature
            </td>
            <td>
              Date
            </td>
          </tr>
        </table>
      </div>
    </body>
    </html>
    <?php
    if (!$html) {
      $content = ob_get_contents();
      ob_end_clean();
      $option = new Options();
      $option->setIsRemoteEnabled(true);
      $dompdf = new Dompdf();
      $dompdf->setOptions($option);
      $dompdf->load_html($content);
      $dompdf->render();
      return $dompdf->output();
    }
  }
  public static function getAffidavit(mth_transitioned  $transition)
  {
    $student =  $transition->student();
    $parent = $student->getParent();
    $address = $parent->getAddress();
    $packet = mth_packet::getStudentPacket($student);
    $year =  $transition->school_year();
    ob_start();
    ?>< !DOCTYPE html>
      <html>

      <head>
        <title>Home School Affidavit</title>
        <style>
          body {
            font-family: 'Ariel', sans-serif;
            font-size: 9.5pt;
            margin: 0;
          }

          .info-table {
            border-collapse: collapse;
            width: 100%;
          }

          .table {
            width: 100%;
          }

          td {
            white-space: nowrap;
          }

          .info-table td {
            border: 1px solid #262324;
            vertical-align: top;
          }

          .separator-tr {
            background-color: #262324;
          }

          .center-text {
            text-align: center;
          }

          .small-col {
            width: 10pt;
          }

          .form_field {
            border-bottom: solid 1px #333;
            font-weight: bold;
            height: 1.33em;
            padding: 0 .4em;
            text-align: left;
          }

          .bold-text {
            font-weight: bold;
          }

          .rotate {
            width: 0.5in;
          }

          .rotate .container {
            position: relative;
            overflow: visible;
          }

          .rotate .content:after {
            overflow: visible;
            -moz-transform: rotate(90.0deg);
            /* FF3.5+ */
            -o-transform: rotate(90.0deg);
            /* Opera 10.5 */
            -webkit-transform: rotate(90.0deg);
            /* Saf3.1+, Chrome */
            filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=1);
            /* IE6,IE7 */
            -ms-filter: "progid:DXImageTransform.Microsoft.BasicImage(rotation=1)";
            /* IE8 */
            position: absolute;
          }

          .rotate .gender.content:after {
            content: "M/F";
            left: 10px;
            top: 10px;
          }

          .rotate .renew.content:after {
            content: "New/Renew";
            left: 10px;
            top: 10px;
          }
        </style>
      </head>

      <body>
        <div style="height: 9in; page-break-inside: avoid;">
          <div style="text-align:center">
            <h3>TOOELE COUNTY SCHOOL DISTRICT<br>AFFIDAVIT AND EXEMPTION CERTIFICATE FOR <br>HOME SCHOOL INSTRUCTION </h3>
          </div>
          <table class="info-table">
            <tr class="center-text">
              <td>Student(s)#</td>
              <td>Student Name(s)</td>
              <td class="rotate">
                <div class="container">
                  <div class="content gender"></div>
                </div>
              </td>
              <td>Birth Date(s)</td>
              <td class="rotate">
                <div class="container">
                  <div class="content renew"></div>
                </div>
              </td>
              <td>School student should<br> attend in your area</td>
              <td>Please list any classes<br>
                or activities your<br>
                student may participate<br>
                in at the local school<br>
                with the principal’s<br>
                permission
              </td>
            </tr>
            <tr class="center-text">
              <td><?= $student->getID() ?></td>
              <td><?= $student->getFirstName() ?> <?= $student->getLastName() ?></td>
              <td><?= $student->getGender() ? substr($student->getGender(), 0, 1) : ''; ?></td>
              <td><?= $student->getDateOfBirth('M j, Y') ?></td>
              <td>--</td>
              <td>N/A</td>
              <td>N/A</td>
            </tr>
            <tr class="center-text">
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr class="center-text">
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr class="center-text">
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr class="separator-tr">
              <td colspan="7">&nbsp;</td>
            </tr>
            <tr>
              <td colspan="2">
                Address:<br>
                <?= $address->getStreet(); ?>
                <?php if ($address->getStreet2()) : ?>
                  <?= $address->getStreet2() ?>
                <?php endif; ?>
              </td>
              <td colspan="3">
                City:<br>
                <?= $address->getCity() ?>
              </td>
              <td>
                Zip:<br>
                <?= $address->getZip() ?>
              </td>
              <td>
                Home Phone:<br>
                <?= $parent->getPhone() ?>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                Parent/Guardian:<br>
                <?= $parent ?>
              </td>
              <td colspan="3">
                Address (if different than student):
              </td>
              <td>
                E-mail (optional):
              </td>
              <td>
                Work Phone: <br>
                <?= $packet ? $packet->getSecondaryPhone() : '' ?>
              </td>
            </tr>
            <tr>
              <td colspan="7">Reason for Home Schooling (optional): <br>&nbsp;</td>
            </tr>
          </table>
          <br>
          <div class="center-text" style="font-size:9pt">* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * </div>
          <div class="center-text">
            <h4>PARENT/GUARDIAN AFFIDAVIT</h4>
          </div>
          <div>
            I, <div class="form_field" style="width:2.8in;display:inline-block">
              <?= $parent ?>
            </div>, (Parent/Guardian) of the above named student(s), declare my intent to
            home school my student(s). I understand and agree:
          </div>
          <p>
            1. To provide instruction in the subjects the Utah State Board of Education requires to be taught in public schools.<br>
            2. To provide instruction for 180 days and 990 hours each year.<br>
            3. I am solely responsible for selecting instructional materials and textbooks.<br>
            4. I am solely responsible for setting the time, place and method of instruction.<br>
            5. I am solely responsible for testing or otherwise evaluating the home school instruction my student receives.<br>
            6. If my student is home schooled, he/she may only earn school district credit consistent with school district policies.
          </p>
          <p>
            I accept full responsibility for my student and understand that he/she may not qualify for a high school diploma
            issued by the Tooele County School District or any of its schools.
          </p>
          <p>
            (For students with IEPs or identified through child find): My decision to home school does not in any way imply that
            the school district did not provide a free and appropriate public education and I understand and agree that my student
            has no individual right to receive some or all of the special education and related services he would receive if enrolled
            in a public school in Tooele County School District, unless I have arranged for dual enrollment consistent with state
            law, Section 53A-11-102.5 and Utah State Board of Education rule, R277-438.
          </p>
          <p>
            I have read this agreement and understand my obligations as a home school parent.
          </p>
          <h4 style="text-decoration: underline">TO BE SIGNED BEFORE A NOTARY:</h4>
          <div>
            <table class="table">
              <tr>
                <td>
                  Parent/Guardian Signature:
                </td>
                <td>
                  <?php
                  $_signature = core_config::getSitePath() . '/_/admin/pdf-creator/1/Signature-on-file.png';
                  $signature = base64_encode(file_get_contents($_signature));
                  $sig_type = \core\Response::TYPE_PNG;
                  ?>
                  <div class="form_field" style="width: 4in;">
                    <img style="max-height: 2em; margin-top:-1em;" src="data:<?= $sig_type ?>;base64,<?= $signature ?>">
                  </div>
                </td>
                <td>
                  Date:
                </td>
                <td>
                  <span class="form_field"><?= '05/18/' . $year->getDateEnd('Y') //$transition->datetime() ? $transition->datetime('m/d/Y') : date('m/d/Y') 
                                            ?></span>
                </td>
              </tr>
            </table>
            <table class="bold-text">
              <tr>
                <td>
                  Subscribed and sworn to before me this
                </td>
                <td>
                  <div class="form_field">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                </td>
                <td>
                  day of
                </td>
                <td>
                  <div class="form_field"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>
                </td>
                <td>,20</td>
                <td>
                  <div class="form_field">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                </td>
                <td>.</td>
              </tr>
            </table>
            <br>
            <div style="text-align:right;">
              Notary Public
              <span class="form_field">
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
              </span>
            </div>
            <div>
              My Commission expires:
              <span class="form_field">
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
              </span>
            </div>
            <br>
            <div>
              Residing at:
              <span class="form_field">
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp;
              </span>
            </div>
          </div>
        </div>
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



    // $dompdf->render();
    // $dompdf->add_info('Title', 'test');
    // $dompdf->stream($title.".pdf",array("Attachment"=>0));
  }

  public static function getWithdrawalLetter(mth_transitioned $transition)
  {
    $siteURL = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
    $student =  $transition->student();
    $parent = $student->getParent();
    $address = $parent->getAddress();
    $year =  $transition->school_year();
    $LogoStyle = '';
    $schedule = mth_schedule::get($student, $year, true);
    if (
      $schedule
      && ($schedulePeriod = $schedule->getPeriod(1))
      && ($enrollment = courses::getStudentHomeroom($student->getID(), $year))
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
    if ($student->getSchoolOfEnrollment(true, $year) == \mth\student\SchoolOfEnrollment::GPA) {
      $header = 'GPA-head.jpg';
      $footer = 'GPA-foot.jpg';
      $header_type = $footer_type = \core\Response::TYPE_JPEG;
      $schoolAddress = '<b>' . $student->getSOEname($year) . '</b><br>
            ' . $student->getSOEaddress(true, $year);
    } elseif ($student->getSchoolOfEnrollment(true, $year) == \mth\student\SchoolOfEnrollment::ALA) {
      $header = 'ALA-head.jpg';
      $footer = 'ALA-foot.jpg';
      $header_type = $footer_type = \core\Response::TYPE_JPEG;
      $schoolAddress = '<b>' . $student->getSOEname($year) . '</b><br>
            ' . $student->getSOEaddress(true, $year);
    } elseif ($student->getSchoolOfEnrollment(true, $year) == \mth\student\SchoolOfEnrollment::ICSD) {
      $header = 'icsd-logo.jpg';
      $footer = 'ALA-foot.jpg';
      $LogoStyle = 'width:2in;';
      $header_type = $footer_type = \core\Response::TYPE_JPEG;
      $schoolAddress = '<b>' . $student->getSOEname($year) . '</b><br>
            ' . $student->getSOEaddress(true, $year);
    } elseif ($student->getSchoolOfEnrollment(true, $year) == \mth\student\SchoolOfEnrollment::Nyssa) {
      $header = 'nyssa-header.png';
      $footer = 'nyssa-footer.png';
      $LogoStyle = 'width:3in; margin-bottom: 1rem; margin-top: 1rem;';
      $header_type = $footer_type = \core\Response::TYPE_PNG;
      $schoolAddress = '<b>' . $student->getSOEname($year) . '</b><br>
            ' . $student->getSOEaddress(true, $year);
    } elseif ($student->getSchoolOfEnrollment(true, $year) == \mth\student\SchoolOfEnrollment::Tooele) {
      $header = 'Tooele-header.png';
      $footer = 'Tooele-footer.png';
      $header_type = $footer_type = \core\Response::TYPE_PNG;
      $schoolAddress = '<b>' . $student->getSOEname($year) . '</b><br>
            Attn: Linda Kirby<br>
            lkirby@tooeleschools.org<br>
            ' . $student->getSOEaddress(true, $year) . '<br>
            ' . $student->getSOEphones(true, $year);
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
    } elseif ($student->getSchoolOfEnrollment(true, $year) == \mth\student\SchoolOfEnrollment::Nebo) {
      $header = 'nebo-header.png';
      $footer = null;
      $header_type = $footer_type = \core\Response::TYPE_PNG;
      $schoolAddress = '<b>' . $student->getSOEname($year) . '</b><br>
            ' . $student->getSOEaddress(true, $year);
    }
    $graphics_paths = core_config::getSitePath() . '/_/mth_includes/school_of_enrollment_graphics/';
    $header_data = base64_encode(file_get_contents($graphics_paths . $header));
    $footer_data = base64_encode(file_get_contents($graphics_paths . $footer));
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
              <td><span class="form_field"><?= '05/18/' . $year->getDateEnd('Y') //date('m/d/Y') 
                                            ?></span></td>
            </tr>
          </table>
          <p>
            How will your student continue their education?
            <span class="form_field" style="display: inline"><?= $transition->reason() ?  $transition->reason() : 'Transitioned to college/workforce' ?></span>
          </p>
          <p>
            New Public School Name (if applicable):
            <span class="form_field" style="display: inline"><?= $transition->new_school_name() ?  $transition->new_school_name() : 'N/A' ?></span>
          </p>
          <p>
            New School Address (if applicable):
            <span class="form_field" style="display: inline"><?= $transition->new_school_address() ? str_replace("\n", ', ',  $transition->new_school_address(false)) : 'N/A' ?></span>
          </p>
          <p><?php echo $parentPhrase; ?></p>

          <table style="border:none; border-collapse: separate; border-spacing: .4em;">
            <tr>
              <td>Signature:</td>
              <td>
                <span class="form_field" style="width: 4in;">
                  <?php if ($transition->sig_file_hash()) : ?>
                    <img style="max-height: 3em; margin-top:-1em;" src="<?= !empty($_SERVER['HTTPS']) ? 'https' : 'http' ?>://<?= $_SERVER['HTTP_HOST'] ?>/_/mth_includes/mth_file.php?hash=<?= $transition->sig_file_hash() ?>">
                  <?php else : ?>
                    <?= $parent->getName() ?>
                  <?php endif; ?>
                </span>
              </td>
              <td>Date:</td>
              <td><span class="form_field">
                  <?= '05/18/' . $year->getDateEnd('Y') //$transition->datetime() ?  $transition->datetime('m/d/Y') : date('m/d/Y') 
                  ?></span>
              </td>
            </tr>
          </table>
          <?php if ($schedule) : ?>
            <p>Current Enrollment:</p>
            <div style="text-align: center" id="scheduleTable">
              <table style="margin: auto;">
                <tr>
                  <th>Course</th>
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
        <?php if ($footer) : ?>
          <p style="text-align: center; margin:0">
            <img style="width:7.5in" src="data:<?= $footer_type ?>;base64,<?= $footer_data ?>">
          </p>
        <?php endif; ?>
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
