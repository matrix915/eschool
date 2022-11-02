<?php
namespace mth\yoda;
use core\Database\PdoAdapterInterface;
use core\Injectable;
use mth\yoda\questions;
use mth\yoda\studentassessment;
use mth_schoolYear;

class answers extends questions{
    use Injectable, Injectable\PdoAdapterFactoryInjector;

    protected $id;
    protected $data;
    protected $type;
    protected $updateQueries = array();
    protected $insertQueries = array();
    protected $yoda_student_asses_id;
    protected $yoda_assessment_question_id;
    private static $_cache = array();
    
    public function getID(){
        return $this->id;
    }

    public function getData(){
        return (json_decode($this->data))->answer;
    }

    public function getGradeLevel(){
        return (json_decode($this->data))->grade_level;
    }

    /**
     * set value and enterers field update query in the the updateQueries array
     * @param string $field
     * @param string $value this function will make sure the value is escaped for the database, but no other sanitation.
     */
    public function set($field, $value)
    {
        if (is_null($value)) {
            $this->updateQueries[$field] = '`' . $field . '`=NULL';
        } else {
            $this->updateQueries[$field] = '`' . $field . '`="' . \core_db::escape($value) . '"';
        }
    }

    public function setInsert($field, $value){
        if (is_null($value)) {
            $this->insertQueries[$field] = 'NULL';
        } else {
            $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
        }
    }
    
    public function save(){
        if (!empty($this->updateQueries)){
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_assessment_answers SET ' . implode(',', $this->updateQueries) . ' WHERE id=' .$this->getID());
        }else{
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_assessment_answers('.implode(',',array_keys($this->insertQueries)).') VALUES(' . implode(',',$this->insertQueries).')');
        }

        return $success;
    }

    /**
     * Delete Answer
     * @param int $yoda_student_asses_id
     * @return void
     */
    public static function delete($yoda_student_asses_id){
        return  \core_db::runQuery('DELETE FROM yoda_assessment_answers WHERE yoda_student_asses_id=' . $yoda_student_asses_id);
    }

    /**
     * Get Answer by Student assessment id
     * @param int $student_assessment_id
     * @return answers
     */
    public static function getByStudentAssessmentId($student_assessment_id){
      
        $cache = &self::$cache['getByStudentAssessmentId'][$student_assessment_id];
        $sql = 'select * from yoda_assessment_answers where yoda_student_asses_id='.$student_assessment_id;

    
        if (!isset($cache)) {
            $cache = \core_db::runGetObjects($sql , __CLASS__);
        }

        return $cache;
    }

    /**
     * Get Answers Array Object by teacher's assessment id
     * @param int $id
     * @return answers
     */
    public function getAnswersByAsessId($id){
        $_cache = &self::$_cache['getAnswersByAsessId'][$id];
        
        $sql = 'select yaa.* from yoda_assessment_answers as yaa 
        inner join yoda_student_assessments as ysa on ysa.id=yaa.yoda_student_asses_id
        where ysa.assessment_id=:id order by yaa.created_at';

        return $this->getPdoAdapter()
        ->prepare($sql)
        ->execute([':id'=>$id])
        ->fetchAllClass(__CLASS__);
    }

    /**
     * Get answer
     * @param int $assesment_id
     * @param int $person_id
     * @param string $order_by
     * @return answers
     */
    public function get($assesment_id,$person_id,$order_by = 'yaa.yoda_assessment_question_id'){
        $_cache = &self::$_cache['get'][$person_id];
        
        $sql = 'select yaa.* from yoda_assessment_answers as yaa 
        inner join yoda_student_assessments as ysa on ysa.id=yaa.yoda_student_asses_id
        where ysa.assessment_id=:id and ysa.person_id=:person_id order by '.$order_by;

        return $this->getPdoAdapter()
        ->prepare($sql)
        ->execute([':id'=>$assesment_id,':person_id'=>$person_id])
        ->fetchAllClass(__CLASS__);
    }

    /**
     * Get Answer object by teacher assessment and question object
     * @param studentassessment $assessment
     * @param questions $question
     * @return answers
     */
    public static function getByAssessmentQuestion(studentassessment $assessment,questions $question){
        $cache = &self::$cache['getByAssessmentQuestion'][$assessment->getID()][$question->getID()];
        $sql = 'select * from yoda_assessment_answers where yoda_student_asses_id='.(int)$assessment->getID().' and yoda_assessment_question_id='.$question->getID().' limit 1';
    
        if (!isset($cache)) {
            $cache = \core_db::runGetObject($sql , __CLASS__);
        }

        return $cache;
    }

    /**
     * Get Question from answer object
     * @param boolean $class true of get Question object false if the question encoded data
     * @return mixed
     */
    public function getQuestion($class = false){
        if($class){
            return parent::getById($this->yoda_assessment_question_id);
        }
        if($question = parent::getById($this->yoda_assessment_question_id)){
            $data = $question->getData();
            return $question->isChecklist()?(json_decode($data))->title:$data;
        }
        return null;
    }
    /**
     * Get answer using answer_id
     * @param int $id
     * @return null||answers
     */
    public static function getUsingId($id){
        return \core_db::runGetObject('SELECT * from yoda_assessment_answers where id='.\core_db::escape($id),__CLASS__);
    }

    /**
     * Get students learning history and compile/collect past PLG selections
     * @param int $student_id
     * @param int $asssessment_id
     * @param mth_schoolYear $schoolYear
     * @return array of strings(PLG Name)
     */
    public static function getPastSelectedSpecial($student_id, $asssessment_id = null, $schoolYear = null){
        if(!$schoolYear){
            $schoolYear = mth_schoolYear::getCurrent();
        }

        $sql = "select yaa.data from yoda_assessment_answers as yaa
        inner join yoda_assessment_question as yaq on yaq.id=yaa.yoda_assessment_question_id
        inner join yoda_teacher_assessments as yta on yta.id=yaq.yoda_teacher_asses_id
        inner join yoda_student_homeroom as ysh on ysh.yoda_course_id=yta.course_id
        inner join mth_student as ms on ms.student_id=ysh.student_id
        where ysh.student_id=$student_id and (yaa.type=".questions::PLG." or yaa.type=".questions::PLG_INDEPENDENT.") and ysh.school_year_id={$schoolYear->getID()}
        ".($asssessment_id?" and yta.id!=$asssessment_id ":''). "
        and yaa.yoda_student_asses_id = (
            select max(id) from yoda_student_assessments where assessment_id=yta.id  
            and person_id=ms.person_id and grade is not null and excused is null and 
            (reset is null or reset=".studentassessment::RESUBMITTED.")
        )";
        return \core_db::runGetValues($sql);
    }
}