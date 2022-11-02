<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 9/8/17
 * Time: 1:27 PM
 */

namespace mth\student\SchoolOfEnrollment;


use core\Injectable;
use core\Injectable\PdoAdapterFactoryInjector;

class Query
{
    use Injectable, PdoAdapterFactoryInjector;

    public function getStudentIdsWhoTransferredSoE(\mth_schoolYear $year){
        if(!($previous_year = $year->getPreviousYear())){
            return [];
        }
        return $this->getPdoAdapter()
            ->prepare('SELECT ss1.student_id 
                      FROM mth_student_school AS ss1
                        INNER JOIN mth_student_school AS ss2
                          ON ss2.student_id=ss1.student_id
                      WHERE (ss2.school_of_enrollment != ss1.school_of_enrollment
                            AND ss2.school_year_id = :previous_year_id
                            AND ss2.school_of_enrollment != 0) 
                                AND ss1.school_year_id = :year_id 
                                OR (ss1.transferred = 1 AND ss2.school_year_id = :ss2_year_id)')
            ->execute([
                ':previous_year_id'=>$previous_year->getID(),
                ':year_id'=>$year->getID(),
                ':ss2_year_id' => $year->getID()
            ])
            ->fetchAll(\PDO::FETCH_COLUMN);
    }
}