<?php
namespace mth\yoda;

/**
 * Past Homeroom Records
 * This is the archive/copy holder for courses object
 */
class memcourse extends courses{
    
    public function getGrade($semester = 0)
    {
        $sql = 'select ROUND(avg(ysa.grade),2) as grade from yoda_teacher_assessments as yta
        inner join yoda_courses as yc on yc.id=yta.course_id
        left join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
        inner join mth_student as ms on ms.person_id=ysa.person_id
        where yc.school_year_id=:school_year_id and ms.student_id=:student_id and grade is not null and excused is null and (reset != 3 || reset IS NULL) and 
        ysa.id = (select max(id) from yoda_student_assessments as y2 where person_id=ysa.person_id and assessment_id=ysa.assessment_id)';
      
        return $this->getPdoAdapter()
        ->prepare($sql)
        ->execute([
            ':student_id'=>$this->student_id,
            ':school_year_id'=>$this->school_year_id,
        ])
        ->fetch(\PDO::FETCH_COLUMN);
    }

    public static function getStudentHomeroom($student_id,$schoolYear){
        if(!$student_id){
            return  null;
        }

        $sql = "select yc.*,ms.student_id from yoda_courses as yc
        inner join yoda_teacher_assessments as yta on yc.id=yta.course_id
        inner join yoda_student_assessments as ysa on ysa.assessment_id=yta.id
        inner join mth_student as ms on ms.person_id=ysa.person_id
        where yc.school_year_id={$schoolYear->getID()} and ms.student_id=$student_id order by ysa.id desc limit 1";

        return \core_db::runGetObject($sql,__CLASS__);
    }

}