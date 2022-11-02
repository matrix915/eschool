<?php

/**
 * keep track of changes made to person information
 *
 * @author abe
 */
class mth_log
{
    protected $log_id;
    protected $person_id;
    protected $field;
    protected $new_value;
    protected $old_value;
    protected $field_id;
    protected $changed_by_user_id;
    protected $date;
    protected $notified;

    const FIELD_FIRST_NAME = 'first_name';
    const FIELD_MIDDLE_NAME = 'middle_name';
    const FIELD_LAST_NAME = 'last_name';
    const FIELD_PREFERRED_FIRST = 'preferred_first_name';
    const FIELD_PREFERRED_LAST = 'preferred_last_name';
    const FIELD_EMAIL = 'email';
    const FIELD_PHONE = 'phone';
    const FIELD_ADDRESS = 'address';

    public static function getAvailableFields()
    {
        return array(
            self::FIELD_FIRST_NAME,
            self::FIELD_MIDDLE_NAME,
            self::FIELD_LAST_NAME,
            self::FIELD_PREFERRED_FIRST,
            self::FIELD_PREFERRED_LAST,
            self::FIELD_EMAIL,
            self::FIELD_PHONE,
            self::FIELD_ADDRESS
        );
    }

    public static function log(mth_person $person, $field, $new_value, $old_value, $field_id = null)
    {
        if (empty($old_value) || $old_value == 'UNKNOWN') {
            return true;
        }
        if (!in_array($field, self::getAvailableFields())) {
            error_log('No such field: ' . $field);
            return FALSE;
        }
        if (core_db::runQuery(sprintf('INSERT INTO mth_log 
                      (`person_id`, `field`, `new_value`, `old_value`, `field_id`, `changed_by_user_id`)
                      VALUES
                      (%d, "%s", "%s", "%s", %d, %d)',
            $person->getPersonID(),
            $field,
            core_db::escape(strip_tags($new_value)),
            core_db::escape(strip_tags($old_value)),
            $field_id,
            core_user::getUserID()
        ))
        ) {
            return self::getLogItem(core_db::getInsertID());
        }
        return false;
    }

    public static function getLogItem($log_id)
    {
        return core_db::runGetObject('SELECT * FROM mth_log WHERE log_id=' . (int)$log_id, 'mth_log');
    }

    public static function getLog($unnotified = true, $startdate = NULL, $orderByPerson = true)
    {
        return core_db::runGetObjects('SELECT * FROM mth_log 
                                    WHERE 1
                                      ' . ($unnotified ? 'AND notified=0' : '') . '
                                      ' . ($startdate !== NULL ? 'AND `date`>=' . date('Y-m-d H:i:s', $startdate) : '') . '
                                    ORDER BY ' . ($orderByPerson ? 'person_id, ' : '') . 'date DESC',
            'mth_log');
    }

    public function getField($nice = true)
    {
        if ($nice) {
            return ucwords(str_replace('_', ' ', $this->field));
        }
        return $this->field;
    }

    public function getOldValue($html = true)
    {
        if ($html && $this->field == self::FIELD_ADDRESS) {
            return nl2br($this->old_value);
        }
        return $this->old_value;
    }

    public function getNewValue($html = true)
    {
        if ($html && $this->field == self::FIELD_ADDRESS) {
            return nl2br($this->new_value);
        }
        return $this->new_value;
    }

    public function getDate()
    {
        return strtotime($this->date);
    }

    public function getPersonID()
    {
        return (int)$this->person_id;
    }

    public function getPerson()
    {
        return mth_person::getPerson($this->person_id);
    }

    public function setNotified($notified = true)
    {
        return core_db::runQuery('UPDATE mth_log SET `notified`=' . (int)(bool)$notified . ' WHERE log_id=' . $this->getID());
    }

    public function getID()
    {
        return (int)$this->log_id;
    }

    public function isNew()
    {
        return empty($this->old_value);
    }

    public function getChangedByUserID()
    {
        return (int)$this->changed_by_user_id;
    }

    public function getChangedByUserName()
    {
        if (($person = mth_parent::getByUserID($this->changed_by_user_id))) {
            return $person->getName();
        } elseif (($user = core_user::getUserById($this->changed_by_user_id))) {
            return $user->getEmail();
        }
        return false;
    }
}
