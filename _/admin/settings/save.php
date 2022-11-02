<?php
if (!isset($_SESSION['core-settings-edit-forms']))
    $_SESSION['core-settings-edit-forms'] = array();

if (!empty($_GET['core-settings-edit-form'])) {
    if (in_array($_GET['core-settings-edit-form'], $_SESSION['core-settings-edit-forms']))
        exit();
    $_SESSION['core-settings-edit-forms'][] = $_GET['core-settings-edit-form'];
    
    $ses = new mth\aws\ses();

    foreach ($_POST['settings'] as $category => $settingArr) {
        foreach ($settingArr as $name => $value) {
            if (($setting = core_setting::get($name, ($category === 'NONE' ? '' : $category)))
                && $setting->getValue() != $value
            ) {
               if($setting->getType() === $setting::TYPE_TEXT && cms_content::checkForbiddenCharacters($value)){
                  return core_notify::addError('Invalid characters in subject.');
               }
                if(core_user::getCurrentUser()){
                    $log = new mth_system_log();
                    $log->setNewValue($value,$setting->getType());
                    $log->setOldValue( $setting->getValue(),$setting->getType());
                    $log->setType($setting->getType());
                    $log->setTag('core_settings-'.$category);
                    $log->setUserId(core_user::getCurrentUser()->getID());
                    $log->save();
                }

                $setting->update($value);
               
                if($category == 'EmailVerification'){
                    $prefix = str_replace(['content','subject'],'',$setting->getName());
                    $param = substr($setting->getName(),strlen($prefix));
                    
               
                    
                    switch($param){
                        case 'subject':
                            if(!$ses->updateCustomVerification($prefix,['TemplateSubject'=>$value])){
                                core_notify::addError('Failed to save Subject changes on SES');
                            }
                            break;
                        case 'content':
                            if(!$ses->updateCustomVerification($prefix,['TemplateContent'=>$value])){
                                core_notify::addError('Failed to save Content changes on SES');
                            }
                            break;
                    }
                }
            }
        }
    }
    core_notify::addMessage('Settings saved');
    core_loader::redirect();
}