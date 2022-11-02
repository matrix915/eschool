Homeroom = new function(){
    this.data = {};
    this.timeout = 0;
    this.completed = 0;
    this.sending = null;
    this.busy = false;
    this.page = 1;
    this.resetTable = true;
    this.statusCountComplete = false;
    this.pagesize = 250;
    this.active_page = 0;

    this.setPageSize = function(size){
        this.pagesize = size;
    }

    this.createDataRowObject = function (studentObj) {
    
        var notes = this.setNotes(studentObj);
        var cb = this.setCheckbox(studentObj);

        return {
            DT_RowId: 'student-' + studentObj.id,
            arrow: '&nbsp;',
            cb: cb,
            date_assigned:studentObj.date_assigned,
            student_name: '<a href="#" onclick=\'global_popup_iframe("mth_student_learning_logs", "/_/user/learning-logs?student='+ studentObj.id +'")\'>'+studentObj.student_name+'</a>',
            gender: studentObj.gender,
            grade_level: studentObj.grade_level,
            grade: studentObj.grade,
            first_semester_zeros: studentObj.first_semester_zeros,
            second_semester_zeros: studentObj.second_semester_zeros,
            zeros: studentObj.zeros,
            ex: studentObj.ex,
            parent_email: studentObj.pemail,
            parent_phone: studentObj.pphone,
            parent_name: studentObj.parent_name,
            // zeros: studentObj.zero_count,
            // notes: notes
        };
    }
    this.setNotes = function (data) {
        return '<a class="notes" data-homeroom="' + data.homeroom + '" data-student="' + data.id + '">Notes ('+data.notes+')</a>';
    }
    this.setCheckbox = function(studentObj){
        return '<input type="checkbox" class="actionCB" value="' + studentObj.id +'::'+studentObj.parentid+ '::'+studentObj.pemail+'::'+studentObj.homeroom+'">';
     }

    this.loadStudents = function (nextPage,data) {
        var data = data;
        if(this.busy && !nextPage){
            return;
        }

        this.busy = true;
     
        if(nextPage){
            this.page +=1;
            prevpage = 'page='+(this.page-1);
            curpage = 'page='+this.page;
            data = data.replace(prevpage,curpage);
        }else{
            this.page = 1;
            data += '&page=1';
        }

        //reset
        $table.addClass('waiting');
        this.statusCountComplete = false;
        
        $.ajax({
            url: '?loadHomeroom=1',
            data: data,
            method: 'get',
            cache:false,
            dataType: 'json',
            success: function (res) {
                if (res.count === 0) {
                    swal('','No students','info');
                    $table.removeClass('waiting');
                    Homeroom.page = 1;
                    Homeroom.busy = false;
                    Homeroom.resetTable = false;
                    return;
                }

                response = res.filtered;

                if(Homeroom.resetTable){
                    $DataTable.rows().remove();
                    $DataTable.draw();
                    Homeroom.resetTable = false;
                }
              
                $.each(response, function (index, studentObj) {
                    var rowData = Homeroom.createDataRowObject(studentObj);
                    if ( !$DataTable.search(studentObj.student_name).row({search: 'applied'}).data() ) {
                        $DataTable.row.add(rowData);
                    }
                });

                $DataTable.search('').row({search: 'applied'}).data();

                $DataTable.draw();
                $('.student_count_display').text($DataTable.data().length);
             

                if(res.count<Homeroom.pagesize){
                    $table.removeClass('waiting');
                    Homeroom.page = 1;
                    Homeroom.busy = false;
                    $DataTable.page(Homeroom.active_page).responsive.recalc().draw(false);
                }else{
                    Homeroom.loadStudents(true,data);
                }
            },
            error: function(){
                swal('','Server Error, Unable to load students. Please try again.','error');
            }
        });
    }
}