<?php

namespace mth\yoda;

use core\Database\PdoAdapterInterface;
use core\Injectable;

class studentassessment
{
    use Injectable, Injectable\PdoAdapterFactoryInjector;

    protected static $cache = array();
    protected $id;
    protected $data;
    protected $grade;
    protected $person_id;
    protected $reset;
    protected $updateQueries = array();
    protected $insertQueries = array();
    protected $excused;
    protected $draft;
    protected $created_at;
    protected $assessment_id;
    protected $message_id;

    const RESET = 2;
    const RESUBMITTED = 1;
    const NA = 3;

    const STATUS_RESET = 'Resubmit Needed';
    const STATUS_SUBMITTED = 'Submitted';
    const STATUS_UNSUBMITTED = 'Not Submitted';
    const STATUS_RESUBMITTED = 'Resubmitted';
    const STATUS_NA = 'N/A';
    const STATUS_EXCUSED = 'Excused';

    public static function getByAssessmentId($id)
    {
        $cache = &self::$cache[$id];
        $sql = 'select * from yoda_student_assessments where assessment_id=' . (int) $id;

        if (!isset($cache)) {
            $cache = \core_db::runGetObjects($sql, __CLASS__);
        }

        return $cache;
    }
    /**
     * Get Student Assessments
     * @param [int] $assessment_id teacher assessment id
     * @param [int] $person_id
     * @return studentassessment
     */
    public static function get($assessment_id, $person_id)
    {
        $cache = &self::$cache['get'][$assessment_id][$person_id];
        $sql = 'select * from yoda_student_assessments where assessment_id=' . (int) $assessment_id . ' and person_id=' . $person_id . ' order by id desc limit 1';

        if (!isset($cache)) {
            $cache = \core_db::runGetObject($sql, __CLASS__);
        }

        return $cache;
    }

    public static function getPreviousAssessments( assessment $teacherAssessment, courses $course, $person_id, $limit = NULL)
    {
        if (!$limit) {
            $limit = 2;
        }
        $teacherAssessmentId = $teacherAssessment->getID();
        $teacherAssessmentDeadline = $teacherAssessment->getDeadline();
        $year = \mth_schoolYear::getByDate(strtotime($teacherAssessmentDeadline));
        $yearStart = $year->getDateBegin('Y-m-d h:i:s');
        $sql = 'SELECT ysa.* FROM yoda_student_assessments AS ysa
              INNER JOIN yoda_teacher_assessments AS yta ON yta.id=ysa.assessment_id
              WHERE ysa.person_id=' . $person_id
              . ' AND yta.course_id=' . $course->getCourseId()
              . ' AND (yta.deadline < "' . $teacherAssessmentDeadline
                  . '" OR (yta.deadline ="' . $teacherAssessmentDeadline
                      . '" AND yta.id < ' . (int) $teacherAssessmentId
              . ')) AND yta.deadline > "' . $yearStart
              . '" AND ysa.draft IS NULL 
              GROUP BY ysa.assessment_id 
              ORDER BY yta.deadline DESC, yta.id DESC 
              LIMIT ' . (int) $limit;
        return \core_db::runGetObjects($sql, __CLASS__);
    }

    public static function getNextAssessments( assessment $teacherAssessment, courses $course, $person_id, $limit = NULL)
    {
        if (!$limit) {
            $limit = 1;
        }
        $teacherAssessmentId = $teacherAssessment->getID();
        $teacherAssessmentDeadline = $teacherAssessment->getDeadline();
        $year = \mth_schoolYear::getByDate(strtotime($teacherAssessmentDeadline));
        $yearEnd = $year->getDateEnd('Y-m-d h:i:s');
        $sql = 'SELECT ysa.* FROM yoda_student_assessments AS ysa
              INNER JOIN yoda_teacher_assessments AS yta ON yta.id=ysa.assessment_id
              WHERE ysa.person_id=' . $person_id
              . ' AND yta.course_id=' . $course->getCourseId()
              . ' AND (yta.deadline > "' . $teacherAssessmentDeadline
                  . '" OR (yta.deadline ="' . $teacherAssessmentDeadline
                      . '" AND yta.id > ' . (int) $teacherAssessmentId
              . ')) AND yta.deadline < "' . $yearEnd
              . '" AND ysa.draft IS NULL 
              GROUP BY ysa.assessment_id 
              ORDER BY yta.deadline ASC, yta.id ASC 
              LIMIT ' . (int) $limit;
        return \core_db::runGetObjects($sql, __CLASS__);
    }

    /**
     * Get the next ungraded studentassessment
     * @param int $id teacher_asessment_id
     * @param array $skipped_logs [int] student_assessment_id that needs to be skip on getting ungraded list
     * @return studentassessment
     */
    public static function getNextUngraded($id, $skipped_logs = [])
    {
        $skipped_log_stmt = !empty($skipped_logs) ? ('and y1.id not in(' . implode(',', $skipped_logs) . ')') : '';
        $sql = "select * from yoda_student_assessments as y1
        inner join mth_person as mp on mp.person_id=y1.person_id
        inner join mth_student as ms on ms.person_id=mp.person_id
        inner join mth_student_status as mss on mss.student_id=ms.student_id
        where assessment_id=$id and grade is null and (reset is null or reset=" . self::RESUBMITTED . ") 
        $skipped_log_stmt
        and id = (select max(id) from yoda_student_assessments as y2 
                   where person_id=y1.person_id and assessment_id=y1.assessment_id)
        and excused is null and draft is null
        and (mss.status != " . \mth_student::STATUS_WITHDRAW . " and mss.school_year_id = " . \mth_schoolYear::getCurrent()->getID() . ")
         order by grade,created_at asc limit 1";

        return \core_db::runGetObject($sql, __CLASS__);
    }

    public static function getSubmittedByAssessmentId($id, $schoolYearID = null)
    {

        if(empty($schoolYearID)) {
            $schoolYearID = \mth_schoolYear::getCurrent()->getID();
        }
        $sql = "select * from yoda_student_assessments as y1
        inner join mth_student as ms on ms.person_id=y1.person_id
        inner join mth_student_status as mss on mss.student_id=ms.student_id
        where assessment_id=$id and (reset is null or reset=" . self::RESUBMITTED . " or reset=" . self::RESET . ") 
        and id = (select max(id) from yoda_student_assessments as y2 
                   where person_id=y1.person_id and assessment_id=y1.assessment_id)
        and excused is null and draft is null
        and (mss.status != " . \mth_student::STATUS_WITHDRAW . " and mss.school_year_id = " . $schoolYearID . ")
        order by grade,created_at asc";

        return \core_db::runGetObjects($sql, __CLASS__);
    }

    public static function getByPersonCourse($person_id, $course_id)
    {
        $cache = &self::$cache['getByPersonCourse'][$course_id][$person_id];
        $sql = 'select * from yoda_student_assessments where person_id=' . $person_id . ' and assessment_id in(
            select id from yoda_teacher_assessments where course_id=' . $course_id . '
        )';

        if (!isset($cache)) {
            $cache = \core_db::runGetObjects($sql, __CLASS__);
        }

        return $cache;
    }

    public function getPerson()
    {
        if (!$this->person_id) {
            return false;
        }
        return \mth_person::getPerson($this->person_id);
    }

    public static function getById($id)
    {
        $cache = &self::$cache['getById'][$id];
        $sql = 'select * from yoda_student_assessments where id=' . (int) $id . ' limit 1';

        if (!isset($cache)) {
            $cache = \core_db::runGetObject($sql, __CLASS__);
        }

        return $cache;
    }

    public function editable()
    {
        return $this->isReset()
            || $this->isResubmitted()
            || $this->isDraft()
            || $this->getGrade() == null;
    }

    public function isReset()
    {
        return $this->reset && self::RESET == $this->reset;
    }

    public function isResubmitted()
    {
        return $this->reset && self::RESUBMITTED == $this->reset;
    }

    public function isNA()
    {
        return $this->reset && self::NA == $this->reset;
    }

    public function getStatus()
    {

        if ($this->isExcused()) {
            return self::STATUS_EXCUSED;
        }

        if ($this->isReset()) {
            return self::STATUS_RESET;
        }

        if ($this->isResubmitted()) {
            return self::STATUS_RESUBMITTED;
        }

        if ($this->isDraft()) {
            return self::STATUS_UNSUBMITTED;
        }

        if ($this->isNA()) {
            return self::STATUS_NA;
        }

        return self::STATUS_SUBMITTED;
    }

    public function isSubmitted()
    {
        return ($this->reset == null && !$this->isDraft()) || $this->isResubmitted();
    }

    public function getGrade()
    {
        return self::NA != $this->reset ? $this->grade : null;
    }

    public function isLate()
    {
        return $this->is_late == 1;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getMessageId()
    {
        return $this->message_id;
    }

    public function getAssessment()
    {
        return assessment::getById($this->assessment_id);
    }

    public function getAssessmentId()
    {
        return $this->assessment_id;
    }

    public function isExcused()
    {
        return $this->excused && $this->excused == 1;
    }

    public function isDraft()
    {
        return $this->draft && $this->draft == 1;
    }

    public function getSubmittedDate($format = null)
    {
        if (!$this->created_at) {
            return null;
        }
        return !$format ? $this->created_at : date($format, strtotime($this->created_at));
    }

    /**
     * set value and enterers field update query in the the updateQueries array
     * @param string $field
     * @param string $value this function will make sure the value is escaped for the database, but no other sanitation.
     */
    public function set($field, $value = null)
    {
        if (is_null($value)) {
            $this->updateQueries[$field] = '`' . $field . '`=NULL';
        } else {
            $this->updateQueries[$field] = '`' . $field . '`="' . \core_db::escape($value) . '"';
        }
    }

    public function setInsert($field, $value = null)
    {
        if (is_null($value)) {
            $this->insertQueries[$field] = 'NULL';
        } else {
            $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
        }
    }


    public function save()
    {
        date_default_timezone_set('America/Denver');
        if (!empty($this->updateQueries)) {
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_student_assessments SET ' . implode(',', $this->updateQueries) . ' WHERE id=' . $this->getID());
        } else {
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_student_assessments(' . implode(',', array_keys($this->insertQueries)) . ') VALUES(' . implode(',', $this->insertQueries) . ')');
            $this->id = \core_db::getInsertID();
        }

        return $success;
    }

    /**
     * Get submitted learning log count weekly(specified week)
     * @param int $school_year_id
     * @return mixed
     */
    public static function getCurrentWeekAllSubmitted($school_year_id)
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('monday next week'));

        $sql = "select count(*) as submitted from yoda_student_assessments as ysa
        inner join yoda_teacher_assessments as yta on ysa.assessment_id=yta.id
        inner join yoda_courses as yc on yc.id=yta.course_id
        where yc.school_year_id=$school_year_id and ysa.id = 
            (select max(id) from yoda_student_assessments where assessment_id=ysa.assessment_id and person_id=ysa.person_id)
        and (reset is null or reset=" . self::RESUBMITTED . ")  and excused is null and draft is null
        and deadline >= '$startOfWeek 00:00:00' AND deadline <= '$endOfWeek 23:59:59'";

        return \core_db::runGetValue($sql);
    }
    /**
     * Get Student Current week log
     * @param [type] $school_year_id
     * @param [type] $person_id
     * @return void
     */
    public static function getCurrentWeekLog($school_year_id, $person_id)
    {
        $cache = &self::$cache['getCurrentWeekLog'][$person_id];

        if (!isset($cache)) {
            $firstday = date('D', time()) === 'Mon' ? 'monday' : 'tuesday';

            $startOfWeek = date('Y-m-d', strtotime("$firstday this week"));
            $endOfWeek = date('Y-m-d', strtotime('monday next week'));

            $sql = "select ysa.*  from yoda_student_assessments as ysa
            inner join yoda_teacher_assessments as yta on ysa.assessment_id=yta.id
            inner join yoda_courses as yc on yc.id=yta.course_id
            where yc.school_year_id=$school_year_id and ysa.id = 
                (select max(id) from yoda_student_assessments where assessment_id=ysa.assessment_id and person_id=ysa.person_id)
                and ysa.person_id = $person_id
            and deadline >= '$startOfWeek 00:00:00' AND deadline <= '$endOfWeek 23:59:59' order by deadline limit 1";

            $cache = \core_db::runGetObject($sql, __CLASS__);
        }

        return $cache;
    }
    /**
     * Get unsubmitted learning log count weekly(specified week)
     * @param int $school_year_id
     * @return mixed
     */
    public static function getCurrentWeekAllUngraded($school_year_id)
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('monday next week'));

        $sql = "select count(ysa.id) as ungraded from yoda_student_assessments as ysa
        inner join yoda_teacher_assessments as yta on ysa.assessment_id=yta.id
        inner join yoda_courses as yc on yc.id=yta.course_id
        where yc.school_year_id=$school_year_id and ysa.id = 
            (select max(id) from yoda_student_assessments where assessment_id=ysa.assessment_id and person_id=ysa.person_id)
        and (reset is null or reset=" . self::RESUBMITTED . ")  and excused is null and draft is null and grade is null
        and deadline >= '$startOfWeek 00:00:00' AND deadline <= '$endOfWeek 23:59:59'";

        return \core_db::runGetValue($sql);
    }

    public static function getLastGradedTime($teacher_user_id)
    {
        $sql = "select max(ysa.updated_at) as updated_at from yoda_courses as yc
        inner join yoda_teacher_assessments as yta on yta.course_id=yc.id
        inner join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
        where teacher_user_id=$teacher_user_id and grade is not null";

        return \core_db::runGetValue($sql);
    }

    public static function getAveGradeTime($teacher_user_id)
    {
        $sql = "select avg(timestampdiff(SECOND, ysa.created_at, ysa.updated_at)) as avetime from yoda_courses as yc
        inner join yoda_teacher_assessments as yta on yta.course_id=yc.id
        inner join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
        where teacher_user_id=$teacher_user_id and grade is not null";

        return \core_db::runGetValue($sql);
    }

    public static function bulkUpdateStatusToSubmitted($id = [])
    {
        $id_str = implode(',', $id);
        $sql = 'UPDATE yoda_student_assessments
                    SET reset=NULL, excused=NULL, draft=NULL
                    WHERE id IN (' . $id_str . ')';
        return \core_db::runQuery($sql);
    }

    public static function getExcusedCount($studentId, $year = null)
    {
        if(!$year)
        {
            $year = \mth_schoolYear::getCurrent();
        }
        $sql = str_replace([':student_id', ':school_year_id'], [$studentId, $year->getID()], courses::EX_STMT);
        $result = \core_db::runGetValue($sql);
        return $result;
    }
}
