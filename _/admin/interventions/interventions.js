$DataTable = null;
var selected_intervention = 0;
var selected_student = 0;

function edit(interventionId) {
    interventionId = interventionId == 'null' ? 0 : interventionId;
    global_popup_iframe('mth_intervention-label-popup-form', '/_/admin/interventions/label?intervention=' + interventionId);
}

function removeDeleted(id) {
    $DataTable.row($('#intervention-' + id)).remove().draw();
}

//sort date , empty dates always at the bottom
jQuery.extend(jQuery.fn.dataTableExt.oSort, {
    'non-empty-date-asc': function (a, b) {
        if(a == "")
            return 1;
        if(b == "")
            return -1;
        var x = Date.parse(a);
        var y = Date.parse(b);
        if (x == y) { return 0; }
        if (isNaN(x) || x < y) { return 1; }
        if (isNaN(y) || x > y) { return -1; }
    },
    'non-empty-date-desc': function (a, b) {
        if(a == "")
            return 1;
        if(b == "")
            return -1;
        var x = Date.parse(a);
        var y = Date.parse(b);
        if (x == y) { return 0; }
        if (isNaN(y) || x < y) { return -1; }
        if (isNaN(x) || x > y) { return 1; }
    }
});

$.fn.dataTable.ext.order['dom-text-numeric'] = function  ( settings, col )
{
    return this.api().column( col, {order:'index'} ).nodes().map( function ( td, i ) {
        return $('a .numeric', td).text() * 1;
    } );
}

$(function () {
  
    var $table = $('#interventions_table');
    var $filter = $('#do_filter');
    var $form = $('#filter_form');
    var $pull = $('#pull-users');
    var $grade_selector = $('.grade_selector');
    var $grade_level_block = $('.grade-levels-block');
    var $dowloadcsv = $('#dowloadcsv');
    var $final_notice_btn = $('#final-notice');
    var $first_notice_btn = $('#first-notice');
    var $missing_log_btn = $('#missinglog-notice');
    var $checkall = $('.check-all');
    var $sync = $('#sync');
    var $add_label = $('#add-label');
    var $headsup_btn = $('#headsup-notice');
    var $consecutive_btn = $('#consecutive-ex');
    var $probation_btn = $('#probation');
    var $exceed_btn = $('#exceed-ex');

    if($('input[name ="zero_count"]').length)
    {
        var exportColumns = [2,3,4,5,6,7,8,11,12,13,14,15,16,17,18,19,20];
    }else {
        var exportColumns =  [2,3,4,5,6,7,8,9,10,12,13,14,15,16,17,18,19,20];
    }

    $DataTable = $table.DataTable({
        //bStateSave: true,
        pageLength: 25,
        columns: [
            { data: 'arrow',sortable: false},
            { data: 'cb', sortable: false},
            { data: 'email_sent' },
            { data: 'date_sent' , type: "date"},
            { data: 'due_date' , type: "non-empty-date"},
            { data: 'label' },
            { data: 'student_name' },
            { data: 'gender' },
            { data: 'grade_level'},
            { data: 'first_sem_zeros' },
            { data: 'second_sem_zeros' },
            { data: 'zeros' },
            { data: 'ex'},
            { data: 'consecutive_ex'},
            { data: 'grade', orderDataType: "dom-text-numeric", type:'num-fmt' },
            { data: 'mid_year' },
            { data: 'parent_email' },
            { data: 'soe' },
            { data: 'parent_phone' },
            { data: 'parent_name' },
            { data: 'notes', sortable: false },
        ],
        aaSorting: [[3, 'desc']],
        iDisplayLength: 25,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csv',
                text: 'Download CSV',
                exportOptions: {
                    columns: exportColumns,
                    modifier: {
                        search: 'none'
                    }
                }
            }
        ]
    });


    if($('input[name ="zero_count"]').length)
    {
        $DataTable.column(9).visible(false);
        $DataTable.column(10).visible(false);
    }else {
        $DataTable.column(11).visible(false);
        // console.log($DataTable.buttons(0));
    }
    
    var GradeLevel = new function () {
        var groups = {
            gAll: [],
            gKto8: [],
            g9to12: []
        };
        var $target = null;
        var $list = null;
        var selected = [];


        this.init = function (target) {
            $target = target;
            $list = $target.find('.grade-level-list :checkbox');
            groups.gAll = $list;
            groups.gKto8 = $list.filter(function () {
                return $(this).val() === 'OR-K' || $(this).val() === 'K' || ($(this).val() * 1) < 9;
            });
            groups.g9to12 = $list.filter(function () {
                return ($(this).val() * 1) >= 9;
            });
            return this;
        }

        this.check = function (group_name) {
            $.inArray(group_name, selected) == -1 && selected.push(group_name);
            groups[group_name].each(function (key, val) {
                $(this).prop('checked', true);
            });
        }

        this.uncheck = function (group_name) {

            selected = selected.filter(function (group) {
                return group != group_name;
            });


            groups[group_name].each(function(key, val){
                var $cb = $(val);
                if (selected.length > 0 && this._isNotSelected($cb)) {
                    $cb.prop('checked', false);
                }

                if (selected.length == 0) {
                    $cb.prop('checked', false);
                }
            });
        }

        this._isNotSelected = function ($cb) {
            var notSelected = true;
            $.each(selected, function (key, value) {
                groups[value].each(function (key, value) {
                    if ($cb.val() == $(this).val()) {
                        notSelected = false;
                        return false;
                    }

                });
            });
            return notSelected;
        }

    };

    Label = new function () {
        this.data = [];
        this.all = [];
        this.tobedeleted = null;
        this.$tbl = $('#label-tbl');
        this.$filter = $('#filter-labels');
        this.onload = true;
        this.save = function (data, _success) {
            this.data = data;
            global_waiting();
            $.ajax({
                url: '?ajax=label',
                data: data,
                method: 'post',
                success: _success
            });
        }
        this.get = function (_success) {
            $.ajax({
                url: '?ajax=label',
                method: 'get',
                dataType: 'json',
                success: _success
            });
        }

        this.delete = function (data, _success) {
            this.tobedeleted = data.label;
            $.ajax({
                url: '?ajax=label&delete=1',
                method: 'post',
                data: data,
                success: function (response) {
                    if (response.error == 0) {
                        Label.all = Label.all.filter(function (data) {
                            return data.label_id != Label.tobedeleted;
                        });
                    }
                    _success(response);
                }
            });
        }
        this.populate = function () {
            var data = this.all;
            var onload = this.onload;
            this.resetDom();
            $.each(data, function (key, value) {
                Label.appendRow(value);
                Label.appendFilter(value);
            });

            Label.appendFilter({
                label_id: 0,
                name: 'Unlabeled'
            });
            
        }
        /**
         * Append Row from Add/Edit labels Modal
         */
        this.appendRow = function (value) {
            this.$tbl.append('<tr class="label-' + value.label_id + '"><td>' + value.name + '</td><td><a data-id="' + value.label_id + '" data-value="' + value.name + '" class="edit-label"><small>Edit</small></a></td><td><a data-id="' + value.label_id + '" class="delete-label"><small>Delete</small></a></td></tr>');
        }

        this.addData = function (value) {
            this.all.push(value);
            return this;
        }

        this.setData = function (data) {
            this.all = [];
            this.all = data;
            return this;
        }
        /**
         * Append on Labels filter list
         */
        this.appendFilter = function (value) {
            var checked = typeof value.is_checked != 'undefined' && value.is_checked?'CHECKED':'';
            this.$filter.append('<div class="checkbox-custom checkbox-primary"><input type="checkbox" name="labels[]" value="' + value.label_id + '" '+checked+'><label>' + value.name + '</label></div>');
        }

        this.resetDom = function () {
            this.$filter.html('');
            this.$tbl.html('');
        }

        this.updateData = function (_data) {
            this.all = this.all.map(function (data) {
                if (data.label_id == _data.label_id) {
                    data['name'] = _data.name;
                }
                return data;
            });

            return this;
        }

        this.find = function (id) {
            var label = this.all.filter(function (data) {
                return data.label_id == id;
            });

            return label.length > 0 ? label[0] : null;
        }
    }

    /**
     * Intervention Class
     */
    Interventions = new function () {
        this.for_first_notice = [];
        this.for_final_notice = [];
        this.all_notice = [];
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
            var notice = this.setEmailSent(studentObj.notif_type,studentObj.notice_count);
            var label = this.setLabel(studentObj);
            var notes = this.setNotes(studentObj);
            var cb = this.setCheckbox(studentObj,notice);
            var isGrade =  !isNaN(studentObj.grade*1);
            return {
                DT_RowId: 'student-' + studentObj.id,
                arrow: '&nbsp;',
                cb: cb,
                email_sent: notice.html,
                label: label,
                student_name: '<a onclick=\'global_popup_iframe("mth_people_edit", "/_/admin/people/edit?student='+studentObj.id+'")\'>'+studentObj.student_name+'</a>',
                gender: studentObj.gender,
                grade_level: studentObj.grade_level,
                parent_email: studentObj.pemail,
                mid_year: studentObj.mid_year,
                parent_phone: studentObj.pphone,
                parent_name: studentObj.parent_name,
                first_sem_zeros: studentObj.first_sem_zero_count,
                second_sem_zeros: studentObj.second_sem_zero_count,
                zeros: studentObj.zero_count,
                ex: studentObj.ex,
                consecutive_ex: studentObj.consecutive_ex,
                grade: '<a onclick=\'global_popup_iframe("mth_student_learning_logs", "/_/teacher/homeroom?st='+studentObj.id+'&hr='+studentObj.hr+'")\'><span class="numeric">'+studentObj.grade+'</span>'+(isGrade?'%':'')+'</a>',
                soe: studentObj.soe,
                date_sent: studentObj.date_sent,
                due_date: studentObj.due_date,
                notes: notes
            };
        }

        this.setCheckbox = function(studentObj,notice){
           var notif_type = studentObj.notif_type; 
           var checked = $.inArray(studentObj.id+"",this.all_notice) == -1?'':'CHECKED';;
           
           return '<input type="checkbox" class="actionCB" value="' + studentObj.id + '" data-type="' + notice.type + '" '+checked+'>';
        }

        this.setNotes = function (data) {
            var intervention_id = data.intervention ? data.intervention : 0;
            if (data.notes != null) {
                return '<a class="notes" data-intervention="' + intervention_id + '" data-student="' + data.id + '">Notes (' + data.notes + ')</a>';
            }
            return '<a class="notes" data-intervention="' + intervention_id + '" data-student="' + data.id + '">Notes (0)</a>'
        }

        this.setLabel = function (data) {
            var intervention_id = data.intervention ? data.intervention : 0;
            if (data.label != null) {
                return '<a class="change-label" data-intervention="' + intervention_id + '" data-student="' + data.id + '">' + data.label.name + '</a>';
            }
            return '<a class="change-label" data-intervention="' + intervention_id + '" data-student="' + data.id + '">unlabeled</a>';
        }

        this.setEmailSent = function (notif_type,notif_count) {
            if (notif_type == FINAL_NOTICE) {
                return { html: '<div class="final-not" style="width:100px">Final Notice ('+notif_count+')</div>', type: FINAL_NOTICE };
            } else if (notif_type == FIRST_NOTICE) {
                return { html: '<div class="first-not" style="width:100px">First Notice ('+notif_count+')</div>', type: FIRST_NOTICE };
            } else if(notif_type == HEADSUP_NOTICE) {
                return { html: '<div class="headsup-not" style="width:100px">Heads up ('+notif_count+')</div>', type: HEADSUP_NOTICE };
            } else if(notif_type == EX_NOTICE){
                return { html: '<div class="consecutive-ex-not" style="width:100px">Max EX('+notif_count+')</div>', type: EX_NOTICE };
            } else if(notif_type == PROBATION_NOTICE){
                return { html: '<div class="probation-not" style="width:100px">Probation ('+notif_count+')</div>', type: PROBATION_NOTICE };
            }else if(notif_type == MISSING_NOTICE){
                return { html: '<div class="missing-not pink-500" style="width:100px">Missing Log ('+notif_count+')</div>', type: MISSING_NOTICE };
            } else if (notif_type == EXCEED_EX){
              return { html: '<div class="exceed-ex-not" style="width:100px">Exceed EX(' + notif_count + ')</div>', type: EXCEED_EX };
            }
            return { html: '', type: 0 };
        }
       
        this.loadStudents = function (nextPage,data) {
            var data = data;
            if(Interventions.busy && !nextPage){
                return;
            }

            Interventions.busy = true;
         
            if(nextPage){
                Interventions.page +=1;
                prevpage = 'page='+(Interventions.page-1);
                curpage = 'page='+Interventions.page;
                data = data.replace(prevpage,curpage);
            }else{
                Interventions.page = 1;
                data += '&page=1';
            }
           
            //reset
            $table.addClass('waiting');
            Interventions.statusCountComplete = false;
            data += '&y='+CURRENT_YEAR;
            
            $.ajax({
                url: '?loadIntervention=1',
                data: data,
                method: 'get',
                cache:false,
                dataType: 'json',
                success: function (res) {
                    if (res.count === 0) {
                        //swal('','Unable to load the students','error');
                        Interventions.page = 1;
                        Interventions.busy = false;
                        $table.removeClass('waiting');
                        return;
                    }

                    response = res.filtered;

                    if(Interventions.resetTable){
                        $DataTable.rows().remove();
                        $DataTable.draw();
                        Interventions.resetTable = false;
                    }
        
                    $.each(response, function (index, studentObj) {
                        var rowData = Interventions.createDataRowObject(studentObj);
                        $DataTable.row.add(rowData);
                    });

                    $DataTable.draw();
                    $('.student_count_display').text($DataTable.data().length);
                 

                    if(res.count<Interventions.pagesize){
                        $table.removeClass('waiting');
                        Interventions.page = 1;
                        Interventions.busy = false;
                        $DataTable.page(Interventions.active_page).responsive.recalc().draw(false);
                    }else{
                        Interventions.loadStudents(true,data);
                    }
                },
                error: function(){
                    swal('','Server Error, Unable to load students. Please try again.','error');
                }
            });
        }
    
        this._sendNotice = function (data) {
            setTimeout(function(){
                $.ajax({
                    url: '?ajax=send&type='+data.type+'&student=' + data.student,
                    method: 'post',
                    dataType: 'json',
                    success: function (response) {
                        if (response.error == 1) {
                            global_waiting_hide();
                            swal('',response.message,'error');
                        } else {
                            Interventions.completed += 1;
                            var notices = Interventions.getNotice();

                            if (notices.length == Interventions.completed) {
                                Interventions.reset();
                                Interventions.completed = 0;
                                
                                global_waiting_hide();
                                $('#do_filter').trigger('click');
                            }
                        }
                    }
                });
            }, this.timeout * 1000);
            this.timeout++;
        }

        this.sendNotice = function (type) {
          var $this = this;
          $.each(this.getNotice(), function(key, value){
            $this._sendNotice({
                  student: value,
                  type: type
              });
          });
        }

        this.sendConfirmation = function(message,_callback){
            swal({
                title: '',
                text: message,
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: true,
                closeOnCancel: true
            },_callback);
        }

        this.pull = function (_success) {
            global_waiting();
            $.ajax({
                url: '?ajax=pull',
                method: 'post',
                success: _success
            });
        }

        this.syncGrades = function (_success) {
            global_waiting();
            $.ajax({
                url: '?ajax=pull-grade',
                method: 'post',
                success: _success
            });
        }

        this.syncZeros = function (_success) {
            global_waiting();
            $.ajax({
                url: '?ajax=pull-zeros',
                method: 'post',
                success: _success
            });
        }


        this.pushNotice = function (id) {
            $.inArray(id, this.all_notice) == -1
                && this.all_notice.push(id);
        }

        this.getNotice = function () {
            return this.all_notice;
        }
      
        this.removeNotice = function (id) {
            this.all_notice = this.all_notice.filter(function (value) {
                return value != id;
            });
        }

        this.reset = function () {
            this.all_notice = [];
            this.sending = null;
        }

        this.save = function (data, _success) {
            this.data = data;
            global_waiting();
            $.ajax({
                url: '?ajax=save-label',
                data: data,
                method: 'post',
                success: _success
            });
        }

    }
    Interventions.setPageSize(PAGE_SIZE);
    var grade_level = GradeLevel.init($grade_level_block);
    Label.get(function (response) {
        Label
            .setData(response)
            .populate();
    });

    $filter.click(function () {
        $('.filter-status').show();
        Interventions.resetTable = true;
        Interventions.active_page = ($DataTable.page.info()).page;
        var data = $form.find('input,select').serialize();
        Interventions.loadStudents(false,data);
    });

    $sync.click(function () {
        Interventions.syncGrades(function (response) {
            $('.sync-status').text(response.data);
            global_waiting_hide();
        });
    });

    $grade_selector.click(function () {
        var $this = $(this);
        if ($this.is(':checked')) {
            grade_level.check($this.val());
        } else {
            grade_level.uncheck($this.val());
        }
    });

    $dowloadcsv.click(function () {
        var data = $form.find('input,select').serialize();
        location.href = '?csv=1&' + data;
    });


    $table.on('click', '.actionCB', function () {
        var $this = $(this);
        if (!$this.is(':checked')) {
            Interventions.removeNotice($this.val());
        } else {
            Interventions.pushNotice($this.val());
        }

    });

    function _sendNotice(msg,id){
        var notices = (Interventions.getNotice()).length;
        if (notices > 0) {
            Interventions.sendConfirmation(
                msg,
                function(){
                    global_waiting();
                    Interventions.sendNotice(id);
                }
            );
           
        }
    }

    $headsup_btn.click(function(){
        _sendNotice('Are you sure you want to send a Heads up Notice?',HEADSUP_NOTICE);
    });

    $missing_log_btn.click(function(){
        _sendNotice('Are you sure you want to send a Missing Log Notice?',MISSING_NOTICE);
    });

    $first_notice_btn.click(function () {
        _sendNotice('Are you sure you want to send a First Notice?',FIRST_NOTICE);
    });

    $final_notice_btn.click(function () {
        _sendNotice('Are you sure you want to send a Final Notice?',FINAL_NOTICE);
    });

    $consecutive_btn.click(function(){
        _sendNotice('Are you sure you want to send a Max EX Notice?',EX_NOTICE);
    });

    $probation_btn.click(function(){
        _sendNotice('Are you sure you want to send a Probation Notice?',PROBATION_NOTICE);
    });

    $exceed_btn.click(function () {
        _sendNotice('Are you sure you want to send a Exceed EX Notice?', EXCEED_EX);
    });

    $add_label.click(function () {
        var label_name = $.trim($('[name="label_name"]').val());
        var label_id = $.trim($('[name="label"]').val());
        if (label_name != '') {
            Label.save({
                label: label_id,
                label_name: label_name
            }, function (response) {
                global_waiting_hide();
                if (response.error == 1) {
                    swal('', response.message ,'error');
                } else {
                    $('[name="label_name"]').val('');
                    var label_name = Label.data.label_name;
                    Label.addData({
                        label_id: response.message,
                        name: label_name
                    }).populate();

                }
            });
        }

    });

    $('#label-tbl').on('click', '.delete-label', function () {
        $this = $(this);
        var sure = confirm('Are you sure you want to delete this label?');
        if (sure) {
            Label.delete({ label: $this.data('id') }, function (response) {
                global_waiting_hide();
                if (response.error == 1) {
                    swal('', response.message ,'error');
                } else {
                    Label.populate();
                }

            });
        }
    }).on('click', '.edit-label', function () {
        $this = $(this);
        var data = $this.data();
        var $elabel_name = $('[name="elabel_name"]');
        var $elabel = $('[name="elabel"]');

        $('#edit-label-form').fadeIn();
        $('#add-label-form').hide();

        $elabel_name.val(data.value);
        $elabel.val(data.id);

        $elabel_name.focus();

    });

    $('.cancel-update').click(function () {
        $('#edit-label-form').hide();
        $('#add-label-form').fadeIn();
    });

    $('#edit-label').click(function () {
        var label_name = $.trim($('[name="elabel_name"]').val());
        var label_id = $.trim($('[name="elabel"]').val());
        if (label_name != '') {
            Label.save({
                label: label_id,
                label_name: label_name
            }, function (response) {
                global_waiting_hide();
                if (response.error == 1) {
                    swal('', response.message ,'error');
                } else {
                    $('[name="label_name"]').val('');
                    var label = Label.data;
                    Label.updateData({ label_id: label.label, name: label.label_name }).populate();
                    $('#edit-label-form').hide();
                    $('#add-label-form').fadeIn();
                }
            });
        }
    });

    $('body').on('click', '.change-label', function () {
        selected_intervention = $(this).data('intervention');
        selected_student = $(this).data('student');
        var $selected_label = $('[name="selected_label"]');
        $selected_label.html('');
        $('#intervention_label_select').modal("show");
        $.each(Label.all, function (key, value) {
            $selected_label.append('<option value="' + value.label_id + '">' + value.name + '</option>');
        });
    }).on('click', '.notes', function () {
        selected_intervention = $(this).data('intervention');
        selected_student = $(this).data('student');
        global_popup_iframe('notesPopup', '/_/admin/interventions/notes?intervention=' + selected_intervention + '&student=' + selected_student+'&y='+CURRENT_YEAR);
    });

    // $('#alter_labels').click(function () {
    //     global_popup('intervention_labels');
    // });

    $('#update-intervention-label').click(function () {
        var selected_label = $('[name="selected_label"]').val();
        Interventions.save({
            label: selected_label,
            intervention: selected_intervention,
            student: selected_student
        }, function (response) {
            if (response.error == 1) {
                swal('', response.message ,'error');
            } else {

                var _label = Label.find(Interventions.data.label);

                var $row = $DataTable.row('#student-' + selected_student);
                var old_data = $row.data();

                var new_label = Interventions.setLabel({
                    intervention: response.message,
                    label: _label,
                    id: selected_student
                });

                var new_data = $.extend({}, old_data, {
                    label: new_label
                });

                $row.data(new_data).draw(false);
            }

            $('#intervention_label_select').modal('hide');
            global_waiting_hide();
        });
    });

});
