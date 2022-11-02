<?php
use function GuzzleHttp\json_encode;
$file = 'Mega Report';
$reportHeader = [
     'School Year',
     'Preferred Student First Name',
     'Legal  Student Last Name',
     'Parent Email',
     'Parent First',
     'Parent Last',
     'Parent Phone',
     'City',
     'District',
     'Age',
     'Grade',
     'Gender',
     'School of Enrollment'
];

if(req_get::bool('content')){
     $reportArr = [];
     if($all_sy = mth_schoolYear::getAll()){
          foreach($all_sy as $sy){
               $filter = new mth_person_filter();
               $filter->setStatusYear([$sy->getID()]);
     
               foreach ($filter->getStudents() as $student) {
                    $parent = $student->getParent();
                    $packet = mth_packet::getStudentPacket($student);
     
                    $reportArr[] = [
                         $sy->getName(),
                         $student->getFirstName(),
                         $student->getLastName(),
                         $parent?$parent->getEmail():null,
                         $parent?$parent->getFirstName():null,
                         $parent?$parent->getLastName():null,
                         $parent?($parent->getPhone()?$parent->getPhone()->getName():null):null,
                         $parent?$parent->getCity():null,
                         $packet?$packet->getSchoolDistrict():null,
                         $student->getAge(),
                         $student->getGradeLevelValue($sy->getID()),
                         $student->getGender(),
                         $student->getSOEname($sy,false)
                    ];
               }
          }
     }
     echo json_encode(
          [
               'recordsTotal' =>  count($reportArr),
               'data' => $reportArr
          ]);
     exit;
}

if(req_get::bool('csv') || req_get::bool('google')){
  
     $reportArr = [];
     $reportArr[] = $reportHeader;

     if($all_sy = mth_schoolYear::getAll()){
          foreach($all_sy as $sy){
               $filter = new mth_person_filter();
               $filter->setStatusYear([$sy->getID()]);
     
               foreach ($filter->getStudents() as $student) {
                    $parent = $student->getParent();
                    $packet = mth_packet::getStudentPacket($student);
     
                    $reportArr[] = [
                         $sy->getName(),
                         $student->getFirstName(),
                         $student->getLastName(),
                         $parent?$parent->getEmail():null,
                         $parent?$parent->getFirstName():null,
                         $parent?$parent->getLastName():null,
                         $parent?($parent->getPhone()?$parent->getPhone()->getName():null):null,
                         $parent?$parent->getCity():null,
                         $packet?$packet->getSchoolDistrict():null,
                         $student->getAge(),
                         $student->getGradeLevelValue($sy->getID()),
                         $student->getGender(),
                         $student->getSOEname($sy,false)
                    ];
               }
          }
     }

     include ROOT . core_path::getPath('../report.php');
     exit;
}





core_loader::includeBootstrapDataTables('css');
core_loader::isPopUp();
core_loader::printHeader();
?>
 <style>
        #mth-reports-table {
            font-size: 12px;
        }


        .dataTables_filter {
            float: none;
            text-align: left;
        }
    </style>
   
    <div class="iframe-actions">
    <button type="button"  title="Send to Google" class="btn btn-round btn-secondary" onclick="window.open((location.search?location.search+'&':'?')+'google=1')">
        <i class="fa fa-google hidden-md-up"></i><span class="hidden-sm-down">Send to Google</span>
    </button>
    <button  type="button" title="Download CSV" class="btn btn-round btn-secondary" onclick="location=(location.search?location.search+'&':'?')+'csv=1'">
        <i class="fa fa-download hidden-md-up"></i><span class="hidden-sm-down">Download CSV</span>
    </button>
    <button type="button" title="Close" class="btn btn-round btn-secondary"  onclick="top.global_popup_iframe_close('reportPopup')">
        <i class="fa fa-close hidden-md-up"></i><span class="hidden-sm-down">Close</span>
    </button>
    </div>
    <h2><?= $file ?></h2>
    <p> Items</p>
    <div class="card">
        <div class="card-block pl-0 pr-0">
            <table id="mth-reports-table" class="table responsive">
                <thead>
                    <tr>
                         <?php foreach ($reportHeader as $value): ?>
                         <th><?= $value ?></th>
                         <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter();
?>

<script>
$(function () {
     $('#mth-reports-table').dataTable({
          "processing": true,
          "serverSide": true,
          "bStateSave": false,
          "bPaginate": false,
          ajax: '?content=1'
     });
});
</script>
