<?php

$log = mth_log::getLog();

if (!empty($_GET['clearNotifications']) && !empty($_POST['logItems'])) {
    core_loader::formSubmitable('clearNotifications-' . $_GET['clearNotifications']) || die();

    foreach ($log as $logItem) {
        if (in_array($logItem->getID(), $_POST['logItems'])) {
            $logItem->setNotified();
        }
    }
    header('Location: ./info-changes');
    exit();
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Person Information Changes');
cms_page::setPageContent('Change logs');
core_loader::printHeader('admin');
?>  
<div class="card">
    <form action="?clearNotifications=<?= uniqid() ?>" method="post">
        <div class="card-block pl-0 pr-0">
            <table class="table responsive" id="log_table">
                <thead>
                <tr class="log-personHeader">
                    <th>Person</th>
                    <th>Field</th>
                    <th>Date</th>
                    <th>Old</th>
                    <th>New</th>
                    <th>Changed by</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($log as $logItem): /* @var $logItem mth_log */ ?>
                    <?php if(!$logItem){continue;}?>
                    <tr>
                        <td><?= $logItem->getPerson()?$logItem->getPerson()->getName(true):'' ?></td>
                        <td><?= $logItem->getField() ?></td>
                        <td>
                            <input type="hidden" name="logItems[]" value="<?= $logItem->getID() ?>">
                            <?= date('M. j', $logItem->getDate()) ?>
                        </td>
                        <td><?= $logItem->getOldValue() ?></td>
                        <td><?= $logItem->getNewValue() ?></td>
                        <td><?= $logItem->getChangedByUserName() ?> (uid:<?= $logItem->getChangedByUserID() ?>)</td>
                    </tr>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-round btn-primary">Clear Notifications</button>
        </div>
    </form>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
 <script>
    $(function () {
        $('#log_table').dataTable({
            "bStateSave": true,
            "bPaginate": false
        });
    });
</script>