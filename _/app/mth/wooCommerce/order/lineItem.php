<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of lineItem
 *
 * @author abe
 */
class mth_wooCommerce_order_lineItem
{
    protected $line_item;
    /**
     *
     * @var mth_wooCommerce_order
     */
    protected $order;

    public function __construct(mth_wooCommerce_order $order, $num)
    {
        $this->order = $order;
        $line_items = $this->order->rawLineItemsArray();
        if (isset($line_items[$num]) && isset($line_items[$num]->id)) {
            $this->line_item = $line_items[$num];
        }
    }

    public function id()
    {
        return (int)$this->line_item->id;
    }

    public function quantity()
    {
        return (int)$this->line_item->quantity;
    }

    public function sku()
    {
        return $this->line_item->sku;
    }

    public function order()
    {
        return $this->order;
    }

    /**
     *
     * @return mth_canvas_course
     */
    public function canvas_course()
    {
        return mth_canvas_course::getBySISID($this->sku());
    }
}
