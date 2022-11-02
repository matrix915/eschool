<?php
namespace mth\yoda;
use core\Database\PdoAdapterInterface;
use core\Injectable;

class messages{
    use Injectable, Injectable\PdoAdapterFactoryInjector;
    protected static $cache = array();
    protected $message_title;
    protected $message_content;
    protected $created_at;
    protected $id;
    protected $updateQueries = array();
    protected $insertQueries = array();

    public function getMessagesById($id){
        $cache = &self::$cache[$id];
        $sql = 'select * from yoda_messages where id=:id limit 1';
    
        return $this->getPdoAdapter()
        ->prepare($sql)
        ->execute([':id'=>$id])
        ->fetchAllClass(__CLASS__);
    }

    public function getMessageByPersonId($person_id){
        $cache = &self::$cache['getMessageByPersonId'][$person_id];
        $sql = 'select * from yoda_messages where to_person_id=:to_person_id order by created_at desc';
    
        return $this->getPdoAdapter()
        ->prepare($sql)
        ->execute([':to_person_id'=>$person_id])
        ->fetchAllClass(__CLASS__);
    }

    /**
     * Get All yoda messages from homerom studen was assigned
     * @param int $person_id
     * @param int $course_id
     * @return void
     */
    public function getAllByHomeroom($person_id,$course_id){
        $sql = "select ym.* from yoda_student_assessments as ysa
                inner join yoda_messages as ym on ym.id=ysa.message_id
                inner join yoda_teacher_assessments as yta on yta.id=ysa.assessment_id
                where ysa.person_id=".\core_db::escape($person_id)." and yta.course_id=".\core_db::escape($course_id)." order by ym.created_at desc";
        return \core_db::runGetObjects($sql,__CLASS__);
    }

    public static function getAllFromAssessment($person_id,$assessment_id){
        $sql = 'select ym.* from yoda_student_assessments as ysa
        inner join yoda_messages as ym on ym.id=ysa.message_id
        where ysa.person_id='.\core_db::escape($person_id).' and assessment_id='.\core_db::escape($assessment_id).' order by created_at desc';
        return \core_db::runGetObjects($sql,__CLASS__);
    }

    public function getTitle(){
        return $this->message_title;
    }

    public function getContent(){
        if($json_encoded = json_decode($this->message_content)){
            return $json_encoded;
        }
        return $this->message_content;
    }

    public function getDate($format = null){
        return $format?\core_model::getDate($this->created_at,$format):$this->created_at;
    }

    public function getLearningLog(){
        $cache = &self::$cache['getLearningLog'][$this->id];

        if(!isset($cache)){
            $sql = 'select * from yoda_student_assessments where message_id=:message_id limit 1';
            $cache = $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([':message_id'=>$this->id])
            ->fetchClass(studentassessment::class);
        }

        return $cache;
    }

    public function getID(){
        return $this->id;
    }

    public function setInsert($field, $value)
    {
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
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_messages SET ' . implode(',', $this->updateQueries) . ' WHERE id=' . $this->getID());
        } else {
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_messages(' . implode(',', array_keys($this->insertQueries)) . ') VALUES(' . implode(',', $this->insertQueries) . ')');
            $this->id = \core_db::getInsertID();
        }

        return $success;
    }
}