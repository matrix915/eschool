<?php
require_once(dirname(__FILE__) . '/settings/save.php');

core_loader::includeCKEditor();
cms_page::setPageTitle('Settings');
core_loader::printHeader('admin');

?>
<style>
    #settingPopup {
        width: 90%;
    }
</style>
<script>
    function showReport(path) {
        global_popup_iframe('settingPopup', '/_/admin/settings/' + path);
    }
</script>
<div class="nav-tabs-horizontal nav-tabs-inverse">
    <?php
    $current_header = 'email';
    include core_config::getSitePath() . "/_/admin/settings/header.php";
    ?>
    <div class="tab-content p-20 higlight-links">
        <li><a onclick="showReport('email-component?category=Applications')">Applications</a></li>
        <li><a onclick="showReport('email-component?category=Miscellaneous')">Miscellaneous</a></li>
        <li><a onclick="showReport('email-component?category=Packets')">Packets (6)</a></li>
        <li><a onclick="showReport('email-component?category=Re-enroll')">Re-enroll (2)</a></li>
        <li>Schedules
            <ul style="margin-bottom: 0;">
                <li><a onclick="showReport('email-component?category=Schedules')">Standard Responses</a></li>
                <li><a onclick="showReport('email-component?category=scheduleBulk')">Reminders</a></li>
            </ul>
        </li>
        <li><a onclick="showReport('email-component?category=User')">User (2)</a></li>
        <li><a onclick="showReport('email-component?category=Withdrawals')">Withdrawals (2)</a></li>
        <li><a onclick="showReport('email-component?category=Interventions')">Interventions (2)</a></li>
        <li><a onclick="showReport('email-component?category=Announcements')">Announcements</a></li>
        <li><a onclick="showReport('email-component?category=EmailVerification')">Email Verification(3)</a></li>
        <li><a onclick="showReport('email-component?category=Enrollment')">Enrollment</a></li>
        <li><a onclick="showReport('email-component?category=LearningLog')">Learning Log</a></li>
        <li><a onclick="showReport('email-component?category=Homeroom')">Homeroom</a></li>
        <li><a onclick="showReport('email-component?category=Reimbursement')">Reimbursement</a></li>
        <li><a onclick="showReport('email-component?category=DirectOrders')">Direct Orders</a></li>
        <li><a onclick="showReport('email-component?category=Re-enrollment')">Re-enrollment</a></li>
    </div>
</div>

<?php
core_loader::printFooter('admin');
?>