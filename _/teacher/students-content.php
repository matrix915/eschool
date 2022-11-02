<?php
use mth\yoda\courses;
use mth\yoda\homeroom\Query;

$current_year = mth_schoolYear::getCurrent();

/**
 * check if $_REQUEST param is set
 *
 * @param string $param param name
 * @param string $type  method
 * @return int|array|string
 */
function req_isset($param,$type){
    if(!(req_post::is_set($param) || req_get::is_set($param))){
        return null;
    }

    $method = req_post::is_set($param)?'post':'get';
   
    return  call_user_func(array("req_$method",$type),$param);
}

function load_homeroom($year){
    $_gradelevel = req_isset('grade','int_array');
    $selected_schoolYear = mth_schoolYear::getByID($year);
    $query = new Query();
    $query->setYear([$selected_schoolYear->getID()]);
    $query->setTeacher(core_user::getCurrentUser()->getID());

    if($_gradelevel){
        $query->setGradeLevel($_gradelevel,$selected_schoolYear->getID());
    }
   
    $enrollments = $query->getAll(req_get::int('page'));
    $return = [];
    foreach($enrollments as $enrollment){
        
        if(!$student = $enrollment->student()){
            continue;
        }

        if($student->isStatus(mth_student::STATUS_WITHDRAW, $selected_schoolYear)){
            $enrollment->delete();
            continue;
        }

        $gradelevel = $student->getGradeLevelValue($selected_schoolYear->getID());
       
        $data = [
            'student_name' => $student->getPreferredLastName().', '.$student->getPreferredFirstName(),
            'grade_level' => $gradelevel,
            'id'=> $student->getID(),
            'slug' => $student->getSlug(),
            'homeroom_id' => $enrollment->getCourseId(),
            'homeroom' => $enrollment->getName(),
            'sy'=>$selected_schoolYear->getName()
        ];

        $return[] = $data;
    }

    return ['count'=>count($enrollments),'filtered'=>$return];
}

if(req_get::bool('loadfilter')){
    $students = load_homeroom(req_get::int('year'));
    header('Content-type: application/json');
    echo json_encode($students);
    exit();
}

core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Students');
cms_page::setPageContent('');
core_loader::printHeader('teacher');
?>
<div class="card container-collapse">
    <div class="card-header">
        <h4 class="card-title mb-0"  data-toggle="collapse"  href="#intervention-filter-cont" aria-controls="intervention-filter-cont">
            <i class="panel-action icon md-chevron-down icon-collapse" ></i> Filter
        </h4>
    </div>
    <div class="card-block collapse info-collapse show" id="intervention-filter-cont">
        <div class="row" id="filter_form">
            <div class="col-md-6">
                <fieldset class="block grade-levels-block">
                    <legend>Grade Level</legend>

                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="grade_selector" value="gAll">
                        <label>
                            All Grades
                        </label>
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="grade_selector" value="gKto8">
                        <label>
                            Grades OR K-8
                        </label>
                    </div>
                    <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" class="grade_selector" value="g9to12">
                        <label>
                            Grades 9-12
                        </label>
                    </div>
                    
                    <hr>
                    <div class="grade-level-list">
                    <?php foreach(mth_student::getAvailableGradeLevelsNormal() as $grade => $name){ ?>
                        <div class="checkbox-custom checkbox-primary">
                        <input type="checkbox" name="grade[]" value="<?=$grade?>">
                        <label>
                            <?=$name?>
                        </label>
                        </div>
                        <?php } ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-4">
                <fieldset>
                    <legend>Years</legend>
                    <select name="year" class="form-control">
                    <?php foreach (mth_schoolYear::getSchoolYears() as $year): /* @var $year mth_schoolYear */ ?>
                        <option <?=mth_schoolYear::getCurrent()->getID() == $year->getID()?'SELECTED':''?> value="<?=$year->getID()?>">
                        <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </fieldset>
            </div>
            
        </div>
        <hr>
        <button id="do_filter" class="btn btn-round btn-primary">Load</button> 
    </div>
</div>
<div class="card">
    <div class="card-header">
        Total Students: <span class="student_count_display"></span>
    </div>
    <div class="card-block pl-0 pr-0">
        <table id="homeroom_table" class="table responsive">
            <thead>
                <tr>
                    <th>Student Last Name, First Name</th>
                    <th>Grade Level</th>
                    <th>Homeroom</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::addJsRef('lazytable','/_/teacher/lazytable.js');
core_loader::addJsRef('gradeleveltool',core_config::getThemeURI().'/assets/js/gradelevel.js');
core_loader::printFooter('admin');
?> 
<script>
    $DataTable = null;
    var PAGE_SIZE = <?= Query::PAGE_SIZE?>;
   
    
    $(function(){
        var $filter = $('#do_filter');
        var $form = $('#filter_form');

        $table = $('#homeroom_table');
        $DataTable = $table.DataTable({
            //bStateSave: true,
            pageLength: 25,
            columns: [
                { data: 'student_name' },
                { data: 'grade_level'},
                { data: 'homeroom' },
            ],
            aaSorting: [[0, 'desc']],
            iDisplayLength: 25
        });

        var Homeroom = new LazyTable(
            {
                'student_name':'student_name',
                'grade_level':'grade_level',
                'homeroom': function(obj){
                    return '<a href="#" onclick=\'global_popup_iframe("mth_student_learning_logs", "/_/teacher/homeroom?st='+ obj.id +'&hr='+obj.homeroom_id+'")\'>'+obj.homeroom+'</a>';
                }
            },
            $table,
            $DataTable
        );

        Homeroom.setPageSize(PAGE_SIZE);

        $filter.click(function () {
            Homeroom.resetTable = true;
            Homeroom.active_page = ($DataTable.page.info()).page;
            var data = $form.find('input,select').serialize();
            Homeroom.load(false,data);
        });

        $filter.trigger('click');
     });
</script>