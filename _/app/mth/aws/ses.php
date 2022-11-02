<?php

namespace mth\aws;

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

class ses
{
    const SET_REGION = 'aws_files_bucket_region',
        SET_KEY_ID = 'aws_key_id',
        SET_KEY_SECRET = 'aws_key_secret';

    /** @var  S3Client */
    protected $ses_client;
    protected $config_set = null;

    /**
     * ses constructor.
     */
    public function __construct()
    {
        $this->ses_client = new SesClient([
            'version'     => 'latest',
            'region'      => \core_setting::get(self::SET_REGION, 'mthawss3')->getValue(),
            'credentials' => [
                'key'    => \core_setting::get(self::SET_KEY_ID, 'mthawss3')->getValue(),
                'secret' => \core_setting::get(self::SET_KEY_SECRET, 'mthawss3')->getValue(),
            ],
        ]);
    }
    public function setConfigSet($config)
    {
        if ($config) {
            $this->config_set = $config;
        }
    }

    public function send(
        array $to = null,
        $from,
        $subject,
        $content,
        array $bcc = null,
        array $replyto = null,
        array $cc = null
    ) {
        try {
            $params = [
                'Destination' => [],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'UTF-8',
                            'Data' => $content,
                        ]
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => $subject,
                    ],
                ],
                'Source' =>  $from
            ];

            if (!is_null($to)) {
                $params['Destination']['ToAddresses'] = $to;
            }

            if (!is_null($bcc)) {
                $params['Destination']['BccAddresses'] = $bcc;
            }

            if (!is_null($cc)) {
                $params['Destination']['CcAddresses'] = $cc;
            }

            if (!is_null($replyto)) {
                $params['ReplyToAddresses'] = $replyto;
            }

            if (!is_null($this->config_set)) {
                $params['ConfigurationSetName'] = $this->config_set;
            }

            $result =  $this->ses_client->sendEmail($params);

            $messageId = $result->get('MessageId');
            return true;
        } catch (SesException $error) {
            error_log("The email was not sent. Error message: " . $error->getAwsErrorMessage() . "\n");

            global $bugsnag;
            $bugsnag->registerCallback(function ($report) use ($params) {
                $report->setMetaData([
                    'params' => [
                        $params
                    ]
                ]);
            });
            $bugsnag->notifyException($error);

            return false;
        }
    }

    public function sendRaw($raw_message, $additional_params = [])
    {
        try {
            $params = array_merge(['RawMessage' => [
                'Data' => $raw_message
            ]], $additional_params);

            if (!is_null($this->config_set)) {
                $params['ConfigurationSetName'] = $this->config_set;
            }

            $result =  $this->ses_client->sendRawEmail($params);

            $messageId = $result->get('MessageId');
            return true;
        } catch (SesException $error) {
            error_log("The email was not sent. Error message: " . $error->getAwsErrorMessage() . "\n");
            return false;
        }
    }

    public function generateActivationCode(): string
    {
        return bin2hex(random_bytes(16));
    }

    function sendActivationEmail(string $email, string $activation_code)
    {
        // create the activation link
        $activation_link = $_SERVER['HTTP_HOST'] . "/activate?email=".urlencode($email)."&activation_code=$activation_code";

        // set email subject & body
        $subject = \core_setting::get('stagingafterapplicationverificationsubject', 'EmailVerification');
        $content = \core_setting::get('stagingafterapplicationverificationcontent', 'EmailVerification');
        $message = <<<MESSAGE
                $content
                $activation_link
                MESSAGE;

        return $this->send([$email],
            \core_setting::getSiteEmail()->getValue(),
            $subject,
            $message,
                );

    }

    public function sendConfirmationEmail($email) {
        // set email subject & body
        $subject = \core_setting::get('applicationAcceptedEmailSubject', 'Applications');
        $message = \core_setting::get('applicationAcceptedEmailContent', 'Applications');
        return $this->send([$email],
            \core_setting::getSiteEmail()->getValue(),
            $subject,
            $message,
                );

    }

    public function verifyEmail($email, $template)
    {
        try {
            $result = $this->ses_client->sendCustomVerificationEmail([
                'EmailAddress' => $email,
                'TemplateName' => $template
            ]);
            return true;
        } catch (SesException $error) {
            error_log("Verification Error: " . $error->getAwsErrorMessage() . "\n");
            return false;
        }
    }

    public function getVerifiedEmails()
    {
        try {
            return $this->ses_client->listVerifiedEmailAddresses()->get('VerifiedEmailAddresses');
        } catch (SesException $error) {
            error_log("getVerifiedEmails Error: " . $error->getAwsErrorMessage() . "\n");
            return false;
        }
    }

    public function createCustomEmailTemplate($name, $subject, $content, $failurl, $successurl)
    {
        try {
            $this->ses_client->createCustomVerificationEmailTemplate([
                'FailureRedirectionURL' => $failurl, // REQUIRED
                'FromEmailAddress' => \core_setting::getSiteEmail()->getValue(), // REQUIRED
                'SuccessRedirectionURL' => $successurl, // REQUIRED
                'TemplateContent' => $content, // REQUIRED
                'TemplateName' => $name, // REQUIRED
                'TemplateSubject' => $subject, // REQUIRED
            ]);
            return true;
        } catch (SesException $error) {
            error_log("createCustomEmailTemplate Error: " . $error->getAwsErrorMessage() . "\n");
            return false;
        }
    }

    public function getCustomVerification($name, $test = false)
    {
        if ($test) {
            return $this->ses_client->getCustomVerificationEmailTemplate([
                'TemplateName' => $name
            ]);
        }

        try {
            return $this->ses_client->getCustomVerificationEmailTemplate([
                'TemplateName' => $name
            ]);
        } catch (SesException $error) {
            error_log("createCustomEmailTemplate Error: " . $error->getAwsErrorMessage() . "\n");
            return false;
        }
    }

    public function getCustomVerifications()
    {
        return $this->ses_client->listCustomVerificationEmailTemplates();
    }

    public function updateCustomVerification($name, $params = [])
    {
        try {
            $_params = [
                // 'FailureRedirectionURL' => $failurl,
                // 'SuccessRedirectionURL' =>  $successurl,
                // 'TemplateContent' => $content,
                'TemplateName' => $name, // REQUIRED
                // 'TemplateSubject' => $subject,
                'FromEmailAddress' => \core_setting::getSiteEmail()->getValue()
            ];
            $result = $this->ses_client->updateCustomVerificationEmailTemplate(
                array_merge($_params, $params)
            );
            return true;
        } catch (SesException $error) {
            error_log("updateCustomVerification Error: " . $error->getAwsErrorMessage() . "\n");
            return false;
        }
    }

    public function initCustomVerification($name, $subject, $content, $failurl, $successurl)
    {
        if ($this->getCustomVerification($name) === false) {
            $this->createCustomEmailTemplate($name, $subject, $content, $failurl, $successurl);
        } else {
            $this->updateCustomVerification($name, [
                'FailureRedirectionURL' => $failurl,
                'SuccessRedirectionURL' => $successurl,
                'TemplateContent' => $content,
                'TemplateSubject' => $subject
            ]);
        }
    }

    public function deleteCustomVerification($name)
    {
        $result = $this->ses_client->deleteCustomVerificationEmailTemplate([
            'TemplateName' => $name, // REQUIRED
        ]);
    }

    public function getSiteFrom()
    {
        if ($SMTPaddress = \core_email::SMTPaddress()) {
            return '"' . \core_setting::getSiteName()->getValue() . '"<' . $SMTPaddress->getValue() . '>';
        }
        return '"' . \core_setting::getSiteName()->getValue() . '"<' . \core_setting::getSiteEmail()->getValue() . '>';
    }
}
