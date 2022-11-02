$(function(){
	jQuery.fn.dataTableExt && jQuery.extend(jQuery.fn.dataTableExt.oSort, {
		'dateNonStandard-asc': function (a, b) {
		var x = Date.parse(a);
		var y = Date.parse(b);
		if (x == y) { return 0; }
		if (isNaN(x) || x < y) { return 1; }
		if (isNaN(y) || x > y) { return -1; }
		},
		'dateNonStandard-desc': function (a, b) {
		var x = Date.parse(a);
		var y = Date.parse(b);
		if (x == y) { return 0; }
		if (isNaN(y) || x < y) { return -1; }
		if (isNaN(x) || x > y) { return 1; }
		}
	});
	
	var SITE = Site.getInstance();

	$('.info-collapse').on('show.bs.collapse',function(){
			$icon = $(this).closest('.container-collapse').find('.icon-collapse');
			$icon.removeClass('md-chevron-right');
			$icon.addClass('md-chevron-down');
	}).on('hide.bs.collapse',function(){
		$icon = $(this).closest('.container-collapse').find('.icon-collapse');
		$icon.removeClass('md-chevron-down');
		$icon.addClass('md-chevron-right');
	});

	$('.mth_schedule-table').length>0 && $('.mth_schedule-table').DataTable({
		stateSave: false,
		"ordering": false,
		"paging": false,
		"searching": false,
		"info": false,
		"columnDefs": [
			{ "width": "40%", "targets": 3 }
		]
	});
});