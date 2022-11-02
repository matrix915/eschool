<?php

/**
 * sanitize request values
 *
 * @author abe
 */
class req_sanitize
{

    public static function int($value)
    {
        return (int)preg_replace('/[^\-0-9]/', '', $value);
    }

    public static function float($value)
    {
        return (float)preg_replace('/[^\-0-9\.]/', '', $value);
    }

    public static function txt($value)
    {
        return trim(htmlentities(str_replace(array("\n", "\r", "\t"), '', strip_tags($value)), ENT_QUOTES | ENT_HTML401, "UTF-8", false));
    }

    public static function multi_txt($value)
    {
        return trim(htmlentities(strip_tags($value), ENT_QUOTES | ENT_HTML401, "UTF-8", false));
    }

    /**
     * decode the html entities for expont in non-html environments
     * @param string $value
     * @return string
     */
    public static function txt_decode($value)
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML401, "UTF-8");
    }

    /**
     * Convert any string to UTF-8 without knowing the original character set
     *
     * @param [type] $text
     * @return void
     */
    public static function txt_utf($text){
        return iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8//IGNORE", $text);
    }

    public static function html($value)
    {
        if (trim($value) == '') {
            return '';
        }
        include_once ROOT . '/_/includes/HTMLPurifier/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.TargetBlank',true);
        $htmlPurifier = new HTMLPurifier($config);
        return $htmlPurifier->purify($value);
    }

    public static function url($value, $localOnly = true)
    {
        $parts = parse_url($value);
        if ((isset($parts['scheme']) && $parts['scheme'] == 'javascript') || $localOnly) {
            unset($parts['scheme'], $parts['host']);
        }
        if (isset($parts['query'])) {
            parse_str($parts['query'], $qVars);
            $parts['query'] = http_build_query($qVars);
        }
        return (isset($parts['scheme']) ? $parts['scheme'] . '://' : '') .
        (isset($parts['host']) ? $parts['host'] : '') .
        (isset($parts['port']) ? ':' . $parts['port'] : '') .
        (isset($parts['path']) ? str_replace('%2F', '/', urlencode($parts['path'])) : '/') .
        (isset($parts['query']) ? '?' . $parts['query'] : '') .
        (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    public static function urlencode($value)
    {
        return urlencode($value);
    }

   /**
    * Check string for forbiddent characters.
    *Excludes alphanumeric, spaces, and punctuations.
    *
    * @param string $value
    * @return bool
    */
    public static function containsForbiddenCharacters($value)
    {
       $regexString = '/[^[:print:]]/';
        return preg_match($regexString, $value);
    }
}
