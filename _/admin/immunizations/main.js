function editImmunization(immunization_id) {
    global_popup_iframe('mth_subject_editor', '/_/admin/immunizations/edit?immunization_id=' + immunization_id, true);
}

function showHideImmunization(id) {
    var event = $('#mth_immunization-'+id);
    var target_tr = $('#mth_immunization-tr-'+id);

    if (target_tr.is(':visible')) {
        target_tr.hide();
        event.removeClass('mth_subject-expanded');
    } else {
        console.log(target_tr);
        target_tr.show();
        event.addClass('mth_subject-expanded').siblings().removeClass('mth_subject-expanded');
    }
}

$(function () {
    var hash = location.hash.replace('#', '');
    if (hash) {
        showHideImmunization(hash.replace(/[^\-]+-/, ''), hash.replace(/-.+/, ''));
    }
});