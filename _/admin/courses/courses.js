function editSubject(subject_id) {
    global_popup_iframe('mth_subject_editor', '/_/admin/courses/subject?subject_id=' + subject_id);
}

function editCourse(course_id) {
    global_popup_iframe('mth_course_editor', '/_/admin/courses/course?course_id=' + course_id);
}

function editProvider(provider_id) {
    global_popup_iframe('mth_provider_editor', '/_/admin/courses/provider?provider_id=' + provider_id);
}

function editProviderCourse(course_id) {
    global_popup_iframe('mth_course_editor', '/_/admin/courses/provider_course?course_id=' + course_id);
}

function showHideCourses(id, type) {
    var coursesRow = $('#mth_' + type + '-' + id + '-courses');
    var subjectRow = $('#mth_' + type + '-' + id);
    if (coursesRow.is(':visible')) {
        location.hash = '';
        coursesRow.hide();
        subjectRow.removeClass('mth_subject-expanded');
    } else {
        subjectRow.parents('table.formatted').find('.mth_subject-courses').hide();
        location.hash = type + '-' + id;
        coursesRow.show();
        subjectRow.addClass('mth_subject-expanded').siblings().removeClass('mth_subject-expanded');
        var content = coursesRow.find('.mth_course-container');
        if (content.html() == '') {
            content.html('<img src="/_/includes/img/loading.gif" alt="Loading...">');
            content.load('/_/admin/courses/ajax?' + type + '_courses=' + id);
        }
    }
}

$(function () {
    var hash = location.hash.replace('#', '');
    if (hash) {
        showHideCourses(hash.replace(/[^\-]+-/, ''), hash.replace(/-.+/, ''));
    }
});