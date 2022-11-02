<?php

namespace mth\yoda;

use core\Database\PdoAdapterInterface;
use core\Injectable;
use core_db;
use mth_schoolYear;
use core_setting;

class assessment
{
    use Injectable, Injectable\PdoAdapterFactoryInjector;

    protected $id;
    protected $title;
    protected $data;
    protected $deadline;
    protected $created_by_user_id;
    protected $course_id;
    protected $type;
    protected $grade;

    protected static $cache = array();
    protected $updateQueries = array();
    protected $insertQueries = array();
    public $person_id;

    const LLOG = 4; //learning logs
    const PASSED_GRADE = 80;

    public $clone_date = false;

    const RESET = 2;
    const RESUBMITTED = 1;
    const NA = 3;

    const STATUS_RESET = 'Resubmit Needed';
    const STATUS_SUBMITTED = 'Submitted';
    const STATUS_UNSUBMITTED = 'Not Submitted';
    const STATUS_RESUBMITTED = 'Resubmitted';
    const STATUS_NA = 'N/A';
    const STATUS_EXCUSED = 'Excused';

    public function getStudentLearningLogs(\mth_student $student, \mth_schoolYear $year = null)
    {
        if (is_null($year)) {
            $year = \mth_schoolYear::getCurrent();
        }

        $sql = 'select yta.* from yoda_teacher_assessments as yta
        inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
        where ysh.student_id=:student_id and ysh.school_year_id=:school_year_id order by deadline';

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([':school_year_id' => $year->getID(), ':student_id' => $student->getID()])
            ->fetchAllClass(__CLASS__);
    }


    public function getCourse()
    {
        if (!$this->course_id) {
            return null;
        }
        return courses::getById($this->course_id);
    }

    public function getByCourseId($course_id)
    {

        $sql = 'select * from yoda_teacher_assessments where course_id=:course_id order by deadline';

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([':course_id' => $course_id])
            ->fetchAllClass(__CLASS__);
    }

    public static function getByCourseDeadline($course_id, $date)
    {
        $sql = 'select * from yoda_teacher_assessments where course_id=' . $course_id . ' and DATE_FORMAT(deadline, "%Y-%m-%d") <= "' . $date . '" order by deadline asc';
        return \core_db::runGetObjects($sql, __CLASS__);
    }
    /**
     * Get Learning log by in between the specified dates
     * @param int $course_id
     * @param string $from datetime string param
     * @param string $to datetime string param
     * @return void
     */
    public static function getByCourseRange($course_id, $from, $to)
    {
        $sql = "select * from yoda_teacher_assessments where deadline >= '$from' and deadline <= '$to' and course_id = $course_id order by deadline";
        return \core_db::runGetObjects($sql, __CLASS__);
    }

    /**
     * Get Learning logs not due to the specified date
     * @param int $school_year
     * @param string $date
     * @return void
     */
    public static function getLogNamesByDeadline($school_year, $date)
    {
        $sql = "select distinct(title) from yoda_teacher_assessments as a
        inner join yoda_student_homeroom as b on a.course_id=b.yoda_course_id  
        where school_year_id=$school_year and deadline < '$date'
        order by deadline";

        return \core_db::runGetValues($sql, __CLASS__);
    }

    public function getLearningsLogsAssissments(\mth_student $student, \mth_schoolYear $year = null)
    {
        if (is_null($year)) {
            $year = \mth_schoolYear::getCurrent();
        }

        $sql = 'select yta.*, ysa.person_id, ysa.assessment_id, ysa.is_late,ysa.grade, ysa.message_id,ysa.`reset`, ysa.excused, ysa.draft, ysa.created_at AS ysa_created_at from yoda_teacher_assessments as yta
        left join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
        left join yoda_student_assessments as ysa on ysa.assessment_id = yta.id AND ysa.id = (
			SELECT ysa2.id FROM yoda_student_assessments ysa2 WHERE ysa2.person_id =:student_person_id and ysa2.assessment_id = yta.id order by ysa2.id desc limit 1
		)
        where ysh.student_id=:student_id and ysh.school_year_id=:school_year_id order by deadline';

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([':school_year_id' => $year->getID(), ':student_id' => $student->getID(), ':student_person_id' => $student->getPersonID()])
            ->fetchAllClass(__CLASS__);
    }

    public function getGrade()
    {
        return $this->grade;
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

    public function isDraft()
    {
        return $this->draft && $this->draft == 1;
    }

    public function isExcused()
    {
        return $this->excused && $this->excused == 1;
    }
    
    public function isSubmitted()
    {
        return ($this->reset == null && !$this->isDraft()) || $this->isResubmitted();
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

    public function getSubmittedDate($format = null)
    {
        if (!$this->ysa_created_at) {
            return null;
        }
        return !$format ? $this->ysa_created_at : date($format, strtotime($this->ysa_created_at));
    }

    public static function getLearningLogsByStudent(\mth_student $student, \mth_schoolYear $year = null, $semester = null)
    {
        if (is_null($year)) {
            $year = \mth_schoolYear::getCurrent();
        }

        $sql = 'select yta.* from yoda_teacher_assessments as yta
        inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
        where ysh.student_id=' . $student->getID() . ' and ysh.school_year_id=' . $year->getID();

        $end_of_first_sem = $year->getFirstSemLearningLogsClose('Y-m-d') ? date('Y-m-d', strtotime($year->getFirstSemLearningLogsClose('Y-m-d') . ' -1 day')) : $year->getLogSubmissionClose('Y-m-d');

        if ($semester == 1) {
            $sql .= ' AND DATE_FORMAT(yta.deadline, "%Y-%m-%d") BETWEEN "' . $year->getDateBegin('Y-m-d') . '" AND "' . $end_of_first_sem . '"';
        } elseif ($semester == 2) {
            $sql .= ' AND DATE_FORMAT(deadline, "%Y-%m-%d") BETWEEN "' . $year->getFirstSemLearningLogsClose('Y-m-d') . '" AND "' . $year->getLogSubmissionClose('Y-m-d') . '"';
        }
        $sql .= ' order by deadline';

        return \core_db::runGetObjects($sql, __CLASS__);
    }

    /**
     * Get current learning logs
     * @return [assessment]
     */
    public static function getCurrentLearningLog()
    {
        $sql = 'select * from yoda_teacher_assessments where DATE_FORMAT(deadline, "%M %d %Y") = DATE_FORMAT(curdate(), "%M %d %Y")';
        return \core_db::runGetObjects($sql, __CLASS__);
    }

    /**
     * Get the total of ungraded learning logs
     * @return mixed
     */
    public function getUngradedCount()
    {
        $sql = "select count(y1.id) as ungraded from yoda_student_assessments as y1
        RIGHT JOIN mth_person as mp ON mp.person_id=y1.person_id
        inner join mth_student as ms on ms.person_id=y1.person_id
        inner join mth_student_status as mss on mss.student_id=ms.student_id
        where assessment_id=:assessment_id and grade is null and (reset is null or reset=:reset) 
        and id = (select max(id) from yoda_student_assessments as y2 
                   where person_id=y1.person_id and assessment_id=y1.assessment_id)
        and excused is null and draft is null
        and (mss.status != " . \mth_student::STATUS_WITHDRAW . " and mss.school_year_id = " . \mth_schoolYear::getCurrent()->getID() . ")";

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([':assessment_id' => $this->getID(), ':reset' => studentassessment::RESUBMITTED])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Get NExt ungraded learning log, use for queueing learning logs
     * @param int $assessment_id
     * @return studentassessment
     */
    public static function getNextUngraded($assessment_id)
    {
        $sql = "select * from yoda_student_assessments as y1
        where assessment_id=$assessment_id and grade is null and (reset is null or reset=" . studentassessment::RESUBMITTED . ") 
        and id = (select max(id) from yoda_student_assessments as y2 
                   where person_id=y1.person_id and assessment_id=y1.assessment_id)
        and draft is null order by grade,created_at asc limit 1";
        return \core_db::runGetObject($sql);
    }

    public static function getByTitle($title, $course_id)
    {
        $db = new \core_db();
        $sql = 'select * from yoda_teacher_assessments where title="' . $db->escape_string($title) . '" and course_id=' . $course_id;
        return $db::runGetObject($sql, __CLASS__);
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getID()
    {
        return (int) $this->id;
    }

    public function isEditable()
    {
        $days_to_submit = core_setting::get('logDaysEditable', 'LearningLog');
        return strtotime($this->deadline) < strtotime("+$days_to_submit days");
    }

    public function validForSubmission()
    {
        if (\core_user::isUserTeacher()) {
            return false;
        }

        if (!($current_year = \mth_schoolYear::getByDate(strtotime($this->deadline))) || !$current_year->isLearningLogOpen()) {
            return false;
        }

        return true;
    }

    public function getData()
    {
        return json_decode($this->data);
    }

    public function getDeadline($format = null)
    {
        return $format ? \core_model::getDate($this->deadline, $format) : $this->deadline;
    }

    public function isDue()
    {
        return strtotime($this->deadline) < time();
    }

    public static function getById($id)
    {
        $cache = &self::$cache['getById'][$id];
        $sql = 'select * from yoda_teacher_assessments where id=' . (int) $id . ' limit 1';

        if (!isset($cache)) {
            $cache = \core_db::runGetObject($sql, __CLASS__);
        }

        return $cache;
    }

    public static function getByDeadline($year = "", $semester = 1)
    {
        if ($semester == 1) {
            $sql = 'SELECT * FROM yoda_teacher_assessments WHERE ( DATE_FORMAT(deadline, "%Y-%m-%d") BETWEEN "' . $year->getDateBegin('Y-m-d') . '" AND "' . $year->getFirstSemLearningLogsClose('Y-m-d') . '")';
        } else {
            $sql = 'SELECT * FROM yoda_teacher_assessments WHERE ( DATE_FORMAT(deadline, "%Y-%m-%d") BETWEEN "' . $year->getFirstSemLearningLogsClose('Y-m-d') . '" AND "' . $year->getLogSubmissionClose('Y-m-d') . '")';
        }
        return \core_db::runGetObjects($sql, __CLASS__);
    }

    public static function isPassing($grade)
    {
        return $grade >= self::PASSED_GRADE;
    }

    public function set($field, $value)
    {
        if (is_null($value)) {
            $this->updateQueries[$field] = '`' . $field . '`=NULL';
        } else {
            $this->updateQueries[$field] = '`' . $field . '`="' . \core_db::escape($value) . '"';
        }
        return $this;
    }

    public function setInsert($field, $value)
    {
        $this->{$field} = $value;
        if (is_null($value)) {
            $this->insertQueries[$field] = 'NULL';
        } else {
            $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
        }
        return $this;
    }

    public function save()
    {
        if (!empty($this->updateQueries)) {
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_teacher_assessments SET ' . implode(',', $this->updateQueries) . ' WHERE id=' . $this->getID());
        } else {
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_teacher_assessments(' . implode(',', array_keys($this->insertQueries)) . ') VALUES(' . implode(',', $this->insertQueries) . ')');
            $this->id = \core_db::getInsertID();
        }

        return $success;
    }

    /**
     * Adjust Deadline
     * @param date $deadline
     * @param string $add
     * @param string $format date format
     * @return date
     */
    public static function adjustDeadline($deadline, $add = '+7 days', $format = 'Y-m-d H:i:s')
    {
        return  date($format, strtotime($deadline . " $add"));
    }

    /**
     * Auto Adjust new deadline + 1 year and should be first day of the week
     * @param string $deadline
     * @param int $deadline_year_id
     * @return void
     */
    public function _generateNewDeadline($deadline, $deadline_year_id)
    {
        if (($selected_year = mth_schoolYear::getByID($deadline_year_id)) && $deadline) {
            $deadline_school_year = mth_schoolYear::getByDate(strtotime($deadline));
            //find the difference to determine how many years to be added to the new deadline
            $school_year_diff = abs($selected_year->getStartYear() - $deadline_school_year->getStartYear());

            if ($this->clone_date) {
                $date = new \Datetime($deadline);
                $result = (new \DateTime())->setISODate((int) $date->format('o') + $school_year_diff, (int) $date->format('W'), (int) $date->format('N'));

                return $result->format('Y-m-d') . date(' H:i:s', strtotime($deadline));
            }

            //add  x year(s) to the old/model deadline
            $_new_deadline = strtotime($deadline . " +$school_year_diff years");
            //set the deadline to be always on monday
            return date('Y-m-d', strtotime("monday this week", $_new_deadline)) . date(' H:i:s', strtotime($deadline));
        }
        return $deadline;
    }

    /**
     * Clone Assessment 
     * @param boolean $include_questions clone questions
     * @param object|null $new_course assign new course/homeroom or use current course/homeroom
     * @param boolean $affix put a copy affix or not
     * @param int|null $deadline_year_id  pass school year id if you want the deadline to auto adjust base on school year
     * @return boolean
     */
    public function clone($include_questions = true, $new_course = null, $affix = true, $deadline_year_id = null)
    {
        $assessment = new assessment();
        if ($assessment
            ->setInsert('title', $this->title . ($affix ? '(Copy)' : ''))
            ->setInsert('data', $this->data)
            ->setInsert('type', $this->type)
            ->setInsert('deadline', ($deadline_year_id ? $this->_generateNewDeadline($this->deadline, $deadline_year_id) : $this->deadline))
            ->setInsert('created_by_user_id', \core_user::getCurrentUser()->getID())
            ->setInsert('course_id', $new_course ? $new_course : $this->course_id)
            ->save()
        ) {
            if ($include_questions) {
                $questions = new questions();
                foreach ($questions->getByTeacherAssesId($this->getID()) as $question) {
                    $question->clone($assessment->getID());
                }
            }
            return true;
        }
        return false;
    }

    public function delete()
    {
        return \core_db::runQuery('DELETE FROM yoda_teacher_assessments where id=' . $this->id);
    }
    /**
     * Get Learning log count from the course
     * @param int $course_id
     * @return void
     */
    public static function getLearningLogCount($course_id)
    {
        $sql = "select count(id) as total from yoda_teacher_assessments where course_id=$course_id";
        return \core_db::runGetValue($sql);
    }

    /**
     * getMonthlyLogDates get all group learninglog deadlines
     * @return array
     */
    public static function getMonthlyLogDates()
    {
        $sql = "select DATE_FORMAT(deadline, '%M-%Y') as deadline from yoda_teacher_assessments group by DATE_FORMAT(deadline, '%m-%Y') ORDER BY YEAR(deadline) DESC, MONTH(deadline) DESC";
        return \core_db::runGetValues($sql);
    }
}
