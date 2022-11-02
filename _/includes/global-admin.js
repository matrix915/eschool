function showContentEditForm(contentID, pagePath, createNew, destination) {
    if (showContentEditForm.popUp === undefined) {
        showContentEditForm.popUp = $('<div></div>', {'id': 'content_edit_popup'});
        showContentEditForm.popUpFrame = $('<iframe></iframe>', {'src': '/_/includes/img/loading.gif'});
        showContentEditForm.popUp.append(showContentEditForm.popUpFrame);
        showContentEditForm.popUp.appendTo($('body'));
    }
    showContentEditForm.popUpFrame.attr({
        'src': '/_/admin/content/edit?contentID=' + contentID +
        '&pagePath=' + pagePath +
        (createNew ? '&newPage=1' : '') +
        '&destination=' + destination
    });
    global_popup('content_edit_popup');
}

function deleteContent(contentID, destination, redirectFromPath) {
    deleteContent.contentID = contentID;
    deleteContent.destination = destination;
    deleteContent.redirectFromPath = redirectFromPath;

    swal({
        title: "",
        text: "Are you sure you want to delete this content?",
        type: "info",
        showCancelButton: true,
        confirmButtonClass: "btn-primary",
        confirmButtonText: "Yes",
        cancelButtonText: "No",
        closeOnConfirm: true,
        closeOnCancel: true
    },function(){
        location = '/_/admin/content?delete=' + deleteContent.contentID +
        '&destination=' + encodeURIComponent(deleteContent.destination) +
        '&redirect=' + encodeURIComponent(deleteContent.redirectFromPath);
    });
}