<?php
core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Homeroom Resources');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
<style>
a.hidden-settings{
    color:#ccc;
}
</style>
<script>
    function editResource(resource_id) {
        global_popup_iframe('mth_resource_editor', '/_/admin/resources/create?resource_id=' + resource_id);
    }
</script>
<div class="card">
    <div class="card-header">
    <button class="btn btn-round btn-success" onclick="editResource(0)">Add</button>
    </div>
    <div class="card-block">
        <table class="table" id="resources-table">
            <thead>
            </thead>
            <tbody>
                <?php while($resource = mth_resource_settings::each(true)):?>
                    <tr onclick="editResource(<?=$resource->getID()?>)">
                        <td><a href="#" class="<?=!$resource->isAvailable()?'hidden-settings':''?>"><?=$resource->name()?></a></td>
                        <td><small>grades <?= $resource->gradeSpan() ?></small></td>
                    </tr>
                <?php endwhile;?>
            </tbody>
        </table>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
