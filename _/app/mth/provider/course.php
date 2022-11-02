<?php

/**
 * mth_provider_course
 *
 * @author abe
 */
class mth_provider_course extends core_model
{
    protected $provider_course_id;
    protected $provider_id;
    protected $title;
    protected $available;
    protected $diploma_only;
    protected $archived;
    protected $reduceTechAllowance;

    protected $mth_course_ids;
    protected $unarchived_mth_course_ids;
    protected $eachNum = 0;
    protected $is_launchpad_course;
    protected $spark_course_id;

    public function id()
    {
        return (int)$this->provider_course_id;
    }

    public function provider(mth_provider $set = NULL)
    {
        if (!is_null($set)) {
            $this->set('provider_id', $set->id());
        }
        return mth_provider::get($this->provider_id);
    }

    public function provider_id($set = NULL)
    {
        if (!is_null($set) && ($provider = mth_provider::get($set))) {
            $this->provider($provider);
        }
        return (int)$this->provider_id;
    }

    public function title($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('title', cms_content::sanitizeText($set));
        }
        return $this->title;
    }

    public function available($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('available', $set ? 1 : 0);
        }
        return $this->available;
    }

    public function archived($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('archived', $set ? 1 : 0);
        }
        return $this->archived;
    }

    public function isLaunchpadCourse($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('is_launchpad_course', $set ? 1 : 0);
        }
        return $this->is_launchpad_course;
    }

    public function sparkCourseId($set=NULL)
    {
        if (!is_null($set)) {
            $this->set('spark_course_id', $set);
        }
        return $this->spark_course_id;
    }

    public function diplomanaOnly($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('diploma_only', $set ? 1 : 0);
        }
        return $this->diploma_only;
    }

    public function reduceTechAllowanceFunction($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('reduceTechAllowance', $set ? 1 : 0);
        }
        return $this->reduceTechAllowance;
    }

    public function mappedToCourse(mth_course $course)
    {
        $this->mth_course_ids();
        return in_array($course->getID(), $this->mth_course_ids);
    }

    public function eachCourse($reset = false)
    {
        $this->mth_course_ids();
        if ($reset) {
            $this->eachNum = 0;
            return NULL;
        }
        if (isset($this->mth_course_ids[$this->eachNum])) {
            $thisCourse = mth_course::getByID($this->mth_course_ids[$this->eachNum]);
            $this->eachNum++;
            return $thisCourse;
        } else {
            $this->eachNum = 0;
            return NULL;
        }
    }

    /**
     *
     * @param mth_provider $provider
     * @param mth_course $course
     * @param bool $reset
     * @return mth_provider_course
     */
    public static function each(mth_provider $provider, mth_course $course = NULL, $reset = false)
    {
        $result = &self::cache(__CLASS__, 'each-' . $provider->id() . '-' . ($course ? $course->getID() : 'ALL'));
        if (!isset($result)) {
            $result = core_db::runQuery('SELECT pc.* 
                                  FROM mth_provider_course AS pc
                                  ' . ($course ? 'INNER JOIN mth_provider_course_mapping AS map
                                      ON map.provider_course_id=pc.provider_course_id
                                        AND map.course_id=' . $course->getID() . '
                                        AND pc.available=1' : '') . '
                                  WHERE provider_id=' . $provider->id() . '
                                  ORDER BY available DESC, title ASC');
        }
        if ($reset) {
            $result->data_seek(0);
            return NULL;
        }
        if (($providerCourse = $result->fetch_object('mth_provider_course'))) {
            return $providerCourse;
        } else {
            $result->data_seek(0);
            return NULL;
        }
    }

    public static function all(mth_provider $provider, mth_course $course = NULL)
    {
        $arr = &self::cache(__CLASS__, 'all-' . $provider->id() . '-' . ($course ? $course->getID() : 'ALL'));
        if (!isset($arr)) {
            $arr = array();
            self::each($provider, $course, true);
            while ($provider_course = self::each($provider, $course)) {
                $arr[$provider_course->id()] = $provider_course;
            }
        }
        return $arr;
    }

    public static function allByProviderIds($providerIds) {
        $providerIds = array_filter($providerIds, function($element) {
            return is_numeric($element);
        } );
        return core_db::runGetObjects('SELECT pc.* 
                                  FROM mth_provider_course AS pc
                                  WHERE provider_id IN (' . implode(",", $providerIds ) . ')
                                  ORDER BY available DESC, title ASC', __CLASS__);
    }

    public static function count(mth_provider $provider, mth_course $course = NULL)
    {
        $count = &self::cache(__CLASS__, 'count-' . $provider->id() . '-' . $course);
        if (!isset($count)) {
            $count = count(self::all($provider, $course));
        }
        return $count;
    }

    public static function countWithoutDiplomaOnly(mth_provider $provider, mth_course $course)
    {
      return core_db::runGetValue('SELECT COUNT(pc.provider_course_id) FROM mth_provider_course AS pc
      INNER JOIN mth_provider_course_mapping AS map
      ON map.provider_course_id=pc.provider_course_id
        AND map.course_id=' . $course->getID() . '
        AND pc.available=1
        AND pc.diploma_only=0
      WHERE provider_id=' . $provider->id() . '
         ORDER BY available DESC, title ASC');
    }


    /**
     *
     * @param mth_provider $provider
     * @param string $title
     * @return mth_provider_course
     */
    public static function getByTitle(mth_provider $provider, $title)
    {
        $course = &self::cache(__CLASS__, 'getByTitle-' . $provider->id() . '-' . $title);
        if (!isset($course)) {
            $course = core_db::runGetObject('SELECT * FROM mth_provider_course 
                                        WHERE provider_id=' . $provider->id() . '
                                          AND title="' . core_db::escape($title) . '"',
                'mth_provider_course');
            if (!$course) {
                unset($course);
                return NULL;
            }
        }
        return $course;
    }

    /**
     *
     * @param mth_provider $provider
     * @param string $title
     * @return mth_provider_course
     */
    public static function getByID($provider_course_id)
    {
        return core_db::runGetObject('SELECT * FROM mth_provider_course 
                                    WHERE provider_course_id=' . (int)$provider_course_id,
            'mth_provider_course');
    }

    public static function getSprakCourses(){
        $course = core_db::runGetObjects("SELECT course.*, provider.name as provider_name FROM mth_provider_course AS course
                LEFT JOIN mth_provider AS provider ON provider.provider_id = course.provider_id
                WHERE course.spark_course_id != ''");
        return $course;
    }

    public function mth_course_ids()
    {
        if (is_array($this->mth_course_ids)) {
            return $this->mth_course_ids;
        }
        $this->mth_course_ids = array();
        $result = core_db::runQuery('SELECT course_id FROM mth_provider_course_mapping WHERE provider_course_id=' . $this->id());
        while ($r = $result->fetch_row()) {
            $this->mth_course_ids[] = $r[0];
        }
        $result->free_result();
        return $this->mth_course_ids;
    }

    public function unarchived_mth_course_ids()
    {
        if (is_array($this->unarchived_mth_course_ids)) {
            return $this->unarchived_mth_course_ids;
        }
        $this->unarchived_mth_course_ids = array();
        $result = core_db::runQuery(
            'SELECT course_id FROM mth_course where archived = 0 and course_id in 
            (select course_id from mth_provider_course_mapping
            WHERE provider_course_id=' . $this->id() . ')'
        );
        while ($r = $result->fetch_row()) {
            $this->unarchived_mth_course_ids[] = $r[0];
        }
        $result->free_result();
        return $this->unarchived_mth_course_ids;
    }

    public function mapCourses($course_ids)
    {
        $this->mth_course_ids = array();
        if (!$course_ids) {
            return;
        }
        if (!is_array($course_ids)) {
            $course_ids = array($course_ids);
        }
        foreach ($course_ids as $course_id) {
            if (!($course = mth_course::getByID($course_id))
                || in_array($course->getID(), $this->mth_course_ids)
            ) {
                continue;
            }
            $this->mth_course_ids[] = $course->getID();
        }
    }

    public function addToMap(mth_course $course)
    {
        $this->mth_course_ids();
        if (in_array($course->getID(), $this->mth_course_ids)) {
            return;
        }
        $this->mth_course_ids[] = $course->getID();
    }

    public function save()
    {
        if (empty($this->title) || empty($this->provider_id)) {
            return false;
        }
        if (empty($this->provider_course_id)) {
            core_db::runQuery('INSERT INTO mth_provider_course (provider_id, title) 
                            VALUES (' . (int)$this->provider_id . ',"UNNAMED")');
            $this->provider_course_id = core_db::getInsertID();
        }

        $success = parent::runUpdateQuery('mth_provider_course', 'provider_course_id=' . $this->id());

        if (is_array($this->mth_course_ids)) {
            $success2 = core_db::runQuery('DELETE FROM mth_provider_course_mapping WHERE provider_course_id=' . $this->id())
                && (empty($this->mth_course_ids)
                    || core_db::runQuery('INSERT INTO mth_provider_course_mapping (provider_course_id, course_id) 
                                        VALUES (' . $this->id() . ',' . implode('),(' . $this->id() . ',', $this->mth_course_ids) . ')'));
        } else {
            $success2 = true;
        }
        return $success && $success2;
    }

    public function __destruct()
    {
        $this->save();
    }

    public function __toString()
    {
        return $this->title();
    }
}
