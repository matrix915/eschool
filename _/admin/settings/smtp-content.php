<?php
if(isset($_GET['test'])){
    $email = new core_emailservice();
    return $email->send(
        ['crestelitoc@codev.com'],
        'Test Subject',
        'Test Content',
    );
    exit;
}

if(isset($_GET['ms'])){
    $email = new core_emailservice();
    if($email->send(['rexc@codev.com'],  'Test Subject', 'Test Content')){
        echo 'SENT';
    }else{
        echo 'Error Sending';
    }
   
    exit;
}

require_once(dirname(__FILE__) . '/save.php');


core_loader::includeCKEditor();
cms_page::setPageTitle('Settings');
core_loader::printHeader('admin');
$category = 'SMTP';
?>
<script>
    CKEDITOR.config.removePlugins = "image,forms,youtube,iframe,print,stylescombo,table,tabletools,undo,bidi";
</script>
<div class="nav-tabs-horizontal nav-tabs-inverse">
<?php 
        $current_header = 'smtp';
        include core_config::getSitePath(). "/_/admin/settings/header.php";
    ?>
<div class="tab-content p-20 higlight-links">
    <form method="post" action="?core-settings-edit-form=<?= time() ?>">
            

            <?php foreach (core_setting::getCategorySettings($category) as $setting): /* @var $setting core_setting */ ?>
                <h3 style="margin-bottom:5px;"><?= $setting->getTitle() ?></h3>

                <div>
                    <div style="font-size: smaller; color:#999"><?= $setting->getDescription() ?></div>
                    <?php
                    $thisName = 'settings['.$category .'][' . $setting->getName() . ']';
                    $thisID = 'core-setting-'.$category .'-' . $setting->getName();
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
                        <input class="form-control" type="text" name="<?= $thisName ?>" id="<?= $thisID ?>" value="<?= $setting->getValue() ?>">
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
                            <input class="form-control" type="text" name="<?= $thisName ?>" id="<?= $thisID ?>"
                                value="<?= $setting->getValue() ?>" size="5">
                            <?php
                            break;
                        }
                        ?>
                </div>
            <?php endforeach; ?>
            <br>
            <p><button class="btn btn-round btn-primary"  type="submit">Save</button> 
            <button class="btn btn-warning btn-primary" id="test" type="button" >Test SMTP</button>
            <button class="btn btn-warning btn-primary" id="test1" type="button" >Test Email Service</button>
        </p>
    </form>
</div> 
</div>

<?php
core_loader::printFooter('admin');
?>
<script>
    $(function(){
        $('#test').click(function(){
            $.ajax({
                url: '?test=1&admin=1',
                type:'GET',
                success: function(response){
                    alert(response);
                }
            });
            return false;
        });

        $('#test1').click(function(){
            $.ajax({
                url: '?ms=1&admin=1',
                type:'GET',
                success: function(response){
                    alert(response);
                }
            });
            return false;
        });
    });
</script>