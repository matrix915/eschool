<?php

$emaillog = new mth_emaillogs;

$emailBatches = [];
$students = [];
$parents = [];
$studentIds = $emaillog->allStudentIds();

if(($batches = mth_emailbatch::all()))
{
    foreach($batches as $batch)
    {
        $emailBatches[$batch->getBatchId()] = $batch;
    }
}

foreach(mth_student::getStudents(['StudentID' => $studentIds]) as $student)
{
    $students[$student->getID()] = $student;
}

foreach(mth_parent::getParents($emaillog->allParentIds()) as $parent)
{
    $parents[$parent->getID()] = $parent;
}

$email_list = $emaillog->all();
cms_page::setPageTitle('Email Sent');
cms_page::setPageContent('');
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader('admin');
?>
<div class="card">
    <div class="card-header">
        Total Entries: <span class="student_count_display"><?= count($email_list) ?></span>
    </div>
    <div class="card-block pl-0 pr-0">
        <table id="email_table" class="table responsive">
            <thead>
            <tr>
                <th>Parent</th>
                <th>Parent Email</th>
                <th>Student</th>
                <th>Status</th>
                <th>Type</th>
                <th>Date Created</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($email_list as $email_sent) :
                /** @var $email_sent mth_emaillogs */
                $batchId = $email_sent->emailBatchId();
                if(array_key_exists($batchId, $emailBatches))
                {
                    $emailBatchData = $emailBatches[$batchId];
                    $type = $emailBatchData->type();
                    $category = $emailBatchData->category();
                    $title = $emailBatchData->title();
                } else
                {
                    $type = $email_sent->getType();
                    $category = 'Schedules';
                    $emailSetting = core_setting::get($type, $category);
                    $title = $emailSetting ? $emailSetting->getTitle() : '';
                }
                $student = $students[$email_sent->studentId()];
                $parent = $parents[$email_sent->parentId()];
                ?>
                <tr>
                    <td><?= $parent ? $parent->getName() : '' ?></td>
                    <td><?= $parent ? $parent->getEmail() : '' ?></td>
                    <td><?= $student ? $student->getName() : '' ?></td>
                    <td><?= $email_sent->getStatusLabel() ?></td>
                    <td><?= $title ?></td>
                    <td><?= $email_sent->getDateCreated() ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
    $(function () {
        $('#email_table').DataTable({
            stateSave: false,
            "paging": false,
            "searching": false,
            "info": false,
            "columnDefs": [{
                orderable: false,
                targets: [0]
            },],
            "aaSorting": [
                [1, 'asc']
            ],
        });
    });
</script>