<?php

/**
 * For storing enrollment data needed to interact with canvas
 *
 * @author abe
 */
class mth_system_log extends core_model
{
    protected $log_id;
    protected $user_id;
    protected $new_value;
    protected $old_value;
    protected $archive;
    protected $tag;
    protected $type;

    private $_updateFields;

    protected static $cache = array();

    public function setUserId($set){
       if($set){
            $this->user_id = (int) $set;
       }
    }

    public function setTag($set){
        if($set){
             $this->tag = $set;
        }
     }

     public function setType($set){
        if($set){
            $this->type =$set;
       }
     }

    public function setNewValue($value,$type = core_setting::TYPE_TEXT){
        if($value){
            $this->new_value = core_setting::sanitizeValueStr($value,$type);
        }
    }

    public function setOldValue($value,$type = core_setting::TYPE_TEXT){
        if($value){
            $this->old_value = core_setting::sanitizeValueStr($value,$type);
        }
    }

    protected function set($field, $value, $force = false)
    {
        return parent::set($field, $value, $force);
    }

    public function setArchive($set = null){
        if (!is_null($set)) {
            $this->set('archive', (int)$set);
        }
    }

    public function getID(){
        return $this->log_id;
    }

    public function save()
    {
        if(!$this->log_id){
            $insert = core_db::runQuery('INSERT INTO mth_system_log(user_id,new_value,old_value,tag,type) 
            VALUES('.core_db::escape($this->user_id).',"'.core_db::escape($this->new_value).'","'.core_db::escape($this->old_value).'","'.core_db::escape($this->tag).'","'.core_db::escape($this->type).'")');
            $this->log_id = core_db::getInsertID();
            return  $insert;
        }

        return parent::runUpdateQuery('mth_system_log', 'log_id=' . $this->getID());
    }

    public static function list(){
        return core_db::runGetObjects('select * from mth_system_log order by date_created desc',
            __CLASS__);
    }

    public function getNewValue(){
        return $this->new_value;
    }

    public function getOldValue(){
        return $this->old_value;
    }

    public function getTag(){
        return $this->tag;
    }

    public function getCreatedDate($format = null){
        return $format?date($format,$this->date_created):$this->date_created;
    }

    public function getUser(){
        return core_user::getUserById($this->user_id);
    }


}