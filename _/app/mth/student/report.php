<?php
use mth\student\SchoolOfEnrollment;

/**
 * Description of reports
 *
 * @author abe
 */
class mth_student_report
{
    public $label_heading;
    public $total_heading = 'Total';
    public $percent_heading = '%';
    public $extra1_heading;
    public $extra2_heading;
    public $end_total = 0;
    public $end_extra1;
    public $end_extra2;

    public $items = array();

    protected $onItem = 0;

    protected static $cache = array();

    /**
     * $item->extra1 = age, $report->end_extra1 = average age
     * @param mth_schoolYear $schoolYear
     * @return \mth_student_report
     */
    public static function getGradeAge(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'Grade';
        $report->extra1_heading = 'Age';
        $result = core_db::runQuery('SELECT DISTINCT
        gl.grade_level, 
        s.student_id, 
        IF(p.date_of_birth IS NULL, gl.grade_level+5, DATE_FORMAT(NOW(), "%Y") - DATE_FORMAT(p.date_of_birth, "%Y") - (DATE_FORMAT(NOW(), "00-%m-%d") < DATE_FORMAT(p.date_of_birth, "00-%m-%d"))) AS age
      FROM mth_student AS s
        INNER JOIN mth_person AS p ON p.person_id=s.person_id
        INNER JOIN mth_student_status AS ss 
          ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        LEFT JOIN mth_student_grade_level AS gl ON gl.student_id=s.student_id
          AND gl.school_year_id=ss.school_year_id');
        $ageMult = 0;
        $gradeLevels = mth_student::getAvailableGradeLevelsNormal();
        while ($r = $result->fetch_assoc()) {
            $grade_level = $r['grade_level']=='K'?0:$r['grade_level'];
            $id = str_pad(( (int) $grade_level * 1000) + (int) $r['age'], 5, '0', STR_PAD_LEFT);
            if (!isset($report->items[$id])) {
                $report->items[$id] = new mth_student_report_item();
                $report->items[$id]->label = $gradeLevels[$r['grade_level']];
                $report->items[$id]->extra1 = $r['age'];
                $report->items[$id]->total = 0;
            }
            /** @var mth_student_report_item $item */
            $item = $report->items[$id];
            $item->total += 1;
            $report->end_total += 1;
            $ageMult += $item->extra1;
        }
        $result->free_result();
        ksort($report->items);
        $report->items = array_values($report->items);
        $report->end_extra1 = $report->end_total?round($ageMult / $report->end_total, 2):0;
        $report->calculatePercentages();
        $result->close();

        return $report;
    }

    public function groupGradeAgeByLabel(){
        $grouped_report = null;
        $this->eachItem(true);
        while ($item = $this->eachItem()) {
            $grouped_report[$item->label][] = $item;
        }
    
        return $grouped_report;
    }

    public function getGradeAgeAverage($item){

        $gradeAgeAverage = array(
            'extra1' => 0,
            'total' => 0,
            'percent' => 0,
            'counter' => 0
        );
    
        if($item){
            foreach($item as $i){
                $gradeAgeAverage['extra1'] += $i->extra1;
                $gradeAgeAverage['total'] += $i->total;
                $gradeAgeAverage['percent'] += (int) $i->percent;
                $gradeAgeAverage['counter']++;
            }
        }else{
            $gradeAgeAverage['counter'] = 1;
        }

        return $gradeAgeAverage;
    }

    public function calculatePercentages()
    {
        $this->eachItem(true);
        while ($item = $this->eachItem()) {
            $item->percent = number_format(round(100 * ($item->total / $this->end_total), 1), 1) . '%';
        }
    }

    public static function getSchoolOfEnrollment(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'School (' . $schoolYear . ')';
        $result = core_db::runQuery('SELECT 
        ssch.school_of_enrollment AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_school AS ssch ON ssch.student_id=s.student_id
          AND ssch.school_year_id=' . $schoolYear->getID() . '
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
      GROUP BY ssch.school_of_enrollment');
      $unassigned = core_db::runGetValues('SELECT 
                                        count(s.student_id) AS total
                                            FROM mth_student AS s 
                                              INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
                                                AND ss.school_year_id=' . $schoolYear->getID() . '
                                                AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
                                            WHERE s.student_id NOT IN (SELECT ssc.student_id
                                                        FROM mth_student_school AS ssc
                                                        WHERE school_of_enrollment IN (0,1,2,3,4,5,6,7,8)
                                                            AND school_year_id IN (' . $schoolYear->getID() . ')
      )');

        $schools = SchoolOfEnrollment::getAllSOE();
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $r->label = $schools[$r->label]->getShortName();
            if($r->label == $schools[0]->getShortName()) {
              $r->total+=$unassigned[0];
            }
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getPreviousSchoolOfEnrollment(mth_schoolYear $schoolYear)
    {
        if (!($previousYear = $schoolYear->getPreviousYear())) {
            return FALSE;
        }
        $report = new mth_student_report();
        $report->label_heading = 'School (' . $previousYear . ')';
        $result = core_db::runQuery('SELECT 
        ssch.school_of_enrollment AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        LEFT JOIN mth_student_school AS ssch ON ssch.student_id=s.student_id
          AND ssch.school_year_id=' . $previousYear->getID() . '
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
      GROUP BY ssch.school_of_enrollment');
        $schools = SchoolOfEnrollment::getAllSOE();
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $r->label = isset($schools[$r->label]) ? $schools[$r->label]->getShortName() : 'New';
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getDistrictOfResidence(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'District of Residence';
        $result = core_db::runQuery('SELECT 
        IFNULL(ma.school_district,"(No Packet)") AS label,
        count(DISTINCT(s.student_id)) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
        AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        LEFT JOIN mth_packet AS p ON p.student_id=s.student_id
        LEFT JOIN mth_parent mp ON s.parent_id = mp.parent_id
        LEFT JOIN mth_person mps ON mp.person_id = mps.person_id
        LEFT JOIN mth_person_address mpa ON mps.person_id = mpa.person_id
        LEFT JOIN mth_address ma ON mpa.address_id = ma.address_id
      GROUP BY ma.school_district');
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getGender(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'Gender';
        $result = core_db::runQuery('SELECT 
        IFNULL(p.gender, "(Unspecified)") AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        INNER JOIN mth_person AS p ON p.person_id=s.person_id
      GROUP BY p.gender');
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getSPED(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'SPED';
        $result = core_db::runQuery('SELECT 
        IF(s.special_ed,"Yes","No") AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
      GROUP BY s.special_ed');
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getDiplomaSeekingByGrade(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'Diploma Seeking';
        $result = core_db::runQuery('SELECT
        gl.grade_level AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        INNER JOIN mth_student_grade_level AS gl ON gl.student_id=s.student_id
          AND gl.school_year_id=ss.school_year_id
      WHERE s.diploma_seeking=1
      GROUP BY gl.grade_level
      ORDER BY gl.grade_level');
        $gradeLevels = mth_student::getAvailableGradeLevelsShort();
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $r->label = $gradeLevels[$r->label] . ' Grade';
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getSPEDbyGrade(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'SPED';
        $result = core_db::runQuery('SELECT 
        gl.grade_level AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        INNER JOIN mth_student_grade_level AS gl ON gl.student_id=s.student_id
          AND gl.school_year_id=ss.school_year_id
      WHERE s.special_ed=1
      GROUP BY gl.grade_level');
        $gradeLevels = mth_student::getAvailableGradeLevelsShort();
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $r->label = $gradeLevels[$r->label] . ' Grade';
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getGPAbyGrade(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'GPA';
        $result = core_db::runQuery('SELECT 
        gl.grade_level AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_school AS ssch ON ssch.student_id=s.student_id
          AND ssch.school_year_id=' . $schoolYear->getID() . '
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        INNER JOIN mth_student_grade_level AS gl ON gl.student_id=s.student_id
          AND gl.school_year_id=ss.school_year_id
      WHERE ssch.school_of_enrollment=' . SchoolOfEnrollment::GPA . '
      GROUP BY gl.grade_level');
        $gradeLevels = mth_student::getAvailableGradeLevelsShort();
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $r->label = $gradeLevels[$r->label] . ' Grade';
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    public static function getESchoolByGrade(mth_schoolYear $schoolYear)
    {
        $report = new mth_student_report();
        $report->label_heading = 'eSchool';
        $result = core_db::runQuery('SELECT 
        gl.grade_level AS label,
        count(s.student_id) AS total
      FROM mth_student AS s 
        INNER JOIN mth_student_school AS ssch ON ssch.student_id=s.student_id
          AND ssch.school_year_id=' . $schoolYear->getID() . '
        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
          AND ss.school_year_id=' . $schoolYear->getID() . '
          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')
        INNER JOIN mth_student_grade_level AS gl ON gl.student_id=s.student_id
          AND gl.school_year_id=ss.school_year_id
      WHERE ssch.school_of_enrollment=' . SchoolOfEnrollment::eSchool . '
      GROUP BY gl.grade_level');
        $gradeLevels = mth_student::getAvailableGradeLevelsShort();
        while ($r = $result->fetch_object('mth_student_report_item')) {
            $r->label = $gradeLevels[$r->label] . ' Grade';
            $report->items[] = $r;
            $report->end_total += $r->total;
        }
        $result->free_result();
        $report->calculatePercentages();
        $result->close();
        return $report;
    }

    /**
     *
     * @param bool $reset
     * @return mth_student_report_item
     */
    public function eachItem($reset = false)
    {
        if (!$reset && isset($this->items[$this->onItem]) && ($item = $this->items[$this->onItem])) {
            $this->onItem++;
            return $item;
        }
        $this->onItem = 0;
        return NULL;
    }

    public static function printReport(mth_student_report $report)
    {
        ?>
        <div class="div-table">
            <div>
                <div style="width: 50%; font-weight: 700;"><?= $report->label_heading ?></div>
                <div style="width: 20%; font-weight: 700; text-align: right;"><?= $report->total_heading ?></div>
                <div style="width: 20%; font-weight: 700; text-align: right;"><?= $report->percent_heading ?></div>
            </div>
            <?php while ($item = $report->eachItem()): ?>
                <div>
                    <div style="width: 50%"><?= $item->label ?></div>
                    <div style="width: 20%; text-align: right"><?= $item->total ?></div>
                    <div style="width: 20%; text-align: right"><?= $item->percent ?></div>
                </div>
            <?php endwhile; ?>
            <div>
                <div style="width: 50%; font-weight: 700;"></div>
                <div style="width: 20%; font-weight: 700; text-align: right;"><?= $report->end_total ?></div>
                <div style="width: 20%; font-weight: 700;"></div>
            </div>
        </div>
        <?php
    }

    /**
     *
     * @param mth_schoolYear $schoolYear
     * @return int
     */
    public static function totalStudents(mth_schoolYear $schoolYear = NULL)
    {
        $total = &self::$cache['totalStudents'][$schoolYear ? $schoolYear->getID() : NULL];
        if (!$total) {
            if (!$schoolYear && !($schoolYear = mth_schoolYear::getCurrent())) {
                return NULL;
            }
            $total = (int)core_db::runGetValue('SELECT count(s.student_id)
                                      FROM mth_student AS s 
                                        INNER JOIN mth_student_status AS ss ON ss.student_id=s.student_id 
                                          AND ss.school_year_id=' . $schoolYear->getID() . '
                                          AND ss.status IN (' . mth_student::STATUS_ACTIVE . ',' . mth_student::STATUS_PENDING . ')');
        }
        return $total;
    }
}
