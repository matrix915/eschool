<?php
require_once(dirname(__FILE__) . '/save.php');
cms_page::setPageTitle('Settings');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>

 <script>
    function showReport(path) {
        global_popup_iframe('settingPopup', '/_/admin/settings/' + path);
    }
</script>
<div class="nav-tabs-horizontal nav-tabs-inverse">
    <?php 
        $current_header = 'general';
        include core_config::getSitePath(). "/_/admin/settings/header.php";
    ?>
    <div class="tab-content p-20 higlight-links">
        
<form method="post" action="?core-settings-edit-form=<?= time() ?>">
  

        <?php foreach (core_setting::getCategorySettings('') as $setting): /* @var $setting core_setting */ ?>
            <h3 style="margin-bottom:5px;"><?= $setting->getTitle() ?></h3>

            <div>
                <div style="font-size: smaller; color:#999"><?= $setting->getDescription() ?></div>
                <?php
                $thisName = 'settings[NONE][' . $setting->getName() . ']';
                $thisID = 'core-setting-NONE-' . $setting->getName();
                switch ($setting->getType()){
                case core_setting::TYPE_HTML:
                    ?>
                    <textarea name="<?= $thisName ?>"
                              id="<?= $thisID ?>"><?= htmlentities($setting->getValue()) ?></textarea>
                    <script type="text/javascript">
                        $('#<?=$thisID?>').ckeditor();
                    </script>
                    <?php
                    break;
                case core_setting::TYPE_TEXT:
                    ?>
                    <input type="text" class="form-control" name="<?= $thisName ?>" id="<?= $thisID ?>" value="<?= $setting->getValue() ?>">
                    <?php
                    break;
                case core_setting::TYPE_BOOL:
                ?>
                <select class="form-control" name="<?= $thisName ?>" id="<?= $thisID ?>">
                    <option value="1" <?= $setting == '1' ? 'selected' : '' ?>>True</option>
                    <option value="0" <?= $setting == '0' ? 'selected' : '' ?>>False</option>
                    </select>
                    <?php
                    break;
                    case core_setting::TYPE_INT:
                        ?>
                        <input type="text" class="form-control" name="<?= $thisName ?>" id="<?= $thisID ?>"
                               value="<?= $setting->getValue() ?>" size="5">
                        <?php
                        break;
                    }
                    ?>
            </div>
        <?php endforeach; ?>
        <br>
        <p><button class="btn btn-round btn-primary"  type="submit">Save</button></p>
    </form>
    </div> 
</div>

<?php
core_loader::printFooter('admin');
?>
