<?php
namespace mth\yoda\homeroom;
use core\Database\PdoAdapterInterface;
use core\Injectable;
use mth\aws\ses;

class messages{
    use Injectable, Injectable\PdoAdapterFactoryInjector;
    protected $id;
    protected $yoda_course_id;
    protected $title;
    protected $content;
    protected $teacher_user_id;
    protected $updateQueries = array();
    protected $insertQueries = array();

    public function getID(){
        return $this->id;
    }
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
            
            if($field == 'title'){
                $value = self::sanitizeText($value);
            }

            if($field == 'content'){
                $value = self::sanitizeAndFixHTML($value);
            }
            
            $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
        }
    }

    public function save(){
        if (!empty($this->updateQueries)) {
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_homeroom_messages SET ' . implode(',', $this->updateQueries) . ' WHERE id=' . $this->getID());
        }else{
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_homeroom_messages('.implode(',',array_keys($this->insertQueries)).') VALUES(' . implode(',',$this->insertQueries).')');
            $this->id = \core_db::getInsertID();
        }

        return $success;
    }

    public static function sanitizeText($text)
    {
        return \req_sanitize::txt($text);
    }

    public static function sanitizeAndFixHTML($HTML)
    {
        $HTML = trim($HTML);
        if (empty($HTML)) {
            return '';
        }
        include_once ROOT . '/_/includes/HTMLPurifier/HTMLPurifier.auto.php';
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Trusted', true);
        $config->set('CSS.Trusted', true);
        $config->set('HTML.TargetBlank', true);
        $htmlFixer = new \HTMLPurifier($config);
        return $htmlFixer->purify($HTML);
    }

    /**
     * Publish homeroom message
     * @param string $content
     * @param string $subject
     * @param array $to 
     * @param [type] $bcc
     * @param [type] $from [email,name]
     * @return boolean
     */
    public function publish($content, $subject,array $to,$bcc = null,$from = null){
     
        $subject = \req_sanitize::txt_decode($subject);

        if (!\core_config::isProduction()) {
            $to = [\core_misc::getTestEmailAddress($to[0])];
        }
        
      
        if(is_null($from) && ($user = \core_user::getCurrentUser())){
            $from = [$user->getEmail(),$user->getName()];
        }

        if($from){
            $ses = new \core_emailservice();
            $ses->enableTracking(true);

            if(!is_null($bcc)){
                $bcc[] = $user->getEmail();
            }

            return $ses->send(
                $to,
                $subject,
                $content,
                $from,
                $bcc
            );
        }
        return false;     
    }
}