<?php

class mth_resource_settings extends core_model
{
    protected $resource_id;
    protected $resource_name;
    protected $min_grade_level;
    protected $max_grade_level;
    protected $hidden;
    protected $created_at;
    protected $updated_at;
    protected $show_parent;
    protected $image;
    protected $content;
    protected $cost;
    protected $show_cost;
    protected $resource_type;
    protected $is_direct_deduction;

    const TYPE_UNLIMITED = 1;
    const TYPE_OPTIONAL = 2;

    protected static $availabe_types = [
        self::TYPE_OPTIONAL => 'Optional Resource',
        self::TYPE_UNLIMITED => 'Unlimited Resource'
    ];

    protected static $cache = array();

    public static function availableTypes()
    {
        return self::$availabe_types;
    }

    protected function set($field, $value, $force = false)
    {
        parent::set('updated_at', date('Y-m-d H:i:s'));
        return parent::set($field, $value, $force);
    }

    public function save()
    {
        if (!$this->resource_id && !$this->resource_name) {
            return FALSE;
        }

        if (!$this->resource_id) {
            core_db::runQuery('INSERT INTO mth_resource_settings (`resource_name`) VALUES ("UNNAMED")');
            $this->resource_id = core_db::getInsertID();
        }

        return parent::runUpdateQuery('mth_resource_settings', 'resource_id=' . $this->getID());
    }

    public function getID()
    {
        return (int) $this->resource_id;
    }

    public function name($set = NULL, $desc = false)
    {
        if (!is_null($set)) {
            $this->set('resource_name', cms_content::sanitizeText($set));
        }
        return $this->resource_name . ($desc ? ' (Grades ' . $this->gradeSpan() . ')' : '');
    }


    public function set_available($set = NULL)
    {
        $this->set('hidden', (int) !$set);
    }

    public function set_show_cost($set = NULL)
    {
        $this->set('show_cost', (int) $set);
    }

    public function set_show_parent($set = NULL)
    {
        $this->set('show_parent', (int) $set);
    }

    public function set_content($set = NULL)
    {
        $this->set('content', $set);
    }

    public function uploadBanner($file)
    {
        if ($file['error']) {
            return false;
        }

        $new_name = preg_replace('/.*/i', (uniqid() . md5(time())), $file['name']);

        try {
            $s3 = new \mth\aws\s3();
            $s3->uploadAsync('banner' . '/' . $new_name, file_get_contents($file['tmp_name']));
            $s3->uploadAsyncWait();
            return 'banner' . '/' . $new_name;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    public function getBanner($url = true)
    {
        $_banner_url = $this->image();
        if ($_banner_url) {
            try {
                $s3 = new \mth\aws\s3();

                return $url ?
                    $s3->getUrl($_banner_url)
                    : base64_encode($s3->getContent($_banner_url));
            } catch (Exception $e) {
                if (stripos((string) $e, '404 Not Found') === false) {
                    error_log('getBanner Error: unable get image');
                }
                error_log($e);
                return null;
            }
        }
        return null;
    }

    public function set_image($set = NULL)
    {
        $this->set('image', $set);
    }

    public function min_grade_level($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $aviableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set; 
            if (isset($aviableGrades[$index])) {
                $this->set('min_grade_level', (int) $set);
            }
        }
        if ($this->min_grade_level === '0' || $this->min_grade_level === 0) {
            $this->min_grade_level = 'K';
        }
        return ( $this->min_grade_level !== NULL && $this->min_grade_level != -1 ) ? $this->min_grade_level : ( $this->min_grade_level == -1 ? 'Oregon Kindergarten' : 'K' );
    }

    public function max_grade_level($set = NULL)
    {
        if ($set !== NULL) {
            if (!$set) {
                $set = 'K';
            }
            $aviableGrades = mth_student::getAvailableGradeLevels();
            $index = $set == -1 ? 'OR-K' : $set; 
            if (isset($aviableGrades[$index])) {
                $this->set('max_grade_level', (int) $set);
            }
        }
        if ($this->max_grade_level === 0) {
            $this->max_grade_level = 'K';
        }
        return ( $this->max_grade_level !== NULL && $this->max_grade_level != -1 ) ? $this->max_grade_level : ( $this->max_grade_level == -1 ? 'Oregon Kindergarten' : 12 );
    }

    public function __destruct()
    {
        $this->save();
    }

    public function gradeSpan()
    {
        return $this->min_grade_level() . ($this->min_grade_level() != $this->max_grade_level() ? '-' . $this->max_grade_level() : '');
    }

    public static function getById($id)
    {
        $resource = &self::cache(__CLASS__, 'getbyid-' . $id);
        if (!isset($resource)) {
            $resource = core_db::runGetObject(
                'SELECT * from mth_resource_settings where resource_id=' . (int) $id,
                'mth_resource_settings'
            );
        }
        return $resource;
    }

    public static function each($include_hidden = false, $grade_level = null, $reset = false)
    {
        $result = &self::$cache['each'][(int) $include_hidden][$grade_level];
        if (!isset($result)) {
            $sql = 'select * from mth_resource_settings where 1
            ' . (!$include_hidden ? "and (hidden is null or hidden != '1')" : '') . '
            ' . ($grade_level !== null ? "and $grade_level between min_grade_level and max_grade_level" : '') . '
            order by resource_name';

            $result = core_db::runQuery($sql);
        }

        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_settings')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }

    public static function banners($reset = false)
    {
        $result = &self::$cache['banners'];

        if (!isset($result)) {
            $sql = 'select * from mth_resource_settings where show_parent = 1 order by resource_name';
            $result = core_db::runQuery($sql);
        }

        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_settings')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }

    public static function unlimitedResources($reset = false)
    {
        $result = &self::$cache['unlimitedResources'];

        if (!isset($result)) {
            $sql = 'select * from mth_resource_settings where resource_type = ' . mth_resource_settings::TYPE_UNLIMITED . ' and show_parent=1 order by resource_name';
            $result = core_db::runQuery($sql);
        }

        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_settings')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }

    public static function optionalResources($reset = false, $grade_level = null, $banner_only = false)
    {
        $result = &self::$cache['optionalResources'];

        if (!isset($result)) {
            $sql = 'select * from mth_resource_settings where 
            resource_type = ' . mth_resource_settings::TYPE_OPTIONAL . ' 
            ' . ($grade_level !== null ? "and $grade_level between min_grade_level and max_grade_level" : '') . '
            ' . ($banner_only ? " and show_parent=1" : " and hidden=0 order by resource_name");
            $result = core_db::runQuery($sql);
        }

        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_settings')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }

    public function isAvailable()
    {
        return $this->hidden != 1;
    }

    public function showToParent()
    {
        return $this->show_parent == 1;
    }

    public function content()
    {
        return $this->content;
    }

    public function image()
    {
        return $this->image;
    }

    public function cost($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('cost', req_sanitize::float($set));
        }
        return $this->cost;
    }

    public function showCost()
    {
        return $this->show_cost == 1;
    }

    public function isDirectDeduction($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('is_direct_deduction', $set);
        }

        return $this->is_direct_deduction;
    }

    public function resourceType($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('resource_type', $set);
        }
        return $this->resource_type;
    }


    public function __toString()
    {
        if (!$this->resource_id) {
            return null;
        }
        return $this->name();
    }
}
