<?php

/**
 * mth_sparkLog
 *
 * @author 
 */
class mth_sparkLog extends core_model
{
     protected $id;
     protected $url;
     protected $enroll_data;
     protected $spark_res;
     protected $created_at;
     protected $updated_at;

     public static function save($url, $enroll_data, $spark_res, $status)
     {
          $success = core_db::runQuery("INSERT INTO mth_spark_log (`url`, `enroll_data`, `spark_res`, `status`) values ('$url', '$enroll_data', '$spark_res', '$status')");
          return $success;
     }
}
