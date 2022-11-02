/**
 * Created by abe on 6/26/17.
 */

(function () {

    var $DataTable = null;
    var $filter_block = null;
    var $missing_data_block = null;

    var checkForMissingData = function(){
        //return; //Disabled function. Not checking right now.
        if(checkForMissingData.busy){
            return;
        }
        checkForMissingData.busy = true;
        $missing_data_block.addClass('waiting');
        $.ajax({
            url:'?ajax=checkMissing',
            method:'get',
            cache:false,
            success:function(response){
                if(!response.length){
                    $missing_data_block.hide();
                    checkForMissingData.busy = false;
                    return;
                }
                $ul = $missing_data_block.find('.missing-container');
                $ul.html('');

                var students = response.sort(function (a, b) {
                    return ('' + a.name).localeCompare(b.name);
                });

                $.each(students, function (index, studentObj) {
                    $ul.append('<div class="checkbox-custom checkbox-primary"><input type="checkbox" class="studentcb" value="'+studentObj.id+'" name="students[]"><label>'+studentObj.name+'</label></div>');
                });
                $missing_data_block.removeClass('waiting');
                if(!$missing_data_block.is(':visible')){
                    $missing_data_block.show();
                    location.hash = 'missing_data';
                }
                checkForMissingData.busy = false;
            }
        })
    };


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

    $(function(){

        $missing_data_block = $('#missing_data');
        sindex = 0;
        marked = [];

        $missing_data_block.find('button').click(function(){
            $selected_students = $missing_data_block.find('.studentcb:checked');
            if($selected_students.length == 0){
                swal('','Select student(s) to mark.','warning');
                return;
            }
            marked = ($selected_students.map(function(){return $(this).val()}).get());

            global_waiting();

            sinterval = setInterval(function(){
                var item = marked[sindex++];
                if(typeof item != 'undefined'){
                    _notifyMissing(item);
                }else{
                    clearInterval(sinterval);
                    sindex = 0;
                    marked = [];
                    global_waiting_hide();
                    checkForMissingData();
                }
            },1000);


            function _notifyMissing(sid){
                $.ajax({
                    url:'?ajax=notifyMissing',
                    method:'post',
                    data: {student:sid},
                    cache:false,
                    success: function (response) {
                    }
                });
            }

        });

        var $table = $('#school_assignment_table');

        $DataTable = $table.DataTable({
            bStateSave: false,
            bPaginate:true,
            pageLength: 25,
            columns: [
                {data:'cb',sortable:false,width:'5%'},
                {data:'student'},
                {data:'gender'},
                {data:'grade_level',width:'5%'},
                {data:'location'},
                {data:'state'},
                {data:'soe'},
                {data:'previous_soe'}
            ],
            aaSorting:[[1,'asc']]
        }).on( 'page.dt', function () {
            //var info = dttable.page.info();
            $('.globalcb:checked').trigger('click');
        });
        var createDataRowObject = function(studentObj){
            return {
                DT_RowId:'student-'+studentObj.id,
                cb: '<input type="checkbox" name="student[]" value="'+studentObj.id+'" class="cb">',
                student: studentObj.name,
                gender: studentObj.gender,
                grade_level: studentObj.grade_level,
                location: studentObj.city,
                state: studentObj.state,
                soe: studentObj.school_of_enrollment,
                previous_soe: studentObj.previous_soe
            };
        };

        $filter_block = $('#school_assignment_filter_block');

        grade_selectors.init($filter_block);

        var $selected_soe_year = $table.find('.selected_soe_year');
        var $previous_soe_year = $table.find('.previous_soe_year');
        var $filter_button = $filter_block.find('button');
        $filter_button.click(function () {
            global_waiting();
            $.ajax({
                url:'?ajax=loadStudents',
                data:$filter_block.find('input, select').serialize(),
                method:'post',
                success:function(response){
                    if(response===0){
                        swal('','Unable to load the students','error');
                        global_waiting_hide();
                        return;
                    }
                    var soe_years_set = false;
                    $DataTable.rows().remove();
                    $.each(response,function(index,studentObj){
                        if(!soe_years_set){
                            $selected_soe_year.html(studentObj.soe_year);
                            $previous_soe_year.html(studentObj.previous_soe_year);
                            soe_years_set = true;
                        }
                        $DataTable.row.add(createDataRowObject(studentObj));
                    });
                    $DataTable.draw();
                    global_waiting_hide();
                }
            });
        });

        var $soe_select = $('#schoolOfEnrollmentSelect');
        $soe_select.change(function(){
            if(!$soe_select.val()){
                return;
            }
            var $selected_students = $('input[name="student[]"]:checked');
            if($selected_students.length<1){
                swal('','No students selected','warning');
                return;
            }
            var send_new_packet = $('#sendUpdatedPacketToDropbox').prop('checked')
            global_waiting();

            $.ajax({
                url:'?ajax=assignSchool',
                data: $selected_students.serialize()
                    +'&school_year_id='+$('#schoolOfEnrollmentYearSelect').val()
                    +'&school_of_enrollment_id='+$soe_select.val()
                    +'&send_new_packet='+String(send_new_packet),
                method:'post',
                success:function (response) {
                    $soe_select.val('');
                    if(response===0){
                        swal('','Unable set the school of enrollment','error');
                        global_waiting_hide();
                        return;
                    }
                    $filter_button.click();
                    checkForMissingData();
                }
            });
        });

      var $update_packets_button = $('#sendUpdatedPacketsButton');
      $update_packets_button.click(async function(){
        var $selected_students = $('input[name="student[]"]:checked');
        // console.log('$selected_students: ', $selected_students)

        if($selected_students.length<1){
          swal('','No students selected','warning');
          return;
        }
        global_waiting();

        function chunk(array, size) {
          const chunked_arr = [];
          let index = 0;
          while (index < array.length) {
            chunked_arr.push(array.slice(index, size + index));
            index += size;
          }
          return chunked_arr;
        }

        $studentCalls = chunk($selected_students, 2);
        // console.log('$studentCalls: ', $studentCalls)
        var i;
        for(i = 0; i<=$studentCalls.length - 1; i++) {
          // console.log('Sending new packets for students: ', $studentCalls[i])

          var callPromises = []
          var c;
          for(c = 0; c<=$studentCalls[i].length - 1; c++) {
            // console.log('STUDENT PACKET: ', $studentCalls[i][c])

            callPromises.push($.ajax({
              url:'?ajax=sendNewPackets',
              data: $([$studentCalls[i][c]]).serialize()
                +'&school_year_id='+$('#schoolOfEnrollmentYearSelect').val(),
              method:'post',
              success:function (response) {
                // console.log(' >>> RESPONSE: ', response)
              },
              error:function (response) {
                console.log('ERROR: FAILED FOR STUDENT ', $studentCalls[i][c])
              }
            }))
          }

          await Promise.all(callPromises).catch(function(e) { })
        }

        global_waiting_hide();
      });
    });

}());