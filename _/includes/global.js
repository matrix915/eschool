$(function () {
    //$('ul.cms_nav a[href="' + location.pathname + '"]').parents('li').addClass('selected');
    $('ul.cms_nav a[href="' + location.pathname + '"]').parents('li').addClass('active');
});

function global_popup(popUpID) {

    if (global_popup.popUps === undefined) {
        global_popup.popUps = {};
        global_popup.openPopUps = [];
    }
    if (global_popup.bg === undefined) {
        global_popup.bg = $('#global_popup_bg');
        global_popup.body = $('body');
        if (global_popup.bg.length < 1) {
            global_popup.bg = $('<div></div>', {'id': 'global_popup_bg'});
            global_popup.bg.appendTo(global_popup.body);
        }
    }

    if (global_popup.popUps[popUpID] === undefined) {
        global_popup.popUps[popUpID] = $('#' + popUpID);
        if (global_popup.popUps[popUpID].length < 1)
            return false;
        if (!global_popup.popUps[popUpID].hasClass('global_popup')) {
            global_popup.popUps[popUpID].addClass('global_popup');
        }
        global_popup.popUps[popUpID].appendTo(global_popup.bg);
    }
    global_popup.bg.fadeIn();
    global_popup.body.css({'overflow': 'hidden'});
    global_popup.popUps[popUpID].fadeIn().siblings().hide();
    if (global_popup.openPopUps.indexOf(popUpID) !== -1) {
        global_popup.openPopUps.splice(global_popup.openPopUps.indexOf(popUpID), 1);
    }
    global_popup.openPopUps.push(popUpID);
}

function global_popup_iframe(popUpID, url, fullWindow) {
    if (global_popup_iframe[popUpID] === undefined) {
        global_popup_iframe[popUpID] = {};
        global_popup_iframe[popUpID].popUp = $('<div></div>',
            {
                'id': popUpID,
                'class': 'global_popup_iframe ' + (fullWindow ? 'global_popup_iframe_full' : '')
            });
        global_popup_iframe[popUpID].popUpFrame = $('<iframe></iframe>', {'src': '/_/includes/img/loading.gif'});
        global_popup_iframe[popUpID].popUp.append(global_popup_iframe[popUpID].popUpFrame);
        global_popup_iframe[popUpID].popUp.appendTo($('body'));
    }
    global_popup_iframe[popUpID].popUpFrame.attr({'src': url});
    global_popup(popUpID);
}

function global_popup_iframe_close(popUpID) {
    if (global_popup_iframe[popUpID])
        global_popup_iframe[popUpID].popUpFrame.attr({'src': '/_/includes/img/loading.gif'});
    global_popup_close(popUpID);
}

function global_popup_close(popUpID) {
    var lastPopUp = global_popup.openPopUps.pop();
    if (popUpID !== lastPopUp || global_popup.openPopUps.length === 0) {
        global_popup.openPopUps = [];
        global_popup.bg.fadeOut();
        global_popup.body.css({'overflow': ''});
        global_popup.popUps[popUpID].fadeOut();
    } else {
        global_popup.popUps[popUpID].hide();
        global_popup.popUps[global_popup.openPopUps[global_popup.openPopUps.length - 1]].fadeIn();
    }
}

/**
 *
 * @param string message
 * @param function affirmativeFunction
 * @param string okButtonText
 * @param string cancelButtonText
 * @param function cancelFunction
 */
function global_confirm(message, affirmativeFunction, okButtonText, cancelButtonText, cancelFunction) {
    if (global_confirm.popUp === undefined) {
        global_confirm.popUp = $('<div></div>', {'id': 'global_confirm_popup'});
        global_confirm.popUpConent = $('<div></div>');
        global_confirm.popUpOK = $('<input>', {'type': 'button'});
        global_confirm.popUpCancel = $('<input>', {'type': 'button'});
        global_confirm.popUp.append(global_confirm.popUpConent, global_confirm.popUpOK, global_confirm.popUpCancel);
        global_confirm.popUp.appendTo($('body'));
    }
    global_confirm.popUpConent.html(message);
    global_confirm.popUpOK.unbind('click').click(affirmativeFunction);
    global_confirm.popUpCancel.unbind('click').click((cancelFunction !== undefined ? cancelFunction : function () {
        global_popup_close('global_confirm_popup')
    }));

    global_confirm.popUpOK.val(okButtonText !== undefined ? okButtonText : 'OK');
    global_confirm.popUpCancel.val(cancelButtonText !== undefined ? cancelButtonText : 'Cancel');

    global_popup('global_confirm_popup');
}

function global_alert(message, focusOnClose, buttonText) {
    if (global_alert.popUp === undefined) {
        global_alert.popUp = $('<div></div>', {'id': 'global_alert_popup'});
        global_alert.popUpConent = $('<div></div>');
        global_alert.popUpOK = $('<input>', {'type': 'button'});
        global_alert.popUp.append(global_alert.popUpConent, global_alert.popUpOK);
        global_alert.popUp.appendTo($('body'));
    }
    global_alert.popUpConent.html(message);
    global_alert.popUpOK.val(buttonText !== undefined ? buttonText : 'OK');
    global_alert.popUpOK.unbind('click').click(function () {
        global_popup_close('global_alert_popup');
        if (focusOnClose) {
            focusOnClose.focus();
        }
    });

    global_popup('global_alert_popup');
}

function showContentEditForm(contentID, pagePath, createNew, destination) {
    var url = '/_/admin/content/edit?contentID=' + contentID +
        '&pagePath=' + pagePath +
        (createNew ? '&newPage=1' : '') +
        '&destination=' + destination;
    global_popup_iframe('content_edit_popup', url);
}

function setCookie(c_name, value, exdays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate() + exdays);
    var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
    document.cookie = c_name + "=" + c_value;
}
function getCookie(c_name) {
    var c_value = document.cookie;
    var c_start = c_value.indexOf(" " + c_name + "=");
    if (c_start == -1) {
        c_start = c_value.indexOf(c_name + "=");
    }
    if (c_start == -1) {
        c_value = null;
    } else {
        c_start = c_value.indexOf("=", c_start) + 1;
        var c_end = c_value.indexOf(";", c_start);
        if (c_end == -1) {
            c_end = c_value.length;
        }
        c_value = unescape(c_value.substring(c_start, c_end));
    }
    return c_value;
}

// for IE8 and lower
if (!Array.indexOf) {
    Array.prototype.indexOf = function (obj) {
        for (var i = 0; i < this.length; i++) {
            if (this[i] == obj) {
                return i;
            }
        }
        return -1;
    }
}

function global_waiting() {
    if (global_waiting.div === undefined) {
        global_waiting.div = $('<div></div>', {'id': 'global_waiting'});
        $('body').append(global_waiting.div);
    }
    global_waiting.div.fadeIn();
}
function global_waiting_hide() {
    if (global_waiting.div) {
        global_waiting.div.fadeOut();
    }
}