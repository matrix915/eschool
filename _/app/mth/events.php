<?php
class mth_events extends core_model{
    protected $event_id;
    protected $name;
    protected $color;
    protected $content;
    protected $start_date;
    protected $end_date;  
    protected static $cache = array();
    private $db;

    public function __construct(){
        $db = new core_db();
        $this->db = $db;
    }

    public function id($set=NULL){
        if(!is_null($set)){
            $this->set('event_id',$set);
        }
        return $this->event_id;
    }

    public function eventName($set = NULL){
        if(!is_null($set)){
            $this->set('name',$set);
        }
        return $this->name;
    }

    public function color($set = NULL){
        if(!is_null($set)){
            $this->set('color',$set);
        }
        return $this->color;
    }

    public function content($set = NULL){
        if(!is_null($set)){
            $this->set('content',self::sanitizeAndFixHTML($set));
        }
        return $this->content;
    }

    public function startDate($set = NULL){
        if(!is_null($set)){
            $date = empty(trim($set))?null:date('Y-m-d H:i:s',strtotime($set));
            $this->set('start_date', $date);
        }
        return $this->start_date;
    }

    public function endDate($set = NULL){
        if(!is_null($set)){
            $date = empty(trim($set))?null:date('Y-m-d H:i:s',strtotime($set));
            $this->set('end_date', $date );
        }
        return $this->end_date;
    }
    
    public function save()
    {
        $columns = [
           'name' => $this->db->escape_string(self::sanitizeText($this->name)),
           'content' => $this->db->escape_string(self::sanitizeAndFixHTML($this->content)),
           'color' => $this->color,
           'start_date' => $this->start_date,
           'end_date' => $this->end_date
        ];
        
        $data_types = [
            '"%s"','"%s"','"%s"','"%s"','"%s"',
        ];

        if($this->event_id){
           $columns = array_merge($columns,[
               'event_id'=>$this->event_id
           ]);
           $data_types[] = '%d';
        }

        if(!$this->event_id){
            $this->db->query(
               vsprintf(
                    'INSERT INTO mth_events ('.implode(',',array_keys($columns)).')
                    VALUES('.implode(',',$data_types).')',
                    array_values($columns)
                )
            );
            $this->event_id = $this->db->insert_id;
            return $this->event_id;
        }
        return parent::runUpdateQuery('mth_events', '`event_id`=' . $this->id());
    }

    public static function each($where = '',$reset = FALSE){

        $result = &self::$cache['each'];

        if(!isset($result)){
            $_where = !empty(trim($where))?' where '.$where:'';
            $sql = 'select * from mth_events'.$_where;
            $result = core_db::runQuery($sql); 
        }

        if(!$reset && ($events = $result->fetch_object('mth_events'))){
            return $events;
        }

        $result->data_seek(0);
        return NULL;
    }

    public function delete(){
        return core_db::runQuery('DELETE FROM mth_events WHERE event_id=' . $this->id());
    }

    public static function getByID($event_id)
    {
        if(!$event_id){
            return null;
        }
        $event = &self::$cache['getByID'][$event_id];

        if (!isset($event)) {
            $event = core_db::runGetObject('SELECT * FROM mth_events 
                                              WHERE event_id=' . (int)$event_id,
                'mth_events');
        }
        return $event;
    }

    public static function getUpcoming($reset = FALSE){
        $result = &self::$cache['getUpcoming'];
        
        if(!isset($result)){
            $sql = 'SELECT * FROM mth_events where DATE(start_date)>=DATE(NOW()) order by start_date limit 4';
            $result = core_db::runQuery($sql); 
        }

        if(!$reset && ($events = $result->fetch_object('mth_events'))){
            return $events;
        }

        $result->data_seek(0);
        return NULL;
    }
    
    public static function sanitizeText($text)
    {
        return req_sanitize::txt($text);
    }

    public static function sanitizeAndFixHTML($HTML)
    {
        $HTML = trim($HTML);
        if (empty($HTML)) {
            return '';
        }
        include_once ROOT . '/_/includes/HTMLPurifier/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Trusted', true);
        $config->set('CSS.Trusted', true);
        $config->set('HTML.TargetBlank', true);
        $htmlFixer = new HTMLPurifier($config);
        return $htmlFixer->purify($HTML);
    }
}