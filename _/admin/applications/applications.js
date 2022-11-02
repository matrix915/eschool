var ApplicationTable = new function () {
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

    this.setPageSize = function(size) {
        this.pagesize = size;
    }

    this.createDataRowObject = function (applicationObject) {
        var checkBox = this.setCheckbox(applicationObject.id);
        var student = this.setPersonLink('student',applicationObject.student_id, applicationObject.student_name);
        var parent = this.setPersonLink('parent',applicationObject.parent_id, applicationObject.parent_name);
        var actions = this.setAction(applicationObject.id);

        return {
            DT_RowId: 'application-' + applicationObject.id,
            DT_RowClass: 'mth_application-' + applicationObject.status['class'],
            cb: checkBox,
            submitted: applicationObject.date_submitted,
            year: applicationObject.school_year,
            student: student,
            grade_level: applicationObject.grade_level + ' (' + applicationObject.school_year + ')',
            diploma: applicationObject.diploma,
            special_ed: applicationObject.special_ed,
            parent: applicationObject.parent_name ? parent : 'No Parent',
            city: applicationObject.city,
            status: applicationObject.status.name,
            verified: applicationObject.verified ? 'Yes' : 'No',
            actions: actions,
        }
    }

    this.setCheckbox = function(id){
        return '<input type="checkbox" name="applications[]" value="' + id + '" class="applicationCB" type="checkbox">';
    }

    this.setAction = function(id){
        return '<a onclick="deleteApplication(' + id + ')">Delete</a> <a onclick="showEditForm(' + id + ')">Edit</a>';
    }

    this.setPersonLink = function(type, id, name){
        return '<a onclick="global_popup_iframe(\'mth_people_edit\',\'/_/admin/people/edit?' + type + '=' + id + '\')">' + name + '</a>';
    }

    this.loadApplications = function (nextPage, data, appCount=0) {
        this.data = data;
        if(this.busy && !nextPage) {
            return;
        }

        this.busy = true;
        if(nextPage) {
            this.page += 1;
            var previousPage = 'page='+(this.page-1);
            var currentPage = 'page='+this.page;
            data = data.replace(previousPage, currentPage);
        } else {
            this.page=1;
            data += '&page=1';
        }

        $table.addClass('waiting');
        this.statusCountComplete = false;

        $.ajax({
            url: '?loadApplications=1',
            data: data,
            method: 'get',
            cache: false,
            dataType: 'json',
            success: function (response) {
                if(response.count === 0) {
                    swal('', 'Unable to load applications', 'error');
                    $table.removeClass('waiting');
                    ApplicationTable.page = 1;
                    ApplicationTable.busy = false;
                    return;
                }

                var applications = response.applications;

                if(ApplicationTable.resetTable) {
                    $DataTable.rows().remove();
                    $DataTable.draw();
                    ApplicationTable.resetTable = false;
                }
                var counting = 0
                $.each(applications, function (index, applicationObj) {
                    var rowData = ApplicationTable.createDataRowObject(applicationObj);
                    ApplicationTable.setNextYear = ApplicationTable.setNextYear || applicationObj.school_year == response.current_year_id;
                    ApplicationTable.setCurrentYear = ApplicationTable.setCurrentYear || applicationObj.school_year == response.next_year_id;
                    $DataTable.row.add(rowData);
                })

                $DataTable.draw();

                if(response.count < ApplicationTable.pagesize) {
                    $table.removeClass('waiting');
                    ApplicationTable.page = 1;
                    ApplicationTable.busy = false;
                    $DataTable.page(ApplicationTable.active_page).responsive.recalc().draw(false);
                    $('#application_count').text(appCount + response.count);
                    if(ApplicationTable.setNextYear) {
                        $('#moveToNextYear').show();
                    }
                    if(ApplicationTable.setCurrentYear) {
                        $('#moveToCurYear').show();
                    }
                } else {
                    ApplicationTable.loadApplications(true, data, appCount+response.count);
                }
            },
            error: function () {
                swal('', 'Unable to load applications', 'error');
            }
        })
    }
}