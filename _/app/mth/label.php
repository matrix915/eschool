<?php
class mth_label extends core_model{
    protected $label_id;
    protected $name;
    protected $user_id;
    protected $date_created;
    protected static $cache = array();
    protected static $all = [];
    
    public function __destruct()
    {
        $this->save();
    }

    public function getID(){
        return $this->label_id;
    }

    public function labelId($set=NULL){
        if(!is_null($set)){
            $this->set('label_id',$set);
        }
        return $this->label_id;
    }

    public function getName(){
        return $this->name;
    }
    public function name($set=NULL){
        if(!is_null($set)){
            $this->set('name',$set);
        }
        return $this->name;
    }

    public function userId($set=NULL){
        if(!is_null($set)){
            $this->set('user_id',$set);
        }
        return $this->user_id;
    }

    public function getCreatedDate($format = NULL){
        return is_null($this->date_created)?null:core_model::getDate($this->date_created, $format);
    }

    public function save(){
        if (!$this->label_id && !$this->name) {
            return false;
        }

        if (!$this->label_id) {
            core_db::runQuery('INSERT INTO mth_label (name,user_id) VALUES ("'.$this->name.'",'.$this->user_id.')');
            $this->label_id = core_db::getInsertID();
        }

        return parent::runUpdateQuery('mth_label', '`label_id`=' . $this->getID());
    }

    public function delete(){
        if (!$this->label_id) {
            return false;
        }

        return core_db::runQuery('DELETE FROM mth_label WHERE label_id=' . $this->getID());

    }

    public function get(){
        if ($this->label_id) {
           return self::getById($this->label_id); 
        }
        return null;
    }

    public static function getById($id){
        if($id){
            return core_db::runGetObject('select * from mth_label where label_id='.$id,'mth_label');
        }
        return null;
    }

    public static function  all(){
        $results = &self::$cache['allLabel'];
        if($results === NULL){
            $query = core_db::runQuery("select * from mth_label");
            $results = [];
            while($_result = $query->fetch_object('mth_label')){
                $results[$_result->getID()] = $_result;
            }
           
        }
        self::$all = $results;
        return new self;
    }

    public function toArray(){
        $response = [];
        foreach(self::$all as $label){
            $response[] = [
                'label_id' => $label->getID(),
                'name' => $label->name(),
                'user_id' => $label->userId(),
                'date_created' => $label->getCreatedDate()
            ];
        }
        return $response;
    }
}