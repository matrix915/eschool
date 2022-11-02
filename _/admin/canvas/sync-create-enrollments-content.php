<?php

if (req_get::bool('course')) {
    $course = mth_course::getByID(req_get::int('course'));
    $role = req_get::int('role') ? req_get::int('role') : mth_canvas_enrollment::ROLE_STUDENT;
    if (!$course) {
        exit('No course found with the ID ' . req_get::int('course'));
    }
    if (($success = mth_canvas_enrollment::createCourseEnrollments($course, $role,null,$course->subject()->inPeriod(1))) === TRUE) {
        exit($course->title() . ' enrollments created');
    } else {
        core_loader::redirect('?course=' . req_get::int('course') . '&role=' . req_get::int('role'));
    }
}

if(req_get::bool('periodfromcourse')){
     $course = mth_course::getByID(req_get::int('periodfromcourse'));
     echo json_encode(mth_canvas_enrollment::getSchedulePeriodIdsFromCourse($course));
     exit;
}
?>
<script>
     var observerCourses = <?=json_encode((array)req_get::int_array('observerCourses'))?>;

     var PeriodFromCourse = {
          get: function(course_id){
               $.ajax({
                    url: '?periodfromcourse='+course_id,
                    dataType: 'JSON',
                    success: function(response){

                    },
                    error: function(){

                    }
               });
          }
     };

     var STUDENTC = {
          role: <?=mth_canvas_enrollment::ROLE_STUDENT?>,
          courses: <?=json_encode((array)req_get::int_array('studentCourses'))?>,
          step: 0,
          start: function(){
               //progressindecator

          },
          get: function(){

          },
          each: function(){
               if(STUDENTC.courses[step] != undefined){
                    STUDENTC.step+=1;
               }else{
                    //nextprocess
               }
          }
     };

     var canvas_progress = {
          onRole: <?=mth_canvas_enrollment::ROLE_STUDENT?>,
          current_course_index: 0,
          start: function(){

          }
     };
</script>