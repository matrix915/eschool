<?php

($category = req_get::txt('category'));

if (is_null($category) && isset($_SESSION['core-setting-category'])) {
    $category = $_SESSION['core-setting-category'];
} else {
    $_SESSION['core-setting-category'] = $category;
}

require_once(dirname(__FILE__) . '/save.php');
//core_loader::includeCKEditor();
core_loader::isPopUp();
core_loader::printHeader();
?>
<script src="//cdn.ckeditor.com/4.10.0/standard/ckeditor.js"></script>
<script>
    CKEDITOR.config.removePlugins = "about,image,forms,youtube,iframe,print,format,pastefromword,pastetext,stylescombo,flash,newpage,save,preview,templates,table,tabletools,undo,bidi,specialchar";
    CKEDITOR.config.disableNativeSpellChecker = false;
</script>

<button class="btn btn-round btn-secondary iframe-close" onclick="top.global_popup_iframe_close('settingPopup')" type="button">Close</button>
<form method="post" action="?category=<?= $category ?>&core-settings-edit-form=<?= time() ?>">
    <?php foreach (core_setting::getCategorySettings($category) as $setting) : /* @var $setting core_setting */ ?>

        <h3 style="margin-bottom:5px;"><?= $setting->getTitle() ?></h3>

        <div>
            <div style="font-size: smaller; color:#999"><?= $setting->getDescription() ?></div>
            <?php
                $thisName = 'settings[' . $category . '][' . $setting->getName() . ']';
                $thisID = 'core-setting-' . $category . '-' . $setting->getName();
                switch ($setting->getType()) {
                    case core_setting::TYPE_HTML:
                        ?>
                    <textarea name="<?= $thisName ?>" id="<?= $thisID ?>"><?= htmlentities($setting->getValue()) ?></textarea>
                    <script type="text/javascript">
                        CKEDITOR.replace('<?= $thisID ?>');
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
                        <option value="0" <?= $setting == '0' ? 'selected' : '' ?>>True</option>
                    </select>
                <?php
                        break;
                    case core_setting::TYPE_INT:
                        ?>
                    <input class="form-control" type="text" name="<?= $thisName ?>" id="<?= $thisID ?>" value="<?= $setting->getValue() ?>" size="5">
                <?php
                        break;
                }
                ?>
        </div>
    <?php endforeach; ?>
    <?php
    if ($category == 'Re-enrollment' && core_setting::get('unlock_packet', 'packet_settings')->getValue()) :
        ?>
        <?php foreach (core_setting::getCategorySettings('re-enrollment_packet') as $setting) :
                if (!core_setting::get($setting->getName(), 'packet_settings')->getValue()) {
                    continue;
                }
                ?>
            <h3 style="margin-bottom:5px;"><?= $setting->getTitle() ?></h3>
            <div>
                <div style="font-size: smaller; color:#999"><?= $setting->getDescription() ?></div>
                <?php
                        $thisName = 'settings[re-enrollment_packet][' . $setting->getName() . ']';
                        $thisID = 'core-setting-re-enrollment_packet-' . $setting->getName();
                        switch ($setting->getType()) {
                            case core_setting::TYPE_HTML:
                                ?>
                        <textarea name="<?= $thisName ?>" id="<?= $thisID ?>"><?= htmlentities($setting->getValue()) ?></textarea>
                        <script type="text/javascript">
                            CKEDITOR.replace('<?= $thisID ?>');
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
                            <option value="0" <?= $setting == '0' ? 'selected' : '' ?>>True</option>
                        </select>
                    <?php
                                break;
                            case core_setting::TYPE_INT:
                                ?>
                        <input class="form-control" type="text" name="<?= $thisName ?>" id="<?= $thisID ?>" value="<?= $setting->getValue() ?>" size="5">
                    <?php
                                break;
                        }
                        ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <br>
    <p><button class="btn btn-round btn-primary" type="submit">Save</button></p>
</form>
<?php
core_loader::printFooter();
?>