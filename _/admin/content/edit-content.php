<?php
/**
 * this page expects $_GET['contentID'], $_GET['pagePath'], and $_GET['newPage'] (optional)
 */

if (req_get::bool('cms-content-edit-form')) {
    core_loader::formSubmitable(req_get::raw('cms-content-edit-form')) || die();

    $content = cms_content::getContentById(req_post::int('contentID'));
    $newContent = $content->saveChanges(req_post::txt('path'), req_post::raw('content'), req_post::int('priority'));

    if ($content->getLocation() == cms_page::LOC_MAIN && req_post::bool('title')) {
        $title = cms_content::getContentByPathLocation($content->getPath(), cms_page::LOC_TITLE);
        if (req_post::txt('title') != $title->getContent()
            || $content->getPath() != $newContent->getPath()
            || $content->getPriority() != $newContent->getPriority()
        ) {
            $title->saveChanges($newContent->getPath(), req_post::txt('title'), $newContent->getPriority());
        }
    }

    if (req_post::txt('createRedirect')) {
        cms_content::makeRedirect($content->getPath(), $newContent->getPath());
    }

    if ($newContent) {
        core_notify::addMessage('Content saved');
        if (req_post::txt('destination')) {
            header('location: ' . req_post::txt('destination'));
        } else {
            header('location: ' . (strpos($newContent->getPath(), '%') === FALSE ? $newContent->getPath() : req_post::txt('pagePath')));
        }
        exit();
    } else {
        core_notify::addError('Unable to save content');
        header('location: /admin/content/edit?contentID=' . $content->getID() . '&pagePath=' . req_post::txt('pagePath') . '&destination=' . req_post::txt('destination'));
        exit();
    }
}

if (empty($_GET['contentID'])) {
    core_notify::addError('Cannot access that page directly.');
    header('location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'admin'));
    exit();
}

$content = cms_content::getContentById($_GET['contentID']);
if (!$content || !$content->isPublished()) {
    core_notify::addError('Cannot edit that content.');
    header('location: ' . $_GET['pagePath']);
    exit();
}

$title = null;
if ($content->getLocation() == cms_page::LOC_MAIN && isset($_GET['newPage'])) {
    $title = '';
    $path = $_GET['pagePath'];
    $contentStr = '';
    $priority = 10;
} else {
    if ($content->getLocation() == cms_page::LOC_MAIN) {
        $title = cms_content::getContentByPathLocation($content->getPath(), cms_page::LOC_TITLE)->getContent();
    } elseif ($content->getLocation() == cms_page::LOC_TITLE
        && $mainContent = cms_content::getContentByPathLocation($content->getPath(), cms_page::LOC_MAIN)
    ) {
        $title = $content->getContent();
        $content = $mainContent;
    }
    $path = $content->getPath();
    $contentStr = $content->getContent();
    $priority = $content->getPriority();
}

if ($content->getType() == cms_content::TYPE_HTML) {
    //core_loader::includeCKEditor();
}

cms_page::setPageTitle('Content Admin');
core_loader::isPopUp();
core_loader::printHeader();

?>
<script type="text/javascript">
    $('#cms-content-edit-path').change(function () {
        if (this.value.indexOf('%') < 0 && $('#cms-content-edit-priority').val() === '0')
            $('#cms-content-edit-priority').val(10);
    });
    function checkPathChange() {
        <?php if($content->getLocation() == cms_page::LOC_MAIN && strpos($path, '%') === false):?>
        if ($('#cms-content-edit-path').val().indexOf('%') === -1
            && $('#cms-content-edit-path').val() !== "<?=$path?>"
            && $('#createRedirect').val() === '0') {
            global_confirm('Would you like to redirect the old path to the new?',
                function () {
                    $('#createRedirect').val('1');
                    $('#cms_content_edit_form').submit();
                },
                'Yes',
                'No');
            return false;
        }
        <?php endif;?>
        return true;
    }
    function deleteContent() {

    }
</script>

        <form method="post" action="?cms-content-edit-form=<?= uniqid('cms-content-edit-form') ?>"
                onsubmit="return checkPathChange();"
                id="cms_content_edit_form" target="_top">
            <input type="hidden" name="contentID" value="<?= $content->getID() ?>">
            <input type="hidden" name="pagePath" value="<?= $_GET['pagePath'] ?>">
            <input type="hidden" name="destination" value="<?= @$_GET['destination'] ?>">
            <input type="hidden" name="createRedirect" value="0" id="createRedirect">
            <h3>Edit <?= $content->getLocation() == cms_page::LOC_MAIN
                    ? 'Page Content'
                    : ($content->getLocation() == cms_page::LOC_TITLE
                        ? 'Page Title'
                        : $content->getLocation()) ?></h3>
            <div class="form-group">
                
                <label for="cms-content-edit-path">Path:</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="<?= $path ?>" name="path" id="cms-content-edit-path">
                    <span class="input-group-btn">
                        <button  type="button"  class="btn btn-primary" onclick="$('#cms-content-edit-path').val('<?= $path ?>')">
                        &circlearrowleft;
                        </button>
                    </span>
                </div>
                <small>
                    Enter the specific page path to only show on that specific
                    page<?= $_GET['pagePath'] ? ' (<a onclick="$(\'#cms-content-edit-path\').val(this.innerHTML).change()">' . $_GET['pagePath'] . '</a>)' : '' ?>
                    . <br>
                    % = all pages, foo% = all paths starting with foo.
                    <?php if ($content->getLocation() == cms_page::LOC_MAIN): ?>
                        (This can useful for page-not-found errors)
                    <?php endif; ?>
                    <br>
                    Leave black for the home page.<br>
                    Exact paths take precedence over others. <br>
                    Changing the path will copy the content to the new path,
                    the content at the old path will not be removed.
                </small>
            </div>
            <?php if ($title !== NULL): ?>
                <div class="form-group">
                    <label for="cms-content-edit-title">Page Title:</label>
                    <input type="text" name="title" class="form-control" id="cms-content-edit-title" value="<?= $title ?>">
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="cms-content-edit-content">Content:</label>
                <?php
                switch ($content->getType()):
                    case cms_content::TYPE_HTML:
                        ?>
                        <textarea name="content" class="form-control" id="cms-content-edit-content"><?= htmlentities($contentStr) ?></textarea>
                        
                        <script src="//cdn.ckeditor.com/4.10.1/full/ckeditor.js"></script>
                        <script>
                                CKEDITOR.config.removePlugins = "iframe,print,format,pastefromword,pastetext,about,image,forms,youtube,iframe,print,stylescombo,flash,newpage,save,preview,templates";
                                CKEDITOR.config.disableNativeSpellChecker = false;
                                CKEDITOR.config.removeButtons = "Subscript,Superscript";

                                CKEDITOR.replace('cms-content-edit-content');
                        </script>
                    <?php
                    break;
                    case cms_content::TYPE_LIMITED_HTML:
                    ?>
                        <textarea class="form-control" name="content" id="cms-content-edit-content"><?= htmlentities($contentStr) ?></textarea>
                        <small>Available html tags: &lt;a&gt;, &lt;span&gt;, &lt;strong&gt;, &lt;b&gt;, &lt;em&gt;, and &lt;i&gt;.</small>
                    <?php
                    break;
                    case cms_content::TYPE_TEXT:
                    ?>
                        <input type="text" class="form-control"  name="content" id="cms-content-edit-content" value="<?= $contentStr ?>">
                    <?php
                    break;
                    case cms_content::TYPE_REDIRECT:
                    ?>
                    <input type="text" class="form-control"  name="content" id="cms-content-edit-content" value="<?= $contentStr ?>">
                        <small>Enter the url to redirect to (can be remote, starting with http://, or local, relative to site
                            root.
                        </small>
                    <?php
                    break;
                    case cms_content::TYPE_URL:
                    ?>
                    <input class="form-control"  type="text" name="content" id="cms-content-edit-content" value="<?= $contentStr ?>">
                        <small>Enter a url (can be remote, starting with http://, or local, relative to site root.</small>
                        <?php
                    break;
                endswitch;
                ?>
            </div>
            <div class="form-group">
                <label for="cms-content-edit-priority">Priority:</label>
                <select name="priority" id="cms-content-edit-priority" class="form-control" style="width:100px;">
                    <?php foreach (range(0, 50) as $num): ?>
                        <option <?= $priority == $num ? 'selected' : '' ?>><?= $num ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Higher priority will take precedence over lower priority.</small>
            </div >
            <p>
                <button  name="button" type="submit" class="btn btn-primary btn-round">Save</button>
                <button class="btn btn-danger btn-round" name="button" type="button" onclick="top.deleteContent(<?= $content->getID() ?>,'<?= $_GET['destination'] ?>','<?= $content->getPath() ?>')">Delete</button>
                <button class="btn btn-secondary btn-round" type="button" onclick="top.global_popup_close('content_edit_popup')">Cancel</button>
            </p>
        </form>
<?php
core_loader::printFooter();
?>