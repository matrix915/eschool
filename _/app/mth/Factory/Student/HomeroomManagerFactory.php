<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 8/14/17
 * Time: 4:24 PM
 */

namespace mth\Factory\Student;


use mth\student\HomeroomManager;

class HomeroomManagerFactory
{
    /**
     * @param \mth_schoolYear $schoolYear
     * @return HomeroomManager
     */
    public function getHomeroomManager(\mth_schoolYear $schoolYear){
        return new HomeroomManager($schoolYear);
    }
}