<?php
if (!empty($_GET['ajaxGet'])) {
    header('Content-type: application/json');

    switch ($_GET['ajaxGet']) {
        case 'Main Content':
            $contentObjs = cms_content::getAllContentByLocation(cms_page::LOC_TITLE, false);
            break;
        case 'Redirects':
            $contentObjs = cms_content::getAllContentByLocation(cms_page::LOC_REDIRECT, false);
            break;
        default :
            $contentObjs = cms_content::getAllContent(array('ExcludeLocations' => array(cms_page::LOC_TITLE, cms_page::LOC_REDIRECT, cms_page::LOC_MAIN)), false);
            break;
    }
    $contentArr = array();
    foreach ($contentObjs as $content) {
        /* @var $content cms_content */
        if (strpos($content->getPath(), 'admin') === 0)
            continue;
        $contentStr = substr($content->isHTML() ? trim(preg_replace('/\s+/', ' ', strip_tags($content->getContent()))) : $content->getContent(), 0, 50);
        $contentArr[$content->getID()] = array(
            'path' => $content->getPath(),
            'content' => $contentStr ? preg_replace('/[^a-zA-Z0-9\' ]/', '', $contentStr) : '',
            'priority' => $content->getPriority(),
            'date' => date('M j, Y', $content->getTime()),
            'editfunction' => getContentEditLink($content, core_path::getPath('/'), FALSE, '/_/admin/content'),
            'location' => ($content->getLocation() == cms_page::LOC_TITLE ? 'Title/Main Content' : $content->getLocation())
        );
    }
    echo json_encode($contentArr);
    exit();
}

if (!empty($_GET['delete'])) {
    $content = cms_content::getContentById($_GET['delete']);
    if (!$content)
        core_notify::addError('Could not file the specified content to delete.');
    elseif ($content->delete())
        core_notify::addMessage('Content Deleted');
    if ($content && $content->isTitleOrMain()) {
        $redirect = cms_content::makeRedirect($_GET['redirect'], '/');

        core_notify::addMessage('<a onclick="' . getContentEditLink($redirect, core_path::getPath('/'), FALSE, $_GET['destination']) . '">
      Please specify a path to redirect the page\'s trafic to</a>. 
      If none is given the home page will be used.');
        header('location:/_/admin/content/?edit=' . $redirect->getID() . '&destination=' . @$_GET['destination']);
    } else {
        if ($_GET['destination'][0] === '/')
            $_GET['destination'] = substr($_GET['destination'], 1);
        header('location:/' . @$_GET['destination']);
    }
    exit();
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Content Admin');
cms_page::setPageContent('');
core_loader::printHeader('admin');

?>
<script type="text/javascript">
    $(function () {
        $('#contentTable').dataTable({
            'aoColumnDefs': [{"bSortable": false, "aTargets": [5]}],
            "bPaginate": false,
            "bStateSave": true
        });
        updateTable();
    });
    function updateTable() {
        $('#loadingGraphic').show();
        var oSettings = $('#contentTable').dataTable().fnSettings();
        var iTotalRecords = oSettings.fnRecordsTotal();
        setCookie('SelectedContentLocation', $('#locationSelection').val());
        for (i = 0; i <= iTotalRecords; i++) {
            $('#contentTable').dataTable().fnDeleteRow(0, null, true);
        }
        $.ajax({
            url: '?ajaxGet=' + encodeURIComponent($('#locationSelection').val()),
            success: function (data) {
                var isMainContent = $('#locationSelection').val() === 'Main Content';
                for (var cID in data) {
                    $('#contentTable').dataTable().fnAddData([
                        data[cID].path,
                        data[cID].location,
                        data[cID].content,
                        data[cID].priority,
                        data[cID].date,
                        '<a class"link" onclick="deleteContent(' + cID + ', \'/_/admin/content\'' + (isMainContent ? ',\'' + data[cID].path + '\'' : '') + ')">Delete</a>'
                        + ' <a class"link" onclick="' + data[cID].editfunction + '">Edit</a>'
                    ]);
                }
                $('#loadingGraphic').hide();
            }
        });
    }
</script>
<div class="card">
    <div class="card-block">
        <p>
            Show:
            <select onchange="updateTable()" id="locationSelection">
                <option>Main Content</option>
                <option>Redirects</option>
                <option>Other</option>
            </select>
            <script type="text/javascript">
                var SelectedContentLocation = getCookie('SelectedContentLocation');
                if (SelectedContentLocation)
                    $('#locationSelection').val(SelectedContentLocation);
            </script>
            <img src="/_/includes/img/loading.gif" id="loadingGraphic">
        </p>

        <table id="contentTable" class="table responsive higlight-links">
            <thead>
            <th>Path</th>
            <th>Location</th>
            <th>Content</th>
            <th>Priority</th>
            <th>Date</th>
            <th>Actions</th>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
