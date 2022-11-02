<?php

/**
 * mth_packet_file
 *
 * @author abe
 */
class mth_packet_file
{
    protected $file_id;
    protected $packet_id;
    protected $mth_file_id;
    protected $kind;

    //eventually remove:
    protected $name;
    protected $type;
    protected $item1;
    protected $item2;
    protected $year;


    /** @var  mth_file */
    protected $file;

    const KIND_SIG = 'SIG';

    private static $_cache = array();

    public function __construct()
    {
        if($this->mth_file_id){
            $this->file = mth_file::get($this->mth_file_id);
        }elseif($this->name){
            $this->convert();
        }
    }

    protected function convert(){
        if(($mth_file = mth_file::convertPacketFile($this->name,$this->type,$this->item1,$this->item2,$this->year))){
            $this->name = '';
            core_db::runQuery('UPDATE mth_packet_file SET name="", mth_file_id='.$mth_file->id().' WHERE file_id='.$this->getID());
            $this->mth_file_id = $mth_file->id();
            $this->file = $mth_file;
        }
    }

    public function getID()
    {
        return (int)$this->file_id;
    }

    public function getPacketID()
    {
        return (int)$this->packet_id;
    }

    public function getKind()
    {
        return $this->kind;
    }

    public function getName()
    {
        return $this->file->name();
    }

    public function getUniqueName()
    {
        return $this->file->unique_name();
    }

    public function getType()
    {
        return $this->file->type();
    }

    public function getHash()
    {
        return $this->file->hash();
    }

    public function delete()
    {
        $this->file->delete();
        return core_db::runQuery('DELETE FROM mth_packet_file WHERE file_id=' . $this->getID());
    }

    public function deleteById($file_id)
    {
        return core_db::runQuery('DELETE FROM mth_packet_file WHERE file_id=' . $file_id);
    }

    /**
     * @param mth_packet $packet
     * @return mth_packet_file[]|bool
     */
    public static function getPacketFiles(mth_packet $packet)
    {
        $cache = &self::$_cache[$packet->getID()];
        if (!isset($cache)) {
            $cache = core_db::runGetObjects('SELECT * 
                                        FROM mth_packet_file 
                                        WHERE packet_id=' . $packet->getID(),
                'mth_packet_file');
        }
        return $cache;
    }

    /**
     *
     * @param mth_packet $packet
     * @param string $kind
     * @param bool $single
     * @return mth_packet_file|mth_packet_file[] of mth_packet_file objects
     */
    public static function getPacketFile(mth_packet $packet, $kind, $single = true)
    {
        $files = self::getPacketFiles($packet);
        $returnArr = array();
        foreach ($files as $file) {
            /* @var $file mth_packet_file */
            if ($file->getKind() === $kind) {
                if ($single) {
                    return $file;
                }
                $returnArr[] = $file;
            }
        }
        return $returnArr;
    }

    /**
     *
     * @param int $file_id
     * @return mth_packet_file|false
     */
    public static function getByID($file_id)
    {
        return core_db::runGetObject('SELECT * FROM mth_packet_file WHERE file_id=' . (int)$file_id, 'mth_packet_file');
    }

    /**
     * @param $item
     * @param $year
     * @return false|mth_packet_file
     */
    public static function getByItem($item,$year){
        $item = core_db::escape($item);
        return core_db::runGetObject(sprintf('SELECT * FROM mth_packet_file 
                            WHERE (item1="%s" OR item2="%s")
                              AND year="%d"',
                            $item,
                            $item,
                            $year),
                        self::class);
    }

    /**
     *
     * @param array $fieldNames visually friendly file field names. Must be the name of the file field and will be used as the file display. Field names must not have square brackets ([]).
     * @param mth_packet $packet
     * @param bool $deletePrevious
     * @return bool
     */
    public static function saveUploadedFiles(ARRAY $fieldNames, mth_packet $packet, $deletePrevious = true)
    {
        foreach ($fieldNames as $field) {
            if (!isset($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if($_FILES[$field]['error'] == UPLOAD_ERR_INI_SIZE){
                $upload_max_size = ini_get('upload_max_filesize');
                error_log('Exceeds maximum file size of '.$upload_max_size);
                continue;
            }
            
            $fileArr = &$_FILES[$field];
            if ($fileArr['error'] != UPLOAD_ERR_OK) {
                error_log('"' . $fileArr['error'] . '"');
                return FALSE;
            }
            if (!self::saveFile(
                $fileArr['name'],
                file_get_contents($fileArr['tmp_name']),
                $fileArr['type'],
                cms_content::sanitizeText($field),
                $packet,
                $deletePrevious)
            ) {
                return false;
            }
        }
        return TRUE;
    }

    public static function saveFile($name, $content, $type, $kind, mth_packet $packet, $deletePrevious = true)
    {
        if (!($mth_file = mth_file::saveFile($name,$content,$type)) ) {
            error_log('Unable to save packet file');
            return FALSE;
        }
        if (!core_db::runQuery('INSERT INTO mth_packet_file 
                            (packet_id, `kind`, `mth_file_id`)
                            VALUES
                            (
                              ' . $packet->getID() . ',
                              "' . core_db::escape($kind) . '",
                              '.$mth_file->id().'
                            )')
        ) {
            $mth_file->delete();
            error_log('Unable to enter packet file into database');
            return FALSE;
        }
        if (!$deletePrevious) {
            return true;
        }
        $insertID = core_db::getInsertID();
        unset(self::$_cache[$packet->getID()]);
        if (($fileArr = self::getPacketFiles($packet))) {
            foreach ($fileArr as $file) {
                /* @var $file mth_packet_file */
                if ($file->getKind() == $kind && $file->getID() != $insertID) {
                    $file->delete();
                }
            }
        }
        unset(self::$_cache[$packet->getID()]);
        return true;
    }


    public function getContents()
    {
        return $this->file->contents();
    }

    /**
     * @return mth_file
     */
    public function getFile(){
        return $this->file;
    }
}
