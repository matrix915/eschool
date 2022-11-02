/**
 * Created by abe on 6/29/16.
 */

function updateFilters() {
    localStorage.scheduleFilters = '';
    var selectedFilters = [];
    $('.status-cb:checked').each(function () {
        selectedFilters.push(this.value);
    });
    localStorage.scheduleFilters = selectedFilters.join(',');
    location.reload();
}

function populateFilters() {
    if (!localStorage.scheduleFilters) {
        localStorage.scheduleFilters = '1,4';
    }
    $.each(localStorage.scheduleFilters.split(','), function (i, v) {
        $('.status-cb[value="' + v + '"]').prop('checked', true);
    });
}

function editSchedule(schedule_id) {
    global_popup_iframe('mth_schedule-edit-'+schedule_id, '/_/admin/schedules/schedule?schedule=' + schedule_id+'&fromschedule=1');
}

function editPeriod(student_slug, schedule_period_id) {
    global_popup_iframe(
        'mth_schedule_period-edit',
        '/student/' + student_slug + '/schedule/period?admin=1&schedule_period=' + schedule_period_id);
}

function updateSchedule() {
    location.reload();
}

function changeType() {
    if ( $('input[name="type"]:checked').val() ) {
        if( $('input[name="type"]:checked').val() == "1" ) {
            if( $('input[name="provider[]"]:checked').length ) {
                $('#periods').show();
            } else {
                $('input[name="periods"]').prop('checked', false);
                $('#periods').hide();
            }
            $('#provider').show();
        } else {
            if ( $('input[name="type"]:checked').val() == 0 ) {
                $('input[name="periods"]').prop('checked', false);
                $('#periods').hide();
            } else {
                $('#periods').show();
            }
            
            $('#provider').hide();
            $('input[name="provider[]"]').prop('checked', false);
        }
    } else {
        $('#periods').hide();
        $('input[name="periods"]').prop('checked', false);
        $('#provider').hide();
        $('input[name="provider[]"]').prop('checked', false);
    }
}

function populateScheduleFilter() {
    if ( localStorage.typeFilter ) {
        $('.type-'+localStorage.typeFilter).prop('checked', true);
    }
    changeProvider();
    changePeriod();
    updateYear();
}

function changePeriod() {
    if ( localStorage.periodFilter ) {
        $('.period-'+localStorage.periodFilter).prop('checked', true);
    }
}

function changeProvider() {
    if ( localStorage.providerFilter ) {
        var provider_filter = JSON.parse(localStorage.providerFilter);
        Object.keys(provider_filter).forEach(function(key) {
            $('.provider-'+provider_filter[key]).prop('checked', true);
        });
    }
}

function updateYear() {
    if(localStorage.yearFilter) {
        $('select[name="year"]').val(localStorage.yearFilter)
    }
}

function fetchDatablesData() {
    global_waiting();
    var req_data = {};
    if (localStorage.typeFilter && localStorage.typeFilter != 0) {
        req_data['type'] = localStorage.typeFilter;
    }
    if ( localStorage.periodFilter && localStorage.periodFilter!=0 ) {
        req_data['periods'] = localStorage.periodFilter;
    }
    if ( localStorage.providerFilter ) {
        req_data['provider'] = JSON.parse(localStorage.providerFilter);
    }
    var scheduleTable = $('#mth_schedule-table');
    $filter_block = $('#schedules_filter_block');
    window.schDT = scheduleTable.DataTable({
        "processing": true,
        "serverSide": true,
        "iDisplayLength": 25,
        "ajax":{
            "type": 'POST',
            "data": req_data,
            url: '?ajax=getAll' + (localStorage.yearFilter ? '&year='+localStorage.yearFilter :'') + ( localStorage.scheduleFilters ? '&status='+localStorage.scheduleFilters : '&status=1,4'),
            "dataSrc": function ( res ) {
                /** update count */
                var counts = res.counts;
                $(".status-cb").next().html("( 0 )")
                for (var key in counts) {
                    $("#status_count_"+key).html("( "+counts[key]+" )");
                }
                return res.aaData;
            }
        },
        columns: [
            {data: 'last_modified'},
            {data: 'student'},
            {data: 'status'}
        ]
    });
    global_waiting_hide();
}

$(function () {

    /** filter event */
    $filter_block = $('#schedules_filter_block');
    $filter_block.find('button').click(function () {
        location.reload();
    });

    fetchDatablesData();

    /** storing of type in local storage */
    $('input[name="type"]').on('change', function() {
        $('input[name="periods"]').prop('checked', false);
        if ($(this).val()) {
            localStorage.typeFilter = $(this).val();
        } else {
            localStorage.removeItem('typeFilter');
        }
        localStorage.removeItem('periodFilter');
        localStorage.removeItem('providerFilter');
        changeType();
    });
    
    /** select all event */
    $('#all-providers').on('change', function(){
        if (this.checked) {
            $('input[name="provider[]"]').prop('checked', true);
        } else {
            $('input[name="provider[]"]').prop('checked', false);
        }
    });

    /** storing of providers in local storage */
    $('input[name="provider[]"]').on('change', function() {
        if ( $(this).val() != 0 && !this.checked ) {
            $('#all-providers').prop('checked', false);
        }
        if( ($('input[name="provider[]"]').length - 1) == $('input[name="provider[]"]:checked').length ) {
            $('#all-providers').prop('checked', true);
        }
        if( $('input[name="provider[]"]:checked').length ) {
            $('#periods').show();
        } else {
            $('input[name="periods"]').prop('checked', false);
            localStorage.periodFilter = "";
            $('#periods').hide();
        }
        var provider_filter = {}
        $('input[name="provider[]"]:checked').each(function (i, data) {
            provider_filter[i] =  $(this).val();
            
        });
        localStorage.setItem("providerFilter", JSON.stringify(provider_filter));
    });

    /** storing of periods in local storage */
    $('input[name="periods"]').on('change', function() {
        localStorage.periodFilter = $(this).val();
    });

    /** change year event */
    $('select[name="year"]').on('change', function() {
        localStorage.yearFilter = $(this).val();
        location.reload();
    });

    populateScheduleFilter();
    populateFilters();
    changeType();
});