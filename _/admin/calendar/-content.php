<?php
if(isset($_GET['addevent'])){
    $event = new mth_events();
    if(req_post::is_set('event_id')){
        $event->id(req_post::int('event_id'));
    }

    $event->eventName(req_post::txt('name'));
    $event->content(req_post::html('content'));
    $event->startDate(req_post::txt('start_date'));
    $event->endDate(req_post::txt('end_date'));
    $event->color(req_post::txt('colorChosen'));
    if($event->save()){
        core_notify::addMessage('Event Saved');
    }else{
        core_notify::addError('Oops! there is and error saving the event');
    }  
    core_loader::redirect();
    exit();
}

if(isset($_GET['getevents'])){
    $data = [];
    while($event = mth_events::each()){
        $color = $event->color();
        $_data = [
            'title' => req_sanitize::txt_decode($event->eventName()),
            'start' => $event->startDate(),
            'backgroundColor' => $color?$color:'#1e88e5',
            'borderColor' => $color?$color:'#1e88e5',
            'content' => $event->content(),
            'id' => $event->id()
        ];
        if($event->endDate() != '0000-00-00 00:00:00'){
            $_data['real_end'] = $event->endDate(); //actual event end date
            $tmp_end = new DateTime($event->endDate()); // format end date for manipulation
            $tmp_end->modify('+1 day'); //this is a workaround against fccalendar date range
            $_data['end'] = $tmp_end->format("Y-m-d H:i:s");
        }
        $data[] = $_data;
        
    }

    echo json_encode($data);
    exit;
}

if(isset($_GET['deleteevent'])){
    $event = mth_events::getByID(req_get::int('deleteevent'));
    $event->delete();
    exit;
}
cms_page::setPageTitle('Calendar');
core_loader::addCssRef('calendarcss', core_config::getThemeURI() . '/vendor/calendar/fullcalendar.min.css');
cms_page::setPageContent('');
core_loader::printHeader('admin');
//core_loader::includeCKEditor();
core_loader::includejQueryUI();
?>
<style>
    #ui-datepicker-div.ui-datepicker{
        z-index:1700 !important;
    }
</style>
<div class="card">
    <div class="card-block">
        <div id="calendar"></div>
    </div>
</div>
<!-- Modals -->
<div class="modal fade" id="addNewEvent" aria-hidden="true" aria-labelledby="addNewEvent" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-simple">
        <form class="modal-content form-horizontal" id="addNewEventForm" action="?addevent=1" method="post" role="form">
            <div class="modal-header">
                <button type="button" class="close" aria-hidden="true" data-dismiss="modal">×</button>
                <h4 class="modal-title">New Event</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label  for="ename">Name:</label>
                    <input type="text" class="form-control" id="ename" name="name" required>

                </div>
                <div class="form-group">
                <label for="econtent">Content:</label>
                <textarea name="content" id="econtent"  class="form-control" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="starts">Starts:</label>
                   
                    <div class="input-group">
                        <input type="text" name="start_date" class="form-control" id="starts"  required/>
                        <span class="input-group-addon">
                            <i class="icon md-calendar" aria-hidden="true"></i>
                        </span>
                    </div>
                    <label class="error" for="starts"></label>
                </div>

                <div class="form-group">
                    <label for="ends">Ends:</label>
                    <div class="input-group">
                        <input type="text" name="end_date" class="form-control" id="ends"/>
                        <span class="input-group-addon">
                        <i class="icon md-calendar" aria-hidden="true"></i>
                        </span>
                    </div>
                    <label class="error" for="ends"></label>
                </div>
                <div class="form-group">
                    <label> Color:</label>
                    <ul class="color-selector">
                        <li>
                            <input type="radio" data-color="blue|600" name="colorChosen" id="editColorChosen2" value="#1e88e5">
                        <label for="editColorChosen2"></label>
                        </li>
                        <li class="bg-green-600">
                        <input type="radio" data-color="green|600" name="colorChosen" id="editColorChosen3" value="#43a047">
                        <label for="editColorChosen3"></label>
                        </li>
                        <li class="bg-cyan-600">
                        <input type="radio" data-color="cyan|600" name="colorChosen" id="editColorChosen4" value="#00acc1">
                        <label for="editColorChosen4"></label>
                        </li>
                        <li class="bg-orange-600">
                        <input type="radio" data-color="orange|600" name="colorChosen" id="editColorChosen5" value="#fb8c00">
                        <label for="editColorChosen4"></label>
                        </li>
                        <li class="bg-red-600">
                        <input type="radio" data-color="red|600" name="colorChosen" id="editColorChosen6" value="#e53935">
                        <label for="editColorChosen6"></label>
                        </li>
                        <li class="bg-blue-grey-600">
                        <input type="radio" data-color="blue-grey|600" name="colorChosen" id="editColorChosen7" value="#546e7a">
                        <label for="editColorChosen7"></label>
                        </li>
                        <li class="bg-purple-600">
                        <input type="radio" data-color="purple|600" name="colorChosen" id="editColorChosen8" value="#8e24aa">
                        <label for="editColorChosen8"></label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <div class="form-actions">
                    <button class="btn btn-primary btn-round" type="submit">Add this event</button>
                    <a class="btn btn-sm btn-white btn-round" data-dismiss="modal" href="javascript:void(0)">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editNewEvent" aria-hidden="true" aria-labelledby="editNewEvent" role="dialog" tabindex="-1" data-show="false">
    <div class="modal-dialog modal-simple">
        <form class="modal-content form-horizontal" id="editEventForm" action="?addevent=1" method="post" role="form">
            <div class="modal-header">
                <button type="button" class="close" aria-hidden="true" data-dismiss="modal">×</button>
                <h4 class="modal-title">Edit Event</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label  for="uname">Name:</label>
                    <input type="text" class="form-control" id="uname" name="name" required>
                    <input type="hidden" name="event_id" value="0">
                </div>
                <div class="form-group">
                <label for="ucontent">Content:</label>
                <textarea name="content" id="ucontent"  class="form-control" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="ustarts">Starts:</label>
                
                    <div class="input-group">
                        <input type="text" name="start_date" class="form-control" id="ustarts"  required/>
                        <span class="input-group-addon">
                            <i class="icon md-calendar" aria-hidden="true"></i>
                        </span>
                    </div>
                    <label class="error" for="ustarts"></label>
                </div>

                <div class="form-group">
                    <label for="uends">Ends:</label>
                    <div class="input-group">
                        <input type="text" name="end_date" class="form-control" id="uends"/>
                        <span class="input-group-addon">
                        <i class="icon md-calendar" aria-hidden="true"></i>
                        </span>
                    </div>
                    <label class="error" for="uends"></label>
                </div>
                <div class="form-group">
                    <label> Color:</label>
                    <ul class="color-selector">
                        <li>
                            <input type="radio" data-color="blue|600" name="colorChosen" id="editColorChosen2" value="#1e88e5">
                        <label for="editColorChosen2"></label>
                        </li>
                        <li class="bg-green-600">
                        <input type="radio" data-color="green|600" name="colorChosen" id="editColorChosen3" value="#43a047">
                        <label for="editColorChosen3"></label>
                        </li>
                        <li class="bg-cyan-600">
                        <input type="radio" data-color="cyan|600" name="colorChosen" id="editColorChosen4" value="#00acc1">
                        <label for="editColorChosen4"></label>
                        </li>
                        <li class="bg-orange-600">
                        <input type="radio" data-color="orange|600" name="colorChosen" id="editColorChosen5" value="#fb8c00">
                        <label for="editColorChosen4"></label>
                        </li>
                        <li class="bg-red-600">
                        <input type="radio" data-color="red|600" name="colorChosen" id="editColorChosen6" value="#e53935">
                        <label for="editColorChosen6"></label>
                        </li>
                        <li class="bg-blue-grey-600">
                        <input type="radio" data-color="blue-grey|600" name="colorChosen" id="editColorChosen7" value="#546e7a">
                        <label for="editColorChosen7"></label>
                        </li>
                        <li class="bg-purple-600">
                        <input type="radio" data-color="purple|600" name="colorChosen" id="editColorChosen8" value="#8e24aa">
                        <label for="editColorChosen8"></label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <div class="form-actions">
                    <button class="btn btn-primary btn-round" type="submit" name="submit" value="save">Save</button>
                    <button class="btn btn-danger btn-round" type="button" onclick="delete_event()">Delete</button>
                    <a class="btn btn-sm btn-white btn-round" data-dismiss="modal" href="javascript:void(0)">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- End Modals -->
<script src="//cdn.ckeditor.com/4.10.0/basic/ckeditor.js"></script>
<?php
core_loader::addJsRef('momentjs', core_config::getThemeURI() . '/vendor/calendar/moment.min.js');
core_loader::addJsRef('calendarjs', core_config::getThemeURI() . '/vendor/calendar/fullcalendar.min.js');
core_loader::includejQueryValidate();
core_loader::printFooter('admin');
?>
<script>
function delete_event(){
   var event_id = $('[name="event_id"]').val();

   swal({
        title: "",
        text: "Are you sure you want to delete these event? This action cannot be undone!",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-warning",
        confirmButtonText: "Yes",
        cancelButtonText: "No",
        closeOnConfirm: false,
        closeOnCancel: true
    },
    function () {
        $.ajax({
            url: '?deleteevent='+event_id,
            success: function(){
                location.href="/_/admin/calendar";
            },
            error: function(){
                swal('','There is an error deleting event','danger');
            }
        });
    });
   
}
$(document).ready(function() {
    CKEDITOR.config.removePlugins = 'about';
    CKEDITOR.config.disableNativeSpellChecker = false;
    
    event = {};
    $('#addNewEventForm').validate();
    $("#uends,#ustarts,#starts,#ends").datepicker();
    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month' //,agendaWeek,agendaDay
        },
        displayEventTime : false,
        selectable: true,
        selectHelper: true,
        select: function(startDate, endDate, allDay,) {
            var timeZoneDifference = startDate._d.getTimezoneOffset() //get difference between current timezone on local and UTC in minutes
            var newStarDate = new Date(startDate._d.getTime() + timeZoneDifference * 60 * 1000); //calculate offseted date
            $('#starts').datepicker('setDate',newStarDate);
            CKEDITOR.instances.econtent && CKEDITOR.instances.econtent.setData('');
            !CKEDITOR.instances.econtent && CKEDITOR.replace('econtent');
            $("#addNewEvent").modal("show");
          
        },
        editable: true,
        eventLimit: true, // allow "more" link when too many events
        eventClick: function(event) {
            var color = event.backgroundColor ? event.backgroundColor : '#1e88e5';
            $modal = $("#editNewEvent");
            $modal.find('[name="name"]').val(event.title);
            $modal.find('[name="content"]').val(event.content);
            $modal.find('[name="event_id"]').val(event.id);
            $modal.find('[name="start_date"]').val(moment(event.start).format('MM/DD/YYYY'));
            if(event.real_end){
                $modal.find('[name="end_date"]').val(moment(event.real_end).format('MM/DD/YYYY'));
            }else{
                $modal.find('[name="end_date"]').val('');
            } 
            $modal.find('[name="colorChosen"]').filter(function() { 
                return $(this).val() == color;
            }).attr('checked','checked');
            CKEDITOR.instances.ucontent && CKEDITOR.instances.ucontent.setData(event.content);
            !CKEDITOR.instances.ucontent && CKEDITOR.replace('ucontent');
            $modal.modal("show");
        },
        events: '?getevents=1',
    }); 

    $('#editNewEvent,#addNewEvent').on('shown.bs.modal', function() {
        $(document).off('focusin.modal');
    });

});    
</script>