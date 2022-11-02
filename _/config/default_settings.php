<?php
//deploy beta test

use mth\aws\s3;
use mth\yoda\settings;

defineDefaultSettingConstants();

core_setting::initSiteName('My Tech High - InfoCenter');
core_setting::initSiteEmail(MTH_DEFAULT_EMAIL);

core_setting::init('siteShortName', '', 'InfoCenter', core_setting::TYPE_TEXT, true, 'Site Short Name');

core_setting::set('GoogleAPIkey', MTH_GOOGLE_API_KEY);

core_setting::init(
    'forgotPasswordEmailSubject',
    'User',
    'Password Forgotten - My Tech High',
    core_setting::TYPE_TEXT,
    true,
    'Forgot Password Email Subject',
    ''
);
core_setting::init(
    'forgotPasswordEmailContent',
    'User',
    '<p>A request to reset the password for your account has been made at [SITENAME].</p>
          <p>Use this link to create a new password:</p>
          <p>[LINK]</p>
          <p>If you did not make the request be sure to delete this email and notify us.</p>
          <p>--[SITENAME]</p>',
    core_setting::TYPE_HTML,
    true,
    'Forgot Password Email',
    '<p>If a user forgets his or her password and requests to reset it this email will be sent to them. 
          You can include <b>[SITENAME]</b> in the content which will be replace with the site name. 
            You must include <b>[LINK]</b> or the user will not recieve the link to reset the password.<br>
            <em>Note: This WYSIWYG editor may have abuilities that will not work in an email. 
            For email it\'s best to keep it simple.</em></p>'
);

core_setting::init(
    'canvasAccount',
    'User',
    '<p>There is a canvas account crated for [FIRST_NAME]. Please use the credential below and be sure to reset password after logging in.</p>
    <p>Email: [EMAIL]<br>Password: [PASSWORD]</p>',
    core_setting::TYPE_HTML,
    true,
    'Canvas Account',
    ''
);

core_setting::init(
    'canvasAccountSubject',
    'User',
    'Canvas Account Credential',
    core_setting::TYPE_TEXT,
    true,
    'Canvas Account Subject',
    ''
);

core_setting::init(
    'newAccountEmailSubject',
    'User',
    'New Parent Account - My Tech High',
    core_setting::TYPE_TEXT,
    true,
    'New Account Email Subject',
    ''
);
core_setting::init(
    'newAccountEmailContent',
    'User',
    '<p>You have a new account at [SITENAME].</p>
          <p>Use this link to create a your password:</p>
          <p>[LINK]</p>
          <p>--[SITENAME]</p>',
    core_setting::TYPE_HTML,
    true,
    'New Account Email',
    '<p>When a new acount is created this email will be sent to the user so they can create their password. 
          You can include <b>[SITENAME]</b> in the content which will be replace with the site name. 
            You must include <b>[LINK]</b> or the user will not recieve the link to create a password.<br>
            <em>Note: This WYSIWYG editor may have abuilities that will not work in an email. 
            For email it\'s best to keep it simple.</em></p>'
);

core_setting::init(
    'applicationAcceptedEmailSubject',
    'Applications',
    'My Tech High Application Accepted',
    core_setting::TYPE_TEXT,
    true,
    'Application Accepted Email Subject',
    ''
);
core_setting::init(
    'applicationAcceptedEmailContent',
    'Applications',
    '<p>Dear [PARENT],</p>

        <p>Congratulations!</p>

        <p>You are receiving this note as confirmation that [STUDENT] has been accepted into our [YEAR] My Tech High program.</p>

        <p>In order to finalize acceptance and reserve the spot, please complete the following by <strong>[DEADLINE]</strong>.</p>

        <ul>
          <li>Review all program details and requirements posted at&nbsp;<a href="http://mytechhigh.com/utah" target="_blank">mytechhigh.com/utah</a></li>
          <li>Complete and submit an&nbsp;Enrollment Packet for [STUDENT] (found at <a href="http://sis.mytechhigh.com/">sis.mytechhigh.com</a>)</li>
        </ul>

        <p>Once all documents are received for [STUDENT], we will send you a final&nbsp;acceptance&nbsp;notification. &nbsp;Then you will be invited to submit her course schedule for approval.</p>

        <p>Thanks - we&rsquo;re excited to have [STUDENT] join our program!</p>

        <p>--My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Application Accepted Email Content',
    '<p>When an application is accepted the parent will recieve this email. 
          You can use the following codes in the email content which will be replaces with actual values:</p>
          <dl>
          <dt>[PARENT]</dt>
          <dd>Parent\'s first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student\'s first name</dd>
          <dt>[YEAR]</dt>
          <dd>The school year they applied for (e.g. 2014-15)</dd>
          <dt>[DEADLINE]</dt>
          <dd>The deadline that the packet information must be all submitted</dd>
          </dl>'
);

core_setting::init(
    'packetMissingInfoEmailSubject',
    'Packets',
    'Enrollment Packet Missing Information',
    core_setting::TYPE_TEXT,
    true,
    'Packet Missing Info Email Subject',
    ''
);
core_setting::init(
    'packetMissingInfoEmail',
    'Packets',
    '<p>Dear [PARENT],</p>

          <p>Your enrollment packet for [STUDENT] is not quite complete.</p>

          <p><strong>We still need the following:</strong><br />
          [FILES]</p>

          <p>[INSTRUCTIONS]</p>

          <p>Use the following link to update the enrollment packet:<br />
          [LINK]</p>

          <p>Let us know if you have any questions! &nbsp;We&#39;re happy to help however we&nbsp;can!</p>

          <p>--My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Packet Missing Info Email Content',
    '<p>This is the email a parent recieves if there is missing info in their packet.
          You can use the following codes in the email content which will be replaces with actual values:</p>
          <dl>
          <dt>[PARENT]</dt>
          <dd>Parent\'s first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student\'s first name</dd>
          <dt>[LINK]</dt>
          <dd>The link for the parent to access student\'s packet</dd>
          <dt>[FILES]</dt>
          <dd>List of files that need to be uploaded</dd>
          <dt>[INSTRUCTIONS]</dt>
          <dd>Where the spacific instructions to the parent will be included in the email</dd>
          </dl>'
);

core_setting::init(
    'packetAcceptedEmailSubject',
    'Packets',
    'Enrollment Packet Accepted',
    core_setting::TYPE_TEXT,
    true,
    'Packet Accepted Email Subject',
    ''
);
core_setting::init(
    'packetAcceptedEmail',
    'Packets',
    '<p>Dear [PARENT],
          <p>Your enrollment packet for [STUDENT] has been accepted.</p>
          <p>You should be able to login to the site and build [STUDENT]\'s schedule.</p>
          <p>Thank you!</p>
          <p>--My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Packet Accepted Email Content',
    '<p>This is the email a parent recieves when the packet is accepted.
          You can use the following codes in the email content which will be replaces with actual values:</p>
          <dl>
          <dt>[PARENT]</dt>
          <dd>Parent\'s first name</dd>
          <dt>[STUDENT]</dt>
          <dd>Student\'s first name</dd>
          </dl>'
);

core_setting::init(
    'packetAutoReminderEmailSubject',
    'Packets',
    'Reminder:  Submit My Tech High Enrollment Packet',
    core_setting::TYPE_TEXT,
    true,
    'Packet Auto Reminder Email Subject',
    ''
);
core_setting::init(
    'packetAutoReminderEmail',
    'Packets',
    '<p>Dear [PARENT],</p>
           <p>This is a friendly reminder that you still need to submit the Student Enrollment Packet for [STUDENT] by <b>[DEADLINE]</b>.
           <p>Please contact Amy at admin@mytechhigh.com if you have any questions.  Thanks!</p>
           <p>--My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Packet Auto Reminder Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[DEADLINE]</dt>
            <dd>The deadline that the packet information must be all submitted.</dd>
          </dl>'
);

core_setting::init(
    'packetAutoReminderEmailSubjectTwo',
    'Packets',
    'Action Required for My Tech High Enrollment',
    core_setting::TYPE_TEXT,
    true,
    '2nd Packet Auto Reminder Email Subject',
    ''
);
core_setting::init(
    'packetAutoReminderEmailTwo',
    'Packets',
    '<p>Dear [PARENT],</p>
           <p>Just a reminder to complete the following for [STUDENT]\'s My Tech High enrollment by <b>[DEADLINE]</b>.
           <ul>
              <li>Review all program details and requirements posted at <a href="mytechhigh.com/utah">mytechhigh.com/utah</a>.</li>
              <li>Complete and submit an Enrollment Packet for [STUDENT] (available in your InfoCenter account at <a href="mytechhigh.com/infocenter">mytechhigh.com/infocenter</a>).</li>
           </ul>
           <p>If you have questions about our program, please join a live, online Q&A session (see the schedule at <a href="mytechhigh.com/info">mytechhigh.com/info</a>) or email Amy at admin@mytechhigh.com.  </p>
           <p>-My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    '2nd Packet Auto Reminder Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[DEADLINE]</dt>
            <dd>The deadline that the packet information must be all submitted.</dd>
            <dt>[YEAR]</dt>
            <dd>The school year they applied for (e.g. 2014-15)</dd>
          </dl>'
);

core_setting::init(
    'schedulebcc',
    'Schedules',
    'jen@mytechhigh.com',
    core_setting::TYPE_TEXT,
    true,
    'Email BCC',
    '<p>Send an email copy to this email address</p>'
);
core_setting::init(
    'scheduleApprovedEmailSubject',
    'Schedules',
    '[STUDENT]\'s [SCHOOL_YEAR] My Tech High Schedule Approved',
    core_setting::TYPE_TEXT,
    true,
    'Schedule Approved Email Subject',
    ''
);
core_setting::init(
    'scheduleApprovedEmail',
    'Schedules',
    '<p>Hi [PARENT],</p>
        <p>Well done!  [STUDENT]\'s [SCHOOL_YEAR] schedule has been approved.  You can review it at any 
          time in your InfoCenter account at mytechhigh.com/infocenter.</p>
        <p>Schedule changes can be made any time prior to [SCHEDULE_CLOSE_DATE] by submitting this 
        <a href="https://docs.google.com/forms/d/1gdrgPEPSCXSMwST09lnudaP6KiiV79mtIkKFAeziMJE/viewform">Change 
          Request Form.</a> (The form is also available in Parent Link.)</p>
        <p>The school year begins on [SCHOOL_YEAR_START_DATE].  Additional information will be posted via 
          Announcements in Parent Link in Canvas starting mid-August.</p>
        <p>Please contact Amy at admin@mytechhigh.com if you have any questions.</p>
        <p>Thanks!</p>
        <p>- My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Schedule Approved Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[SCHOOL_YEAR]</dt>
            <dd>The school year of the approved schedule (2014-15)</dd>
            <dt>[SCHEDULE_CLOSE_DATE]</dt>
            <dd>The schedule close date of the school year of the approved schedule (August 15)</dd>
            <dt>[SCHOOL_YEAR_START_DATE]</dt>
            <dd>The start date of the school year of the approved schedule (September 3)</dd>
          </dl>'
);

core_setting::init(
    'scheduleApprovedEmailSubject2ndSem',
    'Schedules',
    '[STUDENT]\'s [SCHOOL_YEAR] My Tech High Schedule Approved',
    core_setting::TYPE_TEXT,
    true,
    '2nd Semester Schedule Approved Email Subject',
    ''
);
core_setting::init(
    'scheduleApprovedEmail2ndSem',
    'Schedules',
    '<p>Hi [PARENT],</p>
        <p>Well done!  [STUDENT]\'s [SCHOOL_YEAR] schedule has been approved.  You can review it at any 
          time in your InfoCenter account at mytechhigh.com/infocenter.</p>
        <p>Please contact Amy at admin@mytechhigh.com if you have any questions.</p>
        <p>Thanks!</p>
        <p>- My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    '2nd Semester Schedule Approved Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[SCHOOL_YEAR]</dt>
            <dd>The school year of the approved schedule (2014-15)</dd>
          </dl>'
);

core_setting::init(
    'scheduleChangeEmailSubject',
    'Schedules',
    'Updates needed for [STUDENT]\'s My Tech High schedule',
    core_setting::TYPE_TEXT,
    true,
    'Change Schedule Email Subject',
    ''
);
core_setting::init(
    'scheduleChangeEmail',
    'Schedules',
    '<p>Hi [PARENT],</p>
        <p>Thank you for submitting [STUDENT]\'s [SCHOOL_YEAR] Schedule.  We need the following 
        information before it can be approved:</p>
        <p>[PERIOD_LIST]</p>
        <p>Please use the link below to update the schedule and then re-submit it for approval:<br>
        [LINK]</p>
        <p>Let us know if you have any questions by contacting Amy at admin@mytechhigh.com.   
        We\'re happy to help however we can!</p>
        <p>Thanks!<br>
        My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Change Schedule Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[SCHOOL_YEAR]</dt>
            <dd>The school year of the approved schedule (2014-15)</dd>
            <dt>[PERIOD_LIST]</dt>
            <dd>The list of periods that need to be changed</dd>
            <dt>[LINK]</dt>
            <dd>The link to the student\'s editable schedule</dd>
          </dl>'
);

core_setting::init(
    'scheduleUnlockEmailSubject',
    'Schedules',
    'Schedule unlocked for changes',
    core_setting::TYPE_TEXT,
    true,
    'Unlock Schedule Email Subject',
    ''
);
core_setting::init(
    'scheduleUnlockEmail',
    'Schedules',
    '<p>Hi [PARENT],</p>
        <p>You may now login to your account at 
          <a href="http://mytechhigh.com/infocenter">mytechhigh.com/infocenter</a> 
          to make changes to [STUDENT]\'s:</p>
        <p>[PERIOD_LIST]</p>
        <p>Thanks!<br>
        My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Unlock Schedule Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[SCHOOL_YEAR]</dt>
            <dd>The school year of the approved schedule (2014-15)</dd>
            <dt>[PERIOD_LIST]</dt>
            <dd>The list of periods that can be changed</dd>
            <dt>[LINK]</dt>
            <dd>The link to the student\'s editable schedule</dd>
          </dl>'
);

core_setting::init(
    'scheduleUnlockFor2ndSemEmailSubject',
    'Schedules',
    'Schedule 2nd Semester Updates Available',
    core_setting::TYPE_TEXT,
    true,
    'Unlock Schedule for 2nd Semester Email Subject',
    ''
);
core_setting::init(
    'scheduleUnlockFor2ndSemEmail',
    'Schedules',
    '<p>Hi [PARENT],</p>
        <p>You may now login to your account at 
          <a href="http://mytechhigh.com/infocenter">mytechhigh.com/infocenter</a> 
          to make updates to [STUDENT]\'s Schedule for the 2nd Semester.</p>
        <p>Thanks!<br>
        My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Unlock Schedule for 2nd Semester Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[SCHOOL_YEAR]</dt>
            <dd>The school year of the schedule (2014-15)</dd>
            <dt>[DEADLINE]</dt>
            <dd>The deadline that the second semester schedule must be submitted by.</dd>
          </dl>'
);

core_setting::init(
    'reEnrollEmailSubject',
    'Re-enroll',
    'Action Required:  Submit Intent to Re-enroll Form for [SCHOOL_YEAR] program',
    core_setting::TYPE_TEXT,
    true,
    'Intent to Re-enroll notification email Subject',
    'Same merge fields as email content'
);
core_setting::init(
    'reEnrollEmailContent',
    'Re-enroll',
    '<p>Hi [PARENT],</p>
        <p>In order to plan ahead for next year, we need you to submit an "Intent to Re-enroll Form" 
          for the [SCHOOL_YEAR] program.</p>
        <p>Action Required by <b>[DEADLINE]</b>:</p>
        <ul><li>Login to InfoCenter at mytechhigh.com/infocenter and submit an 
          "Intent to Re-enroll Form" for each child.</li></ul>
        <p>That\'s it!  You will then be notified as soon as the [SCHOOL_YEAR] scheduling process begins.</p>
        <p>Please contact Amy at admin@mytechhigh.com if you have any questions.</p>
        <p>Thanks!</p>
        <p>- My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Intent to Re-enroll notification email content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[SCHOOL_YEAR]</dt>
            <dd>The school year of the to be enrolled in (2014-15)</dd>
            <dt>[DEADLINE]</dt>
            <dd>Intent to Re-enroll submission deadline (February 15)</dd>
          </dl>'
);

core_setting::init(
    'reEnrollReminderDays',
    'Re-enroll',
    2,
    core_setting::TYPE_INT,
    true,
    'Intent to Re-enroll Reminder Email',
    '<p>Number of days before Intent to Re-enroll Deadline to send the reminder</p>'
);

core_setting::init(
    'reEnrollReminderEmailSubject',
    'Re-enroll',
    'Reminder:  Submit Intent to Re-enroll Form for [SCHOOL_YEAR] program',
    core_setting::TYPE_TEXT,
    true,
    'Intent to Re-enroll reminder email Subject',
    'Same merge fields as email content'
);
core_setting::init(
    'reEnrollReminderEmailContent',
    'Re-enroll',
    '<p>Hi [PARENT],</p>
        <p>This is a friendly reminder that <b>[STUDENT]\'s</b> Intent to Re-enroll is due by <b>[DEADLINE]</b>.  
          Login to your parent account at mytechhigh.com/infocenter to declare your plans for next year.</p>
        <p>Please contact Amy at admin@mytechhigh.com if you have any questions.  Thanks!</p>
        <p>- My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Intent to Re-enroll reminder email content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[SCHOOL_YEAR]</dt>
            <dd>The school year of the to be enrolled in (2014-15)</dd>
            <dt>[DEADLINE]</dt>
            <dd>Intent to Re-enroll submission deadline (February 15)</dd>
          </dl>'
);

core_setting::init(
    'missingLogContent',
    'LearningLog',
    "<p>Hello!</p>
    <p>
    [STUDENT_FIRST]'s Weekly Learning Log was not submitted this week.  Please submit it today for partial points.
    </p>
    Thanks!",
    core_setting::TYPE_HTML,
    true,
    'Missing Learning Log Email Content',
    '<dl>
        <dt>[STUDENT_FIRST]</dt>
        <dd>Student\'s first name</dd>
    </dl>'
);

core_setting::init(
    'missingLogSubject',
    'LearningLog',
    'Notice of Missing Learning Log',
    core_setting::TYPE_TEXT,
    true,
    'Missing Learning Log Email Subject',
    ''
);

core_setting::init(
    'logDaysEditable',
    'LearningLog',
    21,
    core_setting::TYPE_INT,
    true,
    'Days to submit early',
    '<p>Number of days learning log can be submitted early.</p>'
);

core_email::SMTPaddress(MTH_SMTP_ADDRESS);
core_email::SMTPuser(MTH_SMTP_USER);
core_email::SMTPpassword(MTH_SMTP_PASSWORD);
core_email::SMTPhost(MTH_SMTP_HOST);
core_email::SMTPport(MTH_SMTP_PORT);
core_email::SMTPsecure(MTH_SMTP_SECURE);

core_setting::init('packetDeadline', 'Packets', 14, core_setting::TYPE_INT, true, 'Packet Deadline', '<p>Number of days from application accepted before the packet is due.</p>');
core_setting::init('packetDeadlineReminder', 'Packets', 2, core_setting::TYPE_INT, true, 'Packet Deadline Reminder', '<p>Number of days before a packet is due when a reminder is sent.</p>');
core_setting::init('packetDeadlineReminderTwo', 'Packets', 7, core_setting::TYPE_INT, true, '2nd Packet Deadline Reminder', '<p>Number of days before a packet is due when a reminder is sent.</p>');

core_setting::init('2ndSemUpdatesRequiredReminder', 'Schedule', 5, core_setting::TYPE_INT, true, '2nd Semester Deadline Reminder', '<p>Number of days reminder is sent before 2nd semester deadline.</p>');
core_setting::init(
    '2ndSemUpdatesRequiredReminderSubject',
    'Schedule',
    'Reminder:  2nd Semester schedule needs update',
    core_setting::TYPE_TEXT,
    true,
    '2nd Semester Auto Reminder Email Subject',
    ''
);
core_setting::init(
    '2ndSemUpdatesRequiredReminderEmail',
    'Schedule',
    '<p>Dear [PARENT],</p>
           <p>This is a friendly reminder that you still need to submit the Student 2nd semester Schedule for [STUDENT] by <b>[DEADLINE]</b>.
           <p>Please contact Amy at admin@mytechhigh.com if you have any questions.  Thanks!</p>
           <p>--My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    '2nd Semester Auto Reminder Email Content',
    '<dl>
            <dt>[PARENT]</dt>
            <dd>Parent\'s first name</dd>
            <dt>[STUDENT]</dt>
            <dd>Student\'s first name</dd>
            <dt>[DEADLINE]</dt>
            <dd>The deadline is the 2nd Semester Schedule Submission End Date.</dd>
          </dl>'
);

mth_withdrawal::initEmailContent(
    'Submit Withdrawal Form',
    '<p>Hi [PARENT_FIRST],</p>
          <p>We need you to submit this withdrawal form for [STUDENT_FIRST]</p>
          <p>[LINK]</p>
          <p>- My Tech High</p>'
);
mth_withdrawal::initConfirmationEmailContent(
    'Withdrawal process complete',
    '<p>Hi [PARENT_FIRST],</p>
          <p>Attached is a copy of your official Withdrawal Letter for your records.</p>
          <p>Thanks!</p>
          <p>- My Tech High</p>'
);
$infocenter =  'http' . (core_config::useSSL() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
mth_intervention::initMissingLog(
    'Reminder of Missing Learning Log',
    '<p>Hello [PARENT_FIRST],</p>
      <p>This is a friendly reminder that all students in the My Tech High program need to submit a Learning Log every <i>week</i> AND maintain a Homeroom grade of at least 80%.
        </p>
        <p>
        We want to make you aware that [STUDENT_FIRST] is missing one or more Learning Logs or has a Log scored at 0%.
        </p>
        <p>
        You can check your student\'s progress anytime via Homeroom in your <a href="' . $infocenter . '">InfoCenter account</a>.
        </p>
        <p>
        We appreciate the efforts you and your student are making. Please let me know if you have any questions or need help with the Learning Logs.
        </p>
        <p>
        Thanks! <br>
        Stephanie     
        </p>
        '
);

mth_intervention::initFirstNoticeEmail(
    'Initial Withdrawal Notice [DUE_DATE]',
    '<p>Hello [PARENT_FIRST],</p>
      <p>You are receiving this message as an official notice that [STUDENT_FIRST]  is significantly behind in submitting the required Weekly Learning Logs and Attendance Records.</p>
      <p>Students need to submit a Learning Log EVERY WEEK <b>plus</b> maintain a grade of at least 80% to remain enrolled in the My Tech High program.</p>
      <p>To check student progress, login with your parent account here: <a href="https://mytechhigh.instructure.com/grades">https://mytechhigh.instructure.com/grades</a>.  When reviewing their logs please check for the following:
        <ol>
            <li>All Learning Logs are submitted.</li>
            <li>Learning Logs are complete and include the required detail for each class. Many students have submitted all logs but are below 80% because the logs do not include sufficient detail.  (See attached scoring guide.)
            </li>
        </ol>
        </p>
        <p>
            <u>All students who have not submitted missing learning logs and attendance records by the end of the day [DUE_DATE], may be withdrawn from the program. </u>
        </p>
        <p>
            The details of the Withdrawal Policy are included <a href="https://www.mytechhigh.com/enrollment-packet-policies/">here</a>. Note that there may be fees associated with the Withdrawal.
        </p>
        <p>
        Please let me know if you are having problems.  Thank you for your attention to these important matters. 
        </p>
        <p>
        Stephanie <br>
        My Tech High        
        </p>
        '
);

mth_intervention::initFinalNoticeEmail(
    'Final Withdrawal Notice [DUE_DATE]',
    '<p>Hello [PARENT_FIRST],</p>
            <p>Looks like [STUDENT_FIRST] is still significantly behind in submitting Learning Logs.  Students need to submit a Learning Log EVERY WEEK and maintain a grade of at least 80% to remain enrolled in the My Tech High program.</p>
            <p>Action Required by [DUE_DATE]
                <ul><li>Help [STUDENT_FIRST] submit <i>all</i> missing Weekly Learning Logs and Attendance Records.  Note that you can check progress anytime in your <a href="http://mytechhigh.instructure.com/grades">Parent Canvas account</a>.</li></ul>
                OR
                <ul><li>Initiate the withdrawal process by contacting Amy Bowman (admin@mytechhigh.com).  The details of the Withdrawal Policy are included <a href="https://www.mytechhigh.com/enrollment-packet-policies/">here</a>. Note that there may be fees associated with the Withdrawal.</li></ul>
            </p>
            <p>Please call me or email me if you have any questions.</p>
            <p>Thanks, Stephanie</p>
        '
);

mth_intervention::initHeadsUp(
    'Check your student\'s Homeroom progress',
    '<p>Hello [PARENT_FIRST],</p>
        <p>
        You are receiving this notice to make sure you are aware that one or more of your children may be behind in submitting the required Weekly Learning Logs/Attendance Records or has submitted Learning Logs without enough detail and is not receiving full credit. ​​
        </p>
        <p>
        Students​​ are required to submit a Weekly Learning Log and Attendance record and maintain a grade of at least 80% to remained enrolled in the My Tech High program.
        </p>
        <p>
        To check student progress, login to your Parent Canvas account here: <a href="https://mytechhigh.instructure.com/grades">https://mytechhigh.instructure.com/grades</a>
        </p>
        <p>
        Please let me know if you have any questions or need help with the Learning Logs.
        </p>
        <p>
        Thanks!
        </p>
        <p>
        Stephanie <br>
        My Tech High        
        </p>
        '
);

mth_intervention::initConsecutiveEx(
    'Be sure to submit a full Learning Log this week!',
    '<p>Hello [PARENT_FIRST],</p>
    <p>Our records indicate that [STUDENT_FIRST] has exceeded the number of Excused Learning Logs (i.e. two consecutive or a total of four for the year). We want to help your student be successful in their personalized education program and remind you that submitting a full Learning Log every week is required to remain enrolled in the program.</p>
    <p>To check student progress, login to InfoCenter at <a href="https://infocenter.mytechhigh.com" target="_blank">https://infocenter.mytechhigh.com</a> and click on the Homeroom button.</p>
    <p>Please let us know if there\'s anything we can do to help.</p>
    <p>Thanks!,<br>Stephanie</p>'
);

mth_intervention::initProbation(
    'Withdrawal Notice - [DUE_DATE]',
    '<p>Hello [PARENT_FIRST],</p>
    <p>Our records indicate that [STUDENT_FIRST] is on probation from last year for not meeting the Learning Log requirements and is currently below 80% and/or is missing one or more Learning Logs. (To check student progress, login here to InfoCenter and click on the Homeroom button.)</p>
    <p>Action Required by [DUE_DATE] to avoid being withdrawn:<br>
    - Help [STUDENT_FIRST] submit all missing Weekly Learning Logs and/or resubmit Learning Logs for additional points.<br>
    </p>
    <p>OR</p>
    <ul>
        <li>Initiate the Withdrawal process by contacting Amy at admin@mytechhigh.com. The details of the Withdrawal Policy are included here. Note that there may be fees associated with the Withdrawal.</li>
    </ul>
    <p>Please let me know if you have any questions or need help with the Learning Logs.</p>
    <p>Thanks!,<br>Stephanie</p>'
);

mth_intervention::initExceedEX(
    'ACTION REQUIRED - Exceeded Maximum Number of Excused Learning Logs',
    '<p>Hello [PARENT_FIRST],</p>
  <p>Our records indicate that [STUDENT_FIRST] has submitted more than four total Excused Learning Logs for the year.</p>
  <p>To check student progress, <a href="http://mytechhigh.com/infocenter">login here to InfoCenter</a> and click on the Homeroom button.</p>
  <p>As Weekly Learning Logs are an essential requirement of our personalized education program, students who request more than a total of four Excused Logs in the school year will be withdrawn.</p>
  <p>ACTION REQUIRED by the end of the day [DUE_DATE].</p>
  <ul><li>Resubmit a previously excused Learning Log with additional details AND commit to submitting a complete Learning Log every week for the rest of the year to remain enrolled.</li></ul>
  OR<ul><li>If the My Tech High Learning Log requirements are not a good fit for your student this year, initiate the withdrawal process by completing this <a href="http://mytechhigh.com/withdraw">Withdrawal Form</a>.</li></ul>
  <p>Thank you for your attention to this important matter.</p>
  <p>Stephanie</p>'
);

mth_intervention::initEmailBCC('rexc@codev.com'); //hess@mytechhigh.com
mth_announcements::initEmailBCC('rexc@codev.com');
mth_intervention::initConsecutiveExBCC('cambarijanrex@gmail.com'); //help@mytechhigh.com

mth_emailverifier::init(
    [
        'subject' => 'Action Required by May 8: Email Security Verification Update',
        'content' => '<p>Hi, Parents in the 2018-19 My Tech High Program!</p>
        <p>
        As part of our newly-designed Parent Link (which we hope you like!), we are also upgrading our email security system to ensure all Parent Link Announcements and other emails from us are delivered successfully and securely to you.        
        </p>
        <p>
            We take your privacy seriously and will ONLY email you program-related messages.        
        </p>
        <p>
       <b>Action Required by May 8, 2018:</b><br>
        Please click on the email security verification link found directly below this message to confirm that this is your correct email to which you want all My Tech High communications sent moving forward.        
        </p>
        <p>
        <i>NOTE:  If you wish to change your email, please login to InfoCenter, update your email, and then look for a follow-up email verification message.</i>
        </p>
        <p>
            Thanks!       
        </p>
        <p>************My Tech High InfoCenter Email Security Verification Link************</p>
        ',
        'failurl' => 'https://' . $_SERVER['HTTP_HOST'] . '/verify/failed',
        'successurl' => 'https://' . $_SERVER['HTTP_HOST'] . '/verify/verified'
    ],
    [
        'subject' => 'Thank you for submitting an application to the My Tech High program!',
        'content' => '<p>We have received your application to participate in the My Tech High program.</p>Please click on the link below within 24 hours to verify your email address and receive additional instructions:',
        'failurl' => 'https://' . $_SERVER['HTTP_HOST'] . '/verify/failed',
        'successurl' => 'https://' . $_SERVER['HTTP_HOST'] . '/verify/'
    ],
    [
        'subject' => 'Please verify new InfoCenter email address',
        'content' => 'It appears that you have changed your email address in the My Tech High InfoCenter.  In order to confirm email security, please click on the link below within 24 hours to verify your new email address:',
        'failurl' => 'https://' . $_SERVER['HTTP_HOST'] . '/verify/failed',
        'successurl' => 'https://' . $_SERVER['HTTP_HOST'] . '/verify/verified'
    ]
);


core_setting::init(
    'additionalEnrollmentSubject',
    'Enrollment',
    'ACTION REQUIRED TODAY to Remain Enrolled',
    core_setting::TYPE_TEXT,
    true,
    'Additional Enrollment Information email Subject',
    ''
);

core_setting::init(
    'additionalEnrollmentContent',
    'Enrollment',
    '<p>Hi [PARENT],</p>
    <p>Our records indicate that you have not responded to previous requests for additional enrollment 
    information for [STUDENT_FIRST]</p>
    <p>Note that we need this addressed <b>today</b> and that it should take less than 2 minutes to complete.</p>
    
    <p><b>Action required by 9/1/2017</b><br>
    Please use the link below to submit the required information:<br>
    [LINK]
    </p>
    <p>Here\'s what you need to do after logging into InfoCenter:</p>
  
    <p>
    1) Click on the student\'s name.<br>
    2) Click on the Enrollment Packet link.<br>
    3) Click the "Next" button (center bottom of page).<br>
    4) Look for red text that says "This field is required."<br>
    5) Enter the missing information and click Submit.
    </p>
    <p>Thanks!</p>
    <p>- My Tech High</p>',
    core_setting::TYPE_HTML,
    true,
    'Additional Enrollment Information email content',
    '<dl>
        <dt>[PARENT]</dt>
        <dd>Parent\'s first name</dd>
        <dt>[STUDENT_FIRST]</dt>
        <dd>Student\'s First Name</dd>
        <dt>[LINK]</dt>
        <dd>Enrollment Packet Link</dd>
      </dl>'
);

core_setting::init(
    'homeroombcc',
    'Homeroom',
    'help@mytechhigh.com',
    core_setting::TYPE_TEXT,
    true,
    'Homeroom Teacher Email BCC',
    '<p>Send an email copy to this email address</p>'
);

core_setting::init(
    'homeroomremindercontent',
    'Homeroom',
    '<p>Hello [PARENT_FIRST],</p>
      <p>This is a friendly reminder that [STUDENT_FIRST] need to submit a Learning Log for this week AND maintain a Homeroom grade of at least 80%.
        </p>
        <p>
        You can check your student\'s progress anytime via Homeroom in your <a href="' . $infocenter . '">InfoCenter account</a>.
        </p>
        <p>
        We appreciate the efforts you and your student are making. Please let me know if you have any questions or need help with the Learning Logs.
        </p>
        <p>
        Thanks! <br>
        [TEACHER_FULL_NAME]     
        </p>',
    core_setting::TYPE_HTML,
    true,
    'Learning Log Submission Reminder Content',
    ''
);

core_setting::init(
    'homeroomreminder',
    'Homeroom',
    'Learning Log Submission Reminder',
    core_setting::TYPE_TEXT,
    true,
    'Learning Log Submission Reminder Subject',
    ''
);

core_setting::init(
    'reimbursementcc',
    'Reimbursement',
    'kelly@mytechhigh.com',
    core_setting::TYPE_TEXT,
    true,
    'Tech Allowance and 3rd Party Provider Email BCC',
    '<p>Send an email copy to this email address</p>'
);

core_setting::init(
    'reimbursementurcc',
    'Reimbursement',
    'kelly@mytechhigh.com,madisen@mytechhigh.com',
    core_setting::TYPE_TEXT,
    true,
    'Updates required Email BCC',
    '<p>Send an email copy to this email address(es). Separated by comma(,). (eg. kelly@mytechhigh.com,madisen@mytechhigh.com)</p>'
);

if (!core_user::getUserById(1)) {
    core_user::addUserOne(MTH_DEFAULT_EMAIL);
}

core_setting::userLevelNames(array(
    mth_user::L_STUDENT => 'Student',
    mth_user::L_PARENT => 'Parent',
    mth_user::L_TEACHER => 'Teacher',
    mth_user::L_ADMIN => 'Administrator',
    mth_user::L_SUB_ADMIN => 'Sub Administrator',
    mth_user::L_TEACHER_ASSISTANT => 'Teacher Assistant'
));

$adminNav = cms_nav::getNavObj('admin');




$adminNav->addChild('/_/admin/applications', 'Applications', 0, true);
$adminNav->addChild('/_/admin/packets', 'Packets', 0, true);
$adminNav->addChild('/_/admin/schedules', 'Schedules', 0, true);


$adminNav->addChild('/_/admin/reimbursements', 'Reimbursements', 0, true);




$adminNav->addChild('/_/admin/testing', 'Testing', 0, true);

$adminNav->addChild('/_/admin/withdrawals', 'Withdrawals', 0, true);


$adminNav->addChild('/_/admin/unverified', 'Unverified', 0, true);


$subAdminNav = cms_nav::getNavObj('sub-admin');
$subAdminNav->addChild('/_/admin/packets', 'Packets', 0, true);

$teacherNav = cms_nav::getNavObj('teacher');
$teacherNav->addChild('/_/teacher', 'Homeroom', 0, true, 'fa-home');
$teacherNav->addChild('/_/teacher/messenger', 'Messeger', 0, true, 'fa-comment');
$teacherNav->addChild('/_/teacher/students', 'Students', 0, true, 'fa-users');
$teacherNav->addChild('/_/teacher/calendar', 'Calendar', 0, true, 'fa-calendar');

$teacherAssNav = cms_nav::getNavObj('teacherassistant');
$teacherAssNav->addChild('/_/teacher/assistant', 'Homeroom', 0, true, 'fa-home');

$other = $adminNav->addChild('/_/admin/nav', 'More', 50, true);
if ($other) {
    $other->addChild('/_/admin/announcements', 'Announcements', 0, true);
    $other->addChild('/_/admin/homeroom-logs', 'Auto-Grader', 0, true);
    $other->addChild('/_/admin', 'Dashboard', 0, true);
    $other->addChild('/_/admin/calendar', 'Calendar', 0, true);
    $other->addChild('/_/admin/canvas', 'Canvas', 0, true);
    $other->addChild('/_/admin/content', 'Content', 0, true);
    $other->addChild('/_/admin/courses', 'Course Management', 0, true);
    $other->addChild('/_/admin/info-changes', 'Info Changes', 0, true);
    $other->addChild('/_/admin/interventions', 'Interventions', 0, true);
    $other->addChild('/_/admin/people', 'Master', 0, true);
    $other->addChild('/_/admin/nav', 'Navigation', 0, true);
    $other->addChild('/_/admin/parent-link', 'Parent Link', 0, true);
    // $other->addChild('/_/admin/purchased', 'Purchased Courses', 0, true);
    $other->addChild('/_/admin/homeroom-assignment', 'Homerooms', 0, true);
    $other->addChild('/_/admin/settings', 'Settings', 0, true);
    $other->addChild('/_/admin/years', 'Years', 0, true);
    $other->addChild('/_/admin/users', 'Users', 0, true);
    $other->addChild('/_/admin/reports', 'Reports', 0, true);
    $other->addChild('/_/admin/school-assignment', 'School Assignment', 0, true);
    $other->addChild('/_/admin/transitioned', 'Transitions', 0, true);
    $other->addChild('/_/admin/resources', 'HR Resources', 0, true, 'fa-laptop');
    $other->addChild('/_/admin/systemlogs', 'System Logs', 0, true, 'fa-search');
    $other->addChild('/_/admin/reenroll', 'Reenrollment', 0, true, 'fa-refresh');
    $other->addChild('/_/admin/email-sent', 'Email Sent', 0, true, 'fa-envelope');
}

$homeroom = $adminNav->addChild('/_/admin/yoda', 'Yoda', 50, true, 'fa-chevron-right');
if ($homeroom) {
    $homeroom->addChild('/_/admin/yoda/dashboard', 'Yoda Dashboard', 0, true, 'fa-dashboard');
    $homeroom->addChild('/_/admin/yoda/courses', 'Courses', 0, true, 'fa-book');
    $homeroom->addChild('/_/admin/yoda/students', 'Students', 0, true, 'fa-users');
}

core_setting::init(
    'oldreimbursement',
    'advance',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Enable Old Reimbursement Form',
    'mth_reimbursement'
);

core_setting::init(
    'mustangreimbursement',
    'advance',
    true,
    core_setting::TYPE_BOOL,
    true,
    'Enable Mustang Reimbursement Form',
    'mth_reimbursement'
);

core_setting::init(
    'learninglogs',
    'advance',
    false,
    core_setting::TYPE_BOOL,
    true,
    'Allow Learning Logs without approved Schedule',
    'Allow Learning Logs without approved Schedule'
);

mth_canvas::set_url(MTH_CANVAS_URL);
settings::set_url(YODA_URL);
settings::setParentLink(PARENT_LINK);


mth_canvas::set_token(MTH_CANVAS_TOKEN);
if (!mth_canvas_term::getCurrentTerm()) {
    mth_canvas_term::update_mapping();
}

mth_parent::initEmailChangeNoticeSettings();

mth_wooCommerce::configure(
    MTH_WOO_KEY,
    MTH_WOO_SECRET,
    MTH_WOO_SITE,
    true
);

core_setting::set(s3::SET_REGION, MTH_S3_REGION, core_setting::TYPE_TEXT, s3::class);
core_setting::set(s3::SET_KEY_ID, MTH_AWS_KEY_ID, core_setting::TYPE_TEXT, s3::class);
core_setting::set(s3::SET_KEY_SECRET, MTH_AWS_KEY_SECRET, core_setting::TYPE_TEXT, s3::class);
core_setting::set(s3::SET_BUCKET, MTH_S3_BUCKET, core_setting::TYPE_TEXT, s3::class);
