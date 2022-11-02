<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/6/16
 * Time: 3:51 PM
 */

namespace core;


class Response
{
    const PROTOCOL_VERSION = '1.1';

    const STATUS_OK = 200;
    const STATUS_MOVED_PERMANENTLY = 301;
    const STATUS_FOUND = 302;
    const STATUS_BAD_REQUEST = 400;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_SERVICE_UNAVAILABLE = 503;

    const TYPE_HTML = 'text/html';
    const TYPE_JSON = 'application/json';
    const TYPE_JAVASCRIPT = 'application/javascript';
    const TYPE_CSS = 'text/css';
    const TYPE_JPEG = 'image/jpeg';
    const TYPE_PNG = 'image/png';
    const TYPE_GIF = 'image/gif';
    const TYPE_PDF = 'application/pdf';
    const TYPE_CSV = 'text/csv';
    const TYPE_TXT = 'text/plain';

    const CHARSET_UTF8 = 'utf-8';
    const CHARSET_ASCII = 'us-ascii';

    protected static $statusTexts = [
        self::STATUS_OK => 'OK',
        self::STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
        self::STATUS_FOUND => 'Found',
        self::STATUS_BAD_REQUEST => 'Bad Request',
        self::STATUS_FORBIDDEN => 'Forbidden',
        self::STATUS_NOT_FOUND => 'Not Found',
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable'
    ];

    protected static $requires_charset = [
        self::TYPE_CSS,
        self::TYPE_CSV,
        self::TYPE_HTML,
        self::TYPE_JAVASCRIPT,
        self::TYPE_JSON,
        self::TYPE_TXT
    ];

    protected $status;
    protected $headers = [];

    /**
     * @var callable
     */
    protected $content_provider;


    /**
     * @param view $view
     * @return Response
     */
    public function setView(view $view)
    {
        return $this->setContentProvider(function() use ($view){ $view->printView(); });
    }


    /**
     * @param callable $content_provider
     * @return Response
     */
    public function setContentProvider(callable $content_provider)
    {
        $this->content_provider = $content_provider;
        return $this;
    }

    public function execute(){
        $this->sendHeaders();
        $content_provider = $this->content_provider;
        $content_provider();
        exit();
    }

    public function getContent(){
        ob_start();
        $content_provider = $this->content_provider;
        $content_provider();
        return ob_get_clean();
    }

    /**
     * @param $field
     * @param $value
     * @return Response
     */
    public function setHeader($field, $value){
        $this->headers[trim(strtolower($field))] = trim($value);
        return $this;
    }

    /**
     * @param $field
     * @return Response
     */
    public function removeHeader($field)
    {
        unset($this->headers[trim(strtolower($field))]);
        return $this;
    }

    /**
     * @param $status_code
     * @return Response
     */
    public function setStatus($status_code){
        $this->status = $status_code;
        return $this;
    }

    /**
     * @param $cache_length_in_seconds
     * @return Response
     */
    public function setCacheHeaders($cache_length_in_seconds)
    {
        if($cache_length_in_seconds){
            $this->setHeader('Expires',gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_length_in_seconds));
            $this->setHeader('Cache-Control','max-age='.$cache_length_in_seconds);
            $this->setHeader('Pragma','cache');
            $this->setHeader('User-Cache-Control','max-age='.$cache_length_in_seconds);
        }else{
            unset($this->headers['expires'],$this->headers['user-cache-control']);
            $this->setHeader('Cache-Control','private, max-age=0, no-cache');
            $this->setHeader('Pragma','no-cache');
        }
        return $this;
    }

    /**
     * @param $content_type
     * @param null $download_file_name
     * @param $charset
     * @return Response
     */
    public function setContentTypeHeaders($content_type, $download_file_name=null,$charset=self::CHARSET_UTF8){
        if(in_array($content_type,self::$requires_charset)){
            $this->setHeader('Content-type',$content_type.'; charset='.$charset);
        }else{
            $this->setHeader('Content-type',$content_type);
        }
        if($download_file_name){
            $this->setHeader('Content-Disposition','attachment; filename="'.$download_file_name.'"');
        }else{
            unset($this->headers['content-disposition']);
        }
        return $this;
    }

    public function executeRedirect($url, $status_code=Response::STATUS_FOUND){
        $this->setStatus($status_code);
        $this->setHeader('Location',$url);
        $this->sendHeaders();
        exit();
    }

    /**
     * gets executed in Response->execute() and Response->executeRedirect() methods. Does not get executed in Response->getContent().
     */
    public function sendHeaders(){
        if($this->status){
            header('HTTP/'.self::PROTOCOL_VERSION.' '.$this->status.' '.self::$statusTexts[$this->status]);
            header('Status: '.$this->status.' '.self::$statusTexts[$this->status]);
        }
        foreach($this->headers as $field => $value){
            header($field.': '.$value);
        }
    }
}