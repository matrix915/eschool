<?php

/**
 * mth_period
 *
 * @author abe
 */
class mth_period
{
    protected $num;
    //protected $notes; //use cms

    protected static $periods = array(1, 2, 3, 4, 5, 6, 7);
    protected static $kindergartenPeriods = array(1, 2, 3, 6);
    protected static $labels = array(
        1 => 'Homeroom',
        2 => 'Math',
        3 => 'English / Language Arts',
        4 => 'Science or Social Studies',
        5 => 'Tech / Entrepreneurship',
        6 => 'Elective or another Core',
        7 => 'Optional'
    );

    protected static $walkNum = 1;

    protected function __construct($num)
    {
        if (in_array($num, self::$periods)) {
            $this->num = (int)$num;
        }
    }

    /**
     *
     * @return mth_period
     */
    public static function each($grade_level = NULL)
    {
        if ($grade_level == 'K') {
            while (!in_array(self::$walkNum, self::$kindergartenPeriods) && self::$walkNum <= 7) {
                self::$walkNum++;
            }
        }
        if (($period = self::get(self::$walkNum))) {
            self::$walkNum++;
            return $period;
        } else {
            self::$walkNum = 1;
            return FALSE;
        }
    }

    /**
     *
     * @param int $num
     * @return \mth_period
     */
    public static function get($num)
    {
        if (!in_array($num, self::$periods)) {
            return FALSE;
        }
        return new mth_period($num);
    }

    public function num()
    {
        return $this->num;
    }

    public function required()
    {
        return !(($setting = core_setting::get('allow_none_period_' . $this->num(), 'schedule_period')) && $setting->getValue());
    }

    public function __toString()
    {
        return 'Period ' . $this->num();
    }

    public function label()
    {
        return self::$labels[$this->num];
    }
}
