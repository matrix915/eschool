<?php

$logs = mth_system_log::list();

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('System Logs');
cms_page::setPageContent('Change logs');
core_loader::printHeader('admin');
?>
<div class="card">
    <div class="card-block pl-0 pr-0">
        <table class="table responsive" id="log_table">
            <thead>
                <tr class="log-personHeader">
                    <th>New Value</th>
                    <th>Old Value</th>
                    <th>Tag</th>
                    <th>Changed By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $logItem) : /* @var $logItem mth_log */ ?>
                    <tr>
                        <td><?= $logItem->getNewValue() ?></td>
                        <td><?= $logItem->getOldValue() ?></td>
                        <td><?= $logItem->getTag() ?></td>
                        <td><?= ($logItem->getUser() ? $logItem->getUser()->getName() : '') ?></td>
                        <td><?= $logItem->getCreatedDate() ?></td>
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
    $(function() {
        $('#log_table').dataTable({
            "bStateSave": true,
            "bPaginate": false
        });
    });
</script>