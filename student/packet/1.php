<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */
/* @var $packetRunValidation */

if (!empty($_GET['packetForm1'])) {
    core_loader::formSubmitable('packetForm1-' . $_GET['packetForm1']) || die();

    $parent->setDateOfBirth(strtotime($_POST['pdob']));
    $parent->setGender($_POST['pgender']);
    $parent->saveChanges();

    $student->setName($_POST['student']['first'], $_POST['student']['last'], $_POST['student']['middle'], $_POST['student']['preferred-first'], $_POST['student']['preferred-last']);
    $student->setEmail($_POST['student']['email']);

    if (!empty($_POST['parent']['address']['id'])) {
        $address = mth_address::getAddress($_POST['parent']['address']['id']);
    } else {
        $address = mth_address::create($parent);
    }
    $address->saveForm($_POST['parent']['address']);

    if ($_POST['parent']['phone']['id']) {
        $phone = mth_phone::getPhone($_POST['parent']['phone']['id']);
        if ($phone->getNumber() !== mth_phone::formatNumber(mth_phone::sanitizeNumber($_POST['parent']['phone']['number']))
            && $phone->getName() !== 'Cell'
        ) {
            $phone = mth_phone::create($parent);
        }
    } else {
        $phone = mth_phone::create($parent);
    }
    $phone->saveForm($_POST['parent']['phone']);

    $packet->setSecondaryContact($_POST['secondary_contact_first'], $_POST['secondary_contact_last']);
    $packet->setSecondaryEmail($_POST['secondary_email']);
    $packet->setSecondaryPhone($_POST['secondary_phone']);

    $packet->setRelationShipParentInfo($_POST['pgRelationship_parentInfo']);
    $packet->setRelationShipSecondaryContact($_POST['pgRelationship_secondaryContact']);
    
    $packetStep = 2;

    if (levelOneComplete($packet, $student, $parent)) {
        header('location: ' . $packetURI . '/2');
        exit();
    }

    header('Location: ' . $packetURI);
    exit();
}

cms_page::setPageContent('<p>My Tech High partners with accredited public schools to offer a personalized, tuition-free, distance education program for ages 6 – 18. All students are considered full-time, virtual public school students enrolled in any one of these 3 schools of choice (based on a variety of factors):</p>
<ul>
<li>Gateway&nbsp;Preparatory&nbsp;Academy in Cedar City (<a title="GPA" href="http://gpacharter.org/home/" target="_blank">visit site</a>)</li>
<li>American Leadership Academy in Spanish Fork (<a title="ALA" href="http://www.americanleadership.net/" target="_blank">visit site</a>)</li>
<li>Provo eSchool in Provo School District (<a title="Provo eSchool" href="http://eschool.provo.edu/" target="_blank">visit site</a>)</li>
</ul>
<p style="padding-left: 60px;"><strong>NOTE:</strong> Each student’s school of record will be finalized no later than October 1.</p>
<p>Enrollment is <b>NOT</b> final until all required paperwork has been submitted, received, and approved:</p>
<ul>
<li>Download and review the <a href="http://mytechhigh.com/wp-content/uploads/2013/03/Enrollment-Packet-Policy-Page.pdf" target="_blank">Enrollment Packet Policy Page</a>.</li>
<li>Locate Student Birth Certificate.</li>
<li>Locate Student Immunization Record (or <a href="http://www.immunize-utah.org/information%20for%20the%20public/immunization%20recommendations/exemptions.html" target="_blank">Personal Exemption Form</a>).</li>
<li>Locate&nbsp;Proof of Utah Residency (i.e. utility bill, driver’s license, vehicle registration).</li>
<li>For grades 1-3, a Vision Screening form may also be required.</li>
<li>If applicable, locate a copy of prior IEP or 504 Plan (requires annual review).</li>
<li>Submit one form below for each student.</li>
<li>Scan (or photograph) and then upload all required documents.</li>
<li>Form works best in Firefox or Chrome.</li>
</ul>', 'Packet Form Opening Text', cms_content::TYPE_HTML);
core_loader::printHeader('student');
?>

<div class="page">
    <?=core_loader::printBreadCrumb('window');?>
    <div class="page-content container-fluid">
        <form action="?packetForm1=<?=uniqid()?>" id="packetForm1" method="post" autocomplete="nope">
            <div class="card">
                <?php include core_config::getSitePath() . '/student/packet/header.php';?>
                <div class="card-block">
                    <div class="row">
                        <div class="col-md-6">
                            <?=cms_page::getDefaultPageContent('Packet Form Opening Text', cms_content::TYPE_HTML);?>
                            <h3>Parent/Guardian Information</h3>
                            <fieldset>
                                <legend>Parent</legend>
                                <p><?=$parent?></p>
                                <div class="row">
                                  <div class="col">
                                    <div class="form-group">
                                      <label for="pdob">Date of Birth</label>
                                      <input id="pdob" name="pdob" value="<?=$parent->getDateOfBirth('m/d/Y')?>" type="text" required class="form-control" required>
                                    </div>
                                  </div>
                                  <div class="col">
                                    <div class="form-group">
                                      <label for="pgender">Gender</label>
                                      <select name="pgender" class="form-control" required>
                                        <option></option>
                                        <option <?=$parent->getGender() === mth_person::GEN_FEMALE ? 'selected' : ''?>><?=mth_person::GEN_FEMALE?></option>
                                        <option <?=$parent->getGender() === mth_person::GEN_MALE ? 'selected' : ''?>><?=mth_person::GEN_MALE?></option>
                                      </select>
                                    </div>
                                  </div>
                                </div>
                                <p><?=$parent->getEmail()?></p>
                                <p>
                                    <small><a href="/_/user/profile">(Change your name and email on your profile page)</a></small>
                                </p>
                                <?php $phone = $parent->getPhone('Cell');?>
                                <div class="form-group">
                                    <label for="parent-phone-number">Parent Cell Phone</label>
                                    <input name="parent[phone][id]" value="<?=$phone ? $phone->getID() : ''?>" type="hidden">
                                    <input name="parent[phone][name]" value="Cell" type="hidden">
                                    <input name="parent[phone][number]" autocomplete="nope" id="parent-phone-number"
                                        value="<?=$phone ? $phone->getNumber() : ''?>" required type="text" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="parent-phone-number">Relationship to student</label>
                                     <select name="pgRelationship_parentInfo" class="form-control" required>
                                        <option></option>
                                        <option <?=$packet->getRelationShipParentInfo() === mth_packet::RELATION_FATHER ? 'selected' : ''?>><?=mth_packet::RELATION_FATHER?></option>
                                        <option <?=$packet->getRelationShipParentInfo() === mth_packet::RELATION_MOTHER ? 'selected' : ''?>><?=mth_packet::RELATION_MOTHER?></option>
                                        <option <?=$packet->getRelationShipParentInfo() === mth_packet::RELATION_OTHER ? 'selected' : ''?>><?=mth_packet::RELATION_OTHER?></option>
                                      </select>
                                </div>
                            </fieldset>
                            <fieldset>
                                    <?php
                                        $c_first = $packet->getSecondaryContactFirst();
                                        $c_last = $packet->getSecondaryContactLast();
                                        $c_phone = $packet->getSecondaryPhone();
                                        $c_email = $packet->getSecondaryEmail();

                                        if ($packet->getStatus() == mth_packet::STATUS_NOT_STARTED) {
                                            $STUDENTS = $parent->getStudents();
                                            foreach ($STUDENTS as $_student) {
                                                if ($student->getID() != $_student->getID()) {
                                                    if ($_packet = mth_packet::getStudentPacket($_student)) {
                                                        $c_first = $_packet->getSecondaryContactFirst();
                                                        $c_last = $_packet->getSecondaryContactLast();
                                                        $c_phone = $_packet->getSecondaryPhone();
                                                        $c_email = $_packet->getSecondaryEmail();
                                                        break;
                                                    }

                                                }
                                            }
                                        }
                                    ?>
                                <legend>Secondary Contact</legend>
                                <div class="form-group">
                                    <label for="secondary_contact_first">First Name</label>
                                    <input id="secondary_contact_first" name="secondary_contact_first" type="text" required autocomplete="nope"
                                        value="<?=$c_first?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="secondary_contact_last">Last Name</label>
                                    <input id="secondary_contact_last" name="secondary_contact_last" type="text" required autocomplete="nope"
                                        value="<?=$c_last?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="secondary_phone">Secondary Phone</label>
                                    <input name="secondary_phone" value="<?=$c_phone?>" autocomplete="nope" type="text" required class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="secondary_email">Secondary Email</label>
                                    <input id="secondary_email" name="secondary_email" type="email" required autocomplete="nope"
                                        value="<?=$c_email?>" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="parent-phone-number">Relationship to student</label>
                                     <select name="pgRelationship_secondaryContact" class="form-control" required>
                                        <option></option>
                                        <option <?=$packet->getRelationShipSecondaryContact() === mth_packet::RELATION_FATHER ? 'selected' : ''?>><?=mth_packet::RELATION_FATHER?></option>
                                        <option <?=$packet->getRelationShipSecondaryContact() === mth_packet::RELATION_MOTHER ? 'selected' : ''?>><?=mth_packet::RELATION_MOTHER?></option>
                                        <option <?=$packet->getRelationShipSecondaryContact() === mth_packet::RELATION_OTHER ? 'selected' : ''?>><?=mth_packet::RELATION_OTHER?></option>
                                      </select>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-md-6">
                            <h3><?=$student->getPreferredFirstName()?>'s Information</h3>
                            <fieldset>
                                <legend>
                                    Legal Name
                                    <small style="display: inline"><span class="text-danger">(EXACTLY as it appears on the birth certificate)</span></small>
                                </legend>
                                <div class="form-group">
                                    <label for="student-first">First Name</label>
                                    <input id="student-first" name="student[first]" type="text" required autocomplete="nope"
                                        value="<?=$student->getFirstName()?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="student-middle">Legal Middle Name (Enter "NMN" if no legal middle name)</label>
                                    <input id="student-middle" name="student[middle]" type="text" required autocomplete="nope"
                                        value="<?=$student->getMiddleName()?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="student-last">Last Name</label>
                                    <input id="student-last" name="student[last]" type="text" required autocomplete="nope"
                                        value="<?=$student->getLastName()?>" class="form-control">
                                </div>
                            </fieldset>
                            <fieldset>
                                <legend>Preferred Name</legend>
                                <div class="form-group">
                                    <label for="student-preferred-first">First Name</label>
                                    <input id="student-preferred-first" name="student[preferred-first]" type="text" required autocomplete="nope"
                                        value="<?=$student->getPreferredFirstName()?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="student-preferred-last">Last Name</label>
                                    <input id="student-preferred-last" name="student[preferred-last]" type="text" required autocomplete="nope"
                                        value="<?=$student->getPreferredLastName()?>" class="form-control">
                                </div>
                            </fieldset>
                            <fieldset>
                                <?=cms_page::getDefaultPageContent('Content Above Student Email', cms_content::TYPE_HTML);?>
                                <div class="form-group">
                                    <label for="student-email">Student Email <span class="text-danger">(must be an active email)</span></label>
                                    <input id="student-email" name="student[email]" type="email" required autocomplete="nope"
                                        value="<?=$student->getEmail()?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="student-email">Student Email Again</label>
                                    <input id="student_email_again" name="student_email_again" type="email" required autocomplete="nope"
                                        value="<?=$student->getEmail()?>" class="form-control">
                                </div>
                            </fieldset>
                            <fieldset>
                                <legend>Home Address</legend>
                                <?php
                                    include core_config::getSitePath() . '/_/mth_forms/address.php';
                                    echo printAddressFields('parent[address]', true, $parent);
                                ?>
                            </fieldset>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="button" id="goto-personal1" class="btn need-review btn-primary btn-round btn-lg">Next &raquo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
core_loader::includejQueryUI();
core_loader::printFooter('student');
?>
<script>
    $('#goto-personal1').click(function() {
        var packetForm1 = $('#packetForm1');
        if($('#goto-personal1').hasClass('need-review')){
            $('#goto-personal1').text('Confirm and Continue').removeClass('need-review');
            swal('','Please verify that all information is correct and that the student\'s email address is valid and accessible.','');
            return false;
        }
        packetForm1.submit();
        console.log('clicked');
    })

    $('#pdob').datepicker({
      changeMonth: true,
      changeYear: true,
      yearRange: <?=date('Y', strtotime('-60 years'))?> + ':' + <?=date('Y', strtotime('-10 years'))?>,
      defaultDate: '-35y'
    });
    var packetForm1 = $('#packetForm1');
    // packetForm1.validate({
    //     rules: {
    //         student_email_again: {
    //             equalTo: "#student-email"
    //         },
    //         "student[email]": {
    //             remote: {
    //                 url: '/apply/validate-email.php',
    //                 data: {
    //                     studentid: <?=$student->getID()?>
    //                 }
    //             }
    //         },
    //         "parent[phone][number]": {
    //             required: true,
    //             phoneUS: true
    //         },
    //         secondary_phone: {
    //             required: true,
    //             phoneUS: true
    //         }
    //     },
    //     submitHandler: function(form) {
    //         console.log('submit hanlder');
    //         if($('#goto-personal').hasClass('need-review')){
    //             $('#goto-personal').text('Confirm and Continue').removeClass('need-review');
    //             swal('','Please verify that all information is correct and that the student\'s email address is valid and accessible.','');
    //             return false;
    //         }
    //         form.submit();
    //     }
    // });

    <?php if ($packetRunValidation): ?>
    if (!packetForm1.valid()) {
        packetForm1.submit(); //this will focus the cursor on the problem fields
    }
    <?php endif;?>
</script>