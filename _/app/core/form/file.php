<?php

/**
 * Description of file
 *
 * @author abe
 */
class core_form_file
{
    protected $fileArr;

    public function __construct($fileFieldName)
    {
        if (isset($_FILES[$fileFieldName])) {
            $this->fileArr = $_FILES[$fileFieldName];
        }
    }

    public function count()
    {
        return count($this->fileArr['name']);
    }

    public function name($num = NULL)
    {
        return $this->handleArrayField('name', $num);
    }

    public function type($num = NULL)
    {
        return $this->handleArrayField('type', $num);
    }

    public function error($num = NULL)
    {
        return $this->handleArrayField('error', $num);
    }

    public function size($num = NULL)
    {
        return $this->handleArrayField('size', $num);
    }

    public function tmp_name($num = NULL)
    {
        return $this->handleArrayField('tmp_name', $num);
    }

    public function success($num = null)
    {
        return $this->error($num) === UPLOAD_ERR_OK;
    }

    public function failed($num = null)
    {
        return !$this->fileArr || !in_array($this->error($num), array(UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE));
    }

    public function move($destination, $num = null)
    {
        return move_uploaded_file($this->tmp_name($num), $destination);
    }

    public function contents($num = null)
    {
        return file_get_contents($this->tmp_name($num));
    }

    protected function handleArrayField($field, $num = NULL)
    {
        if (!$this->fileArr || !isset($this->fileArr[$field])) {
            return NULL;
        } elseif (is_array($this->fileArr[$field])) {
            return $this->fileArr[$field][(int)$num];
        } else {
            return $this->fileArr[$field];
        }
    }
}
