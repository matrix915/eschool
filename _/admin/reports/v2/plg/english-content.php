<?php

use mth\yoda\homeroom\Query;
use mth\yoda\plgs;

($year = $_SESSION['mth_reports_school_year']) || die('No current year');
$subject = "English";
$file = "$subject PLG";

function getAttr($grade_level,$aggrigate,$subject,$plg_total){
     if(empty($aggrigate)){
          return 'N/A';
     }
     
     if(isset($aggrigate[$subject][$grade_level])){
          return count($aggrigate[$subject][$grade_level])
          .(isset($plg_total[$grade_level])?'/'.($plg_total[$grade_level]):'');
     }
     return 'N/A';
}

function load_homeroom($year,$subject)
{
     $selected_schoolYear = mth_schoolYear::getByID($year);
     $query = new Query();
     $query->setYear([$selected_schoolYear->getID()]);
     $enrollments = $query->getAll(req_get::int('page'));
     $return = [];
     
     $plg_total = plgs::getGradeLevelCountBySubject($subject,$year);

     foreach ($enrollments as $enrollment) {

          if (!$student = $enrollment->student()) {
               continue;
          }

          if ($student->isStatus(mth_student::STATUS_WITHDRAW, $selected_schoolYear)) {
               $enrollment->delete();
               continue;
          }

          $gradelevel = $student->getGradeLevelValue($selected_schoolYear->getID());
          $plgs = $enrollment->populateStudentPLG();
          $data = [
               'id' => $student->getID(),
               'student_lastname' => $student->getPreferredLastName(),
               'student_firstname' => $student->getPreferredFirstName(),
               'grade_level' => $gradelevel,
               'hr_teacher' => ($enrollment->getTeacher() ? $enrollment->getTeacher()->getName() : ''),
               'kindergarten' => getAttr('Kindergarten',$plgs,$subject,$plg_total),
               '1stgrade' => getAttr('1st Grade',$plgs,$subject,$plg_total),
               '2ndgrade' => getAttr('2nd Grade',$plgs,$subject,$plg_total),
               '3rdgrade' => getAttr('3rd Grade',$plgs,$subject,$plg_total),
               '4thgrade' => getAttr('4th Grade',$plgs,$subject,$plg_total),
               '5thgrade' => getAttr('5th Grade',$plgs,$subject,$plg_total),
               '6thgrade' => getAttr('6th Grade',$plgs,$subject,$plg_total),
               '7thgrade' => getAttr('7th Grade',$plgs,$subject,$plg_total),
               '8thgrade' => getAttr('8th Grade',$plgs,$subject,$plg_total),
          ];

          $return[] = $data;
     }

     return ['count'=>count($enrollments),'filtered'=>$return];
}

if (req_get::bool('loadfilter')) {
     $students = load_homeroom(req_get::int('year'),$subject);
     header('Content-type: application/json');
     echo json_encode($students);
     exit();
}


core_loader::includeBootstrapDataTables('css');
core_loader::addCssRef('btndtrcss', 'https://cdn.datatables.net/buttons/1.5.6/css/buttons.dataTables.min.css');
core_loader::isPopUp();
core_loader::printHeader();
?>
<style>
     #report_tbl {
          font-size: 12px;
     }


     .dataTables_filter {
          float: none;
          text-align: left;
     }
</style>
<div class="iframe-actions">
     <button type="button" title="Close" class="btn btn-round btn-secondary"  onclick="top.global_popup_iframe_close('reportPopup')">
          <i class="fa fa-close hidden-md-up"></i><span class="hidden-sm-down">Close</span>
     </button>
 </div>
 <h2><?= $file ?></h2>
<div class="card">
     <div class="card-block pl-0 pr-0">
          <table id="report_tbl" class="table responsive">
               <thead>
                    <tr>
                         <th>Student Last Name</th>
                         <th>Student First Name</th>
                         <th>Grade Level</th>
                         <th>HR Teacher</th>
                         <th>Kindergarten</th>
                         <th>1st Grade</th>
                         <th>2nd Grade</th>
                         <th>3rd Grade</th>
                         <th>4th Grade</th>
                         <th>5th Grade</th>
                         <th>6th Grade</th>
                         <th>7th Grade</th>
                         <th>8th Grade</th>
                    </tr>
               </thead>
               <tbody></tbody>
          </table>
     </div>
</div>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('cdndtbtn', 'https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js');
core_loader::addJsRef('cdndtbtnhtlm5', 'https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js');
core_loader::addJsRef('cdndtbtnflash', 'https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js');
core_loader::addJsRef('lazytable', '/_/teacher/lazytable.js');
core_loader::printFooter();
?>
<script>
     $(function() {
          var $table = $('#report_tbl');
          $DataTable = $table.DataTable({
               columns: [{
                         data: 'student_lastname'
                    },
                    {
                         data: 'student_firstname'
                    },
                    {
                         data: 'grade_level'
                    },
                    {
                         data: 'hr_teacher'
                    },
                    {
                         data: 'kindergarten'
                    },
                    {
                         data: '1stgrade'
                    },
                    {
                         data: '2ndgrade'
                    },
                    {
                         data: '3rdgrade'
                    },
                    {
                         data: '4thgrade'
                    },
                    {
                         data: '5thgrade'
                    },
                    {
                         data: '6thgrade'
                    },
                    {
                         data: '7thgrade'
                    },
                    {
                         data: '8thgrade'
                    },
               ],
               "bStateSave": false,
               "bPaginate": false,
               dom: 'Bfrtip',
               buttons: [{
                    extend: 'csv',
                    text: 'Download CSV',
                    filename: '<?=$file?>',
                    exportOptions: {
                         format: {
                              body: function ( data, row, column, node ) {
                                   // Strip $ from salary column to make it numeric
                                   return column > 3 ? (" "+data):data;
                              }
                         }
                    }
               }]
          });

          var Homeroom = new LazyTable({
                    'student_lastname': 'student_lastname',
                    'student_firstname': 'student_firstname',
                    'grade_level': 'grade_level',
                    'hr_teacher': 'hr_teacher',
                    'kindergarten': 'kindergarten',
                    '1stgrade': '1stgrade',
                    '2ndgrade': '2ndgrade',
                    '3rdgrade': '3rdgrade',
                    '4thgrade': '4thgrade',
                    '5thgrade': '5thgrade',
                    '6thgrade': '6thgrade',
                    '7thgrade': '7thgrade',
                    '8thgrade': '8thgrade',
               },
               $table,
               $DataTable
          );

          Homeroom.load(false, 'year=<?= $year->getID() ?>');
     });
</script>