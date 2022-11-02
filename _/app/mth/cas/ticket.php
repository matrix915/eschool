<?php

/**
 * Description of ticket
 *
 * @author abe
 */
class mth_cas_ticket
{
    protected $ticket_str;
    protected $user_id;
    protected $service_url;
    protected $date;

    protected static $cache = array();

    const validServiceHostPattern = '/instructure\.com$/';

    const VALIDATE_VALID = 1;
    const VALIDATE_INVALID_TICKET = 2;
    const VALIDATE_INVALID_SERVICE = 3;

    public static function validateService($service_url)
    {
        $urlArr = parse_url($service_url);
        if (empty($urlArr['host']) || !preg_match(self::validServiceHostPattern, $urlArr['host'])) {
            return false;
        }
        return true;
    }

    public static function newTicket($service_url)
    {
        if (!core_user::getUserID() || !self::validateService($service_url)) {
            return false;
        }
        self::cleanUp();
        $ticket_str = 'ST-' .
            core_user::encodePass(
                core_user::getUserID() .
                core_user::getUserEmail() .
                uniqid('cas-')
            );
        core_db::runQuery(sprintf('INSERT INTO mth_cas_ticket (ticket_str, user_id, service_url, `date`)
                        VALUES ("%s",%d,"%s",NOW())',
            core_db::escape($ticket_str),
            core_user::getUserID(),
            core_db::escape($service_url)));
        return $ticket_str;
    }

    public static function validateTicket($ticket_str, $service_url)
    {
        if (!($ticket = self::get($ticket_str))) {
            return self::VALIDATE_INVALID_TICKET;
        } elseif ($ticket->service_url != $service_url) {
            core_db::runQuery('DELETE FROM mth_cas_ticket 
                          WHERE ticket_str="' . core_db::escape($ticket_str) . '"');
            unset(self::$cache['get'][$ticket_str]);
            return self::VALIDATE_INVALID_SERVICE;
        }
        return self::VALIDATE_VALID;
    }

    public static function userIdentifier($ticket_str)
    {
        if (isset(self::$cache['get'][$ticket_str]) //will only exsist if it was validated
            && ($ticket = self::get($ticket_str))
            && ($user = core_user::getUserById($ticket->user_id))
        ) {
            return $user->getEmail();
        }
        return false;
    }

    /**
     *
     * @param string $ticket_str
     * @return mth_cas_ticket
     */
    protected static function get($ticket_str)
    {
        $ticket = &self::$cache['get'][$ticket_str];
        if ($ticket === NULL) {
            $ticket = core_db::runGetObject('SELECT * FROM mth_cas_ticket 
                                        WHERE ticket_str="' . core_db::escape($ticket_str) . '"',
                'mth_cas_ticket');
        }
        return $ticket;
    }

    protected static function cleanUp()
    {
        return core_db::runQuery('DELETE FROM mth_cas_ticket 
                              WHERE `date`<"' . date('Y-m-d', strtotime('-30 days')) . '"');
    }
}
