<?php

/**
 * email batch
 *
 * @author Tiler <tiler@mytechhigh.com>
 */
class mth_emailbatch extends core_model
{
    ################################################ DATABASE FIELDS ############################################

    protected $batch_id;
    protected $type;
    protected $title;
    protected $category;
    protected $template;
    protected $school_year_id;
    protected $sent_by_id;
    protected $batch_date;

    ################################################ STATIC MEMBERS ############################################

    protected static $cache;

    /**
     *
     * @return mth_emailbatch[]
     */
    public static function all()
    {
        $lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
        return core_db::runGetObjects(
            'SELECT * FROM mth_emailbatch
                     WHERE batch_date > "' . date('Y-m-d' , $lastmonth) . '"',
            'mth_emailbatch'
        );
    }

    /**
     *
     * @return int
     */
    public function getBatchId()
    {
        return $this->batch_id;
    }

    /**
     *
     * @return string
     */
    public function type($type = NULL)
    {
        if(!is_null($type))
        {
            $this->set('type', $type);
        }
        return $this->type;
    }

    /**
     *
     * @return string
     */
    public function title($title = NULL)
    {
        if(!is_null($title))
        {
            $this->set('title', $title);
        }
        return $this->title;
    }

    /**
     *
     * @return string
     */
    public function category($category = NULL)
    {
        if(!is_null($category))
        {
            $this->set('category', $category);
        }
        return $this->category;
    }

    /**
     *
     * @return string
     */
    public function template($template = NULL)
    {
        if(!is_null($template))
        {
            $this->set('template', $template);
        }
        return $this->template;
    }

    /**
     *
     * @return int
     */
    public function schoolYearId($id = 0)
    {
        if(!is_null($id))
        {
            $this->set('school_year_id', (int) $id);
        }
        return $this->school_year_id;
    }

    /**
     *
     * @return int
     */
    public function getBatchDate()
    {
        return Date('Y-m-d', strtotime($this->batch_date));
    }

    /**
     *
     * @return int
     */
    public function sent_by_id($id = 0)
    {
        if(!is_null($id))
        {
            $this->set('sent_by_id', (int) $id);
        }
        return $this->sent_by_id;
    }

    /**
     *
     * @return bool|null|mth_emailbatch
     */
    public static function getByBatchId($batchId)
    {
        if(!is_numeric($batchId))
        {
            return null;
        }
        return core_db::runGetObject('SELECT * from mth_emailbatch WHERE batch_id=' . $batchId, 'mth_emailbatch');
    }

    /**
     *
     * @return bool|mth_emailbatch
     */
    public function create()
    {
        core_db::runQuery('INSERT INTO mth_emailbatch (type, title, category, template, school_year_id, sent_by_id) 
        VALUES ("' . $this->type . '","' . $this->title . '","' . $this->category . '","' . req_sanitize::html($this->template) . '",' . $this->school_year_id . ',' . $this->sent_by_id . ')');
        return self::getByBatchId(core_db::getInsertID());
    }
}
