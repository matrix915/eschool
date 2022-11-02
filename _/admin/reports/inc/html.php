<?php
/* @var $reportArr ARRAY */
/* @var $file */

core_loader::includeBootstrapDataTables('css');

core_loader::isPopUp();
core_loader::printHeader();
$popup_id = isset($_popup_id)?$_popup_id:'reportPopup';
?>
    <style>
        #mth-reports-table {
            font-size: 12px;
        }


        .dataTables_filter {
            float: none;
            text-align: left;
        }
    </style>
   
    <div class="iframe-actions">
    <button type="button"  title="Send to Google" class="btn btn-round btn-secondary" onclick="window.open((location.search?location.search+'&':'?')+'google=1')">
        <i class="fa fa-google hidden-md-up"></i><span class="hidden-sm-down">Send to Google</span>
    </button>
    <button  type="button" title="Download CSV" class="btn btn-round btn-secondary" onclick="location=(location.search?location.search+'&':'?')+'csv=1'">
        <i class="fa fa-download hidden-md-up"></i><span class="hidden-sm-down">Download CSV</span>
    </button>
    <button type="button" title="Close" class="btn btn-round btn-secondary"  onclick="top.global_popup_iframe_close(<?= "'$popup_id'" ?>)">
        <i class="fa fa-close hidden-md-up"></i><span class="hidden-sm-down">Close</span>
    </button>
    </div>
    <h2><?= $file ?></h2>
    <p><?= number_format(count($reportArr) - 1) ?> Items</p>
    <div class="card">
        <div class="card-block pl-0 pr-0">
            <table id="mth-reports-table" class="table responsive">
                <?php foreach ($reportArr as $row_key=> $row): ?>
                <?php if (!isset($header_printed)):
                $header_printed = true; ?>
                <thead>
                <tr>
                    <?php foreach ($row as $value): ?>
                        <th><?= $value ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php else: ?>
                    <tr>
                        <?php foreach ($row as $key=>$value): ?>
                            <td <?=isset($columnSort) && isset($columnSort[$row_key]) && isset($columnSort[$row_key][$key]) && !is_null($columnSort[$row_key][$key])?'data-sort="'.$columnSort[$row_key][$key].'"':''?>><?= $value ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>
<script>
$(function () {
    var columndDefs = $.parseJSON('<?= isset($columnDefs)?json_encode($columnDefs):json_encode([]) ?>');
    sortDef  = $.parseJSON('<?= isset($sortDef)?json_encode($sortDef):json_encode([]) ?>');
    
    $('#mth-reports-table').dataTable({
        "bStateSave": false,
        "bPaginate": false,
        columnDefs: columndDefs,
        aaSorting: [[1,'asc'], ...sortDef],
    });
});
</script>