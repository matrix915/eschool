<?php
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */
/* @var $packetRunValidation */
$packet_year = mth_packet::getActivePacketYear($packet);

if (!empty($_GET['packetForm3'])) {
    core_loader::formSubmitable('packetForm3-' . $_GET['packetForm3']) || die();

    $student->setGradeLevel($_POST['grade_level'], $packet_year);
    $sped = isset($_POST['special_ed']) ? $_POST['special_ed'] : mth_packet::SPECIALED_NO;

    if ($sped == 2) {
        if ($_POST['iep_active'] == 1) {
            $sped = 2;

            // if ($_POST['require_iep'] == 1) {
            //     $sped = 2;
            // } else {
            //     $sped = 2; // mth_packet::SPECIALED_EXIT;
            // }
        } else {
            $sped = 0;
        }
    }

    if ($student->specialEd() || $sped > 1) {
        $packet->setSpecialEd($sped);
        if ($sped == mth_packet::SPECIALED_504 || $sped == mth_packet::SPECIALED_IEP) {
            //$packet->setSpecialEdDesc($_POST['special_ed_desc']);
            $packet->setUnderstandsSpecialEd($_POST['understand_special_ed']);
            $packet->setUnderstandsSpedScheduling($_POST['understand_sped_scheduling']);
        }
    }

    // $packet->setSchoolDistrict($_POST['school_district']);

    $packet->setLastSchoolType($_POST['last_school_type']);
    if ($_POST['last_school_type']) {
        if (in_array('last_school', $packet->getReuploadFiles())
            && $packet->getLastSchoolName() != $_POST['last_school']
        ) {
            $packet->setReuploadFiles(array_diff($packet->getReuploadFiles(), array('last_school')));
        }
        if (in_array('last_school_address', $packet->getReuploadFiles())
            && $packet->getLastSchoolAddress(false) != $_POST['last_school_address']
        ) {
            $packet->setReuploadFiles(array_diff($packet->getReuploadFiles(), array('last_school_address')));
        }
        $packet->setLastSchoolName($_POST['last_school']);
        $packet->setLastSchoolAddress($_POST['last_school_address']);
        $packet->setPermissionToRequestRecords($_POST['permission_to_request_records']);
    }

    $packetStep = 4;

    if (levelThreeComplete($packet, $student) && $packet->requiresReenrollFiles()) {
        header('location: ' . $packetURI . '/4');
        exit();
    }

    header('location: ' . $packetURI);
    exit();
}

core_loader::printHeader('student');
?>

<div class="page">
    <?=core_loader::printBreadCrumb('window');?>
    <div class="page-content container-fluid">
        <form action="?packetForm3=<?=uniqid()?>" id="packetForm3" method="post">
            <div class="card">
                <?php include core_config::getSitePath() . '/student/packet/header.php';?>
                <div class="card-block">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="grade_level"><?=$student->getPreferredFirstName()?>'s Grade Level (age)
                                    for <?=$packet_year?></label>
                                <select id="grade_level" name="grade_level" required class="form-control">
                                    <option></option>
                                    <?php foreach (mth_student::getAvailableGradeLevels() as $grade_level => $grade_desc): ?>
                                        <option
                                            value="<?=$grade_level?>" <?=$student->getGradeLevelValue($packet_year) == $grade_level ? 'selected' : ''?>><?=$grade_desc?></option>
                                    <?php endforeach;?>
                                </select>
                            </div>
                                <?php
                                    $packet_sped_status = $packet->getSpecialEd(true);
                                    $default_sped_status = $student->specialEd();
                                    //show when packet if has a packet sped and student sped not equal to exit
                                    if (!(!$packet_sped_status && $default_sped_status == mth_student::SPED_EXIT)): ?>
                                    <p>
                                    <label for="special_ed">Has this student ever been diagnosed with a learning disability or
                                    ever qualified for Special Education Services through an IEP or 504 plan (including Speech
                                    Therapy)?</label>
                                    <?php
                                    $sped_map = [
                                        mth_student::SPED_NO => mth_packet::SPECIALED_NO,
                                        mth_student::SPED_IEP => mth_packet::SPECIALED_IEP,
                                        mth_student::SPED_504 => mth_packet::SPECIALED_504,
                                        mth_student::SPED_EXIT => mth_packet::SPECIALED_EXIT,
                                    ];
                                    ?>

                                <?php foreach (mth_packet::getAvailSpecialEd() as $specialEdID => $specialEd): ?>
                                    <div class="radio-custom radio-primary">
                                        <input type="radio" name="special_ed" id="special_ed-<?=$specialEdID?>"
                                            value="<?=$specialEdID?>"
                                            <?php
                                                echo (!$packet_sped_status && $specialEdID == 0 && $default_sped_status == mth_student::SPED_EXIT) //mark as no if by default is exit before acceptance
                                                    || ($default_sped_status == mth_student::SPED_EXIT && $specialEdID == mth_packet::SPECIALED_IEP && $packet_sped_status) //EXIT is under IEP Option and student is not default to exit before acceptance
                                                    || ((!$packet_sped_status && $sped_map[$default_sped_status] === $specialEdID) // When packet is not set yet use student status
                                                        || ($packet_sped_status && $packet_sped_status == $specialEdID) //When packet is set
                                                        || (!$packet_sped_status && $specialEdID == 0)
                                                    ) ? 'checked' : '';
                                                // echo ($specialEdID == 2) ? 'checked' : '';
                                            ?>
                                            <?php
                                            echo (!$packet_sped_status && $default_sped_status == mth_student::SPED_EXIT) || (!empty($packet_sped_status) && ($default_sped_status && $specialEdID < 2)) ? 'disabled' : '';
                                            // echo ($specialEdID == 0) || ($specialEdID == 4) ? 'disabled' : '';
                                            ?>
                                        >
                                        <label
                                        for="special_ed-<?=$specialEdID?>" <?=$student->specialEd() && $specialEdID < 2 ? 'style="color:#999"' : ''?>>
                                        <?=$specialEd?>
                                        </label>
                                    </div>
                                <?php endforeach;?>
                                <label for="special_ed" class="error" style="display: none;"></label>
                            </p>
                            <?php endif;?>
                            <div id="special_ed_iep" style="display: none;">
                                <label>Has the IEP been active in the past 3 years?</label>
                                <div class="radio-custom radio-primary">
                                    <input type="radio"  value="0" name="iep_active" id="iep_not_active" <?=$default_sped_status == mth_student::SPED_NO ? 'CHECKED' : ''?> disabled>
                                    <label for="iep_not_active" >No&nbsp;</label>
                                </div>
                                <div class="radio-custom radio-primary">
                                    <input type="radio"  value="1" name="iep_active" id="iep_active"  <?=$default_sped_status == mth_student::SPED_EXIT || $default_sped_status == mth_student::SPED_IEP ? 'CHECKED' : ''?> disabled>
                                    <label for="iep_active" >Yes&nbsp;</label>
                                </div>
                            </div>

                            <fieldset id="special_ed_iep_active" style="display: none;">
                                <label>From your perspective, do you think your student still requires Special Education services?</label>
                                <div class="radio-custom radio-primary">
                                    <input type="radio"  value="0" name="require_iep" id="require_iep_no" <?=(!$packet_sped_status && $default_sped_status == mth_student::SPED_EXIT) || ($packet_sped_status && $packet_sped_status == mth_packet::SPECIALED_EXIT) ? 'CHECKED' : ''?> disabled>
                                    <label for="require_iep_no" >No, please email me an exit form to sign</label>
                                </div>
                                <div class="radio-custom radio-primary">
                                    <input type="radio"  value="1" name="require_iep" id="require_iep_yes"  <?=(!$packet_sped_status && $default_sped_status == mth_student::SPED_IEP) || ($packet_sped_status && $packet_sped_status == mth_packet::SPECIALED_IEP) ? 'CHECKED' : ''?> disabled>
                                    <label for="require_iep_yes" >Yes, please schedule me for a meeting with the Special Ed team this fall to review and update the IEP</label>
                                </div>
                            </fieldset>

                            <fieldset id="special_ed_desc-block" style="display: none;">
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="understand_special_ed" id="understand_special_ed" value="1" CHECKED disabled>
                                    <label for="understand_special_ed" >
                                        I understand that an IEP/504 is an important legal document that defines a student's
                                        educational plan and that it must be reviewed regularly by the school's Special Education
                                        IEP/504 team.</label>
                                </div>
                                <label for="understand_special_ed" class="error"></label>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="understand_sped_scheduling" id="understand_sped_scheduling" value="1" CHECKED disabled>
                                    <label for="understand_sped_scheduling" >
                                        I also understand that all final curriculum and scheduling choices for students with an IEP/504
                                        must be made in consultation with the parent and the school's Special Education team.</label>
                                </div>
                                <label for="understand_sped_scheduling" class="error"></label>

                            </fieldset>
                        </div>
                        <div class="col-md-6">
                            <fieldset>
                                <legend>Last School Attended</legend>
                                <?php foreach (mth_packet::getAvailableSchoolTypes() as $typeID => $schoolType): ?>
                                    <div class="radio-custom radio-primary">
                                        <input type="radio" name="last_school_type" id="last_school_type-<?=$typeID?>"
                                                value="<?=$typeID?>" <?=$packet->getLastSchoolType(true) == $typeID ? 'checked' : ''?>>
                                        <label for="last_school_type-<?=$typeID?>">
                                            <?=$schoolType?>
                                        </label>
                                    </div>
                                <?php endforeach;?>
                                <label for="last_school_type" class="error"></label>
                                <div class="form-group">
                                    <label for="last_school">Name of School</label>
                                    <input type="text" id="last_school" name="last_school" value="<?=$packet->getLastSchoolName()?>"
                                        disabled required class="last_school_field form-control">
                                </div>
                                <div class="form-group">
                                    <label for="last_school_address">Address of School</label>
                                    <textarea id="last_school_address" name="last_school_address"
                                            disabled required
                                            class="last_school_field form-control"><?=$packet->getLastSchoolAddress(false)?></textarea>
                                </div>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="permission_to_request_records" id="permission_to_request_records"
                                                value="1" <?=$packet->getPermissionToRequestRecords() !== false ? 'checked' : ''?>
                                                required disabled
                                                class="last_school_field">
                                    <label for="permission_to_request_records">
                                        I understand that <?=$student->getPreferredFirstName()?>'s records will be requested
                                        from his/her prior school anytime
                                        after June 1 (for Fall enrollments) or January 15 (for mid-year enrollments).</label>
                                </div>
                                <label for="permission_to_request_records" class="error"></label>
                            </fieldset>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary btn-round btn-lg">Next &raquo</button>
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
    var packetForm = $('#packetForm3');
    packetForm.validate({
        rules: {
            "special_ed[]": {
                required: true
            },
            last_school_type: {
                required: true
            }
        },
        messages: {
            "special_ed[]": 'Please select one.',
            last_school_type: 'Please select one.'
        }
    });
    setInterval(function () {
        sped = $('input[name="special_ed"]:checked').val();

        if($('#special_ed-<?=mth_packet::SPECIALED_504?>').is(':checked') || ($('#require_iep_yes').is(':checked') && !$('#require_iep_yes').is(':disabled'))){
            $('#special_ed_desc-block').fadeIn().find('input, textarea').prop('disabled', false);
        }else {
            $('#special_ed_desc-block').fadeOut().find('input, textarea').prop('disabled', true);
        }


        //if Yes IEP
        if(sped== 2){
            $('#special_ed_iep').fadeIn().find('input, textarea').prop('disabled', false);
            if($('input[name="iep_active"]:checked').val() == 1){
                $('#special_ed_iep_active').fadeIn().find('input, textarea').prop('disabled', false);
            }else{
                $('#special_ed_iep_active').fadeOut().find('input, textarea').prop('disabled', true);
            }
        }else{
            $('#special_ed_iep').fadeOut().find('input, textarea').prop('disabled', true);
            $('#special_ed_iep_active').fadeOut().find('input, textarea').prop('disabled', true);
        }

        if ($('input[name="last_school_type"]:checked').val() > 0) {
            $('.last_school_field').prop('disabled', false);
        } else {
            $('.last_school_field').prop('disabled', true);
        }
    }, 500);


    <?php if ($packetRunValidation): ?>
    setTimeout(function () {
        if (!packetForm.valid()) {
            packetForm.submit(); //this will focus the cursor on the problem fields
        }
    }, 2000);
    <?php endif;?>
</script>