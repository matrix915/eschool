<?php

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Announcements');
cms_page::setPageContent('');
core_loader::printHeader('admin');

$announcements = mth_announcements::getAllAnnouncements();
?>
<style>
    .dataTables_info{
        display: none;
    }
</style>
<div class="card">
    <div class="card-header">
        <button class="btn btn-primary btn-round" type="button" onclick="global_popup_iframe('announcenment_create_popup','/_/admin/announcements/create')">Create</button>
    </div>
    <div class="card-block pl-0 pr-0">
        <table class="table responsive" id="announcements">
            <thead>
                <th>Announcement</th>
                <th>Posted By</th>
                <th>Date</th>
                <th>Published</th>
                <th></th>
            </thead>
            <tbody>
                <?php foreach($announcements as $announcement):?>
                    <tr>
                        <td>
                            <a class="link" ><?=$announcement->getSubject()?></a>
                        </td>
                        <td>
                            <?=$announcement->getPostedBy()?>
                        </td>
                        <td data-sort="<?=$announcement->isPublished() ? $announcement->getDatePublished() : $announcement->getTime()?>">
                            <?=date('M j, Y', $announcement->isPublished() ? $announcement->getDatePublished() : $announcement->getTime())?>
                        </td>
                        <td>
                            <?= $announcement->isPublished()?'Yes':'No'?>
                        </td>
                        <td>
                            <button class="btn btn-warning" title="Edit" onclick="global_popup_iframe('announcenment_create_popup','/_/admin/announcements/create?id=<?=$announcement->getID()?>')"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-info" title="View" onclick="global_popup_iframe('announcenment_view_popup','/_/admin/announcements/view?id=<?=$announcement->getID()?>')"><i class="fa fa-eye"></i></button>
                        </td>
                    </tr>
                <?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script type="text/javascript">

    $(function () {
        $('#announcements').dataTable({
            "bPaginate": false,
            "aaSorting": [[2, 'desc']],
            "columnDefs": [
		        { orderable: false, targets: [4]},
		    ]
        });
    });

</script>