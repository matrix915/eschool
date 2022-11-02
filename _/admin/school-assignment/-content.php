<?php

/**
 * Created by PhpStorm.
 * User: abe
 * Date: 6/23/17
 * Time: 4:09 PM
 */

use mth\packet;
use mth\student\SchoolOfEnrollment;

$mth_provider = new mth_provider;
$available_providers = $mth_provider->where('deleted', 1, '!=')->where('available_in_school_assignment', '1')->orderBy('name', 'asc')->fetch();

if (
    !($selected_schoolYear_id = &$_SESSION[core_config::sessionVar()][__FILE__])
    || !($selected_schoolYear = mth_schoolYear::getByID($selected_schoolYear_id))
) {
    $selected_schoolYear = mth_schoolYear::getCurrent();
    $selected_schoolYear_id = $selected_schoolYear->getID();
}

// This function borrowed from packets/edit-content.php and tweaked for IN-307
function sendToDropBox($packet, mth_student $student, $year = null)
{
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';
    
    if ($year === null) {
        core_notify::addError('No school year provided.');
    }

    if (!$packet) {
        core_notify::addError('No packet found for student ' . $student->getID());
        return false;
    }

    // $dir_year = $packet->getDateAccepted()?mth_schoolYear::getByStartYear($packet->getDateAccepted('Y')):mth_schoolYear::getCurrent();
    // if($year){
    $dir_year = $year;
    // }

    if (!$dir_year) {
        core_notify::addError('Invalid year');
        return false;
    }

    $newSchoolShortName = str_replace('/', '-', $student->getSchoolOfEnrollment()->getShortName());

    core_notify::init();
    $address = $student->getParent()->getAddress();
    $inputState = $address ? $address->getState() : 'UT';

    $path = '/Updated Packets/' . $dir_year . '/' .($inputState == 'OR' ? 'Oregon/' : ''). $newSchoolShortName . '/' . $student->getLastName() . ', ' .
    $student->getFirstName() . ' (' . $student->getID() . ')/';

    $yearOverride = $year;
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
        }
        $success = mth_dropbox::uploadFileFromString($path . $prefix . $file->getUniqueName(), $file->getContents()) && $success;
    }

    if ($success) {
        //        core_notify::addMessage('Packet sent to DropBox');
        return true;
    } else {
        core_notify::addError('There were errors sending the packet to Dropbox');
        return false;
    }
}

if (req_get::is_set('ajax')) {
    header('Content-type: application/json');
    switch (req_get::txt('ajax')) {

        case 'loadStudents':
            if (req_post::is_set('school_year_id')) {
                $selected_schoolYear = mth_schoolYear::getByID(req_post::int('school_year_id'));
                if (!$selected_schoolYear) {
                    exit('0');
                }
                $selected_schoolYear_id = $selected_schoolYear->getID();
            }
            $filter = new mth_person_filter();
            $filter->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
            $filter->setStatusYear([$selected_schoolYear_id]);
            if (req_post::is_set('district')) {
                $pq = new \mth\packet\query();
                $student_ids = $pq->setParentSchoolDistricts(req_post::txt_array('district'), $selected_schoolYear_id, [mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE])->getStudentIds();
                if (empty($student_ids)) {
                    $student_ids = [0];
                }
            }
            if (req_post::is_set('grade')) {
                $filter->setGradeLevel(req_post::txt_array('grade'));
            }
            if (req_post::is_set('provider')) {
                $sq = new \mth\schedule\query();
                $sq->setProviderIds(req_post::int_array('provider'))
                    ->setStatuses([
                        mth_schedule::STATUS_SUBMITTED,
                        mth_schedule::STATUS_CHANGE,
                        mth_schedule::STATUS_RESUBMITTED,
                        mth_schedule::STATUS_ACCEPTED,
                        mth_schedule::STATUS_CHANGE_POST,
                    ])
                    ->setSchoolYearIds([$selected_schoolYear_id]);
                if (isset($student_ids)) {
                    $student_ids = array_intersect($student_ids, $sq->getStudentIds());
                } else {
                    $student_ids = $sq->getStudentIds();
                }
                if (empty($student_ids)) {
                    $student_ids = [0];
                }
            }
            if (isset($student_ids)) {
                $filter->setStudentIDs($student_ids);
            }
            if (req_post::bool('school')) {
                $filter->setSchoolOfEnrollment(req_post::int_array('school'));
            }
            if (req_post::bool('new')) {
                $filter->setIsNew(true);
            } else if (req_post::bool('returning')) {
                $filter->setIsNew(false);
            }
            if (req_post::bool('transferred')) {
                $filter->setTransferred();
            }
            if (req_post::bool('transferredpending')) {
                $filter->setTransferred(1);
            }
            if (req_post::bool('special_ed')) {
                $filter->setSpecialEd(req_post::int_array('special_ed'));
            }
            if (req_post::bool('state')) {
                $filter->setState(req_post::txt_array('state'));
            }

            $student_data = [];
            foreach ($filter->getStudents() as $student) {
                $address = $student->getAddress();
                $student_data[] = [
                    'id' => $student->getID(),
                    'name' => $student->getName(true),
                    'gender' => $student->getGender(),
                    'grade_level' => $student->getGradeLevelValue($selected_schoolYear_id),
                    'city' => ($address ? $address->getCity() : ''),
                    'state' => ($address ? $address->getState() : ''),
                    'school_of_enrollment' => (string) $student->getSchoolOfEnrollment(false, $selected_schoolYear),
                    'soe_year' => (string) $selected_schoolYear,
                    'previous_soe' => (string) $student->getSchoolOfEnrollment(false, $selected_schoolYear->getPreviousYear()),
                    'previous_soe_year' => (string) $selected_schoolYear->getPreviousYear(),
                ];
            }

            /** getting unassigned manually */
            if (in_array(SchoolOfEnrollment::Unassigned, req_post::int_array('school'))) {
                foreach ($filter->getUnassigned() as $student) {
                    $address = $student->getAddress();
                    $student_data[] = [
                        'id' => $student->getID(),
                        'name' => $student->getName(true),
                        'gender' => $student->getGender(),
                        'grade_level' => $student->getGradeLevelValue($selected_schoolYear_id),
                        'city' => ($address ? $address->getCity() : ''),
                        'state' => ($address ? $address->getState() : ''),
                        'school_of_enrollment' => (string) $student->getSchoolOfEnrollment(false, $selected_schoolYear),
                        'soe_year' => (string) $selected_schoolYear,
                        'previous_soe' => (string) $student->getSchoolOfEnrollment(false, $selected_schoolYear->getPreviousYear()),
                        'previous_soe_year' => (string) $selected_schoolYear->getPreviousYear(),
                    ];
                }
            }
            echo json_encode($student_data);
            break;

        case 'assignSchool':
            if (($SOE = SchoolOfEnrollment::get(req_post::int('school_of_enrollment_id')))
                && req_post::bool('student')
                && ($year = mth_schoolYear::getByID(req_post::int('school_year_id')))
            ) {
                // Update students' School of Enrollment
                $students = mth_student::getStudents(array('StudentID' => req_post::int_array('student')));
                foreach ($students as $student) {
                    /* @var $student mth_student */
                    $transferred = 0;
                    if ($student->getSOEname($year)) {
                        $transferred = 1;
                    }
                    $student->setSchoolOfEnrollment($SOE, $year, $transferred);
                }

                // Send new packets to Dropbox
                if (req_post::bool('send_new_packet') && req_post::raw('send_new_packet') === 'true') {
                    // Need to re-query students to pick up new SoE info
                    $students = mth_student::getStudents(array('StudentID' => req_post::int_array('student')));
                    foreach ($students as $student) {
                        $packet = mth_packet::getStudentPacket($student);
                        sendToDropBox($packet, $student, $year);
                    }
                }

                exit('1');
            } else {
                exit('0');
            }
            break;

        case 'sendNewPackets':
            $students = mth_student::getStudents(array('StudentID' => req_post::int_array('student')));
            $year = mth_schoolYear::getByID(req_post::int('school_year_id'));
            foreach ($students as $student) {
                $packet = mth_packet::getStudentPacket($student);
                sendToDropBox($packet, $student, $year);
            }
            break;

        case 'checkMissing':
            $filter = new mth_person_filter();
            $filter->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
            $filter->setStatusYear([$selected_schoolYear_id]);
            $filter->setSchoolOfEnrollment([SchoolOfEnrollment::Tooele]);
            $pq = new \mth\packet\query();
            $pq->setIncludePacketsMissingTooeleData()
                ->setStatuses([mth_packet::STATUS_ACCEPTED])
                ->setStudentIds($filter->getStudentIDs()); //yes the $filter object is used twice
            $student_ids = $pq->getStudentIds();
            if (empty($student_ids)) {
                echo '[]';
                exit();
            }
            $filter->setStudentIDs($student_ids);
            $student_data = [];
            foreach ($filter->getStudents() as $student) {
                $address = $student->getAddress();
                $student_data[] = [
                    'id' => $student->getID(),
                    'name' => $student->getName(true),
                ];
            }
            echo json_encode($student_data);
            break;

        case 'notifyMissing':
            $success = true;
            if (req_post::bool('student')) {
                if (
                    !($student = mth_student::getByStudentID(req_post::int('student')))
                    || !($parent = $student->getParent())
                    || !($packet = mth_packet::getStudentPacket($student))
                ) {
                    $success = false;
                } else {
                    $packet->setReuploadFiles([]);
                    $success = $packet->setStatus(mth_packet::STATUS_MISSING)
                    && $packet->save()
                        && $success;

                    $link = 'https://' . $_SERVER['HTTP_HOST'] . '/student/' . $student->getSlug() . '/packet/5';

                    $email = new core_emailservice();
                    $email->enableTracking(true);
                    $success = $email->send(
                        [$parent->getEmail()],
                        core_setting::get("additionalEnrollmentSubject", 'Enrollment'),
                        str_replace(
                            ['[PARENT]', '[STUDENT_FIRST]', '[LINK]'],
                            [$parent->getPreferredFirstName(), $student->getPreferredFirstName(), ('<a href="' . $link . '">' . $link . '</a>')],
                            core_setting::get("additionalEnrollmentContent", 'Enrollment')->getValue()
                        ),
                        null,
                        [core_setting::getSiteEmail()]
                    );
                }
            }
            echo (int) $success;
            exit();
            break;
    }
    exit();
}

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('School of Enrollment Assignment Manager');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>
        <div id="missing_data" class="alert bg-warning alert-warning" style="display: none;">
            <h4>Missing Packet Data</h4>
            <p>The following Tooele Students are missing data in their packets:</p>
            <div class="missing-container"></div>
            <p>
                <button class="btn btn-round btn-primary">Mark Packets as Missing Info and Notify Parents</button>
            </p>
        </div>

        <div class="mth_filter_block card container-collapse" id="school_assignment_filter_block">
            <div class="card-header">
                <h4 class="card-title mb-0" data-toggle="collapse" aria-hidden="true" href="#soe-filter-cont" aria-controls="soe-filter-cont">
                    <i class="panel-action icon md-chevron-right icon-collapse"></i> Filter
                </h4>
            </div>
            <div class="card-block collapse info-collapse" id="soe-filter-cont">
                <div class="row">
                    <div class="col col-md-4">
                        <fieldset style="width: 100%; overflow: hidden;">
                            <legend>District of Residence</legend>
                            <fieldset style="width: 154px;float: left;">
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" onclick="$('.districtCB').prop('checked', districtMaster = !window.districtMaster)" title="Select/Deselect all">
                                    <label>All UT Districts</label>
                                </div>
                                <?php foreach (mth_packet::getAvailableSchoolDistricts() as $district) { ?>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" class="districtCB" name="district[]" value="<?= $district ?>">
                                        <label>
                                            <?= $district ?>
                                        </label>
                                    </div>
                                <?php } ?>
                            </fieldset>
                            <fieldset style="margin-left: 111px;">
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" onclick="$('.orDistrictCB').prop('checked', $(this).is(':checked'))" title="Select/Deselect all">
                                    <label>All OR Districts</label>
                                </div>
                                <?php foreach (mth_packet::getORAvailableSchoolDistricts() as $district) { ?>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" class="orDistrictCB" name="district[]" value="<?= $district ?>">
                                        <label>
                                            <?= $district ?>
                                        </label>
                                    </div>
                                <?php } ?>
                            </fieldset>
                        </fieldset>
                    </div>
                    <div class="col">
                        <fieldset>
                            <legend>Grade Level</legend>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="grade_all grade_selector">
                                <label>
                                    All Grades
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="grade_k-8 grade_selector">
                                <label>
                                    Grades OR K-8
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="grade_9-12 grade_selector">
                                <label>
                                    Grades 9-12
                                </label>
                            </div>
                            <hr>
                            <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade => $name) {?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="grade[]" value="<?=$grade?>">
                                    <label>
                                        <?=$name?>
                                    </label>
                                </div>
                            <?php }?>
                        </fieldset>
                    </div>
                    <div class="col">
                        <fieldset>
                            <legend>Curriculum Provider</legend>
                            <?php foreach ($available_providers as $provider) {?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="provider[]" value="<?=$provider->provider_id?>">
                                    <label>
                                        <?=$provider->name?>
                                    </label>
                                </div>
                            <?php }?>
                        </fieldset>

                    </div>
                    <div class="col">
                        <fieldset>
                            <legend>&nbsp;</legend>
                            <p>
                                <select name="school_year_id" title="School Year" class="form-control">
                                    <?php foreach (mth_schoolYear::getAll() as $schoolYear) {?>
                                        <option value="<?=$schoolYear->getID()?>" <?=$selected_schoolYear_id == $schoolYear->getID() ? 'selected' : ''?>><?=$schoolYear->getName()?></option>
                                    <?php }?>
                                </select>
                            </p>
                            Special Ed
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="special_ed[]" value="<?=mth_student::SPED_IEP?>">
                                <label>
                                    <?=mth_student::SPED_LABEL_IEP?>
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="special_ed[]" value="<?=mth_student::SPED_504?>">
                                <label>
                                    <?=mth_student::SPED_LABEL_504?>
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="special_ed[]" value="<?=mth_student::SPED_EXIT?>">
                                <label>
                                    <?=mth_student::SPED_LABEL_EXIT?>
                                </label>
                            </div>
                            <p>
                                For <?=mth_schoolYear::getCurrent()?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="new" value="1" id="new" class="soestatus">
                                    <label for="new">
                                        New
                                    </label>
                                </div>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="returning" value="1" id="returning" class="soestatus">
                                    <label for="returning">
                                        Returning
                                    </label>
                                </div>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="transferred" value="1" id="transferred" class="soestatus">
                                    <label for="transferred">
                                        Transferred
                                    </label>
                                </div>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="transferredpending" value="1" id="transferredpending" class="soestatus">
                                    <label for="transferredpending">
                                        Transferred - Pending
                                    </label>
                                </div>
                                <div>
                                    School of Assignment
                                    <?php foreach (SchoolOfEnrollment::getActive() as $num => $school): ?>
                                        <div class="checkbox-custom checkbox-primary">
                                            <input type="checkbox" name="school[]" value="<?=$num?>">
                                            <label>
                                                <?=$school?>
                                            </label>
                                        </div>
                                    <?php endforeach;?>
                                </div>
                                <div>
                                    State of Residence
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" name="state[]" value="OR" id="oregon" class="state">
                                        <label for="state">
                                            Oregon
                                        </label>
                                    </div>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" name="state[]" value="UT" id="utah" class="state">
                                        <label for="state">
                                            Utah
                                        </label>
                                    </div>
                                </div>
                                <br>
                                <button class="btn btn-round btn-primary btn-block">Load</button>
                            </p>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-block">
                <label>
                    Set School Of Enrollment:
                    <select id="schoolOfEnrollmentYearSelect">
                        <?php while ($year = mth_schoolYear::each()): ?>
                            <option value="<?=$year->getID()?>" <?=$year->getID() == $selected_schoolYear_id ? 'selected' : ''?>>
                                <?=$year?>
                            </option>
                        <?php endwhile;?>
                    </select>
                    <select id="schoolOfEnrollmentSelect">
                        <option></option>
                        <?php foreach (SchoolOfEnrollment::getActive() as $num => $school): ?>
                            <option value="<?=$num?>"><?=$school?></option>
                        <?php endforeach;?>
                    </select>
                    <input type="checkbox" id="sendUpdatedPacketToDropbox" name="sendUpdatedPacketToDropbox" value="test" />
                    <label>
                        Send updated packet to Dropbox
                    </label>
                </label>

                <div class="float-right">
                    <button type="button" class="btn btn-round btn-primary" id="sendUpdatedPacketsButton">Send updated packets to Dropbox</button>
                </div>

            </div>
        </div>
        <div class="card">
            <div class="card-block pl-0 pr-0">
                <table id="school_assignment_table" class="table responsive">
                    <thead>
                        <tr>
                            <th><input type="checkbox" title="Un/Select All" class="globalcb" onclick="$('.cb').prop('checked',window.masterCb = !window.masterCb)"></th>
                            <th>Student</th>
                            <th>Gender</th>
                            <th>Grade Level</th>
                            <th>Location</th>
                            <th>State</th>
                            <th><span class="selected_soe_year"></span> SoE</th>
                            <th><span class="previous_soe_year"></span> SoE</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>


        <?php
core_loader::addJsRef('school-assignment', '/_/admin/school-assignment/school-assignment.js');
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
        <script>
            $(function() {
                $('.soestatus').change(function() {
                    $('.soestatus:checked').not(this).prop('checked', false);
                });
            });
        </script>