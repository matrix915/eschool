<?php

use Dompdf\Dompdf;

if (!isset($packet) || !is_object($packet)) {
    ($packet = mth_packet::getByID($_GET['packet'])) || die();
}
($student = $packet->getStudent()) || die('Student Not Found. Please refresh');
($parent = $student->getParent()) || die('Parent Not Found. Please refresh');

$file_data = null;
$file_type = null;
if (($file = mth_packet_file::getByID($packet->getSignatureFileID()))) {
    $file_data = base64_encode($file->getContents());
    $file_type = $file->getType();
}

// if(date('n')>2){ 
//     $year = mth_schoolYear::getNext();
// }else{
//     $year = mth_schoolYear::getCurrent();
// }

//$year = $packet->getDateAccepted()?mth_schoolYear::getByStartYear($packet->getDateAccepted('Y')):mth_schoolYear::getCurrent();
if (isset($yearOverride)) {
    $year = $yearOverride;
} else {
    $year = mth_packet::getActivePacketYear($packet);
}

if (empty($_GET['html'])) {
    ob_start();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title><?= $student->getPreferredFirstName() ?>'s Packet - <?= date('F j, Y') ?></title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 8pt;
        }

        table {
            width: 100%;
        }

        table td {
            vertical-align: top;
            width: 50%;
            padding: .0625in;
        }

        table table {
            border-collapse: collapse;
        }

        table table th,
        table table td {
            border-bottom: solid .25pt #ddd;
            padding: .0625in .125in;
            text-align: left;
            vertical-align: top;
            width: auto;
        }

        p,
        h2 {
            margin: 0 0 .125in 0;
        }

        table table td h2 {
            margin: 0;
        }

        hr {
            border: none;
            border-bottom: solid .25pt #ddd;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td>
                <table>
                    <tr>
                        <td colspan="2">
                            <h2>Student Info</h2>
                        </td>
                    </tr>
                    <tr>
                        <th>Legal Name:</th>
                        <td><?= $student->getFirstName() ?> <?= $student->getMiddleName() ?> <?= $student->getLastName() ?></td>
                    </tr>
                    <tr>
                        <th>Preferred Name:</th>
                        <td><?= $student ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= $student->getEmail() ?></td>
                    </tr>
                    <tr>
                        <th>DOB:</th>
                        <td><?= $student->getDateOfBirth('F j, Y') ?></td>
                    </tr>
                    <tr>
                        <th>Grade:</th>
                        <td><?= $student->getGradeLevel(false, false, mth_schoolYear::getApplicationYear()->getID()) ?> <small>(<?= mth_schoolYear::getApplicationYear() ?>)</small></td>
                    </tr>
                    <tr>
                        <th>Gender:</th>
                        <td><?= $student->getGender() ?></td>
                    </tr>
                    <tr>
                        <th>Hispanic/Latino:</th>
                        <td><?= $packet->isHispanic() ? 'Yes' : 'No' ?></td>
                    </tr>
                    <tr>
                        <th>Race:</th>
                        <td><?= $packet->getRace() ?></td>
                    </tr>
                    <tr>
                        <th>First language learned by child:</th>
                        <td><?= $packet->getLanguage() ?></td>
                    </tr>
                    <tr>
                        <th>Language used most often by adults in the home:</th>
                        <td><?= $packet->getLanguageAtHome() ?></td>
                    </tr>
                    <tr>
                        <th>Language used most often by child in the home:</th>
                        <td><?= $packet->getLanguageHomeChild() ?></td>
                    </tr>
                    <tr>
                        <th>Language used most often by child with friends outside the home:</th>
                        <td><?= $packet->getLanguageFriends() ?></td>
                    </tr>
                    <tr>
                        <th>Preferred correspondence language for adults in the home:</th>
                        <td><?= $packet->getLanguageHomePreferred() ?></td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <h2>School History</h2>
                        </td>
                    </tr>
                    <tr>
                        <th>Special Ed:</th>
                        <td>
                            <p><?= $packet->getSpecialEd() ?></p>

                        </td>
                    </tr>
                    <tr>
                        <th>School District of Residence:</th>
                        <td><?= $packet->getSchoolDistrict() ?></td>
                    </tr>
                    <tr>
                        <th>Last School Attended:</th>
                        <td>
                            <p><?= $packet->getLastSchoolType() ?></p>
                            <div><?= $packet->getLastSchoolName() ?></div>
                            <div><?= $packet->getLastSchoolAddress() ?></div>
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table>
                    <tr>
                        <td colspan="2">
                            <h2>Parent/Guardian Info</h2>
                        </td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td><?= $parent ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?= $parent->getPhoneNumbers(true) ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= $parent->getEmail() ?></td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td>
                            <p><?= $student->getAddress() ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h2>Secondary Contact</h2>
                        </td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td><?= $packet->getSecondaryContactFirst() ?> <?= $packet->getSecondaryContactLast() ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?= $packet->getSecondaryPhone() ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= $packet->getSecondaryEmail() ?></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h2>Household Income</h2>
                        </td>
                    </tr>
                    <tr>
                        <th>Household Size:</th>
                        <td><?= $packet->getHouseholdSize() ?></td>
                    </tr>
                    <tr>
                        <th>Household Income:</th>
                        <td><?= $packet->getHouseholdIncome() ?></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h2>Other</h2>
                        </td>
                    </tr>

                    <tr>
                        <th>The student presently living:</th>
                        <td><?= $packet->getLivingLocation() ?></td>
                    </tr>

                    <tr>
                        <th>The student lives with:</th>
                        <td><?= $packet->getLivesWith() ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="text-align: center; padding-top: .25in">
        <?= $packet->getUnderstandsSpecialEd()
            ? '<p>I understand that an IEP is an important legal document that defines a student\'s educational plan and that it must be reviewed regularly by the school\'s Special Education IEP team.  I also understand that all final curriculum and scheduling choices for students with an IEP must be made in consultation between the parent and the school\'s Special Ed team.</p>' : '' ?>
        <?= $packet->getPermissionToRequestRecords()
            ? '<p>I understand that ' . $student->getPreferredFirstName() . '\'s records will be requested from ' . ($student->getGender() == 'Male' ? 'his' : 'her') . ' prior school.</p>' : '' ?>
        <?= $packet->agreesToPolicy()
            ? '<p>I agree to the information referenced in the <i>Enrollment Packet Policy Page</i>, including the repayment policy for withdrawing early or failing to demonstrate active participation (up to $350/course).</p>' : '' ?>
        <?= $packet->approveEnrollment()
            ? '<p>I approve for my student to be enrolled in any one of the following schools <br>
            (Gateway Preparatory Academy, Digital Education Center - Tooele County School District, Advanced Learning Center - Nebo School District, and Southwest Education Academy - Iron County School District).</p>' : '' ?>
        
        <?= $packet->nonDiplomaSeeking()
            ? '<p> By selecting a non-diploma seeking track, I understand that our 
            student(s) will not be receiving course credit towards high school diploma requirements.</p><br/>' : '' ?>
        
        <p><?= $packet->getFERPAagreement() ?></p>
        <p><?= $packet->photoPerm() ?></p>
        <p><?= $packet->dirPerm() ?></p>
    </div>
    <br/>
    <br/>
    <br/>
    <br/>
    <p style="text-align: right; margin-right: 1in;"><?= $packet->getSignatureName() ?></p>
    <?php if (($file = mth_packet_file::getByID($packet->getSignatureFileID()))) : ?>
        <p style="text-align: right; margin-right: 1in;"><img style="width: 2in;" src="data:<?= $file_type ?>;base64,<?= $file_data ?>">
        </p>
    <?php endif; ?>
    <p style="text-align: right; margin-right: 1in;">DATE: <?= $packet->getDateSubmitted('m/d/Y') ?></p>
    <?php if (strlen($packet->getSpecialEdDesc()) > 100) : ?>
        <h3>Special Ed Description</h3>
        <p><?= $packet->getSpecialEdDesc() ?></p>
    <?php endif; ?>
</body>

</html>
<?php
if (empty($_GET['html'])) {
    $content = ob_get_contents();
    ob_end_clean();

    $dompdf = new Dompdf();
    $dompdf->load_html($content);
    $dompdf->render();
    header('Content-type: application/pdf');
    echo $dompdf->output();
}
