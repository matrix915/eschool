$(function(){
    var SITE = Site.getInstance();

    function quickSearchView() {
		var id = $('#quick_search_selected').val();
		if (id) {
			global_popup_iframe('mth_people_edit', '/_/admin/people/edit?' + id.replace(':', '='));
		}
	}

    $('#site-navbar-search').on('shown.bs.collapse', function (e) {
		$('.quick_search').focus();
    });
    var cache = {};
	$(".quick_search").mouseup(function (e) {
		return false;
	}).focus(function () {
		$(this).select();
	}).autocomplete({
		minLength: 3,
		source: function (request, response) {
			var term = request.term;
			if (term in cache) {
				response(cache[term]);
				return;
			}

			$.getJSON("/_/admin/people/search", request, function (data, status, xhr) {
				cache[term] = data;
				response(data);
			});
		},
		select: function (event, ui) {
			$('#quick_search_selected').val(ui.item.id);
			quickSearchView();
		}
	});

	/**
	 * Side Menubar Swipe
	 */
	$(".site-menubar").swipe({
		swipeStatus:function(event, phase, direction, distance, duration, fingers)
			{
					if (phase=="move" && direction =="right") {
								SITE.menubar.change('open');
								SITE.menubarType('open');
							
								return false;
					}
					if (phase=="move" && direction =="left") {

						SITE.menubar.change('hide');
						SITE.menubarType('hide');
							return false;
					}
			}
	});

	$('.site-menubar-body .has-sub>a').attr('href','#more').click(function(){
		var icon = $(this).find('i.fa');
		if($(this).closest('.has-sub').hasClass('open')){
			icon.removeClass('fa-chevron-down');
			icon.addClass('fa-chevron-right');
		}else{
			icon.removeClass('fa-chevron-right');
			icon.addClass('fa-chevron-down');
		}
	});

	/**
	 * Side MenuBar handler
	 */
	
	if($.inArray(SITE.getCurrentBreakpoint(),['lg','md']) == -1){
		setTimeout(function(){
			SITE.menubar.change('hide');
			SITE.menubarType('hide');
		});
	}
	
});