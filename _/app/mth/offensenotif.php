<?php

use mth\aws\ses;
/**
 * Offense Email Notification for Intervention
 * Author: Rex
 * 
 */
class mth_offensenotif extends core_model{
    protected $mth_student_id;
    protected $school_year_id;
    protected $offense_id;
    protected $date_created;
    protected $type;
    protected $intervention_id;
    private $db;
    protected static $cache = array();

    const TYPE_FIRST_NOTICE = 1;
    const TYPE_FINAL_NOTICE = 2;
    const TYPE_HEADSUP_NOTICE = 3;
    const TYPE_CONSECUTIVE_EX = 4;
    const TYPE_PROBATION = 5;
    const TYPE_MISSING = 6;
    const TYPE_EXCEED_EX = 7;

    protected static $notice_labels = [
        self::TYPE_FINAL_NOTICE => 'Final Withdrawal Notice',
        self::TYPE_FIRST_NOTICE => 'First Withdrawal Notice',
        self::TYPE_HEADSUP_NOTICE => 'Heads Up',
        self::TYPE_CONSECUTIVE_EX => 'Max Ex',
        self::TYPE_PROBATION => 'Probation',
        self::TYPE_MISSING => 'Missing Log',
        self::TYPE_EXCEED_EX => 'Exceed EX'
    ];

    public static function customTypes(){
        return [self::TYPE_MISSING];
    }


    public function __construct(){
        $db = new core_db();
        $this->db = $db;
    }
    public function getID()
    {
        return (int)$this->offense_id;
    }


    /**
     * Get Types Name
     *
     * @param int $type
     * @return string
     */
    public static function getTypes($type = null){
        if(!is_null($type)){
            return self::$notice_labels[$type];
        }
        return self::$notice_labels;
    }

    /**
     * Save Offense Notification
     *
     * @return mth_offense_notif
     */
    public function save()
    {
        if(!$this->offense_id){
            $this->db->query(
                sprintf(
                    'INSERT INTO mth_offense_notif (mth_student_id,type,school_year_id,intervention_id)
                    VALUES(%d,%d,%d,%d)',
                    $this->mth_student_id,
                    $this->type,
                    $this->school_year_id,
                    $this->intervention_id
                )
            );
            $this->offense_id = $this->db->insert_id;
            return $this->offense_id;
        }
        return parent::runUpdateQuery('mth_offense_notif', '`offense_id`=' . $this->getID());
    }

    /**
     * Set/Get Offense Id
     *
     * @param [type] $set
     * @return void
     */
    public function offenseId($set = NULL){
        if(!is_null($set)){
            $this->set('offense_id',(int) $set);
        }
        return (int)$this->offense_id;
    }

    /**
     * Get/Set Notice Type
     *
     * @param int $set
     * @return int
     */
    public function notifType($set = NULL){
        if(!is_null($set)){
            $this->set('type',(int)$set);
        }
        return (int)$this->type;
    }

    /**
     * Get Notice Type
     *
     * @return int
     */
    public function getType(){
        return (int)$this->type;
    }

    /**
     * Get Notice Type Name
     *
     * @return string
     */
    public function getTypeName(){
        return self::$notice_labels[$this->type];
    }

    /**
     * Get/Set Intervention Id
     *
     * @param int $set
     * @return int
     */
    public function interventionId($set = NULL){
        if(!is_null($set)){
            $this->set('intervention_id',(int)$set);
        }
        return (int)$this->intervention_id;
    }
    
    /**
     * Get/Set Student Id
     *
     * @param int $set
     * @return int
     */
    public function studentId($set = NULL){
        if(!is_null($set)){
            $this->set('mth_student_id',(int)$set);
        }
        return (int)$this->mth_student_id;
    }

    /**
     * Get/Set Year
     *
     * @param int $set
     * @return int
     */
    public function schoolYear($set = NULL){
        if(!is_null($set)){
            $this->set('school_year_id',(int)$set);
        }
        return (int)$this->school_year_id;
    }

    /**
     * Get Each Offense notice
     *
     * @param mth_student $student
     * @param mth_schoolYear $year
     * @param boolean $reset
     * @param string $where
     * @return $result
     */
    public static function each(mth_student $student,mth_schoolYear $year = NULL, $reset = FALSE,$where=''){
       
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return false;
        }

        if(!$student->getID()){
            return false;
        }

        $result = &self::$cache['each'][$student->getID()][$year->getID()];

        if ($reset || $result == NULL) {
            $WHERE = !empty($where)?' and '.$where:'';
            $sql = 'select * from mth_offense_notif where mth_student_id='.$student->getID().' and school_year_id='.$year->getID().$WHERE.' order by date_created desc';
            $result = core_db::runQuery($sql); 
        }

        if(!$reset && ($offense = $result->fetch_object('mth_offensenotif'))){
            return $offense;
        }

        $result->data_seek(0);
        return NULL;
    }

    /**
     * Get Offense Notice by Intervention
     *
     * @param mth_intervention $intervention
     * @param mth_schoolYear $year
     * @param boolean $reset
     * @return mth_offensenotif
     */
    public static function eachByIntervention(mth_intervention $intervention,mth_schoolYear $year = NULL, $reset = FALSE){
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return false;
        }

        if(!$intervention->getID()){
            return false;
        }

        $result = &self::$cache['eachByIntervention'][$intervention->getID()][$year->getID()];

        if(!isset($result)){
            $sql = 'select * from mth_offense_notif where intervention_id='.$intervention->getID().' and school_year_id='.$year->getID().' order by date_created desc';
            $result = core_db::runQuery($sql); 
        }

        if(!$reset && ($offense = $result->fetch_object('mth_offensenotif'))){
            return $offense;
        }

        $result->data_seek(0);
        return NULL;
    }

    /**
     * Get Latest Notification
     *
     * @param mth_student || mth_intervention $class
     * @return void
     */
    public static function getLatestNotif($class){
        if (!($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }
        $each = $class instanceof mth_student?'each':'eachByIntervention';

        $result = &self::$cache[$each][$class->getID()][$year->getID()];

        if (!isset($result)) {
            if($each == 'each'){
                self::each($class,$year,true);
            }else if($each == 'eachByIntervention'){
                self::eachByIntervention($class,$year,true);
            }            
        }

        return $result->fetch_object('mth_offensenotif');
    }

    public static function getLatestNotification(mth_student $student,$year = null){
        if(is_null($year)){
            if (!($year = mth_schoolYear::getCurrent())) {
                return NULL;
            }
        }
       
        $sql = 'select * from mth_offense_notif where mth_student_id='.$student->getID().' and school_year_id='.$year->getID().' order by date_created desc limit 1';
        return core_db::runGetObject($sql,__CLASS__); 
    }
    /**
     * Get Notice Date
     *
     * @param string $format Date format
     * @return string
     */
    public function getCreatedDate($format = NULL){
        return is_null($this->date_created)?null:core_model::getDate($this->date_created, $format);
    }

    /**
     * Get Notice Due Date
     *
     * @param string $format Date format
     * @return string
     */
    public function getDueDate($format = NULL){
        if(!is_null($this->date_created)){
            return self::dueDate($this->date_created,$this->getType(),$format);
        }
        return null;
    }

    /**
     * DEPRECATED FUNCTION
     * Undocumented function
     * @param [type] $format
     * @return void
     */
    private function _getDueDate($format = NULL){
        $due_at = null;
        switch($this->getType()){
            case self::TYPE_FIRST_NOTICE:
                $due_at =  strtotime("+5 days",strtotime($this->date_created));
            break;
            case self::TYPE_FINAL_NOTICE:
                $due_at =  strtotime("+3 days",strtotime($this->date_created));
            break;
            case self::TYPE_PROBATION:
                $due_at =  strtotime("+3 days",strtotime($this->date_created));
            break;
        }
        return !is_null($due_at)?Date($format,$due_at):null;
    }

    public static function dueDate($date = null,$type = null, $format = NULL){
        $due_at = null;
        switch($type){
            case self::TYPE_FIRST_NOTICE:
                $due_at =  strtotime("+5 days",strtotime($date));
            break;
            case self::TYPE_FINAL_NOTICE:
                $due_at =  strtotime("+3 days",strtotime($date));
            break;
            case self::TYPE_PROBATION:
                $due_at =  strtotime("+3 days",strtotime($date));
            break;
            case self::TYPE_EXCEED_EX:
                $due_at = strtotime("+1 days", strtotime($date));
            break;
        }
        return !is_null($due_at)?Date($format,$due_at):null;
    }
    /**
     * Get Notice Count
     *
     * @param mth_student $student
     * @param mth_schoolYear $year
     * @param string $where
     * @return int
     */
    public static function count(mth_student $student,mth_schoolYear $year = NULL,$where = ''){
        if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }

        $result = &self::$cache['each'][$student->getID()][$year->getID()];
        self::each($student,$year,true,$where);

        return $result->num_rows;
    }
    /**
     * Get Notice Type attr
     *
     * @return array
     */
    private function getNoticeAttr(){

        $notice = [
            'preffix' => '',
            'due' => null
        ];

        switch($this->getType()){
            case self::TYPE_FIRST_NOTICE:
               $notice = [
                    'preffix' => 'first',
                    'due' => '+5 days',
                    'fields' => [
                        '[DUE_DATE]','[STUDENT_FIRST]','[PARENT_FIRST]'
                    ]
                ];
            break;
            case self::TYPE_FINAL_NOTICE:
                $notice = [
                    'preffix' => 'final',
                    'due' => '+3 days',
                    'fields' => [
                        '[DUE_DATE]','[STUDENT_FIRST]','[PARENT_FIRST]'
                    ]
                ];
            break;
            case self::TYPE_HEADSUP_NOTICE:
                $notice = [
                    'preffix' => 'headsUp',
                    'due' => null,
                    'fields' => [
                        '[PARENT_FIRST]'
                    ]
                ];
            break;
            case self::TYPE_CONSECUTIVE_EX:
                $notice = [
                    'preffix' => 'consecutiveEx',
                    'due' => null,
                    'fields' => [
                        '[PARENT_FIRST]','[STUDENT_FIRST]'
                    ]
                ];
            break;
            case self::TYPE_PROBATION:
            $notice = [
                'preffix' => 'probation',
                'due' => '+3 days',
                'fields' => [
                    '[DUE_DATE]','[STUDENT_FIRST]','[PARENT_FIRST]'
                ]
            ];
            break;
            case self::TYPE_MISSING:
            $notice = [
                'preffix' => 'missingLog',
                'fields' => [
                    '[STUDENT_FIRST]','[PARENT_FIRST]'
                ]
            ];
            break;
            case self::TYPE_EXCEED_EX:
              $notice = [
                'preffix' => 'exceedEX',
                'due' => '+1 days',
                'fields' => [
                  '[DUE_DATE]', '[STUDENT_FIRST]', '[PARENT_FIRST]'
                ]
              ];
            break;
        }
        return $notice;
    }

    /**
     * Send custom Missing notice
     * @param mth_student $student
     * @param mth_parent $parent
     * @return boolean
     */
    private function sendMissing(mth_student $student, mth_parent $parent){
       
        if(($_content = core_setting::get('missingLogContent', 'LearningLog'))
        && ($subject  = core_setting::get('missingLogSubject', 'LearningLog'))){
            $content = str_replace(
                '[STUDENT_FIRST]',
                $student->getPreferredFirstName(),
                $_content
            );
            return $this->sendViaSes($student,$parent,$content,$subject);
        }
        return false;
    }

    private function sendViaSes(mth_student $student, mth_parent $parent, $content,$subject){
        $reply_to = core_setting::get("interventionbcc",'Interventions')->getValue();
            $bcc = $reply_to;
            
            $fromname =  core_setting::getSiteName()->getValue();
            $fromemail = core_email::SMTPaddress();

            $ses = new ses();
            return $ses->send(
                [$parent->getEmail()],
                '"'.$fromname.'"<'.$fromemail.'>',
                $subject,
                $content,
                [$bcc],
                [$reply_to]
            );
    }

    /**
     * Send Custom Notification that does not belong to intervention format
     * @param mth_student $student
     * @param boolean $reminder
     * @return void
     */
    public function sendCustom(mth_student $student,$reminder = false){
        if (!($parent = $student->getParent())){
            return false;
        }
        $notice = $this->getType();
        if($notice == self::TYPE_MISSING){
            return $this->sendMissing($student,$parent);
        }
        return false;
    }
    /**
     * Send Notification
     *
     * @param mth_student $student
     * @param boolean $reminder
     * @return void
     */
    public function send(mth_student $student,$reminder = false){
        $notice_attr = $this->getNoticeAttr();
        $preffix = $notice_attr['preffix'];

        if (!($parent = $student->getParent())
        || !(core_setting::get("{$preffix }NoticeEmailSubject", 'Interventions'))
        ) {
            return false;
        }
        
        $notice = $this->getType();


        $_due_at = isset($notice_attr['due'])?$notice_attr['due']:null;
        //if reminder use the latest sent date
        $due_at = $reminder?
           strtotime($_due_at,strtotime($this->date_created)):
           strtotime($_due_at);
    
        $due_date = Date('l, F jS',$due_at);

        $attr = array(
            '[DUE_DATE]' => $due_date,
            '[STUDENT_FIRST]' =>  $student->getPreferredFirstName(),
            '[PARENT_FIRST]'  => $parent->getPreferredFirstName()               
        );

        $content_values = [];
        foreach($notice_attr['fields'] as $fields){
            $content_values[] = $attr[$fields];
        }

        $bccs = [core_setting::get("interventionbcc",'Interventions')->getValue()];

        if($notice == self::TYPE_CONSECUTIVE_EX){
            $bccs[] = core_setting::get("interventionconsecutiveexbcc",'Interventions')->getValue();
        }

        $email = new core_emailservice();
        $success = $email->send(
            array($parent->getEmail()),
            $this->getSubjectEmail($due_at,$preffix),
            $this->getContentEmail(
                $notice_attr['fields'],
                $content_values,
                $preffix
            ),
            null,
            $bccs,
            [core_setting::get("interventionbcc",'Interventions')->getValue()]
        );

        //no attachement for now
        // if($notice == self::TYPE_FIRST_NOTICE || $notice == self::TYPE_HEADSUP_NOTICE){
        //     $email->addAttachments([
        //         [
        //         'path' => ROOT . '/_/mth_files/Weekly_Learning_Log_Scoring_Guide_mth.pdf',
        //         'name' => 'Weekly Learning Log Scoring Guide.pdf'
        //         ]
        //     ]);
        // }

        //$email->setBCCBatch($bccs);


        if( !$success ){
            // error_log($email->ErrorInfo);
            return false;
        }

        return $success;
        // $_raw = $mail ->getSentMIMEMessage();
        // $raw  = str_replace('Subject:',("Bcc: ".implode(',',$bccs)." \r\n".'Subject:'),$_raw);
        
        // $ses = new core_emailservice();
        // $ses->enableTracking(true);
        // return $ses->sendRaw($raw);
    }

    /**
     * Get Subject Email String
     *
     * @param datetime $due_at
     * @param string $preffix
     * @return string
     */
    private function getSubjectEmail($due_at = null,$preffix){
        if(!is_null($due_at)){
            $sdue_date = Date('M j, Y',$due_at);
            return str_replace(
                '[DUE_DATE]',
                $sdue_date,
                core_setting::get("{$preffix}NoticeEmailSubject",'Interventions')
            );
        }
        return core_setting::get("{$preffix}NoticeEmailSubject",'Interventions');
    }

    /**
     * Get Content Email String
     *
     * @param array $params
     * @param array $value
     * @param string $preffix
     * @return string
     */
    private function getContentEmail($params = array(),$value = array(),$preffix){
        return str_replace(
            $params,
            $value,
            core_setting::get("{$preffix}NoticeEmailContent",'Interventions')->getValue()
        );
    }

    /**
     * Check if Notification is Past Due
     *
     * @return boolean
     */
    public function isPastDue(){
        if($this->type == self::TYPE_FIRST_NOTICE){
            return strtotime($this->date_created) <  strtotime('-5 days');
        }else if($this->type == self::TYPE_FINAL_NOTICE){
            return strtotime($this->date_created) <  strtotime('-3 days');
        }else if($this->type == self::TYPE_PROBATION){
            return strtotime($this->date_created) <  strtotime('-3 days');
        }
        return false;
    }

}