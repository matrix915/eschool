<?php

/**
 * Authorize.net payment interface
 *
 * @author abe
 */
class pay_authnet extends pay_core
{

    protected $test = false;
    protected $dev = false;

    const AUTHNET_URL = 'https://secure.authorize.net/gateway/transact.dll';
    const AUTHNET_URL_DEV = 'https://test.authorize.net/gateway/transact.dll';

    public function __construct()
    {
        $this->test = @core_config::getVal('authnet_test');
        $this->dev = @core_config::getVal('authnet_dev');
    }

    public static function configVarsSet()
    {
        return core_config::getVal('authnet_login')
        && core_config::getVal('authnet_tran_key');
    }

    public function process($amount)
    {
        if (!self::configVarsSet()) {
            error_log('authnet_login and/or authnet_tran_key not set');
            return false;
        }

        $this->amount = $amount;

        if (!$this->validate())
            return false;

        if ($this->type == self::TYPE_REFUND)
            return $this->processRefund();

        return $this->processAuthCapture();
    }

    /**
     * creates processes and returns a pay_autnet object
     * @param float $amount
     * @return boolean|\pay_authnet
     */
    public function refund($amount)
    {
        $refund = self::newRefund(get_class(), $this);

        if (!$this->transaction_id) {
            $this->validationError = 'No transaction id found';
            return false;
        }

        $refund->setCard($this->card_num, NULL, NULL, $this->card_type);
        $refund->setNameOnCard($this->first_name, $this->last_name);

        $refund->process($amount);

        return $refund;
    }

    protected function processAuthCapture()
    {
        $authnet_values = array(
            'x_type' => 'AUTH_CAPTURE',
            'x_card_num' => $this->card_num,
            'x_exp_date' => date('m/y', $this->card_exp),
            'x_amount' => $this->amount,
            'x_test_request' => $this->test
        );

        if ($this->first_name) {
            $authnet_values['x_first_name'] = $this->first_name;
            $authnet_values['x_last_name'] = $this->last_name;
        }

        if ($this->address) {
            $authnet_values['x_address'] = $this->address;
            $authnet_values['x_city'] = $this->city;
            $authnet_values['x_state'] = $this->city;
            $authnet_values['x_zip'] = $this->zip;
        }

        if ($this->card_cvv) {
            $authnet_values['x_card_code'] = $this->card_cvv;
        }

        if (!$this->execCurl($authnet_values))
            return false;

        $responsArr = explode('|', $this->response);

        $this->status = $responsArr[0] == 1 ? self::STATUS_SUCCESS : self::STATUS_AUTH_ERRER;
        $this->message = $responsArr[3];
        $this->transaction_id = $responsArr[6];

        $this->save();

        return $this->isSuccessful();
    }

    protected function processRefund()
    {
        if (!$this->parent_payment_id) {
            error_log('Refund parent not set!');
            return false;
        }

        $authnet_values = array(
            'x_type' => 'CREDIT',
            'x_trans_id' => $this->getParentPayment()->getTransactionID(),
            'x_card_num' => $this->card_num,
            'x_amount' => $this->amount,
            'x_test_request' => $this->test
        );

        if ($this->first_name) {
            $authnet_values['x_first_name'] = $this->first_name;
            $authnet_values['x_last_name'] = $this->last_name;
        }

        if (!$this->execCurl($authnet_values))
            return false;

        $responsArr = explode('|', $this->response);

        $this->status = $responsArr[0] == 1 ? self::STATUS_SUCCESS : self::STATUS_AUTH_ERRER;
        $this->message = $responsArr[3];
        $this->transaction_id = $responsArr[6];

        $this->save();
    }

    protected function execCurl(ARRAY $authnet_values)
    {
        $standardFields = array(
            'x_login' => core_config::getVal('authnet_login'),
            'x_tran_key' => core_config::getVal('authnet_tran_key'),
            'x_version' => '3.1',
            'x_delim_char' => '|',
            'x_delim_data' => 'TRUE',
            'x_method' => 'CC',
            'x_relay_response' => 'FALSE'
        );

        $authnet_values = $standardFields + $authnet_values;

        $fields = array();
        foreach ($authnet_values as $key => $value)
            $fields[] = $key . '=' . urlencode($value);

        $ch = curl_init($this->dev ? self::AUTHNET_URL_DEV : self::AUTHNET_URL); // URL of gateway for cURL to post to

        curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $fields)); // use HTTP POST to send form data
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
        $this->response = curl_exec($ch); //execute post and get results
        if (!$this->response) {
            $this->message = curl_error($ch);
            $this->status = self::STATUS_SERVER_ERROR;
        }
        curl_close($ch);

        return (bool)($this->response);
    }
}
