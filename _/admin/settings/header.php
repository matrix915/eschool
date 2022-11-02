<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $current_header == 'general' ? 'active' : '' ?>" href="/_/admin/settings/general">General
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $current_header == 'smtp' ? 'active' : '' ?>" href="/_/admin/settings/smtp">SMTP
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $current_header == 'email' ? 'active' : '' ?>" href="/_/admin/settings">Email
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $current_header == 'advance' ? 'active' : '' ?>" href="/_/admin/settings/advance">Advance
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $current_header == 're-enroll' ? 'active' : '' ?>" href="/_/admin/settings/re-enroll">Re-enroll
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $current_header == 'schedules' ? 'active' : '' ?>" href="/_/admin/settings/schedules">Schedules
        </a>
    </li>
</ul>