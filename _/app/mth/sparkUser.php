<?php

/**
 * mth_sparkUser
 *
 * @author 
 */
class mth_sparkUser extends core_model
{
     protected $spark_user_id;
     protected $person_id;
     protected $first_name;
     protected $last_name;
     protected $mth_email;
     protected $to_be_pushed;
     protected $is_delete;

     public static function save($spark_user_id, $person_id, $first_name, $last_name, $mth_email, $to_be_pushed = 1)
     {
          $is_exist = core_db::runGetObject('SELECT * FROM mth_spark_user WHERE spark_user_id=' . (int) $spark_user_id);
          if(!$is_exist){
               $success = core_db::runQuery("INSERT INTO mth_spark_user (`spark_user_id`, `person_id`, `first_name`, `last_name`, `mth_email`, `to_be_pushed`) 
               values ('$spark_user_id', '$person_id', '$first_name', '$last_name', '$mth_email', '$to_be_pushed')");
          }else{
               $success = core_db::runQuery("UPDATE mth_spark_user SET is_delete = 0 WHERE spark_user_id = $spark_user_id");
          }
         
          return $success;
     }

     public static function update($spark_user_id, $person_id, $first_name, $last_name, $mth_email, $to_be_pushed = 1)
     {
          $success = core_db::runQuery(
               "UPDATE mth_spark_user SET person_id='$person_id', first_name='$first_name',last_name='$last_name',mth_email ='$mth_email', to_be_pushed='$to_be_pushed'
               WHERE spark_user_id = " . $spark_user_id
          );
          return $success;
     }

     public static function find_all(){
          $course = core_db::runGetObjects('SELECT * FROM mth_spark_user WHERE is_delete = 0');
          return $course;
     }

     /**
      *
      * @param key $spark_user_id
      * @return sparkUser
      */
     public static function getByID($spark_user_id)
     {
          $course = core_db::runGetObject('SELECT * FROM mth_spark_user WHERE spark_user_id=' . (int) $spark_user_id, 'mth_spark_user');
          return $course;
     }

     public static function getByPersonID($person_id)
     {
          $course = core_db::runGetObject("SELECT * FROM mth_spark_user WHERE `person_id`= '$person_id' ");
          return $course;
     }

     public static function deleteByIds($ids){
          $success = core_db::runQuery(
               "UPDATE mth_spark_user SET is_delete = 1 WHERE spark_user_id IN ($ids)"
          );
          return $success;
     }

     public static function getByEmail($email)
     {
          $course = core_db::runGetObject("SELECT * FROM mth_spark_user WHERE `mth_email`= '$email' ");
          return $course;
     }
}
