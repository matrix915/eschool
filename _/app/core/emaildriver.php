<?php
/**
 * All email container should be contracted/implements from this interface
 */
interface core_emaildriver{
     /**
      * Set recepient
      * @param array $to of string the value should be email address
      * @return void
      */
     public function setTo(array $to);
     public function setBcc(array $bcc);
     public function setReplyTo($replyTo);
     public function setContent($content);
     public function setSubject($subject);
     public function send();
     /**
      * Set Sender
      * @param array $from array of string first param is the email and second param is the name
      * @return void
      */
     public function setFrom(array $from = []);
}