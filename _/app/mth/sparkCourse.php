<?php

/**
 * mth_sparkUser
 *
 * @author 
 */
class mth_sparkCourse extends core_model
{
     protected $id;
     protected $spark_course_id;
     protected $spark_user_id;
     protected $spark_course_name;
     protected $enrolled_on;
     protected $semester;
     protected $school_year_id;
     protected $is_completed;
     protected $course_type;
     protected $mth_course_id;
     protected $period;
     protected $is_delete;

     // public static function save($spark_course_id, $spark_user_id, $spark_course_name, $enrolledOn)
     // {
     //      $current_school_year = mth_schoolYear::getCurrent();
     //      $current_year_id = $current_school_year->getID();

     //      $success = core_db::runQuery("INSERT INTO mth_spark_course (`spark_course_id`,  `spark_user_id`, `spark_course_name`, `enrolledOn`, `seme`) 
     //      values ('$spark_course_id', '$spark_user_id', '$spark_course_name', '$enrolledOn')");
     //      return $success;
     // }

     public static function bulkSave($query)
     {
          $success = core_db::runQuery("INSERT INTO mth_spark_course (`spark_course_id`,  `spark_user_id`, `spark_course_name`, `enrolled_on`, `semester`, `school_year_id`, `course_type`, `mth_course_id`, `period`) 
          values $query");
          return $success;
     }

     public static function bulkDelete($remove_query)
     {
          // $success = core_db::runQuery("DELETE FROM mth_spark_course WHERE $remove_query");
          $success = core_db::runQuery("UPDATE mth_spark_course SET is_delete = 1 WHERE $remove_query");
          return $success;
     }

     public static function bulkMidDelete($remove_query)
     {
          // $success = core_db::runQuery("DELETE FROM mth_spark_course WHERE $remove_query");
          $success = core_db::runQuery("UPDATE mth_spark_course SET is_delete = 2 WHERE $remove_query");
          return $success;
     }

     

     public static function getCourseByUserID($spark_user_id, $semester)
     {
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();
          $courses = core_db::runGetObjects('SELECT * FROM mth_spark_course WHERE is_completed = 0 AND is_delete = 0 AND school_year_id = ' . $current_year_id . ' AND spark_user_id=' . (int) $spark_user_id . ' and semester = ' . (int) $semester);
          return $courses;
     }

     public static function getByInfo($person_id, $period, $course_type, $mth_course_id, $semester){
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();
          $course = core_db::runGetObject(
               "SELECT * FROM mth_spark_course course
               LEFT JOIN mth_spark_user users ON users.spark_user_id = course.spark_user_id
               WHERE course.is_completed = 0 AND course.is_delete = 0 AND course.school_year_id = $current_year_id AND course.semester = $semester
               AND course.period = $period AND users.person_id = $person_id AND course.course_type = '$course_type' AND course.mth_course_id =$mth_course_id");
          return $course;
     }

     // public static function update($spark_course_id, $mth_course_id, $spark_user_id, $enrolledOn)
     // {
     //      $success = core_db::runQuery(
     //           "UPDATE mth_spark_course SET mth_course_id='$mth_course_id', spark_user_id='$spark_user_id',enrolledOn='$enrolledOn'
     //           WHERE spark_course_id = " . $spark_course_id
     //      );
     //      return $success;
     // }

     public static function findBySemester($semester)
     {
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();

          $courses = core_db::runGetObjects("SELECT * FROM mth_spark_course course
           LEFT JOIN mth_spark_user spark_user ON spark_user.spark_user_id = course.spark_user_id 
           WHERE course.semester = $semester AND course.is_completed = 0 AND course.is_delete = 0 AND course.school_year_id = $current_year_id");
          return $courses;
     }

     public static function findAllSemester($semester)
     {
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();

          if($semester != -1){
               $courses = core_db::runGetObjects("SELECT *, course.is_delete as course_deleted FROM mth_spark_course course
               LEFT JOIN mth_spark_user spark_user ON spark_user.spark_user_id = course.spark_user_id 
               WHERE course.semester = $semester AND course.school_year_id = $current_year_id AND is_completed = 0 AND course.is_delete = 0");
          }else{
               $courses = core_db::runGetObjects("SELECT *, course.is_delete as course_deleted FROM mth_spark_course course
               LEFT JOIN mth_spark_user spark_user ON spark_user.spark_user_id = course.spark_user_id 
               WHERE course.school_year_id = $current_year_id AND is_completed = 0 AND course.is_delete = 0");
          }
          return $courses;
     }

     public static function findHistorySemester($semester)
     {
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();

          $courses = core_db::runGetObjects("SELECT *, course.is_delete as course_deleted FROM mth_spark_course course
           LEFT JOIN mth_spark_user spark_user ON spark_user.spark_user_id = course.spark_user_id 
           WHERE course.semester = $semester AND course.school_year_id = $current_year_id AND is_completed = 0 AND course.is_delete = 0");
          return $courses;
     }

     /**
      *
      * @param key $spark_course_id
      * @return sparkCourse
      */
     public static function getByID($spark_course_id)
     {
          $course = core_db::runGetObject('SELECT * FROM mth_spark_course WHERE spark_course_id=' . (int) $spark_course_id, 'mth_spark_course');
          return $course;
     }

     public static function find_incompleted_courses()
     {
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();
          $courses = core_db::runGetObjects("SELECT * FROM mth_spark_course WHERE school_year_id= $current_year_id AND is_completed = 0 ");
          return $courses;
     }

     public static function mark_complete($spark_course_id, $spark_user_ids)
     {
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();

          $success = core_db::runQuery(
               "UPDATE mth_spark_course SET is_completed= 1
               WHERE school_year_id= $current_year_id AND spark_course_id = $spark_course_id AND spark_user_id IN ($spark_user_ids)"
          );
          return $success;
     }

     public static function getSparkCourseCount($mth_course_id, $type, $semester){
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();

          $courses = core_db::runGetObjects("SELECT * FROM mth_spark_course WHERE semester = $semester AND mth_course_id = $mth_course_id AND course_type = '$type' AND school_year_id= $current_year_id AND is_delete != 1 AND is_completed = 0");
          return $courses;
     }

     public static function removed_course(){
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();

          $courses = core_db::runGetObjects("SELECT * FROM mth_spark_course WHERE school_year_id= $current_year_id AND is_delete = 1 AND is_completed = 0");
          return $courses;
     }

     public static function get_enrolled_courses(){
          $current_school_year = mth_schoolYear::getCurrent();
          $current_year_id = $current_school_year->getID();

          $courses = core_db::runGetObjects("SELECT spark_course_id FROM mth_spark_course WHERE school_year_id = $current_year_id GROUP BY spark_course_id");
          return $courses;
     }

     
}
