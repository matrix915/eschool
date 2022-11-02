<?php

/**
 * for storing course data used for interacting with canvas
 *
 * @author abe
 */
class mth_canvas_course extends core_model
{
    protected $canvas_course_id;
    protected $mth_course_id;
    protected $school_year_id;
    protected $workflow_state;
    protected $canvas_teacher;

    protected static $cache = array();

    const WS_UNPUBLISHED = 0;
    const WS_AVAILABLE = 1;
    const WS_COMPELETED = 2;
    const WS_DELETED = 3;

    protected static $workflow_state_labels = array(
        self::WS_UNPUBLISHED => 'unpublished',
        self::WS_AVAILABLE => 'available',
        self::WS_COMPELETED => 'completed',
        self::WS_DELETED => 'deleted'
    );

    public static function workflow_state_label($workflow_state)
    {
        return self::$workflow_state_labels[$workflow_state];
    }

    public function id()
    {
        return $this->canvas_course_id();
    }

    public function teacher(){
        return $this->canvas_teacher;
    }

    public function canvas_course_id()
    {
        return (int)$this->canvas_course_id;
    }

    public function mth_course_id()
    {
        return (int)$this->mth_course_id;
    }

    /**
     *
     * @return mth_course
     */
    public function mth_course()
    {
        if (!($course = mth_course::getByID($this->mth_course_id))) {
            $course = new mth_course();
        }
        return $course;
    }

    public function school_year_id()
    {
        return (int)$this->school_year_id;
    }

    public function workflow_state($returnNumber = false)
    {
        if ($returnNumber) {
            return (int)$this->workflow_state;
        }
        return self::$workflow_state_labels[$this->workflow_state];
    }

    public function isAvailable()
    {
        return $this->workflow_state == self::WS_AVAILABLE;
    }

    public function is($workflow_state)
    {
        return $this->workflow_state == $workflow_state
        || $workflow_state == self::$workflow_state_labels[$this->workflow_state];
    }

    /**
     *
     * @param mth_course $course
     * @param mth_schoolYear $schoolYear
     * @return mth_canvas_course
     */
    public static function get(mth_course $course, mth_schoolYear $schoolYear)
    {
        $canvas_course = &self::$cache['get'][$course->getID()][$schoolYear->getID()];
        if (!isset($canvas_course)) {
            $canvas_course = core_db::runGetObject('SELECT * FROM mth_canvas_course 
                                              WHERE mth_course_id=' . $course->getID() . '
                                                AND school_year_id=' . $schoolYear->getID(),
                'mth_canvas_course');
        }
        return $canvas_course;
    }

    /**
     *
     * @param mth_schedule_period $schedulePeriod
     * @return mth_canvas_course
     */
    public static function getBySchedulePeriod(mth_schedule_period $schedulePeriod)
    {
        if (!$schedulePeriod->course()
            || !$schedulePeriod->schedule()
            || !$schedulePeriod->schedule()->schoolYear()
        ) {
            return NULL;
        }
        return self::get($schedulePeriod->course(), $schedulePeriod->schedule()->schoolYear());
    }

    /**
     *
     * @param string $SIS_ID
     * @return mth_canvas_course|false
     */
    public static function getBySISID($SIS_ID)
    {
        if (strlen($SIS_ID) < 6) {
            return false;
        }
        $idArr = explode('-', $SIS_ID);
        if (!($course = mth_course::getByID($idArr[1]))
            || !($schoolYear = mth_schoolYear::getByStartYear($idArr[0]))
        ) {
            return NULL;
        }
        return self::get($course, $schoolYear);
    }

    /**
     *
     * @param int $canvas_course_id
     * @param bool $getMappedOnly
     * @return mth_canvas_course|null
     */
    public static function getByID($canvas_course_id, $getMappedOnly = true)
    {
        if(!$canvas_course_id){
            return null;
        }
        $canvas_course = &self::$cache['getByID'][$canvas_course_id];
        if (!isset($canvas_course)) {
            $canvas_course = core_db::runGetObject('SELECT * FROM mth_canvas_course 
                                              WHERE canvas_course_id=' . (int)$canvas_course_id,
                'mth_canvas_course');
            if (!$getMappedOnly && !$canvas_course) {
                $canvas_course = new mth_canvas_course();
                $canvas_course->canvas_course_id = $canvas_course_id;
            }
        }
        return $canvas_course;
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param bool $reset
     * @return mth_canvas_course
     */
    public static function each(mth_schoolYear $year = NULL, $reset = FALSE)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }
        $result = &self::$cache['each'][$year->getID()];
        if (!isset($result)) {
            $result = core_db::runQuery('SELECT canvas_course_id, mth_course_id, school_year_id, workflow_state 
                                      FROM mth_canvas_course 
                                    WHERE school_year_id=' . $year->getID() );
                                    // UNION
                                    
                                    // SELECT NULL, course_id, '.$year->getID().', "'.self::WS_AVAILABLE.'" FROM mth_course AS c
                                    //     INNER JOIN mth_subject_period AS sp ON sp.subject_id=c.subject_id
                                    //         AND sp.period=1'
        }
        if (!$reset && ($course = $result->fetch_object('mth_canvas_course'))) {
            return $course;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function eachAndHomeroom(mth_schoolYear $year = NULL, $reset = FALSE,$where = ''){
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }
        $result = &self::$cache['eachAndHomeroom'][$year->getID()];
        if (!isset($result)) {
            $WHERE = !empty($where)?"AND $where":'';

            $sql = "select homeroom_canvas_course_id as canvas_course_id,null,school_year_id,'".self::WS_AVAILABLE."' from mth_student_homeroom 
            where school_year_id=".$year->getID()." $WHERE group by homeroom_canvas_course_id";
            
            $result = core_db::runQuery($sql);
        }
        if (!$reset && ($course = $result->fetch_object('mth_canvas_course'))) {
            return $course;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function count(mth_schoolYear $year = NULL)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return 0;
        }
        $result = &self::$cache['each'][$year->getID()];
        if (!isset($result)) {
            self::each($year, true);
        }
        return $result->num_rows;
    }

    public static function getIDs(mth_schoolYear $year = NULL)
    {
        $IDs = &self::$cache['getIDs'][$year ? $year->getID() : 0];
        if (!isset($IDs)) {
            self::each($year, true);
            $IDs = array();
            while ($course = self::each($year)) {
                $IDs[] = $course->id();
            }
        }
        return $IDs;
    }

    public static function wsCounts(mth_schoolYear $year = NULL)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return array();
        }
        $result = core_db::runQuery('SELECT workflow_state, COUNT(canvas_course_id) 
                                  FROM mth_canvas_course 
                                  WHERE school_year_id=' . $year->getID() . '
                                  GROUP BY workflow_state
                                  ORDER BY workflow_state');
        $arr = array();
        while ($r = $result->fetch_row()) {
            $arr[$r[0]] = $r[1];
        }
        $result->free_result();
        return $arr;
    }

    public static function flush()
    {
        return core_db::runQuery('DELETE FROM mth_canvas_course WHERE 1');
    }

    public static function update_mapping()
    {
        $command = '/accounts/' . mth_canvas::account_id() . '/courses?per_page=50&include[]=teachers&page=';
        $page = 1;
        while ($result = mth_canvas::exec($command . $page)) {
            if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return FALSE;
            }
            if (count($result) == 0) {
                break;
            }
            if (!self::map_results_array($result)) {
                error_log('Unable to save canvas course mapping to database');
                return FALSE;
            }
            $page++;
        }
        return TRUE;
    }

    public static function single_update_mapping($page){
        $command = '/accounts/' . mth_canvas::account_id() . '/courses?per_page=50&include[]=teachers&page=';
        $count = 0;
        
        if ($result = mth_canvas::exec($command . $page)) {
            if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return [
                    'error' => TRUE,
                    'result' => $count
                ];
            }
            $count = count($result);

            if (!self::map_results_array($result)) {
                error_log('Unable to save canvas course mapping to database');
                return [
                    'error' => TRUE,
                    'result' => $count
                ];
            }
        }
        return [
            'error' => FALSE,
            'result' => $count
        ];
    }

    protected static function map_results_array($results_arr)
    {
        $Qs = $delete = array();

        foreach ($results_arr AS $courseObj) {
            $id = explode('-', $courseObj->sis_course_id);
            if (empty($id[0]) || empty($id[1])) {
                continue;
            }
            if (!($year = mth_schoolYear::getByStartYear($id[0])) || !($course = mth_course::getByID($id[1]))) {
                $delete[] = '(mth_course_id=' . (int)$id[1] . ' AND school_year_id=' . (int)$id[0] . ')';
                continue;
            }
            $teacher = !empty($courseObj->teachers)?('"'.$courseObj->teachers[0]->display_name.'"'):'NULL';
            $Qs[] = '(' . (int)$courseObj->id . ',' . $course->getID() . ',' . $year->getID() . ',' .
                array_search($courseObj->workflow_state, self::$workflow_state_labels) . ','.$teacher.')';
            $delete[] = '(mth_course_id=' . $course->getID() . ' AND school_year_id=' . $year->getID() . ')';
        }
        if ($delete) {
            core_db::runQuery('DELETE FROM mth_canvas_course WHERE ' . implode(' OR ', $delete));
        }
        
        if (empty($Qs)) {
            return TRUE;
        }

        return core_db::runQuery('INSERT INTO mth_canvas_course 
                                (canvas_course_id, mth_course_id, school_year_id, workflow_state,canvas_teacher) 
                                VALUES ' . implode(',', $Qs));
    }
}
