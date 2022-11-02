<?php

use mth\student\SchoolOfEnrollment;

/**
 * mth_person_search
 *
 * @author abe
 */
class mth_person_filter
{
    protected $grade_level = array();
    protected $school_of_enrollment = array();
    protected $status = array();
    protected $status_year = array();
    protected $status_year_filter_type;
    protected $exclude_status_year = array();
    protected $state = array();
    protected $special_ed = false;
    protected $diploma_seeking = false;
    protected $is_new = null;
    protected $is_new_next = null;
    protected $student_ids = [];
    protected $parent_ids = [];
    protected $skyward = null;

    protected $transferred = null;
    protected $isNewToSoe = null;
    protected $includeObserver = false;
    protected $mid_year = null;

    protected $has_note = null;

    /**
     *
     * @var mth_schoolYear
     */
    protected $new_to_school_year;

    protected $studentIDs = array();
    protected $parentIDs = array();
    protected $students = array();
    protected $parents = array();
    protected $all = array();
    protected $personIDs = array();
    protected $homeRoomSections = array();
    protected $courseType = "";
    protected $minimumPeriodCount = "";
    protected $providers = [];
    protected $limit = 50;
    protected $page = 1;
    protected $searchValue = "";
    protected $sortField = "";
    protected $sortOrder = "";
    protected $paginate = false;
    protected $hasSchedule = false;
    protected $hasScheduleOnly = false;
    protected $scheduleStatus = array();

    const FILTER_STATUS_YEAR_ALL = 1;
    const FILTER_STATUS_YEAR_ANY = 2;

    protected function setValue($field, $value)
    {
        $this->studentIDs = []; //so the query will get ran again;
        if (!is_array($value)) {
            $value = array($value);
        }
        if (empty($value)) {
            $value = [-1];
        }
        $this->$field = array_map(
            array('core_db', 'escape'),
            array_filter(
                array_map(
                    'trim',
                    $value
                ),
                function ($value) {
                    return !is_null($value);
                }
            )
        );
    }

    public function setObserver($include_observer)
    {
        $this->includeObserver = $include_observer;
    }

    public function setSortField($value)
    {
        $this->sortField = $value;
    }

    public function setSortOrder($value)
    {
        $this->sortOrder = $value;
    }

    public function setLimit($value)
    {
        $this->limit = $value;
    }

    public function setPage($value)
    {
        $this->page = $value;
    }

    public function setPaginate($value)
    {
        $this->paginate = $value;
    }

    public function hasSchedule($value)
    {
        $this->hasSchedule = $value;
    }

    public function setSearchValue($value)
    {
        $this->searchValue = $value;
    }

    public function setHasScheduleOnly($value)
    {
        $this->hasScheduleOnly = $value;
    }

    public function setStudentIDs(array $student_ids)
    {
        $this->setValue('student_ids', array_map('intval', $student_ids));
    }

    public function setParentIDs(array $parent_ids)
    {
        $this->setValue('parent_ids', array_map('intval', $parent_ids));
    }

    public function setGradeLevel($value)
    {
        $this->setValue('grade_level', $value);
    }

    public function setSchoolOfEnrollment($value)
    {
        $this->setValue('school_of_enrollment', array_map('intval', (array) $value));
    }

    public function setStatus($value)
    {
        $this->setValue('status', array_map('intval', (array) $value));
    }

    public function setScheduleStatus($value)
    {
        $this->setValue('scheduleStatus', array_map('intval', (array) $value));
    }

    public function setStatusYear($schoolYearIDs, $filterType = mth_person_filter::FILTER_STATUS_YEAR_ALL)
    {
        $this->setValue('status_year', array_map('intval', (array) $schoolYearIDs));
        sort($this->status_year);
        $this->status_year_filter_type = (int) $filterType;
    }

    public function setExcludeStatusYear($schoolYearIDs)
    {
        $this->setValue('exclude_status_year', array_map('intval', (array) $schoolYearIDs));
        sort($this->exclude_status_year);
    }

    public function setDiplomaSeeking($value)
    {
        $this->diploma_seeking = (bool) $value;
    }

    public function sethasNote($value = true)
    {
        $this->has_note = $value;
    }

    public function setSpecialEd($value)
    {
        $this->setValue('special_ed', array_map('intval', (array) $value));
    }

    public function setState($value)
    {
        $this->setValue('state', $value);
    }

    public function setIsNew($value)
    {
        $this->is_new = (bool) $value;
    }

    public function setSkyward()
    {
        $this->skyward = true;
    }

    public function setIsNewToSoe($value)
    {
        $this->isNewToSoe = (int) $value;
    }
    /**
     * Undocumented function
     *
     * @param [type] $value [1 for pending 2 for approve]
     * @return void
     */
    public function setTransferred($value = 2)
    {
        $this->transferred = $value;
    }

    public function setIsNewNext($value)
    {
        $this->is_new_next = (bool) $value;
    }

    public function setMidYear($value)
    {
        $this->mid_year = (bool) $value;
    }

    public function setHomeRoomSections(array $value)
    {
        $this->setValue('homeRoomSections', $value);
    }

    public function setNewToSchoolYear(mth_schoolYear $year)
    {
        $this->new_to_school_year = $year;
    }

    public function setProviders($providers = [])
    {
        $this->providers = $providers;
    }

    public function setCourseType($type)
    {
        $this->courseType = $type;
    }

    public function setMinimumPeriodCount($count)
    {
        $this->minimumPeriodCount = $count;
    }

    /**
     *
     * @return mysqli_result
     */
    protected function getQueryResult()
    {
        $prevYear = mth_schoolYear::getPrevious();
        $currYear = mth_schoolYear::getCurrent();
        $nextYear = mth_schoolYear::getNext();
        //core_db::$log_next = true;
        $sql = '
        SELECT s.student_id, s.parent_id,s.parent2_id as observer, p.person_id AS student_person_id, p.person_id AS parent_person_id
        FROM mth_person AS p
          INNER JOIN mth_student AS s ON s.person_id=p.person_id
          ' . ($this->status_year || $this->status
            ? 'INNER JOIN mth_student_status AS ss
                  ON ss.student_id=s.student_id'
            : '') . '
          LEFT JOIN mth_parent AS pa ON pa.parent_id=s.parent_id ' .
            ($this->hasSchedule ? 'LEFT JOIN (SELECT * FROM mth_schedule WHERE school_year_id = ' . implode(',', $this->status_year) . ') mss ON s.student_id = mss.student_id' : '')
            . ($this->exclude_status_year
                ? 'LEFT JOIN (SELECT ss2.student_id
                          FROM mth_student_status AS ss2
                          WHERE ss2.school_year_id IN (' . implode(',', $this->exclude_status_year) . ')
                          ) AS ssExclude ON ssExclude.student_id=s.student_id'
                : '') . '
        WHERE 1
          ' . ($this->grade_level
                ? 'AND s.student_id IN (SELECT gl.student_id
                                      FROM mth_student_grade_level AS gl
                                      WHERE gl.grade_level IN ("' . implode('","', $this->grade_level) . '")
                                        AND gl.school_year_id IN (' . ($this->status_year ? implode(',', $this->status_year) : $currYear->getID()) . '))'
                : '') . '
          ' . ($this->school_of_enrollment
                ? 'AND s.student_id IN (SELECT ssc.student_id
                                      FROM mth_student_school AS ssc
                                      WHERE school_of_enrollment IN (' . implode(',', $this->school_of_enrollment) . ')
                                        AND school_year_id IN (' . ($this->status_year ? implode(',', $this->status_year) : $currYear->getID()) . '))'
                : '') . '
          ' . ($this->special_ed
                ? 'AND s.special_ed IN (' . implode(',', array_map('intval', $this->special_ed)) . ')'
                : '') . '
          ' . ($this->diploma_seeking
                ? 'AND s.diploma_seeking=1'
                : '') . '
          ' . ($this->status
                ? 'AND ss.status IN (' . implode(',', $this->status) . ')'
                : '') . '
                ' . (!is_null($this->is_new) && $prevYear
                ? 'AND s.student_id ' . ($this->is_new ? 'NOT' : '') . ' IN (SELECT
                                                                    ss2.student_id
                                                                    FROM mth_student_status AS ss2
                                                                    WHERE school_year_id=' . $prevYear->getID() . '
                                                                      AND ss2.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . '))'
                : '') . '
          ' . (!is_null($this->transferred) && $prevYear
                ? 'AND s.student_id  IN (select ss6.student_id from mth_student_status as ss6
                  inner join mth_student_school as ss5 on ss6.student_id=ss5.student_id
                  inner join mth_student_school as ss4  on ss6.student_id=ss4.student_id
                  inner join mth_packet as mp4 on mp4.student_id=ss6.student_id
                  where ss6.school_year_id=' . $currYear->getID() . ' and ss5.school_year_id=' . $currYear->getID() . ($this->transferred == 2 ? ' and mp4.status = "Accepted"' : ' and mp4.status != "Accepted"') . '
                  and ss4.school_year_id=' . $prevYear->getID() . ' and ss5.school_of_enrollment!=' . SchoolOfEnrollment::Unassigned . ' and ss4.school_of_enrollment!=' . SchoolOfEnrollment::Unassigned . ' and ss4.school_of_enrollment!=ss5.school_of_enrollment
                  and ss6.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . ') OR ss5.transferred =1 GROUP BY student_id)'
                : '')
            .
            (!is_null($this->isNewToSoe) && $prevYear
                ? 'AND s.student_id NOT IN (
              select ss9.student_id from mth_student_school as ss9
                          inner join mth_student_status as ss8 on ss8.student_id=ss9.student_id
                          where ss9.school_year_id=' . $prevYear->getID() . ' and ss8.school_year_id=' . $prevYear->getID() . ' and ss9.school_of_enrollment=' . $this->isNewToSoe . '
                          and ss8.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . ')
          )'
                : '')
            . (!is_null($this->mid_year) && $this->mid_year ? ' AND s.student_id IN(
              SELECT student_id FROM mth_application WHERE midyear_application = 1 AND school_year_id IN ("' . implode(',', $this->status_year) . '")' . '
          )' : '')
            . ((!is_null($this->courseType) && $this->courseType) || (!is_null($this->providers) && $this->providers) ? ' AND s.student_id IN(
                SELECT ms.student_id FROM mth_schedule ms LEFT JOIN mth_schedule_period msp on msp.schedule_id = ms.schedule_id WHERE 1' .
                (!is_null($this->courseType) && $this->courseType ? ' AND msp.course_type = ' . $this->courseType : '') .
                (!is_null($this->providers) && $this->providers ? ' AND msp.mth_provider_id IN (' . implode(',', $this->providers) . ')' : '') .
                ' AND msp.period != 1 AND msp.subject_id IS NOT NULL AND ms.status != ' . mth_schedule::STATUS_DELETED . ' AND ms.status != ' . mth_schedule::STATUS_ACCEPTED
                . ' AND ms.school_year_id IN (' . implode(',', $this->status_year) . ') GROUP BY ms.student_id, msp.mth_provider_id ' . (!is_null($this->minimumPeriodCount) && $this->minimumPeriodCount ? 'HAVING COUNT(*) >= ' . $this->minimumPeriodCount . ' ' : '') . '
            )' : '') . ($this->hasSchedule ? ' AND ( ( mss.status IS NULL AND ss.status = ' . mth_student::STATUS_PENDING . ' ) OR mss.status != ' . mth_schedule::STATUS_DELETED . ' AND mss.status != ' . mth_schedule::STATUS_ACCEPTED . ' )' : ''
            )
            . (!is_null($this->is_new_next) && $currYear
                ? 'AND s.student_id ' . ($this->is_new_next ? 'NOT' : '') . ' IN (SELECT
                                                                    ss3.student_id
                                                                    FROM mth_student_status AS ss3
                                                                    WHERE ss3.school_year_id=' . $currYear->getID() . '
                                                                      AND ss3.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . '))'
                : '') . '
          ' . ($this->new_to_school_year && $this->new_to_school_year->getPreviousYear()
                ? 'AND s.student_id NOT IN (SELECT
                                          ss3.student_id
                                          FROM mth_student_status AS ss3
                                          WHERE ss3.school_year_id=' . $this->new_to_school_year->getPreviousYear()->getID() . '
                                            AND ss3.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . '))'
                : '') . '
          ' . ($this->exclude_status_year
                ? 'AND ssExclude.student_id IS NULL'
                : '') . '
          ' . ($this->homeRoomSections
                ? 'AND s.student_id IN (SELECT student_id FROM mth_student_section
                                      WHERE name IN ("' . implode('","', $this->homeRoomSections) . '")
                                        AND period_num=1
                                        ' . ($this->status_year ? 'AND schoolYear_id IN ("' . implode(',', $this->status_year) . '")' : '') . ')'
                : '') . '
          ' . ($this->student_ids
                ? 'AND s.student_id IN (' . implode(',', $this->student_ids) . ')'
                : '') . '
          ' . ($this->parent_ids
                ? 'AND s.parent_id IN (' . implode(',', $this->parent_ids) . ')'
                : '') .
            ($this->has_note ? ' AND s.parent_id IN(SELECT parent_id from mth_familynote where note <> null or note!="")' : '') .
            (!empty($this->state) ? ' AND s.parent_id IN ( SELECT mp.parent_id from mth_parent mp 
                                                                LEFT JOIN mth_person mps ON mp.person_id = mps.person_id
                                                                LEFT JOIN mth_person_address mpa ON mps.person_id = mpa.person_id
                                                                LEFT JOIN mth_address ma ON mpa.address_id = ma.address_id
                                                                WHERE ma.state IN (' . implode(', ', array_map(function ($val) {
                return sprintf("'%s'", $val);
            }, $this->state)) . ') )' : '') .
            ($this->hasScheduleOnly ? ' AND mss.schedule_id IS NOT NULL ' : '') .
            ($this->scheduleStatus ? ' AND ( mss.status IN (' . implode(',', $this->scheduleStatus) . ')' . (in_array("66", $this->scheduleStatus) ? ' OR mss.status IS NULL ' : '') . ')' : '') .
            ($this->searchValue != "" ? ' AND ( p.last_name like "%' . $this->searchValue . '%" OR p.first_name like "%' . $this->searchValue . '%" )' : '') . '
          ' . ($this->status_year
                ? ' AND ss.school_year_id IN (' . implode(",", $this->status_year) . ')
                GROUP BY s.student_id 
                ' . ($this->status_year_filter_type == self::FILTER_STATUS_YEAR_ANY
                    ? ''
                    : 'HAVING GROUP_CONCAT(ss.school_year_id) = "' . implode(",", $this->status_year) . '"')
                : '');

        if ($this->sortField != "") {

            if ($this->sortField == "student") {
                $sortField = "p.last_name";
            } else if ($this->sortField == "status") {
                if ($this->sortOrder == "asc") {
                    $sortField = "CASE mss.status
                        WHEN '2' THEN 1
                        WHEN '6' THEN 2
                        WHEN '4' THEN 3
                        WHEN '0' THEN 4
                        WHEN '1' THEN 5
                        WHEN '3' THEN 6
                        WHEN '5' THEN 7
                    END, p.last_name, p.first_name asc";
                } else {
                    $sortField = "CASE mss.status
                        WHEN '5' THEN 1
                        WHEN '3' THEN 2
                        WHEN '1' THEN 3
                        WHEN '0' THEN 4
                        WHEN '4' THEN 5
                        WHEN '6' THEN 6
                        WHEN '2' THEN 7
                        ELSE 8
                    END, p.last_name, p.first_name asc";
                }
            } else {
                $sortField = "mss." . $this->sortField;
            }

            if ($this->sortField == "last_modified") {
                $sql .= ' ORDER BY CAST(mss.last_modified AS DATE) ' . $this->sortOrder . ', p.last_name ASC, p.first_name ASC';
            } else {
                $sql .= ' ORDER BY ' . $sortField . ' ' . ($this->sortField != "status" ? $this->sortOrder : '');
            }
        }

        if ($this->paginate) {
            $sql .= ' LIMIT ' . $this->page . ', ' . $this->limit;
        }

        return core_db::runQuery($sql);
    }

    public function getFilteredCounts()
    {
        $prevYear = mth_schoolYear::getPrevious();
        $currYear = mth_schoolYear::getCurrent();
        $nextYear = mth_schoolYear::getNext();
        //core_db::$log_next = true;
        $sql = '
        SELECT count(*) as cu
        FROM mth_person AS p
          INNER JOIN mth_student AS s ON s.person_id=p.person_id
          ' . ($this->status_year || $this->status
                  ? 'INNER JOIN mth_student_status AS ss 
                  ON ss.student_id=s.student_id'
            : '') . '
          LEFT JOIN mth_parent AS pa ON pa.parent_id=s.parent_id '.
          ($this->hasSchedule ? 'LEFT JOIN (SELECT * FROM mth_schedule WHERE school_year_id = ' . implode(',', $this->status_year) . ') mss ON s.student_id = mss.student_id' : '')
          . ($this->exclude_status_year
            ? 'LEFT JOIN (SELECT ss2.student_id
                          FROM mth_student_status AS ss2
                          WHERE ss2.school_year_id IN (' . implode(',', $this->exclude_status_year) . ')
                          ) AS ssExclude ON ssExclude.student_id=s.student_id'
                  : '') . '
        WHERE 1
          ' . ($this->grade_level
                  ? 'AND s.student_id IN (SELECT gl.student_id 
                                      FROM mth_student_grade_level AS gl 
                                      WHERE gl.grade_level IN ("' . implode('","', $this->grade_level) . '")
                                        AND gl.school_year_id IN (' . ($this->status_year ? implode(',', $this->status_year) : $currYear->getID()) . '))'
                  : '') . '
          ' . ($this->school_of_enrollment
                ? 'AND s.student_id IN (SELECT ssc.student_id 
                                      FROM mth_student_school AS ssc 
                                      WHERE school_of_enrollment IN (' . implode(',', $this->school_of_enrollment) . ')
                                        AND school_year_id IN (' . ($this->status_year ? implode(',', $this->status_year) : $currYear->getID()) . '))'
                : '') . '
          ' . ($this->special_ed
                  ? 'AND s.special_ed IN (' . implode(',', array_map('intval', $this->special_ed)) . ')'
                  : '') . '
          ' . ($this->diploma_seeking
                  ? 'AND s.diploma_seeking=1'
                  : '') . '
          ' . ($this->status
                  ? 'AND ss.status IN (' . implode(',', $this->status) . ')'
                  : '') . '
          ' . (!is_null($this->is_new) && $prevYear
                  ? 'AND s.student_id ' . ($this->is_new ? 'NOT' : '') . ' IN (SELECT 
                                                                    ss2.student_id 
                                                                    FROM mth_student_status AS ss2
                                                                    WHERE school_year_id=' . $prevYear->getID() . '
                                                                      AND ss2.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . '))'
                  : '') . '
          ' . (!is_null($this->transferred) && $prevYear
                  ? 'AND s.student_id  IN (select ss6.student_id from mth_student_status as ss6
                  inner join mth_student_school as ss5 on ss6.student_id=ss5.student_id 
                  inner join mth_student_school as ss4  on ss6.student_id=ss4.student_id
                  inner join mth_packet as mp4 on mp4.student_id=ss6.student_id
                  where ss6.school_year_id='. $currYear->getID() .' and ss5.school_year_id='. $currYear->getID() .($this->transferred==2?' and mp4.status = "Accepted"':' and mp4.status != "Accepted"').'
                  and ss4.school_year_id=' . $prevYear->getID() . ' and ss5.school_of_enrollment!='.SchoolOfEnrollment::Unassigned.' and ss4.school_of_enrollment!='.SchoolOfEnrollment::Unassigned.' and ss4.school_of_enrollment!=ss5.school_of_enrollment
                  and ss6.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . '))'
          : '')
          .
          (!is_null($this->isNewToSoe) && $prevYear
          ? 'AND s.student_id NOT IN (
              select ss9.student_id from mth_student_school as ss9 
                          inner join mth_student_status as ss8 on ss8.student_id=ss9.student_id
                          where ss9.school_year_id=' . $prevYear->getID() . ' and ss8.school_year_id=' . $prevYear->getID() . ' and ss9.school_of_enrollment='.$this->isNewToSoe.'
                          and ss8.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . ')
          )'
          : '') 
          .(!is_null($this->mid_year) && $this->mid_year?' AND s.student_id IN(
              SELECT student_id FROM mth_application WHERE midyear_application = 1 AND school_year_id IN ("' . implode(',', $this->status_year) . '")' . '
          )':'')
          .( (!is_null($this->courseType) && $this->courseType) || (!is_null($this->providers) && $this->providers) ? ' AND s.student_id IN(
                SELECT ms.student_id FROM mth_schedule ms LEFT JOIN mth_schedule_period msp on msp.schedule_id = ms.schedule_id WHERE 1'. 
                ( !is_null($this->courseType) && $this->courseType ? ' AND msp.course_type = '. $this->courseType : '' ) .
                ( !is_null($this->providers) && $this->providers ? ' AND msp.mth_provider_id IN (' . implode(',', $this->providers) . ')' : '' ) .
                ' AND msp.period != 1 AND msp.subject_id IS NOT NULL AND ms.status != '. mth_schedule::STATUS_DELETED . ' AND ms.status != '. mth_schedule::STATUS_ACCEPTED
                .' AND ms.school_year_id IN (' . implode(',', $this->status_year) . ') GROUP BY ms.student_id, msp.mth_provider_id '. ( !is_null($this->minimumPeriodCount) && $this->minimumPeriodCount ? 'HAVING COUNT(*) >= '.$this->minimumPeriodCount.' ' : '') . '
            )':'').(
                $this->hasSchedule ? ' AND ( ( mss.status IS NULL AND ss.status = '.mth_student::STATUS_PENDING.' ) OR mss.status != '. mth_schedule::STATUS_DELETED . ' AND mss.status != '. mth_schedule::STATUS_ACCEPTED .' )' : ''
            )
          .(!is_null($this->is_new_next) && $currYear
                  ? 'AND s.student_id ' . ($this->is_new_next ? 'NOT' : '') . ' IN (SELECT 
                                                                    ss3.student_id 
                                                                    FROM mth_student_status AS ss3
                                                                    WHERE ss3.school_year_id=' . $currYear->getID() . '
                                                                      AND ss3.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . '))'
                  : '') . '
          ' . ($this->new_to_school_year && $this->new_to_school_year->getPreviousYear()
                  ? 'AND s.student_id NOT IN (SELECT 
                                          ss3.student_id 
                                          FROM mth_student_status AS ss3
                                          WHERE ss3.school_year_id=' . $this->new_to_school_year->getPreviousYear()->getID() . '
                                            AND ss3.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . '))'
                  : '') . '
          ' . ($this->exclude_status_year
                  ? 'AND ssExclude.student_id IS NULL'
                  : '') . '
          ' . ($this->homeRoomSections
                  ? 'AND s.student_id IN (SELECT student_id FROM mth_student_section 
                                      WHERE name IN ("' . implode('","', $this->homeRoomSections) . '")
                                        AND period_num=1
                                        ' . ($this->status_year ? 'AND schoolYear_id IN ("' . implode(',', $this->status_year) . '")' : '') . ')'
                  : '') . '
          ' . ($this->student_ids
                  ? 'AND s.student_id IN (' . implode(',', $this->student_ids) . ')'
                  : '') . '
          ' . ($this->parent_ids
                  ? 'AND s.parent_id IN (' . implode(',', $this->parent_ids) . ')'
                  : ''). 
        ($this->has_note?' AND s.parent_id IN(SELECT parent_id from mth_familynote where note <> null or note!="")':'').
        ( $this->hasScheduleOnly ? ' AND mss.schedule_id IS NOT NULL ' : '' ).
        ( $this->scheduleStatus ? ' AND ( mss.status IN (' . implode(',', $this->scheduleStatus) . ')'. (in_array("66",  $this->scheduleStatus) ? ' OR mss.status IS NULL ' : '').')' : '' ).
        ($this->searchValue != "" ?' AND ( p.last_name like "%'.$this->searchValue.'%" OR p.first_name like "%'.$this->searchValue.'%" )':'').'
          ' . ($this->status_year
                  ? ' AND ss.school_year_id IN (' . implode(",", $this->status_year) . ')
                GROUP BY s.student_id 
                ' . ($this->status_year_filter_type == self::FILTER_STATUS_YEAR_ANY
                      ? ''
                      : 'HAVING GROUP_CONCAT(ss.school_year_id) = "' . implode(",", $this->status_year) . '"')
                : '');
        return core_db::runQuery($sql);
    }


    public function getUnassigned()
    {
        $currYear = mth_schoolYear::getCurrent();
        $sql = 'SELECT s.student_id, p.first_name, p.last_name, p.gender, s.parent_id,s.parent2_id as observer, p.person_id AS student_person_id, p.person_id AS parent_person_id FROM mth_person AS p
                    INNER JOIN mth_student AS s ON s.person_id=p.person_id
                    INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id
                    WHERE 1
                        ' . ($this->student_ids
            ? 'AND s.student_id IN (' . implode(',', $this->student_ids) . ')'
            : '') . '
                        ' . ($this->parent_ids
            ? 'AND s.parent_id IN (' . implode(',', $this->parent_ids) . ')'
            : '') . ($this->grade_level
            ? 'AND s.student_id IN (SELECT gl.student_id
                                                    FROM mth_student_grade_level AS gl
                                                    WHERE gl.grade_level IN ("' . implode('","', $this->grade_level) . '")
                                                        AND gl.school_year_id IN (' . ($this->status_year ? implode(',', $this->status_year) : $currYear->getID()) . '))'
            : '') . '
                        ' . ($this->school_of_enrollment
            ? 'AND s.student_id NOT IN (SELECT ssc.student_id
                                                    FROM mth_student_school AS ssc
                                                    WHERE school_of_enrollment IN (0,1,2,3,4,5,6,7,8)
                                                        AND school_year_id IN (' . ($this->status_year ? implode(',', $this->status_year) : $currYear->getID()) . '))'
            : '') . '
                            ' . ($this->status ?
            'AND ss.status IN (' . implode(',', $this->status) . ')'
            : '') . '
                            AND ss.school_year_id IN (' . $currYear->getID() . ')
                                        GROUP BY s.student_id';

        return core_db::runGetObjects($sql, 'mth_student');
    }

    public function getAllCountWithoutFilter()
    {
        $sql = 'SELECT COUNT(*) AS TOTAL
                    FROM mth_person AS p
                    INNER JOIN mth_student AS s ON s.person_id=p.person_id
                    INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id
                    LEFT JOIN mth_parent AS pa ON pa.parent_id=s.parent_id
                    LEFT JOIN (SELECT * FROM mth_schedule WHERE school_year_id = ' . implode(',', $this->status_year) . ') mss ON s.student_id = mss.student_id
                WHERE ss.status IN (' . implode(',', $this->status) . ')
                    AND ( ( mss.status IS NULL AND ss.status = ' . mth_student::STATUS_PENDING . ' ) OR mss.status != 99 AND mss.status != 2 )
                    AND ss.school_year_id IN (' . implode(',', $this->status_year) . ')' .
            ($this->hasScheduleOnly ? ' AND mss.schedule_id IS NOT NULL ' : '') .
            ($this->scheduleStatus ? ' AND ( mss.status IN (' . implode(',', $this->scheduleStatus) . ')' . (in_array("66", $this->scheduleStatus) ? ' OR mss.status IS NULL ' : '') . ')' : '') .
            ($this->searchValue != "" ? ' AND ( p.last_name like "%' . $this->searchValue . '%" OR p.first_name like "%' . $this->searchValue . '%" )' : '') .
            ($this->hasSchedule ? ' AND ( mss.status IS NULL OR mss.status != ' . mth_schedule::STATUS_DELETED . ' AND mss.status != ' . mth_schedule::STATUS_ACCEPTED . ' )' : '');

        $result = core_db::runQuery($sql);
        $total = 0;
        while ($r = $result->fetch_row()) {
            $total = $r[0];
        }

        $result->free_result();

        return $total;
    }

    public function getAllCounts()
    {
        $sql = 'SELECT mss.status, COUNT(*) AS COUNTS
                    FROM mth_person AS p
                    INNER JOIN mth_student AS s ON s.person_id=p.person_id
                    INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id
                    LEFT JOIN mth_parent AS pa ON pa.parent_id=s.parent_id
                    LEFT JOIN (SELECT * FROM mth_schedule WHERE school_year_id = ' . implode(',', $this->status_year) . ') mss ON s.student_id = mss.student_id
                WHERE ss.status IN (' . implode(',', $this->status) . ')
                    AND ss.school_year_id IN (' . implode(',', $this->status_year) . ')' .
            ($this->hasSchedule ? ' AND ( ( mss.status IS NULL AND ss.status = ' . mth_student::STATUS_PENDING . ' ) OR mss.status != ' . mth_schedule::STATUS_DELETED . ' AND mss.status != ' . mth_schedule::STATUS_ACCEPTED . ' )' : '') .
            ($this->searchValue != "" ? ' AND ( p.last_name like "%' . $this->searchValue . '%" OR p.first_name like "%' . $this->searchValue . '%" )' : '') .
            ((!is_null($this->courseType) && $this->courseType) || (!is_null($this->providers) && $this->providers) ? ' AND s.student_id IN(
                        SELECT ms.student_id FROM mth_schedule ms LEFT JOIN mth_schedule_period msp on msp.schedule_id = ms.schedule_id WHERE 1' .
                (!is_null($this->courseType) && $this->courseType ? ' AND msp.course_type = ' . $this->courseType : '') .
                (!is_null($this->providers) && $this->providers ? ' AND msp.mth_provider_id IN (' . implode(',', $this->providers) . ')' : '') .
                ' AND msp.period != 1 AND msp.subject_id IS NOT NULL AND ms.status != ' . mth_schedule::STATUS_DELETED . ' AND ms.status != ' . mth_schedule::STATUS_ACCEPTED
                . ' AND ms.school_year_id IN (' . implode(',', $this->status_year) . ') GROUP BY ms.student_id, msp.mth_provider_id ' . (!is_null($this->minimumPeriodCount) && $this->minimumPeriodCount ? 'HAVING COUNT(*) >= ' . $this->minimumPeriodCount . ' ' : '') . '
                    )' : '')
            . ' GROUP BY mss.status';

        $result = core_db::runQuery($sql);
        $counts = [];
        while ($r = $result->fetch_row()) {
            $key = ($r[0] == null ? 66 : $r[0]);
            $counts[$key] = $r[1];
        }

        $result->free_result();

        return $counts;
    }

    protected function populateIDs()
    {
        if (!empty($this->studentIDs)) {
            return;
        }

        $result = $this->getQueryResult();
        if (!$result) {
            error_log('Error in filter query: ' . print_r($this, true));
            return;
        }
        while ($r = $result->fetch_object()) {
            if ($this->includeObserver && $r->observer && !isset($this->parentIDs[$r->observer])) {
                $this->parentIDs[$r->observer] = $r->observer;
            }
            if (!isset($this->parentIDs[$r->parent_id])) {
                $this->parentIDs[$r->parent_id] = $r->parent_id;
            }
            if (!isset($this->studentIDs[$r->student_id])) {
                $this->studentIDs[$r->student_id] = $r->student_id;
            }
            if (!isset($this->personIDs[$r->student_person_id]) && $r->student_person_id) {
                $this->personIDs[$r->student_person_id] = $r->student_person_id;
            }
            if (!isset($this->personIDs[$r->parent_person_id]) && $r->parent_person_id) {
                $this->personIDs[$r->parent_person_id] = $r->parent_person_id;
            }
        }
        $result->free_result();
    }

    public function getStudentIDs()
    {
        $this->populateIDs();
        return $this->studentIDs;
    }

    public function getParentIDs()
    {
        $this->populateIDs();
        return $this->parentIDs;
    }

    public function getPersonIDs()
    {
        $this->populateIDs();
        return $this->personIDs;
    }

    /**
     *
     * @return mth_student[] of mth_student objects
     */
    public function getStudents()
    {
        if (empty($this->students)) {
            $this->students = mth_student::getStudents(array('StudentID' => $this->getStudentIDs()), false);
        }
        return $this->students;
    }

    /**
     *
     * @return array of mth_parent objects
     */
    public function getParents()
    {
        if (empty($this->parents)) {
            $this->parents = mth_parent::getParents($this->getParentIDs());
        }
        return $this->parents;
    }

    public function getAll()
    {
        if (empty($this->all)) {
            $this->all = array_merge($this->getStudents(), $this->getParents());
            usort($this->all, array('self', 'sort'));
        }
        return $this->all;
    }

    public static function sort(mth_person $a, mth_person $b)
    {
        if (($aName = $a->getPreferredLastName() . $a->getPreferredFirstName()) ==
            ($bName = $b->getPreferredLastName() . $b->getPreferredFirstName())
        ) {
            return 0;
        }
        return $aName < $bName ? -1 : 1;
    }
}
