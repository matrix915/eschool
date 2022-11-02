$(function () {
    $('.form-progress a[href="' + location.pathname + '"]').parent('li').addClass('selected');
    $('.form-progress li:not(:first-child).selected').prepend('<span></span>');
});