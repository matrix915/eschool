<?php

/**
 * mth_canvas
 *
 * urls:
 * https://mytechhigh.instructure.com
 * https://mytechhigh.test.instructure.com
 *
 * @author abe
 */
class mth_canvas
{

    protected static $last_error;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    public static function token()
    {
        if (($token = core_setting::get('Token', 'Canvas'))) {
            return $token->getValue();
        } else {
            error_log('Canvas token not set, run mth_canvas::set_token() to set it.');
            return NULL;
        }
    }

    public static function set_token($tokenStr)
    {
        core_setting::set('Token', $tokenStr, core_setting::TYPE_TEXT, 'Canvas');
    }

    public static function url()
    {
        if (($url = core_setting::get('URL', 'Canvas'))) {
            return $url->getValue();
        } else {
            error_log('Canvas URL not set, run mth_canvas::set_url() to set it.');
            return NULL;
        }
    }

    public static function set_url($url)
    {
        core_setting::set('URL', $url, core_setting::TYPE_TEXT, 'Canvas');
    }

    public static function account_id($forceUpdate = false)
    {
        if (!($account_id = core_setting::get('AccountID', 'Canvas')) || $forceUpdate) {
            if (($accounts = self::exec('/accounts')) && isset($accounts[0]->id)) {
                $account_id = core_setting::set('AccountID', $accounts[0]->id, core_setting::TYPE_TEXT, 'Canvas');
            } else {
                mth_canvas_error::log('Account ID not found', '/accounts', $accounts);
                return NULL;
            }
        }
        return $account_id->getValue();
    }

    /**
     *
     * @param string $commandPath after /api/v1
     * @param array $postFields
     * @return stdClass|array on success
     */
    public static function exec($commandPath, ARRAY $postFields = array(), $method = NULL)
    {
        if (!self::url() || !self::token()) {
            return false;
        }

        if (is_null($method)) {
            $method = empty($postFields) ? self::METHOD_GET : self::METHOD_POST;
        }

        if (!($response = self::execCURL($commandPath, $postFields, $method))) {
            return false;
        }
        try {
            $responseObj = json_decode($response);
        } catch (Exception $e) {
            self::$last_error = mth_canvas_error::log(
                $e->getMessage(),
                $commandPath,
                $response);
        }

        if (isset($responseObj->errors)) {
            self::$last_error = mth_canvas_error::log(
                is_array($responseObj->errors) && isset($responseObj->errors[0]->message)
                    ? $responseObj->errors[0]->message
                    : 'Unknown Error',
                $commandPath,
                $responseObj,
                $postFields);
            return false;
        }

        return $responseObj;
    }

    protected static function execCURL($commandPath, ARRAY $postFields, $method)
    {
        $ch = curl_init(self::url() . '/api/v1' . $commandPath);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . self::token()));

        if ($method == self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ($method != self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (!empty($postFields) && $method != self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, ($method != self::METHOD_POST ? http_build_query($postFields) : $postFields));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);

        $error = curl_error($ch);
        if (!empty($error)) {
            self::$last_error = mth_canvas_error::log($error, $commandPath, array('curl error' => array(curl_errno($ch), $error)));
            return false;
        }
        return $response;
    }
}
