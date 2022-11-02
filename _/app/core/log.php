<?php

/**
 * Created by PhpStorm.
 * User: abe
 * Date: 10/14/16
 * Time: 1:18 PM
 */
class core_log
{
    const MAX_DAYS_LIFE = 30;

    public static function log($message,$tag=null){
        core_db::runQuery('DELETE FROM core_log 
            WHERE `datetime`<"'.date('Y-m-d H:i:s',strtotime('-'.self::MAX_DAYS_LIFE.' days')).'"');
        return core_db::runQuery('INSERT INTO core_log (`datetime`,tag,content) 
                VALUES ("'.date('Y-m-d H:i:s').'",
                    '.($tag?'"'.core_db::escape($tag).'"':'NULL').',
                    "'.core_db::escape($message).'")');
    }
}