<?php

/**
 * mth_packet_file
 *
 * @author abe
 */
class mth_file extends core_model
{
    protected $file_id;
    protected $name;
    protected $type;
    protected $item1;
    protected $item2;
    protected $item3;
    protected $year;
    protected $is_new_upload_type;

    const PATH = '/_/mth_files/';

    protected $allowed_files = [
        'text/plain' => 'txt',
        'application/x-shockwave-flash' => 'swf',
        'video/x-flv' => 'flv',

        // images
        'image/png' =>  'png',
        'image/jpeg' => 'jpg',
        'image/gif' =>  'gif',
        'image/bmp' => 'bmp',
        'image/vnd.microsoft.icon' => 'ico',
        'image/tiff' => 'tiff',
        'image/svg+xml' => 'svg',

        // archives
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar',
        'application/vnd.ms-cab-compressed' => 'cab',

        // audio/video
        'audio/mpeg' => 'mp3',

        // adobe
        'application/pdf' => 'pdf',
        'image/vnd.adobe.photoshop' => 'psd',
        'application/postscript' => 'ai',
        // 'application/postscript' => 'eps',
        // 'application/postscript' => 'ps',

        // ms office
        'application/msword' => 'doc',
        'application/rtf' => 'rtf',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.ms-powerpoint' => 'ppt',

        // open office
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods'
    ];

    public function id()
    {
        return (int) $this->file_id;
    }

    public function name($append_extension = false)
    {
        if ($this->type == 'image/svg+xml') {
            return $this->name . '.png';
        } elseif ($append_extension) {
            $ext = isset($this->allowed_files[strtolower($this->type)]) ? '.' . $this->allowed_files[strtolower($this->type)] : '';
            return $this->name . $ext;
        }


        return $this->name;
    }

    public function unique_name()
    {
        if (!($id = $this->id())) {
            $id = uniqid();
        }
        return trim(preg_replace('/(\.[^\.]{2,5})$/', '-' . $id . '$1', $this->name()));
    }

    public function type()
    {
        // if ($this->type == 'image/svg+xml' && class_exists('Imagick')) {
        //     return 'image/png';
        // }
        return $this->type;
    }

    public function delete()
    {
        if (is_file(self::path() . $this->year . '/' . $this->item1)) {
            unlink(self::path() . $this->year . '/' . $this->item1);
            unlink(self::path() . $this->year . '/' . $this->item2);
        } else {
            try {
                $s3 = new \mth\aws\s3();
                $s3->delete($this->year . '/' . $this->item1);
                $s3->delete($this->year . '/' . $this->item2);
            } catch (Exception $e) {
                error_log($e);
            }
        }
        return core_db::runQuery('DELETE FROM mth_file WHERE file_id=' . $this->id());
    }

    public function moveToS3()
    {
        if (!is_file(self::path() . $this->year . '/' . $this->item1)) {
            return true;
        }
        try {
            $s3 = new \mth\aws\s3();
            $s3->uploadAsync(
                $this->year . '/' . $this->item1,
                file_get_contents(self::path() . $this->year . '/' . $this->item1)
            );
            $s3->uploadAsync(
                $this->year . '/' . $this->item2,
                file_get_contents(self::path() . $this->year . '/' . $this->item2)
            );
            $s3->uploadAsyncWait();
            unlink(self::path() . $this->year . '/' . $this->item1);
            unlink(self::path() . $this->year . '/' . $this->item2);
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return true;
    }

    public static function cleanUp($limit = 10)
    {
        $count = 1;
        $year = 2009;
        $current_year = (int) date('Y');
        $return = [];
        while ($count < $limit && ($year += 1) <= $current_year) {
            $dir = self::path() . DIRECTORY_SEPARATOR . $year;
            if (!is_dir($dir)) {
                continue;
            }
            $empty = true;
            foreach (scandir($dir) as $file) {
                if ($file[0] == '.') {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    $empty = false;
                    $return[] = $path . ' is a directory... continuing...';
                    continue;
                }
                $return[] = self::handleFileItem($file, $year, $empty);
                $count += 1;
                if ($count > $limit) {
                    $return[] = 'Reached limit. Ending.';
                    return implode(PHP_EOL, $return);
                }
            }
            if ($empty) {
                rmdir($dir);
                $return[] = $dir . ' was deleted because it was empty';
            }
        }
        return implode(PHP_EOL, $return);
    }

    protected static function handleFileItem($item, $year, &$empty)
    {
        $file = self::getByItem($item, $year);
        if (!$file && ($packet_file = mth_packet_file::getByItem($item, $year))) {
            $file = $packet_file->getFile();
        }
        if (!$file) {
            unlink(self::path() . $year . DIRECTORY_SEPARATOR . $item);
            return $year . DIRECTORY_SEPARATOR . $item . ' deleted. No matching file.';
        }
        if ($file->moveToS3()) {
            return $year . DIRECTORY_SEPARATOR . $item . ' - file:' . $file->id() . ' Moved to S3';
        } else {
            $empty = false;
            return $year . DIRECTORY_SEPARATOR . $item . ' - file:' . $file->id() . ' UNABLE TO MOVE TO S3!!!';
        }
    }

    public function hash()
    {
        return $this->file_id . '-' . $this->item1;
    }

    public function isNewUploadType()
    {
        return $this->is_new_upload_type == 1;
    }

    /**
     *
     * @param int $file_id
     * @return mth_file
     */
    public static function get($file_id)
    {
        $file = &self::cache(__CLASS__, 'get-' . (int) $file_id);
        if (!isset($file)) {
            $file = core_db::runGetObject(
                'SELECT * FROM mth_file WHERE file_id=' . (int) $file_id,
                'mth_file'
            );
        }
        return $file;
    }

    /**
     * @param $item
     * @param $year
     * @return false|mth_file
     */
    public static function getByItem($item, $year)
    {
        $item = core_db::escape($item);
        return core_db::runGetObject(
            sprintf(
                'SELECT * FROM mth_file 
                            WHERE (item1="%s" OR item2="%s")
                              AND year="%d"',
                $item,
                $item,
                $year
            ),
            self::class
        );
    }

    /**
     *
     * @param string $hash
     * @return mth_file
     */
    public static function getByHash($hash)
    {
        $file = &self::cache(__CLASS__, 'getByHash-' . $hash);
        if (!isset($file)) {
            $idArr = explode('-', $hash);
            $file = core_db::runGetObject(
                'SELECT * FROM mth_file 
                                      WHERE file_id=' . (int) $idArr[0] . '
                                        AND `item1`="' . core_db::escape($idArr[1]) . '"',
                'mth_file'
            );
        }
        return $file;
    }

    /**
     *
     * @param string $fieldName visually friendly file field name. Must be the name of the file field. Field names must not have square brackets ([]).
     * @return mth_file|false
     */
    public static function saveUploadedFile($fieldName)
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] == UPLOAD_ERR_NO_FILE) {
            return NULL;
        }
        $fileArr = &$_FILES[$fieldName];
        if ($fileArr['error'] != UPLOAD_ERR_OK) {
            error_log('"' . $fileArr['error'] . '"');
            return FALSE;
        }
        return self::saveFile(
            $fileArr['name'],
            file_get_contents($fileArr['tmp_name']),
            $fileArr['type']
        );
    }

    /**
     *
     * @param string $name
     * @param string $content
     * @param string $type
     * @return mth_file|false
     */
    public static function saveFile($name, $content, $type)
    {
        $item1 = uniqid() . md5(time());
        $item2 = md5(time()) . uniqid();
        $year = date('Y');
        $content = base64_encode($content);
        $contentArr = str_split($content, (strlen($content) / 2) + 10);

        if (strlen($content) > 500) {
            $contentArr[0] = str_split($contentArr[0], 240);
            $item3 = array_pop($contentArr[0]);
            $contentArr[0] = implode('', $contentArr[0]);
        } else {
            $item3 = '';
        }
        try {
            $s3 = new \mth\aws\s3();
            $s3->uploadAsync($year . '/' . $item1, $contentArr[0]);
            $s3->uploadAsync($year . '/' . $item2, $contentArr[1]);
            $s3->uploadAsyncWait();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }

        return self::insertFile($name, $type, $item1, $item2, $item3, $year);
    }

    /**
     *
     * @param string $name
     * @param string $type
     * @param string $item1
     * @param string $item2
     * @param string $item3
     * @param int $year
     * @return mth_file|false
     */
    protected static function insertFile($name, $type, $item1, $item2, $item3, $year)
    {
        if (core_db::runQuery(sprintf(
            'INSERT INTO mth_file 
                            (`name`, `type`, `item1`, `item2`, `item3`, `year`)
                            VALUES
                            ("%s", "%s", "%s", "%s", "%s", %d)',
            core_db::escape(preg_replace('/[^A-Za-z0-9\. \-_]/', '', $name)),
            core_db::escape($type),
            $item1,
            $item2,
            core_db::escape($item3),
            $year
        ))) {
            return self::get(core_db::getInsertID());
        }
        return FALSE;
    }

    public static function convertPacketFile($name, $type, $item1, $item2, $year)
    {
        return self::insertFile($name, $type, $item1, $item2, '', $year);
    }

    public function contents($decode = true)
    {
        if (is_file(self::path() . $this->year . '/' . $this->item1)) {
            $content = file_get_contents(self::path() . $this->year . '/' . $this->item1) . $this->item3 . file_get_contents(self::path() . $this->year . '/' . $this->item2);
            if ($decode) {
                $content = base64_decode($content);
            }
            $this->moveToS3();
        } else {
            try {
                $content = $this->getContent($decode);
                if ($decode) {
                    $content = base64_decode($content);
                }
            } catch (Exception $e) {
                if (stripos((string) $e, '404 Not Found') === false) {
                    error_log($e);
                }
                return null;
            }
        }
        // if ($this->type == 'image/svg+xml' && strlen($content) > 0 && class_exists('Imagick')) {
        //     $image = new Imagick();
        //     $image->readImageBlob($content);
        //     $image->setImageFormat("png32");
        //     $image->resizeImage(2000, 1000, imagick::FILTER_LANCZOS, 1, true);
        //     $content = (string) $image;
        // }
        return $content;
    }

    private function getContent(&$decode = true)
    {
        $s3 = new \mth\aws\s3();

        if (filter_var($this->item3, FILTER_VALIDATE_URL) && $this->isNewUploadType()) {
            //return $s3->getContent($this->item3);
            $decode = false;
            return file_get_contents($this->item3);
        }
        return $s3->getContent($this->year . '/' . $this->item1) . $this->item3 . $s3->getContent($this->year . '/' . $this->item2);
    }

    private static function path()
    {
        return ROOT . self::PATH;
    }
}
