<?php

/**
 * for storing errors recived durring canvas interaction
 *
 * @author abe
 */
class mth_canvas_error extends core_model
{
    protected $error_id;
    protected $error_message;
    protected $time;
    protected $command;
    protected $post_fields;
    protected $full_response;
    protected $flag;

    protected static $cache = array();

    /**
     *
     * @param string $message
     * @param string $command
     * @param mixed $unserialized_response
     * @return mth_canvas_error
     */
    public static function log($message, $command, $unserialized_response, $unserialized_post_fields = NULL)
    {
        core_db::runQuery(sprintf('INSERT INTO mth_canvas_error 
                              (error_message, time, command, full_response, post_fields) 
                              VALUES ("%s", NOW(), "%s", "%s", "%s")',
            core_db::escape(req_sanitize::txt($message)),
            core_db::escape($command),
            core_db::escape(htmlentities(serialize($unserialized_response), ENT_HTML401)),
            ($unserialized_post_fields
                ? core_db::escape(htmlentities(serialize($unserialized_post_fields), ENT_HTML401)) :
                '')
        ));
        return self::getByID(core_db::getInsertID());
    }

    /**
     *
     * @param int $error_id
     * @return mth_canvas_error
     */
    public static function getByID($error_id)
    {
        $error = &self::$cache['getByID'][(int)$error_id];
        if (!isset($error)) {
            $error = core_db::runGetObject('SELECT * FROM mth_canvas_error WHERE error_id=' . (int)$error_id,
                'mth_canvas_error');
        }
        return $error;
    }

    public function id()
    {
        return (int)$this->error_id;
    }

    public function message()
    {
        return $this->error_message;
    }

    public function command()
    {
        return $this->command;
    }

    public function time($format = NULL)
    {
        return self::getDate($this->time, $format);
    }

    public function response()
    {
        if (($response = @unserialize($this->full_response))) {
            return $response;
        }
        return $this->full_response;
    }

    public function post_fields()
    {
        if (($post_fields = @unserialize($this->post_fields))) {
            return $post_fields;
        }
        return $this->post_fields;
    }

    public function print_response()
    {
        print_r($this->response());
    }

    public function print_post_fields()
    {
        print_r($this->post_fields());
    }

    public function flag($flag = NULL)
    {
        if (!is_null($flag)) {
            $this->flag = $flag ? 1 : 0;
            core_db::runQuery('UPDATE mth_canvas_error SET `flag`=' . $this->flag . ' WHERE error_id=' . $this->id());
        }
        return (bool)$this->flag;
    }

    /**
     *
     * @param bool $reset
     * @return mth_canvas_error
     */
    public static function each($reset = false)
    {
        $result = &self::$cache['each'];
        if (!isset($result)) {
            $result = core_db::runQuery('SELECT * FROM mth_canvas_error ORDER BY `time` DESC');
        }
        if (!$reset && ($canvas_log = $result->fetch_object('mth_canvas_error'))) {
            return $canvas_log;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function count()
    {
        $result = &self::$cache['each'];
        /* @var $result mysqli_result */
        if (!isset($result)) {
            self::each(true);
        }
        return $result->num_rows;
    }

    public static function clear($clearFlagged = false)
    {
        return core_db::runQuery('DELETE FROM mth_canvas_error 
                              WHERE ' . ($clearFlagged ? '1' : '`flag`=0'));
    }
}
