<?php
class mth_intervention_notes extends core_model{
    protected $notes_id;
    protected $intervention_id;
    protected $user_id;
    protected $notes;
    protected $date_created;
    protected static $cache = array();

    public function getID(){
        return (int)$this->notes_id;
    }

    /**
     * @return mth_intevention
     */
    public function intervention(){
        return mth_intervention::getByID($this->intervention_id);
    }

    public function getNote(){
        return $this->notes;
    }

    public function user(){
        return core_user::getUserById($this->user_id);
    }

    public function setId($set = NULL){
        if(!is_null($set)){
            $this->set('notes_id',(int)$set);
        }
        return (int)$this->notes_id;
    }
    public function setInterventionId($set = NULL){
        if(!is_null($set)){
            $this->set('intervention_id',(int)$set);
        }
        return (int)$this->intervention_id;
    }
    public function setUserId($set = NULL){
        if(!is_null($set)){
            $this->set('user_id',(int)$set);
        }
        return (int)$this->user_id;
    }
    public function setNotes($set = NULL){
        if(!is_null($set)){
            $this->set('notes',core_db::escape($set));
        }
        return $this->notes;
    }

    public function save(){
        if(!$this->notes_id){
            core_db::runQuery("INSERT INTO mth_intervention_notes(intervention_id,user_id,notes) VALUES({$this->intervention_id},{$this->user_id},'{$this->notes}')");
            $this->notes_id = core_db::getInsertID();
            return $this->notes_id;
        }
        return parent::runUpdateQuery("mth_intervention_notes",'notes_id='.$this->getID());
    }

    public static function getById($id){
        $result = &self::$cache['getById'][$id];
        if(!isset($result)){
            $sql = 'select * from mth_intervention_notes where notes_id='.$id.' limit 1';
            $result = core_db::runGetObject($sql,__CLASS__); 
        }
        return $result;
    }

    public function delete(){
        if(!$this->notes_id){
            return false;
        }
        return core_db::runQuery("DELETE FROM mth_intervention_notes where notes_id=".$this->notes_id);
    }

    public static function each($intervention_id,$reset = FALSE){
        if (!$intervention_id) {
            return false;
        }
        $result = &self::$cache['each'][$intervention_id];

        if(!isset($result)){
            $sql = 'select * from mth_intervention_notes where intervention_id='.$intervention_id.' order by date_created desc';
            $result = core_db::runQuery($sql); 
        }

        if(!$reset && ($offense = $result->fetch_object('mth_intervention_notes'))){
            return $offense;
        }

        $result->data_seek(0);
        return NULL;
    }

    public static function count($intervention_id = null){
        if (is_null($intervention_id)) {
            return NULL;
        }

        $result = &self::$cache['each'][$intervention_id];
        if (!isset($result)) {
            self::each($intervention_id,true);
        }

        return $result->num_rows;
    }

    public function getCreatedDate($format = NULL){
        return is_null($this->date_created)?null:core_model::getDate($this->date_created, $format);
    }
}