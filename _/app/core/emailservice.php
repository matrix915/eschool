<?php
/**
 * Author: Rex
 * Date: 4/20/2019
 * Global Email service with injectable containers
 * So in the future using different email service container is possible without making a huge change from the code
 * 
 * Uses ses service as default container
 */
class core_emailservice{
     private $container;

     public function __construct(core_emaildriver $container = null)
     {
          $this->container = $container?$container: new core_ses();
     }
     /**
      * Execute email send
      * @param array $to
      * @param string $subject
      * @param string $htmlContent
      * @param array $from [email,name(optional)]
      * @param array $bcc
      * @param string $replyTo
      * @return bool
      */
     public function send(array $to, $subject, $htmlContent, $from = null, $bcc = null, $replyTo = null){
          // $to = ['infocenter@mytechhigh.com'];//$this->preSend($to);

          $formated_emails = array();
          foreach ($to as $email) {
               $formated_email = 'infocenter+staging+' . str_replace('@', '-', $email) . '@mytechhigh.com';
               array_push($formated_emails, $formated_email);
          }

          $this->container->setTo($formated_emails)
               ->setSubject(req_sanitize::txt_decode($subject))
               ->setContent($htmlContent)
               ->setFrom();

          if($from){
               $this->container->setFrom($from);
          }
          
          if($bcc){
               $this->container->setBcc($bcc);
          }

          if($replyTo){
               $this->container->setReplyTo($replyTo);
          }
          return $this->container->send();
     }

     public function sendRaw($content){
          return $this->container->sendRaw($content);
     }

     public function preSend($recepients){
          if(core_config::isProduction()){
              return $recepients;
          } else if(core_config::isStaging()){
               return $recepients;
          }
          return $this->alterEmail($recepients);
     }

     private function alterEmail($emails){
          $new_emails = [];
          foreach($emails as $email){
               $new_emails[] = core_misc::getTestEmailAddress($email);
          }
          return $new_emails;
     }

     public function enableTracking($value){
          if(method_exists($this->container,'enableTracking')){
               $this->container->enableTracking($value);
          }
     }

     public function getContainer(){
          return $this->container;
     }

}