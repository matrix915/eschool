<?php

use mth\student\SchoolOfEnrollment;
use mth\yoda\courses;
use mth\yoda\memcourse;

/**
 * student
 *
 * @author abe
 */
class mth_student extends mth_person
{
    ################################################ DATABASE FIELDS ############################################

    protected $student_id;
    protected $parent_id;
    protected $parent2_id; // not used
    protected $grade_level; // no longer used as of mth027.sql
    protected $status; //no longer used as of mth_6.sql
    protected $school_of_enrollment; //no longer used as of mth_10.sql
    protected $special_ed;
    protected $diploma_seeking;
    protected $reenrolled;
    protected $teacher_notes;

    ################################################ OTHER MEMBERS ############################################

    protected $year_statuses = null;
    protected $year_withdrawals = null;
    protected $year_status_dates = null;
    protected $year_schools = null;
    protected $year_grade_levels = null;

    protected $updateQuery = array();

    protected static $limit = 50;
    protected static $page = 1;
    protected static $skyward = false;
    protected static $searchValue = "";
    protected static $sortField = "";
    protected static $sortOrder = "";
    protected static $paginate = false;

    ################################################ STATIC MEMBERS ############################################

    protected static $cache;

    const STATUS_PENDING = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_WITHDRAW = 3;
    const STATUS_GRADUATED = 4;
    const STATUS_TRANSITIONED = 5;

    const STATUS_LABEL_PENDING = 'Pending';
    const STATUS_LABEL_ACTIVE = 'Active';
    const STATUS_LABEL_WITHDRAW = 'Withdrawn';
    const STATUS_LABEL_GRADUATED = 'Graduated';
    const STATUS_LABEL_NOT_ENROLLED = 'Not Enrolled';
    const STATUS_LABEL_TRANSITIONED = 'Transitioned';

    protected static $availableStatuses = array(
        self::STATUS_ACTIVE => self::STATUS_LABEL_ACTIVE,
        self::STATUS_PENDING => self::STATUS_LABEL_PENDING,
        self::STATUS_WITHDRAW => self::STATUS_LABEL_WITHDRAW,
        self::STATUS_GRADUATED => self::STATUS_LABEL_GRADUATED,
        self::STATUS_TRANSITIONED => self::STATUS_LABEL_TRANSITIONED
    );

    public static function getAvailableStatuses()
    {
        return self::$availableStatuses;
    }

    public static function statusLabel($status)
    {
        return isset(self::$availableStatuses[$status]) ? self::$availableStatuses[$status] : null;
    }

    const SPED_NO = 0;
    const SPED_IEP = 1;
    const SPED_504 = 2;
    const SPED_EXIT = 3;

    const SPED_LABEL_NO = 'No';
    const SPED_LABEL_IEP = 'IEP';
    const SPED_LABEL_504 = '504';
    const SPED_LABEL_EXIT = 'Exit';

    protected static $spEd = array(
        self::SPED_NO => self::SPED_LABEL_NO,
        self::SPED_IEP => self::SPED_LABEL_IEP,
        self::SPED_504 => self::SPED_LABEL_504,
        self::SPED_EXIT => self::SPED_LABEL_EXIT
    );

    public static function getAvailableSpEd()
    {
        return self::$spEd;
    }

    public function getReenrolled($schoolYearId = null)
    {
        // If school year is not provided, default to current school year
        if ($schoolYearId == null && ($schoolYear = mth_schoolYear::getCurrent())) {
            $schoolYearId = $schoolYear->getID();
        }

        return core_db::runGetObjects('SELECT * FROM mth_student_reenrollment_status WHERE student_id = ' .
            $this->getID() . ' AND school_year_id = ' . $schoolYearId . ' AND reenrolled=1');
    }

    public function setReenrolled($reenrolled, $schoolYearId = null)
    {
        // If school year is not provided, default to current school year
        if ($schoolYearId == null && ($schoolYear = mth_schoolYear::getCurrent())) {
            $schoolYearId = $schoolYear->getID();
        }

        return core_db::runQuery('REPLACE INTO mth_student_reenrollment_status ' .
            '(student_id, school_year_id, reenrolled) VALUES (' .
            implode(', ', [$this->getID(), $schoolYearId, (int) $reenrolled]) . ')');
    }

    public static function getSped($sped_id)
    {
        return self::$spEd[$sped_id];
    }

    protected static $availableGradeLevels = array(
        'OR-K' => 'Oregon Kindergarten (5)',
        'K' => 'Kindergarten (5)',
        1 => '1st grade (6)',
        '2nd grade (7)',
        '3rd grade (8)',
        '4th grade (9)',
        '5th grade (10)',
        '6th grade (11)',
        '7th grade (12)',
        '8th grade (13)',
        '9th grade (14)',
        '10th grade (15)',
        '11th grade (16)',
        '12th grade (17/18)'
    );

    public static function getAvailableGradeLevels()
    {
        return self::$availableGradeLevels;
    }

    public static function gradeLevelFullLabel($gradeLevel)
    {
        return isset(self::$availableGradeLevels[$gradeLevel]) ? self::$availableGradeLevels[$gradeLevel] : '';
    }

    protected static $availableGradeLevelsNormal = array(
        'OR-K' => 'Oregon Kindergarten',
        'K' => 'Kindergarten',
        1 => '1st grade',
        '2nd grade',
        '3rd grade',
        '4th grade',
        '5th grade',
        '6th grade',
        '7th grade',
        '8th grade',
        '9th grade',
        '10th grade',
        '11th grade',
        '12th grade'
    );

    public static function getAvailableGradeLevelsNormal()
    {
        return self::$availableGradeLevelsNormal;
    }

    protected static $availableGradeLevelsShort = array(
        'OR-K' => 'OR - K',
        'K' => 'K',
        1 => '1st',
        '2nd',
        '3rd',
        '4th',
        '5th',
        '6th',
        '7th',
        '8th',
        '9th',
        '10th',
        '11th',
        '12th'
    );

    public static function getAvailableGradeLevelsShort()
    {
        return self::$availableGradeLevelsShort;
    }

    protected static $getAvailableGradeLevelsText = array(
        'OR-K' => 'OR - Kindergarten',
        'K' => 'Kindergarten',
        1 => 'First grade',
        'Second grade',
        'Third grade',
        'Fourth grade',
        'Fifth grade',
        'Sixth grade',
        'Seventh grade',
        'Eighth grade',
        'Nineth grade',
        'Tenth grade',
        'Eleventh grade',
        'Twelfth grade'
    );

    public function setLimit($value)
    {
        self::$limit = $value;
    }

    public function setPage($value)
    {
        self::$page = $value;
    }

    public function setSkyward()
    {
        self::$skyward = true;
    }

    public function setPaginate($value)
    {
        self::$paginate = $value;
    }

    public static function getAvailableGradeLevelsText()
    {
        return self::$getAvailableGradeLevelsText;
    }

    public function setSortField($value)
    {
        self::$sortField = $value;
    }

    public function setSortOrder($value)
    {
        self::$sortOrder = $value;
    }

    public function setSearchValue($value)
    {
        self::$searchValue = $value;
    }

    /**
     *
     * @param array $filter Can use ParentID, StudentID (array), PersonID (array)
     * @return mth_student[]
     */
    public static function getStudents(array $filter = null, $order = true)
    {
        if ((isset($filter['StudentID']) && empty($filter['StudentID']))
            || (isset($filter['PersonID']) && empty($filter['PersonID']))
        ) {
            return [];
        }
        $sql = '
            SELECT * FROM mth_student AS s
            LEFT JOIN mth_person AS p ON p.person_id=s.person_id
            LEFT JOIN mth_packet AS mp ON mp.student_id=s.student_id
            LEFT JOIN(
                SELECT
                    gl.student_id,
                    gl.grade_level,
                    (YEAR(msy.date_end)+(12-gl.grade_level)) as graduation,
                    msy.date_end as date_end
                    FROM
                        mth_student_grade_level AS gl
                    INNER JOIN mth_student AS ss
                    ON
                        gl.student_id = ss.student_id
                    INNER JOIN mth_schoolyear AS msy
                    ON
                        msy.school_year_id=gl.school_year_id
                    WHERE gl.school_year_id=11
                    GROUP BY
                        gl.student_id 
            ) AS msgl
            ON
                msgl.student_id = s.student_id 
            LEFT JOIN(
                select count(mss.student_id) as count_num, mss.student_id as mss_id from mth_student_school as mss 
                LEFT JOIN mth_student AS ss
                ON mss.student_id=ss.student_id 
                where mss.student_id=ss.student_id and mss.school_of_enrollment=4 and mss.school_year_id=10 GROUP BY
                mss.student_id ) AS mss_c ON
                mss_c.mss_id = s.student_id 
            WHERE 1
            ' . (isset($filter['ParentID'])
            ? 'AND (s.parent_id=' . (int) $filter['ParentID'] . ' OR s.parent2_id=' . (int) $filter['ParentID'] . ' )'
            : '') . '
            ' . (isset($filter['StudentID'])
            ? 'AND s.student_id IN (' . implode(',', array_map('intval', (array) $filter['StudentID'])) . ')'
            : '') . '
            ' . (isset($filter['PersonID'])
            ? 'AND s.person_id IN (' . implode(',', array_map('intval', (array) $filter['PersonID'])) . ')'
            : '') .
            (self::$searchValue != "" ? ' AND ( p.last_name like "%' . self::$searchValue . '%" OR p.first_name like "%' . self::$searchValue . '%" OR msgl.grade_level like "%' . self::$searchValue . '%" OR DATE_FORMAT(mp.date_assigned_to_soe, "%m/%d/%Y") like "%' . self::$searchValue . '%" OR DATE_FORMAT(p.date_of_birth, "%m/%d/%Y") like "%' . self::$searchValue . '%" OR mp.school_district like "%' . self::$searchValue . '%" OR mp.last_school like "%' . self::$searchValue . '%" OR msgl.graduation like "%' . self::$searchValue . '%" )' : '') .
            ' group by s.student_id ';

        if (self::$sortField == "last_name") {
            $sql .= ' ORDER BY p.last_name ' . self::$sortOrder;
        } elseif (self::$sortField == "first_name") {
            $sql .= ' ORDER BY p.first_name ' . self::$sortOrder;
        }  elseif (self::$sortField == "grade_level") {
            $sql .= ' ORDER BY msgl.grade_level ' . self::$sortOrder;
        } elseif (self::$sortField == "graduation") {
            $sql .= ' ORDER BY msgl.graduation ' . self::$sortOrder;
        } elseif (self::$sortField == "date_soe") {
            $sql .= ' ORDER BY CAST(mp.date_assigned_to_soe AS DATE) ' . self::$sortOrder;
        } elseif (self::$sortField == "birthday") {
            $sql .= ' ORDER BY CAST(p.date_of_birth AS DATE) ' . self::$sortOrder;
        } elseif (self::$sortField == "middle_name") {
            $sql .= ' ORDER BY p.middle_name ' . self::$sortOrder;
        } elseif (self::$sortField == "district") {
            $sql .= ' ORDER BY mp.school_district ' . self::$sortOrder;
        } elseif(self::$sortField == "status"){
            $sql .= ' ORDER BY mss_c.count_num ' . self::$sortOrder; 
        } elseif(self::$sortField == "status_year"){
            $sql .= ' ORDER BY mss_c.count_num ' . self::$sortOrder; 
        } elseif(self::$sortField == "p_soe"){
            $sql .= ' ORDER BY mp.last_school ' . self::$sortOrder; 
        } else {
            $sql .= ($order ? ' ORDER BY p.preferred_first_name, p.preferred_last_name' : 'ORDER BY field(s.student_id, ' . implode(',', array_map('intval', (array) $filter['StudentID'])) . ')');
        }

        if (self::$paginate) {
            $sql .= ' LIMIT ' . self::$page . ', ' . self::$limit;
        }
        $result = core_db::runQuery($sql);
        $students = [];
        while ($student = $result->fetch_object(mth_student::class)) {
            /** @var mth_student $student */
            $students[$student->student_id] = $student;
            self::$cache['student_id'][$student->student_id] = $student;
        }
        $result->free_result();
        return $students;
    }

    public static function getAllStudents(array $filter = null, $order = true)
    {
        if ((isset($filter['StudentID']) && empty($filter['StudentID']))
            || (isset($filter['PersonID']) && empty($filter['PersonID']))
        ) {
            return [];
        }
        $result = core_db::runQuery('
            SELECT * FROM mth_student AS s
            INNER JOIN mth_person AS p ON p.person_id=s.person_id
            WHERE 1
            ' . (isset($filter['ParentID'])
            ? 'AND (s.parent_id=' . (int) $filter['ParentID'] . ' OR s.parent2_id=' . (int) $filter['ParentID'] . ' )'
            : '') . '
            ' . (isset($filter['StudentID'])
            ? 'AND s.student_id IN (' . implode(',', array_map('intval', (array) $filter['StudentID'])) . ')'
            : '') . '
            ' . (isset($filter['PersonID'])
            ? 'AND s.person_id IN (' . implode(',', array_map('intval', (array) $filter['PersonID'])) . ')'
            : '') . ($order ? ' ORDER BY p.preferred_first_name, p.preferred_last_name' : 'ORDER BY field(s.student_id, ' . implode(',', array_map('intval', (array) $filter['StudentID'])) . ')'));
        $students = [];
        while ($student = $result->fetch_object(mth_student::class)) {
            /** @var mth_student $student */
            $students[$student->student_id] = $student;
            self::$cache['student_id'][$student->student_id] = $student;
        }
        $result->free_result();
        return $students;
    }

    public function getFilteredStudentCount(array $filter = null, $order = true)
    {
        if ((isset($filter['StudentID']) && empty($filter['StudentID']))
            || (isset($filter['PersonID']) && empty($filter['PersonID']))
        ) {
            return [];
        }
        $sql = '
        SELECT * FROM mth_student AS s
        INNER JOIN mth_person AS p ON p.person_id=s.person_id
        LEFT JOIN mth_packet AS mp ON mp.student_id=s.student_id
        LEFT JOIN(
            SELECT
                gl.student_id,
                gl.grade_level,
                (YEAR(msy.date_end)+(12-gl.grade_level)) as graduation,
                msy.date_end as date_end
                FROM
                    mth_student_grade_level AS gl
                INNER JOIN mth_student AS ss
                ON
                    gl.student_id = ss.student_id
                INNER JOIN mth_schoolyear AS msy
                ON
                    msy.school_year_id=gl.school_year_id
                WHERE gl.school_year_id=11
                GROUP BY
                    gl.student_id 
        ) AS msgl
        ON
            msgl.student_id = s.student_id 
       
        WHERE 1
        ' . (isset($filter['ParentID'])
            ? 'AND (s.parent_id=' . (int) $filter['ParentID'] . ' OR s.parent2_id=' . (int) $filter['ParentID'] . ' )'
            : '') . '
            ' . (isset($filter['StudentID'])
            ? 'AND s.student_id IN (' . implode(',', array_map('intval', (array) $filter['StudentID'])) . ')'
            : '') . '
            ' . (isset($filter['PersonID'])
            ? 'AND s.person_id IN (' . implode(',', array_map('intval', (array) $filter['PersonID'])) . ')'
            : '') .
            (self::$searchValue != "" ? ' AND ( p.last_name like "%' . self::$searchValue . '%" OR p.first_name like "%' . self::$searchValue . '%" OR msgl.grade_level like "%' . self::$searchValue . '%" OR DATE_FORMAT(mp.date_assigned_to_soe, "%m/%d/%Y") like "%' . self::$searchValue . '%" OR DATE_FORMAT(p.date_of_birth, "%m/%d/%Y") like "%' . self::$searchValue . '%" OR mp.school_district like "%' . self::$searchValue . '%" OR mp.last_school like "%' . self::$searchValue . '%" OR msgl.graduation like "%' . self::$searchValue . '%" )' : '') .
            ' group by s.student_id ';
        $result = core_db::runQuery($sql);
        $students = [];
        while ($student = $result->fetch_object(mth_student::class)) {
            /** @var mth_student $student */
            $students[$student->student_id] = $student;
            self::$cache['student_id'][$student->student_id] = $student;
        }
        $result->free_result();
        return count($students);
    }


    /**
     * @return mth_student[]|bool
     */
    public static function getStudentsWithoutPackets()
    {
        return core_db::runGetObjects(
            'SELECT s.*,pe.* FROM mth_student AS s 
                                        INNER JOIN mth_person AS pe ON pe.person_id=s.person_id
                                        LEFT JOIN mth_packet AS p ON p.student_id=s.student_id
                                        WHERE p.packet_id IS NULL',
            'mth_student'
        );
    }

    /**
     *
     * @param array $filter
     * @param bool $reset
     * @return mth_student
     */
    public static function each(array $filter = null, $reset = false)
    {
        $result = &self::$cache['each-' . serialize($filter)];

        if (!isset($result)) {
            if ((isset($filter['StudentID']) && empty($filter['StudentID']))
                || (isset($filter['PersonID']) && empty($filter['PersonID']))
            ) {
                $result = core_db::runQuery('SELECT * FROM mth_student WHERE 0');
            } else {
                $result = core_db::runQuery('
                    SELECT * FROM mth_student AS s
                      INNER JOIN mth_person AS p ON p.person_id=s.person_id
                    WHERE 1
                      ' . (isset($filter['ParentID'])
                    ? 'AND (s.parent_id=' . (int) $filter['ParentID'] . ' OR s.parent2_id=' . (int) $filter['ParentID'] . ' )'
                    : '') . '
                      ' . (isset($filter['StudentID'])
                    ? 'AND s.student_id IN (' . implode(',', array_map('intval', (array) $filter['StudentID'])) . ')'
                    : '') . '
                      ' . (isset($filter['PersonID'])
                    ? 'AND s.person_id IN (' . implode(',', array_map('intval', (array) $filter['PersonID'])) . ')'
                    : ''));
            }
        }
        if (!$reset && ($student = $result->fetch_object('mth_student'))) {
            return $student;
        }
        $result->data_seek(0);
        return null;
    }

    public static function create()
    {
        core_db::runQuery('INSERT INTO mth_student (person_id) VALUES (' . parent::create() . ')');
        return self::getByStudentID(core_db::getInsertID());
    }

    public function makeUser()
    {
        if ($this->user_id || empty($this->email)) {
            return false;
        }
        if (($newUser = core_user::newUser($this->email, $this->getPreferredFirstName(), $this->getPreferredLastName(), mth_user::L_STUDENT))) {
            $this->user_id = $newUser->getID();
            $newUser->changePassword($this->getDateOfBirth());
            return core_db::runQuery('UPDATE mth_person SET user_id=' . $this->user_id . ' 
                                WHERE person_id=' . $this->getPersonID());
        }
        return false;
    }

    /**
     *
     * @param int $person_id
     * @return int $student_id
     */
    public static function isStudent($person_id)
    {
        if (empty(self::$cache['student_person_ids'])) {
            $results = core_db::runQuery('SELECT student_id, person_id FROM mth_student');
            while ($row = $results->fetch_object()) {
                self::$cache['student_person_ids'][$row->person_id] = $row->student_id;
            }
        }
        return isset(self::$cache['student_person_ids'][$person_id])
            ? self::$cache['student_person_ids'][$person_id]
            : false;
    }

    public function getType()
    {
        return 'student';
    }

    public function getHomeroomTeacher($school_year_id)
    {
        $sql = 'select cu.* from yoda_student_homeroom as ysh
        inner join yoda_courses as yc on yc.id=ysh.yoda_course_id 
        inner join core_users as cu on cu.user_id=yc.teacher_user_id
        where student_id=' . $this->student_id . ' and ysh.school_year_id=' . $school_year_id;

        return core_db::runGetObject($sql, 'core_user');
    }

    /**
     *
     * @param int $person_id
     * @return mth_student
     */
    public static function getByPersonID($person_id)
    {
        if (!isset(self::$cache['person_id'][$person_id])) {
            self::$cache['person_id'][$person_id] = core_db::runGetObject('
                        SELECT * 
                          FROM mth_student AS s 
                            INNER JOIN mth_person AS p ON s.person_id=p.person_id
                          WHERE s.person_id=' . (int) $person_id, 'mth_student');
            if (self::$cache['person_id'][$person_id]) {
                self::$cache['student_id'][self::$cache['person_id'][$person_id]->getID()] = self::$cache['person_id'][$person_id];
            }
        }
        return self::$cache['person_id'][$person_id];
    }

    /**
     *
     * @param str $email
     * @return mth_student
     */
    public static function getByEmail($email)
    {
        return core_db::runGetObject(
            'SELECT * 
                                  FROM mth_student AS s 
                                    INNER JOIN mth_person AS p ON s.person_id=p.person_id
                                  WHERE p.email="' . core_db::escape(strtolower(trim($email))) . '"',
            'mth_student'
        );
    }

    /**
     *
     * @param int $user_id
     * @return mth_student
     */
    public static function getByUserID($user_id)
    {
        $student = &self::$cache['getByUserID'][$user_id];
        if ($student === null) {
            $student = core_db::runGetObject('SELECT * 
                                  FROM mth_student AS s 
                                    INNER JOIN mth_person AS p ON s.person_id=p.person_id
                                  WHERE p.user_id=' . (int) $user_id, 'mth_student');
            if ($student) {
                self::$cache['person_id'][$student->getPersonID()] = $student;
                self::$cache['student_id'][$student->getID()] = $student;
            }
        }
        return $student;
    }

    /**
     *
     * @param string $slug
     * @return mth_student
     */
    public static function getBySlug($slug)
    {
        $slugArr = explode('-', $slug);
        return self::getByStudentID(end($slugArr));
    }

    /**
     *
     * @param int $student_id
     * @return mth_student
     */
    public static function getByStudentID($student_id, $no_cache = false )
    {
        if (!isset(self::$cache['student_id'][$student_id]) || $no_cache) {
            self::$cache['student_id'][$student_id] = core_db::runGetObject('
                                SELECT * 
                                  FROM mth_student AS s 
                                    INNER JOIN mth_person AS p ON s.person_id=p.person_id
                                  WHERE s.student_id=' . (int) $student_id, 'mth_student');
            if (self::$cache['student_id'][$student_id]) {
                self::$cache['person_id'][self::$cache['student_id'][$student_id]->getPersonID()] = self::$cache['student_id'][$student_id];
            }
        }
        return self::$cache['student_id'][$student_id];
    }

    public function getID()
    {
        return (int) $this->student_id;
    }

    public function setParent(mth_parent $parent)
    {
        $this->parent_id = $parent->getID();
        $this->updateQuery[] = 'parent_id=' . $this->parent_id;
    }

    public function getParentID()
    {
        return (int) $this->parent_id;
    }

    /**
     *
     * @return mth_parent
     */
    public function getParent()
    {
        return mth_parent::getByParentID($this->parent_id);
    }

    public function getObserver()
    {
        return mth_parent::getByParentID($this->parent2_id);
    }

    public function updateStudentStatusHistory()
    {
        $last_student_status = core_db::runGetObject('select * from mth_student_status where student_id=' . $this->getID() . ' order by date_updated DESC');

        if (!$last_student_status) {
            return false;
        }

        $student_status_history = core_db::runGetObject('select * from mth_student_status_history where student_id=' . $this->getID() . ' 
                                                                AND school_year_id=' . $last_student_status->school_year_id . ' 
                                                                AND status=' . $last_student_status->status . ' 
                                                                AND date_updated="' . $last_student_status->date_updated . '" order by date_updated DESC');

        if ($student_status_history) {
            return true;
        }

        return core_db::runQuery('INSERT INTO mth_student_status_history (student_id, school_year_id, status, date_updated) 
                                    VALUES (' . $this->getID() . ',' . $last_student_status->school_year_id . ',' . $last_student_status->status . ', "' . $last_student_status->date_updated . '" )');
    }

    public function getStatusSchoolYear()
    {
        $result = core_db::runGetObject('SELECT y.* 
                                      FROM mth_schoolyear AS y
                                        LEFT JOIN mth_student_status AS ss ON ss.school_year_id=y.school_year_id
                                      WHERE ss.student_id=' . $this->getID() . ' AND ss.status=' . $this->getStatus() . '', 'mth_schoolYear');
        if ($result) {
            return $result;
        }
        return false;
    }

    public function setStatus($status, mth_schoolYear $year, $status_date = null)
    {
        $this->updateStudentStatusHistory();
        if ($this->getStatus($year) == $status) {
            return true;
        }
        if (empty($status)) {
            return core_db::runQuery('DELETE FROM mth_student_status 
                                  WHERE student_id=' . $this->getID() . ' 
                                    AND school_year_id=' . $year->getID());
        }
        if (!isset(self::$availableStatuses[$status])) {
            return false;
        }

        if ($status == self::STATUS_WITHDRAW) {
            $archive = mth_archive::record($this, $year, true);
            if (!$archive->execute()) {
                error_log('Unable to archive the student ' . $this->getID() . ' record.');
            }
        }

        $_status_date = is_null($status_date) ? 'NOW()' : '"' . $status_date . '"';

        return core_db::runQuery('REPLACE INTO mth_student_status 
                              (student_id, school_year_id, `status`, date_updated)
                              VALUES (' . $this->getID() . ',' . $year->getID() . ',' . (int) $status . ', ' . $_status_date . ')');
    }

    public function setEmail($email)
    {
        $sendChangeNotice = !empty($this->email) && $this->email != trim(strtolower($email));
        if (!parent::setEmail($email)) {
            return false;
        }
        if ($sendChangeNotice) {
            $this->getParent()->sendEmailChangeNotice();
        }
        return true;
    }

    public function __destruct()
    {
        parent::__destruct();
        if (empty($this->updateQuery)) {
            return;
        }
        core_db::runQuery('UPDATE mth_student 
                        SET ' . implode(',', $this->updateQuery) . ' 
                        WHERE student_id=' . $this->getID());
    }

    public function setObserver($parent_id)
    {
        if (!$parent_id) {
            return false;
        }
        return core_db::runQuery('UPDATE mth_student 
                        SET parent2_id=' . $parent_id . '
                        WHERE student_id=' . $this->getID());
    }

    public function getSlug()
    {
        return preg_replace('/[^0-9a-z]+/', '-', strtolower($this->getPreferredFirstName() . '-' . $this->getPreferredLastName() . '-' . $this->getID()));
    }

    public function isEditable(core_user $user = null)
    {
        $user = (!$user) ? core_user::getCurrentUser() : $user;

        //if user is admin
        if ($user && ($user->isAdmins())) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $student = $user->isStudent() ? self::getByUserID($user->getID()) : false;

        //if user is student make sure user is using own id
        if ($student) {
            return $this->getID() == $student->getID();
        }


        //if user is assistant make sure to access student only under them
        if ($user->isAssistant()) {
            if ($obj = mth_assistant::getByUserId($user->getID())) {
                foreach ($obj as $assistant) {
                    if ($assistant->isAssignToSchool() && $this->getSchoolOfEnrollment(true) == $assistant->getValue()) {
                        return true;
                    }
                    if ($assistant->isAssignToProvider() && (($sched = $this->schedule()) && in_array($assistant->getValue(), $sched->getCurrentProviders()))) {
                        return true;
                    }
                    if ($assistant->isAssignToSped() && $this->specialEd() == $assistant->getValue()) {
                        return true;
                    }
                }
            }
        }

        if (($parent = mth_parent::getByUserID($user->getID()))
            && ($parent->getID() == $this->parent_id
                || $parent->getID() == $this->parent2_id)
        ) {
            return true;
        }
        return false;
    }


    public function setGradeLevel($grade_level, mth_schoolYear $schoolYear)
    {
        if (!isset(self::$availableGradeLevels[$grade_level])) {
            return false;
        }
        $this->year_grade_levels = null;
        self::$cache['getGradeLevelValue'] = array();
        return core_db::runQuery('REPLACE INTO mth_student_grade_level (student_id, school_year_id, grade_level)
                                VALUES (' . $this->getID() . ',' . $schoolYear->getID() . ',"' . core_db::escape($grade_level) . '")');
    }

    /**
     * @param null|int|mth_schoolYear $school_year_id
     * @return bool
     */
    public function getGradeLevelValue($school_year_id = null)
    {
        if (is_object($school_year_id)) {
            $school_year_id = is_callable(array($school_year_id, 'getID')) ? $school_year_id->getID() : null;
        }
        $grade_level = &self::$cache['getGradeLevelValue'][$this->student_id][$school_year_id];
        if ($grade_level === null) {
            $this->populateGradeLevels();
            if ($school_year_id == null && ($schoolYear = mth_schoolYear::getCurrent())) {
                $school_year_id = $schoolYear->getID();
            }
            if (isset($this->year_grade_levels[$school_year_id])) {
                $grade_level = $this->year_grade_levels[$school_year_id];
            } else {
                $grade_level = false;
            }
        }
        return $grade_level;
    }

    public function populateNextYearGradeLevel()
    {
        if (
            !($nextY = mth_schoolYear::getNext())
            || $this->getGradeLevelValue($nextY->getID()) === false
            || !($currentY = mth_schoolYear::getCurrent())
            || !($currentG = $this->getGradeLevelValue($currentY->getID()))
            || $currentG > 11
            || $nextY->getID() == $currentY->getID()
        ) {
            return false;
        }
        return $this->setGradeLevel($currentG + 1, $nextY);
    }

    public function fixYearGradeLevel($school_year)
    {
        if (
            !($currentY = mth_schoolYear::getCurrent())
            || !($currentG = $this->getGradeLevelValue())
            || $currentG > 11
        ) {
            return false;
        }

        $currentG = ($school_year->getID() != $currentY->getID()) ? ($currentG + 1) : $currentG;

        return $this->setGradeLevel($currentG + 1, $school_year) && $this->populateNextYearGradeLevel();
    }

    public function getGradeLevel($returnDesc = false, $shortDesc = false, $school_year_id = null, $textDesc = false)
    {
        $grade_level = $this->getGradeLevelValue($school_year_id);
        if ($returnDesc && $grade_level) {
            $gradeLevels = $shortDesc ? self::getAvailableGradeLevelsShort() : self::getAvailableGradeLevelsNormal();
            return $gradeLevels[$grade_level];
        }
        return $grade_level;
    }

    /**
     * @param bool $number
     * @param mth_schoolYear|NULL $year
     * @return SchoolOfEnrollment|int
     */
    public function getSchoolOfEnrollment($number = false, mth_schoolYear $year = null)
    {
        $this->populateSchools();
        if (is_null($year)) {
            $year = mth_schoolYear::getCurrent();
        }
        if (
            !$year
            || !isset($this->year_schools[$year->getID()])
        ) {
            return $number ? SchoolOfEnrollment::Unassigned : SchoolOfEnrollment::get(SchoolOfEnrollment::Unassigned);
        }
        $school = $this->year_schools[$year->getID()];

        if (!$number && ($SOE = SchoolOfEnrollment::get($school))) {
            return $SOE;
        }
        return $school;
    }

    /**
     * @param bool $number
     * @param mth_schoolYear|NULL $year
     * @return SchoolOfEnrollment|int
     */
    public function getWithdrawalSOE($number = false, mth_schoolYear $year = null)
    {
        $this->populateSchools();
        if (is_null($year)) {
            $year = mth_schoolYear::getCurrent();
        }

        if (
            empty($this->year_schools) || !$year
            || !isset($this->year_schools[$year->getID()])
        ) {
            return $this->getSchoolOfEnrollment($number, $year->getPreviousYear());
        }

        $school = $this->year_schools[$year->getID()];

        if ($school == SchoolOfEnrollment::Unassigned) {
            return $this->getSchoolOfEnrollment($number, $year->getPreviousYear());
        }

        return $this->getSchoolOfEnrollment($number, $year);
    }

    public function getFirstSOE($name = true, $longname = true)
    {
        $firstSOE = &self::$cache['getFirstSOE'][$name ? 'name' : 'obj'][$this->student_id];
        if ($firstSOE === null) {
            $soe_id = core_db::runGetValue('select school_of_enrollment from mth_student_school where student_id=' . $this->student_id . ' order by school_year_id limit 1');
            if ($soe_id && ($school = SchoolOfEnrollment::get($soe_id))) {
                if ($name) {
                    $firstSOE = $longname ? $school->getLongName() : $school->getShortName();
                } else {
                    $firstSOE = $school;
                }
            }
        }
        return $firstSOE;
    }

    public function getSOEname(mth_schoolYear $year = null, $longname = true)
    {
        if (($school = $this->getSchoolOfEnrollment(false, $year))) {
            return $longname ? $school->getLongName() : $school->getShortName();
        }
        return null;
    }

    public function getSOEaddress($nl2br = true, mth_schoolYear $year = null)
    {
        if (($school = $this->getSchoolOfEnrollment(false, $year))) {
            return $school->getAddresses($nl2br);
        }
        return null;
    }


    public function getSOEphones($nl2br = true, mth_schoolYear $year = null)
    {
        if (($school = $this->getSchoolOfEnrollment(false, $year))) {
            return $school->getPhones($nl2br);
        }
        return null;
    }

    public function getSOEnameAndAddress($nl2br = true, mth_schoolYear $year = null)
    {
        if (($school = $this->getSchoolOfEnrollment(false, $year))) {
            $return = $school->getLongName() . PHP_EOL . $school->getAddresses(false);
            return $nl2br ? nl2br($return) : $return;
        }
        return null;
    }

    public function setSchoolOfEnrollment(SchoolOfEnrollment $schoolOfEnrollment, mth_schoolYear $year, $transferred = 0)
    {
        if (mth_schoolYear::getCurrent() == $year && $this->getSchoolOfEnrollment() != $schoolOfEnrollment) {
            $packet = mth_packet::getStudentPacket($this);
            $packet->dateAssignedToSoe(true);
        }
        // echo $transferred;exit;
        $this->year_schools = null;
        return core_db::runQuery('REPLACE INTO mth_student_school
           (student_id, school_year_id, `school_of_enrollment`, transferred)
            VALUES (' . $this->getID() . ',' . $year->getID() . ',' . $schoolOfEnrollment->getId() . ',' . $transferred . ')');
    }

    public function getAddress()
    {
        $parent = $this->getParent();
        if (!$parent) {
            return null;
        }
        return $parent->getAddress();
    }

    public static function getAllAddress()
    {
        $arr = array();
        $result = core_db::runQuery('SELECT pa.person_id, a.address_id, a.name, a.street, a.street2, a.city, a.state, a.zip FROM mth_address AS a 
            LEFT JOIN mth_person_address AS pa 
            ON pa.address_id=a.address_id');
        if ($result) {
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = array(
                    'address_id' => $r[1],
                    'name' => $r[2],
                    'street' => $r[3],
                    'street2' => $r[4],
                    'city' => $r[5],
                    'state' => $r[6],
                    'zip' => $r[7]
                );
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    public static function getAllStudentStatusByYear($year_id)
    {
        $arr = array();
        $result = core_db::runQuery('SELECT ss.student_id, ss.date_updated 
            FROM mth_student_status AS ss
            LEFT JOIN mth_schoolyear AS y ON ss.school_year_id=y.school_year_id
            WHERE ss.school_year_id=' . $year_id . '
            ORDER BY y.date_begin ASC');
        if ($result) {
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = $r[1];
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    public function populateStatuses()
    {
        if (is_null($this->year_statuses)) {
            $this->year_statuses = array();
            $result = core_db::runQuery('SELECT ss.* 
                                      FROM mth_student_status AS ss
                                        LEFT JOIN mth_schoolyear AS y ON ss.school_year_id=y.school_year_id
                                      WHERE ss.student_id=' . $this->getID() . '
                                      ORDER BY y.date_begin ASC');
            while ($r = $result->fetch_object()) {
                $this->year_statuses[$r->school_year_id] = (int) $r->status;
                $this->year_status_dates[$r->school_year_id] = $r->date_updated;
            }
            $result->free_result();
        }
    }

    public function populateWithdrawals()
    {
        if (is_null($this->year_withdrawals)) {
            $this->year_withdrawals = array();
            $result = core_db::runQuery('SELECT wit.* 
                                      FROM mth_withdrawal wit
                                      WHERE wit.student_id=' . $this->getID());
            while ($r = $result->fetch_array(MYSQLI_ASSOC)) {
                $withdrawalData = [
                    'withdrawal_id' => $r['withdrawal_id'],
                    'school_year_id' => $r['school_year_id'],
                    'datetime' => $r['datetime'],
                    'effective_date' => $r['effective_date'],
                    'intent_reenroll_action' => $r['intent_reenroll_action'],
                ];
                $this->year_withdrawals[$r['school_year_id']] = $withdrawalData;
            }
            $result->free_result();
        }
    }

    public function populateSchools()
    {
        if (is_null($this->year_schools)) {
            $this->year_schools = array();
            if (!$this->getLastStatus() && !core_user::isUserAdmin()) {
                return;
            }
            $result = core_db::runQuery('SELECT * FROM mth_student_school WHERE student_id=' . $this->getID());
            while ($r = $result->fetch_object()) {
                $this->year_schools[$r->school_year_id] = $r->school_of_enrollment;
                $this->year_schoolsWithTransfer[$r->school_year_id] = $r;
            }
            $result->free_result();
        }
    }

    public static function getAllSchools($year_id)
    {
        $arr = array();
        $result = core_db::runQuery('SELECT student_id, school_of_enrollment FROM mth_student_school WHERE school_year_id=' . $year_id);
        if ($result) {
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = $r[1];
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    public static function getAllGradeLevelsByYearId($year_id)
    {
        $arr = array();
        $result = core_db::runQuery('SELECT gl.student_id, gl.grade_level 
                FROM mth_student_grade_level AS gl
                LEFT JOIN mth_schoolyear AS y ON gl.school_year_id=y.school_year_id
                WHERE gl.school_year_id=' . $year_id . '
                ORDER BY y.date_begin ASC');
        if ($result) {
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = $r[1];
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    public static function getAllParent()
    {
        $arr = array();
        $result = core_db::runQuery('SELECT parent_id, person_id 
                FROM mth_parent');
        if ($result) {
            while ($r = $result->fetch_row()) {
                $arr[$r[0]] = $r[1];
            }
            $result->free_result();
            return $arr;
        }
        return false;
    }

    public function populateGradeLevels()
    {
        if ($this->year_grade_levels === null) {
            $this->year_grade_levels = array();
            $baseYearId = null;
            $baseYear = null;
            $baseValue = null;
            $result = core_db::runQuery('SELECT gl.* 
                                    FROM mth_student_grade_level AS gl
                                      LEFT JOIN mth_schoolyear AS y ON gl.school_year_id=y.school_year_id
                                    WHERE gl.student_id=' . $this->getID() . '
                                    ORDER BY y.date_begin ASC');
            while ($r = $result->fetch_object()) {
                $this->year_grade_levels[$r->school_year_id] = $r->grade_level;
                if ($baseYearId < $r->school_year_id) {
                    $baseYearId = $r->school_year_id;
                    $baseValue = $r->grade_level;
                }
            }
            $result->free_result();
            if (!$baseYearId) {
                return;
            }
            if (!($sy = mth_schoolYear::getByID($baseYearId))) {
                return;
            }

            $baseYear = $sy->getStartYear();
            foreach (mth_schoolYear::getAll() as $schoolYear) {
                if (!isset($this->year_grade_levels[$schoolYear->getID()])) {
                    $thisGrade = ($schoolYear->getStartYear() - $baseYear) + ($baseValue == 'K' ? 0 : ($baseValue == 'OR-K' ? -1 : $baseValue));
                    if ($thisGrade < 0) {
                        continue;
                    }
                    if ($thisGrade == 0) {
                        $thisGrade = 'K';
                    } elseif ($thisGrade == -1) {
                        $thisGrade = 'OK-R';
                    }
                    $this->year_grade_levels[$schoolYear->getID()] = $thisGrade;
                }
            }
        }
    }

    public function getStatus(mth_schoolYear $year = null)
    {
        $this->populateStatuses();
        if (is_null($year)) {
            $year = mth_schoolYear::getCurrent();
        }
        if (!$year || !isset($this->year_statuses[$year->getID()])) {
            return null;
        }
        return $this->year_statuses[$year->getID()];
    }

    public function getWithdrawal(mth_schoolYear $year = null)
    {
        $this->populateWithdrawals();
        if (is_null($year)) {
            $year = mth_schoolYear::getCurrent();
        }
        if (!$year || !isset($this->year_withdrawals[$year->getID()])) {
            return null;
        }
        return $this->year_withdrawals[$year->getID()];
    }

    public function getPreviousWithdrawal()
    {
        return core_db::runGetObject('SELECT * FROM mth_student_status_history 
                        WHERE student_id =' . $this->getID() . ' AND status=3 ORDER BY date_updated DESC LIMIT 1');
    }

    public function getWithdrawalDate(mth_schoolYear $year = null, $format = null)
    {
        $withdrawal = $this->getWithdrawal($year);

        if (isset($withdrawal['effective_date']) && $withdrawal['effective_date'] !== null) {
            return core_model::getDate($withdrawal['effective_date'], $format);
        }

        if (isset($withdrawal['intent_reenroll_action']) && $withdrawal['intent_reenroll_action'] !== null) {
            return core_model::getDate($withdrawal['intent_reenroll_action'], $format);
        }

        if (isset($withdrawal['datetime']) && $withdrawal['datetime'] !== null) {
            return core_model::getDate($withdrawal['datetime'], $format);
        }

        return null;
    }

    public function getStatusDate(mth_schoolYear $year = null, $format = null)
    {
        $this->populateStatuses();
        if (is_null($year)) {
            $year = mth_schoolYear::getCurrent();
        }
        if (
            !$year
            || !isset($this->year_status_dates[$year->getID()])
        ) {
            return null;
        }
        return core_model::getDate($this->year_status_dates[$year->getID()], $format);
    }

    public function getWithdrawalOrStatusDate(mth_schoolYear $year = null, $format = null)
    {
        $withdrawalDate = $this->getWithdrawalDate($year, $format);

        return $withdrawalDate !== null ? $withdrawalDate : $this->getStatusDate($year, $format);
    }

    public function getStatusLabel(mth_schoolYear $year = null)
    {
        if (($status = $this->getStatus($year))) {
            return self::$availableStatuses[$status];
        }
        return null;
    }

    public function getStudentApplication()
    {
        return mth_application::getStudentApplication($this);
    }

    public function getLastStatus()
    {
        $lastStatus = &self::$cache['getLastStatus'][$this->student_id];
        if ($lastStatus === null) {
            $this->populateStatuses();
            $year = mth_schoolYear::getCurrent();
            if (isset($this->year_statuses[$year->getID()])) {
                $lastStatus = $this->year_statuses[$year->getID()];
                if (
                    $lastStatus == self::STATUS_WITHDRAW
                    && ($app = mth_application::getStudentApplication($this))
                    && $app->getSchoolYear(true)->getDateBegin() > time()
                ) {
                    $lastStatus = null;
                }
            } else {
                $lastStatus = end($this->year_statuses);
            }
        }
        return $lastStatus;
    }

    public function getFirstStatus()
    {
        $firstStatus = &self::$cache['firstStatus'][$this->student_id];
        if ($firstStatus === null) {
            $this->populateStatuses();
            $firstStatus = reset($this->year_statuses);
        }
        return $firstStatus;
    }

    public function getFirstSchoolYear()
    {
        $firstYear = &self::$cache['getFirstSchoolYear'][$this->student_id];
        if ($firstYear === null) {
            $this->populateStatuses();
            reset($this->year_statuses);
            $firstYear = key($this->year_statuses);
        }
        return $firstYear ? mth_schoolYear::getByID($firstYear) : null;
    }

    public function getLastSchoolYear()
    {
        $lastYear = &self::$cache['getLastSchoolYear'][$this->student_id];
        if ($lastYear === null) {
            $this->populateStatuses();
            end($this->year_statuses);
            $lastYear = key($this->year_statuses);
        }
        return $lastYear ? mth_schoolYear::getByID($lastYear) : null;
    }

    public function isMidYear($statusYear = null)
    {
        if ($statusYear === null) {
            $statusYear = mth_schoolYear::getCurrent();
            $statusYearId = null;
        } else {
            $statusYearId = $statusYear->getID();
        }

        $application = mth_application::getStudentApplication($this, $statusYearId);
        if ($application === null || $application->getSchoolYearID() !== $statusYear->getID()) {
            return false;
        }
        return $application->getMidyearApplication();
    }

    public function statusYearCount()
    {
        $this->populateStatuses();
        return count($this->year_statuses);
    }

    public function getLastStatusLabel()
    {
        if (($status = $this->getLastStatus())) {
            return self::$availableStatuses[$status];
        }
        return null;
    }

    public function isWithdrawn()
    {
        return $this->getLastStatus() == self::STATUS_WITHDRAW;
    }

    public function wasWithdrawn(mth_schoolYear $year = null)
    {
        return $this->getStatus($year) == self::STATUS_WITHDRAW;
    }

    public function isGraduated()
    {
        return $this->getLastStatus() == self::STATUS_GRADUATED;
    }

    public function isWithdrawnOrGraduated()
    {
        $lastStatus = $this->getLastStatus();
        return $lastStatus == self::STATUS_WITHDRAW
            || $lastStatus == self::STATUS_GRADUATED;
    }

    public function getStatuses()
    {
        $this->populateStatuses();
        return $this->year_statuses;
    }

    public function hasBeenActive()
    {
        $this->populateStatuses();
        foreach ($this->year_statuses as $status) {
            if ($status == self::STATUS_ACTIVE) {
                return true;
            }
        }
        return false;
    }

    public function hasAtleastOneImmunization()
    {
        $result = core_db::runGetObject('SELECT * FROM mth_student_immunizations WHERE student_id=' . (int)$this->student_id . ' AND exempt = 1', 'mth_student_immunizations');
        return $result;
    }

    public function hasBeenWithdrawn($exception = 0)
    {
        $this->populateStatuses();
        foreach ($this->year_statuses as $key => $status) {
            if ($key == $exception) {
                continue;
            }
            if ($status == self::STATUS_WITHDRAW) {
                return true;
            }
        }
        return false;
    }

    public function hasPendingOrActiveStatus()
    {
        $this->populateStatuses();
        foreach ($this->year_statuses as $status) {
            if ($status == self::STATUS_ACTIVE || $status == self::STATUS_PENDING) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks the status of the specified year, or the current year if one is not specified
     * @param int $status
     * @param mth_schoolYear $year
     * @return bool
     */
    public function isStatus($status, mth_schoolYear $year = null)
    {
        return $this->getStatus($year) == $status;
    }

    public function isActive(mth_schoolYear $year = null)
    {
        return $this->getStatus($year) == self::STATUS_ACTIVE;
    }

    public function isPendingOrActive(mth_schoolYear $year = null)
    {
        $status = $this->getStatus($year);
        return $status == self::STATUS_ACTIVE
            || $status == self::STATUS_PENDING;
    }

    public function isPending(mth_schoolYear $year = null)
    {
        $status = $this->getStatus($year);
        return $status == self::STATUS_PENDING;
    }

    public function canSubmitSchedule(mth_schoolYear $year = null)
    {
        if (!$year && !($year = mth_schoolYear::getOpenReg())) {
            return false;
        }
        return in_array($this->getStatus($year), array(self::STATUS_ACTIVE, self::STATUS_PENDING));
    }

    /**
     *
     * @param mth_schoolYear $year
     * @return mth_schedule
     */
    public function schedule(mth_schoolYear $year = null)
    {
        return mth_schedule::get($this, $year);
    }

    public function delete()
    {
        if (!$this->canBeDeleted()) {
            return false;
        }
        mth_schedule::deleteStudentSchedules($this);
        mth_application::deleteStudentApplications($this);
        mth_packet::deleteStudentPackets($this);
        mth_purchasedCourse::removeStudent($this);
        $success = core_db::runQuery('DELETE FROM mth_person WHERE person_id=' . $this->getPersonID())
            && core_db::runQuery('DELETE FROM mth_student WHERE student_id=' . $this->getID());
        // if (($parent = $this->getParent())) {
        //     $parent->delete();
        // }

        return $success;
    }

    public function restore(mth_schoolYear $year = null)
    {
        if (!$year) {
            $year = mth_schoolYear::getCurrent();
        }

        if (!$this->canBeRestored($year)) {
            return false;
        }

        $archive = mth_archive::get($this, $year);
        $schedule_restored = true;
        $packet_restored = true;

        if ($schedule = mth_schedule::get($this, $year, true)) {
            $schedule_restored = $schedule->restore($archive);
        } else {
            core_notify::addWarning('Unable to find schedule');
        }

        if ($packet = mth_packet::getStudentPacket($this)) {
            $packet_restored = $packet->restore();
        } else {
            core_notify::addWarning('Unable to find packet');
        }


        if ($archive || $enrollment = memcourse::getStudentHomeroom($this->getID(), $year)) {
            $course = new courses();
            $course->assignToStudent(
                $archive ? $archive->homeroom_id() : $enrollment->getCourseId(),
                $this,
                $year
            );
        }

        $student_stat = $archive ? $archive->student_status() : self::STATUS_ACTIVE;
        $student_stat_date = $archive ? $archive->status_date() : null;

        if ($archive) {
            $archive->delete();
        }

        return $schedule_restored &&
            $packet_restored &&
            core_db::runQuery(
                'REPLACE INTO mth_student_status 
            (student_id, school_year_id, `status`' . ($student_stat_date ? ',date_updated' : '') . ')
            VALUES (' . $this->getID() . ',' . $year->getID() . ',' . $student_stat . ($student_stat_date ? ',"' . $student_stat_date . '"' : '') . ')'
            );;
    }

    public function canBeDeleted()
    {
        return !mth_canvas_user::get($this, false)
            && !mth_reimbursement::countByStudent($this)
            && !$this->hasBeenActive()
            && !$this->hasActiveArchive();
    }

    public function hasActiveArchive()
    {
        $archive = mth_archive::get($this);
        if ($archive && $archive->student_status() == self::STATUS_ACTIVE) {
            return true;
        }
        return false;
    }

    public function hasCurrentAcceptedPacket()
    {
        if ($packets = mth_packet::getStudentPackets($this)) {
            foreach ($packets as $packet) {
                if ($packet->getStatus() == mth_packet::STATUS_ACCEPTED) {
                    return true;
                }
            }
        }
        return false;
    }

    public function canBeRestored(mth_schoolYear $year)
    {
        return $this->wasWithdrawn($year);
    }

    public function setDateOfBirth($date_of_birth)
    {
        if (!self::validateDOB($date_of_birth)) {
            return false;
        }
        return parent::setDateOfBirth($date_of_birth);
    }

    public static function validateDOB($date_of_birth)
    {
        if (!is_int($date_of_birth)) {
            return false;
        }
        return strtotime('-19 years') < $date_of_birth && strtotime('-4 years') > $date_of_birth;
    }

    public function specialEd($returnLabel = false)
    {
        if ($returnLabel) {
            return self::$spEd[(int) $this->special_ed];
        }
        return (int) $this->special_ed;
    }

    public function set_spacial_ed($value)
    {
        if (isset(self::$spEd[$value])) {
            $this->special_ed = (int) $value;
            $this->updateQuery['special_ed'] = 'special_ed=' . $this->special_ed;
        }
    }

    /**
     *
     * @param mth_schoolYear $year
     * @return array array (StatusN => array(students, SPED));
     */
    public static function getStatusCounts(mth_schoolYear $year)
    {
        $arr = &self::$cache['statusCounts-' . $year];
        if (!isset($arr)) {
            $results = core_db::runQuery('SELECT 
                                    COUNT(s.student_id), ss.status, SUM(IF(s.special_ed>0,1,0))
                                    FROM mth_student_status AS ss 
                                      INNER JOIN mth_student AS s ON s.student_id=ss.student_id
                                    WHERE ss.school_year_id=' . $year->getID() . '
                                    GROUP BY ss.status');
            $arr = array();
            while ($r = $results->fetch_row()) {
                $arr[$r[1]] = array($r[0], $r[2]);
            }
            $results->close();
        }
        return $arr;
    }

    public static function getStudentCount(mth_schoolYear $year, $SPED = false, array $studentStatuses = null)
    {
        if (is_null($studentStatuses)) {
            $studentStatuses = array(self::STATUS_ACTIVE, self::STATUS_PENDING);
        }
        $total = 0;
        $statusCounts = self::getStatusCounts($year);
        foreach ($studentStatuses as $status) {
            if (isset($statusCounts[$status][($SPED ? 1 : 0)])) {
                $total += $statusCounts[$status][($SPED ? 1 : 0)];
            }
        }
        return $total;
    }

    public static function getStatusParentCounts(mth_schoolYear $year)
    {
        $results = core_db::runQuery('SELECT 
                                  COUNT(DISTINCT p.parent_id), ss.status 
                                  FROM mth_student_status AS ss 
                                    INNER JOIN mth_student AS s ON s.student_id=ss.student_id
                                    INNER JOIN mth_parent AS p ON p.parent_id=s.parent_id
                                  WHERE ss.school_year_id=' . $year->getID() . '
                                  GROUP BY ss.status');
        $arr = array();
        while ($r = $results->fetch_row()) {
            $arr[$r[1]] = $r[0];
        }
        $results->close();
        return $arr;
    }

    public static function getParentCount(mth_schoolYear $year, array $studentStatuses = null)
    {
        if (is_null($studentStatuses)) {
            $studentStatuses = array(self::STATUS_ACTIVE, self::STATUS_PENDING);
        }
        return core_db::runGetValue('SELECT 
                                  COUNT(DISTINCT p.parent_id)
                                  FROM mth_student_status AS ss 
                                    INNER JOIN mth_student AS s ON s.student_id=ss.student_id
                                    INNER JOIN mth_parent AS p ON p.parent_id=s.parent_id
                                  WHERE ss.school_year_id=' . $year->getID() . '
                                    AND ss.status IN (' . implode(',', array_map('intval', $studentStatuses)) . ')');
    }

    public function diplomaSeeking($set = null)
    {
        if ($set !== null) {
            $this->diploma_seeking = $set ? 1 : 0;
            $this->updateQuery['diploma_seeking'] = '`diploma_seeking`=' . $this->diploma_seeking;
        }
        return $this->diploma_seeking;
    }

    public function setDiplomaSeeking($set)
    {
        $diploma_seeking = $set == NULL ? 'NULL' : $set;
        return core_db::runQuery('UPDATE mth_student 
        SET diploma_seeking=' . $diploma_seeking . '
        WHERE student_id=' . $this->getID());
    }

    public function teacherNotes($set = null, $html = true)
    {
        if ($set !== null) {
            $this->teacher_notes = req_sanitize::multi_txt($set);
            return core_db::runQuery('UPDATE mth_student
            SET teacher_notes="' . $this->teacher_notes . '"
            WHERE student_id=' . $this->getID());
        }
        return ($html ? $this->teacher_notes : strip_tags($this->teacher_notes));
    }

    public function hasAcceptedSiblings()
    {
        $hasAcceptedSiblings = &self::$cache['hasAcceptedSiblings-' . $this->getID()];
        if (!isset($hasAcceptedSiblings)) {
            $hasAcceptedSiblings = core_db::runGetValue('
                                    SELECT COUNT(DISTINCT s.student_id) 
                                    FROM mth_student AS s
                                      INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id
                                    WHERE s.parent_id=' . $this->getParentID() . '
                                      AND s.student_id!=' . $this->getID());
        }
        return $hasAcceptedSiblings;
    }

    /**
     * @param null|mth_schoolYear[]|mth_schoolYear $schoolYear
     * @return int
     */
    public function hasActiveSiblings($schoolYear = null)
    {
        if (!$schoolYear) {
            $schoolYearIds = mth_schoolYear::getCurrent()->getID();
        } elseif (is_array($schoolYear)) {
            $schoolYearIds = array();
            foreach ($schoolYear as $thisSchoolYear) {
                if (!is_object($thisSchoolYear)) {
                    continue;
                }
                $schoolYearIds[] = $thisSchoolYear->getID();
            }
            sort($schoolYearIds);
            $schoolYearIds = implode(',', $schoolYearIds);
        } else {
            $schoolYearIds = $schoolYear->getID();
        }
        $hasActiveSiblings = &self::$cache['hasActiveSiblings-' . $this->getID()][$schoolYearIds];
        if (!isset($hasActiveSiblings)) {
            $hasActiveSiblings = core_db::runGetValue('
                                    SELECT COUNT(DISTINCT s.student_id) 
                                    FROM mth_student AS s
                                      INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id
                                        AND ss.school_year_id IN (' . $schoolYearIds . ')
                                        AND ss.status IN (' . self::STATUS_ACTIVE . ',' . self::STATUS_PENDING . ')
                                    WHERE s.parent_id=' . $this->getParentID() . '
                                      AND s.student_id!=' . $this->getID());
        }
        return (int) $hasActiveSiblings;
    }

    public function isReturnStudent(mth_schoolYear $schoolYear = null)
    {
        $isReturn = &self::$cache['isReturnStudent'][($schoolYear ? $schoolYear->getID() : null)][$this->getID()];
        if (isset($isReturn)) {
            return $isReturn;
        }
        if (!$schoolYear) {
            $schoolYear = mth_schoolYear::getCurrent();
        }
        $year_count = core_db::runGetValue('SELECT COUNT(ss.school_year_id) 
                                                  FROM mth_student_status AS ss 
                                                    INNER JOIN mth_schoolyear AS y ON y.school_year_id=ss.school_year_id
                                                  WHERE y.date_begin<"' . $schoolYear->getDateBegin('Y-m-d') . '" 
                                                    AND ss.student_id=' . $this->getID());
        return ($isReturn = $year_count > 0);
    }

    public static function updateGradeLevelsAndSchoolOfEnrollments()
    {
        $years = [mth_schoolYear::getCurrent(), mth_schoolYear::getNext()];
        foreach ($years as $schoolYear) {

            // UPDATE students grade levels
            core_db::runQuery('REPLACE INTO mth_student_grade_level (student_id, school_year_id, grade_level)
                            SELECT 
                                gl.student_id,
                                ss.school_year_id,
                                IF(gl.grade_level="K",0,gl.grade_level)+(YEAR(y1.date_begin)-YEAR(y2.date_begin))
                            FROM mth_student_grade_level AS gl
                                INNER JOIN mth_student_status AS ss
                                    ON ss.school_year_id=' . $schoolYear->getID() . '
                                    AND ss.student_id=gl.student_id
                                    AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
                                LEFT JOIN mth_schoolyear AS y1 ON y1.school_year_id=ss.school_year_id
                                LEFT JOIN mth_schoolyear AS y2 ON y2.school_year_id=gl.school_year_id
                                LEFT JOIN (SELECT g2.student_id FROM mth_student_grade_level AS g2
                                          WHERE g2.school_year_id=' . $schoolYear->getID() . ') AS g2 ON g2.student_id=gl.student_id
                            WHERE g2.student_id IS NULL
                                AND ((gl.grade_level="K" AND (YEAR(y1.date_begin)-YEAR(y2.date_begin)) BETWEEN 1 AND 12) 
                                   OR (gl.grade_level!="K" AND gl.grade_level+(YEAR(y1.date_begin)-YEAR(y2.date_begin)) BETWEEN 1 AND 12))
                            GROUP BY gl.student_id, gl.grade_level, gl.school_year_id, ss.school_year_id');

            // UPDATE unassigned school of records
            core_db::runQuery('INSERT INTO mth_student_school 
                                    (student_id, school_year_id, school_of_enrollment) 
                          SELECT s.student_id, ' . $schoolYear->getID() . ', ' . SchoolOfEnrollment::Unassigned . ' 
                          FROM mth_student AS s 
                            LEFT JOIN mth_student_school AS ssch ON ssch.student_id=s.student_id
                              AND ssch.school_year_id=' . $schoolYear->getID() . '
                          WHERE ssch.school_of_enrollment IS NULL');
        }
    }

    public function isTransffered()
    {
        $previous_year = mth_schoolYear::getPrevious();
        $currYear = mth_schoolYear::getCurrent();

        $year_count = core_db::runGetValue('select count(student_status.student_id) from mth_student_status as student_status
        inner join mth_student_school as current_school on student_status.student_id=current_school.student_id 
        inner join mth_student_school as previous_school  on student_status.student_id=previous_school.student_id
        inner join mth_packet as mp4 on mp4.student_id=student_status.student_id
        where student_status.school_year_id=' . $currYear->getID() . ' and current_school.school_year_id=' . $currYear->getID() . //current school status
            ' and previous_school.school_year_id=' . $previous_year->getID() .  //previous school
            ' and current_school.school_of_enrollment!=' . SchoolOfEnrollment::Unassigned . ' and previous_school.school_of_enrollment!=' . SchoolOfEnrollment::Unassigned . //shoul not be unsigned
            ' and previous_school.school_of_enrollment!=current_school.school_of_enrollment 
        and student_status.status IN (' . mth_student::STATUS_PENDING . ',' . mth_student::STATUS_ACTIVE . ') and student_status.student_id=' . $this->getID());

        return $year_count > 0;
    }

    public function isNewFromSOE($soe)
    {
        $previous_year = mth_schoolYear::getPrevious();
        $year_count = core_db::runGetValue('select count(student_id)
        from mth_student_school where student_id=' . $this->getID() .
            ' and school_of_enrollment=' . $soe .
            ' and school_year_id=' . $previous_year->getID());

        return $year_count == 0;
    }

    public function isNewFromDiplomaSeeking(mth_schoolYear $year)
    {
        $previous_year = $year->getPreviousYear();
        $year_count = core_db::runGetValue('SELECT count(mss.student_id) FROM mth_student_status AS mss 
            LEFT JOIN mth_student_grade_level AS msgl ON mss.student_id = msgl.student_id 
            WHERE mss.student_id=' . $this->getID() .
            ' AND mss.status < 3 
              AND msgl.grade_level >= 9
              AND msgl.school_year_id = ' . $previous_year->getID() .
            ' AND mss.school_year_id=' . $previous_year->getID());

        return $year_count == 0;
    }

    public function getSOEStatus(mth_schoolYear $year, $packet = null)
    {
        if ($packet == null && !($packet = mth_packet::getStudentPacket($this))) {
            return 'Missing Packet';
        }


        if ($this->isTransffered()) {
            $status = $packet->getStatus() != mth_packet::STATUS_ACCEPTED ? ' - packet pending' : '';
            return 'Transferred' . $status;
        }

        if ($this->isReturnStudent($year)) {
            return 'Returning';
        } else {
            return 'New';
        }
    }

    public function hadGraduated()
    {
        return in_array($this->getLastStatus(), [mth_student::STATUS_TRANSITIONED, mth_student::STATUS_GRADUATED]);
    }

    public function getRequiredImmunizations()
    {
        $grade_level = 0;
        $school_year = mth_schoolYear::getNext();
        if ($_SERVER['STATE'] == 'UT') {
            $grade_level = 7;
            if ($this->getGradeLevel(false, false, $school_year->getID()) == $grade_level) {
                return true;
            }
        } elseif ($_SERVER['STATE'] == 'CO') {
            if (!($packet = mth_packet::getStudentPacket($this))) {
                return true;
            }
            $grade_level = 6;
            if (
                $this->getGradeLevel(false, false, $school_year->getID()) == $grade_level
                || $packet->isExempImmunization()
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return mth_student[]|bool
     */
    public static function getStudentIdsWithoutSchedules(mth_schoolYear $year = null)
    {
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return [];
        }
        return core_db::runGetValues(
            'SELECT s.student_id FROM mth_student AS s
            INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id
            LEFT JOIN (SELECT student_id, schedule_id FROM mth_schedule WHERE school_year_id=' .
                (int) $year->getID() . ' AND STATUS !=99) AS validSchedules ON validSchedules.student_id=s.student_id
            WHERE ss.school_year_id =' . (int) $year->getID() . ' && ss.`status`=' . mth_student::STATUS_PENDING . '
            AND validSchedules.schedule_id IS NULL
            GROUP BY s.student_id'
        );
    }

    public static function getStudentIdsByProviders(array $providers, $year, $midYear)
    {

        $studentIds = core_db::runGetValues('SELECT ms.student_id FROM mth_schedule_period AS sp
                                        LEFT JOIN mth_schedule AS s ON s.schedule_id=sp.schedule_id
                                        inner join mth_student as ms on s.student_id=ms.student_id
                                      WHERE sp.mth_provider_id IN (' . implode(',', $providers) . ')
                                        AND sp.course_type=' . self::TYPE_MTH . '
                                        AND s.school_year_id=' . $year->getID() . '
                                        AND (sp.custom_desc IS NULL OR sp.custom_desc!="[:NONE:]")
                                        AND s.student_id in (select mss.student_id from mth_student_status as mss
                                         ' . $getMidyearApplications . '
                                         where mss.school_year_id=' . $year->getID() . ' and mss.`status`  IN (' . $student_status . ') ' . $getMidyear . ')
                                      ORDER BY sp.`period` ASC');
    }
    public function hasReduceTechAllowance($scheduleID = null)
    {
        $techAllowance = false;
        $count_techAllowance = core_db::runGetValue('SELECT COUNT(*)  as total FROM
                                    mth_schedule_period AS mthP
                                    LEFT JOIN  mth_provider_course AS mthPC
                                    ON mthP.provider_course_id = mthPC.provider_course_id 
                                    WHERE mthP.schedule_id = ' . $scheduleID . ' AND mthPC.reduceTechAllowance = 1');
        if ($count_techAllowance > 0) {
            $techAllowance = true;
        }
        return $techAllowance;
    }
    public function TransfereeEffectiveDate()
    {
        $CurrentSoE = null;
        $yearID = mth_schoolYear::getCurrent()->getID();
        $result = core_db::runQuery('SELECT * FROM mth_student_school WHERE student_id=' . $this->getID() . ' AND school_year_ID=' . $yearID);
        if (!is_null($result)) {
            $CurrentSoE = $result->fetch_object();
        }
        return $CurrentSoE;
    }
    //get teacher notes per schoolyear
    //mth_teacher_notes table
    public function getTeacherNotes($schoolyearID = 0, $value = null)
    {
        $result = core_db::runQuery('select * from mth_teacher_notes
        WHERE student_id=' . $this->getID() . ' AND school_year_id=' . $schoolyearID);

        if ($value !== null) {
            $this->teacher_notes = req_sanitize::multi_txt($value);
            if (!is_null($result) && $result->num_rows > 0) {
                $sqlInsert = 'UPDATE  mth_teacher_notes set notes ="' . $value . '" 
                WHERE student_id=' . $this->getID() . ' AND school_year_id=' . $schoolyearID;
            } else {
                $sqlInsert = 'INSERT INTO mth_teacher_notes (student_id,school_year_id,notes) 
                values(' . $this->getID() . ', ' . $schoolyearID . ',"' . $value . '") ';
            }
            echo $sqlInsert;
            return core_db::runQuery($sqlInsert);
        } else {
            $teacherNotes = '';
            // $result = core_db::runQuery('select * from mth_teacher_notes
            // WHERE student_id=' . $this->getID().' AND school_year_id='.$schoolyearID);

            if (!is_null($result) && $result->num_rows > 0) {
                $teacherNotes = $result->fetch_object();
                $teacherNotes = $teacherNotes->notes;
            }
            return $teacherNotes;
        }
    }
}
