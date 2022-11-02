<?php
namespace mth\yoda;

class model
{
     protected $updateQueries = array();
     protected $insertQueries = array();
     

     /**
     * set value and enterers field update query in the the updateQueries array
     * @param string $field
     * @param string $value this function will make sure the value is escaped for the database, but no other sanitation.
     */
     public function set($field, $value = null)
     {
          if (is_null($value)) {
               $this->updateQueries[$field] = '`' . $field . '`=NULL';
          } else {
               $this->updateQueries[$field] = '`' . $field . '`="' . \core_db::escape($value) . '"';
          }
     }
     /**
      * Undocumented function
      * @param string $field field name
      * @param [type] $value
      * @return void
      */
     public function setInsert($field, $value = null)
     {
          if (is_null($value)) {
               $this->insertQueries[$field] = 'NULL';
          } else {
               $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
          }
     }
}

