<?php

global $packet, $student, $year;

$packet = mth_packet::getByID($_GET['packet']);
if (!$packet) {
    if($_GET['file']) {
        mth_packet_file::deleteById($_GET['file']);
    }
    die();
}

($student = $packet->getStudent()) || die('Student Not Found. Please refresh');
($parent = $student->getParent()) || die('Parent Not Found. Please refresh');
$year = max($student->getReenrolled() ? mth_schoolYear::getNext() : mth_schoolYear::getCurrent(), mth_packet::getActivePacketYear($packet));

function sendToDropBox($year = null)
{
    global $packet, $student;

    // $dir_year = $packet->getDateAccepted()?mth_schoolYear::getByStartYear($packet->getDateAccepted('Y')):mth_schoolYear::getCurrent();
    // if($year){
    $dir_year = $year;
    // }

    if (!$dir_year) {
        core_notify::addError('Invalid year');
        return FALSE;
    }

    core_notify::init();

    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';

    $path = '/' . $dir_year . '/' .($inputState == 'OR' ? 'Oregon/' : ''). $student->getLastName() . ', ' . $student->getFirstName() . ' (' . $student->getID() . ')/';

    ob_start();
    include core_config::getSitePath() . '/_/admin/packets/pdf-content.php';
    $content = ob_get_contents();
    ob_end_clean();

    $files = mth_packet_file::getPacketFiles($packet);

    $success = mth_dropbox::uploadFileFromString($path . 'packet.pdf', $content);

    foreach ($files as $file) {
        /* @var $file mth_packet_file */
        if ($file->getKind() == mth_packet_file::KIND_SIG) {
            continue;
        }
        $prefix = '';
        switch ($file->getKind()) {
            case 'bc':
                $prefix = 'birthcertificate_';
                break;
            case 'im':
                $prefix = 'immunization_';
                break;
            case 'ur':
                $prefix = 'proofofresidency_';
                break;
            case 'iep':
                $prefix = 'dontupload_';
                break;
            // case 'itf':
            //     $prefix = 'inter_district_transfer_';
            //     break;
        }
        $success = mth_dropbox::uploadFileFromString($path . $prefix . $file->getUniqueName(), $file->getContents()) && $success;
    }

    if ($success) {
        core_notify::addMessage('Packet sent to DropBox');
        return true;
    } else {
        core_notify::addError('There were errors sending the packet to Dropbox');
        return false;
    }
}

if (!empty($_GET['form'])) {
    $date_administered = isset($_POST['date_administered']) ? $_POST['date_administered'] : [];
    $ex = isset($_POST['ex']) ? $_POST['ex'] : [];
    $na = isset($_POST['na']) ? $_POST['na'] : [];
    $im = isset($_POST['im']) ? $_POST['im'] : [];
    $im_ids = isset($_POST['imunization_ids']) ? $_POST['imunization_ids'] : [];
    $student_immunization = new mth_student_immunizations;
    $student_immunization->setStudentId($student->getID());
    $student_immunization->createOrUpdateBulk($date_administered, $ex, $na, $im, $im_ids);

    core_loader::formSubmitable('packet-edit-form-' . $_GET['form']) || die();

    $packet->setStatus($_POST['status']);

    $packet->setSecondaryContact($_POST['secondary_contact_first'], $_POST['secondary_contact_last']);
    $packet->setSecondaryPhone($_POST['secondary_phone']);
    $packet->setSecondaryEmail($_POST['secondary_email']);
    $packet->setAdminNotes($_POST['admin_notes']);
    $packet->setImmunizationNotes($_POST['immunization_notes']);

    $packet->setHispanic($_POST['hispanic']);

    if (!empty($_POST['race'])) {
        if (($key = array_search('other', $_POST['race']))) {
            $_POST['race'][$key] = $_POST['raceOther'];
        }
        $packet->setRace($_POST['race']);
    }
    $packet->setLanguage($_POST['language']);
    $packet->setLanguageAtHome($_POST['language_home']);
    $packet->setLanguageHomeChild(req_post::txt('language_home_child'));
    $packet->setLanguageFriends(req_post::txt('language_friends'));
    $packet->setLanguageHomePreferred(req_post::txt('language_home_preferred'));

    $packet->setHouseholdSize($_POST['household_size']);
    $packet->setHouseholdIncome($_POST['household_income']);

    $packet->setBirthPlace(req_post::txt('birth_place'));
    $packet->setBirthCountry(req_post::txt('birth_country'));
    $packet->setWorkedInAgriculture(req_post::bool('worked_in_agriculture'));
    $packet->setMilitary(req_post::bool('military'));
    $packet->setMilitaryBranch(req_post::txt('military_branch'));
    $packet->setFERPAagreement(req_post::int('ferpa_agreement'));
    $packet->photoPerm(req_post::int('photo_permission'));
    $packet->dirPerm(req_post::int('dir_permission'));

    $packet->setSpecialEd($_POST['special_ed']);
    //$packet->setSpecialEdDesc($_POST['special_ed_desc']);

    // $packet->setSchoolDistrict($_POST['school_district']);
    $parent->getAddress()->setSchoolDistrictOfR($_POST['school_district']);

    $packet->setLastSchoolType($_POST['last_school_type']);
    $packet->setLastSchoolName($_POST['last_school']);
    $packet->setLastSchoolAddress($_POST['last_school_address']);
    $packet->setExempImmunization(req_post::bool('exemp_immunization'));
    $packet->setExemptionFormDate(req_post::txt('exemption_form_date'));
    $packet->setMedicalExemption(req_post::bool('medical_exemption'));

    if ($_POST['button_pressed'] == 'Save' && $packet->save()) {
        core_notify::addMessage('Packet Saved');
    } elseif ($_POST['button_pressed'] == 'Save & Accept Packet') {
        $packet->accept();
        $packet->save();
        core_notify::addMessage('The packet has been accepted');
        sendToDropBox(
            mth_schoolYear::getApplicationYear()
        );
    } elseif ($_POST['button_pressed'] == 'Resend to DropBox') {
        sendToDropBox(
            mth_schoolYear::getApplicationYear()
        );
    }
    header('Location: /_/admin/packets/edit?packet=' . $packet->getID());
    exit();
}

if (!$packet->isSubmitted()) {
    core_notify::addError('This packet has not yet been submitted');
}

if (req_get::bool('notifyMissing')) {
    $packet->setReuploadFiles([]);
    $success = $packet->setStatus(mth_packet::STATUS_MISSING)
        && $packet->save();
    $link = 'https://' . $_SERVER['HTTP_HOST'] . '/student/' . $student->getSlug() . '/packet/5';

    $email = new core_emailservice();
    $email_result = $email->send(
        [$parent],
        core_setting::get("additionalEnrollmentSubject", 'Enrollment'),
        str_replace(
            ['[PARENT]', '[STUDENT_FIRST]', '[LINK]'],
            [$parent->getPreferredFirstName(), $student->getPreferredFirstName(), ('<a href="' . $link . '">' . $link . '</a>')],
            core_setting::get("additionalEnrollmentContent", 'Enrollment')->getValue()
        ),
        null,
        [core_setting::getSiteEmail()->getValue()]
    );

    if ($email_result && $success) {
        core_notify::addMessage('Notification Missing Sent');
    } else {
        core_notify::addError('Notification Missing Failed');
    }

    header('Location: /_/admin/packets/edit?packet=' . $packet->getID());
    exit();
}
$immunization_settings = core_setting::get('immunizations', 'packet_settings')->getValue();
cms_page::setPageTitle('Edit Packet');
core_loader::isPopUp();
core_loader::printHeader();

?>
    <style>
        h2 a {
            text-decoration: none;
        }

        input,
        select {
            font-size: 14px;
        }

        table.formatted {
            width: 100%;
        }

        table.formatted th {
            vertical-align: top;
            padding-top: 10px;
        }

        .packet-status-Started,
        .packet-status-MissingInfo {
            color: red;
        }

        .packet-status-Submitted {
            color: green;
        }

        .packet-status-Resubmitted {
            color: #990099;
        }

        #vaccine_table #check-all-container {
            display: none;
        }

        #vaccine_table.active #check-all-container {
            display: block;
        }

    #vaccine_table th {
        text-align: center;
    }

    .date_invalid, .vaccine_row_needs_review {
        border: 2px solid #f44336;
    }

    .vaccine_row_not_set {
        background-color: #d19a02;
    }

    small {
        color: #666;
    }

        .jquery_accordion_title {
            font-size: 14px;
            color: #333;
            line-height: 140%;
            padding: 8px 40px 8px 8px;
            font-weight: bold;
            position: relative;
            cursor: pointer;
        }
        .jquery_accordion_title:after {
            content: "";
            width: 0;
            height: 0;
            display: inline-block;
            position: absolute;
            right: -16px;
            top: 30px;
            border: 6px solid transparent;
            border-top-color: #333;
            transition: border 400ms, margin 400ms;
            margin-top: -3px; /* half of border value */
        }
        .jquery_accordion_item.active .jquery_accordion_title:after {
            border-color: transparent;
            border-bottom-color: #333;
            margin-top: -9px; /* fixing arrow position */
        }
        .jquery_accordion_content {
            padding: 8px;
            display: none;
            color: #333;
        }
        .jquery_accordion_content > *:first-child {
            margin-top: 0;
        }
    #age-issue-button {
        color: white;
    }
    </style>
    <button type="button" class="iframe-close btn btn-secondary btn-round" onclick="closePacket()">Close</button>
    <form action="?packet=<?= $packet->getID() ?>&form=<?= uniqid() ?>" method="post" id="packetAdminForm">
    <input type="hidden" name="button_pressed" />
        <div class="row person-links">
            <div class="col-md-<?= $immunization_settings ? 4 : 6 ?>">
                <h3>
                    <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?student=<?= $student->getID() ?>')">
                        <?= $student ?>
                    </a>
                </h3>
                <div><b><?= $student->getFirstName() ?> <?= $student->getMiddleName() ?> <?= $student->getLastName() ?></b>
                </div>
                <div>Gender: <b><?= $student->getGender() ?></b></div>
                <div>DOB: <b><?= $student->getDateOfBirth('F j, Y') ?></b> (<?= $student->getAge() ?>)</div>
                <div>
                    <?php
                    if (!($_gradelevel = $student->getGradeLevelValue($year->getID())) && ($next_year = $year->getNext())) {
                        $_gradelevel = $student->getGradeLevelValue($next_year->getID());
                        echo $_gradelevel == 'K' ? 'Pre-K' : mth_student::gradeLevelFullLabel($_gradelevel);
                    } else {
                        echo mth_student::gradeLevelFullLabel($_gradelevel);
                    }
                    ?>
                    <small>(<?= $year ?>)</small>
                </div>
                <div>SPED: <?= $student->specialEd(true) ?></div>
                <div><?= $student->getEmail(true) ?></div>
                <p><?= $student->getAddress() ?></p>
                <p>
                    <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?student=<?= $student->getID() ?>')">
                        View/Edit Student Profile
                    </a>
                </p>
                <legend for="admin_notes">Packet Notes</legend>
                <textarea class="borderLess form-control" rows='8' style="width:90%; margin-bottom: 25px;" name="admin_notes" id="admin_notes"><?= $packet->getAdminNotes() ?></textarea>
            </div>
            <div class="col-md-<?= $immunization_settings ? 4 : 6 ?>">
                <h3>Parent:
                    <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?parent=<?= $parent->getID() ?>')"><?= $parent ?></a>
                </h3>
                <div><?= $parent->getEmail(true) ?></div>
                <div><?= $parent->getPhoneNumbers(true) ?></div>
                <p>
                    <a onclick="top.global_popup_iframe('mth_people_edit','/_/admin/people/edit?parent=<?= $parent->getID() ?>')">
                        View/Edit Parent Profile
                    </a>
                </p>
                <div>
                    <?php
                    $fileTypeArr = array(
                        'bc' => 'Birth Certificate',
                        'im' => 'Immunization',
                        'ur' => 'Proof of Residency',
                        'iep' => 'IEP or 504 Plan',
                    );
                    $state = $student->getAddress() ? $student->getAddress()->getState() : 'UT';
                    if($state=='OR'){ //only available for OR students 
                        $fileTypeArr['itf']  = 'Inter-District Transfer';
                    }
                    ?>
                    <fieldset>
                        <legend>Documents</legend>
                        <table class="formatted">
                            <?php foreach ($fileTypeArr as $kind => $desc) : ?>
                                <?php if (!($fileArr = mth_packet_file::getPacketFile($packet, $kind, false))) {
                                        continue;
                                    } ?>
                                <tr>
                                    <th><?= $fileTypeArr[$kind] ?></th>
                                    <td>
                                        <?php foreach ($fileArr as $file) : /* @var $file mth_packet_file */ ?>
                                            <div class="file_<?= $file->getID() ?>">
                                                <a href="/_/admin/packets/file?file=<?= $file->getID() ?>"><?= $file->getName() ?></a>
                                                <button type="button" class="close" aria-label="Close" onclick="deleteDocument(<?= $file->getID() ?>)"><span>&times;</span></button>
                                            <!-- <a class="link" onclick="top.global_popup_iframe('fileviewer','/_/user/fileviewer?file=<?= ($file->getFile())->hash() ?>')"><?= $file->getName() ?></a> -->
                                                <br>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </fieldset>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="exemp_immunization" id="exemp_immunization" value="1" <?= $packet->isExempImmunization() ? 'CHECKED' : '' ?> <?= $immunization_settings ? 'disabled' : '' ?>>
                        <label>Exemption form for Tdap Booster, Meningococcal and Varicella?</label>
                    </div>
                </div>
              <?php if($immunization_settings) :?>
                  <div id="immunization_notes_container" style="width:90%;">
                      <legend for="immunization_notes">Immunization Notes</legend>
                      <textarea class="borderLess form-control" rows='5' name="immunization_notes" id="immunization_notes"><?= $packet->getImmunizationNotes() ?></textarea>
                  </div>
              <?php endif; ?>
            </div>
            <?php if ($immunization_settings) : ?>
                <div class="col-md-4">
                    <table class="table higlight-links jquery_accordion_item" id="vaccine_table" data-status="<?= $packet->getStatus() != mth_packet::STATUS_ACCEPTED ? 'active' : '' ?>">
	                    <thead>
                        <tr>
                            <th>
                                <h4><a>Vaccine</a></h4>
                            </th>
                            <th>
                                <h4 data-toggle="tooltip" title="Date vaccine is administered"><a>Date</a></h4>
                            </th>
                            <th>
                              <h4 data-toggle="tooltip" title="Check if student has an exemption form"><a>EX</a></h4>
                              <div class="checkbox-custom checkbox-primary" id="check-all-container" style="position: absolute; top: 28px;">
                                <input type="checkbox" id="ex-check-all" />
                                <label></label>
                              </div>
                            </th>
                            <th>
                                <h4 data-toggle="tooltip" title="Check if not applicable"><a>N/A</a></h4>
                            </th>
                            <th class="jquery_accordion_title">
                                <h4 data-toggle="tooltip" title="Check if student is Immune" ><a>IM</a></h4>
                            </th>
                        </tr>
                      </thead>
                      <tbody class="jquery_accordion_content">
                        <?php
                            foreach (mth_immunization_settings::getEach() as $immunization) :
                                $student_immunization = mth_student_immunizations::getByImmunizationId($student->getID(), $immunization->getID());
                                ?>
                <tr class="vaccine-table-row">
                                <td>
                                    <b data-toggle="tooltip" title="<?= $immunization->getTooltip() ?>"><?= $immunization->getTitle() ?></b>
                                    <input type="hidden" name="imunization_ids[]" class="imunization_ids" value="<?= $immunization->getID(); ?>" />
                                </td>
                                <td>
                        <input type="date"
                            data-consecutive-vaccine="<?= $immunization->getConsecutiveVaccine() ?>"
                            data-min-spacing-interval="<?= $immunization->getMinSpacingInterval() ?>"
                            data-min-spacing-date="<?= $immunization->timeLabel($immunization->getMinSpacingDate()) ?>"
                            data-max-spacing-interval="<?= $immunization->getMaxSpacingInterval() ?>"
                            data-max-spacing-date="<?= $immunization->timeLabel($immunization->getMaxSpacingDate()) ?>"
                            name="date_administered[<?= $immunization->getID(); ?>]"
                            data-recorded-date="<?= $student_immunization ? $student_immunization->getDateAdministered('Y-m-d') :'' ?>"
                            class="form-control date_administered"
                            autocomplete="off" 
                            value="<?= $student_immunization ? $student_immunization->getDateAdministered('Y-m-d') :'' ?>"
                            format="MM/dd/y"
                            placeholder="MM/dd/y"
                            max="9999-12-31"
                            />
                                </td>
                                <td>
                                    <svg class="info-tooltip" data-toggle="tooltip" title="Does not fall within vaccine timeframe, school may request a new vaccine record" style="float: left; position: relative; left: -2.5rem; top: 0.9rem; color: #cc3300;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                                        <path d="M8.93 6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                                    </svg>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" name="ex[<?= $immunization->getID(); ?>]" class="ex vaccine-checkbox dynamic-checkbox" <?= $student_immunization && $student_immunization->getExempt() ? 'CHECKED' : '' ?>>
                                        <label></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" name="na[<?= $immunization->getID(); ?>]" class="na vaccine-checkbox dynamic-checkbox" <?= ($student_immunization && $student_immunization->getNonapplicable()) || (!$student_immunization && $immunization->getGradeLevelNonApplicable($student, $year)) ? 'CHECKED' : '' ?>>
                                        <label></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input data-isImmunityAllowed="<?= $immunization->isImmunityAllowed() ?>" type="checkbox" name="im[<?= $immunization->getID(); ?>]" class="im vaccine-checkbox dynamic-checkbox" <?= $student_immunization && $student_immunization->getImmune() ? 'CHECKED' : '' ?>>
                                        <label></label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="exemption_form_date_tr">
                            <td>
                                <b>Exemption Form Date</b>
                            </td>
                            <td colspan="2">
                                <input type="date" name="exemption_form_date" class="form-control exemption_form_date format_date" value="<?= $packet->getExemptionFormDate('Y-m-d')?>"/>
                            </td>
                            <td>
                                <b>Medical Exemption</b>
                            </td>
                            <td>
                                <div class="checkbox-custom checkbox-primary" style="text-align: left;">
                                    <input type="checkbox" name="medical_exemption" class="medical_exemption" <?= $packet->getMedicalExemption() ? 'CHECKED' : '' ?>>
                                    <label></label>
                                </div>
                            </td>
                        </tr>
                      </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <?php if (
                !($accessToken = core_setting::get('accessTokenV2', 'DropBox'))
                || $accessToken->getValue() == ''
            ) : ?>
                <p><b style="color: red;">The site has not been authorized to send files to your DropBox account.
                        <a href="/_/admin/settings/dropbox-start" target="_blank">Click here to authorize now.</a></b></p>
            <?php endif; ?>
        <button id="save_button" type="submit" name="button" class="btn btn-primary btn-round btn-submit" value="Save">Save</button>
            <?php if ($packet->getStatus() == mth_packet::STATUS_SUBMITTED || $packet->getStatus() == mth_packet::STATUS_RESUBMITTED) : ?>
            <button id="save_accept_button" type="submit" name="button" class="btn btn-success btn-round btn-submit"
                    value="Save & Accept Packet">Save & Accept Packet
                </button>
                <script>
                    function confirmReject() {
                        swal({
                                title: '',
                                text: "This action will not save any changes you might have made to this packet.",
                                type: "info",
                                showCancelButton: !0,
                                confirmButtonClass: "btn-primary",
                                confirmButtonText: "Continue",
                                cancelButtonText: "Cancel",
                                closeOnConfirm: true,
                                closeOnCancel: true
                            },
                            function() {
                                top.global_popup_iframe('mth-packet-reject', '/_/admin/packets/reject?packet=<?= $packet->getID() ?>')
                            });
                    }
                function ageIssueModal() {
                    swal({
                            title: '',
                            text: "This action will not save any changes you might have made to this packet.",
                            type: "info",
                            showCancelButton: !0,
                            confirmButtonClass: "btn-primary",
                            confirmButtonText: "Continue",
                            cancelButtonText: "Cancel",
                            closeOnConfirm: true,
                            closeOnCancel: true
                        },
                        function () {
                            top.global_popup_iframe('mth-packet-age-issue', '/_/admin/packets/age-issue?packet=<?= $packet->getID() ?>')
                        });
                }
                </script>
                <button type="button" onclick="confirmReject()" class="btn btn-success btn-round btn-danger">Info Missing
                </button>
            <button type="button" onclick="ageIssueModal()" id="age-issue-button" class="btn btn-success btn-round btn-warning">Age Issue
            </button>
                <p>
                    <div class="alert alert-info alert-alt">
                        <small>
                            <b>Save</b> - Saves changes you made to the packet<br>
                            <b>Save & Accept Packet</b> - Saves changes, marks the packet as accepted, sets the student as
                            active (parent can submit a schedule), and notifies the parent<br>
                    <b>Info Missing</b> - No changes are saved, you are taken to a new window to specify the missing
                    files and give instructions.<br>
                    <b>Age Issue</b> - No changes are saved, you are taken to a new window to send further instructions.
                        </small>
                    </div>
                </p>
            <?php elseif ($packet->isAccepted()) : ?>
                <button id="dropbox_button" type="submit" name="button" class="btn btn-success btn-round btn-info btn-submit" value="Resend to DropBox">Resend to DropBox
                </button>
            <?php endif; ?>
            <a class="btn btn-round btn-secondary float-right" style="display:none" href="?packet=<?= $packet->getID() ?>&notifyMissing=1">Notify Missing (Test)</a>
        </div>
        <hr>
        <h3>Packet Fields</h3>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" onchange="updateform()" class="borderLess form-control packet-status-<?= str_replace(' ', '', $packet->getStatus()) ?>">
                        <?php foreach (mth_packet::getAvailableStatuses() as $status) : ?>
                            <option <?= $packet->getStatus() == $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>No emails are sent if if the status is changed using this field, nor will the student's status be
                        affected.
                    </small>
                </div>
                <fieldset>
                    <legend>Secondary Contact</legend>
                    <table class="formatted">
                        <tr>
                            <th>First Name</th>
                            <td>
                                <input id="secondary_contact_first" name="secondary_contact_first" type="text" class="borderLess form-control" value="<?= $packet->getSecondaryContactFirst() ?>">
                            </td>
                        </tr>
                        <tr>
                            <th>Last Name</th>
                            <td>
                                <input id="secondary_contact_last" name="secondary_contact_last" type="text" class="borderLess form-control" value="<?= $packet->getSecondaryContactLast() ?>">
                            </td>
                        </tr>
                        <tr>
                            <th>Secondary Phone</th>
                            <td>
                                <input name="secondary_phone" value="<?= $packet->getSecondaryPhone() ?>" type="text" class="borderLess form-control">
                            </td>
                        </tr>
                        <tr>
                            <th>Secondary Email</th>
                            <td>
                                <input id="secondary_email" name="secondary_email" type="email" class="borderLess form-control" value="<?= $packet->getSecondaryEmail() ?>">
                            </td>
                        </tr>
                    </table>
                </fieldset>
                <br>
                <fieldset>
                    <legend>Personal Info</legend>
                    <table class="formatted">
                        <tr>
                            <th>Birthplace</th>
                            <td>
                                <input type="text" name="birth_place" value="<?= $packet->getBirthPlace() ?>" class="borderLess form-control">
                                <select name="birth_country" class="borderLess form-control">
                                    <option></option>
                                    <?php foreach (mth_packet::getAvailableCountries() as $county_code => $county_name) : ?>
                                        <option value="<?= $county_code ?>" <?= $packet->getBirthCountry() == $county_code ? 'selected' : '' ?>>
                                            <?= $county_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Hispanic/Latino</th>
                            <td>
                                <select name="hispanic" id="hispanic" class="borderLess form-control">
                                    <option></option>
                                    <option <?= $packet->isHispanic() ? 'selected' : '' ?> value="1">Yes</option>
                                    <option <?= $packet->isHispanic() === false ? 'selected' : '' ?> value="0">No</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Race</th>
                            <td>
                                <?php $studentRace = $packet->getRace(FALSE); ?>
                                <?php foreach (mth_packet::getAvailableRace() as $raceID => $race) : ?>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" name="race[]" id="race-<?= $raceID ?>" value="<?= $raceID ?>" <?= in_array($raceID, $studentRace) ? 'checked' : '' ?>>
                                        <label for="race-<?= $raceID ?>">
                                            <?= $race ?>
                                        </label>
                                    </div>

                                <?php endforeach; ?>
                                <?php
                                $raceOther = trim(preg_replace('/[0-9]+/', '', implode('', $studentRace)));
                                ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="race[]" id="race-other" <?= $raceOther ? 'checked' : '' ?> value="other">
                                    <label for="raceOtherCB">
                                        Other:
                                        <input type="text" name="raceOther" id="raceOther" value="<?= $raceOther ?>" style="max-width: 150px;" class="borderLess form-control">
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <?php

                        $language_options = [
                            'English' => 'English',
                            'Spanish' => 'Spanish',
                            'Other' => 'Other (Indicate)'
                        ];
                        $languageBlock = function ($name, $label, $selected) use ($language_options) {
                            $selected_other = '';
                            if ($selected && !isset($language_options[$selected])) {
                                $selected_other = $selected;
                                $selected = 'Other';
                            }
                            ?>
                            <tr>
                                <th><label for="<?= $name ?>"><?= $label ?></label></th>
                                <td><select name="<?= $name ?>" id="<?= $name ?>" class="language_select borderLess form-control">
                                        <option></option>
                                        <?php
                                            foreach ($language_options as $value => $option_label) {
                                                echo '<option value="', $value, '" ', ($selected == $value ? 'selected' : ''), '>
                                    ', $option_label, '
                                    </option>';
                                            }
                                            ?>
                                    </select><input type="text" name="<?= $name ?>" id="<?= $name ?>-other" class="borderLess" style="display: none; max-width: 150px" disabled value="<?= $selected_other ?>" required title="Other Language"></td>
                            </tr>
                        <?php
                        };
                        $languageBlock('language', 'First language learned by child', $packet->getLanguage());
                        $languageBlock('language_home', 'Language used most often by adults in the home', $packet->getLanguageAtHome());
                        $languageBlock('language_home_child', 'Language used most often by child in the home', $packet->getLanguageHomeChild());
                        $languageBlock('language_friends', 'Language used most often by child with friends outside the home', $packet->getLanguageFriends());
                        $languageBlock('language_home_preferred', 'Preferred correspondence language for adults in the home', $packet->getLanguageHomePreferred());

                        ?>
                    </table>
                    <script>
                        var $language_select = $('.language_select');
                        $language_select.change(function() {
                            if (this.value == 'Other') {
                                $('#' + this.id + '-other').show().prop('disabled', false).focus();
                            } else {
                                $('#' + this.id + '-other').val('').hide().prop('disabled', true);
                            }
                        });
                        $language_select.change();
                    </script>
                </fieldset>
            </div>
            <div class="col-sm-6">
                <fieldset>
                    <legend>Education</legend>
                    <table class="formatted">
                        <tr>
                            <th>School District</th>                            
                            <td>
                                <select name="school_district" id="school_district" class="borderLess form-control">
                                    <option></option>
                                    <?php 
                                    $state = $parent->getAddress() ? $parent->getAddress()->getState() : 'UT';
                                    foreach (mth_packet::getSchoolDistrictbyState($state) as $schoolDistrict) : ?>
                                        <option <?= ($parent->getAddress() && $parent->getAddress()->getSchoolDistrictOfR() == $schoolDistrict ) ? 'selected' : '' ?>><?= $schoolDistrict ?></option>
                                    <?php endforeach; ?>
                                </select>                          
                            </td>
                        </tr>
                        <tr>
                            <th>Special Ed</th>
                            <td>
                                <select name="special_ed" id="special_ed" class="borderLess form-control">
                                    <option></option>
                                    <?php foreach (mth_packet::getAvailableSecialEd() as $specialEdID => $specialEd) : ?>
                                        <option value="<?= $specialEdID ?>" <?= $packet->getSpecialEd(true) === $specialEdID ? 'selected' : '' ?>>
                                            <?= $specialEd ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Last School Attended</th>
                            <td>
                                <p>
                                    <?php foreach (mth_packet::getAvailableSchoolTypes() as $typeID => $schoolType) : ?>
                                        <div class="radio-custom radio-primary">
                                            <input type="radio" name="last_school_type" id="last_school_type-<?= $typeID ?>" value="<?= $typeID ?>" <?= $packet->getLastSchoolType(true) == $typeID ? 'checked' : '' ?>>
                                            <label for="last_school_type-<?= $typeID ?>">
                                                <?= $schoolType ?>
                                            </label>
                                        </div>

                                    <?php endforeach; ?>
                                    <label for="last_school_type" class="error"></label>
                                </p>
                                <p>
                                    <label for="last_school">Name of School</label>
                                    <input type="text" id="last_school" name="last_school" value="<?= $packet->getLastSchoolName() ?>" class="borderLess form-control">
                                </p>
                                <p>
                                    <label for="last_school_address">Address of School</label>
                                    <textarea id="last_school_address" name="last_school_address" class="borderLess form-control"><?= $packet->getLastSchoolAddress(false) ?></textarea>
                                </p>
                            </td>
                        </tr>

                    </table>
                </fieldset>
                <br>
                <fieldset>
                    <legend>Income Information</legend>
                    <table class="formatted">
                        <tr>
                            <th>Household Size</th>
                            <td>
                                <input type="number" name="household_size" id="household_size" value="<?= $packet->getHouseholdSize() ?>" class="borderLess form-control">
                            </td>
                        </tr>
                        <tr>
                            <th>House Income</th>
                            <td>
                                <select name="household_income" id="household_income" class="borderLess form-control">
                                    <?php foreach (mth_packet::getAvailableIncome() as $incomeID => $income) : ?>
                                        <option value="<?= $incomeID ?>" <?= $packet->getHouseholdIncome(false) == $incomeID ? 'selected' : '' ?>>
                                            <?= $income ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </fieldset>
                <br>
                <fieldset>
                    <legend>Other</legend>
                    <table class="formatted">
                        <tr>
                            <th>Has the parent/guardian or spouse worked in Agriculture?</th>
                        </tr>
                        <tr>
                            <td>
                                <select name="worked_in_agriculture" id="worked_in_agriculture" class="borderLess form-control">
                                    <option></option>
                                    <option <?= $packet->getWorkedInAgriculture() ? 'selected' : '' ?> value="1">Yes
                                    </option>
                                    <option <?= $packet->getWorkedInAgriculture() === false ? 'selected' : '' ?> value="0">
                                        No
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Is a parent or legal guardian on active duty in the military?</th>
                        </tr>
                        <tr>
                            <td>
                                <select name="military" id="military" class="borderLess form-control">
                                    <option></option>
                                    <option <?= $packet->getMilitary() ? 'selected' : '' ?> value="1">Yes</option>
                                    <option <?= $packet->getMilitary() === false ? 'selected' : '' ?> value="0">No</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Military Branch</th>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" name="military_branch" id="military_branch" class="form-control" value="<?= $packet->getMilitaryBranch() ?>">
                            </td>
                        </tr>
                    </table>
                </fieldset>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-sm-6">
                <fieldset>
                    <legend>Submission</legend>
                    <table class="formatted">
                        <tr>
                            <th>FERPA</th>
                            <td>
                                <?php $ferpaSelected = $packet->getFERPAagreement() ?>
                                <select name="ferpa_agreement" id="ferpa_agreement" required class="form-control">
                                    <?php foreach (mth_packet::getAvailableFerpa() as $ferpaID => $ferpaOption) : ?>
                                        <option <?= $ferpaSelected === $ferpaOption ? 'selected' : '' ?> value="<?= $ferpaID ?>"><?= $ferpaOption ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Student Photo Permission</th>
                            <td>
                                <select name="photo_permission" id="photo_permission" required class="form-control">
                                    <?php $photoSelected = $packet->photoPerm() ?>
                                    <?php foreach (mth_packet::getPhotoPermOpts() as $photoID => $photoOption) : ?>
                                        <option <?= $photoSelected == $photoOption ? 'selected' : '' ?> value="<?= $photoID ?>"><?= $photoOption ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>School Student Directory Permission</th>
                            <td>
                                <?php $directorySelected = $packet->dirPerm() ?>
                                <select name="dir_permission" id="dir_permission" required class="form-control">
                                    <?php foreach (mth_packet::getDirPermOpts() as $directoryID => $directoryOption) : ?>
                                        <option <?= $directorySelected == $directoryOption ? 'selected' : '' ?> value="<?= $directoryID ?>"><?= $directoryOption ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Signature Name</th>
                            <td><?= $packet->getSignatureName() ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php if ($packet->getSignatureFileID()) : ?>
                                    <img src="/_/admin/packets/file?file=<?= $packet->getSignatureFileID() ?>" style="width: 100%;">
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </fieldset>
            </div>
        </div>
    </form>
    <?php

    core_loader::printFooter();
    ?>
    <script>
        pendingsave = false;

        function updateform() {
            pendingsave = true;
        }

        function closePacket() {
            if ($(top.document.body).is('.admin_packets') && $(top.document.body).find('#mth_people_edit').length == 0 && !pendingsave) {
                //top.location=top.location;
                var $row = top.$DataTable.api().row("#packet-" + top.selected_packet);
                var $td = $($row.node()).find("td:eq(2)");
                var status = $('#status').val();
                var old_data = $row.data();
                var selected_statuses = top.$('[name="statuses[]"]:checked').map(function() {
                    return $(this).val()
                }).get();

                if (status == "<?= mth_packet::STATUS_ACCEPTED ?>" || $.inArray(status, selected_statuses) == -1) {
                    $row.remove().draw();
                } else {
                    if (!$td.hasClass('packet-has-age-issue')) {
                        $td.attr('class', 'packet-status-' + status.replace(' ', ''));
                    } else {
                        status = status + ' (Age Issue)';
                    }

                    var new_data = $.extend({}, old_data, {
                        2: "<span class='hidden'>0</span>" + status
                    });
                    $row.data(new_data).draw(false);
                }
            }
            setTimeout(function() {
                top.global_popup_iframe_close('mth_packet_edit');
            }, 500);
        }

        function deleteDocument(fileId) {
            swal({
                title: '',
                text: 'Are you sure you want to delete this file?',
                type: "info",
                showCancelButton: !0,
                confirmButtonClass: "btn-primary",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function (proceedToSave) {
                if (proceedToSave) {
                    $.ajax({
                        url: '?file=' + fileId, // file delete url
                        type: 'GET',
                        success: function () {
                            // delete document on UI
                            $(`.file_${fileId}`).remove();
                        },
                        error: function () {
                            top.swal('', 'There is an error deleting documents.', 'error');
                        }
                    });
                }
            })
        }

        function isVaccineDateEditable() {
            $("#vaccine_table input.ex, #vaccine_table input.na").prop("disabled", false);
            $("#vaccine_table input.date_administered").prop("readOnly", false);
            $('*[data-isImmunityAllowed="0"]').prop("disabled", true);
            $('*[data-isImmunityAllowed="1"]').prop("disabled", false);
            $('input.ex:checkbox:checked, input.na:checkbox:checked, input.im:checkbox:checked').closest("tr").find("input.date_administered").prop("readOnly", true);
            $('input.ex:checkbox:checked, input.na:checkbox:checked, input.im:checkbox:checked').closest("tr").find("input.vaccine-checkbox:not(:checked)").prop("disabled", true);
            $('.imunization_ids').prop("disabled", false);
        }

        function exCheckAll() {
          if($('input#ex-check-all:checkbox:checked').length) {
              $('input.ex').each(function(){
                 if($(this).closest( "tr" ).find( "input.na:not(:checked)").length && $(this).closest( "tr" ).find( "input.im:not(:checked)").length) {
                     $(this).prop( "checked", true );
	                 }
              });
            } else {
              $('input.ex').each(function(){
	                 if($(this).closest( "tr" ).find( "input.na:not(:checked)").length && $(this).closest( "tr" ).find( "input.im:not(:checked)").length) {
                     $(this).prop( "checked", false );
                 }
             });
          }
        checkAllVaccines();
        }

        function exemptionFormDateToogleShow() {
            $('#exemption_form_date_tr').hide();
            $('.exemption_form_date').prop('required', false);
            if ($("input.ex:checkbox:checked").length) {
                $('#exemption_form_date_tr').show();
                $('.exemption_form_date').prop('required', true);
            }
        }

        function getDateInterval(interval, type, referal) {
            if (!interval || !type || !referal) {
                return false;
            }
            let result = new Date();
            switch (type) {
                case 'MONTHS':
                    result = new Date(referal.setMonth(referal.getMonth() + interval));
                    break;
                case 'WEEKS':
                    referal.setDate(referal.getDate() + interval * 7);
                    result = referal;
                    break;
                default:
                    referal.setDate(referal.getDate() + interval);
                    result = referal;
                    break;
            }

            return result;
        }

        function calculateDateAdministered() {
            $('.date_administered').each(function() {
                let selected_date = $(this).val().split("-");
                let selected_year = parseInt(selected_date[0], 10);
                let selected_month = selected_date[1];
                let selected_day = selected_date[2];
              if(selected_year.toString().length <= 2 && selected_year.toString().length > 0) {
                  let current_year = new Date().getFullYear();
                  let current_century = Math.floor(current_year / 100) * 100;
                  selected_year = current_century + selected_year;
	                $(this).val(selected_year+"-"+selected_month+"-"+selected_day);
                }

                let min_date_required = getDateInterval(
                    $(this).data("min-spacing-interval"),
                    $(this).data("min-spacing-date"),
                    new Date($('input[name="date_administered[' + $(this).data("consecutive-vaccine") + ']"]').val()));
                let max_date_required = getDateInterval(
                    $(this).data("max-spacing-interval"),
                    $(this).data("max-spacing-date"),
                    new Date($('input[name="date_administered[' + $(this).data("consecutive-vaccine") + ']"]').val()));
                let warning = true;
                let new_date_value = new Date(selected_year,selected_month-1,selected_day);
                new_date_value.setHours(0, 0, 0, 0);
	              min_date_required ? min_date_required.setHours(0, 0, 0, 0) : min_date_required;
                max_date_required ? max_date_required.setHours(0, 0, 0, 0) : max_date_required
                if (min_date_required && max_date_required) {
                  if(new_date_value >= min_date_required && new_date_value <= max_date_required) {
                        warning = false;
                    }
                } else if (min_date_required) {
                    if(new_date_value >= min_date_required) {
                        warning = false;
                    }
                } else {
                    warning = false;
                }

            if(warning && $(this).val()) {
                $(this).addClass('invalid-consecutive-date');
                    $(this).closest('tr').find('.info-tooltip').show();
                if(($(this).val() != $(this).data("recorded-date") || $(this).data('check-date-range'))) {
                    if(!($(this).closest('tr').find('.na').prop("checked") || $(this).closest('tr').find('.ex').prop("checked") || $(this).closest('tr').find('.im').prop("checked"))) {
                        $(this).addClass('date_invalid');
                    }
                    $(this).removeData('check-date-range');
                }
            }else{
                $(this).removeClass('invalid-consecutive-date')
                    $(this).closest('tr').find('.info-tooltip').hide();
                if($(this).data('check-date-range') && $(this).val()) {
                    $(this).removeData('check-date-range')
                }
                $(this).removeClass('date_invalid');
                }
            });
        }

        function checkAllEx() {
          if($('#vaccine_table input.dynamic-checkbox:checkbox:checked').length == $('input.ex').length) {
              $('input#ex-check-all').prop( "checked", true );
          } else {
              $('input#ex-check-all').prop( "checked", false );
          }
        }

            function checkAllVaccines() {
                $(".vaccine-table-row").each(function () {
                    if (!($(this).find('.date_administered').val() || $(this).find('.na').prop("checked") || $(this).find('.ex').prop("checked") || $(this).find('.im').prop("checked"))
                    ) {
                        $(this).addClass('vaccine_row_not_set');
                    } else {
                        $(this).removeClass('vaccine_row_not_set vaccine_row_needs_review');
                    }
                })
            }
        $(function () {
            exemptionFormDateToogleShow();
            isVaccineDateEditable();
            calculateDateAdministered();
            checkAllEx();
            checkAllVaccines();

            $('#save_button,#save_accept_button,#dropbox_button').click(function (event) {
                $("input[name='button_pressed']").val($(event.target).val())
            })

            $('#packetAdminForm').submit(function (event) {

                if ($("#exemption_form_date_tr").not(":hidden") && $('.exemption_form_date').val() != "") {
                    global_waiting();
                } else if ($("#exemption_form_date_tr").is(":hidden")) {
                    global_waiting();
                }

                let incompleteVaccines = false;
                $(".vaccine-table-row").each(function () {
                    if ((!$(this).find('.date_administered').val() || $(this).find('.date_administered').hasClass('invalid-consecutive-date')) && !($(this).find('.na').prop("checked") || $(this).find('.ex').prop("checked") || $(this).find('.im').prop("checked"))
                    ) {
                        $(this).addClass('vaccine_row_needs_review');
                        incompleteVaccines = true;
                    }
                })

                if (!incompleteVaccines || $('#packetAdminForm').data("pre-submit-passed") == 'true') {
                    return true;
                }

                swal({
                        title: '',
                        text: 'Data is missing for Immunizations. Do you wish to Save the packet?',
                        type: "info",
                        showCancelButton: !0,
                        confirmButtonClass: "btn-primary",
                        confirmButtonText: "Yes",
                        cancelButtonText: "Cancel",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function (proceedToSave) {
                        if (proceedToSave) {
                            $('#packetAdminForm').data("pre-submit-passed", 'true').submit();
                        }
                    })
                global_waiting_hide();
                return false;
            })

            $('.ex,.na,.im').click(function () {
                checkAllEx();
                isVaccineDateEditable();
                checkAllVaccines();
                $(this).closest('.vaccine-table-row').removeClass('vaccine_row_needs_review')
            });

            $('.ex').click(function () {
                exemptionFormDateToogleShow();
            });

            $('.vaccine_datepicker').datepicker();

            $('.date_administered').on('blur', function () {
                $('.date_administered[data-consecutive-vaccine=' + (parseInt($(this).data('consecutive-vaccine')) + 1) + ']').data('check-date-range', 'true')
                if ($(this).val() == '') {
                    $(this).val('');
                } else {
                    $(this).attr('title', '');
                    $(this).closest('.vaccine-table-row').removeClass('vaccine_row_needs_review')
                }
                checkAllVaccines()
                calculateDateAdministered();
            });

            if ($('#vaccine_table').closest('.jquery_accordion_item').data('status') == 'active') {
                $('#vaccine_table').closest('.jquery_accordion_item').toggleClass('active').find('.jquery_accordion_content').slideToggle(400);
            } else {
                $('#immunization_notes_container').toggle();
            }

            $('.jquery_accordion_title').click(function () {
                $(this).closest('.jquery_accordion_item').siblings().removeClass('active').find('.jquery_accordion_content').slideUp(400);
                $(this).closest('.jquery_accordion_item').toggleClass('active').find('.jquery_accordion_content').slideToggle(400);
                $('#immunization_notes_container').toggle(400);
                return false;
            });

            $('.format_date').on('blur', function () {
                let selected_date = $(this).val().split("-");
                let selected_year = parseInt(selected_date[0], 10);
                let selected_month = selected_date[1];
                let selected_day = selected_date[2];
                if (selected_year.toString().length <= 2 && selected_year.toString().length > 0) {
                    let current_year = new Date().getFullYear();
                    var current_century = Math.floor(current_year / 100) * 100;
                    let year = current_century + selected_year;
                    $(this).val(year + "-" + selected_month + "-" + selected_day);
                }
            });

            $('#ex-check-all').change(function () {
                exCheckAll();
                exemptionFormDateToogleShow();
                isVaccineDateEditable();
            });

            $(window).keydown(function (event) {
                if (event.keyCode == 13 && !(event.target.tagName.toLowerCase() == 'textarea')) {
                    event.preventDefault();
                    return false;
                }
            });
        });
    </script>
