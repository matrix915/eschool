<?php
core_user::getUserLevel() || core_secure::loadLogin();

if (isset($_GET['getevents'])) {
    $data = [];
    while ($event = mth_events::each()) {
        $color = $event->color();
        $_data = [
            'title' => req_sanitize::txt_decode($event->eventName()),
            'start' => $event->startDate(),
            'backgroundColor' => $color ? $color : '#1e88e5',
            'borderColor' => $color ? $color : '#1e88e5',
            'content' => $event->content(),
            'id' => $event->id()
        ];
        if ($event->endDate() != '0000-00-00 00:00:00') {
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

cms_page::setPageTitle('Calendar');

core_loader::addCssRef('calendarcss', core_config::getThemeURI() . '/vendor/calendar/fullcalendar.min.css');
cms_page::setPageContent('');
core_loader::printHeader('student');
?>
<div class="page">
    <?= core_loader::printBreadCrumb('window'); ?>
    <div class="page-content container-fluid">
        <div class="card">
            <div class="card-block">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="viewEvent" aria-hidden="true" aria-labelledby="viewEvent" role="dialog" tabindex="-1" data-show="false">
    <div class="modal-dialog modal-simple">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" aria-hidden="true" data-dismiss="modal">×</button>
                <h4 class="modal-title event-name">Event Name</h4>
            </div>
            <div class="modal-body">
                <p class="content"></p>
                <span class="event-date"></span>
            </div>
        </div>
    </div>
</div>
<?php
core_loader::addJsRef('momentjs', core_config::getThemeURI() . '/vendor/calendar/moment.min.js');
core_loader::addJsRef('calendarjs', core_config::getThemeURI() . '/vendor/calendar/fullcalendar.min.js');
core_loader::printFooter('student');
?>
<script>
    $(document).ready(function() {
        event = {};
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month' //,agendaWeek,agendaDay
            },
            displayEventTime: false,
            selectable: false,
            selectHelper: false,
            editable: false,
            eventLimit: true, // allow "more" link when too many events
            eventClick: function(event) {
                var color = event.backgroundColor ? event.backgroundColor : '#1e88e5';
                var dates = [moment(event.start).format('MM/DD/YYYY')];
                // if(event.end){
                //     dates.push(moment(event.end).format('MM/DD/YYYY'));
                // }

                if (event.real_end) {
                    dates.push(moment(event.real_end).format('MM/DD/YYYY'));
                }

                $modal = $("#viewEvent");
                $modal.find('.event-name').text(event.title);
                $modal.find('.content').html(event.content);
                $modal.find('.event-date').text(dates.join(' to '));
                $modal.modal("show");
            },
            events: '?getevents=1',
        });

    });
</script>