<?php

/**
 * Description of query
 *
 * @author abe
 */
class mth_canvas_course_query
{
    protected static $query = '
    SELECT * FROM mth_canvas_course
    WHERE 1 [WHERE_CLAUSE]';

    protected $where = array();

    /**
     *
     * @var mysqli_result
     */
    protected $result;

    public function set_mth_course_ids(ARRAY $mth_course_ids)
    {
        $this->where['mth_course_ids'] = 'AND mth_course_id IN (' . implode(',', array_map('intval', $mth_course_ids)) . ')';
    }

    public function set_school_year_ids(ARRAY $school_year_ids)
    {
        $this->where['school_year_ids'] = 'AND school_year_id IN (' . implode(',', array_map('intval', $school_year_ids)) . ')';
    }

    protected function runQuery()
    {
        $this->result = core_db::runQuery(str_replace('[WHERE_CLAUSE]', implode(' ', $this->where), self::$query));
    }

    /**
     *
     * @param bool $reset
     * @return mth_canvas_course
     */
    public function each($reset = false)
    {
        $this->result || $this->runQuery();

        if (!$reset && ($course = $this->result->fetch_object('mth_canvas_course'))) {
            return $course;
        }
        $this->result->data_seek(0);
        return NULL;
    }
}
