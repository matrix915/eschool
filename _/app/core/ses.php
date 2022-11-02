<?php
use mth\aws\ses;

/**
 * Email container that uses MTH AWS with SES sdk
 */
class core_ses implements core_emaildriver
{
     private $to;
     private $from = null;
     private $bcc = null;
     private $replyTo = null;
     private $content;
     private $subject;
     private $config_set;
     private $enable_tracking = false;
     const DEFAULT_CONFIG_SET = 'default-configuration';

     public function setTo(array $to)
     {
          $this->to = $to;
          return $this;
     }
     /**
      * FROM Setter
      * If parameter is emtpy set the default sender to site email
      * @param array $from 
      * @return core_ses
      */
     public function setFrom(array $from = [])
     {
          if (empty($from)) {
               $from = [core_setting::getSiteEmail()->getValue(), core_setting::getSiteName()->getValue()];
          }

          $this->from = $this->formatFrom($from);
          return $this;
     }
     public function setBcc(array $bcc)
     {
          $this->bcc = $bcc;
          return $this;
     }
     public function setReplyTo($replyTo)
     {
          $this->replyTo = $replyTo;
          return $this;
     }
     public function setContent($content)
     {
          $this->content = $content;
          return $this;
     }
     public function setSubject($subject)
     {
          $this->subject = $subject;
          return $this;
     }

     /**
      * Execute Send
      * @return boolean
      */
     public function send()
     {
          $ses = new ses();
          
          if($this->enable_tracking){
               $ses->setConfigSet($this->config_set);
          }

          return $ses->send(
               $this->to,
               $this->from,
             req_sanitize::txt_decode($this->subject),
               $this->content,
               $this->bcc,
               $this->replyTo
          );
     }

     public function sendRaw($content){
          $ses = new ses();
          if($this->enable_tracking){
               $ses->setConfigSet($this->config_set);
          }
          return $ses->sendRaw($content);
     }

     /**
      * Format SES standard FROM
      * @param array $from [email,name]
      * @return string email@email.com | Name <email@email.com>
      */
     private function formatFrom($from)
     {
          
          if (isset($from[1])) {
               return '"' . $from[1] . '"<' . $from[0] . '>';
          }
          return    $from[0];
     }

     public function enableTracking($enable){
          $this->enable_tracking = $enable;
          if($enable){
              $this->setConfigSet();
          }
     }

     public function setConfigSet($value = null){
          $this->config_set = $value?$value:self::DEFAULT_CONFIG_SET;
     }
}
