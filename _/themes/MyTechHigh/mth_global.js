$(function () {
    $('.borderLess').hover(
        function () {
            $(this).removeClass('borderLess');
        },
        function () {
            $(this).addClass('borderLess');
        })
        .focus(function () {
            $(this).removeClass('borderLess');
        })
        .blur(function () {
            $(this).addClass('borderLess');
        });
    var mth_scheduleCollapse = $('.mth_schedule-collapse div');
    mth_scheduleCollapse.wrap('<div></div>');
    mth_scheduleCollapse.each(function () {
        var content = $(this);
        var parent = content.parent();
        if (content.height() > parent.height()) {
            var a = $('<a></a>');
            a.click(function () {
                $(this).prev().toggleClass('expanded');
            });
            parent.after(a);
        }
    });
    /*if($('body.admin').length>0){
        $.ajax({
            url:'/_/admin/file-clean-up',
            cache:false
        })
    }*/
});