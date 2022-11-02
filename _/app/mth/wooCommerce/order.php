<?php

/**
 * Description of order
 *
 * @author abe
 */
class mth_wooCommerce_order
{
    protected $order;

    protected $onLineItem = 0;

    protected static $cache = array();

    protected function __construct($orderObj)
    {
        if (isset($orderObj->order->id)) {
            $this->order = $orderObj->order;
        } elseif (isset($orderObj->id)) {
            $this->order = $orderObj;
        } elseif (isset($orderObj->errors)) {
            error_log('WooCommerce Order Errors: ' . print_r($orderObj->errors, true));
        }
    }

    public function id()
    {
        if (!isset($this->order->id)) {
            return NULL;
        }
        return $this->order->id;
    }

    public function hash()
    {
        if (!isset($this->order->id, $this->order->billing_address->email,
            $this->order->billing_address->phone, $this->order->customer_user_agent)
        ) {
            return NULL;
        }
        return $this->order->id . '-' . md5($this->order->id . $this->order->billing_address->email .
            $this->order->billing_address->phone . $this->order->customer_user_agent);
    }

    public function created_at($format = null)
    {
        return core_model::getDate($this->order->created_at, $format);
    }

    /**
     *
     * @param int $order_id
     * @return mth_wooCommerce_order
     */
    public static function get($order_id)
    {
        $order_id = intval($order_id);
        $order = &self::$cache['order'][$order_id];
        if (!isset($order)) {
            $wc = new mth_wooCommerce();
            $order = new mth_wooCommerce_order($wc->get_order($order_id));
        }
        return $order;
    }

    public static function get_by_hash($orderHash)
    {
        if (!$orderHash) {
            return false;
        }
        $orderVars = explode('-', $orderHash);
        if (($order = self::get($orderVars[0]))
            && $orderHash == $order->hash()
        ) {
            return $order;
        }
    }

    public function customer()
    {
        return new mth_wooCommerce_customer($this->order->customer);
    }

    public function mth_parent()
    {
        if (($customer = $this->customer())) {
            return $customer->mth_parent();
        }
    }

    /**
     *
     * @param bool $reset
     * @return \mth_wooCommerce_order_lineItem
     */
    public function eachLineItem($reset = false)
    {
        if (!$reset && isset($this->order->line_items[$this->onLineItem])
            && $line_item = new mth_wooCommerce_order_lineItem($this, $this->onLineItem)
        ) {
            $this->onLineItem++;
            return $line_item;
        }
        $this->onLineItem = 0;
        return NULL;
    }

    public function rawLineItemsArray()
    {
        return $this->order->line_items;
    }
}
