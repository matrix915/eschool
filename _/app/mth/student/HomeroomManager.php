<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/14/17
 * Time: 1:19 PM
 */

namespace mth\student;


use core\Database\PdoAdapterInterface;
use core\Injectable;

class HomeroomManager
{
    use Injectable, Injectable\PdoAdapterFactoryInjector;

    protected $homerooms;

    /** @var  \mth_schoolYear */
    protected $schoolYear;

    /** @var  PdoAdapterInterface */
    protected $studentCourseIdPdoAdapter;

    /**
     * HomeroomManager constructor.
     * @param \mth_schoolYear $schoolYear
     */
    public function __construct(\mth_schoolYear $schoolYear)
    {
        $this->schoolYear = $schoolYear;

        foreach($this->getPdoAdapter()
            ->prepare('SELECT * FROM mth_homeroom 
                            WHERE school_year_id=:school_year_id
                            ORDER BY name')
            ->execute([':school_year_id'=>$schoolYear->getID()])
            ->fetchAll(\PDO::FETCH_ASSOC) as $row
        ){
            $this->homerooms[$row['canvas_course_id']] = $row['name'];
        }
    }

    public function courseIdUsed($canvas_course_id){
        return isset($this->homerooms[$canvas_course_id]);
    }

    public function add($canvas_course_id,$name){
        if($this->courseIdUsed($canvas_course_id)){
            throw new \InvalidArgumentException('$canvas_course_id '.$canvas_course_id.' already used');
        }
        $this->homerooms[$canvas_course_id] = $name;
        $this->getPdoAdapter()
            ->prepare('INSERT INTO mth_homeroom (canvas_course_id, school_year_id, name) 
                VALUES (:canvas_course_id, :school_year_id, :name)')
            ->execute([
                ':canvas_course_id'=>(int)$canvas_course_id,
                ':school_year_id'=>$this->schoolYear->getID(),
                ':name'=>$name
            ]);
    }

    public function getAll()
    {
        return $this->homerooms;
    }

    public function assignToStudent($canvas_course_id, \mth_student $student)
    {
        if(!$this->courseIdUsed($canvas_course_id)){
            throw new \InvalidArgumentException('Invalid $canvas_course_id');
        }
        $this->getPdoAdapter()
            ->prepare('INSERT INTO mth_student_homeroom (student_id, school_year_id, homeroom_canvas_course_id) 
                            VALUES (:student_id, :school_year_id, :homeroom_canvas_course_id1)
                            ON DUPLICATE KEY UPDATE homeroom_canvas_course_id=:homeroom_canvas_course_id2')
            ->execute([
                ':student_id'=>$student->getID(),
                ':school_year_id'=>$this->schoolYear->getID(),
                ':homeroom_canvas_course_id1'=>(int)$canvas_course_id,
                ':homeroom_canvas_course_id2'=>(int)$canvas_course_id
            ]);
    }

    public function getStudentHomeroomCourseId($student_id){
        if(!$this->studentCourseIdPdoAdapter){
            $this->studentCourseIdPdoAdapter = $this->getPdoAdapter()
                ->prepare('SELECT homeroom_canvas_course_id FROM mth_student_homeroom 
                            WHERE school_year_id=:school_year_id
                              AND student_id=:student_id');
        }

        return $this->studentCourseIdPdoAdapter
            ->execute([':school_year_id'=>$this->schoolYear->getID(),':student_id'=>$student_id])
            ->fetch(\PDO::FETCH_COLUMN);
    }

    public function getStudentHomeroomName($student_id)
    {
        $course_id = $this->getStudentHomeroomCourseId($student_id);
        if(!$course_id){ return null; }
        return $this->homerooms[$course_id];
    }

    /**
     * @param array $homeroom_canvas_course_ids
     * @return mixed
     */
    public function getStudentIds(array $homeroom_canvas_course_ids){
        if(empty($homeroom_canvas_course_ids)){
            $homeroom_canvas_course_ids = [-1];
        }else{
            $homeroom_canvas_course_ids = array_map('intval',$homeroom_canvas_course_ids);
        }

        $c = 0;
        $homeroom_canvas_course_ids = array_combine(
            array_map(
                function($key) use (&$c){
                    return ':id'.($c+=1);
                },
                array_keys($homeroom_canvas_course_ids)),
            $homeroom_canvas_course_ids);

        return $this->getPdoAdapter()
            ->prepare(
                'SELECT sh.student_id FROM mth_student_homeroom AS sh
                      INNER JOIN mth_homeroom AS h ON h.canvas_course_id=sh.homeroom_canvas_course_id
                        AND h.school_year_id=sh.school_year_id
                    WHERE sh.school_year_id<=>:school_year_id
                      AND sh.homeroom_canvas_course_id IN ('.implode(',',array_keys($homeroom_canvas_course_ids)).')')
            ->execute(array_merge([':school_year_id'=>$this->schoolYear->getID()],$homeroom_canvas_course_ids))
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

}