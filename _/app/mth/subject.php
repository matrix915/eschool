<?php

/**
 * mth_9?
 * mth_subject
 *
 * @author abe
 */
class mth_subject extends core_model
{
    protected $subject_id;
    protected $name;
    protected $desc;
    protected $show_providers;
    protected $require_tp_desc; //not used
    protected $allow_2nd_sem_change; // mth2ndSem001.sql
    protected $periods;
    protected $available;
    protected $archived;
    protected static $cache = array();

    /**
     * Returns mth_subject objects one at a time
     * @param mth_period $period
     * @param bool $reset
     * @return mth_subject
     */
    public static function getEach(mth_period $period = null, $reset = false, $providerId = null)
    {
        $result = &self::$cache['getEach'][$period ? $period->num() : NULL];
        if ($result === NULL) {
            $query = 'SELECT DISTINCT ms.* FROM mth_subject ms
                ' . ($period ? 'INNER JOIN mth_subject_period AS msp
                                  ON msp.subject_id=ms.subject_id
                                    AND msp.period=' . $period->num() : '') .
                   ($providerId ? ' INNER JOIN mth_course mc ON ms.subject_id = mc.subject_id ' : '')
                  . ' WHERE 1 ' . ($providerId ? ('
                  AND mc.course_id IN (SELECT c.course_id FROM mth_course c
                    INNER JOIN mth_provider_course_mapping mpch on c.course_id = mpch.course_id
                     INNER JOIN mth_provider_course mpc on mpch.provider_course_id = mpc.provider_course_id
                      WHERE mpc.provider_id = ' . (int) $providerId . ') ') : '' ) . '
                  ORDER BY
                    available DESC,
                    (SELECT MIN(msp2.period)
                      FROM mth_subject_period AS msp2
                      WHERE msp2.subject_id=ms.subject_id) ASC,
                    `name` ASC';
            $result = core_db::runQuery($query);
        }
        if (!$reset && ($subject = $result->fetch_object('mth_subject'))) {
            self::$cache['getByID'][$subject->getID()] = $subject;
            return $subject;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function getAll(mth_period $period = NULL)
    {
        $arr = &self::$cache['getAll'][$period ? $period->num() : NULL];
        if ($arr === NULL) {
            $arr = array();
            self::getEach($period, true);
            while ($subject = self::getEach($period)) {
                $arr[$subject->getID()] = $subject;
            }
        }
        return $arr;
    }

    public static function getCount(mth_period $period = NULL, $providerId = null)
    {
        $result = &self::$cache['getEach'][$period ? $period->num() : NULL];
        if ($result === NULL) {
            self::getEach($period, true, $providerId);
        }
        return $result->num_rows;
    }

    /**
     *
     * @param int $subject_id
     * @return mth_subject
     */
    public static function getByID($subject_id)
    {
        $subject = &self::$cache['getByID'][$subject_id];
        if ($subject === NULL) {
            $subject = core_db::runGetObject('SELECT * FROM mth_subject WHERE subject_id=' . (int)$subject_id, 'mth_subject');
        }
        return $subject;
    }

    /**
     *
     * @param string $name
     * @return mth_subject
     */
    public static function getByName($name)
    {
        $subject = &self::$cache['getByName'][$name];
        if ($subject === NULL) {
            $subject = core_db::runGetObject('SELECT * FROM mth_subject WHERE `name`="' . core_db::escape($name) . '"', 'mth_subject');
        }
        return $subject;
    }
    
    public static function getByStrings($strings = []){
        $subjects = &self::$cache['getByStrings'];
        if ($subjects === NULL) {
            $likes = implode('%" or name like "%', $strings);
            $subjects = core_db::runGetObjects('SELECT * FROM mth_subject WHERE `name` like "%'.$likes.'%"', 'mth_subject');
        }
        return $subjects;
    }

    public function getID()
    {
        return (int)$this->subject_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDesc()
    {
        return $this->desc;
    }

    public function showProviders($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('show_providers', $set ? 1 : 0);
        }
        return (bool)$this->show_providers;//$this->getRequireTPDesc()?false:(bool)$this->show_providers;
    }

    public function setName($value)
    {
        $this->set('name', cms_content::sanitizeText($value));
    }

    public function setDesc($value)
    {
        $this->set('desc', cms_content::sanitizeText($value));
    }

    public function available($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('available', $set ? 1 : 0);
        }
        return (bool) $this->available;
    }

    public function archived($set = NULL)
    {
        if (!is_null($set)) {
            $this->set('archived', $set ? 1 : 0);
        }
        return $this->archived;
    }

    public function __destruct()
    {
        $this->save();
    }

    public function save()
    {
        if (!$this->subject_id && !$this->name && !$this->require_tp_desc) {
            return false;
        }
        if (!$this->subject_id) {
            core_db::runQuery('INSERT INTO mth_subject (name) VALUES ("UNNAMED")');
            $this->subject_id = core_db::getInsertID();
        }
        return parent::runUpdateQuery('mth_subject', '`subject_id`=' . $this->getID());
    }

    /**
     *
     * @return array of intagers
     */
    public function getPeriods()
    {
        if (is_null($this->periods)) {
            if (($str = core_db::runGetValue('SELECT GROUP_CONCAT(`period`) FROM mth_subject_period WHERE subject_id=' . $this->getID()))) {
                $this->periods = array_map('intval', explode(',', $str));
            } else {
                $this->periods = array();
            }
        }
        return $this->periods;
    }

    public function inPeriod($period)
    {
        return in_array($period, $this->getPeriods());
    }

    public function setPeriods(ARRAY $periods)
    {
        if (!$this->getID() && !$this->save()) {
            return false;
        }
        $Qs = array();
        foreach ($periods as $period) {
            $Qs[$period] = '(' . $this->getID() . ',' . (int)$period . ')';
        }
        return core_db::runQuery('DELETE FROM mth_subject_period WHERE subject_id=' . $this->getID())
        && core_db::runQuery('INSERT INTO mth_subject_period (subject_id,`period`) VALUES ' . implode(',', $Qs));
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function allow_2nd_sem_change($periodNum)
    {
        $secondSemPeriods = &self::$cache['allow_2nd_sem_change'][$this->subject_id];
        if ($secondSemPeriods === NULL) {
            $secondSemPeriods = explode(',', $this->allow_2nd_sem_change);
        }
        return in_array($periodNum, $secondSemPeriods);
    }

    public function set_allow_2nd_sem_change(ARRAY $periodNums)
    {
        $this->set('allow_2nd_sem_change', implode(',', array_map('intval', $periodNums)));
        self::$cache['allow_2nd_sem_change'] = NULL;
    }
}
