<?php

/**
 * canvas terms
 *
 * @author abe
 */
class mth_canvas_term extends core_model
{
    protected $canvas_term_id;
    protected $name;
    protected $school_year_id;

    protected static $cache = array();

    public function id()
    {
        return (int)$this->canvas_term_id;
    }

    public function name()
    {
        return $this->name;
    }

    public static function getBySchoolYear(mth_schoolYear $year)
    {
        $term = &self::$cache['getBySchoolYear'][$year->getID()];
        if (!isset($term)) {
            $term = core_db::runGetObject('SELECT * FROM mth_canvas_term 
                                      WHERE school_year_id=' . $year->getID(),
                'mth_canvas_term');
        }
        return $term;
    }

    public static function getCurrentTerm()
    {
        if (!($year = mth_schoolYear::getCurrent())) {
            return NULL;
        }
        return self::getBySchoolYear($year);
    }

    public static function update_mapping()
    {
        $command = '/accounts/' . mth_canvas::account_id() . '/terms?per_page=50&page=';
        $page = 1;
        while ($result = mth_canvas::exec($command . $page)) {
            if (!isset($result->enrollment_terms)) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return FALSE;
            }
            if (count($result->enrollment_terms) == 0) {
                break;
            }
            if (!self::map_results_array($result->enrollment_terms)) {
                error_log('Unable to save canvas term mapping to database');
                return FALSE;
            }
            $page++;
        }
        return TRUE;
    }

    /**
     * Single account mapper
     * @param int $page
     * @return array
     */
    public static function single_mapping($page){
        $command = '/accounts/' . mth_canvas::account_id() . '/terms?per_page=50&page=';
        $count = 0;

        if($result = mth_canvas::exec($command . $page)) {
            
            if (!isset($result->enrollment_terms)) {
                mth_canvas_error::log('Unexpected response', $command . $page, $result);
                return [
                    'error' =>  TRUE,
                    'result' => $count
                ];
            }

            $count = count($result->enrollment_terms);
         
            if (!self::map_results_array($result->enrollment_terms)) {
                error_log('Unable to save canvas term mapping to database');
                return [
                    'error' =>  TRUE,
                    'result' => $count
                ];
            }

        }

        return [
            'error' => FALSE,
            'result' => $count
        ];
    }
    

    protected static function map_results_array($array)
    {
        $Qs = array();
        foreach ($array as $termObj) {
            if (!$termObj->start_at
                || !($schoolYear = mth_schoolYear::getByDate(strtotime($termObj->start_at)))
            ) {
                continue;
            }
            $Qs[] = sprintf(
                '(%d,"%s",%d)',
                $termObj->id,
                core_db::escape(req_sanitize::txt($termObj->name)),
                $schoolYear->getID());
        }
        if (empty($Qs)) {
            return TRUE;
        }
        return core_db::runQuery('REPLACE INTO mth_canvas_term 
                              (canvas_term_id, `name`, school_year_id) 
                              VALUES ' . implode(',', $Qs));
    }
}
