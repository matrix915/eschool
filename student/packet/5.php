<?php
/* @var $parent mth_parent */

use mth\student\SchoolOfEnrollment;

/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */
/* @var $packetRunValidation */

if (!packetReadyToSubmit($packet, $student, $parent)) {
  header('Location: ' . $packetURI);
  exit();
}

if (!empty($_GET['packetForm5'])) {
    core_loader::formSubmitable('packetForm5-' . $_GET['packetForm5']) || die();

    $packet->agreesToPolicy($_POST['agrees_to_policy']);
    $packet->approveEnrollment($_POST['approves_enrollment']);
    $packet->setFERPAagreement($_POST['ferpa_agreement']);
    $packet->photoPerm($_POST['photo_permission']);
    $packet->dirPerm($_POST['dir_permission']);
    $packet->saveSignatureFile($_POST['signatureContent']);
    $packet->setReenrollFiles([], true);
    $packet->submit($_POST['signature_name']);
    $packet->nonDiplomaSeeking($_POST['nonDiplomaStudent']);

    $packetStep = 6;

    header('Location: ' . $packetURI);
    exit();
}
core_loader::addHeaderItem('
<!--[if lt IE 9]>
<script type="text/javascript" src="/_/mth_includes/jSignature/libs/flashcanvas.min.js"></script>
<![endif]-->');
core_loader::addJsRef('jSignature', '/_/mth_includes/jSignature/libs/jSignature.min.js');

$policy_content = "I have read, understand, and agree to abide by the information outlined in the <a href='https://www.mytechhigh.com/enrollment-packet-policies/'
     target='_blank'>Enrollment Packet Policies page</a>, including the repayment policy for withdrawing early or failing to demonstrate active participation(up to $350/course)";
cms_page::setPageContent($policy_content, 'Packet Submission Policy', cms_content::TYPE_HTML);
core_loader::printHeader('student');
?>
<div class="page">
    <?= core_loader::printBreadCrumb('window'); ?>
    <div class="page-content container-fluid">
        <form action="?packetForm5=<?= uniqid() ?>" id="packetForm5" method="post" enctype="multipart/form-data">
            <div class="card">
                <?php include core_config::getSitePath() . '/student/packet/header.php'; ?>
                <div class="card-block">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="checkbox-custom checkbox-primary">
                                <input type="hidden" name="agrees_to_policy" value="0">
                                <input type="checkbox" name="agrees_to_policy" id="agrees_to_policy" value="1" required>
                                <label for="agrees_to_policy">
                                    <?= cms_page::getDefaultPageContent('Packet Submission Policy', cms_content::TYPE_HTML) ?>
                                </label>
                            </div>
                            <label for="agrees_to_policy" class="error" style="display: none;"></label>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="hidden" name="approves_enrollment" value="0">
                                <input type="checkbox" name="approves_enrollment" id="approves_enrollment" value="1" required>
                                <label for="approves_enrollment">
                                    I approve for my student to be enrolled in any one of the following schools
                                    <?php
                                    $array = array_map(function (SchoolOfEnrollment $SOE) {
                                        return $SOE->getLongName();
                                    }, SchoolOfEnrollment::getVisible());
                                    $last  = array_slice($array, -1);
                                    $first = join(', ', array_slice($array, 0, -1));
                                    $both  = array_filter(array_merge(array($first), $last), 'strlen');
                                    echo "(" . join(', and ', $both) . ")";
                                    ?>
                                </label>
                            </div>
                            <label for="approves_enrollment" class="error" style="display: none;"></label>

                            <div class="checkbox-custom checkbox-primary">
                                <input type="hidden" name="nonDiplomaStudent" value="0">
                                <input type="checkbox" name="nonDiplomaStudent" id="nonDiplomaStudent" value="1" required>
                                <label for="nonDiplomaStudent">
                                    By selecting a non-diploma seeking track, I understand that our 
                                    student(s) will not be receiving course credit towards high school diploma requirements.
                                </label>
                            </div>
                            <label for="nonDiplomaStudent" class="error" style="display: none;"></label>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ferpa_agreement">FERPA Agreement Options</label>
                                <select name="ferpa_agreement" id="ferpa_agreement" required class="form-control">
                                    <?php foreach (mth_packet::getAvailableFerpa() as $ferpaID => $ferpaOption) : ?>
                                        <option value="<?= $ferpaID ?>"><?= $ferpaOption ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="photo_permission">Student Photo Permission</label>
                                <select name="photo_permission" id="photo_permission" required class="form-control">
                                    <option></option>
                                    <?php foreach (mth_packet::getPhotoPermOpts() as $optID => $desc) : ?>
                                        <option value="<?= $optID ?>" <?= $optID == 2 ? 'selected' : '' ?>><?= $desc ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dir_permission">School Student Directory Permission</label>
                                <select name="dir_permission" id="dir_permission" required class="form-control">
                                    <option></option>
                                    <?php foreach (mth_packet::getDirPermOpts() as $optID => $desc) : ?>
                                        <option value="<?= $optID ?>" <?= $optID == 2 ? 'selected' : '' ?>><?= $desc ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div style="max-width: 500px; margin: 40px auto;">
                        <p>
                            <label for="signature_name">
                                I certify that I am the legal guardian or custodial parent of this student.
                                I certify that I have read and understood the information on this registration site and that the
                                information entered is true and accurate.
                            </label>
                            <input type="text" name="signature_name" id="signature_name" placeholder="i.e. Jonathan J. Doe" required class="form-control">
                            <small>Type full legal parent name and provide a Digital Signature below.</small>
                        </p>
                        <div class="p">
                            <label>Signature
                                <small style="display: inline">(use the mouse to sign)</small>
                            </label>
                            <div style="height: 0; overflow: hidden;"><input type="text" name="signatureContent" id="signatureContent" required></div>
                            <label for="signatureContent" class="error"></label>
                            <div id="signature"></div>
                            <button type="button" class="btn btn-round btn-success" onclick="sigdiv.jSignature('reset')">Reset Signature</button>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="button" class="btn btn-primary btn-round btn-lg" onclick="captureSignatureAndSubmitForm()">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php

core_loader::printFooter('student');
?>

<script>
    //$.validator.setDefaults({debug:true});
    var sigdiv = $("#signature");
    $(function() {
        sigdiv.jSignature();
    });

    var packetForm = $('#packetForm5');
    packetForm.validate();
    <?php if ($packetRunValidation) : ?>
        if (!packetForm.valid()) {
            packetForm.submit(); //this will focus the cursor on the problem fields
        }
    <?php endif; ?>

    function captureSignatureAndSubmitForm() {
        if (sigdiv.jSignature('isModified')) {
            $('#signatureContent').val(sigdiv.jSignature("getData", "svgbase64")[1]);
        } else {
            $('#signatureContent').val('');
        }
        packetForm.submit();
    }
</script>