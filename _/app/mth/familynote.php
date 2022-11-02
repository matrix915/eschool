<?php
class mth_familynote extends core_model
{
    protected $id;
    protected $parent_id;
    protected $note;

    private static $_cache;
    private $_updateQuery = array();
    
    public function setNote($note = null)
    {
        if ($note !== NULL && $note != $this->note) {
            $this->note =  req_sanitize::multi_txt($note);
            $this->_updateQuery[] = 'note="' . core_db::escape($this->note) . '"';
        }
    }

    public function getID(){
        return $this->id;
    }

    public static function getByID($id){
        $cache = &self::$_cache['id'][$id];
        if (!isset($cache)) {

            $cache = core_db::runGetObject('SELECT * 
                                  FROM mth_familynote where id='.$id,__CLASS__);
            if ($cache) {
                self::$_cache['id'][$cache->getID()] = $cache;
            }
        }
        return $cache;
    }

    public static function create(mth_parent $parent,$note = null)
    {
        if (!$parent) {
            return false;
        }
        $note = req_sanitize::multi_txt($note);
        $_note = $note?('"'.$note.'"'):'NULL';

        core_db::runQuery('INSERT INTO mth_familynote (parent_id, note) 
                        VALUES (' . $parent->getID() . ', '. $_note.')');
        return self::getByID(core_db::getInsertID());
    }

    public function __destruct()
    {
        $this->saveChanges();
    }

    public function saveChanges()
    {
        if (empty($this->_updateQuery)) {
            return true;
        }
        $success = core_db::runQuery('UPDATE mth_familynote
                                  SET ' . implode(',', $this->_updateQuery) . '
                                  WHERE parent_id=' . $this->getParentId());
        $this->_updateQuery = array();
        return $success;
    }

    public static function getByParentID($parent_id){
        $cache = &self::$_cache['parent_id'][$parent_id];
        if (!isset($cache)) {

            $cache = core_db::runGetObject('SELECT * 
                                  FROM mth_familynote where parent_id='.$parent_id,__CLASS__);
            if ($cache) {
                self::$_cache['parent_id'][$cache->getParentId()] = $cache;
            }
        }
        return $cache;
    }

    public function getParentId(){
        return $this->parent_id;
    }

    public function getNote(){
        return $this->note;
    }

    public function __toString()
    {
        return (string) $this->getNote();
    }
}