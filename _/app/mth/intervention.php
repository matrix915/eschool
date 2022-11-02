<?php
class mth_intervention extends core_model{
    protected $intervention_id;
    protected $mth_student_id;
    protected $zero_count;
    protected $grade;
    protected $school_year_id;
    protected $last_login;
    protected $date_created;
    protected $label_id;
    protected $resolve = 0;
    protected static $cache = array();
    private $db;

    public function __construct(){
    }

    /**
     * Initialize first notice email
     *
     * @param [string] $subject
     * @param [string] $content
     * @return void
     */
    public static function initFirstNoticeEmail($subject,$content){
        core_setting::init(
            'firstNoticeEmailSubject',
            'Interventions',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            'First Notice Email Subject',
            '<p>[DUE_DATE] - the day when email falls due(5 days)</p>'
        );

        core_setting::init(
            'firstNoticeEmailContent',
            'Interventions',
            $content,
            core_setting::TYPE_HTML,
            true,
            'First Notice Email Content',
            '<p>[STUDENT_FIRST] - the student\'s  preferred first name<br>
            [PARENT_FIRST] - the parent\'s  preferred first name<br>
            [DUE_DATE] - the day when email falls due(5 days)
            </p>'
        );
    }
    /**
     * Undocumented function
     *
     * @param string $email
     * @return void
     */
    public static function initEmailBCC($email){
        core_setting::init(
            'interventionbcc',
            'Interventions',
            $email,
            core_setting::TYPE_TEXT,
            true,
            'Email BCC',
            '<p>Send an email copy to this email address</p>'
        );
    }
    /**
     * Initialize final notice email
     *
     * @param [string] $subject
     * @param [string] $content
     * @return void
     */
    public static function initFinalNoticeEmail($subject,$content){
        core_setting::init(
            'finalNoticeEmailSubject',
            'Interventions',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            'Final Notice Email Subject',
            '<p>[DUE_DATE] - the day when email falls due(3 days)</p>'
        );

        core_setting::init(
            'finalNoticeEmailContent',
            'Interventions',
            $content,
            core_setting::TYPE_HTML,
            true,
            'Final Notice Email Content',
            '<p>[STUDENT_FIRST] - the student\'s  preferred first name<br>
            [PARENT_FIRST] - the parent\'s  preferred first name<br>
            [DUE_DATE] - the day when email falls due(3 days)
            </p>'
        );
    }
    /**
     * Initialize heads up notice email
     *
     * @param string $subject
     * @param string $content
     * @return void
     */
    public static function initHeadsUp($subject,$content){
        core_setting::init(
            'headsUpNoticeEmailSubject',
            'Interventions',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            'Heads up Email Subject'
        );

        core_setting::init(
            'headsUpNoticeEmailContent',
            'Interventions',
            $content,
            core_setting::TYPE_HTML,
            true,
            'Heads up Email Content',
            '<p>
            [PARENT_FIRST] - the parent\'s  preferred first name
            </p>'
        );
    }
    /**
     * Initialize Consecutive EX Notice
     *
     * @param string $subject
     * @param string $content
     * @return void
     */
    public static function initConsecutiveEx($subject,$content){
        core_setting::init(
            'consecutiveExNoticeEmailSubject',
            'Interventions',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            'Consecutive Ex Subject'
        );

        core_setting::init(
            'consecutiveExNoticeEmailContent',
            'Interventions',
            $content,
            core_setting::TYPE_HTML,
            true,
            'Consecutive EX Content',
            '<p>[STUDENT_FIRST] - the student\'s  preferred first name<br>
            [PARENT_FIRST] - the parent\'s  preferred first name<br>
            </p>'
        );
    }
    /**
     * Initialize Probation Notice 
     *
     * @param string $subject
     * @param string $content
     * @return void
     */
    public static function initProbation($subject,$content){
        core_setting::init(
            'probationNoticeEmailSubject',
            'Interventions',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            'Probation Subject'
        );

        core_setting::init(
            'probationNoticeEmailContent',
            'Interventions',
            $content,
            core_setting::TYPE_HTML,
            true,
            'Probation Content',
            '<p>[STUDENT_FIRST] - the student\'s  preferred first name<br>
            [PARENT_FIRST] - the parent\'s  preferred first name<br>
            [DUE_DATE] - the day when email falls due(3 days)
            </p>'
        );
    }

    /**
     * Missing log intervention email
     * @param [type] $subject
     * @param [type] $content
     * @return void
     */
    public static function initMissingLog($subject,$content){
        core_setting::init(
            'missingLogNoticeEmailSubject',
            'Interventions',
            $subject,
            core_setting::TYPE_TEXT,
            true,
            'Missing Log Subject'
        );

        core_setting::init(
            'missingLogNoticeEmailContent',
            'Interventions',
            $content,
            core_setting::TYPE_HTML,
            true,
            'Missing Log Content',
            '<p>[STUDENT_FIRST] - the student\'s  preferred first name<br>
            [PARENT_FIRST] - the parent\'s  preferred first name<
            </p>'
        );
    }

    public static function initConsecutiveExBCC($email){
        core_setting::init(
            'interventionconsecutiveexbcc',
            'Interventions',
            $email,
            core_setting::TYPE_TEXT,
            true,
            'Consicutive EX Email BCC',
            '<p>Send an email copy to this email address</p>'
        );
    }

    public static function initExceedEX($subject,$content){
        core_setting::init(
          'exceedEXNoticeEmailSubject',
          'Interventions',
          $subject,
          core_setting::TYPE_TEXT,
          true,
          'Exceed EX Subject'
        );

        core_setting::init(
          'exceedEXNoticeEmailContent',
          'Interventions',
          $content,
          core_setting::TYPE_HTML,
          true,
          'Exceed EX Content',
          '<p>[STUDENT_FIRST] - the student\'s  preferred first name<br>
                [PARENT_FIRST] - the parent\'s  preferred first name<br>
                [DUE_DATE] - the day when email falls due(3 days)
                </p>'
        );
    }

     public function label($set = NULL){
        if(!is_null($set)){
            $this->set('label_id',(int)$set);
        }
        return (int)$this->label_id;
     }
     public function student($set = NULL){
        if(!is_null($set)){
            $this->set('mth_student_id',(int)$set);
        }
        return (int)$this->mth_student_id;
     }
     public function zeroCount($set = NULL){
        if(!is_null($set)){
            $this->set('zero_count',(int)$set);
        }
        return (int)$this->zero_count;
     }
     public function grade($set = NULL){
        if(!is_null($set)){
            $this->set('grade',$set);
        }
        return $this->grade;
     }
     public function schoolYear($set = NULL){
        if(!is_null($set)){
            $this->set('school_year_id',(int)$set);
        }
        return $this->school_year_id;
     }
     public function lastLogin($set = NULL){
        if(!is_null($set)){
            $this->set('last_login',$set);
        }
        return $this->last_login;
     }
     public function resolve($set = NULL){
         if(!is_null($set)){
            $this->set('resolve',(int)$set);
        }
        return $this->resolve;
     }
     public function id($set=NULL){
         if(!is_null($set)){
             $this->set('intervention_id',$set);
         }
         return $this->intervention_id;
     }
     public function getID(){
        return $this->intervention_id;
     }
     public function getSchoolYear(){
         return mth_schoolYear::getByID($this->school_year_id);
     }
     public function getDateCreated($format = NULL){
        if(is_null($format)){
            return $this->date_created;
        } 

        return is_null($this->date_created)?null:core_model::getDate($this->date_created, $format);
     }
     public function getLabel(){
         if(!$this->intervention_id && !$this->label_id){
            return false;
         }
         return mth_label::getById($this->label_id);
     }
     public function isResolved(){
         return (boolean)$this->resolve;
     }

     /**
      * Get Student Object
      *
      * @return mth_student
      */
     public function getStudent(){
        return mth_student::getByStudentID($this->mth_student_id);
     }
     /**
      * Update/Insert Record
      *
      * @return void
      */
     public function save()
     {
        $db = new core_db();
         $columns = [
            'mth_student_id' => $this->mth_student_id,
            'zero_count'=> $this->zero_count,
            'grade' => $this->grade,
            'school_year_id' => $this->school_year_id,
            //'last_login' => $this->last_login,
            'resolve' => $this->resolve
         ];

         $data_types = [
             '%d','%d','%f','%d'
             //,'"%s"'
             ,'%d'
         ];

         if($this->label_id){
            $columns = array_merge($columns,[
                'label_id'=>$this->label_id
            ]);
            $data_types[] = '%d';
         }

         if(!$this->intervention_id){
            $db->query(
                vsprintf(
                     'INSERT INTO mth_intervention ('.implode(',',array_keys($columns)).')
                     VALUES('.implode(',',$data_types).')',
                     array_values($columns)
                 )
             );
             $this->intervention_id = $db->insert_id;
             return $this->intervention_id;
         }
         return parent::runUpdateQuery('mth_intervention', '`intervention_id`=' . $this->id());
     }
     /**
      * Get Intervention By Student
      *
      * @param mth_student $student
      * @param mth_schoolYear $year
      * @return mth_intervention
      */
     public static function getByStudent(mth_student $student,mth_schoolYear $year = NULL){
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return false;
        }
        if(!$student->getID()){
            return false;
        }

        $result = &self::$cache['getByStudent'][$student->getID()][$year->getID()];

       
        if(!isset($result)){
            
            $sql = 'select * from mth_intervention where mth_student_id='.$student->getID().' and school_year_id='.$year->getID().' limit 1';
            $result = core_db::runGetObject($sql,'mth_intervention'); 
        }

        return $result;
     }
     /**
      * Get Intervention By ID 
      *
      * @param int $intervention_id
      * @return mth_intervention
      */
     public static function getByID($intervention_id){
        $intervention = &self::$cache['getByID'][$intervention_id];
        if($intervention === NULL){
            $intervention = core_db::runGetObject('SELECT * FROM mth_intervention WHERE intervention_id=' . (int)$intervention_id, 'mth_intervention');
        }
        return $intervention;
     }

     /**
      * Fetch intervention row by year
      * @param string $where
      * @param mth_schoolYear $year
      * @param boolean $reset
      * 
      * @return void
      */
     public static function each($where = '',mth_schoolYear $year = NULL, $reset = FALSE){
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return false;
        }

        $result = &self::$cache['each'][$year->getID()];

        if(!isset($result)){
            $_where = !empty(trim($where))?' and '.$where:'';
            $sql = 'select * from mth_intervention where school_year_id='.$year->getID().$_where;
            $result = core_db::runQuery($sql); 
        }

        if(!$reset && ($intervention = $result->fetch_object('mth_intervention'))){
            return $intervention;
        }

        $result->data_seek(0);
        return NULL;
     }

     public static function getAllByStudent(mth_student $student,$reset = FALSE){
        $result = &self::$cache['getAll'];

        if(!isset($result)){
            $sql = 'select * from mth_intervention where mth_student_id='.$student->getID();
            $result = core_db::runQuery($sql); 
        }

        if(!$reset && ($intervention = $result->fetch_object('mth_intervention'))){
            return $intervention;
        }

        $result->data_seek(0);
        return NULL;
     }
    
}