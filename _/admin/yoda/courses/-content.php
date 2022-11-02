<?php

use mth\yoda\course\Query;
use mth\yoda\courses;

/**
 * check if $_REQUEST param is set
 *
 * @param string $param param name
 * @param string $type  method
 * @return int|array|string
 */
function req_isset($param, $type)
{
     if (!(req_post::is_set($param) || req_get::is_set($param))) {
          return null;
     }

     $method = req_post::is_set($param) ? 'post' : 'get';

     return  call_user_func(array("req_$method", $type), $param);
}

function load_courses()
{
     $_years = req_isset('years', 'int_array');
     $query = new Query();
     $query->setYear($_years);

     $courses = $query->getAll(req_get::int('page'));
     $return = [];
     foreach ($courses as $course) {
          $data = [
               'title' => $course->getName(),
               'teacher' => $course->getTeacherObject() ? $course->getTeacherObject()->getName() : 'NA',
               'year' => $course->getSchoolYear()?$course->getSchoolYear()->getName():'',
               'id' => $course->getCourseId()
          ];

          $return[] = $data;
     }

     return ['count' => count($courses), 'filtered' => $return];
}

if (req_get::bool('loadfilter')) {
     $courses = load_courses();
     header('Content-type: application/json');
     echo json_encode($courses);
     exit();
}

if(req_get::bool('del')){
     $response = [
          'error' => 1,
          'data' => 'Unable to find homeroom.'
     ];
     if($course = courses::getById(req_get::int('del'))){
          if($success = $course->deleteCourse()){
               $response  = [
                    'error' => 0,
                    'data' => req_get::int('del')
               ];
          }else{
               $response  = [
                    'error' => 1,
                    'data' => 'Unable to delete homeroom.'
               ];
          }
     }
     header('Content-type: application/json');
     echo json_encode($response);
     exit();
}

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Courses');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
<div class="card container-collapse">
     <div class="card-header">
          <h4 class="card-title mb-0" data-toggle="collapse" href="#intervention-filter-cont" aria-controls="intervention-filter-cont">
               <i class="panel-action icon md-chevron-down icon-collapse"></i> Filter
          </h4>
     </div>
     <div class="card-block collapse info-collapse show" id="intervention-filter-cont">
          <div class="row" id="filter_form">
               <div class="col-md-4">
                    <fieldset>
                         <legend>Years</legend>
                         <?php foreach (mth_schoolYear::getSchoolYears() as $year) : /* @var $year mth_schoolYear */ ?>
                              <div class="checkbox-custom checkbox-primary">
                                   <input type="checkbox" name="years[]" value="<?= $year->getID() ?>" <?= mth_schoolYear::getCurrent()->getID() == $year->getID() ? 'CHECKED' : '' ?>>
                                   <label><?= $year ?></label>
                              </div>
                         <?php endforeach; ?>
                    </fieldset>
               </div>

          </div>
          <hr>
          <button id="do_filter" class="btn btn-round btn-primary">Load</button>
     </div>
</div>
<div class="card">
     <div class="card-header">
          <button class="btn btn-success btn-round" id="add-course" onclick="global_popup_iframe('course_popup','/_/admin/yoda/courses/edit')">Add Course</button>
     </div>
     <div class="card-block">
          <table class="table responsive" id="courses_tbl">
               <thead>
                    <th>Title</th>
                    <th>Teacher</th>
                    <th>Year</th>
                    <th>Actions</th>
               </thead>
               <tbody>

               </tbody>
          </table>
     </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('lazytable', '/_/teacher/lazytable.js');
core_loader::printFooter('admin');
?>
<script>
     $DataTable = null;
     var PAGE_SIZE = <?= Query::PAGE_SIZE ?> ;

     function updateTable(){
          $('#do_filter').trigger('click');
     }

     function cloner(course_id){
          swal({
                title: '',
                text: 'Are you sure you want to clone this homeroom?',
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            },function(){
               global_popup_iframe('course_popup','/_/admin/yoda/courses/clone?course='+course_id);
            });
     }

     function deleteCourse(course_id){
          swal({
                title: '',
                text: 'Are you sure you want to delete this homeroom?',
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            },function(){
               $.ajax({
                    url:'?del='+course_id,
                    dataType:'JSON',
                    success: function(response){
                         if(response.error == 1){
                              swal('',respose.data,'error');
                         }else{
                              swal('','Homeroom deleted','success');
                              updateTable();
                         }
                    }
               });
            });
     }

     function importer(course_id){
          global_popup_iframe('import_popup','/_/admin/yoda/courses/import?course='+course_id);
     }

     $(function() {
          var $filter = $('#do_filter');
          var $form = $('#filter_form');

          $table = $('#courses_tbl');
          $DataTable = $table.DataTable({
               //bStateSave: true,
               pageLength: 25,
               columns: [{
                         data: 'title'
                    },
                    {
                         data: 'teacher'
                    },
                    {
                         data: 'year'
                    },
                    {
                         data: 'actions'
                    },
               ],
               aaSorting: [
                    [0, 'asc']
               ],
               'columnDefs': [ {
                    'targets': [3], /* column index */
                    'orderable': false, /* true or false */
               }],
               iDisplayLength: 25
          });

          var Homerooms = new LazyTable({
                    'title': 'title',
                    'teacher': 'teacher',
                    'year': 'year',
                    'actions': function(obj) {
                         return '<button class="btn btn-success mt-5" onclick="global_popup_iframe(\'course_popup\',\'/_/admin/yoda/courses/edit?course='+obj.id+'\')">Edit</button>' +
                              '&nbsp;<button class="btn btn-info mt-5" onclick="cloner('+obj.id+')">Clone</button>' +
                              '&nbsp;<button class="btn btn-secondary mt-5" onclick="importer('+obj.id+')" title="Import Checklist"><i class="fa fa-upload"></i></button>'+
                              '&nbsp;<button class="btn btn-warning mt-5" onclick="global_popup_iframe(\'mth_student_learning_logs\',\'/_/admin/yoda/courses/homeroom?course='+obj.id+'\')">Learning Logs</button>' +
                              '&nbsp;<button class="btn btn-pink mt-5" onclick="global_popup_iframe(\'mth_student_learning_logs\',\'/_/admin/yoda/courses/reminder?course='+obj.id+'\')" title="Remind unsubmitted logs"><i class="fa fa-bell"></i></button>' +
                              '&nbsp;<button class="btn btn-danger mt-5" onclick="deleteCourse('+obj.id+')">Delete</button>';
                    }
               },
               $table,
               $DataTable
          );

          Homerooms.setPageSize(PAGE_SIZE);

          $filter.click(function() {
               Homerooms.resetTable = true;
               Homerooms.active_page = ($DataTable.page.info()).page;
               var data = $form.find('input,select').serialize();
               Homerooms.load(false, data);
          });

          updateTable();
     });
</script>