<?php

/**
 *
 * @author abe
 */
interface core_payment
{
    /**
     *
     * @param string $class
     * @return pay_core
     */
    public static function newPayment($class);

    /**
     * @param float $amount
     * @param string $card_num
     * @param int $card_exp timestamp
     * @param string $card_cvv
     * @param string $firstname
     * @param string $lastname
     * @param int $user_id
     * @return bool
     */
    public function process($amount);

    /**
     *
     * @param float $amount
     * @return pay_core
     */
    public function refund($amount);

    public function setCard($card_num, $card_exp = NULL, $card_cvv = NULL, $card_type = NULL);

    public function setAddress($address, $city, $state, $zip);

    public function setNameOnCard($first_name, $last_name);

    public function setUser(core_user $user);

    public function getMessage();

    public function isSuccessful();

    /**
     * @param int $payment_id
     * @return pay_core
     */
    public static function getPayment($payment_id);
}

