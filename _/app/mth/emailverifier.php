<?php

use mth\aws\ses;

/**
 * Email Verifier
 * 
 *  @author  Rex
 */
class mth_emailverifier
{
    protected $id;
    protected $email;
    protected $verified;
    protected $user_id;
    protected $date_created;
    protected $verification_type;

    const TYPE_AFTERAPPLICATION  = 1;
    const TYPE_BATCH = 2;
    const TYPE_CHANGEEMAIL = 3;


    protected static $types = [
        self::TYPE_AFTERAPPLICATION => 'afterapplicationverification',
        self::TYPE_BATCH => 'batchsendout',
        self::TYPE_CHANGEEMAIL => 'changeemailverification'
    ];

    public static function getTypeId($type)
    {
        $prefix = self::getPrefix();
        return $prefix . (self::$types[$type]);
    }

    public static function getPrefix()
    {
        if (core_config::isDevelopment()) {
            return 'dev';
        }

        if (core_config::isStaging()) {
            return 'staging';
        }
        return '';
    }


    public function getType($int = true)
    {

        $prefix = self::getPrefix();

        if ($int) {
            return $this->verification_type;
        }

        return $prefix . (self::$types[$this->verification_type]);
    }

    public function isVerified($int = false)
    {
        if ($int) {
            return $this->verified;
        }
        return (bool) (int) $this->verified;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getUser() {
        $user_id = $this->user_id;
        return core_db::runGetObjects("SELECT * from core_user where user_id=$user_id", "core_user");
    }

    public function getDateCreated($format = null)
    {
        if ($format) {
            return date($format, strtotime($this->date_created));
        }
        return strtotime($this->date_created);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }


    public function getUnverified()
    {
        return core_db::runGetObjects("SELECT * from mth_emailverifier where verfied=0", __class__);
    }

    public static function init(
        array $batchverification,
        array $afterapplication,
        array $changeemail,
        $create = false
    ) {
        $ses = new ses();


        /**
         * After Application
         * For 2018-2019 
         */
        $afterapplication_id =  self::getTypeId(self::TYPE_AFTERAPPLICATION);

        core_setting::init(
            $afterapplication_id . 'subject',
            'EmailVerification',
            $afterapplication['subject'],
            core_setting::TYPE_TEXT,
            true,
            'New Application Email Subject'
        );

        core_setting::init(
            $afterapplication_id . 'content',
            'EmailVerification',
            $afterapplication['content'],
            core_setting::TYPE_HTML,
            true,
            'New Application Email Content',
            'AWS SES after brand new application is submitted'
        );


        /**
         * Batch Verification Email
         */
        $batchsend_id =  self::getTypeId(self::TYPE_BATCH);

        core_setting::init(
            $batchsend_id . 'subject',
            'EmailVerification',
            $batchverification['subject'],
            core_setting::TYPE_TEXT,
            true,
            'Batch Send Out Subject'
        );

        core_setting::init(
            $batchsend_id . 'content',
            'EmailVerification',
            $batchverification['content'],
            core_setting::TYPE_HTML,
            true,
            'Batch Send Out Content',
            'After uploading emails to AWS, send this out to all 2018-19 parents'
        );

        /**
         * After email change
         */
        $emailchange_id = self::getTypeId(self::TYPE_CHANGEEMAIL);

        core_setting::init(
            $emailchange_id . 'subject',
            'EmailVerification',
            $changeemail['subject'],
            core_setting::TYPE_TEXT,
            true,
            'Email Change Subject'
        );

        core_setting::init(
            $emailchange_id . 'content',
            'EmailVerification',
            $changeemail['content'],
            core_setting::TYPE_HTML,
            true,
            'Email Change Content',
            ''
        );


        if (!core_config::isDevelopment() && $create) {
            $ses->initCustomVerification(
                $afterapplication_id,
                $afterapplication['subject'],
                $afterapplication['content'],
                $afterapplication['failurl'],
                $afterapplication['successurl']
            );

            $ses->initCustomVerification(
                $batchsend_id,
                $batchverification['subject'],
                $batchverification['content'],
                $batchverification['failurl'],
                $batchverification['successurl']
            );

            $ses->initCustomVerification(
                $emailchange_id,
                $changeemail['subject'],
                $changeemail['content'],
                $changeemail['failurl'],
                $changeemail['successurl']
            );
        }
    }


    public static function findEmail($email, $verified = 0)
    {
        $db = new core_db();
        return $db->getObject(
            'SELECT * 
                        FROM mth_emailverifier 
                        WHERE email="' . $db->escape_string(strtolower($email)) . '" limit 1',
            'mth_emailverifier'
        );
    }

    public static function getByUserId($id)
    {
        $db = new core_db();
        return $db->getObject(
            'SELECT * 
                        FROM mth_emailverifier 
                        WHERE user_id=' . $id . ' order by date_created desc limit 1',
            'mth_emailverifier'
        );
    }

    public static function getBatchByUserId($idArray)
    {
        $db = new core_db();
        return $db->runGetObjects(
            'SELECT * 
                        FROM mth_emailverifier 
                        WHERE user_id IN(' . implode(',', $idArray) . ') order by date_created asc',
            'mth_emailverifier'
        );
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function update($email)
    {
        if (!self::validateEmail($email)) {
            core_notify::addError('Invalid email: ' . $email);
            return false;
        }

        if ($verifier = self::findEmail($email)) {
            if ($verifier->isVerified()) {
                core_notify::addError('The email address you entered has already been verified.');
                return false;
            }
            $db = new core_db();
            $db->query('UPDATE mth_emailverifier set verified=1 where id=' . $verifier->getId());
            return core_user::getUserById($verifier->getUserId());
        } else {
            core_notify::addError('The email address you entered does not match.');
            return false;
        }
    }

    public static function insert($email, $user_id, $type, $code = null)
    {
        $db = new core_db();
        $db->query("REPLACE INTO mth_emailverifier(email,user_id,verification_type, code) values('$email',$user_id,$type, '$code')");
    }

    public static function getByEmailCode($email, $code)
    {
        $db = new core_db();
        return $db->getObject(
            'SELECT * 
                        FROM mth_emailverifier 
                        WHERE email="' . $db->escape_string(strtolower($email)) . '" and code="'.$code.'" limit 1',
            'mth_emailverifier'
        );
    }

    public static function activateUser($email, $code)
    {
        if ($verifier = self::findEmail($email)) {
            $db = new core_db();
            $db->query('UPDATE mth_emailverifier set verified=1 where code='.$code.' id=' . $verifier->getId());
            return core_user::getUserById($verifier->getUserId());
        } else {
            core_notify::addError('The email address you entered does not match.');
            return false;
        }
    }
}
