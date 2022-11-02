<?php

/**
 * interface with payment table
 *
 * @author abe
 */
abstract class pay_core implements core_payment
{

    protected $payment_id;
    protected $user_id;
    protected $amount;
    protected $class;
    protected $type;
    protected $parent_payment_id;
    protected $transaction_id;
    //only last four digits of the card_num are stored in database
    protected $card_num;
    protected $card_type;
    protected $first_name;
    protected $last_name;
    protected $address;
    protected $city;
    protected $state;
    protected $zip;
    protected $status;
    protected $message;
    protected $response;

    //not in database
    protected $card_cvv;
    protected $card_exp;
    protected $validationError;

    const CC_VISA = 'Visa';
    const CC_MASTER = 'MasterCard';
    const CC_DISCOVER = 'Discover Card';
    const CC_AMEX = 'American Express';

    protected $availableCardTypes = array(self::CC_VISA, self::CC_MASTER, self::CC_DISCOVER, self::CC_AMEX);

    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_AUTH_ERRER = 'AUTH ERROR';
    const STATUS_SERVER_ERROR = 'SERVER ERROR';

    const TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const TYPE_REFUND = 'REFUND';

    protected static $cache;

    public static function newPayment($class)
    {
        if (!class_exists($class)) {
            error_log('No such payment class: ' . $class);
            return false;
        }
        return new $class();
    }

    protected static function newRefund($class, pay_core $parentPayment)
    {
        if (!class_exists($class)) {
            error_log('No such payment class: ' . $class);
            return false;
        }
        $refund = new $class();
        /* @var $refund pay_core */
        $refund->setRefund();
        $refund->setParentPaymentID($parentPayment->getID());
        return $refund;
    }

    public function setRefund($isRefund = true)
    {
        $this->type = $isRefund ? self::TYPE_REFUND : self::TYPE_AUTH_CAPTURE;
    }

    public function isRefund()
    {
        return $this->type === self::TYPE_REFUND;
    }

    protected function save()
    {
        $db = new core_db();
        $success = $db->query(
            sprintf('INSERT INTO payment 
                (user_id, 
                amount, `class`, type, parent_payment_id, transaction_id, 
                card_num, card_type, 
                first_name, last_name, 
                address, city, `state`, zip, 
                status, message, response)
                VALUES
                (%d,
                %f, "%s", "%s", %d, "%s",
                "%s", "%s",
                "%s", "%s",
                "%s", "%s", "%s", "%s",
                "%s", "%s", "%s")',
                $this->user_id,
                $this->amount,
                get_class($this),
                $db->escape_string($this->type),
                $this->parent_payment_id,
                $db->escape_string($this->transaction_id),
                substr(self::sanitizeNumberStr($this->card_num), -4),
                $db->escape_string($this->card_type),
                $db->escape_string($this->first_name),
                $db->escape_string($this->last_name),
                $db->escape_string($this->address),
                $db->escape_string($this->city),
                $db->escape_string($this->state),
                $db->escape_string($this->zip),
                $db->escape_string($this->status),
                $db->escape_string($this->message),
                $db->escape_string($this->response)
            ));
        if ($success) {
            $this->payment_id = $db->insert_id;
            return $this->payment_id;
        }

        return FALSE;
    }

    public function getID()
    {
        return (int)$this->payment_id;
    }

    public function getUserID()
    {
        return (int)$this->user_id;
    }

    public function getAmount()
    {
        return (float)$this->amount;
    }

    public function getAmountMinusRefunds()
    {
        $refunds = $this->getChildren();
        $amount = $this->getAmount();
        foreach ($refunds as $refund) {
            /* @var $refund pay_core */
            if (!$refund->isRefund())
                continue;

            if (!$refund->isSuccessful())
                continue;

            $amount -= $refund->getAmount();
        }
        return $amount;
    }

    public function isSuccessful()
    {
        return $this->status == self::STATUS_SUCCESS;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMessage()
    {
        if ($this->message)
            return $this->message;

        return $this->validationError;
    }

    public function getCardNum()
    {
        return $this->card_num;
    }

    public function getCardType()
    {
        return $this->card_type;
    }

    public function getFullAddress($html = true)
    {
        if (empty($this->address))
            return '';
        $fullAddress = $this->address . "\n" .
            $this->city . ', ' . $this->state . ' ' . $this->zip;
        return ($html ? nl2br($fullAddress) : $fullAddress);
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getZip()
    {
        return $this->zip;
    }

    public function setCard($card_num, $card_exp = NULL, $card_cvv = NULL, $card_type = NULL)
    {
        $this->card_num = $card_num;
        $this->card_exp = $card_exp;
        $this->card_cvv = $card_cvv;
        $this->card_type = $card_type;
    }

    public function getTransactionID()
    {
        return $this->transaction_id;
    }

    public function setParentPaymentID($payment_id)
    {
        $this->parent_payment_id = (int)$payment_id;
    }

    /**
     *
     * @return pay_core
     */
    public function getParentPayment()
    {
        if (!$this->parent_payment_id)
            return false;
        return self::getPayment($this->parent_payment_id);
    }

    public function getParentPaymentID()
    {
        return (int)$this->parent_payment_id;
    }

    public function setAddress($address, $city, $state, $zip)
    {
        $this->address = htmlentities(strip_tags($address));
        $this->city = htmlentities(strip_tags($city));
        $this->state = substr(preg_replace('/[^a-zA-Z]/', '', $state), 0, 2);
        $this->zip = preg_replace('/[^0-9\-]/', '', $zip);
    }

    public function setNameOnCard($first_name, $last_name)
    {
        $this->first_name = strip_tags($first_name);
        $this->last_name = strip_tags($last_name);
    }

    public function getNameOnCard()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function setUser(core_user $user)
    {
        if (!$user)
            return false;

        $this->user_id = $user->getID();
        return true;
    }

    /**
     * This sanitizes and checks for any errors in the amount and card info before running the transaction
     * @return boolean
     */
    protected function validate()
    {

        //already been processed and saved
        if ($this->payment_id
            || $this->response
        ) {
            $this->validationError = 'Payment already processed';
            return false;
        }

        $this->amount = (float)preg_replace('/[^0-9\.]/', '', $this->amount);
        if ($this->amount <= 0) {
            $this->validationError = 'Invalid amount';
            return false;
        }

        $this->card_num = self::sanitizeNumberStr($this->card_num);

        if (!$this->card_num) {
            $this->validationError = 'Invalid card number';
            return false;
        }

        if ($this->type != self::TYPE_REFUND) {

            if (empty($this->type))
                $this->type = self::TYPE_AUTH_CAPTURE;

            if (date('Ym') > date('Ym', $this->card_exp)) {
                $this->validationError = 'Invalid exiration date';
                return false;
            }

            if ($this->card_cvv) {
                $this->card_cvv = self::sanitizeNumberStr($this->card_cvv);
                if (strlen($this->card_cvv) < 3 || strlen($this->card_cvv) > 4) {
                    $this->validationError = 'Invalid cvv code';
                    return false;
                }
            }


            $this->card_type = self::identifyCardNumber($this->card_num);

            if (!$this->card_type) {
                $this->validationError = 'Invalid card number';
                return false;
            }

            if (!self::validateCardNumber($this->card_num)) {
                $this->validationError = 'Invalid card number';
                return false;
            }
        } else {
            if ($this->getParentPayment()->isRefund()) {
                $this->validationError = 'Cannot refund a refund';
                return false;
            }

            if ($this->amount > $this->getParentPayment()->getAmountMinusRefunds()) {
                $this->validationError = 'Invalid refund amount';
                return false;
            }
        }

        return true;
    }


    public static function sanitizeNumberStr($numStr)
    {
        return preg_replace('/[^0-9]/', '', $numStr);
    }

    public static function identifyCardNumber($card_num)
    {

        if (preg_match('#^4(.{12}|.{15})$#', $card_num))
            return self::CC_VISA;

        if (preg_match('#^5[1-5].{14}$#', $card_num))
            return self::CC_MASTER;

        if (preg_match('#^6011.{12}$#', $card_num))
            return self::CC_DISCOVER;

        if (preg_match('#^3[4-7].{13}$#', $card_num))
            return self::CC_AMEX;


        return FALSE;
    }

    public static function validateCardNumber($card_num)
    {   // Implements the Luhn modulo 10 check on the supplied number
        $card_num = strrev($card_num);
        $NoDigits = strlen($card_num);
        $TestSum = 0;
        for ($Digit = 0; $Digit < $NoDigits; $Digit = $Digit + 2) {
            $thisDigit = @$card_num[$Digit + 1] * 2;
            if ($thisDigit >= 10)
                $thisDigit = $thisDigit - 9;
            $TestSum = $TestSum + (@$card_num[$Digit]) + $thisDigit;
        }
        if (floor($TestSum / 10) != ($TestSum / 10)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     *
     * @return array of pay_core objects
     */
    public function getChildren()
    {
        if (!isset(self::$cache['children'][$this->getID()])) {
            self::$cache['children'][$this->getID()] = array();
            $IDs = explode(',', core_db::runGetValue('SELECT GROUP_CONCAT(payment_id) FROM payment WHERE parent_payment_id=' . $this->getID()));
            foreach ($IDs as $id) {
                if (!$id)
                    continue;
                self::$cache['children'][$this->getID()][] = self::getPayment($id);
            }
        }
        return self::$cache['children'][$this->getID()];
    }


    /**
     *
     * @param int $payment_id
     * @return pay_core
     */
    public static function getPayment($payment_id)
    {
        if (!$payment_id)
            return false;

        if (!isset(self::$cache[$payment_id])) {
            $result = core_db::runQuery('SELECT * FROM payment WHERE payment_id=' . (int)$payment_id);
            if (!$result)
                return false;

            $class = $result->fetch_object()->class;
            $result->data_seek(0);
            self::$cache[$payment_id] = $result->fetch_object($class);
        }
        return self::$cache[$payment_id];
    }
}

?>
