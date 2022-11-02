<?php

namespace mth\yoda;

use core\Database\PdoAdapterInterface;
use core\Injectable;
use mth\yoda\assessment;
use mth\yoda\studentassessment;
use mth_teacher;

/**
 * Courses class is mainly targeting yoda_courses
 * But on special queries and processes it join related tables
 * like yoda_student_homeroom, studentassessment, assessments
 */
class courses
{
    protected $id;
    protected $name;
    protected $type;
    protected static $cache = array();
    protected $homerooms;
    protected $school_year_id;
    protected $teacher_user_id;

    /**
     * Usable keys when join with yoda_student_homeroom
     *
     */
    protected $date_assigned;
    protected $student_id;
    protected $yoda_course_id;



    /**
     * When *_STMT used as a subquery (eg. Intervention Query)
     * or subqueries from intervention
     * @return any
     */
    protected $ave_grade;
    protected $zero_count;
    protected $ex_count;
    protected $grade_level;
    protected $notif_type;
    protected $date_sent;
    protected $notif_count;
    protected $label;
    protected $notes_count;
    protected $intervention_id;
    protected $label_id;
    protected $soe;
    protected $consecutive_ex;

    public function getAveGrade()
    {
        return $this->ave_grade;
    }
    public function getZeroCount()
    {
        return $this->zero_count;
    }
    public function getFirstSemZeroCount()
    {
        return $this->first_sem_zeros;
    }
    public function getSecondSemZeroCount()
    {
        return $this->second_sem_zeros;
    }
    public function getExCount()
    {
        return $this->ex_count;
    }
    public function notifCount()
    {
        return $this->notif_count;
    }
    public function getGradeLevel()
    {
        return $this->grade_level;
    }
    public function getLatestNotifType()
    {
        return $this->notif_type;
    }
    public function getSOE()
    {
        return $this->soe;
    }
    public function getLatestNotifDateSent($format = NULL)
    {
        return is_null($this->date_sent) ? null : \core_model::getDate($this->date_sent, $format);
    }
    public function getLatestNotifDueDate($format = NULL)
    {
        return \mth_offensenotif::dueDate($this->date_sent, $this->notif_type, $format);
    }
    public function getNotesCount()
    {
        return $this->notes_count;
    }
    public function getLabel()
    {
        return $this->label;
    }
    public function getInterventionId()
    {
        return $this->intervention_id;
    }
    public function getLabelId()
    {
        return $this->label_id;
    }
    public function getConsecutiveEX()
    {
        return $this->consecutive_ex * 1;
    }

    use Injectable, Injectable\PdoAdapterFactoryInjector;

    const HOMEROOM = 1; //homeroom

    /**
     * Statement on getting grades(for students) from every row of the result query
     * when getting student homeroom enrollment
     */
    const GRADE_STMT = 'select ROUND(avg(ysa.grade),2) as grade from yoda_teacher_assessments as yta
    inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
    left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
    inner join mth_student as ms on ms.student_id=ysh.student_id
    where ysh.student_id=:student_id and ysa.person_id=ms.person_id and ysh.school_year_id=:school_year_id
    and grade is not null and excused is null and (reset != 3 || reset IS NULL) and 
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)';
    /**
     * Statement on getting zero count(for students) from every row of the result query
     * when getting student homeroom enrollment
     */
    const ZERO_STMT = 'select count(*) as zeros from yoda_teacher_assessments as yta
    inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
    left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
    inner join mth_student as ms on ms.student_id=ysh.student_id
    where ysh.student_id=:student_id and ysa.person_id=ms.person_id and ysh.school_year_id=:school_year_id
      and deadline > 
        (SELECT
          CASE WHEN CURDATE() > yr.first_sem_learning_logs_close
        THEN (yr.first_sem_learning_logs_close + INTERVAL 1 DAY)
          ELSE yr.date_begin
        END AS zeros_start_date
        FROM (
         SELECT * FROM mth_schoolYear AS yr
         WHERE CURDATE() > yr.date_begin AND CURDATE() < yr.date_end
        ) as yr)
    and deadline <=
        (SELECT
          CASE WHEN CURDATE() > yr.first_sem_learning_logs_close
        THEN yr.date_end
          ELSE (yr.first_sem_learning_logs_close + INTERVAL 1 DAY)
        END AS zeros_end_date
        FROM (
          SELECT * FROM mth_schoolYear AS yr
          WHERE CURDATE() > yr.date_begin AND CURDATE() < yr.date_end
         ) AS yr)
    and grade is not null and excused is null and (reset != 3 || reset IS NULL) and grade=0 and 
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)';

    /**
     * Statement on getting zero count(for students) from every row of the result query
     * when getting student homeroom enrollment
     */
    const ALL_ZERO_STMT = 'select count(*) as zeros from yoda_teacher_assessments as yta
    inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
    left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
    inner join mth_student as ms on ms.student_id=ysh.student_id
    where ysh.student_id=:student_id and ysa.person_id=ms.person_id and ysh.school_year_id=:school_year_id 
    and grade is not null and excused is null and (reset != 3 || reset IS NULL) and grade=0 and 
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)';

    /**
     * Statement on getting first sem zeros count(for students) from every row of the result query
     * when getting student homeroom enrollment
     */
    const FIRST_SEM_ZERO_STMT = 'select count(*) as zeros from yoda_teacher_assessments as yta
    inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
    left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
    inner join mth_student as ms on ms.student_id=ysh.student_id
    where ysh.student_id=:student_id and ysa.person_id=ms.person_id and ysh.school_year_id=:school_year_id
      and deadline >=
        (SELECT 
            yr.date_begin
            AS zeros_start_date
        FROM (
         SELECT * FROM mth_schoolYear AS yr
         WHERE CURDATE() > yr.date_begin AND CURDATE() < yr.date_end
        ) as yr)
    and deadline <=
        (SELECT
            yr.first_sem_learning_logs_close
            AS zeros_end_date
        FROM (
          SELECT * FROM mth_schoolYear AS yr
          WHERE CURDATE() > yr.date_begin AND CURDATE() < yr.date_end
         ) AS yr)
    and grade is not null and excused is null and (reset != 3 || reset IS NULL) and grade=0 and 
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)';

    /**
     * Statement on getting second sem zeros count(for students) from every row of the result query
     * when getting student homeroom enrollment
     */
    const SECOND_SEM_ZERO_STMT = 'select count(*) as zeros from yoda_teacher_assessments as yta
    inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
    left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
    inner join mth_student as ms on ms.student_id=ysh.student_id
    where ysh.student_id=:student_id and ysa.person_id=ms.person_id and ysh.school_year_id=:school_year_id
      and deadline >
        (SELECT 
            yr.first_sem_learning_logs_close
            AS zeros_start_date
        FROM (
         SELECT * FROM mth_schoolYear AS yr
         WHERE CURDATE() > yr.date_begin AND CURDATE() < yr.date_end
        ) as yr)
    and deadline <=
        (SELECT
            yr.date_end
            AS zeros_end_date
        FROM (
          SELECT * FROM mth_schoolYear AS yr
          WHERE CURDATE() > yr.date_begin AND CURDATE() < yr.date_end
         ) AS yr)
    and grade is not null and excused is null and (reset != 3 || reset IS NULL) and grade=0 and 
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)';

    /**
     * Statement on getting excuses count(for students) from every row of the result query
     * when getting student homeroom enrollment
     */
    const EX_STMT = 'select count(*) as ex from yoda_teacher_assessments as yta
    inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
    left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
    inner join mth_student as ms on ms.student_id=ysh.student_id
    where ysh.student_id=:student_id and ysa.person_id=ms.person_id and ysh.school_year_id=:school_year_id
    and excused is not null and 
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)';

    /** @var  PdoAdapterInterface */
    protected $studentCourseIdPdoAdapter;

    public function eachHomeroom()
    {

        return $this->getPdoAdapter()
            ->prepare(
                'SELECT * FROM yoda_courses 
                WHERE type=:type'
            )
            ->execute([':type' => self::HOMEROOM])
            ->fetchAllClass(__CLASS__);
    }

    public function getCourseId()
    {
        return $this->id;
    }



    public function getName()
    {
        return $this->name;
    }

    /**
     * Assign student to a course/homeroom
     * @param int $course_id
     * @param \mth_student $student
     * @param \mth_schoolYear $schoolYear
     * @return bool
     */
    public function assignToStudent($course_id, \mth_student $student, $schoolYear)
    {

        $this->getPdoAdapter()
            ->prepare('INSERT INTO yoda_student_homeroom (student_id, school_year_id, yoda_course_id, date_assigned) 
                            VALUES (:student_id, :school_year_id, :yoda_course_id1, :date_assigned)
                            ON DUPLICATE KEY UPDATE yoda_course_id=:yoda_course_id2,date_assigned=:date_assigned2')
            ->execute([
                ':student_id' => $student->getID(),
                ':school_year_id' => $schoolYear->getID(),
                ':yoda_course_id1' => (int) $course_id,
                ':yoda_course_id2' => (int) $course_id,
                ':date_assigned' => date('Y-m-d H:i:s'),
                ':date_assigned2' => date('Y-m-d H:i:s')
            ]);
    }
    /**
     * Get ALl homerooms by year
     * @param mth_schoolYear $schoolYear
     * @return [courses]
     */
    public static function eachHomeroomByYear($schoolYear = null)
    {
        if (is_null($schoolYear)) {
            $schoolYear = \mth_schoolYear::getCurrent();
        }
        $sql = "select * from yoda_courses where type=" . self::HOMEROOM . " and school_year_id=" . $schoolYear->getID() . " ORDER BY `name`";
        return \core_db::runGetObjects($sql, __CLASS__);
    }

    /**
     * Get all Homerom by year
     * @param  mth_schoolYear $school_year
     * @return array [[course_id=>course_name]]
     */
    public static function getHomeroomsByYear($school_year)
    {
        $response = [];
        foreach (self::eachHomeroomByYear($school_year) as $hr) {
            $response[$hr->getCourseId()] = $hr->getName();
        }
        return $response;
    }

    /**
     * Get Homerooms
     * @return array
     */
    public function getHomerooms()
    {
        $response = [];
        foreach ($this->eachHomeroom() as $hr) {
            $response[$hr->getCourseId()] = $hr->getName();
        }
        return $response;
    }

    /**
     * Get Student ids for homeroom student enrollments
     * @param array $course_ids
     * @param mth_schoolYear $schoolYear
     * @return mixed
     */
    public function getStudentIds(array $course_ids, $schoolYear)
    {
        if (empty($course_ids)) {
            $course_ids = [-1];
        } else {
            $course_ids = array_map('intval', $course_ids);
        }

        $c = 0;
        $course_ids = array_combine(
            array_map(
                function ($key) use (&$c) {
                    return ':id' . ($c += 1);
                },
                array_keys($course_ids)
            ),
            $course_ids
        );

        return $this->getPdoAdapter()
            ->prepare(
                'SELECT sh.student_id FROM yoda_student_homeroom AS sh
                      INNER JOIN yoda_courses AS h ON h.id=sh.yoda_course_id
                    WHERE sh.school_year_id<=>:school_year_id and h.type=' . self::HOMEROOM . '
                      AND sh.yoda_course_id IN (' . implode(',', array_keys($course_ids)) . ')'
            )
            ->execute(array_merge([':school_year_id' => $schoolYear->getID()], $course_ids))
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get Student school year Homeroom name 
     * @param int $student_id
     * @param mixed $schoolYear
     * @return void
     */
    public function getStudentHomeroomName($student_id, $schoolYear)
    {
        $course_id = $this->getStudentHomeroomCourseId($student_id, $schoolYear);

        if (!$course_id) {
            return null;
        }
        $homerooms = self::getHomeroomsByYear($schoolYear);
        return isset($homerooms[$course_id]) ? $homerooms[$course_id] : null;
    }

    /**
     * Get Student Homeroom from school year
     * @param int $student_id
     * @param \mth_schoolYear $schoolYear
     * @return void
     */
    public static function getStudentHomeroom($student_id, $schoolYear)
    {
        if (!$student_id) {
            return  null;
        }

        if (!$schoolYear) {
            return null;
        }

        $sql = "SELECT * FROM yoda_student_homeroom as ysh  
        INNER JOIN  yoda_courses as yc on ysh.yoda_course_id=yc.id
        WHERE ysh.school_year_id=" . $schoolYear->getID() . "
            AND ysh.student_id=$student_id";
        return \core_db::runGetObject($sql, __CLASS__);
    }

    /**
     * Get homeroom/course by id
     * @param int $course_id
     * @return null|courses
     */
    public static function getById($course_id)
    {
        if (!$course_id) {
            return null;
        }

        return \core_db::runGetObject(('select * from yoda_courses where id=' . $course_id), __CLASS__);
    }

    public function getStudentHomeroomCourseId($student_id, $schoolYear)
    {
        return $this->getPdoAdapter()
            ->prepare('SELECT yoda_course_id FROM yoda_student_homeroom 
                        WHERE school_year_id=:school_year_id
                            AND student_id=:student_id')
            ->execute([':school_year_id' => $schoolYear->getID(), ':student_id' => $student_id])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    public static function getTeacherHomerooms($user, $year = null)
    {
        if (!$user) {
            return null;
        }

        $sql = "select * from yoda_courses where type=" . self::HOMEROOM . " and teacher_user_id=" . $user->getID() . ($year ? ' and school_year_id=' . $year->getID() : '') . ' order by school_year_id desc,name';
        return \core_db::runGetObjects($sql, __CLASS__);
    }

    public function student()
    {
        return \mth_student::getByStudentID($this->student_id);
    }

    public function getStudendId()
    {
        return $this->student_id;
    }

    public function getSchoolYearId()
    {
        return $this->school_year_id;
    }

    public function getSchoolYear()
    {
        return \mth_schoolYear::getByID($this->school_year_id);
    }

    public function delete()
    {
        return \core_db::runQuery('DELETE FROM yoda_student_homeroom 
        WHERE student_id=' . (int) $this->student_id . '
          and school_year_id=' . (int) $this->school_year_id . '
           and yoda_course_id=' . (int) $this->yoda_course_id);
    }

    public function deleteCourse()
    {
        return \core_db::runQuery("DELETE FROM yoda_courses where id={$this->id}");
    }
    /**
     * @deprecated
     * Use getTeacherObject instead
     * @return void
     */
    public function getTeacher()
    {
        if (is_null($this->teacher_user_id)) {
            return null;
        }
        return \core_user::getUserById($this->teacher_user_id);
    }

    public function getTeacherUserID()
    {
        return $this->teacher_user_id;
    }

    public function getTeacherObject()
    {
        if (is_null($this->teacher_user_id)) {
            return null;
        }
        return mth_teacher::getByUserID($this->teacher_user_id);
    }

    /**
     * @deprecated version
     * Use getGrade instead
     * @return void
     */
    public function getStudentHomeroomGrade()
    {
        $logcount = 0;
        $gradesum = 0;
        if ($student = $this->student()) {
            foreach (assessment::getLearningLogsByStudent($student) as $key => $log) {
                if (($student_assessment = studentassessment::get($log->getID(), $student->getPersonID()))
                    && !$student_assessment->isExcused()
                    &&  $student_assessment->getGrade() != null
                    //&& $log->isDue()
                ) {
                    //error_log('grade:'.$student_assessment->getGrade().' log:'.$log->getID().' person:'.$student->getPersonID());
                    $gradesum += $student_assessment->getGrade();
                    $logcount++;
                }
            }
            return $logcount == 0 ? null : round(($gradesum / ($logcount * 100)) * 100, 2);
        } else {
            error_log('Unable to find student ' . $this->student_id);
        }

        return $logcount;
    }

    /**
     * Get students grades per homeroom
     *
     * @param integer $semester default is 0 means calculate whole learning log
     * 1 means first sem, 2 means 2nd sem
     * @return mixed
     */
    public function getGrade($semester = 0)
    {

        $sql = self::GRADE_STMT;

        $school_year = \mth_schoolYear::getByID($this->school_year_id);
        $second_sem_open = $school_year->getFirstSemLearningLogsClose('d/m/Y');

        if ($semester === 1) {
            $sql .= "and date(deadline) <= STR_TO_DATE('$second_sem_open', '%d/%m/%Y')";
        } elseif ($semester === 2) {
            $sql .= "and date(deadline) > STR_TO_DATE('$second_sem_open', '%d/%m/%Y')";
        }


        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([
                ':student_id' => $this->student_id,
                ':school_year_id' => $this->school_year_id,
            ])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Get Students zero count per homeroom
     * @return mixed
     */
    public function getZeros()
    {

        $sql = self::ZERO_STMT;

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([
                ':student_id' => $this->student_id,
                ':school_year_id' => $this->school_year_id,
            ])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Get Students zero count per homeroom
     * @return mixed
     */
    public function getFirstSemesterZeros()
    {

        $sql = self::FIRST_SEM_ZERO_STMT;

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([
                ':student_id' => $this->student_id,
                ':school_year_id' => $this->school_year_id,
            ])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Get Students zero count per homeroom
     * @return mixed
     */
    public function getSecondSemesterZeros()
    {

        $sql = self::SECOND_SEM_ZERO_STMT;

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([
                ':student_id' => $this->student_id,
                ':school_year_id' => $this->school_year_id,
            ])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Get Students all zero count per homeroom instead of dynamic from year this is to correct the filter result count
     * @return mixed
     */
    public function getAllZeros()
    {
        $sql = self::ALL_ZERO_STMT;

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([
                ':student_id' => $this->student_id,
                ':school_year_id' => $this->school_year_id,
            ])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Get Students excuses count per homeroom
     * @return void
     */
    public function getExcuses()
    {

        $sql = self::EX_STMT;

        return $this->getPdoAdapter()
            ->prepare($sql)
            ->execute([
                ':student_id' => $this->student_id,
                ':school_year_id' => $this->school_year_id,
            ])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * @deprecated version
     * Use getZeros instead
     * @return void
     */
    public function getStudentHomeroomZeros($semester = null)
    {
        $zerocount = 0;
        if ($student = $this->student()) {
            foreach (assessment::getLearningLogsByStudent($student, null, $semester) as $key => $log) {
                if (($student_assessment = studentassessment::get($log->getID(), $student->getPersonID()))
                    && !$student_assessment->isExcused()
                    && ($student_assessment->getGrade() != null && $student_assessment->getGrade() == 0 && !$student_assessment->isNA())
                    //&& $log->isDue()
                ) {
                    $zerocount++;
                }
            }
            return $zerocount;
        } else {
            error_log('Unable to find student ' . $this->student_id);
        }
    }

    public function populateStudentPLG()
    {
        $_sql = "select yaa.`data` as data,plg_subject as subject from yoda_teacher_assessments as yta
        inner join yoda_student_homeroom  as ysh on yta.course_id=ysh.yoda_course_id
        left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
        left join yoda_assessment_answers as yaa on yaa.yoda_student_asses_id=ysa.id
        inner join yoda_assessment_question as yaq on yaq.id=yaa.yoda_assessment_question_id
        inner join mth_student as ms on ms.student_id=ysh.student_id
        where ysh.student_id=:student_id and ysa.person_id=ms.person_id and ysh.school_year_id=:school_year_id
        and yaa.type = " . questions::PLG . "
        and grade is not null and excused is null and (ysa.`reset` IS NULL OR ysa.`reset` = " . studentassessment::RESUBMITTED . ") AND
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)
        order by plg_subject";
        $sql = str_replace([':student_id', ':school_year_id'], [$this->student_id, $this->school_year_id], $_sql);

        $result = \core_db::runQuery($sql);
        if (!$result) {
            error_log('Error in filter query: ' . print_r($this, true));
            return;
        }
        $subjects = [];
        while ($r = $result->fetch_object()) {
            if ($r->data) {
                $answer = (json_decode($r->data))->answer;
                $grade_level = (json_decode($r->data))->grade_level;

                if (!$answer || !$grade_level) {
                    continue;
                }

                if (!isset($subjects[$r->subject])) {
                    $subjects[$r->subject] = [];
                }

                if (!isset($subjects[$r->subject][$grade_level])) {
                    $subjects[$r->subject][$grade_level] = [];
                }
                $subjects[$r->subject][$grade_level] = array_unique(array_merge($subjects[$r->subject][$grade_level], $answer));
            }
        }
        $result->free_result();
        return $subjects;
    }

    /**
     * Hry Date student assigned to a homeroom
     * @param string|null $format
     * @return null|mixed
     */
    public function getDateAssigned($format = null)
    {
        return  $format ? date($format, strtotime($this->date_assigned)) : $this->date_assigned;
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

    /**
     * Set fields to be inserted
     * @param string $field field name
     * @param mixed $value field value
     * @return void
     */
    public function setInsert($field, $value = null)
    {
        if (is_null($value)) {
            $this->insertQueries[$field] = 'NULL';
        } else {
            $this->insertQueries[$field] = '"' . \core_db::escape($value) . '"';
        }
    }

    /**
     * Save changes from set|setInsert
     * @return mixed
     */
    public function save()
    {
        if (!empty($this->updateQueries)) {
            $this->set('updated_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('UPDATE yoda_courses SET ' . implode(',', $this->updateQueries) . ' WHERE id=' . $this->getCourseId());
        } else {
            $this->setInsert('created_at', date('Y-m-d H:i:s'));
            $success = \core_db::runQuery('INSERT INTO yoda_courses(' . implode(',', array_keys($this->insertQueries)) . ') VALUES(' . implode(',', $this->insertQueries) . ')');
            $this->id = \core_db::getInsertID();
        }

        return $success;
    }

    /**
     * get Homeroom student count per school year
     * @param int $school_year_id
     * @return mixed
     */
    public static function getStudentCount($school_year_id)
    {
        $sql =   "select count(student_id) from yoda_student_homeroom where school_year_id=$school_year_id";
        return \core_db::runGetValue($sql);
    }

    /**
     * get homeroom student count per teacher and school year
     * @param int $school_year_id
     * @param int $teacher_user_id
     * @return void
     */
    public static function getTeacherStudentCount($school_year_id, $teacher_user_id)
    {
        $sql =   "select count(*) as total_student from yoda_courses as yc
        left join yoda_student_homeroom as ysh on yc.id=ysh.yoda_course_id
        where teacher_user_id=$teacher_user_id and yc.school_year_id=$school_year_id and ysh.school_year_id=$school_year_id";

        return \core_db::runGetValue($sql);
    }

    public function getImportedPLGs()
    {
        $sql = "SELECT yaq.*  FROM yoda_assessment_question AS yaq
          INNER JOIN  yoda_teacher_assessments as yta ON yaq.yoda_teacher_asses_id=yta.id
          WHERE course_id=" . $this->getCourseId() . " AND (yaq.`type`= " . questions::PLG . " OR yaq.`type` = " . questions::PLG_INDEPENDENT . ")
          GROUP BY yaq.data";
        return \core_db::runGetObjects($sql, questions::class);
    }

    /**
     * Get Student ids for homeroom student enrollments
     * @param array $course_ids
     * @return mixed
     */
    public function getStudentIdsByCourseIds(array $course_ids)
    {
        if (empty($course_ids)) {
            $course_ids = [-1];
        } else {
            $course_ids = array_map('intval', $course_ids);
        }

        $c = 0;
        $course_ids = array_combine(
            array_map(
                function ($key) use (&$c) {
                    return ':id' . ($c += 1);
                },
                array_keys($course_ids)
            ),
            $course_ids
        );

        return $this->getPdoAdapter()
            ->prepare(
                'SELECT sh.student_id FROM yoda_student_homeroom AS sh
                      INNER JOIN yoda_courses AS h ON h.id=sh.yoda_course_id
                    WHERE h.type=' . self::HOMEROOM . '
                      AND sh.yoda_course_id IN (' . implode(',', array_keys($course_ids)) . ')'
            )
            ->execute(array_merge($course_ids))
            ->fetchAll(\PDO::FETCH_COLUMN);
    }
}
