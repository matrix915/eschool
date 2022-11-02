<?php
($parent = mth_parent::getByUser()) || core_secure::loadLogin();

// if(req_get::bool('delete_request_value')){
//     if(($req = mth_resource_request::getById(req_get::int('delete_request_value')))){
//         if($req->delete()){
//             core_notify::addMessage('Request deleted.');
//         }else{
//             core_notify::addError('Unable to delete request.');
//         }

//     }else{
//         core_notify::addError('Unable to find request.');
//     }
//     core_loader::redirect();
//     exit;
// }

if (req_get::bool('get_resource_option')) {
    if (!($student = mth_student::getByStudentID(req_get::int('student_id')))) {
        exit;
    }
    $grade_level = $_GET['get_resource_option'] == 'K' ? 0 : ( $_GET['get_resource_option'] == 'OR-K' ? -1 : req_get::int('get_resource_option') );

    $resources = [];
    while ($request = mth_resource_request::getByStudent($student, mth_schoolYear::getCurrent())) {
        $resources[] = $request->resource_id();
    }

    echo '<label>Select Homeroom Resource(s)</label>';
    while ($resource = mth_resource_settings::optionalResources(false,  $grade_level)) {
        if (in_array($resource->getID(), $resources)) {
            continue;
        }
        $cost_str = $resource->showCost() ? ' - $' . $resource->cost() : '';
        echo '<div class="checkbox-primary checkbox-custom"><input class="resource" type="checkbox" name="resource[]" id="res_' . $resource->getID() . '" value="' . $resource->getID() . '"><label for="res_' . $resource->getID() . '">' . $resource->name(null, true) . $cost_str . '</label></div>';
    }
    exit;
}

$year = null;
if (req_get::bool('year')) {
    $year = mth_schoolYear::getByStartYear(req_get::int('year'));
}
if (!$year) {
    ($year = mth_schoolYear::getCurrent()) || die('No year defined');
}

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die('Unable to submit form');

    foreach ($_POST['resource'] as $r) {
        $request = new mth_resource_request();
        $request->parent_id($parent->getID());
        $request->student_id($_POST['student']);
        $request->school_year_id($year->getID());
        $request->resource_id($r);
        $request->save();
        if (($resource = $request->getResource()) && $resource->isDirectDeduction()) {
            $student = mth_student::getByStudentID($_POST['student']);
            $reimbursement = new mth_reimbursement();
            $reimbursement->set_type(mth_reimbursement::TYPE_DIRECT);
            $reimbursement->set_status(mth_reimbursement::STATUS_PAID);
            $reimbursement->set_amount($resource->cost());
            $reimbursement->set_description('Direct Deduction - Homeroom Resource - ' . $resource->name());
            $reimbursement->set_student_year($student, $year);
            $reimbursement->set_at_least_80(true);
            $reimbursement->set_tag_type(NULL);
            $reimbursement->set_resource_request_id($request->getID());

            if (!$reimbursement->doesExist()) {
                $reimbursement->save();
            }
        }
    }
    core_notify::addMessage('Homeroom Account request sent');

    core_loader::redirect();
}
core_loader::includeBootstrapDataTables('css');
cms_page::setPageTitle('Optional Homeroom Resources');
cms_page::setPageContent('<p>The following are optional Homeroom Resources available free to all My Tech High students. Please make sure to follow grade guidelines when requesting the account and complete one form for each student.</p>', 'HomeroomResouceTop', cms_content::TYPE_HTML);
core_loader::printHeader('student');
?>
<div class="page">
    <?= core_loader::printBreadCrumb('window'); ?>
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-6">
                <form id="mth_resource_form" method="post" action="?form=<?= uniqid('mth_requestresource-form') ?>">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                Student Account Request
                            </h4>
                        </div>
                        <div class="card-block">
                            <div class="alert alert-alt alert-info">
                                <?= cms_page::getDefaultPageContent('HomeroomResouceTop', cms_content::TYPE_HTML) ?>

                            </div>

                            <div class="form-group">
                                <label>Select a Student</label>
                                <select class="form-control" name="student" id="select_student">
                                    <option value="0"></option>
                                    <?php foreach ($parent->getStudents() as $student) : ?>
                                        <?php if (!in_array($student->getStatus(), [mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING])) {
                                                continue;
                                            } ?>
                                        <option data-gradelevel="<?= $student->getGradeLevelValue() ?>" value="<?= $student->getID() ?>"><?= $student ?> (<?= $student->getGradeLevel(true) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="resouce-list">
                            </div>

                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary btn-round" type="submit" id="send" style="display:none">Request</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <select onchange="location='?year='+this.value" title="School Year" autocomplete="off" class="form-control">
                            <?php foreach (mth_schoolYear::getSchoolYears() as $each_year) { ?>
                                <option value="<?= $each_year->getStartYear() ?>" <?= $each_year->getID() == $year->getID() ? 'selected' : '' ?>><?= $each_year ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="card-block">
                        <table class="table">
                            <thead>
                                <th>Student</th>
                                <th>Requests</th>
                                <th>Requested</th>
                                <!-- <th></th> -->
                            </thead>
                            <tbody>
                                <?php while ($req = mth_resource_request::get($parent, $year)) : ?>
                                    <?php
                                        $res = $req->getResource();
                                        $cost_str = $res->showCost() ? ' ($' . $res->cost() . ')' : '';
                                        $selected_resource = $res . $cost_str;
                                        ?>
                                    <tr>
                                        <td><?= $req->student() ?></td>
                                        <td>
                                            <?= $selected_resource ?>
                                        </td>
                                        <td>
                                            <?= $req->createDate('m/d/Y') ?>
                                        </td>
                                        <!-- <td>
                                            <a class="btn btn-danger remove-vendor" data-id="<?= $req->getID() ?>" title="Delete" href="#"><i class="fa fa-trash"></i></a>
                                        </td> -->
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
core_loader::includejQueryValidate();
core_loader::printFooter('student');
?>
<script>
    $(function() {
        $('#select_student').change(function() {
            if ($(this).val() == '0') {
                $('.resouce-list').html('');
                $('#send').hide();
                return false;
            }
            var grade_level = $(this).find(':selected').data('gradelevel');
            var student_id = $(this).val();
            $.get('?get_resource_option=' + grade_level + '&student_id=' + student_id, function(data) {
                $('.resouce-list').html(data);
            });
            $('#send').fadeIn();
        });

        // $('.remove-vendor').click(function(){
        //     var req_id = $(this).data('id');
        //     swal({
        //         title: "",
        //         text: "Are you sure you want to delete this request?",
        //         type: "warning",
        //         showCancelButton: !0,
        //         confirmButtonClass: "btn-warning",
        //         confirmButtonText: "Yes",
        //         cancelButtonText: "Cancel",
        //         closeOnConfirm: true,
        //         closeOnCancel: true
        //     },
        //     function () {
        //         location.href = '?delete_request_value=' + req_id
        //     });
        // });

        $('#mth_resource_form').submit(
            function() {
                if ($('.resource:checked').length == 0) {
                    swal('', 'Please select at least 1 homeroom resource');
                    return false;
                }
                return true;
            }
        );
    });
</script>