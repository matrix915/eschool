<?php

/**
 * Should move all interactions with dropbox into this class
 *
 * @author abe
 */
class mth_dropbox
{
    protected static $dbx;
    protected static $errors = array();

    protected static function error($message)
    {
        self::$errors[] = $message;
    }

    public static function errors()
    {
        $errors = self::$errors;
        self::$errors = array();
        return $errors;
    }

    /**
     *
     * @return \Kunnu\Dropbox\Dropbox|false
     */
    protected static function dbx()
    {
        if (self::$dbx === NULL) {
            if (!($accessToken = core_setting::get(DROPBOX_TOKEN_VAR, 'DropBox'))) {
               self::error('DropBox accessToken not set');
               return false;
           }
           $dropbox_app = new \Kunnu\Dropbox\DropboxApp(DROPBOX_CLIENT_ID,DROPBOX_CLIENT_SECRET,$accessToken->getValue());
           self::$dbx = new Kunnu\Dropbox\Dropbox($dropbox_app);
       }
       return self::$dbx;
    }

    public static function uploadFileFromString($path, $content)
    {
        if (!($dpx = self::dbx())) {
            self::error('Unable to connect to DropBox');
            return false;
        }
        $tmp_file = tempnam(sys_get_temp_dir(),uniqid());
        try {
            file_put_contents($tmp_file,$content);
            $dpx->upload($tmp_file,$path,['mode'=>'overwrite']);
        } catch (Exception $ex) {
            error_log('mth_dropbox::uploadFileFromString() error: ' . $ex->getMessage());
            self::error('Unable to upload file to DropBox');
            return false;
        }
        return true;
    }

}
