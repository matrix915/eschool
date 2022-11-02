<?php

/**
 * mth_reimbursement
 *
 * @author abe
 */
class mth_reimbursementtype
{
     protected $placeholder;
     protected $label;
     protected $is_enabled;
     protected $id;

     protected static $cache = array();
     protected $updateQueries = array();
     protected $insertQueries = array();

     public function getPlaceHolder()
     {
          return $this->placeholder;
     }
     public function getLabel()
     {
          return $this->label;
     }
     public function isEnabled()
     {
          return $this->is_enable == 1;
     }

     public function getIsEnable(){
          return $this->is_enabled;
     }

     public function each($reset = false)
     {
          if (NULL === ($result = &self::$cache['each'])) {
               $result = core_db::runQuery('SELECT * from mth_reimbursement_type');
          }
          if (!$reset && ($reimbursement = $result->fetch_object('mth_reimbursementtype'))) {
               return $reimbursement;
          }
          $result->data_seek(0);
          return NULL;
     }

     public static function getEnabledPlaceHolders(){
          if (NULL === ($result = &self::$cache['getEnabled'])) {
               $result = [];
               if($q = core_db::runQuery('SELECT `placeholder` FROM mth_reimbursement_type where is_enable=1')){
                    while ($r = $q->fetch_row()) {
                         $result[] = $r[0];
                    }
                    $q->close();
               }    
          }
          return $result;
     }


     public function set($field, $value)
     {
          if (is_null($value)) {
               $this->updateQueries[$field] = '`' . $field . '`=NULL';
          } else {
               $this->updateQueries[$field] = '`' . $field . '`="' . core_db::escape($value) . '"';
          }
          return $this;
     }

     public function setInsert($field, $value)
     {
          if (is_null($value)) {
               $this->insertQueries[$field] = 'NULL';
          } else {
               $this->insertQueries[$field] = '"' . core_db::escape($value) . '"';
          }
          return $this;
     }

     public function getID(){
          return $this->id;
     }

     public function save()
     {
          if (!empty($this->updateQueries)) {
               $success = core_db::runQuery('UPDATE mth_reimbursement_type SET ' . implode(',', $this->updateQueries) . ' WHERE id=' . $this->getID());
          } else {
               $success = core_db::runQuery('INSERT INTO mth_reimbursement_type(' . implode(',', array_keys($this->insertQueries)) . ') VALUES(' . implode(',', $this->insertQueries) . ')');
               $this->id = core_db::getInsertID();
          }

          return $success;
     }

     public static function getByPlaceHolder($placeholder)
     {
          $cache = &self::$cache[$placeholder];
          if (!isset($cache)) {
               $cache = core_db::runGetObject('SELECT * FROM mth_reimbursement_type WHERE placeholder=' . (int) $placeholder, 'mth_reimbursementtype');
          }
          return $cache;
     }
}
