<?php

/**
 * Stores canvas user information for interacting with canvas
 *
 * @author abe
 */
class mth_canvas_user extends core_model
{
    protected $canvas_user_id;
    protected $mth_person_id;
    protected $email;
    protected $to_be_pushed;
    protected $last_pushed;
    protected $canvas_login_id;
    protected $last_login;
    protected $avatar_url;


    protected static $cache = array();

    /**
     * To store the person object
     * @var mth_person
     */
    protected $person;

    public function id()
    {
        return (int)$this->canvas_user_id;
    }

    public function to_be_pushed()
    {
        return $this->to_be_pushed;
    }

    public function avatar_url(){
        return $this->avatar_url;
    }

    public function set_to_be_pushed($to_be_pushed)
    {
        $this->set('to_be_pushed', $to_be_pushed ? 1 : 0);
    }

    protected function set_last_pushed()
    {
        $this->set('last_pushed', date('Y-m-d H:i:s'));
    }

    public function person_id(){
        return $this->mth_person_id;
    }

    public function canvas_login_id()
    {
        if (!$this->canvas_login_id
            && $this->canvas_user_id
            && ($result = mth_canvas::exec('/users/' . $this->canvas_user_id . '/logins'))
            && is_array($result)
            && isset($result[0]->id)
        ) {
            $this->canvas_login_id = $result[0]->id;
            core_db::runQuery('UPDATE mth_canvas_user SET canvas_login_id=' . (int)$this->canvas_login_id . ' 
                          WHERE canvas_user_id=' . $this->id());
        }
        return (int)$this->canvas_login_id;
    }

    public function save()
    {
        if (!$this->canvas_user_id) {
            return FALSE;
        }
        return parent::runUpdateQuery('mth_canvas_user', 'canvas_user_id=' . $this->id());
    }

    public function __destruct()
    {
        $this->save();
    }

    public function delete()
    {
        if (($result = mth_canvas::exec('/accounts/' . mth_canvas::account_id() . '/users/' . $this->id(), array(), mth_canvas::METHOD_DELETE))
            && isset($result->id) && $result->id == $this->id()
        ) {
            $this->updateQueries = array();
            unset(self::$cache['get'][$this->mth_person_id]);
            return mth_canvas_enrollment::deleteUserEnrollmentRecords($this)
            && core_db::runQuery('DELETE FROM mth_canvas_user WHERE canvas_user_id=' . $this->id());
        }
        return false;
    }

    /**
     *
     * @return mth_parent|mth_student
     */
    public function person()
    {
        if (!$this->person) {
            $this->person = mth_person::getPerson($this->mth_person_id);
        }
        return $this->person;
    }

    /**
     *
     * @param mth_person $person
     * @return mth_canvas_user
     */
    public static function get(mth_person $person, $returnTempIfNone = false, $forceRefresh = false)
    {
        /* @var $canvas_user mth_canvas_user */
        $canvas_user = &self::$cache['get'][$person->getPersonID()];
        if (!isset($canvas_user) || $forceRefresh) {
            $canvas_user = core_db::runGetObject('SELECT * FROM mth_canvas_user 
                                            WHERE mth_person_id=' . $person->getPersonID() . '
                                              OR email="' . core_db::escape($person->getEmail()) . '"',
                'mth_canvas_user');
            if ($canvas_user && !$canvas_user->mth_person_id) {
                $canvas_user->set('mth_person_id', $person->getPersonID());
                $canvas_user->save();
            }
            if (!$canvas_user && $returnTempIfNone) {
                $canvas_user = new mth_canvas_user();
                $canvas_user->mth_person_id = $person->getPersonID();
                $canvas_user->to_be_pushed = true;
            }
            if ($canvas_user) {
                $canvas_user->person = $person;
            }
        }
        return $canvas_user;
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param bool $to_be_pushed Can by TRUE, FALSE, NULL (Null for all)
     * @param bool $reset
     * @return mth_canvas_user
     */
    public static function each(mth_schoolYear $year = NULL, $to_be_pushed = true, $reset = false)
    {
        $result = &self::$cache['each'][$year ? $year->getID() : NULL][(is_null($to_be_pushed) ? -1 : (!$to_be_pushed ? 0 : 1))];
        if (!isset($result)) {
            if (is_null($year) && !($year = mth_schoolYear::getCurrent())) {
                return false;
            }
            $filter = new mth_person_filter();
            $filter->setStatusYear($year->getID());
            $filter->setStatus(mth_student::STATUS_ACTIVE);
            $personIDs = $filter->getPersonIDs();
            $result = core_db::runQuery('SELECT 
                                  cu.canvas_user_id, p.person_id AS mth_person_id,
                                  IFNULL(cu.to_be_pushed,1) AS to_be_pushed, cu.last_pushed
                                FROM mth_person AS p
                                    LEFT JOIN mth_canvas_user AS cu ON cu.mth_person_id=p.person_id
                                WHERE ' . (is_null($to_be_pushed) ? '1' : 'IFNULL(cu.to_be_pushed,1)=' . ($to_be_pushed ? 1 : 0)) . '
                                  AND p.person_id IN (' . implode(',', ($personIDs ? $personIDs : array(0))) . ')');
        }
        if (!$reset && ($user = $result->fetch_object('mth_canvas_user'))) {
            return $user;
        }
        $result->data_seek(0);
        return NULL;
    }


    public static function count(mth_schoolYear $year = NULL, $to_be_pushed = true, $forceUpdate = false)
    {
        self::each($year, $to_be_pushed, true);
        $result = &self::$cache['each'][$year ? $year->getID() : NULL][(is_null($to_be_pushed) ? -1 : (!$to_be_pushed ? 0 : 1))];
        return $result->num_rows;
    }

    /**
     *
     * @param int $canvas_user_id
     * @return mth_canvas_user
     */
    public static function getByID($canvas_user_id)
    {
        $user = &self::$cache['getByID'][$canvas_user_id];
        if (!isset($user)) {
            $user = core_db::runGetObject('SELECT * FROM mth_canvas_user 
                                    WHERE canvas_user_id=' . (int)$canvas_user_id,
                'mth_canvas_user');
        }
        return $user;
    }

    /**
     *
     * @param int $canvas_user_id
     * @return mth_canvas_user
     */
    public static function getByPersonID($person_id, $to_be_pushed = true)
    {
        $user = &self::$cache['getByPersonID'][$person_id];
        if (!isset($user)) {
            $user = core_db::runGetObject('SELECT 
                            cu.canvas_user_id, p.person_id AS mth_person_id,
                            IFNULL(cu.to_be_pushed,1) AS to_be_pushed, cu.last_pushed
                        FROM mth_person AS p
                            LEFT JOIN mth_canvas_user AS cu ON cu.mth_person_id=p.person_id
                        WHERE ' . (is_null($to_be_pushed) ? '1' : 'IFNULL(cu.to_be_pushed,1)=' . ($to_be_pushed ? 1 : 0)) . '
                            AND p.person_id IN ('.$person_id.') limit 1',
                'mth_canvas_user');
        }
        return $user;
    }

    /**
     *
     * @return boolean  TRUE on completion, FALSE on failure
     */
    public static function pull()
    {
        core_db::runQuery('DELETE cu.* FROM mth_canvas_user AS cu LEFT JOIN mth_person AS p ON p.person_id=cu.mth_person_id WHERE p.person_id IS NULL');
        $command = '/accounts/' . mth_canvas::account_id() . '/users?per_page=50&include[]=last_login&include[]=avatar_url&page=';
        $page = 1;
        while ($result = mth_canvas::exec($command . $page)) {
            if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return FALSE;
            }
            if (count($result) == 0) {
                break;
            }
            if (!self::insert($result)) {
                error_log('Unable to save canvas user mapping to database');
                return FALSE;
            }
            $page++;
        }
        return true;
    }

    /**
     * Delete canvas user records for non existing infocenter user
     * @return boolean
     */
    public static  function deleteUnmatched(){
        return core_db::runQuery('DELETE cu.* FROM mth_canvas_user AS cu LEFT JOIN mth_person AS p ON p.person_id=cu.mth_person_id WHERE p.person_id IS NULL');
    }

    /**
     * Canvas user pull per page
     * @param int $page
     * @return boolean
     */
    public static function singlePull($page){
        $command = '/accounts/' . mth_canvas::account_id() . '/users?per_page=50&include[]=last_login&include[]=avatar_url&page='.$page;
        $count = 0;
        
        if($result = mth_canvas::exec($command)){
            
            if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return [
                    'error' =>  TRUE,
                    'result' => $count
                ];
            }
            
            $count = count($result);

            if (!self::insert($result)) {
                error_log('Unable to save canvas user mapping to database');
                return [
                    'error' =>  TRUE,
                    'result' => $count
                ];
            }
        }

        return [
            'error' => FALSE,
            'result' => $count
        ];
    }

    protected static function insert($insertArr)
    {
        $success = array();
        foreach ($insertArr as $userObj) {
            if (!($person = mth_parent::getByEmail($userObj->login_id))
                && !($person = mth_student::getByEmail($userObj->login_id))
            ) { 
                $person_id = 'NULL';
                $to_be_pushed = 0;
            } else {
                $person_id = $person->getPersonID();
                $to_be_pushed = $person->getPreferredFirstName() != $userObj->short_name
                    || $person->getName() != $userObj->name
                    || $person->getPreferredLastName() . ', ' . $person->getPreferredFirstName() != $userObj->sortable_name;
            }
            $avatar_url = $userObj->avatar_url?'"'.$userObj->avatar_url.'"':'NULL';
            
            $last_login = $userObj->last_login?"'$userObj->last_login'":"NULL";
            
            $sql = 'INSERT INTO mth_canvas_user 
                                      (canvas_user_id, mth_person_id, `email`, to_be_pushed,avatar_url) 
                                        VALUES (' . (int)$userObj->id . ',' .
                $person_id . ',' .
                '"' . core_db::escape(strtolower($userObj->login_id)) . '",' .
                (int)$to_be_pushed . ', '.$avatar_url.') ON DUPLICATE KEY UPDATE last_login='.$last_login;
           
            $success[] = core_db::runQuery($sql);

        }
        return count($success) == count(array_filter($success));
    }

    public static function update_last_login($canvas_user_id,$last_login){
        $sql = "UPDATE mth_canvas_user set last_login='".$last_login."' where canvas_user_id=$canvas_user_id";
        return core_db::runQuery($sql);
    }

    public function push($updateIfExisting = true, $createIfNonExsistant = true, mth_canvas_user $observee = null)
    {
        if ($this->canvas_user_id) {
            if (!$updateIfExisting) {
                return true;
            }
            return $this->update_canvas($observee);
        } else {
            if (!$createIfNonExsistant) {
                return true;
            }
            return $this->create_in_canvas($observee);
        }
    }

    protected function update_canvas(mth_canvas_user $observee = null)
    {
        if (!$this->canvas_user_id) {
            return false;
        }

         

        $params =  array(
            'user[name]' => req_sanitize::txt_decode($this->person()->getPreferredFirstName() . ' ' . $this->person()->getPreferredLastName()),
            'user[short_name]' => req_sanitize::txt_decode($this->person()->getPreferredFirstName()),
            'user[sortable_name]' => req_sanitize::txt_decode($this->person()->getPreferredLastName() . ', ' . $this->person()->getPreferredFirstName()),
            'initial_enrollment_type' => 'student',
        );

        // if(is_null($this->last_login)){
        //     $password = $this->generatePassword();
        //     $this->update_canvas_login_password($password);
        //     $this->send_account_creation_email($password);
        // }

        $userObj = mth_canvas::exec(
            '/users/' . $this->id(),
           $params,
            mth_canvas::METHOD_PUT); 
           
        if (is_object($userObj) && isset($userObj->id)) {
            $this->set_to_be_pushed(false);
            $this->set_last_pushed();
            $this->save();
            if($observee){
                //disable observer
                //$this->addObservee($observee);
            }
            return true;
        }
        return false;
    }

    public function addObservee(mth_canvas_user $observee = null){
        mth_canvas::exec('/users/' . $this->id().'/observees/'.$observee->id(),
            [],
            mth_canvas::METHOD_PUT);
    }

    public function update_canvas_login_email()
    {
        if (!$this->person()) {
            return false;
        }
        $result = mth_canvas::exec(
            '/accounts/' . mth_canvas::account_id() . '/logins/' . $this->canvas_login_id(),
            array('login[unique_id]' => $this->person()->getEmail()),
            mth_canvas::METHOD_PUT);
        if (is_object($result) && isset($result->unique_id) && $result->unique_id == $this->person()->getEmail()) {
            return true;
        }
        return false;
    }
    public function update_canvas_default_email()
    {
        if (!$this->person()) {
            return false;
        }
        $result = mth_canvas::exec(
            '/users/' . $this->canvas_user_id ,
            array('user[email]' => $this->person()->getEmail()),
            mth_canvas::METHOD_PUT);
        if (is_object($result) && isset($result->email) && $result->email == $this->person()->getEmail()) {
            return true;
        }
        return false;
    }

    public function update_canvas_login_password($password)
    {
        if (!$this->person()) {
            return false;
        }
        $result = mth_canvas::exec(
            '/accounts/' . mth_canvas::account_id() . '/logins/' . $this->canvas_login_id(),
            array('login[password]' => $password),
            mth_canvas::METHOD_PUT);
        if (is_object($result) && isset($result->unique_id) && $result->unique_id == $this->person()->getEmail()) {
            return true;
        }
        return false;
    }

    /**
     * Random Password Generator
     * @return string
     */
    protected function generatePassword(){
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = [];
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    protected function send_account_creation_email($password){
        $isParent = mth_parent::isParent($this->person()->getPersonID());

        $parent =  $isParent?$this->person():$this->person()->getParent();

        if(!core_user::validateEmail($parent->getEmail())){
            error_log('Invalid Email:'.$parent->getEmail());
            return false;
        }

        $email = new core_emailservice();
        return $email->send(
            [$parent->getEmail()],
            core_setting::get('canvasAccountSubject', 'User'),
            str_replace(
                [
                    '[FIRST_NAME]',
                    '[EMAIL]',
                    '[PASSWORD]',
                ],
                [
                    $this->person()->getPreferredFirstName(),
                    $this->person()->getEmail(),
                    $password
                ],
                core_setting::get('canvasAccount', 'User')
            )
        );
    }

    protected function create_in_canvas(mth_canvas_user $observee = null)
    {
        if ($this->canvas_user_id) {
            return false;
        }

        $password = $this->generatePassword();

        $params =  array(
            'user[name]' => req_sanitize::txt_decode($this->person()->getPreferredFirstName() . ' ' . $this->person()->getPreferredLastName()),
            'user[short_name]' => req_sanitize::txt_decode($this->person()->getPreferredFirstName()),
            'user[sortable_name]' => req_sanitize::txt_decode($this->person()->getPreferredLastName() . ', ' . $this->person()->getPreferredFirstName()),
            'pseudonym[unique_id]' => $this->person()->getEmail(),
            //'pseudonym[sis_user_id]'=>$this->person()->getID(),
            'user[terms_of_use]' => 1,
            'initial_enrollment_type' => 'student',
        );

        if(is_null($this->last_login)){
            $params = array_merge($params,['pseudonym[password]' => $password]);
        }

        $userObj = mth_canvas::exec(    
            '/accounts/' . mth_canvas::account_id() . '/users',
            $params
        );

        if(is_null($this->last_login)){
            $this->send_account_creation_email($password);
        }

        if (is_object($userObj) && isset($userObj->id)) {
            $this->canvas_user_id = $userObj->id;
            if($observee){
                //disable observer
                //$this->addObservee($observee); 
            }
            return core_db::runQuery(sprintf('INSERT IGNORE INTO mth_canvas_user 
                              (canvas_user_id, mth_person_id, to_be_pushed, last_pushed) 
                              VALUES (%d,%d,0,NOW())',
                $userObj->id,
                $this->person()->getPersonID()));
        }
        return false;
    }

    /**
     * creates the student and parent account for the specified student
     * @param mth_student $student
     * @param bool $updateIfExisting
     * @return boolean
     */
    public static function createCanvasAccounts(mth_student $student, $updateIfExisting = true)
    {
        if (!$student->getParent()) {
            return false;
        }
        return ($studentAccount = self::get($student, true))
        && $studentAccount->push($updateIfExisting);

        //disable parent account creation
        // && ($parentAccount = self::get($student->getParent(), true))
        // && $parentAccount->push($updateIfExisting,true,$studentAccount);
    }

    /**
     * @param boolean $createIfNonExsistant
     * @param mth_schoolYear $year
     * @param int $quitAfterSeconds
     * @return boolean|null TRUE on success, FALSE if there are errors, NULL on timout
     */
    public static function pushUserAccounts($createIfNonExsistant = true, mth_schoolYear $year = NULL, $quitAfterSeconds = 28)
    {
        $endTime = time() + $quitAfterSeconds;
        $success = array();
        while ($user = self::each($year)) {
            $success[] = $user->push(true, $createIfNonExsistant);
            if (time() >= $endTime) {
                return count($success) == count(array_filter($success)) ? NULL : FALSE;
            }
        }
        return count($success) == count(array_filter($success));
    }

    public static function pushAccountByPersonId($person_id,$createIfNonExsistant = true){
        if($user = self::getByPersonID($person_id)){
            return $user->push(true, $createIfNonExsistant);
        }
        return false;
    }

    public static function flush()
    {
        return core_db::runQuery('DELETE FROM mth_canvas_user WHERE 1');
    }

    public function getLastLogin($format = NULL){
        return $this->last_login=='0000-00-00 00:00:00' || is_null($this->last_login)?null:core_model::getDate($this->last_login, $format);
    }
}
