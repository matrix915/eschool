<?php

/**
 * person
 *
 * @author abe
 */
class mth_person
{
    protected $person_id;
    protected $first_name;
    protected $middle_name;
    protected $last_name;
    protected $preferred_first_name;
    protected $preferred_last_name;
    protected $gender;
    protected $email;
    public $date_of_birth;
    protected $user_id;
    public $school_district;

    const GEN_MALE = 'Male';
    const GEN_FEMALE = 'Female';

    private $_updateQuery = array();

    protected static $cache;

    public static function getPeople(ARRAY $filters = array())
    {
        return core_db::runGetObjects('
      SELECT * FROM mth_person 
      WHERE 1
        ' . (isset($filters['email'])
                ? 'AND `email`="' . core_db::escape(strtolower($filters['email'])) . '"' : ''),
            'mth_person');
    }

    /**
     *
     * @param str $searchString
     * @param bool $reset
     * @return mth_person
     */
    public static function old_search($searchString, $reset = false)
    {
        if (trim($searchString) == '') {
            return NULL;
        }
        $result = &self::$cache['search-' . $searchString];
        if (!isset($result)) {
            $terms = array_values(array_filter(array_map('trim', explode(' ', str_replace(array(',', ':', '(', ')'), ' ', $searchString)))));
            if (count($terms) == 2) {
                $Q1s = '((p.first_name LIKE "%' . core_db::escape($terms[0]) . '%"
                      OR p.preferred_first_name LIKE "%' . core_db::escape($terms[0]) . '%")
                  AND (p.last_name LIKE "%' . core_db::escape($terms[1]) . '%"
                      OR p.preferred_last_name LIKE "%' . core_db::escape($terms[1]) . '%"))
                OR ((p.first_name LIKE "%' . core_db::escape($terms[1]) . '%"
                      OR p.preferred_first_name LIKE "%' . core_db::escape($terms[1]) . '%")
                  AND (p.last_name LIKE "%' . core_db::escape($terms[0]) . '%"
                      OR p.preferred_last_name LIKE "%' . core_db::escape($terms[0]) . '%"))';
            }
            $Qs = array();
            foreach ($terms as $term) {
                $Qs[] = 'p.first_name LIKE "%' . core_db::escape($term) . '%"';
                $Qs[] = 'p.preferred_first_name LIKE "%' . core_db::escape($term) . '%"';
                $Qs[] = 'p.last_name LIKE "%' . core_db::escape($term) . '%"';
                $Qs[] = 'p.preferred_last_name LIKE "%' . core_db::escape($term) . '%"';
            }
            $result = core_db::runQuery(
                (isset($Q1s)
                    ? '(SELECT p.*
                  FROM mth_person AS p
                  WHERE ' . $Q1s . '
                  ORDER BY  p.preferred_last_name ASC, p.preferred_first_name ASC)
                      
                UNION DISTINCT
                  
                  '
                    : '') .
                '(SELECT p.*
                FROM mth_person AS p
                WHERE ' . implode(' OR ', $Qs) . '
                ORDER BY  p.preferred_last_name ASC, p.preferred_first_name ASC)');
        }
        if (!$result) {
            return NULL;
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }
        if (($person = $result->fetch_object('mth_person'))) {
            return $person;
        } else {
            self::search($searchString, true);
        }
    }

    public static function search($searchString, $reset = false){
        if (trim($searchString) == '') {
            return NULL;
        }
        $result = &self::$cache['search-' . $searchString];
        if (!isset($result)) {
            $term = str_replace([',', ':', '(', ')',' '], '',trim($searchString));
            $where = [
                "concat(REPLACE(first_name, ' ', ''),REPLACE(last_name, ' ', '')) like '%".core_db::escape($term) ."%'",
                "concat(REPLACE(last_name, ' ', ''),REPLACE(first_name, ' ', '')) like '%".core_db::escape($term) ."%'",
                "concat(REPLACE(preferred_first_name, ' ', ''),REPLACE(preferred_last_name, ' ', '')) like '%".core_db::escape($term) ."%'",
                "concat(REPLACE(preferred_last_name, ' ', ''),REPLACE(preferred_first_name, ' ', '')) like '%".core_db::escape($term) ."%'",
            ];
            $result = core_db::runQuery(
                'SELECT * from mth_person where ' . implode(' OR ', $where) . ' ORDER BY  preferred_last_name ASC, preferred_first_name ASC'
            );
           
        }
        if (!$result) {
            return NULL;
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }
        if (($person = $result->fetch_object('mth_person'))) {
            return $person;
        } else {
            self::search($searchString, true);
        }
    }
    

    /**
     * @param $person_id
     * @return mth_parent|mth_student
     */
    public static function getPerson($person_id)
    {
        if (mth_parent::isParent($person_id)) {
            return mth_parent::getByPersonID($person_id);
        } else {
            return mth_student::getByPersonID($person_id);
        }
    }

    protected static function create()
    {
        core_db::runQuery('INSERT INTO mth_person (first_name) VALUES ("UNKNOWN")');
        return core_db::getInsertID();
    }

    public static function new($user_id,$email,$firstname,$last_name)
    {
        $email = strtolower($email);
        core_db::runQuery("INSERT INTO mth_person (user_id,email,first_name,last_name) VALUES ($user_id,'$email','$firstname','$last_name')");
        return core_db::getInsertID();
    }

    public function __toString()
    {
        return $this->getPreferredFirstName() . ' ' . $this->getPreferredLastName();
    }

    public function getName($lastFirst = false,$legalName = false)
    {
        if($legalName){
            if ($lastFirst) {
                return $this->getLastName() . ', ' . $this->getFirstName();
            }
            return $this->getFirstName() . ' ' . $this->getLastName();
        }

        if ($lastFirst) {
            return $this->getPreferredLastName() . ', ' . $this->getPreferredFirstName();
        }
        return $this->getPreferredFirstName() . ' ' . $this->getPreferredLastName();
    }

    public function getPersonID()
    {
        return (int)$this->person_id;
    }

    public function getStudentID()
    {
        return (int)$this->student_id;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }
    
    public function getLastLogin($format = null)
    {
        // $user = mth_canvas_user::get($this);
        // $last_login = $user ? $user->last_login : '';
        if($user = $this->user()){
            return $user->getLastLogin()?$user->getLastLogin($format):null;
        }
       return null;
    }

    public function getMiddleName()
    {
        return $this->middle_name;
    }

    public function getPreferredFirstName()
    {
        if (!empty($this->preferred_first_name)) {
            return $this->preferred_first_name;
        }
        return $this->first_name;
    }

    public function getPreferredLastName()
    {
        if (!empty($this->preferred_last_name)) {
            return $this->preferred_last_name;
        }
        return $this->last_name;
    }

    public function getGender()
    {
        return $this->gender;
    }   

    public function getEmail($htmlLink = false)
    {
        if ($htmlLink && !empty($this->email)) {
            return '<a href="mailto:' . strtolower($this->email) . '">' . strtolower($this->email) . '</a>';
        }
        return strtolower($this->email);
    }

    /**
     *
     * @param string $format
     * @return string|int If format is provided a string will be retured, otherwize an int timestamp will be returned
     */
    public function getDateOfBirth($format = NULL)
    {
        if (empty($this->date_of_birth)) {
            return NULL;
        }
        if ($format) {
            return date($format, strtotime($this->date_of_birth));
        }
        return strtotime($this->date_of_birth);
    }

    public function getAge()
    {
        $DOB = $this->getDateOfBirth();
        if (!$DOB) {
            return false;
        }
        $date = time();
        $age = 0;
        while ($date > $DOB = strtotime('+1 year', $DOB)) {
            ++$age;
        }
        return $age;
    }

    public function getSchoolDistrict()
    {
        return $this->school_district;
    }

    public function setSchoolDistrict($schoolDistrict)
    {
        if ($schoolDistrict == $this->school_district) {
            return true;
        }
        $this->_updateQuery[] = 'school_district="' . $schoolDistrict . '"';
        return true;
    }

    public function setName($first_name = NULL, $last_name = NULL, $middle_name = NULL, $preferred_first_name = NULL, $preferred_last_name = NULL)
    {
        if ($first_name !== NULL && $first_name != $this->first_name) {
            mth_log::log($this, mth_log::FIELD_FIRST_NAME, core_user::sanitize(ucfirst($first_name)), $this->first_name);
            $this->first_name = core_user::sanitize(ucfirst($first_name));
            $this->_updateQuery[] = 'first_name="' . core_db::escape($this->first_name) . '"';
        }
        if ($last_name !== NULL && $last_name != $this->last_name) {
            mth_log::log($this, mth_log::FIELD_LAST_NAME, core_user::sanitize(ucfirst($last_name)), $this->last_name);
            $this->last_name = core_user::sanitize(ucfirst($last_name));
            $this->_updateQuery[] = 'last_name="' . core_db::escape($this->last_name) . '"';
        }
        if ($middle_name !== NULL && $middle_name != $this->middle_name) {
            mth_log::log($this, mth_log::FIELD_MIDDLE_NAME, core_user::sanitize(ucfirst($middle_name)), $this->middle_name);
            $this->middle_name = core_user::sanitize(ucfirst($middle_name));
            $this->_updateQuery[] = 'middle_name="' . core_db::escape($this->middle_name) . '"';
        }
        if ($first_name && empty($preferred_first_name) && empty($this->preferred_first_name)) {
            $preferred_first_name = $first_name;
        }
        if ($preferred_first_name !== NULL && $preferred_first_name != $this->preferred_first_name) {
            mth_log::log($this, mth_log::FIELD_PREFERRED_FIRST, core_user::sanitize(ucfirst($preferred_first_name)), $this->preferred_first_name);
            $this->preferred_first_name = core_user::sanitize(ucfirst($preferred_first_name));
            $this->_updateQuery[] = 'preferred_first_name="' . core_db::escape($this->preferred_first_name) . '"';
        }
        if ($last_name && empty($preferred_last_name) && empty($this->preferred_last_name)) {
            $preferred_last_name = $last_name;
        }
        if ($preferred_last_name !== NULL && $preferred_last_name != $this->preferred_last_name) {
            mth_log::log($this, mth_log::FIELD_PREFERRED_LAST, core_user::sanitize(ucfirst($preferred_last_name)), $this->preferred_last_name);
            $this->preferred_last_name = core_user::sanitize(ucfirst($preferred_last_name));
            $this->_updateQuery[] = 'preferred_last_name="' . core_db::escape($this->preferred_last_name) . '"';
        }

        if ($this->user_id && ($user = core_user::getUserById($this->user_id))) {
            $user->changeName($this->getPreferredFirstName(), $this->getPreferredLastName());
        }
        return true;
    }

    public function setGender($gender)
    {
        if ($gender == $this->gender) {
            return true;
        }
        $this->gender = strtolower($gender) == strtolower(self::GEN_MALE) ? self::GEN_MALE : self::GEN_FEMALE;
        $this->_updateQuery[] = 'gender="' . $this->gender . '"';
        return true;
    }

    public function setEmail($email)
    {
        $email = strtolower(trim($email));
        if ($email == $this->email) {
            return true;
        }
        if (!self::validateEmailAddress($email)) {
            return FALSE;
        }
        if ($this->user_id
            && ($user = core_user::getUserById($this->user_id))
            && !$user->changeEmail($email)
        ) {
            return false;
        }
        mth_log::log($this, mth_log::FIELD_EMAIL, $email, $this->email);
        $this->email = $email;

        $db = new core_db();
        $this->_updateQuery[] = 'email="' . $db->escape_string($this->email) . '"';
        if (($canvas_user = mth_canvas_user::get($this))) {
            $this->error_updating_canvas_login_email = !($canvas_user->update_canvas_login_email() && $canvas_user->update_canvas_default_email());
        }else{
            error_log('Unable to get canvas_user for '.$this->email);
        }
        $this->saveChanges();
        return TRUE;
    }

    public function errorUpdatingCanvasLoginEmail()
    {
        return !empty($this->error_updating_canvas_login_email);
    }

    public static function validateEmailAddress($email)
    {
        if (!core_user::validateEmail($email)) {
            return false;
        }
        if (core_user::findUser($email)) {
            return false;
        }
        if (self::getPeople(array('email' => $email))) {
            return false;
        }
        return TRUE;
    }

    /**
     *
     * @param int $date_of_birth
     * @return boolean
     */
    public function setDateOfBirth($date_of_birth)
    {
        $thisDOB = date('Y-m-d', $date_of_birth);
        if ($thisDOB == $this->date_of_birth) {
            return true;
        }
        if (!self::validateDOB($date_of_birth)) {
            return false;
        }
        $this->date_of_birth = $thisDOB;
        $this->_updateQuery[] = 'date_of_birth="' . $this->date_of_birth . '"';
        return true;
    }

    public function __destruct()
    {
        $this->saveChanges();
    }

    public function saveChanges()
    {
        if (empty($this->_updateQuery)) {
            return true;
        }
        $success = core_db::runQuery('UPDATE mth_person
                                  SET ' . implode(',', $this->_updateQuery) . '
                                  WHERE person_id=' . $this->getPersonID());
        $this->_updateQuery = array();
        return $success;
    }

    public static function validateDOB($date_of_birth)
    {
        if (!is_int($date_of_birth)) {
            return false;
        }
        return strtotime('-100 years') < $date_of_birth && strtotime('-1 years') > $date_of_birth;
    }

    public function delete()
    {
        return core_db::runQuery('DELETE FROM mth_person WHERE person_id=' . $this->getPersonID());
    }

    /**
     *
     * @return mth_address
     */
    public function getAddress()
    {
        if (!mth_parent::isParent($this->person_id)) {
            $student = mth_student::getByPersonID($this->person_id);
            return $student->getAddress();
        }
        return mth_address::getPersonAddress($this);
    }



    public function getCity()
    {
        if (($address = $this->getAddress())) {
            return $address->getCity();
        }
        return '';
    }

    /**
     *
     * @return array|string array of mth_phone objects or string
     */
    public function getPhoneNumbers($returnString = false, $html = true, $nocache = false)
    {
        $phoneNumbers = mth_phone::getPersonPhones($this, $nocache);
        if (!$returnString) {
            return $phoneNumbers;
        }
        $string = implode("\n", $phoneNumbers);
        if ($html) {
            $string = nl2br($string);
        }
        return $string;
    }

    /**
     *
     * @param string $name
     * @return mth_phone
     */
    public function getPhone($name = 'Home', $nocache = false)
    {
        $phoneNumber = $this->getPhoneNumbers(false, true, $nocache);
        if (empty($phoneNumber)) {
            return false;
        }
        foreach ($phoneNumber as $phone) {
            /* @var $phone mth_phone */
            if ($phone->getName() == $name) {
                return $phone;
            }
        }
        foreach ($phoneNumber as $phone) {
            /* @var $phone mth_phone */
            if ($phone->getName() == ($name == 'Cell' ? 'Home' : 'Cell')) {
                return $phone;
            }
        }
        return $phoneNumber[0];
    }

    public function getType()
    {
        if (mth_parent::isParent($this->person_id)) {
            return 'parent';
        } elseif(mth_student::isStudent($this->person_id)) {
            return 'student';
        }else{
            return false;
        }

    }

    /**
     *
     * @return int parent_id or student_id depending on what type
     */
    public function getID()
    {
        if (mth_parent::isParent($this->person_id)) {
            return mth_parent::isParent($this->person_id);
        } else {
            return mth_student::isStudent($this->person_id);
        }
    }

    /**
     *
     * @return core_user
     */
    public function user()
    {
        if ($this->user_id) {
            return core_user::getUserById($this->user_id);
        }
    }

    public function getUserID(){
        return $this->user_id;
    }

    public function userAccount(){
        if ($this->user_id) {
            return core_user::getUserById($this->user_id);
        }
        return false;
    }

    /**
     * GEt Person by user_id
     * @param int $user_id
     * @param boolean $reset
     */
    public static function hasPerson($user_id,$reset = false){
        
        $result = &self::$cache['getByUser-' . $user_id];

        if (!isset($result)) {
           
            $result = core_db::runQuery(
                'SELECT * from mth_person where user_id='.$user_id
            );
           
        }
        if (!$result) {
            return NULL;
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }
        if (($person = $result->fetch_object('mth_person'))) {
            return $person;
        }
        return $result;
    }

    /**
     * Get Person object by user id
     * @param int $user_id
     * @return mth_person
     */
    public static function getByUserId($user_id){
        $result = &self::$cache['getByUser-' . $user_id];
        if (!isset($result)) {
            $result = core_db::runGetObject(
                'SELECT * from mth_person where user_id='.$user_id,
                'mth_person'
            );
        }
        return $result;
    }
}
