<?php

$course_id = &$_SESSION['parent-link-enrollments']['course_id'];
$year = &$_SESSION['parent-link-enrollments']['year_id'];

//should be called by ajax
if (req_get::is_set('course_id')) {
    $course_id = req_get::int('course_id');
    echo $course_id;
    exit();
}

if (req_get::is_set('year_id')) {
    $year = mth_schoolYear::getByID(req_get::int('year_id'));
    echo $year ? $year->getID() : '';
    exit();
}

if ($year === NULL) {
    $year = mth_schoolYear::getNext();
}

$person_ids_setting = core_setting::get('person_ids-' . $course_id . '-' . $year->getID(), 'parent-link-enrollments');
if ($person_ids_setting) {
    $person_ids = explode(';', $person_ids_setting->getValue());
} else {
    $person_ids = array();
}

if (req_get::bool('loadParents')) {
    header('Content-type: application/json');
    $arr = array();
    $filter = new mth_person_filter();
    $filter->setStatusYear(array($year->getID()));
    $filter->setStatus(array(mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE));
    foreach ($filter->getParents() as $parent) {
        /* @var $parent mth_parent */
        $arr[$parent->getPersonID()] = array(
            'name' => $parent->getName(true),
            'email' => $parent->getEmail(),
            'phone' => (string)$parent->getPhone(),
            'city' => $parent->getCity(),
            'enrolled' => in_array($parent->getPersonID(), $person_ids) ? 'Yes' : 'No'
        );
    }
    echo json_encode($arr);
    exit();
}


if (!$year) {
    core_notify::addError('<a href="/_/admin/years">You must create the next school year</a>');
}

cms_page::setPageTitle('Parent Link Enrollment');
cms_page::setPageContent('');
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader('admin');
?>
    
    <style>
        tr.enrolled-Yes td {
            color: #1181DE;
        }
    </style>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                For
                <select id="schoolYearID">
                    <?php while ($eachYear = mth_schoolYear::each()): ?>
                        <option value="<?= $eachYear->getID() ?>"
                            <?= $eachYear->getID() == $year->getID() ? 'selected' : '' ?>><?= $eachYear ?></option>
                    <?php endwhile; ?>
                </select>
            </h3>
        </div>
        <div class="card-block">
            <div class="row">
                <div class="col-md-6">
                    <label for="course_id">Enter the course canvas ID</label>
                    <input type="text" id="course_id" value="<?= $course_id ?>" class="form-control">
                    <small style="display: block">Screen shot illustrates where to find it.</small>
                </div>
                <div class="col-md-6">
                    <img src="/_/admin/parent-link/canvas_course_id_demo.png" class="img-fluid"
                        style="border:solid 3px #666; border-radius:3px;">
                </div>
            </div>
        </div>
        <div class="card-footer bg-info">
        <p>Once you enter the canvas course ID the page will update to show which enrollments have been created. Check the
        boxes of the people you want to create enrollments for and hit the enroll button at the bottom.</p>
        </div>
    </div>
    
   

    
    <div class="card">
        <div class="card-block">
            <table id="mth_parent_link_table" class="table responsive">
                <thead>
                <tr>
                    <th><input type="checkbox" onclick="$('.pcb').prop('checked',window.mcb = !window.mcb)"></th>
                    <th>Parent</th>
                    <th>City</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Enrollment Created</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card-footer">
            <button type="button" class="enrollBut btn btn-primary btn-round" onclick="enrollPersons()" >Enroll</button>
            <button  type="button" class="enrollBut btn btn-pink btn-round" onclick="withdrawPersons()" >Withdraw</button>
        </div>
    </div>
    <p>
        <small>If enrollments need to be recreated which were already created through this tool you will need to run a
            "Flush & Sync" on the <a href="/_/admin/canvas">canvas page</a> first
        </small>
    </p>

<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>

<script>
        var enrollBut, courseID, yearSelect, table, selected_person_ids;
        function loadParents() {
            var loadID = courseID.val() + '-' + yearSelect.val();
            if (loadParents.loading || loadParents.loadedFor === loadID || enrollBut.prop('disabled') || loadParents.wait) {
                return;
            }
            console.log('loadParents called');
            global_waiting();
            loadParents.loading = true;
            loadParents.loadedFor = loadID;
            $.ajax({
                url: '?loadParents=1',
                success: function (data) {
                    table.fnClearTable(false);
                    for (var iID in data) {
                        table.fnAddData([
                            '<input value="' + iID + '" class="pcb enrolled-' + data[iID].enrolled + '" type="checkbox">',
                            data[iID].name,
                            data[iID].city,
                            data[iID].email,
                            data[iID].phone,
                            data[iID].enrolled
                        ], false);
                    }
                    table.fnDraw();
                    loadParents.loading = false;
                    global_waiting_hide();
                    updateClasses();
                }
            });
        }
        function updateClasses() {
            $('.pcb.enrolled-Yes').parents('tr').addClass('enrolled-Yes');
        }
        function getSelectedPersonIDs() {
            selected_person_ids = [];
            $('.pcb:checked').each(function () {
                selected_person_ids.push(this.value);
            });
            if (selected_person_ids.length < 1) {
                swal('','Please select at least one person','warning');
                return false;
            }
            return true;
        }
        function enrollPersons() {
            if (getSelectedPersonIDs()) {
                global_popup_iframe('mth_parent_link_popup', '/_/admin/parent-link/execute');
            }
        }
        function withdrawPersons() {
            if (getSelectedPersonIDs()) {
                global_popup_iframe('mth_parent_link_popup', '/_/admin/parent-link/execute?withdraw=1');
            }
        }

        $(function () {

            table = $('#mth_parent_link_table').dataTable({
                aoColumnDefs: [{"bSortable": false, "aTargets": [0]}],
                bStateSave: true,
                bPaginate: false,
                aaSorting: [[1, 'asc']]
            });

            enrollBut = $('.enrollBut');
            courseID = $('#course_id');
            yearSelect = $('#schoolYearID');

            courseID.keyup(function () {
                loadParents.wait = true;
                clearTimeout(courseID.timmer);
                enrollBut.prop('disabled', true);
                $.ajax({
                    url: '?course_id=' + this.value,
                    success: function (data) {
                        if (data === courseID.val()) {
                            enrollBut.prop('disabled', false);
                        }
                        courseID.timmer = setTimeout(function () {
                            loadParents.wait = false;
                        }, 1000);
                    }
                });
            });
            yearSelect.change(function () {
                loadParents.wait = true;
                clearTimeout(yearSelect.timmer);
                enrollBut.prop('disabled', true);
                $.ajax({
                    url: '?year_id=' + this.value,
                    success: function (data) {
                        if (data === yearSelect.val()) {
                            enrollBut.prop('disabled', false);
                        }
                        yearSelect.timmer = setTimeout(function () {
                            loadParents.wait = false;
                        }, 10);
                    }
                });
            });
            enrollBut.prop('disabled', courseID.val() === '');
            setInterval(loadParents, 500);
        });
    </script>