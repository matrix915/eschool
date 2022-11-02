<?php
require ROOT . '/_/mth_includes/class-wc-api-client.php';

/**
 * For communicating with the WooCommerce REST API
 *
 * @author abe
 */
class mth_wooCommerce extends WC_API_Client
{

    public static function consumer_key()
    {
        if (($key = core_setting::get('consumer_key', __CLASS__))) {
            return $key->getValue();
        } else {
            error_log('wooCommerce consumer_key not set');
            return NULL;
        }
    }

    public static function consumer_secret()
    {
        if (($secret = core_setting::get('consumer_secret', __CLASS__))) {
            return $secret->getValue();
        } else {
            error_log('wooCommerce consumer_secret not set');
            return NULL;
        }
    }

    public static function store_url()
    {
        if (($url = core_setting::get('store_url', __CLASS__))) {
            return $url->getValue();
        } else {
            error_log('wooCommerce store_url not set');
            return NULL;
        }
    }

    public static function is_ssl()
    {
        if (($is_ssl = core_setting::get('is_ssl', __CLASS__))) {
            return $is_ssl->getValue();
        } else {
            error_log('wooCommerce is_ssl not set');
            return FALSE;
        }
    }

    public static function configure($consumer_key, $consumer_secret, $store_url, $is_ssl)
    {
        core_setting::set('consumer_key', $consumer_key, core_setting::TYPE_TEXT, __CLASS__);
        core_setting::set('consumer_secret', $consumer_secret, core_setting::TYPE_TEXT, __CLASS__);
        core_setting::set('store_url', $store_url, core_setting::TYPE_TEXT, __CLASS__);
        core_setting::set('is_ssl', $is_ssl, core_setting::TYPE_BOOL, __CLASS__);
    }

    public function __construct()
    {
        parent::__construct(self::consumer_key(), self::consumer_secret(), self::store_url(), self::is_ssl());
    }

    public static function isOnline()
    {
        $isOnline = &$_SESSION[core_config::getCoreSessionVar()][__CLASS__]['isOnline'];
        if (!$isOnline) {
            $wc = new mth_wooCommerce();
            $response = $wc->get_products_count();
            $isOnline = isset($response->count) ? 1 : 0;
        }
        return $isOnline;
    }
}
