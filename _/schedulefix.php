<?php
require_once 'app/inc.php';

$result = core_db::runQuery('
select * from mth_student_grade_level as y2 
where school_year_id = 7 
    and grade_level = 9
 and exists(
      select * from mth_student_grade_level as y1 
      where y1.student_id=y2.student_id and school_year_id=6 and grade_level=10
)');
if(!$result){
    echo 'Error';
    return;
}
$i = 0;
while ($r = $result->fetch_object()) {
    $i++;
    if($e = core_db::runQuery('update mth_student_grade_level as a set grade_level=11 where student_id='.$r->student_id.' and school_year_id=7')){
        echo $i.':: '.$r->student_id.' :: Updated<br>'; 
    }else{
         echo 'error ::'.$r->student_id;
    }
}
echo $i;