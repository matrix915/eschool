<?php

/**
 * Mustang Secure Class
 */
class mustang_secure
{
  const login_endpoint = 'login';
  const token_variable = 'MUSTANG_TOKEN';

  public static function getTokenName()
  {
    return STATE . '_' . self::token_variable;
  }

  public static function endPoint($endpoint)
  {
    return MUSTANG_API_URI . "/$endpoint";
  }

  /**
   * REST request for mustang API login 
   * @param string $email
   * @param string $password
   * @return object
   */
  public static function login($email, $password)
  {
    $url = self::endPoint(self::login_endpoint);
    $data = array('email' => $email, 'password' => $password);
    $data_string = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
      )
    );

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }

  public static function addCookie($token)
  {
    setcookie(self::getTokenName(), $token, (int) time() + 172800, '/', MUSTANG_COOKIE_DOMAIN);
  }

  public static function deleteCookie()
  {
    // empty value and expiration one hour before
    setcookie(self::getTokenName(), '', (int) time() - 3600, '/', MUSTANG_COOKIE_DOMAIN);
  }
}
