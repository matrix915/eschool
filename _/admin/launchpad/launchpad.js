var LaunchpadTable = new function () {
    this.data = {};
    this.pagesize = 250;
    this.active_page = 0;
    this.timeout = 0;
    this.completed = 0;
    this.sending = null;
    this.busy = false;
    this.page = 1;
    this.resetTable = true;
    this.statusCountComplete = false;
    this.fullCount = 0;
    this.setCurrentYear = false;
    this.setNextYear = false;

    this.setPageSize = function (size) {
        this.pagesize = size;
    }

    this.createDataRowObject = function (courseObj, mthItem) {
        return {
            sparkID: courseObj.sparkID,
            provider: courseObj.provider,
            course: courseObj.course,
            launchpadCourse: courseObj.launchpadCourse,
            semester1: courseObj.semester1,
            semester2: courseObj.semester2
        }
    }

    this.setCheckbox = function (id) {
        return '<input type="checkbox" name="applications[]" value="' + id + '" class="applicationCB" type="checkbox">';
    }

    this.setAction = function (id) {
        return '<a onclick="deleteApplication(' + id + ')">Delete</a> <a onclick="showEditForm(' + id + ')">Edit</a>';
    }

    this.setPersonLink = function (type, id, name) {
        return '<a onclick="global_popup_iframe(\'mth_people_edit\',\'/_/admin/people/edit?' + type + '=' + id + '\')">' + name + '</a>';
    }

    this.loadCourse = function () {
        $table.addClass('waiting');
        this.statusCountComplete = false;
        $.ajax({
            url: '?get_launchpad_course=1',
            method: 'get',
            cache: false,
            dataType: 'json',
            success: function (response) {
                console.log({response})
                if (response.status !== 'success') {
                    swal('', 'Unable to load courses', 'error');
                    $table.removeClass('waiting');
                    LaunchpadTable.page = 1;
                    return;
                }

                var courses = response.courses;
                if (LaunchpadTable.resetTable) {
                    $DataTable.rows().remove();
                    $DataTable.draw();
                    LaunchpadTable.resetTable = false;
                }
                var counting = 0
                let rowData;
                $.each(courses, function (index, courseObj) {
                    rowData = LaunchpadTable.createDataRowObject(courseObj);
                    $DataTable.row.add(rowData);
                })

                $DataTable.draw();

                $table.removeClass('waiting');
                LaunchpadTable.page = 1;
                LaunchpadTable.busy = false;
                $DataTable.page(LaunchpadTable.active_page).responsive.recalc().draw(false);

            },
            error: function () {
                swal('', 'Unable to load course', 'error');
            }
        })
    }
}