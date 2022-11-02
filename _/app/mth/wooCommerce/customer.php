<?php

/**
 * Description of customer
 *
 * @author abe
 */
class mth_wooCommerce_customer
{
    protected $customer;

    protected static $cache;

    public function __construct($customerObj)
    {
        if (isset($customerObj->customer->id)) {
            $this->customer = $customerObj->customer;
        } elseif (isset($customerObj->id)) {
            $this->customer = $customerObj;
        } elseif (isset($customerObj->errors)) {
            error_log('WooCommerce Customer Errors: ' . print_r($customerObj->errors, true));
        }
    }

    /**
     *
     * @param type $customer_id
     * @return \mth_wooCommerce_customer
     */
    public static function get($customer_id)
    {
        $customer_id = intval($customer_id);
        $customer = &self::$cache['customer'][$customer_id];
        if (!isset($customer)) {
            $wc = new mth_wooCommerce();
            $customer = new mth_wooCommerce_customer($wc->get_customer($customer_id));
        }
        return $customer;
    }


    public function id()
    {
        if (!isset($this->customer->id)) {
            return NULL;
        }
        return $this->customer->id;
    }

    public function email()
    {
        if (!isset($this->customer->email)) {
            return NULL;
        }
        return $this->customer->email;
    }

    public function last_order_id()
    {
        if (!isset($this->customer->last_order_id)) {
            return NULL;
        }
        return $this->customer->last_order_id;
    }

    public function first_name()
    {
        if (!isset($this->customer->billing_address->first_name)) {
            return NULL;
        }
        return $this->customer->billing_address->first_name;
    }

    public function last_name()
    {
        if (!isset($this->customer->billing_address->last_name)) {
            return NULL;
        }
        return $this->customer->billing_address->last_name;
    }

    public function address_1()
    {
        if (!isset($this->customer->billing_address->address_1)) {
            return NULL;
        }
        return $this->customer->billing_address->address_1;
    }

    public function address_2()
    {
        if (!isset($this->customer->billing_address->address_2)) {
            return NULL;
        }
        return $this->customer->billing_address->address_2;
    }

    public function city()
    {
        if (!isset($this->customer->billing_address->city)) {
            return NULL;
        }
        return $this->customer->billing_address->city;
    }

    public function state()
    {
        if (!isset($this->customer->billing_address->state)) {
            return NULL;
        }
        return $this->customer->billing_address->state;
    }

    public function postcode()
    {
        if (!isset($this->customer->billing_address->postcode)) {
            return NULL;
        }
        return $this->customer->billing_address->postcode;
    }

    public function phone()
    {
        if (!isset($this->customer->billing_address->phone)) {
            return NULL;
        }
        return $this->customer->billing_address->phone;
    }

    public function mth_parent()
    {
        if ((($parent_id = mth_purchasedCourse::getParentIDfromCustomerID($this->id()))
                && ($parent = mth_parent::getByParentID($parent_id)))
            || ($parent = mth_parent::getByEmail($this->email()))
        ) {
            return $parent;
        }
        $parent = mth_parent::create();
        $parent->setEmail($this->email());
        $parent->setName($this->first_name(), $this->last_name());
        $parent->saveChanges();
        $phone = mth_phone::create($parent);
        $phone->setName('Cell');
        $phone->setNumber($this->phone());
        $phone->save();
        return $parent;
    }

    public function setAddressFromOrder()
    {
        $parent = $this->mth_parent();
        if (!($address = $parent->getAddress())) {
            $address = mth_address::create($parent);
        }
        return $address->saveForm(array(
            'street' => $this->address_1(),
            'street2' => $this->address_2(),
            'city' => $this->city(),
            'state' => $this->state(),
            'zip' => $this->postcode()
        ));
    }
}
