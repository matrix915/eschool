<?php
/**
 *  PLG is only applicable for PLG activated HOMEROOMS
 *  which is initiated from importing PLG from the yoda course admin page
 */
namespace mth\yoda;

use mth_schoolYear;
use core_db;

class plgs
{
     protected $plg_id;
     protected $grade_level;
     protected $plg_name;
     protected $school_year_id;
     protected $subject;
     protected $plg_type;

     protected static $cache = array();
     protected $updateQueries = array();
     protected $insertQueries = array();

     CONST PLG_TYPE_DEPENDENT = 1;
     CONST PLG_TYPE_INDEPENDENT = 2;

     public function getID()
     {
          return $this->plg_id;
     }

     public function getGradeLevel()
     {
          return $this->grade_level;
     }

     public function getName()
     {
          return $this->plg_name;
     }

     public function getSchoolYearID()
     {
          return $this->school_year_id;
     }

     public function getSubject()
     {
          return $this->subject;
     }

     public function getType(){
       return $this->plg_type;
     }

     public function set($field, $value)
     {
          if (is_null($value)) {
               $this->updateQueries[$field] = '`' . $field . '`=NULL';
          } else {
               $this->updateQueries[$field] = '`' . $field . '`="' . \core_db::escape($value) . '"';
          }
          return $this;
     }

     public function setInsert($field, $value)
     {
          $this->{$field} = $value;
          if (is_null($value)) {
               $this->insertQueries[$field] = 'NULL';
          } else {
               $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
          }
          return $this;
     }

     public function save()
     {
          if (!empty($this->updateQueries)) {
               $success = \core_db::runQuery('UPDATE yoda_plgs SET ' . implode(',', $this->updateQueries) . ' WHERE plg_id=' . $this->getID());
          } else {
               $success = \core_db::runQuery('INSERT INTO yoda_plgs(' . implode(',', array_keys($this->insertQueries)) . ') VALUES(' . implode(',', $this->insertQueries) . ')');
               $this->plg_id = \core_db::getInsertID();
          }
          if (!$success) {
               error_log('Unable to save question');
          }
          return $success;
     }

     public static function getPLGs($school_year = null){
          if(!$school_year){
               $school_year = mth_schoolYear::getCurrent();
          }

          $sql = "select * from yoda_plgs where school_year_id={$school_year->getID()}";
          return core_db::runGetObjects($sql,__CLASS__);
     }

     public static function getPLGCount($school_year = null){
          if(!$school_year){
               $school_year = mth_schoolYear::getCurrent();
          }

          $sql = "select count(*) from yoda_plgs where school_year_id={$school_year->getID()}";
          return core_db::runGetValue($sql);
     }
     /**
      * Get unique subjects from plg record
      * @param mth_schoolYear $school_year
      * @return mixed
      */
     public static function distictSubjects($school_year = null){
          if(!$school_year){
               $school_year = mth_schoolYear::getCurrent();
          }

          $sql = "select distinct(subject) from yoda_plgs where school_year_id={$school_year->getID()}";
          return core_db::runGetValues($sql);
     }

    public static function getBySubjectAndSchoolYear($subject_name,$school_year){
      $sql = "select * from yoda_plgs where subject='$subject_name' and school_year_id={$school_year->getID()}";
      return core_db::runGetObjects($sql,__CLASS__);
    }

    public static function getCheclistBySubjectAndYear($subject_name, $school_year,$format = false){
      $items = [];
      foreach (self::getBySubjectAndSchoolYear($subject_name, $school_year) as $item) {
        $items[] = $format?["list"=> $item->getName()]: $item->getName();
      }
      return $items; 
    } 

     public static function getFirstType($school_year = null){
        $sql = "select plg_type from yoda_plgs where school_year_id={$school_year->getID()} limit 1";
        return core_db::runGetValue($sql);
     }

     /**
      * Get unique grade level from plg record
      * @param int $school_year
      * @return mixed
      */
     public static function distictGradeLevels($school_year = null){
          if(!$school_year){
               $school_year = mth_schoolYear::getCurrent();
          }

          $sql = "select distinct(grade_level) from yoda_plgs where school_year_id={$school_year->getID()}";
          return core_db::runGetValues($sql);
     }
     /**
      * Get plg courses
      * @param null|string $grade_level
      * @param string $subject
      * @param int $school_year_id
      * @return null|array
      */
     public static function get($grade_level = null,$subject,$school_year_id){
          $grade_level_filter = $grade_level?"and grade_level='$grade_level'":'';
          $sql = "select * from yoda_plgs where school_year_id=$school_year_id $grade_level_filter and subject='$subject'";
          return core_db::runGetObjects($sql,__CLASS__);
     }
     /**
      * Delete PLG cache by year but only allow delete if there is no exisiting homeroom for the current year
      * @param mth_schoolYear $school_year
      * @param int|null $last_course course id of the remaining course/homeroom for the target year
      * @return bool
      */
     public static function deleteByYear($school_year,$last_course_id = null){
          if(self::allowDelete($school_year,$last_course_id)){
               return core_db::runQuery("DELETE FROM yoda_plgs where school_year_id={$school_year->getID()}");
          }
          return false;
     }

     /**
      * Validation for deleting plg
      * @param mth_schoolYear $school_year
      * @param int|null $last_course_id last course id from the target school year
      * @return bool
      */
     public static function allowDelete($school_year,$last_course_id = null){
          $course_count = 0;
          foreach(courses::eachHomeroomByYear($school_year) as $course){
               if($last_course_id && $course->getCourseId() != $last_course_id){
                    $course_count++;
               }
          }

          return $course_count==0;
     }

     public static function getGradeLevelCountBySubject($subject,$school_year_id){
          $sql = "select count(*) as total,grade_level from yoda_plgs where school_year_id=$school_year_id and subject='$subject' group by grade_level";
          $result = core_db::runQuery($sql);
          if(!$result){
               error_log('Error in filter query: getGradeLevelCountBySubject subject:'.$subject.' sy:'.$school_year_id);
               return;
          }
          $aggrigate = [];
          while ($r = $result->fetch_object()) {
               $aggrigate[$r->grade_level] = $r->total;
          }
          $result->free_result();
          return $aggrigate;
     }
}
