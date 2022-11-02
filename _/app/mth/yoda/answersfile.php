<?php
namespace mth\yoda;
use core\Database\PdoAdapterInterface;
use core\Injectable;

class answersfile{
    private $id;
    private $student_assesment_id;
    private $mth_file_id;

    public function getFile(){
        return \mth_file::get($this->mth_file_id);
    }
    
    public function getID(){
        return $this->id;
    }

    public static function saveUploadedFiles(ARRAY $fieldNames)
    {
        $error = '';
        foreach ($fieldNames as $field) {
            if (!isset($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if($_FILES[$field]['error'] == UPLOAD_ERR_INI_SIZE){
                $upload_max_size = ini_get('upload_max_filesize');
                error_log('Exceeds maximum file size of '.$upload_max_size);
                $error = 'Exceeds maximum file size of '.$upload_max_size;
                continue;
            }
            
            $fileArr = &$_FILES[$field];
            if ($fileArr['error'] != UPLOAD_ERR_OK) {
                error_log('"' . $fileArr['error'] . '"');
                return ['error'=>1,'data'=>$fileArr['error']];
            }
            if ($file = self::saveFile(
                $fileArr['name'],
                file_get_contents($fileArr['tmp_name']),
                $fileArr['type'])
            ) {
                return ['error'=>0,'data'=>$file];
            }
        }
        return ['error'=>1,'data'=>(!empty($error)? $error:'No File Uploaded' )];
    }

    public static function assignToAnswer(array $ids,$student_assessment_id){        
        return \core_db::runQuery('UPDATE yoda_answers_file SET student_assesment_id='.$student_assessment_id.' where id in('.implode(',',$ids).')');
    }

    /**
     * Save frile from mth_file record and then save from yoda_answers_file for homeroom learning logs
     * mth file holds all hashed value file save to s3
     * @param string $name
     * @param string $content
     * @param string $type
     * @return array
     */
    public static function saveFile($name, $content, $type)
    {
        if (!($mth_file = \mth_file::saveFile($name,$content,$type)) ) {
            error_log('Unable to save attachment file');
            return FALSE;
        }

        if (!\core_db::runQuery('INSERT INTO yoda_answers_file 
                            (`mth_file_id`)
                            VALUES
                            (
                              '.$mth_file->id().'
                            )')
        ) {
            $mth_file->delete();
            error_log('Unable to enter attachment file into database');
            return FALSE;
        }
        return ['file_id' => \core_db::getInsertID(),'hash'=>$mth_file->hash()];
    }

    public static function delete($file_id){
        if($file_id){
            return \core_db::runQuery('DELETE from yoda_answers_file where mth_file_id='.$file_id);
        }
        return false;
    }
    /**
     * getByStudentAssessmentId
     * @param int $student_assesment_id
     * @return getByStudentAssessmentId
     */
    public static function getByStudentAssessmentId($student_assesment_id){
        $sql = "SELECT * from yoda_answers_file where student_assesment_id=".$student_assesment_id;
        return \core_db::runGetObjects($sql,__CLASS__);
    }
}