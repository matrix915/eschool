<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 9/26/16
 * Time: 11:18 AM
 */

namespace mth\aws;


use Aws\S3\S3Client;
use GuzzleHttp\Promise\PromiseInterface;

class s3
{
    const SET_REGION = 'aws_files_bucket_region',
        SET_KEY_ID = 'aws_key_id',
        SET_KEY_SECRET = 'aws_key_secret',
        SET_BUCKET = 'aws_files_bucket';

    /** @var  S3Client */
    protected $s3_client;

    /** @var  PromiseInterface[] */
    protected $promises = array();
    
    protected $bucket;

    /**
     * s3 constructor.
     */
    public function __construct()
    {
        $this->s3_client = new S3Client([
            'version'     => '2006-03-01',
            'region'      => \core_setting::get(self::SET_REGION,self::class)->getValue(),
            'credentials' => [
                'key'    => \core_setting::get(self::SET_KEY_ID,self::class)->getValue(),
                'secret' => \core_setting::get(self::SET_KEY_SECRET,self::class)->getValue(),
            ],
        ]);
        $this->bucket = \core_setting::get(self::SET_BUCKET,self::class)->getValue();
    }

    /**
     * @param $file_path
     * @param $content
     */
    public function uploadAsync($file_path,$content)
    {
        $this->promises[$file_path] = $this->s3_client->uploadAsync(
            $this->bucket,
            $file_path,
            $content
        );
    }

    public function promiseUpload($file_path,$content){
        $this->promises[$file_path] = $this->s3_client->uploadAsync(
            $this->bucket,
            $file_path,
            $content
        )->then(function($value){
            return $value->get('ObjectURL');
        },function($reason){
            error_log($reason);
            return false;
        });
    }

    public function promiseWait($file_path){
        return $this->promises[$file_path]->wait();
    }
    

    public function uploadAsyncWait()
    {
        foreach($this->promises as $promise){
            $promise->wait();
        }
    }

    public function getContent($file_path){
        $result = $this->s3_client->getObject([
            'Bucket'=>$this->bucket,
            'Key'=>$file_path
        ]);
        return $result->get('Body');
    }

    public function getUrl($file_path){
        $result = $this->s3_client->getObjectUrl( 
            $this->bucket,
            $file_path
        );
        return  $result;
    }

    public function getContentLength($file_path){
        $result = $this->s3_client->headObject([
            'Bucket'=>$this->bucket,
            'Key'=>$file_path
        ]);
        return $result->get('ContentLength');
    }

    /**
     * @param $file_path
     */
    public function delete($file_path){
        $this->s3_client->deleteObject([
            'Bucket'=>$this->bucket,
            'Key'=>$file_path
        ]);
    }

}