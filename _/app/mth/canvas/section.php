<?php

/**
 * Description of section
 *
 * @author abe
 */
class mth_canvas_section
{
    ################################################ DATABASE FIELDS ############################################

    protected $canvas_section_id,
        $canvas_course_id,
        $name;

    ################################################ STATIC MEMBERS ############################################

    protected static $cache = array();

    ################################################ GET METHODS ############################################

    public function canvas_section_id()
    {
        return $this->canvas_section_id;
    }

    public function canvas_course_id()
    {
        return $this->canvas_course_id;
    }

    public function name()
    {
        return $this->name;
    }

    ################################################ STATIC METHODS ############################################

    public static function pull(mth_canvas_course $course)
    {
        $result = mth_canvas::exec('/courses/' . $course->canvas_course_id() . '/sections');
        if (!is_array($result) || (count($result) > 0 && !isset($result[0]->id))) {
            mth_canvas_error::log('Unexpected response', $command, $result);
            return FALSE;
        }
        return self::save($result);
    }

    protected static function save(ARRAY $arrayOfObjectsFromCanvas)
    {
        $q = array();
        foreach ($arrayOfObjectsFromCanvas as $sectionObj) {
            $q[] = '(' . (int)$sectionObj->id . ',' . (int)$sectionObj->course_id . ',"' . core_db::escape($sectionObj->name) . '")';
        }
        if (!$q) {
            return;
        }
        return core_db::runQuery('REPLACE INTO mth_canvas_section (canvas_section_id, canvas_course_id, `name`)
                              VALUES ' . implode(',', $q));
    }

    /**
     *
     * @param mth_canvas_course $course
     * @param string $name
     * @return mth_canvas_section
     */
    public static function create(mth_canvas_course $course, $name)
    {
        self::pull($course);
        self::$cache['get'][$course->canvas_course_id()][$name] = NULL;
        if (($section = self::get($course, $name))) {
            return $section;
        }

        $obj = mth_canvas::exec('/courses/' . $course->canvas_course_id() . '/sections',
            array('course_section[name]' => req_sanitize::txt($name)));
        if (!isset($obj->id)) {
            return false;
        }

        self::save(array($obj));

        self::$cache['get'][$course->canvas_course_id()][$name] = NULL;
        return self::get($course, $name);
    }

    /**
     *
     * @param mth_canvas_course $course
     * @param string $name
     * @param bool $create
     * @return mth_canvas_section
     */
    public static function get(mth_canvas_course $course, $name)
    {
        if (NULL === ($section = &self::$cache['get'][$course->canvas_course_id()][$name])) {
            $section = core_db::runGetObject('SELECT * FROM mth_canvas_section 
                          WHERE canvas_course_id=' . $course->canvas_course_id() . ' 
                            AND name="' . core_db::escape($name) . '"',
                'mth_canvas_section');
        }
        return $section;
    }

}
