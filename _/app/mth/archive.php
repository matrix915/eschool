<?php

use mth\yoda\courses;

class mth_archive extends core_model
{
    protected $archive_id;
    protected $student_id;
    protected $student_status;
    protected $status_date;
    protected $schedule_status;
    protected $schedule_date;
    protected $homeroom_id;
    protected $school_year_id;
    protected $status;
    protected $created_at;

    CONST STATUS_PENDING = 0;
    CONST STATUS_EXECUTED = 1;
    //CONST STATUS_DELETED = 99;

    protected static $cache = array();

    protected $fillable = [
        'archive_id', 'student_id', 'student_status', 'schedule_status', 'homeroom_id', 'school_year_id'
    ];

    protected function set($field, $value, $force = false)
    {
        return parent::set($field, $value, $force);
    }

    public function save()
    {
        if (!$this->archive_id && !$this->student_status) {
            error_log('Archive save no archive_id and no student_status');
            return false;
        }

        if (!$this->archive_id) {
            core_db::runQuery('INSERT INTO mth_archive (`student_id`) VALUES (0)');
            $this->archive_id = core_db::getInsertID();
        }

        return parent::runUpdateQuery('mth_archive', 'archive_id=' . $this->getID());
    }

    public function getID()
    {
        return $this->archive_id;
    }

    public static function record(mth_student $student,$school_year = null,$return_obj = false)
    {
        if (!$student) {
            error_log('Archive Record: Student not found');
            return false;
        }

       
        if(!$school_year){
            $school_year = mth_schoolYear::getCurrent();
        }
        
        if(!($archive = mth_archive::get($student,$school_year))){
            $archive = new mth_archive();
        }
        $schedule = $student->schedule();
        $archive->student_id($student->getID());
        $archive->student_status($student->getStatus($school_year));
        $archive->status_date($student->getStatusDate($school_year,'Y-m-d H:i:s'));
        $archive->schedule_status(  $schedule? $schedule->status(true): mth_schedule::STATUS_ACCEPTED);
        if($schedule){
            $archive->schedule_date($schedule->getLastModified('Y-m-d H:i:s'));
        }
        if ($enrollment = courses::getStudentHomeroom($student->getID(), $school_year)) {
            $archive->homeroom_id($enrollment->getCourseId());
        }
        $archive->school_year_id($school_year->getID());
        return $return_obj?$archive:$archive->save();
    }

    public function getStudent(){
        return mth_student::getByStudentID($this->student_id);
    }
    
    public function isDue(){
        return strtotime($this->created_at) < strtotime("-2 days");
    }
    public function execute(){
        $year = mth_schoolYear::getByID($this->school_year_id);
        $student = $this->getStudent();
        if (!$student 
            || (!$year
                && $student->getStatus($year) != mth_student::STATUS_WITHDRAW
            )
        ) {
            error_log('Archive Execute: no student or no year not withdrawn');
            return false;
        }

        if($schedule = $student->schedule($year)){
            $canvas_enrollment = mth_canvas_enrollment::getScheduleEnrollments($schedule);
            if(!empty($canvas_enrollment )){
                foreach($canvas_enrollment as $course){
                    $course->delete();
                }
            }
            $schedule->delete();
        }
        
        if(($packet = mth_packet::getStudentPacket($student))
        && (NULL === $student->getStatus($year->getNextYear()))){
            mth_packet::deleteStudentPackets($student);
        }

        if($homeroomenrollment = courses::getStudentHomeroom($student->getID(),$year)){
            $homeroomenrollment->delete();
        }
        $current_sy = mth_schoolYear::getCurrent();
        //delete user account only if student is withdrawn for current school year
        if( $current_sy && $this->school_year_id == $current_sy->getID() && $user = $student->user()){
            $user->delete();
        }

        $this->status(self::STATUS_EXECUTED);
        return $this->save();
    }

    public function delete(){
        if(!$this->archive_id){
           return false; 
        }
        return  core_db::runQuery('delete from mth_archive where archive_id='.$this->archive_id);
    }

    public static function getPendings($reset = false,$with_interval = true){
        $result = &self::$cache['getPendings'];
        if (!isset($result)) {
            $result = core_db::runQuery("select * from mth_archive  where status = ".self::STATUS_PENDING.($with_interval?" and created_at < (NOW() - INTERVAL  2 DAY)":""));
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }
        if ($archive = $result->fetch_object('mth_archive')) {
            self::cache(__CLASS__, $archive->getID(), $archive);
            return $archive;
        }

        $result->data_seek(0);
        return NULL;
    }

    public static function get(mth_student $student,$year = null,$executed = false){
        if(!$year){ 
            $year = mth_schoolYear::getCurrent();
        }

        $archive = &self::cache(__CLASS__, 'get-'.$student->getID().'-'.$year);

        if(!isset($archive)){
            $archive = core_db::runGetObject('SELECT * from mth_archive where school_year_id='.$year->getID().' and student_id='.(int)$student->getID().($executed?' and status='.self::STATUS_EXECUTED:''),
            'mth_archive');
        }
        return $archive;
    }

    public function student_id($set = null)
    {
        if (!is_null($set)) {
            $this->set('student_id', (int)$set);
        }
        return $this->student_id;
    }

    public function student_status($set = null)
    {
        if (!is_null($set)) {
            $this->set('student_status', (int)$set);
        }
        return $this->student_status;
    }

    public function status_date($set = null)
    {
        if (!is_null($set)) {
            $this->set('status_date', $set);
        }
        return $this->status_date;
    }

    public function schedule_status($set = null)
    {
        if (!is_null($set)) {
            $this->set('schedule_status', (int)$set);
        }
        return $this->schedule_status;
    }

    public function schedule_date($set = null)
    {
        if (!is_null($set)) {
            $this->set('schedule_date', $set);
        }
        return $this->schedule_date;
    }

    public function homeroom_id($set = null)
    {
        if (!is_null($set)) {
            $this->set('homeroom_id', (int)$set);
        }
        return $this->homeroom_id;
    }

    public function school_year_id($set = null)
    {
        if (!is_null($set)) {
            $this->set('school_year_id', (int)$set);
        }
        return $this->school_year_id;
    }

    public function status($set = null)
    {
        if (!is_null($set)) {
            $this->set('status', (int)$set);
        }
        return $this->status;
    }

}