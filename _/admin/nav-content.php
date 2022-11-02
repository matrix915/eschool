<?php

if (isset($_GET['saveNav'])) { //ajax call
    $unusedNavItemIDs = cms_nav::getAllNavItemIDs();
    $finished = array();
    function updateNavItem($id, $navItemArr)
    {
        global $finished;
        if (isset($finished[$id])) {
            return $finished[$id];
        }

        if (strpos($navItemArr['parent'], 'new')) {
            $parentId = updateNavItem($navItemArr['parent'], $_POST['navItem'][$navItemArr['parent']]);
        } else {
            $parentId = str_replace('cms_nav-', '', $navItemArr['parent']);
        }
        if (strpos($id, 'new')) {
            $parentNav = cms_nav::getNavItemByID($parentId);
            $finished[$id] = $parentNav->addChild($navItemArr['path'], $navItemArr['title'], $navItemArr['order'],false,$navItemArr['icon'])->getID();
        } else {
            $navItem = cms_nav::getNavItemByID(str_replace('cms_nav-', '', $id));
            $navItem->update($navItemArr['title'], $navItemArr['order'], $navItemArr['path'], $parentId,$navItemArr['icon']);
            $finished[$id] = $navItem->getID();
        }
        return $finished[$id];
    }

    foreach ((array)@$_POST['navItem'] as $id => $navItemArr) {
        $id = updateNavItem($id, $navItemArr);
        unset($unusedNavItemIDs[$id]);
    }
    foreach ($unusedNavItemIDs as $navItemID) {
        if (($navItem = cms_nav::getNavItemByID($navItemID))) {
            $navItem->delete();
        }
    }
    exit(1);
}

core_loader::includejQueryUI();

cms_page::setPageTitle('Nav Admin');
cms_page::setPageContent('<p>Admin Navigation Settings</p>');
core_loader::printHeader('admin');

?>
    <style type="text/css">
        .cms_nav_item {
            list-style: none;
            display: block;
            border: solid 1px #ddd;
            padding: 5px 10px 0 30px;
            margin: 0 0 10px 0;
            cursor: move;
            background: #fff url(/_/includes/img/grip.png) 5px 5px no-repeat;
            position: relative;
        }

        .ui-sortable-helper {
            box-shadow: 0 5px 20px rgba(0, 0, 0, .3);
            opacity: .5;
        }

        .cms_nav_item input[type="text"] {
            display: block;
            margin: 0 0 -5px 0;
            width: 100%;
            padding: 0;
            border: none;
            font-size: 1em;
            box-shadow: none;
        }

        .cms_nav_item span {
            font-size: smaller;
            color: #999;
            height: 20px;
            overflow: hidden;
            display: block;
        }

        #available_pages, #cms_nav_base, #custom_items {
            list-style: none;
            padding: 0;
            overflow: auto;
            background: none;
            border: none;
            margin: 0;
            box-shadow: none;
            border-radius: 0;
        }

      
        #available_pages {
            height: 300px;
            padding: 10px 20px 0 0;
            margin-right: -20px;
        }

        #available_pages .cms_nav_item {

        }

        #cms_nav_base > li > a {
            margin-bottom: 0;
            cursor: default;
            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
            color: #666;
        }

        #cms_nav_base > li {
            cursor: default;
            background: #fff;
            padding-left: 10px;
        }

        #cms_nav_base > li > ul {
            margin-left: -10px;
        }

        .ui-sortable {
            padding: 10px;
            margin: 0 -10px 0 -30px;
            box-shadow: inset 0 3px 20px rgba(0, 0, 0, .1);
            border: solid 3px #fff;
            background: #f3f3f3;
            border-radius: 5px;
        }

        .rightSide {
            float: right;
            width: 300px;
            border: 1px solid #ddd;
            margin: -11px;
        }

        .rightSide > div {
            padding: 0 20px 10px;
        }

        .rightSide h2 {
            padding: 10px 20px;
            border-top: solid 1px #ddd;
            border-bottom: solid 1px #eee;
            margin: 0 -20px;
        }

      

        .removeButton {
            position: absolute;
            top: 0;
            right: 5px;
            color: #999;
            text-decoration: none;
        }

        .removeButton:hover {
            color: #000;
        }

        #available_pages .removeButton {
            display: none;
        }
    </style>
    <script type="text/javascript">
        $(function () {
            $('#cms_nav_base>li>a').each(function () {
                if (this.innerHTML === 'admin') { //put the admin menu and the end of the list
                    $(this).parent().appendTo('#cms_nav_base');
                }
            });
            $('.admin_page').hide();
            var availablePages = $('#available_pages');
            $('#cms_nav_base li').each(function () {
                var li = $(this);
                li.addClass('cms_nav_item');
                var a = li.children('a');
                if (li.parent().attr('id') !== 'cms_nav_base') {
                    var icon = a.find('.site-menu-icon').attr('class').match(/fa\-(([a-z]|\-)*)(\s?)/gi);
                    icon = icon==null?'':icon;

                    li.prepend('<input type="text" name="title" value="' + (a.find('.site-menu-title').html()) + '"><span>' + a.attr('href') + '</span><a onclick="$(this).parent().remove()" class="removeButton">x</a>');
                    li.append('<input type="text" name="icon" value="'+icon+'" placeholder="fa-*">');
                    if (a.attr('href').indexOf('/') === 0)
                        availablePages.append($('.page_path' + a.attr('href').replace(/\//g, '-')));
                    a.remove();
                    li.attr({id: 'editor-' + li.attr('id')});
                } else {
                    a.attr({href: '#'});
                }
                if (li.children('ul').length < 1)
                    li.append('<ul class="' + li.attr('id') + '"></ul>');
            });

            $("#available_pages, .cms_nav_item ul, #custom_items").sortable({
                connectWith: "#available_pages, .cms_nav_item ul, #custom_items",
                helper: "clone"
            }).disableSelection();
        });
        function cmsNavSave() {
            $('.cms_nav_save_button').each(function () {
                this.disabled = true;
                this.style.cursor = 'wait';
                $(this).html('Saving...');
            });
            cmsNavSave.data = {};
            $('#cms_nav_base li').each(function () {
                var li = $(this);
                var parents = li.parents('li');
                if (parents.length > 0) {
                    var field = 'navItem[' + li.attr('id').replace('editor-', '') + ']';
                    cmsNavSave.data[field + '[parent]'] = parents[0].id.replace('editor-', '');
                    cmsNavSave.data[field + '[path]'] = li.children('span').html();
                    cmsNavSave.data[field + '[title]'] = li.children('[name="title"]').val();
                    cmsNavSave.data[field + '[order]'] = li.prevAll().length;
                    cmsNavSave.data[field + '[icon]'] = li.children('[name="icon"]').val();
                }
            });
            $.ajax('?saveNav=1', {
                'type': 'post',
                'data': cmsNavSave.data,
                'success': function () {
                    $('.cms_nav_save_button').each(function () {
                        window.location.reload();
                    });
                }
            });
        }
        function createCustomItem() {
            if (createCustomItem.num === undefined)
                createCustomItem.num = 1;
            else
                createCustomItem.num++;

            var cItem = $('#cms_nav_new-custom' + createCustomItem.num);
            cItem.children('[name="title"]').val($('#custom_title').val());
            cItem.children('[name="icon"]').val($('#custom_icon').val());
            cItem.children('span').html($('#custom_path').val());
            cItem.show();
            $('#custom_title, #custom_path, #custom-icon').val('');
        }
        function showHideAdminPages() {
            if ($('#hideAdminCB:checked').length > 0) {
                $('.admin_page').hide();
            } else {
                $('.admin_page').show();
            }
        }
    </script>

<?php
$pages = cms_content::getAllContentByLocation(cms_page::LOC_TITLE);
$newID = 0;
?>
    <div class="alert bg-info alert-info">
        INFOCENTER NEW FEATURE: you can now add icons on the navigation. This only supports <a href="http://fontawesome.io/icons/" target="_blank">Font Awesome Icons (fontawesome.io/icons)</a>
        <br>
        Example Usage: input 'fa-envelope' to the field below url field and this will print and evenlope icon
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="btn btn-round btn-primary cms_nav_save_button" onclick="cmsNavSave()" >Save</button>
                    <button type="button" class="btn btn-round btn-success"  onclick="location.reload()" >Cancel/Refresh</button>
                </div>
                <div class="card-block">
                    <ul id="cms_nav_base">
                        <?php cms_nav::printNav(NULL,NULL,NULL,TRUE) ?>
                    </ul>
                </div>
                <div class="card-footer">
                    <button type="button" onclick="cmsNavSave()" class="cms_nav_save_button btn btn-round btn-primary">Save</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header p-15">
                    <h3 class="card-title mb-0">Available Pages</h3>
                   
                </div>
                <div class="card-block">
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" id="hideAdminCB" checked onclick="showHideAdminPages()">
                        <label for="hideAdminCB" onclick="showHideAdminPages()">
                            Hide Admin  Pages
                        </label>
                    </div>

                    <ul id="available_pages">
                        <?php foreach ($pages as $content): $newID++; /* @var $content cms_content */ ?>
                            <li id="cms_nav_new-<?= $newID ?>"
                                class="cms_nav_new cms_nav_item page_path-<?= str_replace('/', '-', $content->getPath()) ?> <?= core_path::getPath($content->getPath())->isAdmin() ? 'admin_page' : '' ?>">
                                <input type="text" name="title" value="<?= $content->getContent() ?>">
                                <span><?= $content->getPath() ?></span><a onclick="$(this).parent().remove()"
                                                                        class="removeButton">x</a>
                                <input type="text" name="icon" value="" placeholder="fa-*">
                                <ul id="cms_nav_sub_new-<?= $newID ?>" class="cms_nav_new_sub"></ul>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Custom Item</h3>
                </div>
                <div class="card-block">
                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" id="custom_title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>URL:</label>
                        <input type="text" id="custom_path" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Icon:</label>
                        <input type="text" id="custom_icon" class="form-control">
                    </div>
                    <ul id="custom_items">
                        <?php foreach (range(1, 20) as $customNum): ?>
                            <li id="cms_nav_new-custom<?= $customNum ?>" class="cms_nav_new cms_nav_item"
                                style="display: none">
                                <input type="text" name="title" value="">
                                <span></span><a onclick="$(this).parent().remove()" class="removeButton">x</a>
                                <input type="text" name="icon" value="">
                                <ul id="cms_nav_sub_new-custom<?= $customNum ?>" class="cms_nav_new_sub"></ul>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-primary btn-round" onclick="createCustomItem()">Create</button>
                </div>
            </div>
        </div>
    </div>
   

  
    <br style="clear: both">
<?php
core_loader::printFooter('admin');
?>