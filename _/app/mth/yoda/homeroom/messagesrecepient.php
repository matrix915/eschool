<?php
namespace mth\yoda\homeroom;
use core\Database\PdoAdapterInterface;
use core\Injectable;
use mth\aws\ses;

class messagesrecepient{
    protected $person_id;
    protected $yoda_homeroom_messages_id;
    protected $id;

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
        if (isset($this->updateQueries['id'])) {
            $id = explode('=',$this->updateQueries['id']);
            $this->id = intval(str_replace('"','',$id[1]));
            
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_homeroom_messages_recepient SET ' . implode(',', $this->updateQueries) . ' WHERE id=' . $this->updateQueries['id']);
        }else{
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_homeroom_messages_recepient('.implode(',',array_keys($this->insertQueries)).') VALUES(' . implode(',',$this->insertQueries).')');
            $this->id = \core_db::getInsertID();
        }

        return $success;
    }
}