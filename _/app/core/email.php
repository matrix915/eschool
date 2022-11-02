<?php
include_once ROOT . '/_/includes/PHPMailer/class.phpmailer.php';

/**
 * for sending emails
 *
 * @author abe
 */
class core_email
{
    protected $HTMLcontent;
    protected $subject;
    protected $to = array();
    protected $success = array();
    protected $bcc = NULL;
    protected $attachmentStrings = array();
    protected $attachments = array();
    protected $replyTo = NULL; 
    protected $from;

    /**
     *
     * @param array $to An array of recipients, can be user_ids, objects with getEmail and getName methods, strings emails, sub associative array with array('Name'=>'Person Name', 'Email'=>'Person email') structure.
     * @param string $subject plain text
     * @param string $htmlContent fixed and sanitzed HTML
     * @param mixed $from [Email,Name]
     */
    public function __construct(ARRAY $to, $subject, $htmlContent, $from = null)
    {
        $this->to = (array)$to;
        $this->subject = $subject;
        $this->HTMLcontent = $htmlContent;
        $this->from = $from;
    }

    public function send()
    {
        $this->prepToArray();
        foreach ($this->to as $toArr) {
            $mail = new PHPMailer();

            $this->setFrom($mail);

            //$mail->AddAddress($toArr['Email'], $toArr['Name']);
            if (core_config::isProduction()) {
                $mail->AddAddress($toArr['Email'], $toArr['Name']);
            } elseif (($user1 = core_user::getUserById(1))) {
                $mail->AddAddress(core_misc::getTestEmailAddress($toArr['Email']));
            }

            
            $this->setBccs($mail);

            $mail->Subject = req_sanitize::txt_decode($this->subject);

            foreach ($this->attachmentStrings as $filename => $content) {
                $mail->AddStringAttachment($content, $filename);
            }

            foreach($this->attachments as $file){
                $file_name = isset($file['name']) && !empty(trim($file['name']))?$file['name']:'';
                $mail->AddAttachment($file['path'],$file_name);
            }

            $mail->IsHTML();

            $mail->Body = $this->HTMLcontent;
            $mail->AltBody = $mail->html2text($this->HTMLcontent);
            $mail->CharSet = 'UTF-8';

            // $mail->SMTPDebug=true;
            // $mail->Debugoutput='error_log';
            if (!($this->success[$toArr['Email']] = $mail->Send())) {
                error_log('PHP Mailer error: ' . $mail->ErrorInfo);
            }
        }
        return count($this->success) == count(array_filter($this->success));
    }

    public function preSend(){
        $mail = new PHPMailer();
        $this->prepToArray();
        foreach ($this->to as $toArr) {
            

            $this->setFrom($mail);

            //$mail->AddAddress($toArr['Email'], $toArr['Name']);
            if (core_config::isProduction()) {
                $mail->AddAddress($toArr['Email'], $toArr['Name']);
            } elseif (($user1 = core_user::getUserById(1))) {
                $mail->AddAddress(core_misc::getTestEmailAddress($toArr['Email']));
            }

            
            $this->setBccs($mail);

            $mail->Subject = req_sanitize::txt_decode($this->subject);

            foreach ($this->attachmentStrings as $filename => $content) {
                $mail->AddStringAttachment($content, $filename);
            }

            foreach($this->attachments as $file){
                $file_name = isset($file['name']) && !empty(trim($file['name']))?$file['name']:'';
                $mail->AddAttachment($file['path'],$file_name);
            }

            $mail->IsHTML();

            $mail->Body = $this->HTMLcontent;
            $mail->AltBody = $mail->html2text($this->HTMLcontent);
            $mail->CharSet = 'UTF-8';

            $mail->SMTPDebug=true;
            $mail->Debugoutput='error_log';
            if (!($this->success[$toArr['Email']] = $mail->preSend())) {
                error_log('PHP Mailer error: ' . $mail->ErrorInfo);
            }
        }
        return count($this->success) == count(array_filter($this->success))?$mail:false;
    }

    protected function setBccs(PHPMailer &$mail){
        if ($this->bcc) {
            if(is_array($this->bcc)){
                foreach($this->bcc as $bcc){
                    $mail->AddBCC($bcc);
                }
            }else{
                $mail->AddBCC($this->bcc);
            }
        }
    }

    protected function setFrom(PHPMailer &$mail)
    {
        if(!is_null($this->from)){
            $mail->SetFrom($this->from[0],$this->from[1]);
        }elseif (($SMTPaddress = self::SMTPaddress())
            && ($SMTPuser = self::SMTPuser())
            && ($SMTPpasswrod = self::SMTPpassword())
            && ($SMTPhost = self::SMTPhost())
            && ($SMTPsecure = self::SMTPsecure())
            && ($SMTPport = self::SMTPport())
        ) {
            $mail->SetFrom($SMTPaddress->getValue(), core_setting::getSiteName()->getValue());
            if($this->replyTo){
                $mail->AddReplyTo($this->replyTo);
            }else{
                $mail->AddReplyTo(core_setting::getSiteEmail()->getValue(), core_setting::getSiteName()->getValue());     
            }
            $mail->IsSMTP();
            $mail->Host = $SMTPhost->getValue();
            $mail->Port = $SMTPport->getValue();
            $mail->SMTPAuth = true;
            $mail->Username = $SMTPuser->getValue();
            $mail->Password = $SMTPpasswrod->getValue();
            $mail->SMTPSecure = $SMTPsecure->getValue();
        } else {
            $mail->SetFrom(core_setting::getSiteEmail()->getValue(), core_setting::getSiteName()->getValue());
        }
    }


    protected function prepToArray()
    {
        $addresses = array();
        foreach ($this->to as $key => &$value) {
            if (is_int($value)) {
                $value = core_user::getUserById($value);
            }
            if (is_object($value) && is_callable(array($value, 'getEmail'))) {
                $value = array(
                    'Email' => $value->getEmail(),
                    'Name' => is_callable(array($value, 'getName')) ? $value->getName() : ''
                );
            }
            if (is_string($value)) {
                $value = array('Email' => $value, 'Name' => '');
            }
            if (!isset($value['Email']) || !core_user::validateEmail($value['Email'])
                || in_array($value['Email'], $addresses)
            ) {
                unset($this->to[$key]);
            }
            $addresses[] = $value['Email'];
        }
    }

    public function setBCC($email)
    {
        if (core_user::validateEmail($email)) {
            $this->bcc = $email;
        }
    }

    public function setBCCBatch(array $emails){
        foreach($emails as $email){
            if (core_user::validateEmail($email)) {
                $this->bcc[] = $email;
            }
        }
    }

    public function addBccs(array $people){
        foreach($people as $person){
            $email = $person->getEmail();
            if (core_user::validateEmail($email)) {
                $this->bcc[] = $email;
            }
        }
    }

    public function setReplyTo($email){
        if (core_user::validateEmail($email)) {
            $this->replyTo = $email;
        }
    }

    public function addStringAttachment($content, $filename)
    {
        $this->attachmentStrings[$filename] = $content;
    }

    /**
     * Undocumented function
     *
     * @param array $files should contain atleast [path] field eg. [path=>'root/..',name=>'file.pdf']
     * @return void
     */
    public function addAttachments($files = array()){
        $this->attachments = $files;
    }

    /**
     * Get and set the site's SMTP email address
     * @param type $set
     * @return core_setting
     */
    public static function SMTPaddress($set = NULL)
    {
        if (!is_null($set) && core_user::validateEmail($set)) {
            return core_setting::init(
                'SMTPaddress',
                'SMTP',
                $set,
                core_setting::TYPE_TEXT,
                true,
                'SMTP Email Address',
                '<p>The email address to use when sending an email.</p>');
        }
        return core_setting::get('SMTPaddress', 'SMTP');
    }

    /**
     * Get and set the site's SMTP user account
     * @param type $set
     * @return core_setting
     */
    public static function SMTPuser($set = NULL)
    {
        if (!is_null($set) && core_user::validateEmail($set)) {
            return core_setting::init(
                'SMTPuser',
                'SMTP',
                $set,
                core_setting::TYPE_TEXT,
                true,
                'SMTP User Account',
                '<p>The SMTP user account to use when sending an email</p>');
        }
        return core_setting::get('SMTPuser', 'SMTP');
    }

    /**
     * Get and set the site's SMTP user account
     * @param type $set
     * @return core_setting
     */
    public static function SMTPpassword($set = NULL)
    {
        if (!is_null($set)) {
            return core_setting::init(
                'SMTPpassword',
                'SMTP',
                $set,
                core_setting::TYPE_TEXT,
                true,
                'SMTP Password',
                '<p>The SMTP password account to use when sending an email</p>');
        }
        return core_setting::get('SMTPpassword', 'SMTP');
    }

    /**
     * Get and set the site's SMTP user account
     * @param type $set
     * @return core_setting
     */
    public static function SMTPhost($set = NULL)
    {
        if (!is_null($set)) {
            return core_setting::init(
                'SMTPhost',
                'SMTP',
                $set,
                core_setting::TYPE_TEXT,
                true,
                'SMTP Host',
                '<p>The SMTP host to use when sending an email</p>');
        }
        return core_setting::get('SMTPhost', 'SMTP');
    }

    /**
     * Get and set the site's SMTP user account
     * @param type $set
     * @return core_setting
     */
    public static function SMTPsecure($set = NULL)
    {
        if (!is_null($set)) {
            return core_setting::init(
                'SMTPsecure',
                'SMTP',
                $set,
                core_setting::TYPE_TEXT,
                true,
                'SMTP Security Method',
                '<p>The SMTP security menthod (ssl) to use when sending an email</p>');
        }
        return core_setting::get('SMTPsecure', 'SMTP');
    }

    /**
     * Get and set the site's SMTP user account
     * @param type $set
     * @return core_setting
     */
    public static function SMTPport($set = NULL)
    {
        if (!is_null($set)) {
            return core_setting::init(
                'SMTPport',
                'SMTP',
                $set,
                core_setting::TYPE_TEXT,
                true,
                'SMTP Port',
                '<p>The SMTP port to use when sending an email</p>');
        }
        return core_setting::get('SMTPport', 'SMTP');
    }


}
