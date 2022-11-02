<?php

if (req_get::bool('year')) {
    $year = mth_schoolYear::getByID(req_get::int('year'));
}
if (!isset($year) && !($year = mth_schoolYear::getCurrent())) {
    exit('The current school year is not defined');
}

if (req_get::bool('markPaid')) {
    $rrIDs = array_map('intval', explode('|', req_post::txt('rrIDs')));
    $success = array();
    foreach ($rrIDs as $rrID) {
        if (!($reimbursement = mth_reimbursement::get($rrID))) {
            continue;
        }
        $reimbursement->set_status(mth_reimbursement::STATUS_PAID);
        $success[] = $reimbursement->save();
    }
    if (count($success) != count(array_filter($success))) {
        echo '0';
    } else {
        echo '1';
    }
    exit();
}

if (req_get::bool('delete')) {
    $rrIDs = array_map('intval', explode('|', req_post::txt('rrIDs')));
    $success = array();
    foreach ($rrIDs as $rrID) {
        if (!($reimbursement = mth_reimbursement::get($rrID))) {
            continue;
        }
        $success[] = $reimbursement->delete();
    }
    if (count($success) != count(array_filter($success))) {
        echo '0';
    } else {
        echo '1';
    }
    exit();
}

if (req_get::bool('loadReimbursements')) {
    error_reporting(E_ALL ^ E_WARNING); // TODO: better way to silence warnings in JSON output?

    $query = new \mth\Reimbursement\Query();
    if (req_get::bool('statuses')) {
        $query->setStatuses(req_get::int_array('statuses'));
    }

    if (req_get::bool('types')) {
        $query->setTypes(req_get::int_array('types'));
    }

    if (req_get::bool('methods')) {
        $query->setMethods(req_get::int_array('methods'));
    }

    $query->setSchoolYearIds([$year->getID()]);
    $lastModifiedFailSafe = &$_SESSION['reimbursement_admin']['lastModifiedFailSafe'];
    if (!$lastModifiedFailSafe) {
        $lastModifiedFailSafe = strtotime('-1 year');
    }
    if (req_get::bool('modified_since')) {
        if (!($lastModifiedFromClient = req_get::strtotime('modified_since'))) {
            $lastModifiedFromClient = $lastModifiedFailSafe;
        }
        $query->setModifiedSince($lastModifiedFromClient);
    }
    $reimbursements = $query->getAllWithRelations(req_get::int('page'));

    // $student_ids = [];
    // $schedule_period_ids = [];
    // foreach($reimbursements as $reimbursement){
    //     $student_ids[$reimbursement->student_id()] = $reimbursement->student_id();
    //     $schedule_period_ids[$reimbursement->schedule_period_id()] = $reimbursement->schedule_period_id();
    // }
    // if($schedule_period_ids){
    //     mth_schedule_period::cacheSchedulePeriods($schedule_period_ids);
    // }
    // if($student_ids){
    //     mth_student::getStudents(['StudentIds'=>$student_ids]);
    // }

    $return = [];
    foreach ($reimbursements as $reimbursement) {
        if ($reimbursement->getLastModified()->Format('U') > $lastModifiedFailSafe) {
            $lastModifiedFailSafe = $reimbursement->getLastModified()->Format('U');
        }

        if ($reimbursement->type(true) == 11) {
            continue;
        }

        $student_preferred_last_name = $reimbursement->student_preferred_last_name ? $reimbursement->student_preferred_last_name : $reimbursement->student_last_name;
        $student_preferred_first_name = $reimbursement->student_preferred_first_name ? $reimbursement->student_preferred_first_name : $reimbursement->student_first_name;
        $parent_preferred_first_name = $reimbursement->parent_preferred_first_name ? $reimbursement->parent_preferred_first_name : $reimbursement->parent_first_name;
        $student_name = $student_preferred_last_name . ', ' . $student_preferred_first_name;

        $return[] = [
            'id' => $reimbursement->id(),
            'student' => $student_name,
            'parent' => $parent_preferred_first_name,
            'status' => $reimbursement->status(),
            'status_id' => $reimbursement->status(true),
            'is_second' => false, //$reimbursement->isSecond(),
            'date_submitted' => $reimbursement->date_submitted('m/d/Y'),
            'amount' => '$' . $reimbursement->amount(true),
            'date_paid' => $reimbursement->date_paid('m/d/Y'),
            'schedule_period' => ($reimbursement->schedule_period() ? $reimbursement->schedule_period_description() : $reimbursement->type()),
            'type' => $reimbursement->type(true),
            'last_modified' => $reimbursement->getLastModified()->Format('Y-m-d H:i:s'),
            'reimbursement_method' => ($reimbursement->is_direct_order()) ? 'DO' : 'RB',
        ];
    }
    header('Content-type: application/json');
    echo json_encode($return);
    exit();
}

if (req_get::bool('getCounts')) {
    header('Content-type: application/json');

    $statuses = req_get::bool('statuses') ? req_get::int_array('statuses') : [];

    echo json_encode([
        'status' => mth_reimbursement::statusCounts($year),
        'type' => mth_reimbursement::typeCounts($year, $statuses),
        'method' => mth_reimbursement::methodCounts($year, $statuses),
    ]);
    exit();
}

if (req_get::bool('getDiff')) {
    echo mth_reimbursement::detectDiff();
    exit;
}

if (req_get::bool('fixDiff')) {
    if (mth_reimbursement::fixDiff()) {
        core_notify::addMessage('Time discrepancy fixed');
    } else {
        core_notify::addError('There is an issue fixing discrepancy please contact Web Admin.');
    }
    core_loader::redirect();
    exit;
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('Reimbursement Requests');
cms_page::setPageContent('');

core_loader::printHeader('admin');
?>
<style>
  #mth_reimbursement-admin-filters label {
    display: inline;
    margin: 0 10px;
  }

  .mth_reimbursement-Resubmitted a:not([href]):not([tabindex]),
  .mth_reimbursement-Submitted a:not([href]):not([tabindex]) {
    color: #616161;
  }

  .mth_reimbursement-SubmittedSecond a:not([href]):not([tabindex]),
  .mth_reimbursement-ResubmittedSecond a:not([href]):not([tabindex]) {
    color: #990099;
  }

  .mth_reimbursement-UpdatesRequired a:not([href]):not([tabindex]),
  .mth_reimbursement-UpdatesRequiredSecond a:not([href]):not([tabindex]) {
    color: #ff9800;
  }

  .mth_reimbursement-Paid a:not([href]):not([tabindex]),
  .mth_reimbursement-PaidSecond a:not([href]):not([tabindex]) {
    color: #2196f3;
  }

  .mth_reimbursement-Approved a:not([href]):not([tabindex]),
  .mth_reimbursement-ApprovedSecond a:not([href]):not([tabindex]) {
    color: #4caf50;
  }

  #mth_reimbursement-admin-table_info {
    display: none;
  }

  #mth_reimursements_total {
    position: absolute;
    display: inline-block;
    color: #2196f3;
    background: rgba(255, 255, 255, .8);
    padding: 10px 20px;
    border-radius: 5px 5px 0 0;
    z-index: 5;
    font-size: 24px;
  }

  #mth_reimursements_total:before {
    content: 'Sum of selected: $'
  }

  #mth_reimursements_total.fixed {
    position: fixed;
    top: auto;
    bottom: 0px;
  }

  /* #mth_reimbursement-admin-table_holder{
            margin: 0 -30px;
            position: relative;
            z-index: 1;
        } */

  .left-p .pagination {
    justify-content: normal !important;
  }
</style>
<div class="alert alert-danger" id="time-diff" style="display:none">
  Detected time discrepancy <a class="btn btn-primary btn-sm" href="?fixDiff=1">Fix Now</a> or contact web admistrator.
</div>

<div class="card">
  <div id="mth_reimbursement-admin-filters" class="card-block">
    <div class="mb-10">
      <select name="year" title="School Year">
        <?php while ($selYear = mth_schoolYear::each()): ?>
          <option value="<?=$selYear->getID()?>" <?=$selYear == $year ? 'selected' : ''?>><?=$selYear?></option>
        <?php endwhile;?>
      </select>

      <?php foreach (mth_reimbursement::availableStatuses() as $status => $label): ?>
        <label>
          <input type="checkbox" name="statuses[]" value="<?=$status?>" class="status_cb_<?=$status?>" <?=in_array($status, [mth_reimbursement::STATUS_SUBMITTED, mth_reimbursement::STATUS_RESUBMITTED]) ? 'checked' : ''?>>
          <?=$label?>
          <small>(<span class="status_count_<?=$status?>"></span>)</small>
        </label>
      <?php endforeach;?>

      <?php foreach (mth_reimbursement::availableMethods() as $method => $label): ?>
        <label>
          <input type="checkbox" name="methods[]" value="<?=$method?>" class="method_cb_<?=$method?>" <?=in_array($method, [mth_reimbursement::METHOD_REIMBURSEMENT, mth_reimbursement::METHOD_DIRECT_ORDER]) ? 'checked' : ''?>>
          <?=$label?>
          <small>(<span class="method_count_<?=$method?>"></span>)</small>
        </label>
      <?php endforeach;?>
      <hr>
      <?php foreach (mth_reimbursement::type_labels() as $key => $value): ?>
        <?php if (!mth_reimbursement::isTypeEnable($key)) {
    continue;
}?>
        <label>
          <input type="checkbox" name="types[]" class="type_cb_<?=$key?>" CHECKED value="<?=$key?>"> <?=mth_reimbursement::type_label($key)?> <small class="type_count_<?=$key?>"></small>
        </label>
      <?php endforeach;?>
    </div>
  </div>
</div>

<div class="card">
    <div class="card-header reimbursement-footer" style="text-align: right">
    <button type="button" onclick="paid()" class="btn btn-primary btn-round btnMarkPaid">Mark as Paid</button>
    <button type="button" onclick="deleteSelected()" class="btn btn-danger btn-round delete-btn">Delete</button>
  </div>
  <div id="mth_reimbursement-admin-table_holder" class="card-block pl-0 pr-0">
    <table id="mth_reimbursement-admin-table" class="table responsive">
      <thead>
        <tr>
          <th><input type="checkbox" class="check-all" title="Un/Select All" onclick="$('.rrCB').prop('checked',rrCBmaster = !window.rrCBmaster)"></th>
          <th>Student</th>
          <th>Parent</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Amount</th>
          <th>Paid/Ordered</th>
          <th>Period</th>
          <th>Method</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
</div>
<div id="mth_reimursements_total" style="display: none; align-self: center"></div>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>


<script>
  let runningPaid=false;//flag if paidfunction is running
  var $tableHolder;
  var $DataTable;
  var $filters;
  var $statusCbs = {};
  var active_page = localStorage.getItem('reimbursement_page');
  var totalCheckedIds = [];
  let previousPage = 0;
  var createDataRowObject = function(reimbursementObj) {

    return {
      DT_RowId: 'reimbursement-' + reimbursementObj.id,
      DT_RowClass: 'mth_reimbursement-' + reimbursementObj.status.replace(/ /g, '') + (reimbursementObj.is_second ? 'Second' : ''),
      cb: '<input type="checkbox" class="rrCB" value="' + reimbursementObj.id + '">',
      student: reimbursementObj.student,
      parent: reimbursementObj.parent,
      status: '<a class="link" onclick="edit(' + reimbursementObj.id + ')">' + reimbursementObj.status + '</a>',
      date_submitted: reimbursementObj.date_submitted,
      amount: reimbursementObj.amount,
      date_paid: reimbursementObj.date_paid,
      schedule_period: reimbursementObj.schedule_period,
      reimbursement_method: reimbursementObj.reimbursement_method,
    }
  };

  var PAGE_SIZE = <?=\mth\Reimbursement\Query::PAGE_SIZE?>;

  function loadReimbursements(nextPage) {

    if (loadReimbursements.busy && !nextPage) {
      return;
    }
    loadReimbursements.busy = true;
    if (nextPage) {
      loadReimbursements.page += 1;
    }

    var data = {
      statuses: $filters.find('input[name="statuses[]"]:checked').map(function() {
        return this.value
      }).get(),
      year: $filters.find('select[name="year"]').val(),
      types: $filters.find('input[name="types[]"]:checked').map(function() {
        return this.value
      }).get(),
      methods: $filters.find('input[name="methods[]"]:checked').map(function() {
        return this.value
      }).get()
    };

    var dataStr = JSON.stringify(data);
    if (!loadReimbursements.data || loadReimbursements.data !== dataStr) {
      $tableHolder.addClass('waiting');
      loadReimbursements.resetTable = true;
      loadReimbursements.page = 1;
      window.statusCountComplete = false;
    }
    loadReimbursements.data = dataStr;
    if (loadReimbursements.last_modified && loadReimbursements.page === 1 && !loadReimbursements.resetTable) {
      data.modified_since = loadReimbursements.last_modified.toLocaleString();
      delete data.statuses;
      delete data.types;
      delete data.methods;
    }

    data.page = loadReimbursements.page;

    $.ajax({
      url: '?loadReimbursements=1',
      method: 'get',
      cache: false,
      data: data,
      dataType: 'json',
      success: function(response) {
        if (!response) {
          swal('', 'Unable to load reimbursements', 'error');
          response = [];
        }
        if (loadReimbursements.resetTable) {
          $DataTable.rows().remove();
          $DataTable.draw();
          loadReimbursements.resetTable = false;
        }
        var fullRedraw = false;

        $.each(response, function(index, reimbursementObj) {
          var last_modified = new Date(reimbursementObj.last_modified);
          if ((!loadReimbursements.last_modified ||
              loadReimbursements.last_modified < last_modified) &&
            reimbursementObj.last_modified
          ) {
            loadReimbursements.last_modified = last_modified;
          }

          var rowData = createDataRowObject(reimbursementObj);
          var $row = $DataTable.row($('#' + rowData.DT_RowId));
          if ($row.length > 0) {
            if (!$statusCbs[reimbursementObj.status_id].prop('checked')) {
              $row.remove().draw(false);
            } else {
              $row.data(rowData).draw(false);
            }
          } else if ($statusCbs[reimbursementObj.status_id].prop('checked')) {
            $DataTable.row.add(rowData);
            fullRedraw = true;
          }
        });


        if (fullRedraw) {
          $DataTable.draw();
        }
        if (response.length < PAGE_SIZE) {
          $tableHolder.removeClass('waiting');
          loadReimbursements.page = 1;
          loadReimbursements.busy = false;
        } else {
          loadReimbursements(true);
          // $tableHolder.removeClass('waiting');
          // loadReimbursements.page = 1;
          // loadReimbursements.busy = false;
        }
      },
      error: function() {
        swal('', 'Unable to load reimbursements', 'error');
      }
    })
  }

  function edit(reimbursementID, type) {
    if (type == <?=mth_reimbursement::TYPE_DIRECT?>) {
      global_popup_iframe('directdeduction', '/_/admin/reimbursements/direct?reimbursement=' + reimbursementID);
    } else {
      global_popup_iframe('mth_reimbursement-popup-form', '/_/admin/reimbursements/edit?reimbursement=' + reimbursementID);
    }
  }

  function paid() {
    // var rrIDs = selectedRequests();
    // if (rrIDs < 1) {
    //   return;
    // }
  

    getTotalCheckedIds(res=>{
       if(res == true){
         let arrayCheckedIds =[];
         if(totalCheckedIds.length > 0){
           console.log("totalCheckedIds: ", totalCheckedIds);
           totalCheckedIds.map(child=>{
           arrayCheckedIds= [...arrayCheckedIds, ...child.items]
           })
           console.log("arrayCheckedIds: ", arrayCheckedIds);

        //if it will reach here we set the Paid function execution as true 
        //if this is true, refresh data functions will not be called not unless it is done
        runningPaid = true;
        $('.btnMarkPaid').attr('disabled','disabled');
        $('.delete-btn').attr('disabled','disabled');
        $.ajax({
          url: '?markPaid=1',
          method: 'post',
          data: 'rrIDs=' + arrayCheckedIds.join('|'),
          success: function(response) {
            runningPaid = false;
            if (response === '0') {
              $('.btnMarkPaid').removeAttr('disabled');
              $('.delete-btn').removeAttr('disabled');
              swal('', 'Unable to mark reimbursements as paid', 'error');
            } else {
              $('.check-all').is(':checked') && $('.check-all').trigger('click');
              getCountFunction();//so it will reload
              getDiffFunction();//so it will reload
              loadReimbursements(1);//so it will reload
              $('.btnMarkPaid').removeAttr('disabled');
              $('.delete-btn').removeAttr('disabled');
            }
          }
        });
      }else{
           swal('', 'Select at least one reimbusement request', 'info');
         }
       }
     });
    
  }

  function getTotalCheckedIds(callback) {
    var rrIDs = [];
    var CBs = $('.rrCB:checked');

    if (CBs.length > 0) {
      CBs.each(function() {
        rrIDs.push(this.value);
      });
    }

    // var rrIDs = selectedRequests();
      if(rrIDs.length > 0){
        if(totalCheckedIds.length == 0){
          // console.log("empty")
            totalCheckedIds = [{
              "pageIndex":0,
              "items":rrIDs
          }]
        }else{
          // console.log("exist")
            totalCheckedIds[previousPage] = {
              "pageIndex":previousPage,
              "items":rrIDs
          }
        }
      }
      // console.log("totalCheckedIds: ", totalCheckedIds);
      callback(true);
  }

  function removeDeleted(id) {
    $DataTable.row($('#reimbursement-' + id)).remove().draw();
  }

  function deleteSelected() {
    var rrIDs = selectedRequests();
    if (rrIDs < 1) {
      return;
    }
    $.ajax({
      url: '?delete=1',
      method: 'post',
      data: 'rrIDs=' + rrIDs.join('|'),
      success: function(response) {
        if (response === '0') {
          swal('', 'Unable to delete some of the reimbursements', 'error');
        } else {
          $.each(rrIDs, function(index, id) {
            removeDeleted(id);
          });
        }
      }
    });
  }

  function selectedRequests() {
    var rrIDs = [];
    var CBs = $('.rrCB:checked');

    if (CBs.length < 1) {
      swal('', 'Select at least one reimbusement request', 'info');
    } else {
      CBs.each(function() {
        rrIDs.push(this.value);
      });
    }
    return rrIDs;
  }

  function setLastPage() {
    active_page && $DataTable.page(active_page).responsive.recalc().draw(false);
  }

  function getCountFunction(){
    if (window.statusCountBusy || ($tableHolder.hasClass('waiting') && window.statusCountComplete)) {
        return;
      }
      window.statusCountBusy = true;
      var _data = {
        statuses: $filters.find('input[name="statuses[]"]:checked').map(function() {
          return this.value
        }).get(),
        year: $filters.find('select[name="year"]').val(),
      };
      $.ajax({
        url: '?getCounts=1',
        method: 'get',
        data: _data,
        cache: false,
        success: function(response) {
          $.each(response.status, function(status, count) {
            $('.status_count_' + status).html(count);
          });
          $.each(response.type, function(ptype, count) {
            $('.type_count_' + ptype).html("(" + count + ")");
          });
          $.each(response.method, function(method, count) {
            $('.method_count_' + method).html(count);
          });
          window.statusCountBusy = false;
          window.statusCountComplete = true;
        }
      });
  }
  
  function getDiffFunction(){
    if (window.statusCountBusy || ($tableHolder.hasClass('waiting') && window.statusCountComplete)) {
        return;
      }
      window.statusCountBusy = true;
    $.ajax({
        url: '?getDiff=1',
        method: 'get',
        cache: false,
        success: function(response) {
          if (response * 1 > 0) {
            $('#time-diff:hidden').fadeIn();
          } else {
            $('#time-diff').hide();
          }
          window.statusCountBusy = false;
          window.statusCountComplete = true;
        }
      });
  }

  $(function() {
    $filters = $('#mth_reimbursement-admin-filters');
    <?php foreach (mth_reimbursement::availableStatuses() as $status => $label) {?>
      $statusCbs[<?=$status?>] = $filters.find('.status_cb_<?=$status?>');
    <?php }?>
    $tableHolder = $('#mth_reimbursement-admin-table_holder');
    $DataTable = $('#mth_reimbursement-admin-table').DataTable({
      bStateSave: false,
      pageLength: 25,
      columns: [{
          data: 'cb',
          sortable: false
        },
        {
          data: 'student'
        },
        {
          data: 'parent'
        },
        {
          data: 'status'
        },
        {
          data: 'date_submitted',
          type: "date"
        },
        {
          data: 'amount',
          className: 'mth_reimbursement_amount'
        },
        {
          data: 'date_paid'
        },
        {
          data: 'schedule_period'
        },
        {
          data: 'reimbursement_method'
        },
      ],
      aaSorting: [
        [4, 'asc'],
        [1, 'asc']
      ],
      dom: "<'row'<'col-md-6 left-p'p><'col-md-6'f>>" +
        "<'row'<'col-md-12'tr>>" +
        "<'row'<'col-md-6'l><'col-md-6'p>>",
    });
    var $total = $('#mth_reimursements_total');

    setInterval(function() {
      if(runningPaid ==false){//if markPaid Function is still running no interval calls would be executed.
        var btn = $('.delete-btn');
        var btnoffset = btn.offset();
        loadReimbursements();
        var total = 0;
        $('.rrCB:checked').parent().siblings('.mth_reimbursement_amount').each(function() {
          total += Number(this.innerHTML.replace(/[^\d.-]/g, ''));
        });

        if ($('.rrCB:checked').length > 0) {
          $total.show().html(total.toFixed(2));
          $total.css('left', '47%');


          var scrollHeight = $(document).height();
          var scrollPosition = $(window).height() + $(window).scrollTop();
          var isbottom = ((scrollHeight - scrollPosition) / scrollHeight) == 0;

          if (isbottom) {
            $total.css('top', $(document).height() - 170);
            $total.removeClass('fixed');
          } else {
            $total.css('top', 'auto');
            $total.addClass('fixed');
          }

        } else {
          $total.hide();
        }
      }

    }, 2000);
    setInterval(function() {
      if(runningPaid ==false){//if markPaid Function is still running no interval calls would be executed.
      //count function 
      getCountFunction();
    }
    }, 5000);

    setInterval(function() {
      if(runningPaid ==false){ //if markPaid Function is still running no interval calls would be executed.
      //getDiffFunction
      getDiffFunction();
    }
    }, 6000);

    $DataTable.on('page.dt', function() {
      // var page = ($DataTable.page.info()).page;
      //     console.log("page: ", page)
      //     previousPage = page;
      //     localStorage.setItem('reimbursement_page', page);

      getTotalCheckedIds(res=>{
        if(res == true){
          var page = ($DataTable.page.info()).page;
          // console.log("page: ", page)
          previousPage = page;
          localStorage.setItem('reimbursement_page', page);
        }
      });
    });
  });
</script>