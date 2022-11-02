<?php

function getContentEditLink(cms_content $content, core_path $pagePath, $createNew = false, $destination = '')
{
    if (!core_user::isUserAdmin()) {
        return '';
    }
    return 'showContentEditForm(' . $content->getID() . ', \'' . $pagePath . '\', ' . ((int)(bool)$createNew) . ', \'' . $destination . '\')';
}