<?php
class mth_teacher extends mth_person
{
     protected $user_id;
     private static $_cache;

     public static function get(array $user_ids = [])
     {
          return core_db::runGetObjects(
               'SELECT * FROM core_users AS t
        INNER JOIN mth_person AS p ON p.user_id=t.user_id
          WHERE 1
          and level='.mth_user::L_TEACHER.'
        ' . ($user_ids ? 'AND u.user_id IN (' . implode(',', array_map('intval', $user_ids)) . ')' : ''),
               'mth_teacher'
          );
     }


      /**
     *
     * @param int $user_id
     * @return mth_parent
     */
    public static function getByUserID($user_id)
    {
        $cache = &self::$_cache['user_id'][$user_id];
        if (!isset($cache)) {
            $cache = core_db::runGetObject('SELECT * 
                                  FROM core_users AS u 
                                    INNER JOIN mth_person AS p ON p.user_id=u.user_id
                                  WHERE  level='.mth_user::L_TEACHER.' and u.user_id=' . (int)$user_id, 'mth_teacher');
            if ($cache) {
                self::$_cache['person_id'][$cache->getPersonID()] = $cache;
                self::$_cache['user_id'][$cache->getID()] = $cache;
            }
        }
        return $cache;
    }

     public function getUserID(){
          return $this->user_id;
     }
}
