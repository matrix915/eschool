<?php

class mth_resource_request extends core_model
{
    protected $request_id;
    protected $parent_id;
    protected $student_id;
    protected $resource_id;
    protected $school_year_id;
    protected $created_at;
    protected $updated_at;
    protected $where = [],$join = [];
    protected static $_query = 'SELECT /*SELECT*/ FROM mth_resource_request AS m /*JOIN*/ WHERE 1 /*WHERE*/';

    protected static $cache = array();

    protected function set($field, $value, $force = false)
    {
        return parent::set($field, $value, $force);
    }

    public function save()
    {
        if (!$this->request_id && !$this->parent_id && !$this->student_id) {
            return FALSE;
        }

        if (!$this->request_id) {
            core_db::runQuery('INSERT INTO mth_resource_request (`parent_id`) VALUES (0)');
            $this->request_id = core_db::getInsertID();
        }

        return parent::runUpdateQuery('mth_resource_request', 'request_id=' . $this->getID());
    }

    public function delete(){
        if(!$this->request_id){
           return false; 
        }
        return  core_db::runQuery('delete from mth_resource_request where request_id='.$this->request_id);
    }

    public function getID()
    {
        return (int)$this->request_id;
    }


    public function parent_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('parent_id', (int)$set);
        }
        return $this->parent_id;
    }

    public function resource_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('resource_id', (int)$set);
        }
        return $this->resource_id;
    }

    public function student_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('student_id', (int)$set);
        }
        return $this->student_id;
    }

    public function student(){
        if(!$this->student_id){
            return null;
        }
        return mth_student::getByStudentID($this->student_id);
    }

    public function school_year_id($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('school_year_id', (int)$set);
        }
        return $this->school_year_id;
    }

    public function createDate($format = null){
        if (empty($this->created_at)) {
            return NULL;
        }
        if ($format) {
            return date($format, strtotime($this->created_at));
        }
        return strtotime($this->created_at);
    }

    public function getResource(){
        if(!$this->resource_id){
            return false;
        }
        return mth_resource_settings::getById($this->resource_id);
    }

    public static function get(mth_parent $parent,mth_schoolYear $year = null,$reset = false){
        $result = &self::$cache['get'][(int)$parent->getID()][$year?$year->getID():'all'];
        if (!isset($result)) {
            $sql = 'select * from mth_resource_request where 1
            '.($year?'and school_year_id='.$year->getID():'').'
            and parent_id='.$parent->getID();
            $result = core_db::runQuery($sql);
        }

        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_request')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }    

    public static function getOneByStudent(mth_student $student,mth_schoolYear $year = null){
        $result = &self::$cache['getOneByStudent'][(int)$student->getID()][$year?$year->getID():'all'];
        if (!isset($result)) {
            $sql = 'select * from mth_resource_request where 1
            '.($year?'and school_year_id='.$year->getID():'').'
            and student_id='.$student->getID().' limit 1';
            $result = core_db::runGetObject($sql);
        }
        return $result;
    }

    public static function getById($id){

        $resource = &self::cache(__CLASS__, 'getById-'.$id);
        if(!isset($resource)){
            $resource = core_db::runGetObject('SELECT * from mth_resource_request where request_id='.(int)$id,
            'mth_resource_request');
        }

        return $resource;
    }
    public static function getByStudent(mth_student $student,mth_schoolYear $year = null,$reset = false){
        $result = &self::$cache['getByStudent'][(int)$student->getID()][$year?$year->getID():'all'];
        if (!isset($result)) {
            $sql = 'select * from mth_resource_request where 1
            '.($year?'and school_year_id='.$year->getID():'').'
            and student_id='.$student->getID();
            $result = core_db::runQuery($sql);
        }

        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_request')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }

    public function all(mth_schoolYear $year = null,$reset = false){
        if(!$year){
            $year = mth_schoolYear::getCurrent();
        }
        $result = &self::$cache['all'][$year->getID()];
        if (!isset($result)) {
            $sql = 'select * from mth_resource_request where school_year_id='.$year->getID();
            $result = core_db::runQuery($sql);
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_request')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }
    
    public function whereResourceId(array $resources){
        if(!empty($resources)){
            $this->where['resource_id'] = 'AND resource_id IN("'.implode('","',array_map(['core_db','escape'],$resources)).'")';
        }
    }

    public function whereYearId(array $years){
        if(!empty($years)){
            $this->where['school_year_id'] = 'AND school_year_id IN("'.implode('","',array_map(['core_db','escape'],$years)).'")';
        }
    }

    public function whereStudentId(array $students){
        if(!empty($students)){
            $this->where['student_id'] = 'AND student_id IN("'.implode('","',array_map(['core_db','escape'],$students)).'")';
        }
    }

    public function whereStudenStatus(array $statuses,array $year){
        if(!empty($statuses) && !empty($year)){
            $_statuses = implode(',',array_map(['core_db','escape'],$statuses));
            $_year = implode(',',array_map(['core_db','escape'],$year));

            $this->where['student_status'] = 'AND student_id IN(
                select  student_id from mth_student_status where status in('.$_statuses.') and school_year_id in('.$_year.'))';
        }
    }

    
    protected function getQuery($select = 'm.*'){
        return str_replace(
            [
                '/*SELECT*/',
                '/*JOIN*/',
                '/*WHERE*/'
            ],
            [
                $select,
                implode(PHP_EOL, $this->join),
                implode(PHP_EOL,$this->where)
            ],
            self::$_query
        );
    }

    public function query($reset = false){
        $result = &self::$cache['query'];
        if (!isset($result)) {
            $sql = $this->getQuery();
            $result = core_db::runQuery($sql);
        }

        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }

        if ($resource = $result->fetch_object('mth_resource_request')) {
            self::cache(__CLASS__, $resource->getID(), $resource);
            return $resource;
        }

        $result->data_seek(0);
        return NULL;
    }

}