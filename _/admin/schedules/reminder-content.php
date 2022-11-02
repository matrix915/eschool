<?php

$year = !empty(req_get::int('year')) ? mth_schoolYear::getByID(req_get::int('year')) : mth_schoolYear::getCurrent();
$category = 'scheduleBulk';

if(req_get::bool('getStudents'))
{
    $status = req_get::txt('status');
    $yearId = req_get::int('year');

    if($status == '' && $status !== 0)
    {
        core_notify::addError('Please select a status.');
        die();
    }

    $response = [
        'status' => $status,
        'year' => $yearId,
        'successes' => 0,
    ];

    if($status == 'not_started')
    {
        $response['student_ids'] = mth_student::getStudentIdsWithoutSchedules($year);
    } else
    {
        $response['student_ids'] = mth_schedule::getStudentIdsFromStatus($yearId, $status);
    }

    echo json_encode($response);
    return;
}

if(req_get::bool('sendEmails'))
{
    $status = req_post::txt('status');
    $yearId = req_get::int('year');
    $bcc = core_setting::getSiteEmail()->getValue();
    $successes = req_post::int('successes') ?: 0;
    $failures = req_post::int('failures') ?: 0;
    $studentIds = req_post::int_array('batch_student_ids');
    $emailBatchId = req_post::int('email_batch_id');

    $find = [
        '[PARENT]',
        '[STUDENT]',
        '[SCHOOL_YEAR]',
        '[LINK]',
        '[PERIOD_LIST]',
    ];
    $studentFilter = [
        'StudentID' => $studentIds
    ];

    $students = mth_student::getStudents($studentFilter);
    $parents = mth_parent::getParentsByStudentIds($studentIds);

    $parentsById = [];
    foreach($parents as $parent)
    {
        $parentsById[$parent->getID()] = $parent;
    }
    unset($parents);

    if(($status == mth_schedule::STATUS_CHANGE || $status == mth_schedule::STATUS_CHANGE_POST))
    {
        $schedules = mth_schedule::getSchedulesByStudentIDs($studentIds, $year);
        $schedulesById = [];
        foreach($schedules as $schedule)
        {
            $schedulesById[$schedule->student_id()] = $schedule;
        }
        unset($schedules);
    }
    unset($studentIds);

    foreach($students as $student)
    {
        if(!$student || !($parent = $parentsById[$student->getParentID()]))
        {
            continue;
        }
        /**
         * @var mth_parent $parent
         * @var mth_student $student
         */

        $invalidPeriodText = '';
        $studentId = $student->getID();
        if(($status == mth_schedule::STATUS_CHANGE || $status == mth_schedule::STATUS_CHANGE_POST) && !empty($schedulesById))
        {
            $schedule = $schedulesById[$studentId];
            while($period = mth_schedule_period::each($schedule))
            {
                $addText = $period->require_change() || ($period->require_change_date() && !$schedule->isNewSubmission());
                $invalidPeriodText .= $addText ? '<li>Period ' . $period->period_number() . '</li>' : '';
            }
        }
        $link = '<a href="' . $_SERVER['SERVER_NAME'] . '/student/' . $student->getSlug() . '/schedule/' . $year . '">' . $_SERVER['SERVER_NAME'] . '/student/' . $student->getSlug() . '/schedule/' . $year . '</a>';
        $replace = [
            $parent->getFirstName(),
            $student->getFirstName(),
            (string) $year,
            $link,
            !empty($invalidPeriodText) ? '<ul>' . $invalidPeriodText . '</ul>' : ''
        ];

        $email = new core_emailservice();
        $emailResult = $email->send(
            array($parent->getEmail()),
            str_replace($find, $replace, req_sanitize::txt_decode(req_post::txt('subject'))),
            str_replace($find, $replace, req_post::html('content')),
            null,
            array($bcc)
        );

        if($emailResult)
        {
            $successes++;
        } else
        {
            if($emailBatchId == 0)
            {
                $emailBatch = new mth_emailbatch;
                $emailBatch->schoolYearId($yearId);
                $emailBatch->sent_by_id(core_user::getUserID());
                $emailBatch->template(req_post::html('content'));
                $emailBatch->category($category);
                $emailBatch->type('scheduleStatus-' . $status . '-content');
                $settingContent = core_setting::get($emailBatch->type(), $category);
                if(!empty($settingContent))
                {
                    $emailBatch->title($settingContent->getTitle());
                }
                $emailBatch = $emailBatch->create();
                $emailBatchId = $emailBatch->getBatchId();
            }
            $emailLog = new mth_emaillogs;
            $emailLog->emailBatchId($emailBatchId);
            $emailLog->studentId($studentId);
            $emailLog->parentId($student->getParentID());
            $emailLog->schoolYearId($yearId);
            $emailLog->status(mth_emaillogs::STATUS_FAILED);
            $emailLog->type('scheduleStatus-' . $status . '-content');
            $emailLog->emailAddress($parent->getEmail());
            $emailLog->errorMessage("Unable to send schedule status email: "
                . ($status == 'not_started' ? 'Not Started' : mth_schedule::status_option_text($status)));
            $failures++;
            $emailLog->create();
        }
    }

    $response = [
        'successes' => $successes,
        'failures' => $failures,
        'year' => $yearId,
        'status' => $status,
        'email_batch_id' => $emailBatchId,
    ];
    echo json_encode($response);
    exit;
}

$scheduleStatusEmails = [];
$statusOptions = ['not_started' => 'Not Started'];
foreach(mth_schedule::status_options() as $id => $description)
{
    $statusOptions[$id] = $description;
}

foreach($statusOptions as $statusId => $status)
{
    $emailContent = core_setting::get('scheduleStatus-' . $statusId . '-content', $category);
    $emailSubject = core_setting::get('scheduleStatus-' . $statusId . '-subject', $category);
    $count = $statusId === 'not_started' ? mth_schedule::getNotStartedCount($year) : mth_schedule::getStatusCount($statusId, $year);
    $scheduleStatusEmails[$statusId] = [
        'subject' => $emailSubject ? req_sanitize::txt_decode($emailSubject->getValue()) : '',
        'content' => $emailContent ? $emailContent->getValue() : '',
        'email-count' => $count,
    ];
}

core_loader::includeCKEditor();
cms_page::setPageTitle('Send Reminder');
core_loader::isPopUp();
core_loader::printHeader();
?>
    <div id="schedule-reminder-form">
        <h2>Schedule Status Reminder</h2>
        <fieldset class="form-group">
            <legend>Select Schedule Status</legend>
            <select class="form-control" name="select-status" id="select-status">
                <option value="">Select</option>
                <?php foreach($statusOptions as $statusId => $status):
                    if($statusId != mth_schedule::STATUS_ACCEPTED && $statusId != mth_schedule::STATUS_CHANGE_PENDING) : ?>
                        <option value="<?= $statusId ?>">
                            <?= $status ?>
                        </option>
                    <?php endif;
                endforeach; ?>
            </select>
        </fieldset>
        <fieldset class="form-group">
            <legend>Subject</legend>
            <input type="text" class="form-control" name="subject" id="email-subject" value="">
            <legend>Content</legend>
            <textarea name="emailContent" id="emailContent"></textarea>
        </fieldset>
        <p style="margin-bottom: 0">Available for use in Subject and Content</p>
        <table style="font-size: 10px; color: #999">
            <tr>
                <td>[PARENT]</td>
                <td>Parent's first name</td>
            </tr>
            <tr>
                <td>[STUDENT]</td>
                <td>Student's first name</td>
            </tr>
            <tr>
                <td>[SCHOOL_YEAR]</td>
                <td>The school year of the schedule (<?= $year ?>)</td>
            </tr>
            <tr>
                <td>[LINK]</td>
                <td>The link to the student's editable schedule</td>
            </tr>
            <tr>
                <td style="display: flex; text-align:start;">[PERIOD_LIST]</td>
                <td>The list of periods that need to be changed (This should be used in Updates Required and Unlocked
                    templates only)
                </td>
            </tr>
        </table>
        <br>
        <p><span id="email-count">0</span> Emails to be Sent.</p>
        <p>
            <button type="button" id="send-button" class="btn btn-primary btn-round">Send</button>
            <button type="button" onclick="top.global_popup_iframe_close('mth_schedule_reminder')"
                    class="btn btn-round btn-secondary">
                Close
            </button>
        </p>
        <script>
            CKEDITOR.config.removePlugins = "image,forms,youtube,iframe,print,stylescombo,table,tabletools,undo,specialchar,removeformat,pastefromword,pastetext,smiley,font,clipboard,selectall,format,blockquote,div,resize,elementspath,find,maximize,showblocks,sourcearea,scayt,colorbutton,about,wsc,justify,bidi,horizontalrule";
            CKEDITOR.config.removeButtons = "Subscript,Superscript,Anchor";

            var emailContent = $('#emailContent');
            emailContent.ckeditor();
            let statusesEmailData = <?= json_encode($scheduleStatusEmails) ?>;

            $("#select-status").change(function () {
                if (this.value !== '') {
                    let emailData = statusesEmailData[this.value];
                    $("#email-subject").val(emailData['subject'])
                    if (emailContent.val().indexOf(emailData['content']) < 0) {
                        CKEDITOR.instances.emailContent.setData(emailData['content']);
                    }
                    $('#email-count').html(emailData['email-count'])
                } else {
                    $("#email-subject").val('')
                    CKEDITOR.instances.emailContent.setData('')
                    $('#email-count').html(0)
                }
            })

            $('#send-button').click(function startSend() {
                this.swal = swal;
                this.student_ids = [];

                global_waiting();
                if ($('#select-status').val() === '') {
                    this.swal('', 'Please select a status option.', 'warning');
                    global_waiting_hide();
                    return false;
                }

                if ($('#email-count').html() <= 0) {
                    this.swal('', 'There are no emails to be sent.', 'warning');
                    global_waiting_hide();
                    return false;
                }

                function sendBatch(data) {
                    if(startSend.student_ids.length === 0) {
                        var title = '';
                        var text = data.successes + ' messages sent successfully';
                                if (data.failures > 0) {
                            title = data.successes + ' messages sent successfully';
                            text = data.failures + ' message(s) not sent successfully, please check Email Sent page for more details';
                        }
                        global_waiting_hide();
                        this.swal({
                            title: title,
                            text: text,
                            type: 'info',
                            showCancelButton: false,
                            confirmButtonClass: 'btn-primary',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            location = '?year=<?= $year->getID() ?>'
                        });
                    } else {
                        $.ajax({
                            url: "?year=" + data.year + "&sendEmails=1",
                            method: 'POST',
                            async: false,
                            data: {
                                "batch_student_ids": startSend.student_ids.splice(0, 25),
                                'status': data.status,
                                'subject': $("#email-subject").val(),
                                'content': $("#emailContent").val(),
                                'successes': data.successes,
                                'failures': data.failures,
                                'email_batch_id': data.email_batch_id,
                            },
                            success: function (response) {
                                response = JSON.parse(response);
                                sendBatch(response);
                            },
                            error: function () {
                                this.swal('', 'An error occurred while sending students. ' + response.successes + ' messages sent successfully', 'warning');
                            }
                        })
                    }
                }

                $.ajax({
                    url: "?year=<?= $year->getID() ?>&getStudents=1&status=" + $("#select-status").val(),
                    method: 'GET',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.student_ids.length === 0) {
                            swal('', 'No students found. Please try again.', 'warning');
                            global_waiting_hide();
                            return false;
                        } else {
                            startSend.student_ids = response.student_ids;
                            response.email_batch_id = 0;
                            sendBatch(response);
                        }
                    },
                    error: function () {
                        swal('', 'Unable to obtain student IDs. Please try again.', 'warning');
                    }
                })
            });
        </script>
    </div>
<?php
core_loader::printFooter();
?>