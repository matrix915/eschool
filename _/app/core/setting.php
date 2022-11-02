<?php

/**
 * settings are values saved in the database and can be user configarable.
 *
 * @author abe
 */
class core_setting
{
    protected $name;
    protected $category;
    protected $title;
    protected $type;
    protected $value;
    protected $description;
    protected $user_changeable;
    protected $date_changed;

    const TYPE_TEXT = 'Text';
    const TYPE_HTML = 'HTML';
    const TYPE_BOOL = 'Bool';
    const TYPE_INT = 'Integer';
    const TYPE_FLOAT = 'Float';
    const TYPE_RAW = 'RAW';

    const DEFAULT_SITE_NAME = 'siteName';
    const DEFAULT_SITE_EMAIL = 'siteEmail';


    public static function getAvailableTypes()
    {
        return array(self::TYPE_TEXT, self::TYPE_HTML, self::TYPE_BOOL, self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_RAW);
    }

    protected static $cache;

    /**
     *
     * @param string $name
     * @param string $category
     * @return core_setting
     */
    public static function get($name, $category = '')
    {
        if (empty(self::$cache[$category][$name])) {
            $name = self::sanitizeNameCategoryStr($name);
            $category = self::sanitizeNameCategoryStr($category);
            self::$cache[$category][$name] = core_db::runGetObject(
                'SELECT * 
                                          FROM core_settings 
                                          WHERE `name`="' . $name . '" 
                                            AND category="' . $category . '"',
                'core_setting'
            );
        }
        return self::$cache[$category][$name];
    }

    /**
     *
     * @param string $category
     * @return array of core_setting objects
     */
    public static function getCategorySettings($category, $userEditableOnly = true)
    {
        return core_db::runGetObjects(
            'SELECT * FROM core_settings 
              WHERE category="' . self::sanitizeNameCategoryStr($category) . '" 
                ' . ($userEditableOnly ? 'AND user_changeable=1' : '') . '
              ORDER BY title',
            'core_setting'
        );
    }

    public static function getCategories($userEditableOnly = true)
    {
        $arr = array();
        $result = core_db::runQuery('SELECT category 
                                  FROM core_settings 
                                  ' . ($userEditableOnly ? 'WHERE user_changeable=1' : '') . '
                                  ORDER BY category');
        while ($r = $result->fetch_row())
            $arr[$r[0]] = $r[0];

        $result->free_result();
        return $arr;
    }

    /**
     *
     * @param string $name
     * @param string $category
     * @param string $value
     * @param string $type
     * @param bool $user_changable
     * @param string $title
     * @param string $description
     * @return core_setting
     */
    public static function init($name, $category, $value, $type = self::TYPE_TEXT, $user_changable = false, $title = NULL, $description = NULL)
    {
        if (($setting = self::get($name, $category))) {
            $value = $setting->getValue();
        }

        core_db::runQuery('REPLACE INTO core_settings 
                        (`name`, category, `type`, `value`, user_changeable, title, description, date_changed) 
                      VALUES 
                        (
                          "' . core_db::escape(self::sanitizeNameCategoryStr($name)) . '",
                          "' . core_db::escape(self::sanitizeNameCategoryStr($category)) . '",
                          "' . (in_array($type, self::getAvailableTypes()) ? $type : self::TYPE_TEXT) . '",
                          "' . core_db::escape(self::sanitizeValueStr($value, $type)) . '",
                          ' . (int) (bool) $user_changable . ',
                          ' . ($user_changable ? '"' . core_db::escape(self::sanitizeTitleStr($title)) . '"' : 'NULL') . ',
                          ' . ($user_changable ? '"' . core_db::escape(self::sanitizeDesriptionStr($description)) . '"' : 'NULL') . ',
                          NOW()
                        )');
        return self::get($name, $category);
    }

    /**
     *
     * @param string $value
     * @return core_setting
     */
    public static function initSiteName($value)
    {
        return self::init(
            self::DEFAULT_SITE_NAME,
            '',
            $value,
            core_setting::TYPE_TEXT,
            true,
            'Site Name',
            'The name of the site to be used in the html title and various locations in the site'
        );
    }

    /**
     *
     * @return core_setting
     */
    public static function getSiteName()
    {
        $siteName = self::get(self::DEFAULT_SITE_NAME);
        if (!$siteName) {
            $siteName = self::initSiteName('Foundation');
        }
        return $siteName;
    }

    /**
     *
     * @param string $value
     * @return core_setting
     */
    public static function initSiteEmail($value)
    {
        return self::init(
            self::DEFAULT_SITE_EMAIL,
            '',
            $value,
            core_setting::TYPE_TEXT,
            true,
            'Site Email',
            'The from email address to be used when sending an email'
        );
    }

    /**
     *
     * @return core_setting
     */
    public static function getSiteEmail()
    {
        $siteEmail = self::get(self::DEFAULT_SITE_EMAIL);
        if (!$siteEmail) {
            $siteEmail = self::initSiteEmail('site@example.com');
        }
        return $siteEmail;
    }

    /**
     * Set the value of a text non-user-editable setting
     * @param string $name
     * @param string $value
     * @param string $type defaults to core_setting:TYPE_TEXT
     * @param string $category
     * @return core_setting
     */
    public static function set($name, $value, $type = self::TYPE_TEXT, $category = '')
    {
        if (!($setting = self::get($name, $category))) {
            return self::init($name, $category, $value, $type);
        }
        $setting->update($value);
        return $setting;
    }

    /**
     * Set the value of a bool non-user-editable setting
     * @param string $name
     * @param bool $value
     * @param string $category
     * @return core_setting
     */
    public static function setBool($name, $value, $category = '')
    {
        self::$cache[$category][$name] = self::init($name, $category, $value, self::TYPE_BOOL);
        return self::$cache[$category][$name];
    }

    /**
     * Set the value of a int non-user-editable setting
     * @param string $name
     * @param int $value
     * @param string $category
     * @return core_setting
     */
    public static function setInt($name, $value, $category = '')
    {
        self::$cache[$category][$name] = self::init($name, $category, $value, self::TYPE_INT);
        return self::$cache[$category][$name];
    }

    /**
     *
     * @param string $value
     * @return bool
     */
    public function update($value)
    {
        $this->value = self::sanitizeValueStr($value, $this->type);
        return core_db::runQuery('UPDATE core_settings 
                              SET `value`="' . core_db::escape($this->value) . '",
                                date_changed=NOW()
                              WHERE `name`="' . core_db::escape($this->name) . '" 
                                AND category="' . core_db::escape($this->category) . '"');
    }

    public static function sanitizeNameCategoryStr($str)
    {
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $str);
    }

    public static function sanitizeTitleStr($title)
    {
        return preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $title);
    }

    public static function sanitizeValueStr($value, $type = self::TYPE_TEXT)
    {
        switch ($type) {
            case self::TYPE_HTML:
                return req_sanitize::html($value);
            case self::TYPE_BOOL:
                return $value ? 1 : 0;
            case self::TYPE_INT:
                return (int) $value;
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_RAW:
                return $value;
            default:
                return req_sanitize::txt($value);
        }
    }

    public static function sanitizeDesriptionStr($description)
    {
        return self::sanitizeValueStr($description, self::TYPE_HTML);
    }


    public function getName()
    {
        return $this->name;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getType()
    {
        return $this->type;
    }


    public function getValue()
    {
        return $this->value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function isUserChangeable()
    {
        return (bool) $this->user_changeable;
    }

    public function __toString()
    {
        return (string) $this->getValue();
    }

    public function getDateChanged($format = NULL)
    {
        return core_model::getDate($this->date_changed, $format);
    }

    public static function userLevelNames(array $setLevels = NULL)
    {
        if (!($setting = self::get('userLevelNames', 'User')) || !is_null($setLevels)) {
            if (!empty($setLevels)) {
                $setLevels = array_combine(
                    array_map('intval', array_keys($setLevels)),
                    array_map('core_setting::sanitizeValueStr', $setLevels)
                )
                    + array(1 => 'Standard', 10 => 'Administrator');
            } else {
                $setLevels = array(1 => 'Standard', 10 => 'Administrator');
            }
            unset($setLevels[0]);
            $setting = self::set('userLevelNames', serialize($setLevels), self::TYPE_RAW, 'User');
        }
        return unserialize($setting->getValue());
    }

    public function getTypeHmtl()
    {
        if ($this->getType() == self::TYPE_BOOL) {
            return '<div class="checkbox-custom checkbox-primary" title="' . $this->getDescription() . '"><input class="advance_settings" data-category="' . $this->getCategory() . '" data-type="' . self::TYPE_BOOL . '" type="checkbox" ' . ($this->getValue() ? 'CHECKED' : '') . ' name="' . $this->getName() . '"><label>' . $this->getTitle() . '</label></div>';
        }

        return '<div class="form-group"><label>' . $this->getTitle() . '</label><textarea class="advance_settings form-control" name="' . $this->getName() . '">' . $this->getValue() . '</textarea></div>';
    }
}
