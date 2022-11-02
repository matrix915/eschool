<?php

/**
 * mth_sparkSetting
 *
 * @author 
 */
class mth_sparkSetting extends core_model
{
     protected $id;
     protected $key;
     protected $value;
     protected $school_year_id;

     public function save($key, $value, $school_year_id)
     {
          $success = core_db::runQuery("INSERT INTO mth_spark_setting (`key`, `value`, `school_year_id`) values ('$key', '$value', '$school_year_id')");
          return $success;
     }

     public function update($key, $value, $school_year_id)
     {
          $success = core_db::runQuery("UPDATE mth_spark_setting SET `value` = '$value' WHERE school_year_id = $school_year_id AND `key` = '$key'");
          return $success;
     }

     public static function cron_test($school_year_id, $txt = "testing")
     {
          date_default_timezone_set('US/Mountain');
          $date_test = date('Y-m-d H:i:s');
          self::save($txt, $date_test, $school_year_id);
     }

     public static function saveSemDate($first_sem_start, $second_sem_start, $sem_end, $school_year_id)
     {
          $first_ob = self::getByKey('first_sem_start', $school_year_id);
          if ($first_ob) {
               self::update('first_sem_start', $first_sem_start, $school_year_id);
          } else {
               self::save('first_sem_start', $first_sem_start, $school_year_id);
          }

          $second_ob = self::getByKey('second_sem_start', $school_year_id);
          if ($second_ob) {
               self::update('second_sem_start', $second_sem_start, $school_year_id);
          } else {
               self::save('second_sem_start', $second_sem_start, $school_year_id);
          }

          $sem_end_ob = self::getByKey('sem_end', $school_year_id);
          if ($sem_end_ob) {
               self::update('sem_end', $sem_end, $school_year_id);
          } else {
               self::save('sem_end', $sem_end, $school_year_id);
          }
          return true;
     }

     /**
      *
      * @param key $string
      * @return settingObject
      */
     public static function getByKey($key, $school_year_id)
     {
          $course = core_db::runGetObject("SELECT * FROM mth_spark_setting WHERE school_year_id = $school_year_id AND `key` = '$key'");
          return $course;
     }
}
