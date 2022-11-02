<?php

/**
 *
 *
 * @author abe
 */
class mth_testOptOut extends core_model
{
    //mth_forms001
    protected $testOptOut_id;
    protected $in_attendance;
    protected $parent_id;
    protected $sig_file_id;
    protected $school_year_id;
    protected $date_submitted;

    protected $student_ids;
    protected $sent_to_dropbox;
    protected $onStudent = 0;
    protected static $cache = array();

    //columns when joins with mth_testoptout_student via mth\optout\Query
    protected $student_id;

    /**
     *
     * @param int $testOptOut_id
     * @return mth_testOptOut
     */
    public static function get($testOptOut_id)
    {
        if (NULL === ($optOut = &self::$cache['get'][(int)$testOptOut_id])) {
            $optOut = core_db::runGetObject('SELECT * FROM mth_testOptOut 
                                        WHERE testOptOut_id=' . (int)$testOptOut_id,
                'mth_testOptOut');
        }
        return $optOut;
    }
    /**
     * WHen expecting int only
     * Function usable when joins with mth_testoptout_student
     * via mth\optout\Query
     */
    public function isSentToDropbox(){
        if(!is_array($this->sent_to_dropbox)){
            return $this->sent_to_dropbox == 1;
        }
        return false;
    }

    /**
     * Function usable when joins with mth_testoptout_student
     * via mth\optout\Query
     * @return void
     */
    public function getStudent(){
        if(!$this->student_id){
            return null;
        }
        return mth_student::getByStudentID($this->student_id);
    }

    /**
     *
     * @param mth_parent $parent
     * @param mth_schoolYear $schoolYear
     * @return mth_testOptOut
     */
    public static function getByParentYear(mth_parent $parent, mth_schoolYear $schoolYear)
    {
        if (NULL === ($optOut = &self::$cache['getByParentYear'][$parent->getID()][$schoolYear->getID()])) {
            $optOut = core_db::runGetObject('SELECT * FROM mth_testOptOut 
                                        WHERE parent_id=' . $parent->getID() . '
                                          AND school_year_id=' . $schoolYear->getID(),
                'mth_testOptOut');
        }
        return $optOut;
    }

    /**
     *
     * @param mth_student $student
     * @param mth_schoolYear $schoolYear
     * @return mth_testOptOut
     */
    public static function getByStudent(mth_student $student, mth_schoolYear $schoolYear)
    {
        if (NULL === ($optOut = &self::$cache['getByStudent'][$student->getID()][$schoolYear->getID()])) {
            $optOut = core_db::runGetObject('SELECT too.* 
                                        FROM mth_testoptout AS too
                                          INNER JOIN mth_testoptout_student AS toos 
                                            ON toos.testOptOut_id=too.testOptOut_id
                                        WHERE too.school_year_id=' . $schoolYear->getID() . '
                                          AND toos.student_id=' . $student->getID(),
                'mth_testoptout');
        }
        return $optOut;
    }

    public function id()
    {
        return (int)$this->testOptOut_id;
    }

    /**
     *
     * @return mth_parent
     */
    public function get_parent()
    {
        return mth_parent::getByParentID($this->parent_id);
    }

    public function set_parent(mth_parent $parent)
    {
        $this->parent_id = $parent->getID();
    }

    public function save_sig_file($svgXMLbase64content)
    {
        if (($sigFile = mth_file::saveFile('sig.svg', base64_decode($svgXMLbase64content), 'image/svg+xml'))) {
            $this->sig_file_id = $sigFile->id();
        }
    }

    public function set_in_attendance($in_attendance)
    {
        $this->in_attendance = $in_attendance ? 1 : 0;
    }

    public function set_student_ids($student_ids)
    {
        if (!($parent = $this->get_parent())) {
            error_log('You must set the parent first');
            return false;
        }
        $this->get_student_ids();
        $availableStudents = $parent->getStudents();
        foreach ($student_ids as $student_id) {
            if (($student = mth_student::getByStudentID($student_id))
                && in_array($student, $availableStudents)
                && !in_array($student->getID(), $this->student_ids)
            ) {
                $this->student_ids[] = $student->getID();
            }
        }
        return !empty($this->student_ids);
    }

    public function get_student_ids()
    {
        if (is_null($this->student_ids)) {
            $this->student_ids = array();
            if (!$this->id()) {
                return array();
            }
            $result = core_db::runQuery('SELECT student_id, sent_to_dropbox FROM mth_testOptOut_student WHERE testOptOut_id=' . $this->id());
            while ($r = $result->fetch_row()) {
                $this->student_ids[] = $r[0];
                $this->sent_to_dropbox[$r[0]] = $r[1];
            }
            $result->free_result();
        }
        return $this->student_ids;
    }

    public function sent_to_dropbox($student_id)
    {
        $this->get_student_ids();
        return !empty($this->sent_to_dropbox[$student_id]);
    }

    /**
     *
     * @param bool $reset
     * @return mth_student
     */
    public function eachStudent($reset = false)
    {
        $ids = $this->get_student_ids();
        if (!$reset && isset($ids[$this->onStudent])
            && ($student = mth_student::getByStudentID($ids[$this->onStudent]))
        ) {
            $this->onStudent++;
            return $student;
        }
        $this->onStudent = 0;
        return NULL;
    }

    public function set_school_year_id($school_year_id)
    {
        if (($year = mth_schoolYear::getByID($school_year_id))) {
            $this->school_year_id = $year->getID();
        }
    }

    /**
     *
     * @return mth_schoolYear
     */
    public function school_year()
    {
        return mth_schoolYear::getByID($this->school_year_id);
    }

    public function submit()
    {
        if (!$this->parent_id || !$this->sig_file_id || !$this->school_year_id || empty($this->student_ids)) {
            return false;
        }
        if (!$this->testOptOut_id) {
            $this->testOptOut_id = $this->insert_main();
        }
        if (!$this->testOptOut_id) {
            return false;
        }
        return $this->save_student_ids();
    }

    protected function insert_main()
    {
        if (!core_db::runQuery(sprintf('INSERT INTO mth_testOptOut 
                              (in_attendance, parent_id, sig_file_id, school_year_id, date_submitted)
                            VALUES (%d,%d,%d,%d,NOW())',
            $this->in_attendance,
            $this->parent_id,
            $this->sig_file_id,
            $this->school_year_id))
        ) {
            return false;
        }
        return core_db::getInsertID();
    }

    public function delete($student_id = NULL)
    {
        if (!core_db::runQuery('DELETE FROM mth_testOptOut_student 
                            WHERE testOptOut_id=' . $this->id() . ' 
                              ' . ($student_id ? 'AND student_id=' . (int)$student_id : ''))
        ) {
            return false;
        }
        $this->student_ids = NULL;
        $this->sent_to_dropbox = NULL;
        if (count($this->get_student_ids()) < 1
            && !core_db::runQuery('DELETE FROM mth_testOptOut WHERE testOptOut_id=' . $this->id())
        ) {
            return false;
        }
        return true;
    }

    public static function deleteStudentOptOuts(mth_student $student)
    {
        return core_db::runQuery('DELETE FROM mth_testOptOut_student WHERE student_id=' . $student->getID());
    }

    protected function save_student_ids()
    {
        $Qs = array();
        foreach ($this->student_ids as $student_id) {
            $Qs[] = '(' . $this->id() . ',' . $student_id . ')';
        }
        return core_db::runQuery('REPLACE INTO mth_testOptOut_student (testOptOut_id, student_id) 
                              VALUES ' . implode(',', $Qs));
    }

    public function send_to_dropbox($student_id = NULL)
    {
        if (is_numeric($student_id)) {
            $student_ids = array((int)$student_id);
        } else {
            $student_ids = $this->get_student_ids();
        }
        if (!($authToken = core_setting::get('accessTokenV2', 'DropBox')) || !$this->id()) {
            core_notify::addError('Dropbox error');
            return FALSE;
        }
        $success = array();
        while ($student = mth_student::each(array('StudentID' => $student_ids))) {
            $state = $student->getAddress()->getState();
            if($state !== 'OR'){
                if (!($content = mth_views_testOptOut::get2022PDFcontent($this, $student))) {
                    continue;
                }
            }else{
                if (!($content = mth_views_testOptOut::getOregonPDFcontent($this, $student))) {
                    continue;
                }
            }

            $address = $student->getParent()->getAddress();
            $inputState = $address ? $address->getState() : 'UT';
        
            $success[$student->getID()] = true;
            $path = '/SAGE Opt-out/' . $this->school_year() . '/' . ($inputState == 'OR' ? 'Oregon/' : '') . $student->getSchoolOfEnrollment(false, $this->school_year()) . '/'
                . $student->getLastName() . ', ' . $student->getFirstName() . ' (' . $student->getID() . ').pdf';
            $success[$student->getID()] = mth_dropbox::uploadFileFromString($path,$content);
            if ($success[$student->getID()]) {
                core_db::runQuery('UPDATE mth_testOptOut_student SET sent_to_dropbox=1 
                            WHERE testOptOut_id=' . $this->id() . ' AND student_id=' . $student->getID());
                $this->sent_to_dropbox[$student->getID()] = 1;
            }
        }
        return $success;
    }

    public function date_submitted($format = NULL)
    {
        return self::getDate($this->date_submitted, $format);
    }

    public function sig_file_hash()
    {
        if (($sig_file = mth_file::get($this->sig_file_id))) {
            return $sig_file->hash();
        }
    }

    public function sig_file_contents()
    {
        if (($sig_file = mth_file::get($this->sig_file_id))) {
            return $sig_file->contents();
        }
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param bool $reset
     * @return mth_testOptOut
     */
    public static function each(mth_schoolYear $year, $reset = false)
    {
        $result = &self::$cache['each'][$year->getID()];
        if (!$result) {
            $result = core_db::runQuery('SELECT * FROM mth_testOptOut WHERE school_year_id=' . $year->getID());
        }
        if (!$reset && ($optOut = $result->fetch_object(__CLASS__))) {
            return $optOut;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function cacheAll(mth_schoolYear $year)
    {
        self::each($year, true);
        while ($optOut = self::each($year)) {
            while ($student = $optOut->eachStudent()) {
                self::$cache['getByStudent'][$student->getID()][$year->getID()] = $optOut;
            }
            self::$cache['getByParentYear'][$optOut->parent_id][$year->getID()] = $optOut;
            self::$cache['get'][$optOut->id()] = $optOut;
        }
    }

    public static function count(mth_schoolYear $year)
    {
        $result = &self::$cache['each'][$year->getID()];
        if (!$result) {
            self::each($year, true);
        }
        return $result->num_rows;
    }

    public static function studentCount(mth_schoolYear $year)
    {
        $count = &self::$cache['studentCount'][$year->getID()];
        if (!isset($count)) {
            self::each($year, true);
            while ($optOut = self::each($year)) {
                $count += count($optOut->get_student_ids());
            }
        }
        return $count;
    }

    public static function potentialStudentCount(mth_schoolYear $year)
    {
        $filter = new mth_person_filter();
        $filter->setStatus(array(mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE));
        $filter->setStatusYear($year->getID());
        return count($filter->getStudentIDs());
    }
}
