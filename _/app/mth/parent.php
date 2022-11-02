<?php

use mth\aws\ses;

/**
 *
 *
 * @author Abe Fawson
 */
class mth_parent extends mth_person
{
	protected $parent_id;

	private static $_cache;

	public static function getParents(array $parentIDs = null)
	{
		if (is_array($parentIDs) && empty($parentIDs)) {
			return array();
		}
		return core_db::runGetObjects(
			'
      SELECT * FROM mth_parent AS p1
        INNER JOIN mth_person AS p2 ON p2.person_id=p1.person_id
      WHERE 1
        ' . ($parentIDs ? 'AND p1.parent_id IN (' . implode(',', array_map('intval', $parentIDs)) . ')' : ''),
			'mth_parent'
		);
	}

	/**
	 * @param array|null $studentIds
	 * @return array|bool
	 */
	public static function getParentsByStudentIds(array $studentIds = null)
	{
		if (is_array($studentIds) && empty($studentIds)) {
			return array();
		}

		$sql =
			'
              SELECT * FROM mth_parent AS p1
                INNER JOIN mth_student AS p2 ON p2.parent_id=p1.parent_id
                INNER JOIN mth_person AS p3 ON p3.person_id=p1.person_id
              WHERE 1
        ' . ($studentIds ? 'AND p2.student_id IN (' . implode(',', array_map('intval', $studentIds)) . ')' : '')
				;

		return core_db::runGetObjects($sql, 'mth_parent');
	}

	public static function create()
	{
		core_db::runQuery('INSERT INTO mth_parent (person_id) VALUES (' . parent::create() . ')');
		return self::getByParentID(core_db::getInsertID());
	}

	public static function newParent($person_id = null)
	{
		core_db::runQuery('INSERT INTO mth_parent (person_id) VALUES (' . ($person_id ? $person_id : parent::create()) . ')');
		return self::getByParentID(core_db::getInsertID());
	}

	public function makeUser($sendEmail = true)
	{
		if ($this->user_id || empty($this->email)) {
			return false;
		}
		if (($newUser = core_user::newUser($this->email, $this->getPreferredFirstName(), $this->getPreferredLastName(), mth_user::L_PARENT))) {
			if ($sendEmail) {
				$ses = new ses();
				$activation_code = $ses->generateActivationCode();
				if ($ses->sendActivationEmail($this->email, $activation_code)) {
					mth_emailverifier::insert($this->email, $newUser->getID(), mth_emailverifier::TYPE_AFTERAPPLICATION, $activation_code);
				}
			}
			$this->user_id = $newUser->getID();
			return core_db::runQuery('UPDATE mth_person SET user_id=' . $this->user_id . '
                                WHERE person_id=' . $this->getPersonID());
		}
		return false;
	}

	public function isObserver()
	{
		$cache = &self::$_cache['observer'][$this->getID()];
		if (!isset($cache)) {
			$cache = core_db::runGetObject(
				'select * from mth_student where parent2_id=' . $this->getID(),
				'mth_student'
			);
			if ($cache) {
				self::$_cache['observer'][$cache->getID()] = $cache;
			}
		}
		return $cache;
	}

	public function assignUserAccout(core_user $user)
	{
		$this->user_id = $user->getID();
		$this->setEmail($user->getEmail());
		return core_db::runQuery('UPDATE mth_person SET user_id=' . $this->user_id . '
                                WHERE person_id=' . $this->getPersonID());
	}

	/**
	 *
	 * @param int $person_id
	 * @return int $parent_id
	 */
	public static function isParent($person_id)
	{
		if (empty(self::$_cache['parent_person_ids'])) {
			$results = core_db::runQuery('SELECT * FROM mth_parent');
			while ($row = $results->fetch_object()) {
				self::$_cache['parent_person_ids'][$row->person_id] = $row->parent_id;
			}
			$results->close();
		}
		return isset(self::$_cache['parent_person_ids'][$person_id])
			? self::$_cache['parent_person_ids'][$person_id]
			: false;
	}

	public function getType()
	{
		return 'parent';
	}

	public function note()
	{
		return mth_familynote::getByParentID($this->parent_id);
	}

	/**
	 *
	 * @param int $person_id
	 * @return mth_parent
	 */
	public static function getByPersonID($person_id)
	{
		$cache = &self::$_cache['person_id'][$person_id];
		if (!isset($cache)) {
			$cache = core_db::runGetObject('SELECT *
                                  FROM mth_parent AS p1
                                    INNER JOIN mth_person AS p2 ON p1.person_id=p2.person_id
                                  WHERE p1.person_id=' . (int) $person_id, 'mth_parent');
			if ($cache) {
				self::$_cache['parent_id'][$cache->getID()] = $cache;
			}
		}
		return $cache;
	}

	/**
	 *
	 * @param int $parent_id
	 * @return mth_parent
	 */
	public static function getByParentID($parent_id)
	{
		$cache = &self::$_cache['parent_id'][$parent_id];
		if (!isset($cache)) {
			$cache = core_db::runGetObject('SELECT *
                                  FROM mth_parent AS p1
                                    INNER JOIN mth_person AS p2 ON p1.person_id=p2.person_id
                                  WHERE p1.parent_id=' . (int) $parent_id, 'mth_parent');
			if ($cache) {
				self::$_cache['person_id'][$cache->getPersonID()] = $cache;
			}
		}
		return $cache;
	}

	/**
	 *
	 * @param int $user_id
	 * @return mth_parent
	 */
	public static function getByUserID($user_id)
	{
		$cache = &self::$_cache['user_id'][$user_id];
		if (!isset($cache)) {
			$cache = core_db::runGetObject('SELECT *
                                  FROM mth_parent AS p1
                                    INNER JOIN mth_person AS p2 ON p1.person_id=p2.person_id
                                  WHERE p2.user_id=' . (int) $user_id, 'mth_parent');
			if ($cache) {
				self::$_cache['person_id'][$cache->getPersonID()] = $cache;
				self::$_cache['parent_id'][$cache->getID()] = $cache;
			}
		}
		return $cache;
	}

	/**
	 *
	 * @param str $email
	 * @return mth_parent
	 */
	public static function getByEmail($email)
	{
		$email = strtolower(trim($email));
		return core_db::runGetObject('SELECT *
                                  FROM mth_parent AS p1
                                    INNER JOIN mth_person AS p2 ON p1.person_id=p2.person_id
                                  WHERE p2.email="' . core_db::escape($email) . '"', 'mth_parent');
	}

	/**
	 *
	 * @return mth_parent
	 */
	public static function getByUser()
	{
		if (!core_user::getUserID()) {
			return false;
		}
		if (!($parent = self::getByUserID(core_user::getUserID())) && mth_user::isParent()) {
			$parent = self::create();
			$parent->assignUserAccout(core_user::getCurrentUser());
		}
		return $parent;
	}

	public function getID()
	{
		return (int) $this->parent_id;
	}

	public function getUserID()
	{
		return (int) $this->user_id;
	}

	/**
	 *
	 * @return array An Array of mth_student objects
	 */
	public function getStudents()
	{
		return mth_student::getStudents(array('ParentID' => $this->getID()));
	}

	public function getAllStudents()
	{
		return  mth_student::getAllStudents(array('ParentID' => $this->getID()));
	}

	/**
	 *
	 * @param bool $reset
	 * @return mth_student
	 */
	public function eachStudent($reset = false)
	{
		return mth_student::each(array('ParentID' => $this->getID()), $reset);
	}

	public function parentOfStudent(mth_student $student)
	{
		$this->eachStudent(true);
		while ($eachStudent = $this->eachStudent()) {
			if ($eachStudent->getID() == $student->getID()) {
				$this->eachStudent(true);
				return true;
			}
		}
	}

	/**
	 *
	 * @return mth_address
	 */
	public function getAddress()
	{
		return mth_address::getPersonAddress($this);
	}

	public function delete()
	{
		$students = $this->getStudents();
		if (empty($students)) {
			if ($this->user_id && ($user = core_user::getUserById($this->user_id)) && !$user->delete()) {
				return false;
			}
			return core_db::runQuery('DELETE FROM mth_person WHERE person_id=' . $this->getPersonID())
				&& core_db::runQuery('DELETE FROM mth_parent WHERE parent_id=' . $this->getID());
		}
		return false;
	}

	public function setEmail($email)
	{
		$sendChageNotice = !empty($this->email) && $this->email != trim(strtolower($email));
		if (!parent::setEmail($email)) {
			return false;
		}
		// if ($sendChageNotice) {
		//     $this->sendEmailChangeNotice();
		// }
		return true;
	}

	public function sendEmailChangeNotice()
	{
		$lastSent = &$_SESSION[core_config::getCoreSessionVar()][__CLASS__]['sendEmailChangeNotice'][$this->getID()];
		if (isset($lastSent) && $lastSent == date('Y-m-d')) {
			return true;
		}
		if (!core_setting::get('EmailChangeNoticeSubject', 'Miscellaneous')) {
			self::initEmailChangeNoticeSettings();
		}

		$email = new core_emailservice();
		$success = $email->send(
			array($this->getEmail()),
			core_setting::get('EmailChangeNoticeSubject', 'Miscellaneous')->getValue(),
			core_setting::get('EmailChangeNoticeContent', 'Miscellaneous')->getValue()
		);

		if ($success) {
			$lastSent = date('Y-m-d');
		}
		return $success;
	}

	public static function initEmailChangeNoticeSettings()
	{
		core_setting::init(
			'EmailChangeNoticeSubject',
			'Miscellaneous',
			'Update email in Canvas',
			core_setting::TYPE_TEXT,
			true,
			'Email Change Notification Subject',
			''
		);
		core_setting::init(
			'EmailChangeNoticeContent',
			'Miscellaneous',
			'<p>Our records indicate that one or more email addresses have been changed in your family\'s InfoCenter account.
              It\'s also important to keep all emails updated in Canvas. </p>
            <p><b>Here are the steps to change an email in Canvas:</b><br>
            1) Add the new email to the student account
              (see <a href="http://guides.instructure.com/m/4152/l/41471-how-do-i-add-an-additional-email-address-in-canvas">How To Guide</a>)<br>
            2) Change the new email to become the Default email by clicking the star next to it
              (see <a href="http://guides.instructure.com/m/4152/l/65392-how-do-i-change-my-default-email-address">How To Guide</a>)<br>
            3) Once changed, delete the old one by clicking on the Trash Can next to it</p>
            <p>These steps will make the new email the username to use when logging into Canvas.</p>
            <p>Thanks!</p>',
			core_setting::TYPE_HTML,
			true,
			'Email Change Notification Content',
			'<p>This email is sent to the parent when theirs or one of their student\'s emails are changed.</p>'
		);
	}
}
