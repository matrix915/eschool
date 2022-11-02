<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */
/* @var $packetRunValidation */

if (($application = mth_application::getStudentApplication($student))) {
    $year = $application->getSchoolYear(true);
} else {
    $year = mth_schoolYear::getCurrent();
}
core_loader::printHeader('student');
?>
<div class="page packet-page">
    <?= core_loader::printBreadCrumb('window');?>
    <div class="page-content container-fluid">
        <div class="card">
            <?php include core_config::getSitePath() . '/student/packet/header.php'; ?>
            <div class="card-block">
                <div class="alert  alert-alt alert-success">
                    Enrollment Packet has been submitted.
                </div>
                <div class="alert alert-info alert-alt"><?= $packet->isAccepted() ? '<b>The Packet has been accepted.</b>' : 'We will review the packet and notify you when it has been received.' ?></div>
                <div class="row">
                    <div class="col-md-6">
                        <h4>Student</h4>
                        <p>
                            Legal Name: <?= $student->getFirstName() ?> <?= $student->getMiddleName() ?> <?= $student->getLastName() ?>
                            <br>
                            Preferred Name: <b><?= $student ?></b><br>
                            Email: <a href="#"><?= $student->getEmail() ?></a>
                        </p>
                        <h4>Address</h4>
                        <p>
                            <?= $parent->getAddress() ?><br>
                            School District of Residence: <b><?= $parent->getAddress()->getSchoolDistrictOfR() ?></b><br>
                            <a href="/_/user/profile">Edit</a>
                        </p>
                        <h4>Parent/Guardian</h4>
                        <p>
                            Name: <b><?= $parent->getName() ?></b><br>
                            Gender: <b><?= $parent->getGender() ?></b><br>
                            Date of Birth: <b><?= $parent->getDateOfBirth('m/d/Y') ?></b><br>
                            Cell Phone: <?= $parent->getPhone('Cell') ?><br>
                            Email: <a  href="#"><?= $parent->getEmail() ?></a><br>
                            <a href="/_/user/profile">Edit</a>
                        </p>
                        <h4>Secondary Contact</h4>
                        <p>
                            Name: <?= $packet->getSecondaryContactFirst() . ' ' . $packet->getSecondaryContactLast() ?><br>
                            Phone: <?= $packet->getSecondaryPhone() ?><br>
                            Email: <?= $packet->getSecondaryEmail() ?>
                        </p>
                        <h4><?= $student->getPreferredFirstName() ?>'s Personal Info</h4>
                        <p>
                            DOB: <b><?= $student->getDateOfBirth('F j, Y') ?></b><br>
                            Gender: <b><?= $student->getGender() ?></b><br>
                            Hispanic/Latino: <?= $packet->isHispanic() ? 'Yes' : 'No' ?><br>
                            Race: <?= $packet->getRace() ?><br>
                            Primary Language: <?= $packet->getLanguage() ?><br>
                            Language at Home: <?= $packet->getLanguageAtHome() ?>
                        </p>
                        <a class="btn btn-primary btn-round" href="/">Back</a>
                    </div>
                    <div class="col-md-6">
                        <h4>Education</h4>
                        <p>
                            Grade: <b><?= $student->getGradeLevel(true, false, $year->getID()) ?> </b> (<?= $year ?>)<br>
                            Special Ed: <?= $packet->getSpecialEd() ?><br>
                         
                        </p>
                        <p>
                            <!-- School District of Residence: <b><?= $packet->getSchoolDistrict() ?></b><br> -->
                            Last School Attended: <?= $packet->getLastSchoolType() ?>
                            <span style="display: block"><?= $packet->getLastSchoolName() ?></span>
                            <span style="display: block"><?= $packet->getLastSchoolAddress() ?></span>
                        </p>
                        <h4>Household Income</h4>
                        <p>
                            Household Size: <?= $packet->getHouseholdSize() ?><br>
                            Household Income: <?= $packet->getHouseholdIncome() ?>
                        </p>
                        <h4>Files</h4>
                        <p>
                            <?php foreach (mth_packet_file::getPacketFiles($packet) as $file): /* @var $file mth_packet_file */ ?>
                                <?php if ($file->getKind() == mth_packet_file::KIND_SIG) {
                                    continue;
                                } ?>
                                <a href="?file=<?= $file->getID() ?>"><?= $file->getName() ?></a><br>
                            <?php endforeach; ?>
                        </p>
                        <h4>Submission</h4>
                        <p>FERPA: <?= $packet->getFERPAagreement() ?></p>
                        <p>Student Photo Permission: <?= $packet->photoPerm(); ?></p>
                        <p>School Student Directory Permission: <?= $packet->dirPerm() ?></p>
                        <p>
                            Signature: <?= $packet->getSignatureName() ?><br>
                            <img style="max-width: 100%" src="?file=<?= $packet->getSignatureFileID() ?>">
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

core_loader::printFooter('student');