<?php
namespace mth\yoda;
use core\Database\PdoAdapterInterface;
use core\Injectable;

class questions{
    use Injectable, Injectable\PdoAdapterFactoryInjector;
    protected $data;
    protected $type;
    protected $id;
    protected $number;
    protected $yoda_teacher_asses_id;
    protected $plg_subject;
    protected static $cache = array();
    protected $updateQueries = array();
    protected $insertQueries = array();
    protected $is_required;

    const CHECKLIST = 2;
    const TEXT = 1;
    const PLG = 3;
    const PLG_INDEPENDENT = 4;

    public function getByTeacherAssesId($yoda_teacher_asses_id){
        $cache = &self::$cache['getByTeacherAssesId'][$yoda_teacher_asses_id];
        $sql = 'select * from yoda_assessment_question where yoda_teacher_asses_id=:yoda_teacher_asses_id order by `number`,id';
    
        return $this->getPdoAdapter()
        ->prepare($sql)
        ->execute([':yoda_teacher_asses_id'=>$yoda_teacher_asses_id])
        ->fetchAllClass(__CLASS__);
    }

    public static function getById($id){
        $cache = &self::$cache['getById'][$id];
        $sql = 'select * from yoda_assessment_question where id='.(int)$id.' limit 1';
    
        if (!isset($cache)) {
            $cache = \core_db::runGetObject($sql , __CLASS__);
        }

        return $cache;
    }

    public function isChecklist(){
        return $this->type == self::CHECKLIST;
    }

    public function isChecklistType(){
      return in_array($this->type,[self::CHECKLIST, self::PLG_INDEPENDENT]);
    }

    public function isPlgIndependent()
    {
      return $this->type == self::PLG_INDEPENDENT;
    }

    public function isPLG(){
        return $this->type == self::PLG;
    }

    public function isPLGgroup(){
      return in_array($this->type, [self::PLG, self::PLG_INDEPENDENT]);
    }

    public function getID(){
        return $this->id;
    }

    public function isText(){
        return $this->type == self::TEXT;
    }

    public function getType(){
        return $this->type;
    }

    public function getSubject(){
        return $this->plg_subject;
    }

    public function getData(){
        return $this->data;
    }

    public function getNumber(){
        return $this->number;
    }

    public function isRequired(){
      return  $this->is_required == 1;
    }

    public static function getByData($data,$assess_id){
        $db = new \core_db();
        $sql = "select * from yoda_assessment_question where data='{$db->escape_string($data)}' and yoda_teacher_asses_id=$assess_id";
        return $db::runGetObject($sql,__CLASS__);
    }

    public static function getQuestionCountByAssessment($assessment_id){
        $sql = "select count(*) as last_row from yoda_assessment_question where yoda_teacher_asses_id=$assessment_id";
        return \core_db::runGetValue($sql);
    }

    public function set($field, $value)
    {
        $this->{$field} = $value;
        if (is_null($value)) {
            $this->updateQueries[$field] = '`' . $field . '`=NULL';
        } else {
            $this->updateQueries[$field] = '`' . $field . '`="' . \core_db::escape($value) . '"';
        }
        return $this;
    }

    public function setInsert($field, $value){
        $this->{$field} = $value;
        if (is_null($value)) {
            $this->insertQueries[$field] = 'NULL';
        } else {
            $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
        }
        return $this;
    }

    public function save(){
        if (!empty($this->updateQueries)){
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_assessment_question SET ' . implode(',', $this->updateQueries) . ' WHERE id=' .$this->getID());
        }else{
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_assessment_question('.implode(',',array_keys($this->insertQueries)).') VALUES(' . implode(',',$this->insertQueries).')');
            $this->id = \core_db::getInsertID();
        }
        if(!$success){
            error_log('Unable to save question');
        }
        return $success;
    }
    /**
     * Delete question except specified question
     * @param array $ids
     * @param int $assessment_id
     * @return void
     */
    public static function deleteExcept($ids = [],$assessment_id){
        if(!$ids){
            return false;
        } 
        return  \core_db::runQuery('delete from yoda_assessment_question 
        where yoda_teacher_asses_id='.$assessment_id.' and id not in('.implode(',',$ids).')');
    }

    public function clone($new_assessment_id = null){
        $_question = new questions();
        return $_question->setInsert('data',$this->data)
        ->setInsert('yoda_teacher_asses_id',$new_assessment_id?$new_assessment_id:$this->yoda_teacher_asses_id)
        ->setInsert('type',$this->type)
        ->setInsert('number',$this->number)
        ->setInsert('plg_subject',$this->plg_subject)
        ->setInsert('is_required',(int) $this->is_required)
        ->save();
    }

}