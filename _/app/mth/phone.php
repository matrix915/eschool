<?php

/**
 * phone
 *
 * @author abe
 */
class mth_phone
{
    protected $phone_id;
    protected $person_id;
    protected $name;
    protected $number;
    protected $ext;

    protected $updateQuery = array();
    protected $old;
    protected static $cache;

    protected static $availableNames = array('Home', 'Work', 'Cell', 'Other');

    public static function getAvailableNames()
    {
        return self::$availableNames;
    }

    /**
     *
     * @param mth_person $person
     * @return array
     */
    public static function getPersonPhones(mth_person $person, $nocache = false)
    {
        $cache = &self::$cache['person_id'][$person->getPersonID()];
        if (!isset($cache) || $nocache) {
            $cache = core_db::runGetObjects('SELECT * FROM mth_phone WHERE person_id=' . $person->getPersonID(), 'mth_phone');
        }
        return $cache;
    }

    /**
     *
     * @param int $phone_id
     * @return mth_phone
     */
    public static function getPhone($phone_id)
    {
        $cache = &self::$cache['phone_id'][$phone_id];
        if (!isset($cache)) {
            $cache = core_db::runGetObject('SELECT * FROM mth_phone WHERE phone_id=' . (int)$phone_id, 'mth_phone');
        }
        return $cache;
    }

    public static function create(mth_person $person)
    {
        core_db::runQuery('INSERT INTO mth_phone (person_id) VALUES (' . $person->getPersonID() . ')');
        return self::getPhone(core_db::getInsertID());
    }

    public function __destruct()
    {
        $this->save();
    }

    public function save()
    {
        if (empty($this->number)) {
            $this->delete();
        }
        if (empty($this->updateQuery)) {
            return;
        }
        if (($person = mth_person::getPerson($this->person_id))) {
            mth_log::log($person, mth_log::FIELD_PHONE, $this->__toString(), $this->old, $this->getID());
        }
        return core_db::runQuery('UPDATE mth_phone 
                                SET ' . implode(',', $this->updateQuery) . ' 
                                WHERE phone_id=' . $this->getID());
    }

    public function __toString()
    {
        return $this->number . ($this->ext ? ' ext. ' . $this->ext : '');//.(!empty($this->name)?' ('.$this->name.')':'');
    }

    public function getPersonID()
    {
        return (int)$this->person_id;
    }

    public function getID()
    {
        return (int)$this->phone_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function getExt()
    {
        return $this->ext;
    }

    /**
     *
     * @param array $formFields expects fields: number, name, ext
     */
    public function saveForm(ARRAY $formFields)
    {
        $this->setNumber($formFields['number']);
        $this->setName($formFields['name']);
        if (!empty($formFields['ext'])) {
            $this->setExt($formFields['ext']);
        }
    }

    public function setNumber($number)
    {
        $number = explode(' ', self::formatNumber(self::sanitizeNumber($number)));
        if (count($number) > 1) {
            $this->setExt($number[1]);
        }
        if ($number[0] == $this->number) {
            return true;
        }
        if (is_null($this->old)) {
            $this->old = !empty($this->number) ? $this->__toString() : '';
        }
        $this->number = $number[0];
        $this->updateQuery[] = '`number`="' . $this->number . '"';
    }

    public function setName($name)
    {
        $name = cms_content::sanitizeText($name);
        if ($name == $this->name) {
            return true;
        }
        if (is_null($this->old)) {
            $this->old = !empty($this->number) ? $this->__toString() : '';
        }
        $this->name = $name;
        $this->updateQuery[] = '`name`="' . core_db::escape($this->name) . '"';
    }

    public function setExt($ext)
    {
        $ext = cms_content::sanitizeText($ext);
        if ($ext == $this->ext) {
            return true;
        }
        if (is_null($this->old)) {
            $this->old = !empty($this->number) ? $this->__toString() : '';
        }
        $this->ext = $ext;
        $this->updateQuery[] = '`ext`="' . core_db::escape($this->ext) . '"';
    }

    public static function sanitizeNumber($number)
    {
        return preg_replace('/[^0-9]/', '', $number);
    }

    public static function validateNumber($sanitizedNumber)
    {
        return strlen($sanitizedNumber) == 7 || strlen($sanitizedNumber) == 10;
    }

    public static function formatNumber($sanitizedNumber)
    {
        return trim(preg_replace('/([0-9]{3})?([0-9]{3})([0-9]{4})([0-9]*)/', '$1-$2-$3 $4', $sanitizedNumber), '-');
    }

    public function delete()
    {
        $this->updateQuery = array();
        return core_db::runQuery('DELETE FROM mth_phone WHERE phone_id=' . $this->getID());
    }

    public static function deleteOrphaned()
    {
        return core_db::runQuery('
      DELETE ph.*
      FROM mth_phone AS ph
        LEFT JOIN mth_person AS p ON ph.person_id=p.person_id
      WHERE p.person_id IS NULL');
    }
}
