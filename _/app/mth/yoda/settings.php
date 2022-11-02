<?php
namespace mth\yoda;
use core\Database\PdoAdapterInterface;
use core\Injectable;

class settings{
    public static function url()
    {
        if (($url = \core_setting::get('URL', 'Yoda'))) {
            return $url->getValue();
        } else {
            error_log('Yoda URL not set, run settings::set_url() to set it.');
            return NULL;
        }
    }

    public static function set_url($url)
    {
        \core_setting::set('URL', $url, \core_setting::TYPE_TEXT, 'Yoda');
    }

    public static function setParentLink($url){
        \core_setting::set('ParentLink', $url, \core_setting::TYPE_TEXT, 'Yoda');
    }

    public static function getParentLink()
    {
        if (($url = \core_setting::get('ParentLink', 'Yoda'))) {
            return $url->getValue();
        } else {
            error_log('ParentLink is not set.');
            return '#';
        }
    }
}