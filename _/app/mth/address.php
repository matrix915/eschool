<?php

/**
 * interacts with mth_address and mth_person_address tables
 *
 * @author Abe Fawson
 */
class mth_address
{
    protected $address_id;
    protected $name;
    protected $street;
    protected $street2;
    protected $city;
    protected $state;
    protected $zip;
    protected $county;
    protected $school_district;

    protected $person_id;

    private $_formFields = array('name', 'street', 'street2', 'city', 'state', 'zip', 'county', 'school_district');
    private $_old;
    private static $_cache;

    public static function create(mth_parent $person)
    {
        if (($address = self::getPersonAddress($person))) {
            return $address;
        }
        core_db::runQuery('INSERT INTO mth_address (`name`) VALUES (NULL)');
        $address_id = core_db::getInsertID();
        core_db::runQuery('INSERT INTO mth_person_address (person_id, address_id)
                        VALUES (' . $person->getPersonID() . ',' . $address_id . ')');
        return self::getAddress($address_id);
    }

    /**
     *
     * @param int $address_id
     * @return mth_address
     */
    public static function getAddress($address_id)
    {
        if (!isset(self::$_cache['address_id'][$address_id])) {
            self::$_cache['address_id'][$address_id] = core_db::runGetObject('
                SELECT a.*, pa.person_id
                FROM mth_address AS a
                  LEFT JOIN mth_person_address AS pa ON pa.address_id=a.address_id
                WHERE a.address_id=' . (int) $address_id, 'mth_address');
        }
        return self::$_cache['address_id'][$address_id];
    }

    public static function getPersonAddress(mth_person $person)
    {
        $cache = &self::$_cache['person_id'][$person->getPersonID()];
        if (!isset($cache)) {
            $cache = self::getAddress(core_db::runGetValue('SELECT address_id
                                                      FROM mth_person_address
                                                      WHERE person_id=' . $person->getPersonID() . '
                                                      ORDER BY address_id DESC
                                                      LIMIT 1'));
        }
        return $cache;
    }

    public function getID()
    {
        return (int) $this->address_id;
    }

    public function getUserID()
    {
        return (int) $this->user_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function getStreetNum()
    {
        return explode(" ", $this->street)[0];
    }

    public function getStreetNanme($num)
    {
        return str_replace($num." ","", $this->street);
    }

    public function getStreet2()
    {
        return $this->street2;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getZip()
    {
        return $this->zip;
    }

    public function getCounty()
    {
        return $this->county;
    }

    public function getSchoolDistrictOfR()
    {
        return $this->school_district;
    }

    // UPDATE `mth_address` SET `school_district` = NULL WHERE `mth_address`.`address_id` = 4106;
    public function setSchoolDistrictOfR($re_school_district)
    {
        $this->school_district = $re_school_district;
        $updateQuery = 'school_district="' . $this->school_district . '"';
        core_db::runQuery('UPDATE mth_address SET ' . $updateQuery . '  WHERE address_id=' . $this->getID());
        return true;
    }

    public function getFull($html = true)
    {
        if (empty($this->street) && empty($this->city)) {
            return '';
        }
        $address = $this->getStreet() . "\n" . ($this->getStreet2() ? $this->getStreet2() . "\n" : '') . $this->getCity() . ', ' . $this->getState() . ' ' . $this->getZip();
        if ($html) {
            return nl2br($address);
        }
        return $address;
    }

    public function __toString()
    {
        return $this->getFull();
    }

    public static function saveAddressForm(array $postFields)
    {
        if (core_user::isUserAdmin() && isset($postFields['parent_id'])) {
            $parent = mth_parent::getByParentID($postFields['parent_id']);
        } else {
            $parent = mth_parent::getByUser();
        }
        if (!($parent)) {
            return false;
        }
        $address = self::create($parent);
        return $address->saveForm($postFields);
    }

    /**
     *
     * @param array $postFields expects fields name, street, street2, city, state, zip
     * @return bool
     */
    public function saveForm(array $postFields)
    {
        $updateQuery = array();
        $badField = false;
        $this->_old = $this->getFull(false) . ($this->getName() ? "\n(" . $this->getName() . ')' : '');
        $ignore = array('id', 'parent_id', 'person_id');
        foreach ($postFields as $field => $value) {
            if (in_array($field, $ignore)) {
                continue;
            }
            if (!in_array($field, $this->_formFields)) {
                error_log('Invalid address field passed: ' . $field);
                $badField = true;
                continue;
            }
            if ($field == 'state') {
                $value = substr(strtoupper($value), 0, 2);
                // $updateQuery[] = '`school_district`=""';
                // $updateQuery[] = '`county`=""';
            }
            if ($field == 'city') {
                $value = self::standardizeCityName($value);
            }
            if ($this->$field == $value) {
                continue;
            }
            $this->$field = cms_content::sanitizeText($value);
            $updateQuery[] = '`' . $field . '`="' . core_db::escape($this->$field) . '"';
        }
        if (!empty($updateQuery)) {
            if (($person = mth_person::getPerson($this->getPersonID()))) {
                mth_log::log($person,
                    mth_log::FIELD_ADDRESS,
                    $this->getFull(false) . ($this->getName() ? "\n(" . $this->getName() . ')' : ''),
                    $this->_old,
                    $this->getID());
            }
            return core_db::runQuery('UPDATE mth_address
                                SET ' . implode(',', $updateQuery) . '
                                WHERE address_id=' . $this->getID());
        }
        return !$badField;
    }

    public static function standardizeCityName($city_name)
    {
        $lc = strtolower($city_name);
        if ($lc == 'saint george') {
            $city_name = 'St. George';
        }
        return $city_name;
    }

    public function getPersonID()
    {
        return (int) $this->person_id;
    }

    public function delete()
    {
        return core_db::runQuery('DELETE FROM mth_person_address WHERE address_id=' . $this->getID())
        && core_db::runQuery('DELETE FROM mth_address WHERE address_id=' . $this->getID());
    }

    public static function deleteOrphaned()
    {
        return core_db::runQuery('
      DELETE a.*, pa.*
      FROM `mth_address` AS a
        INNER JOIN `mth_person_address` AS pa ON pa.address_id=a.address_id
        LEFT JOIN `mth_person` AS p ON p.person_id=pa.person_id
      WHERE p.person_id IS NULL');
    }
}
