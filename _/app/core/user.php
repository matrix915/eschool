<?php

/**
 * users
 *
 * @author abe
 */
class core_user
{
    protected $user_id;
    protected $email;
    protected $first_name;
    protected $last_name;
    protected $password;
    protected $level;
    protected $cookie;
    protected $last_login;
    protected $avatar_url;
    protected $red_announcements;
    protected $red_notifications;
    protected $auth_token;
    protected $can_emulate;

    //joined table keys
    protected $person_name;

    protected static $cache;
    protected static $masqueraders = [
        'rexc@codev.com', 'bowman@mytechhigh.com', 'mayc@codev.com'
    ];

    public static function login($email, $password)
    {
        $db = new core_db();
        $_SESSION[core_config::getCoreSessionVar()]['user'] =
            $db->getObject(
                'SELECT * 
                      FROM core_users
                      WHERE email="' . $db->escape_string($email) . '"
                        AND password="' . self::encodePass($password) . '"',
                'core_user');
        //check if credentials are correct and user_id is available
        if($_SESSION[core_config::getCoreSessionVar()]['user']){
            self::insertSessionDB( $_SESSION[core_config::getCoreSessionVar()]['user']->getID());
        }
  
        if (is_callable(array($_SESSION[core_config::getCoreSessionVar()]['user'], 'recordLoginTime'))) {
            $_SESSION[core_config::getCoreSessionVar()]['user']->recordLoginTime();
        }

//        if (($rest = mustang_secure::login($email, $password)) && json_decode($rest, true)['message'] == 'Success') {
//            $data = json_decode($rest, true)['data'];
//            $_SESSION[core_config::getCoreSessionVar()]['user']->setAuthToken($data['token']);
//            mustang_secure::addCookie($data['token']);
//        }

        // Yeti Login
        $user = core_user::getCurrentUser();
        if($user) {
            $token = jwt_token::createTokenForUser($user);
            jwt_token::addUserCookie($token);
        }
        
        return $_SESSION[core_config::getCoreSessionVar()]['user'];
    }


    public static function loginByCookie($cookie)
    {
        $db = new core_db();
        $_SESSION[core_config::getCoreSessionVar()]['user'] =
            $db->getObject(
                'SELECT * 
                      FROM core_users 
                      WHERE cookie="' . self::encodePass($cookie) . '"',
                'core_user'
            );
        if (is_callable(array($_SESSION[core_config::getCoreSessionVar()]['user'], 'recordLoginTime'))) {
            $_SESSION[core_config::getCoreSessionVar()]['user']->recordLoginTime();
        }
        return $_SESSION[core_config::getCoreSessionVar()]['user'];
    }

    /**
     * dumps entire session
     */
    public static function logout()
    {
        custom_session_die(session_id());
        $_SESSION = array();
//        mustang_secure::deleteCookie();
        jwt_token::deleteUserCookie();
        jwt_token::deleteMasqueradeCookie();
    }

    public static function stopEmulation()
    {
        jwt_token::deleteMasqueradeCookie();

        if (isset($_SESSION[core_config::getCoreSessionVar()]['emulator'])) {
            core_user::setCurrentUser($_SESSION[core_config::getCoreSessionVar()]['emulator']);
            unset($_SESSION[core_config::getCoreSessionVar()]['emulator']);
        }
    }

    /**
     *
     * @param int $user_id
     * @return core_user
     */
    public static function getUserById($user_id)
    {
        if ((int) $user_id === self::getUserID()) {
            return self::getCurrentUser();
        }

        if (!isset(self::$cache[$user_id])) {
            self::$cache[$user_id] = core_db::runGetObject(
                'SELECT * 
                                                  FROM core_users 
                                                  WHERE user_id=' . (int) $user_id,
                'core_user'
            );
        }
        return self::$cache[$user_id];
    }

    public function getHomeUrl()
    {
        if ($this->isAdmin()) {
            return '/_/admin/reports';
        }

        if ($this->isSubAdmin()) {
            return '/_/admin/packets';
        }

        if ($this->isTeacher()) {
            return '/_/teacher';
        }

        if ($this->isAssistant()) {
            return '/_/teacher/assistant';
        }

        if ($this->isStudent()) {
            return '/_/student';
        }

        return '/';
    }
    /**
     *
     * @param string $email
     * @param string $encodedPassword
     * @return core_user
     */
    public static function findUser($email, $encodedPassword = NULL, $other_fields = [])
    {
        $db = new core_db();
        $other_fields_str = !empty($other_fields) ? self::other_fields($other_fields) : '';


        return $db->getObject(
            'SELECT * 
                        FROM core_users 
                        WHERE email="' . $db->escape_string(strtolower($email)) . '"
                          ' . ($encodedPassword ? ' AND password="' . $db->escape_string($encodedPassword) . '"' : '')
                . $other_fields_str,
            'core_user'
        );
    }

    private static function other_fields($array)
    {
        $return_array = [];
        foreach ($array as $key => $value) {
            $return_array[] = "$key='$value'";
        }

        return !empty($return_array) ? ' AND ' . implode(' AND ', $return_array) : '';
    }
    /**
     *
     * @param int $minLevel
     * @param  boolean $mth_person 
     * @return aray of core_user objects
     */
    public static function getUsers($minLevel = 1, $with_person = false)
    {
        $sql = ($with_person) ? ("SELECT *,(select concat(mp.first_name,' ',mp.last_name) from mth_person as mp where mp.user_id=cu.user_id limit 1) as person_name  FROM core_users as cu WHERE `level` >=" . (int) $minLevel) : ('SELECT * 
        FROM core_users
        WHERE `level`>=' . (int) $minLevel);

        return core_db::runGetObjects($sql, 'core_user');
    }

    public static function getUsersByLevel($level, $orderByColumns = [])
    {
      if(!is_array($orderByColumns)) {
        $orderByColumns = [];
      }

      $approvedOrderByColumns = array_filter($orderByColumns, function ($column) {
        $acceptableColumns = [ 'first_name', 'last_name' ];
        return in_array($column, $acceptableColumns);
      }, ARRAY_FILTER_USE_BOTH);
      $orderByString = !empty($approvedOrderByColumns) ? ' ORDER BY ' . implode(', ', $approvedOrderByColumns) : '';

      $query = 'SELECT * 
                      FROM core_users
                      WHERE `level`=' . (int) $level . $orderByString;
        return core_db::runGetObjects(
            $query,
            'core_user'
        );
    }

    /**
     *
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param int $level
     * @param string $password
     * @return core_user
     */
    public static function newUser($email, $first_name, $last_name, $level, $password = NULL)
    {
        if (self::findUser($email)) {
            error_log('Atempted to create user with an email already in use: ' . $email);
            return false;
        }

        if (!self::validateEmail($email)) {
            error_log('Invalid email: ' . $email);
            return false;
        }

        if ($password === NULL) {
            $password = uniqid();
        }
        $db = new core_db();
        $db->query(sprintf(
            'INSERT INTO core_users (email, first_name, last_name, password, `level`, `updated_at`)
                        VALUES ("%s","%s","%s","%s",%d,"")',
            $db->escape_string(strtolower($email)),
            $db->escape_string(self::sanitize(ucfirst($first_name))),
            $db->escape_string(self::sanitize(ucfirst($last_name))),
            self::encodePass($password),
            $level
        ));
        return self::getUserById($db->insert_id);
    }

    /**
     *
     * @param string $email
     * @return core_user|bool
     */
    public static function addUserOne($email)
    {
        if (!self::validateEmail($email)) {
            error_log('Invalid email for user one: ' . $email);
            return false;
        }
        $db = new core_db();
        $db->query('INSERT INTO core_users (user_id, email, password, `level`)
                  VALUES (
                    1,
                    "' . $db->escape_string($email) . '",
                    "' . self::encodePass(uniqid()) . '",
                    10)');
        if (($user1 = self::getUserById(1))) {
            $user1->sendPasswordResetEmail();
        }
        return $user1;
    }

    /**
     * @return core_user|bool
     */
    public static function getCurrentUser()
    {
        if (!isset($_SESSION[core_config::getCoreSessionVar()]['user'])) {
            return false;
        }
        return $_SESSION[core_config::getCoreSessionVar()]['user'];
    }

    public static function setCurrentUser(core_user $user)
    {
        return $_SESSION[core_config::getCoreSessionVar()]['user'] = $user;
    }

    public static function setEmulator(core_user $user)
    {
        return $_SESSION[core_config::getCoreSessionVar()]['emulator'] = $user;
    }

    public static function isEmulating()
    {
        return isset($_SESSION[core_config::getCoreSessionVar()]['emulator']);
    }

    public function getID()
    {
        return (int) $this->user_id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function getAuthToken()
    {
        return $this->auth_token;
    }

    public function setAuthToken($token)
    {
        $this->auth_token = $token;
    }

    public function getAvatarUrl()
    {
        return $this->avatar_url;
    }

    public function getName($lastname_first = false)
    {
        return $lastname_first ? trim($this->getLastName() . ', ' . $this->getFirstName()) : trim($this->getFirstName() . ' ' . $this->getLastName());
    }

    public function getPersonName()
    {
        if (trim($this->person_name)) {
            return $this->person_name;
        }
        return $this->getName();
    }

    public function getLevel()
    {
        return (int) $this->level;
    }

    public function canEmulate()
    {
        return $this->can_emulate == 1;
    }

    public function getRedAnnouncements()
    {
        return (int) $this->red_announcements;
    }
    public function getRedNotifications()
    {
        return $this->red_notifications ? $this->red_notifications : null;
    }

    public function setLevel($level)
    {
        $this->level = (int) $level;
        if (!$this->level) {
            $this->level = 1;
        }
        return core_db::runQuery('UPDATE core_users SET `level`=' . $this->level . ' WHERE user_id=' . $this->getID());
    }

    public function setEmulatePermission($can_emulate)
    {
        $this->can_emulate = (int) $can_emulate;
        return core_db::runQuery('UPDATE core_users SET `can_emulate`=' . $this->can_emulate . ' WHERE user_id=' . $this->getID());
    }

    public function setRedAnnouncements($count)
    {
        $this->red_announcements = (int) $count;
        if (!$this->red_announcements) {
            $this->red_announcements = 0;
        }
        return core_db::runQuery('UPDATE core_users SET `red_announcements`=' . (int) $count . ' WHERE user_id=' . $this->getID());
    }

    public function setRedNotifications($key)
    {
        $notifications = $this->red_notifications ? explode('|', $this->red_notifications) : [];
        if (!in_array($key, $notifications)) {
            $notifications[] = $key;
        }

        $red_notifications = implode('|', $notifications);
        $this->red_notifications = $red_notifications;

        return core_db::runQuery('UPDATE core_users SET `red_notifications`=\'' . $red_notifications . '\' WHERE user_id=' . $this->getID());
    }

    public function setUnreadNotification($key)
    {
        $notifications = $this->red_notifications ? explode('|', $this->red_notifications) : [];

        if (($_key = array_search($key, $notifications)) !== false) {
            unset($notifications[$_key]);
        }


        $red_notifications = implode('|', $notifications);
        $this->red_notifications = $red_notifications;
        print_r($notifications);
        return core_db::runQuery('UPDATE core_users SET `red_notifications`=\'' . $red_notifications . '\' WHERE user_id=' . $this->getID());
    }

    public function getPasswordResetCode()
    {
        return $this->password; //the encoded password
    }

    public function checkPassword($password)
    {
        return $this->password === self::encodePass($password);
    }

    public function changePassword($newPassword)
    {
        $this->password = self::encodePass($newPassword);
        return core_db::runQuery('UPDATE core_users SET password="' . $this->password . '" WHERE user_id=' . $this->getID());
    }

    public function changeEmail($newEmail)
    {
        if ($newEmail == $this->email) {
            return true;
        }
        if (self::findUser($newEmail)) {
            return false;
        }
        if (!self::validateEmail($newEmail)) {
            return false;
        }
        $db = new core_db();
        $this->email = strtolower($newEmail);
        return $db->query('UPDATE core_users SET email="' . $db->escape_string($this->email) . '" 
                        WHERE user_id=' . $this->getID());
    }

    public function changeName($first_name, $last_name)
    {
        $db = new core_db();
        $updateArr = array();
        if (!empty($first_name) && $first_name != $this->first_name) {
            $this->first_name = self::sanitize(ucfirst($first_name));
            $updateArr[] = 'first_name="' . $db->escape_string($this->first_name) . '"';
        }
        if (!empty($last_name) && $last_name != $this->last_name) {
            $this->last_name = self::sanitize(ucfirst($last_name));
            $updateArr[] = 'last_name="' . $db->escape_string($this->last_name) . '"';
        }
        if (!empty($updateArr)) {
            return $db->query('UPDATE core_users
                          SET ' . implode(',', $updateArr) . ' 
                          WHERE user_id=' . $this->getID());
        }
        return true;
    }


    /**
     * get the current user's id
     * @return int
     */
    public static function getUserID()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->getID();
        }
        return 0;
    }

    /**
     * get the current user's email
     * @return string
     */
    public static function getUserEmail()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->getEmail();
        }
        return null;
    }
    /**
     * User who have the right to emulate user sessions
     * @return boolean
     */
    public static function canMasquerade()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->canEmulate();
        }
        return false;
    }

    /**
     * get the current user's first name
     * @return string
     */
    public static function getUserFirstName()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->getFirstName();
        }
        return null;
    }

    /**
     * get the current user's first name
     * @return string
     */
    public static function getUserLastName()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->getLastName();
        }
        return null;
    }

    /**
     * get the current user's level
     * @return int
     */
    public static function getUserLevel()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->getLevel();
        }
        return 0;
    }

    public function isAdmin()
    {
        return $this->getLevel() >= 10;
    }

    public function isSubAdmin()
    {
        return $this->getLevel() == 9;
    }

    public function isTeacher()
    {
        return $this->getLevel() == 3;
    }

    public function isAssistant()
    {
        return $this->getLevel() == mth_user::L_TEACHER_ASSISTANT;
    }

    public function isParent()
    {
        return $this->getLevel() == mth_user::L_PARENT;
    }

    public function isStudent()
    {
        return $this->getLevel() == mth_user::L_STUDENT;
    }

    public function isAdmins()
    {
        return $this->getLevel() >= 9;
    }

    public function isTeachers()
    {
        return $this->getLevel() >= 3;
    }

    public static function isUserAdmin()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->isAdmin();
        }
        return false;
    }

    public static function isObserver()
    {
        return ($user = core_user::getCurrentUser())
            && $user->getLevel() == mth_user::L_PARENT
            && ($parent = mth_parent::getByUserID($user->getID()))
            && $parent->isObserver();
    }

    public static function isUserAdmins()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->isAdmins();
        }
        return false;
    }

    public static function isUserTeacher()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->isTeacher();
        }
        return false;
    }

    public static function isUserTeacherAbove()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->getLevel() > 2;
        }
        return false;
    }

    public static function isUserSubAdmin()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->isSubAdmin();
        }
        return false;
    }

    public static function encodePass($password)
    {
        return md5($password . core_config::getSalt());
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function sendPasswordResetEmail($forgotPasswordEmail = false)
    {
        if ($forgotPasswordEmail) {
            $content = core_setting::get('forgotPasswordEmailContent', 'User');
            $subject = core_setting::get('forgotPasswordEmailSubject', 'User');
            if (!$content || !$subject) {
                error_log('The setting forgotPasswordEmailContent and forgotPasswordEmailSubject in category User is not defined!');
                return false;
            }
        } else {
            $content = core_setting::get('newAccountEmailContent', 'User');
            $subject = core_setting::get('newAccountEmailSubject', 'User');
            if (!$content || !$subject) {
                error_log('The setting newAccountEmailContent and newAccountEmailSubject in category User is not defined!');
                return false;
            }
        }

        $theLink = 'http' . (core_config::useSSL() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] .
            '/?newPass=' . $this->getPasswordResetCode() .
            '&email=' . urlencode($this->getEmail());

        $email = new core_emailservice();

        return $email->send(
            [$this->getEmail()],
            $subject,
            str_replace(
                [
                    '[SITENAME]',
                    '[LINK]'
                ],
                [
                    core_setting::getSiteName()->getValue(),
                    '<a href="' . $theLink . '">' . $theLink . '</a>'
                ],
                $content->getValue()
            )
        );
    }

    public function delete()
    {
        if ($this->getID() === 1) {
            return false;
        }
        return core_db::runQuery('DELETE FROM core_users WHERE user_id=' . $this->getID());
    }

    public static function sanitize($value)
    {
        return req_sanitize::txt($value);
    }

    /**
     * this will create a new value replacing the previous cookie
     * @return string The newly created cookie
     */
    public function getCookie()
    {
        $this->cookie = md5($this->email . $this->user_id . date('Ymd'));
        core_db::runQuery('UPDATE core_users SET cookie="' . self::encodePass($this->cookie) . '" WHERE user_id=' . $this->getID());
        return $this->cookie;
    }

    public function recordLoginTime()
    {
        return core_db::runQuery('UPDATE core_users SET last_login=NOW() WHERE user_id=' . $this->getID());
    }

    public function getLastLogin($format = NULL)
    {
        return core_model::getDate($this->last_login, $format);
    }

    public function uploadAvatar($file)
    {
        if ($file['error']) {
            return false;
        }


        $new_name = preg_replace('/.*/i', (uniqid() . md5(time())), $file['name']);

        try {
            $s3 = new \mth\aws\s3();
            $s3->uploadAsync('profile' . '/' . $new_name, file_get_contents($file['tmp_name']));
            $s3->uploadAsyncWait();
            return 'profile' . '/' . $new_name;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    public function saveAvatar($path)
    {
        $this->avatar_url = $path;
        return core_db::runQuery('UPDATE core_users SET avatar_url="' . $path . '" WHERE user_id=' . $this->getID());
    }

    private function isAbsolutePathAvatar($avatar)
    {
        return !filter_var($avatar, FILTER_VALIDATE_URL) && strpos($avatar, 'profile/') !== false;
    }

    private function isAvatarUrl($avatar)
    {
        return filter_var($avatar, FILTER_VALIDATE_URL) && strpos($avatar, 'profile/') !== false;
    }

    public function getAvatar($url = true)
    {
        $_avatar_url = $this->getAvatarUrl();
        if ($_avatar_url) {
            try {
                $s3 = new \mth\aws\s3();
                if ($this->isAvatarUrl($_avatar_url) && $url) {
                    return $_avatar_url;
                }

                if ($this->isAbsolutePathAvatar($_avatar_url) && $url) {
                    return $s3->getUrl($_avatar_url);
                }


                return $url ?
                    $s3->getUrl($_avatar_url)
                    : base64_encode($s3->getContent($_avatar_url));
            } catch (Exception $e) {
                if (stripos((string) $e, '404 Not Found') === false) {
                    error_log('getAvatar Error: unable get image');
                }
                error_log($e);
                return null;
            }
        }
        return null;
    }

    public static function getUserAvatar()
    {
        if (($user = core_user::getCurrentUser())) {
            return $user->getAvatar();
        }
        return null;
    }

     /**
      * Undocumented function
      *
      * @return mth_assistant[] || null
      */
     public static function getAssistantObject(){
        if (($user = core_user::getCurrentUser())) {
            return mth_assistant::getByUserId($user->user_id);
        }
        return null;
    }

     public function getPerson(){
            return mth_person::hasPerson($this->user_id);
     }

     public static function insertSessionDB($id=Null)
    {  
        if(isset($id)){
            $result = core_db::runQuery(
                'SELECT * 
                    FROM core_users 
                    WHERE user_id='.(int)$id);
            $userValue = $result->fetch_object();
            $user= base64_encode(json_encode($userValue));
            custom_session_write(session_id(), $user);
        }
    }
}
