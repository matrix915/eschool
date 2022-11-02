<?php

/**
 * User: Rex
 * Date: 11/30/17
 * Time: 12:52 AM
 */

namespace mth\yoda\homeroom;

use mth\yoda\courses;

class Query
{

    const PAGE_SIZE = 250;
    const ROLE_STUDENT = 1;
    const STATUS_DELETED = 0;

    protected static $query = 'SELECT */*SELECT*/ 
                                FROM yoda_student_homeroom AS sh
                                INNER JOIN yoda_courses AS h ON h.id=sh.yoda_course_id
                                /*JOIN*/ 
                                WHERE 1/*WHERE*/ 
                                ORDER BY h.id ASC /*LIMIT*/';

    protected $where = [],
        $bind = [], $year = null, $join = [], $select = [];

    public function __construct()
    { }

    /**
     * setGrade unused need to be join by student assessment
     *
     * @param [type] $grade
     * @return void
     */
    public function setGrade($grade = null)
    {
        if (!is_null($grade)) {
            $this->bind[':grade'] = $grade;
            $this->where['grade'] = ($grade < 100) ? "(`grade` is null or `grade` <= :grade)" : "grade=:grade";
        }
        return $this;
    }

    public function selectGrade()
    {
        $sql = str_replace([':student_id', ':school_year_id'], ['sh.student_id', 'sh.school_year_id'], courses::GRADE_STMT);
        $this->select['grade'] = "($sql) as ave_grade";
        return $this;
    }

    public function selectConsecutiveEx()
    {
        $this->select['cex'] = "GET_CONSECUTIVE_EX(sh.student_id,sh.school_year_id) consecutive_ex";
        return $this;
    }

    public function selectZeros()
    {
        $sql = str_replace([':student_id', ':school_year_id'], ['sh.student_id', 'sh.school_year_id'], courses::ALL_ZERO_STMT);
        $this->select['zeros'] = "($sql) as zero_count";
        return $this;
    }

    public function selectFirstSemZeros()
    {
        $sql = str_replace([':student_id', ':school_year_id'], ['sh.student_id', 'sh.school_year_id'], courses::FIRST_SEM_ZERO_STMT);
        $this->select['first_sem_zeros'] = "($sql) as first_sem_zeros";
        return $this;
    }

    public function selectSecondSemZeros()
    {
        $sql = str_replace([':student_id', ':school_year_id'], ['sh.student_id', 'sh.school_year_id'], courses::SECOND_SEM_ZERO_STMT);
        $this->select['second_sem_zeros'] = "($sql) as second_sem_zeros";
        return $this;
    }

    public function selectEx()
    {
        $sql = str_replace([':student_id', ':school_year_id'], ['sh.student_id', 'sh.school_year_id'], courses::EX_STMT);
        $this->select['ex'] = "($sql) as ex_count";
        return $this;
    }

    public function selectGradeLevel($select = 'grade_level')
    {
        $_sql = "select $select from mth_student_grade_level as gl1 where school_year_id=:school_year_id and student_id=:student_id";
        $sql = str_replace([':student_id', ':school_year_id'], ['sh.student_id', 'sh.school_year_id'], $_sql);
        $this->select['grade_level'] = "($sql) as grade_level";
        return $this;
    }

    public function selectSOE($select =  'school_of_enrollment')
    {
        $_sql = "select $select from mth_student_school where school_year_id=:school_year_id and student_id=:student_id";
        $sql = str_replace([':student_id', ':school_year_id'], ['sh.student_id', 'sh.school_year_id'], $_sql);
        $this->select['soe'] = "($sql) as soe";
        return $this;
    }

    public function selectTeacherAssessments()
    {
        $sql = "left join yoda_teacher_assessments AS yta on yta.`course_id`= sh.`yoda_course_id`";
        $this->join['yoda_teacher_assessments'] = $sql;
        $this->select['yoda_teacher_assessments'] = "yta.deadline as deadline";
        return $this;
    }

    public function selectInterventions()
    {
        $this->join['intervention'] = 'left join mth_intervention as mi on mi.mth_student_id=sh.student_id and mi.school_year_id=sh.school_year_id';
        $this->select['intervention'] = '(select count(*) from mth_intervention_notes where intervention_id = mi.intervention_id) as notes_count,
        (select name from mth_label where label_id=mi.label_id) as label';
        return $this;
    }

    public function setGradeLevel(array $gradelevel, $year_id)
    {
        if (!is_null($gradelevel)) {
            $this->join['gradelevel'] = "inner join mth_student_grade_level as msl on msl.student_id=sh.student_id";
            $this->bind[':gradelevel'] = implode(',', $gradelevel);
            $this->where['gradelevel'] = "msl.grade_level in(:gradelevel) and msl.school_year_id=" . $year_id;
        }
        return $this;
    }

    public function setSOE(array $soe, array $year)
    {
        if (!is_null($soe)) {
            $this->join['soe'] = "right join mth_student_school as mss on mss.student_id=sh.student_id";
            $this->bind[':soe'] = implode(',', $soe);
            $this->bind[':school_year_ids'] = implode(',', $year);
            $this->where['soe'] = "mss.school_of_enrollment in(:soe) AND mss.school_year_id in (:school_year_ids)";
        }
        return $this;
    }

    public function selectOffenseNotif()
    {
        $this->select['offense_notif'] = 'mon.date_created as date_sent, mon.type as notif_type,(
            select count(*) from mth_offense_notif as notif where type=mon.type and mth_student_id=mon.mth_student_id and school_year_id=mon.school_year_id
        ) as notif_count';
        $this->join['offense_notif'] = 'left join mth_offense_notif as mon on mon.mth_student_id=sh.student_id and mon.school_year_id=sh.school_year_id';
        $this->where['offense_notif']  = '(mon.offense_id is null or mon.offense_id = (
            select max(offense_id) from mth_offense_notif where mth_student_id=sh.student_id and school_year_id=sh.school_year_id
        ))';

        return $this;
    }

    public function setStatus(array $status, $year_id)
    {
        $this->bind[':status'] = implode(',', $status);
        $this->join['status'] = "inner join mth_student_status as mss on mss.student_id=sh.student_id";
        $this->where['status'] = "mss.status in(:status) and mss.school_year_id=" . $year_id;
        return $this;
    }

    public function setYear(array $year)
    {
        $this->bind[':school_year_ids'] = implode(',', $year);
        $this->where['school_year_ids'] = 'h.school_year_id in (:school_year_ids)';
        return $this;
    }

    public function setHomerom(array $courses)
    {
        $this->bind[':course_ids'] = implode(',', $courses);
        $this->where['course_ids'] = 'yoda_course_id in (:course_ids)';
        return $this;
    }

    public function setTeacher($id)
    {
        $this->bind[':teacher_user_id'] = $id;
        $this->where['teacher_user_id'] = '`teacher_user_id`=:teacher_user_id';
        return $this;
    }


    public function setSchool(array $school)
    {
        $this->bind[':school'] = implode(',', $school);
        $this->where['school'] = 'sh.student_id in(
            select student_id from mth_student_school as mss 
            where mss.school_year_id=sh.school_year_id and mss.school_of_enrollment in(
                :school
            )
        )';
        return $this;
    }

    public function setProvider(array $provider, $year_id)
    {
        $this->bind[':provider'] = implode(',', $provider);
        $this->where['provider'] = 'sh.student_id in (
            select student_id from mth_schedule as ms
            inner join mth_schedule_period as msp on msp.schedule_id=ms.schedule_id
            where status !=99 and school_year_id=' . $year_id . ' and msp.mth_provider_id in(:provider)
        )';
        return $this;
    }

    public function setSped(array $sped)
    {
        $this->bind[':sped'] = implode(',', $sped);
        $this->where['sped'] = 'exists(select 1 from mth_student as student 
        where student.student_id=sh.student_id and special_ed in(:sped))';
        return $this;
    }

    protected function getQuery($page = null, $select = null)
    {
        $tags = ['1/*WHERE*/'];
        $replace = [implode(' AND ', $this->where)];
        if ($page) {
            $tags[] = '/*LIMIT*/';
            $replace[] = 'LIMIT ' . (($page - 1) * self::PAGE_SIZE) . ',' . self::PAGE_SIZE;
        }
        if ($select) {
            $tags[] = '*/*SELECT*/';
            $replace[] = $select;
        }

        if ($this->select) {
            $tags[] = '*/*SELECT*/';
            $replace[] = implode(',', array_merge(['*'], $this->select));
        }


        if (!empty($this->join)) {
            $tags[] = '/*JOIN*/ ';
            $replace[] = implode(' ', $this->join);
        }

        return str_replace($tags, $replace, self::$query);
    }

    /**
     * @param null|int $page
     * @return courses[]
     */
    public function getAll($page = null)
    {
        $query = str_replace(array_keys($this->bind), array_values($this->bind), $this->getQuery($page));
        return \core_db::runGetObjects($query, courses::class);
    }
}
