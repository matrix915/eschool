<?php

class mth_userannouncement extends core_model
{
    protected $id;
    protected $announcement_id;
    protected $status;
    protected $user_id;

    protected static $cache = array();

    CONST STATUS_ARCHIVE = 1;

    protected function set($field, $value, $force = false)
    {
        return parent::set($field, $value, $force);
    }

    public function getID()
    {
        return $this->id;
    }

    public function save(){
        if(!$this->id && !$this->user_id && !$this->announcement_id){
            return false;
        }

        if(!$this->id){
            $result = core_db::runQuery('INSERT INTO mth_user_announcement (`user_id`,`announcement_id`) 
            VALUES ('.$this->user_id.','.$this->announcement_id.')');
            $this->id = core_db::getInsertID();
            return $this->id;
        }
        return parent::runUpdateQuery('mth_user_announcement', 'id=' . $this->getID());
    }

    public function user_id($set = null){
        if (!is_null($set)) {
            $this->set('user_id', (int)$set);
        }
        return $this->user_id;
    }

    public function announcement_id($set = null){
        if (!is_null($set)) {
            $this->set('announcement_id', (int)$set);
        }
        return $this->announcement_id;
    }

    public function status($set = null){
        if (!is_null($set)) {
            $this->set('status', (int)$set);
        }
        return $this->status;
    }

    public function isArchived(){
        return $this->status == self::STATUS_ARCHIVE;
    }

    public static function get($user_id,$announcement_id){
        $announcement = &self::cache(__CLASS__, 'get'.$user_id.'-'.$announcement_id);
        if(!isset($announcement)){
            $announcement = core_db::runGetObject('SELECT * from mth_user_announcement where user_id='.(int)$user_id.' and announcement_id='.(int)$announcement_id,
            'mth_userannouncement');
        }

        return $announcement;
    }

    public function archive(){
        $this->status(self::STATUS_ARCHIVE);
        return $this->save();
    }

}