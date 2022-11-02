Optout = new function(){
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
        var cb = this.setCheckbox(studentObj);

        return {
            DT_RowId: 'student-' + studentObj.id,
            cb: cb,
            student_name:studentObj.student_name,
            parent_name: studentObj.parent_name,
            city: studentObj.city,
            grade_level: studentObj.grade_level,
            school: studentObj.school,
            optdate: studentObj.optdate,
            sent: studentObj.sent
        };
    }
  
    this.setCheckbox = function(studentObj){
        return '<input type="checkbox" class="optOutCB" value="' + studentObj.id +'">';
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
            url: '?loaOptout=1',
            data: data,
            method: 'get',
            cache:false,
            dataType: 'json',
            success: function (res) {
                if (res.count === 0) {
                    swal('','Unable to load the students','error');
                    $table.removeClass('waiting');
                    Optout.page = 1;
                    Optout.busy = false;
                    return;
                }

                response = res.filtered;

                if(Optout.resetTable){
                    $DataTable.rows().remove();
                    $DataTable.draw();
                    Optout.resetTable = false;
                }
              
            
    
                $.each(response, function (index, studentObj) {
                    var rowData = Optout.createDataRowObject(studentObj);
                    $DataTable.row.add(rowData);
                });

                $DataTable.draw();

                if(res.count<Optout.pagesize){
                    $table.removeClass('waiting');
                    Optout.page = 1;
                    Optout.busy = false;
                    $DataTable.page(Optout.active_page).responsive.recalc().draw(false);
                }else{
                    Optout.loadStudents(true,data);
                }
            },
            error: function(){
                swal('','Server Error, Unable to load students. Please try again.','error');
            }
        });
    }
}