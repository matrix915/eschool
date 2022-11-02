/**
 * Created by abe on 6/26/17.
 */

(function () {

    var $DataTable = null;
    var $filter_block = null;
    var $homeroom_select = null;

    var grade_selectors = new function(){
        var selected = null;
        var classes= {
            gAll: 'grade_all',
            gKto8: 'grade_k-8',
            g9to12: 'grade_9-12'
        };
        var cbs= {};
        cbs[classes.gAll] = null;
        cbs[classes.gKto8] = null;
        cbs[classes.g9to12] = null;
        var selectable= {};
        selectable[classes.gAll] = null;
        selectable[classes.gKto8] = null;
        selectable[classes.g9to12] = null;

        var busy = true;
        var self = this;


        this.init = function ($filter_block) {
            cbs[classes.gAll] = $filter_block.find('.'+classes.gAll);
            cbs[classes.gKto8] = $filter_block.find('.'+classes.gKto8);
            cbs[classes.g9to12] = $filter_block.find('.'+classes.g9to12);

            var timeout = null;
            var action = function(){
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    self.selectGrades();
                },100);
            };
            $filter_block.find('.grade_selector').change(action).focus(action).click(action);

            selectable[classes.gAll] = $filter_block.find('input[name="grade[]"]');
            selectable[classes.gKto8]= selectable[classes.gAll].filter(function(){
                return this.value === 'OR-K' || this.value === 'K' || this.value<9;
            });
            selectable[classes.g9to12]= selectable[classes.gAll].filter(function(){
                return this.value>=9;
            });
            busy = false;
        };

        this.selectGrades = function(){
            if(busy){
                return;
            }
            busy = true;
            var old_selected = selected;
            selected = null;
            $.each(cbs,function(class_name, $cb){
                if(old_selected!==class_name
                    && $cb.prop('checked')
                ){
                    selected=class_name;
                }else{
                    $cb.prop('checked',false);
                }
            });
            selectable[classes.gAll].prop('checked',false);
            if(selected){
                selectable[selected].prop('checked',true);
            }
            busy = false;
        };
    };

    var getHomerooms = function(){
        $.ajax({
            url:'?ajax=getHomerooms',
            method:'get',
            cache:false,
            success:function (response) {
                $homeroom_select.html('<option></option>');
                if(response===0){
                    swal('','Unable to pull homerooms','warning');
                    return;
                }
                $.each(response.canvas_course_ids,function(index,value){
                    $homeroom_select.append('<option value="'+value+'">'+response.names[index]+'</option>');
                });
            }
        });
    };

    $(function(){

        var $table = $('#homeroom_assignment_table');
        getHomerooms();
        $DataTable = $table.DataTable({
            bStateSave: true,
            bPaginate:false,
            columns: [
                {data:'cb',sortable:false,width:'5%'},
                {data:'cb2',sortable:false,width:'5%'},
                {data:'student',orderData:[0,2]},
                {data:'gender',orderData:[0,3]},
                {data:'grade_level',orderData:[0,4],width:'5%'},
                {data:'location',orderData:[5,0]},
                {data:'homeroom',orderData:[6,0]}
            ],
            aaSorting:[[0,'asc'],[2,'asc']]
        });
        var parent_cbs_created = [];
        var createDataRowObject = function(studentObj){
            var parent_cb_cell = '<span class="hidden">'+studentObj.parent_name+' '+(Number(studentObj.parent_id)+100000000)+'</span>';
            if(parent_cbs_created.indexOf(studentObj.parent_id)<0){
                parent_cb_cell += '<input type="checkbox" class="cb" id="cbSelector'+studentObj.parent_id+'">';
                parent_cbs_created.push(studentObj.parent_id);
            }
            return {
                DT_RowId:'student-'+studentObj.id,
                DT_RowClass:'parent-'+studentObj.parent_id,
                cb: parent_cb_cell,
                cb2: '<input type="checkbox" name="student[]" value="'+studentObj.id+'" class="cb'+studentObj.parent_id+'">',
                student: studentObj.name,
                gender: studentObj.gender,
                grade_level: studentObj.grade_level,
                location: studentObj.city,
                homeroom: studentObj.homeroom
            };
        };

        $filter_block = $('#homeroom_assignment_filter_block');

        grade_selectors.init($filter_block);

        var $selected_soe_year = $table.find('.selected_soe_year');
        var $previous_soe_year = $table.find('.previous_soe_year');
        var $filter_button = $filter_block.find('button');
        var $year_select = $filter_block.find('select[name="school_year_id"]');
        var $year_displays = $('.school_year_display');
        $filter_button.click(function () {
            global_waiting();
            $.ajax({
                url:'?ajax=loadStudents',
                data:$filter_block.find('input, select').serialize(),
                method:'post',
                success:function(response){       
                    if(response===0){
                        swal('','Unable to load the students','warning');
                        global_waiting_hide();
                        return;
                    }
                    $('.student_count_display').html(response.length);
                    parent_cbs_created = [];
                    $DataTable.rows().remove();
                    $.each(response,function(index,studentObj){
                        $DataTable.row.add(createDataRowObject(studentObj));
                    });
                    $DataTable.draw();
                    $table.find('tr').mouseover(function(){
                        var thisClass = $(this).attr('class');
                        if(!thisClass){return;}
                        $('.'+thisClass.replace(/.*(parent\-[0-9]+).*/,'$1')).addClass('partOfFam')
                    }).mouseout(function(){
                        $('.partOfFam').removeClass('partOfFam');
                    });
                    global_waiting_hide();
                }
            });
        });

        var selectFamily = function(parent_id, toggle){
            var checked = true;
            if(selectFamily.selected===undefined){
                selectFamily.selected = [];
            }
            if(toggle){
                checked = !selectFamily.selected[parent_id];
            }
            $('tr.parent-'+parent_id+' input').prop('checked',checked);
            selectFamily.selected[parent_id] = checked;
        };

        $table.click(function(event){
            if(event.target.tagName==='INPUT'){
                $target = $(event.target);
                if($target.hasClass('cb')){
                    selectFamily($target.attr('id').replace('cbSelector',''),true)
                }else if($target.attr('id')==='cbSelector'){
                    var checked = (window.masterCb = !window.masterCb);
                    $('input.cb').each(function(){
                        if(this.checked !== checked){
                            this.click();
                        }
                    });
                }
            }
        });

        $homeroom_select = $('#homeRoomSelect');
        $('#assign').click(function(){
            if(!$homeroom_select.val()){
                swal('','Select a homeroom');
                return false;
            }
            
            var $selected_students = $('input[name="student[]"]:checked');
            if($selected_students.length<1){
                swal('','No students selected','warning');
                return false;
            }
            global_waiting();

            $.ajax({
                url:'?ajax=assignHomeroom',
                data: $selected_students.serialize()
                +'&homeroom_course_id='+$homeroom_select.val(),
                method:'post',
                dataType: 'json',
                success:function (response) {
                    $homeroom_select.val('');
                    if(response.success==0){
                        swal('',response.error,'warning');
                        global_waiting_hide();
                        return;
                    }
                    $filter_button.click();
                }
            });
            return false;
        });

        $('#transfer').click(function(){
            if(!$homeroom_select.val()){
                swal('','Select a homeroom');
                return false;
            }
            var $selected_students = $('input[name="student[]"]:checked');
            if($selected_students.length<1){
                swal('','No students selected','warning');
                return false;
            }
            global_waiting();
            $.ajax({
                url:'?ajax=transferHomeroom',
                data: $selected_students.serialize()
                +'&homeroom_course_id='+$homeroom_select.val(),
                method:'post',
                dataType: 'json',
                success:function (response) {
                    $homeroom_select.val('');
                    if(response.success==0){
                        swal('','There were some errors while setting the homeroom. '+response.error,'warning');
                        global_waiting_hide();
                        return;
                    }
                    $filter_button.click();
                }
            });
            return false;
        });

        var $addHomeroom = null;
        $('#add_homeroom_link').click(function () {
            $('#add_homeroom').modal('show');
            if(!$addHomeroom){
                $addHomeroom = $('#add_homeroom');
              
                $addHomeroom.find('.add-homeroom').click(function () {
                    global_waiting();
                    $.ajax({
                        url:'?ajax=addHomeroom',
                        data: $addHomeroom.find('input').serialize(),
                        method:'post',
                        success:function (response) {
                            $homeroom_select.val('');
                            if(response===0){
                                swal('','There were some errors while adding the homeroom','warning');
                                global_waiting_hide();
                                return;
                            }
                            $filter_button.click();
                            $('#add_homeroom').modal('hide');
                            $addHomeroom.find('input').val('');
                        }
                    });
                });
            }
            return false;
        });
    });

}());