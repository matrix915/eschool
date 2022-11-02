<nav id="core-global-user-menu">
    <div id="core-global-user-links">
        <a href="/_/user/profile" title="Edit your profile"><?= core_user::getUserEmail() ?></a>
        <a href="?logout=1">Logout</a>
    </div>
    <ul class="sf-menu">
        <?php if (core_user::isUserAdmin()): ?>
            <?php if (core_path::getPath()->isAdmin()): ?>
                <li><a href="/"><?= core_setting::getSiteName() ?></a></li>
            <?php else: ?>
                <li>
                    <a href="/_/admin">Admin</a>
                    <?php cms_nav::printNav('admin') ?>
                </li>
            <?php endif; ?>
            <?php if (cms_page::isDefaultPage404()): ?>
                <li><a onclick="<?= getContentEditLink(
                        cms_page::getDefaultPageMainContent(),
                        cms_page::getDefaultPagePath()) ?>">Edit "Page Not Found" error</a></li>
                <li><a onclick="<?= getContentEditLink(
                        cms_page::getDefaultPageMainContent(),
                        cms_page::getDefaultPagePath(),
                        true) ?>">Add Page Here</a></li>
            <?php elseif (($availableAreas = cms_page::getDefaultPageAvailableContentAreas())): ?>
                <li>
                    <a>Edit Page</a>
                    <ul id="cms-content-edit-links">
                        <?php foreach ($availableAreas as $location => $content): ?>
                            <li><a onclick="<?= getContentEditLink($content, cms_page::getDefaultPagePath()) ?>">
                                    <?= $location ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php if (core_path::getPath()->isAdmin()): ?>
                <li>
                &nbsp;
                     Quick Search:
                    <input id="quick_search" type="text" style="width: 200px; max-width: 100%">
                    <input type="button" value="View" id="quick_search_button">
                    <input type="hidden" id="quick_search_selected">
                </li>
                <?php endif; ?>
            <?php endif; ?>
            <?php /*<li><a>Add To Nav</a></li>*/ ?>
        <?php endif; ?>
    </ul>
</nav>
<style type="text/css">
    html {
        margin-top: 35px;
    }
</style>
<script>
        function quickSearchView() {
            var id = $('#quick_search_selected').val();
            if (id) {
                global_popup_iframe('mth_people_edit', '/_/admin/people/edit?' + id.replace(':', '='));
            }
        }
        $(function () {
            var cache = {};
            $("#quick_search").mouseup(function (e) {
                return false;
            }).focus(function () {
                $(this).select();
            }).autocomplete({
                minLength: 3,
                source: function (request, response) {
                    var term = request.term;
                    if (term in cache) {
                        response(cache[term]);
                        return;
                    }

                    $.getJSON("/_/admin/people/search", request, function (data, status, xhr) {
                        cache[term] = data;
                        response(data);
                    });
                },
                select: function (event, ui) {
                    $('#quick_search_selected').val(ui.item.id);
                    quickSearchView();
                }
            });
            $('#quick_search_button').click(quickSearchView);
        });
    </script>