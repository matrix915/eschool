<?php
use mth\student\SchoolOfEnrollment;

if (req_get::is_set('delete')) {
    mth_announcements::delete(req_get::int('delete'));
    core_notify::addMessage('Announcement Deleted');
    echo '<script>parent.location.reload();</script>';
    exit();
}

if (isset($_GET['publish'])) {
    if (empty(trim(req_post::txt('subject')))) {
        echo json_encode(['error' => 1, 'data' => ['id' => 0, 'msg' => 'Subject is required']]);
        exit();
    }

    if (empty(trim(req_post::html('content')))) {
        echo json_encode(['error' => 1, 'data' => ['id' => 0, 'msg' => 'Content is required']]);
        exit();
    }

    if (mth_announcements::publish(
        req_post::html('content'),
        req_post::txt('subject'),
        [req_post::txt('email')]
    )) {
        echo json_encode(['error' => 0, 'data' => ['id' => req_post::int('id'), 'msg' => 'Sent']]);
    } else {
        echo json_encode(['error' => 1, 'data' => ['id' => req_post::int('id'), 'msg' => 'Error Sending']]);
    }

    exit();

}

if (isset($_GET['markpublish'])) {
    $announcements = new mth_announcements();
    $announcements->setPublished(1);
    $announcements->setId(req_post::int('id'));
    $announcements->setDatePublished(date('Y-m-d H:i:s'));
    if ($announcements->save(req_post::html('content'), req_post::txt('subject'), req_post::txt('user_id'))) {
        core_notify::addMessage('Announcement Published');
    } else {
        core_notify::addMessage('Unable to mark announcement Published');
    }
    exit();
}

if (!empty($_GET['form'])) {

    if (empty(trim(req_post::html('subject')))) {
        core_notify::addError('Subject is required');
        core_loader::redirect();
        exit();
    }

    if (empty(trim(req_post::html('content')))) {
        core_notify::addError('Content is required');
        core_loader::redirect();
        exit();
    }

    $announcements = req_post::is_set('id') && intval(req_post::int('id')) > 0 ? mth_announcements::getContentById(req_post::int('id')) : new mth_announcements();
    
    if ($announcements->save(req_post::html('content'), req_post::txt('subject'))) {
        core_notify::addMessage('Announcement Posted');
        exit('<html><script>
        parent.location.reload(true);
        </script></html>');
    } else {
        core_notify::addError('There is a problem saving announcement');
    }

}

if (isset($_GET['getverified'])) {
    $search = new mth_person_filter();
    $search->setStatus([mth_student::STATUS_PENDING, mth_student::STATUS_ACTIVE]);
    $search->setStatusYear(
        [
            mth_schoolYear::getCurrent()->getID(),
        ]
    );

    if (isset($_GET['grade']) && !empty($_GET['grade'])) {
        $search->setGradeLevel(req_get::txt_array('grade'));
    }

    $search->setObserver(true);
    if (isset($_GET['midyear'])) {
        $search->setMidYear(true);
    }

    if (isset($_GET['SoeGrade']) && !empty($_GET['SoeGrade'])) {
        $search->setSchoolOfEnrollment(req_get::txt_array('SoeGrade'));
    }

    $people = $search->getParents();

    $verifieds = [];

    foreach ($people as $person) {
        if (($verified = mth_emailverifier::getByUserId($person->getUserID())) && $verified->isVerified()) {
            $verifieds[] = [
                'user_id' => $person->getUserID(),
                'email' => $person->getEmail(),
                'name' => $person->getName(),
            ];
        }
    }

    echo json_encode(['data' => $verifieds]);
    exit();
}

$contentStr = '';
$subject = '';
$id = 0;
if (isset($_GET['id'])) {
    $announcement = mth_announcements::getContentById($_GET['id']);
    $contentStr = $announcement->getContent();
    $subject = $announcement->getSubject();
    $id = $_GET['id'];
}

//core_loader::includeCKEditor();
cms_page::setPageTitle('Add Announcements');
core_loader::isPopUp();
core_loader::printHeader();

$admins = [];
?>
<div class="row">
    <div class="col-md-6">
        <form action="?form=<?=uniqid()?>" method="post" id="announcement-form">
            <div class="form-group">
                <label>Subject</label>
                <input type="text" class="form-control" required name="subject" value="<?=$subject?>">
                <input type="hidden" name="id" value="<?=$id?>">
                <input id="parent_user_ids" type="hidden" name="user_id" value="">
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" class="form-control"
                    id="announcement-content"><?=htmlentities($contentStr)?></textarea>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Email Preview</h4>
                </div>
                <div id="email-preview" class="card-block cke-preview">
                    <?=$contentStr?>
                </div>
            </div>

            <p>
                <button name="submit" type="submit" class="btn btn-success btn-round" value="Save">Save</button>
                <button name="submit" type="button" onclick="publish()" class="publish-btn btn btn-primary btn-round"
                    value="Publish"><i class="fa fa-paper-plane"></i> <span class="publish-txt">Publish</span></button>
                <button class="btn btn-secondary btn-round" type="button"
                    onclick="top.global_popup_iframe_close('announcenment_create_popup')">Close</button>
                <?php if ($id != 0): ?>
                <button type="button" name="Delete" class="btn btn-danger btn-round"
                    onclick=delete_announcement()>Delete</button>
                <?php endif;?>
            </p>
        </form>
    </div>
    <div class="col-md-6">
        <div class="card container-collapse">
            <div class="card-header">
                <h4 class="card-title mb-0" data-toggle="collapse" href="#intervention-filter-cont"
                    aria-controls="intervention-filter-cont">
                    <i class="panel-action icon md-chevron-down icon-collapse"></i> Filter
                </h4>
            </div>
            <div class="card-block show info-collapse collapse" id="intervention-filter-cont">
                <fieldset class="block grade-levels-block">

                    <div class="grade-selector-two-item-container">
                        <div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" value="midyear" name="midyear">
                                <label>
                                    Mid-year Enrollees
                                </label>
                            </div>
                            <strong>Parent(s) with student grade level of:</strong>

                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="grade_selector" value="gAll">
                                <label>
                                    All Grades
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="grade_selector" value="gKto8">
                                <label>
                                    Grades OR K-8
                                </label>
                            </div>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" class="grade_selector" value="g9to12">
                                <label>
                                    Grades 9-12
                                </label>
                            </div>
                        </div>

                        <div class="grade-selector-new-soe-item-container">
                            <strong class="grade-selector-new-item-title-text-style">SoE:</strong>
                            <?php foreach (SchoolOfEnrollment::getActive() as $grade => $name) {?>
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" name="SoeGrade[]" value="<?=$grade?>" class="soe_grade_selector">
                                <label>
                                    <?=$name?>
                                </label>
                            </div>
                            <?php }?>
                        </div>
                    </div>

                    <hr>
                    <div class="grade-level-list">
                        <?php foreach (mth_student::getAvailableGradeLevelsNormal() as $grade => $name) {?>
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" name="grade[]" value="<?=$grade?>">
                            <label>
                                <?=$name?>
                            </label>
                        </div>
                        <?php }?>
                    </div>
                </fieldset>
                <button class="btn btn-primary filter-grade">Filter</button>
            </div>
        </div>
        <div class="card">
            <div class="card-block">
                <div class="verified-container">
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                List of Admins
            </div>
            <div class="card-block">
                <?php foreach (core_user::getUsers(mth_user::L_ADMIN) as $admin): ?>
                <?php
$admins[] = [
    'user_id' => $admin->getID(),
    'email' => $admin->getEmail(),
];
?>
                <div id="admin-<?=$admin->getID()?>">
                    <i class="fa fa-check sent" style="display:none;color:green;"></i>
                    <i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>
                    <?=$admin->getEmail()?>
                </div>
                <?php endforeach;?>
            </div>
        </div>
    </div>
</div>

<style>
.grade-selector-two-item-container {
    display: flex;
    justify-content: flex-start;
    align-items: flex-start;
}

.grade-selector-new-soe-item-container {
    margin-left: 165px;
    /* margin-top: -20px; */
}

.grade-selector-new-item-title-text-style {
    color: #757575;
}

@media(max-width:768px) {
    .grade-selector-two-item-container {
        flex-direction: column;
        align-items: flex-start;
    }

    .grade-selector-new-soe-item-container {
        margin-left: 0px;
        margin-top: 5px;
    }
}
</style>

<?php
core_loader::includejQueryValidate();
core_loader::addJsRef('gradeleveltool', core_config::getThemeURI() . '/assets/js/gradelevel.js');
core_loader::printFooter();
?>
<script src="//cdn.ckeditor.com/4.10.1/full/ckeditor.js"></script>
<script type="text/javascript">
var aid = <?=$id?>;
var vindex = 0;
var aindex = 0;
verified = [];
jsonAdmins = [];
admins = '<?=json_encode($admins)?>';
var errors = 0;
CKEDITOR.config.removePlugins =
    "iframe,print,format,pastefromword,pastetext,about,image,forms,youtube,iframe,print,stylescombo,flash,newpage,save,preview,templates";
CKEDITOR.config.disableNativeSpellChecker = false;
CKEDITOR.config.removeButtons = "Subscript,Superscript";

CKEDITOR.replace('announcement-content');
CKEDITOR.instances["announcement-content"].on('change', function() {
    $('#email-preview').html(this.getData());
});

CKEDITOR.on('dialogDefinition', function(e) {
    if (e.data.name === 'link') {
        var target = e.data.definition.getContents('target');
        var options = target.get('linkTargetType').items;
        var targetField = target.get('linkTargetType');
        targetField['default'] = '_blank';
    }
});

function delete_announcement() {
    swal({
            title: "",
            text: "Are you sure you want to delete this announcement?",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-warning",
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            closeOnConfirm: true,
            closeOnCancel: true
        },
        function() {
            location = '?delete=' + aid;
        });
}

function publish() {
    CKEDITOR.instances["announcement-content"].updateElement();
    if (verified.length == 0) {
        toastr.error('There is no active and verified parent to send the annoucement for');
        return;
    }

    $('.publish-btn').attr('disabled', 'disabled').find('.publish-txt').text('Publishing..');

    $('i.sent').fadeOut();
    $('i.error').fadeOut();
    
    vindex = 0;
    vinterval = setInterval(function() {
        var item = verified[vindex++];
        if (typeof item != 'undefined') {
            _publish(item, 'parent');
        } else {
            clearInterval(vinterval);
            cc();
        }
    }, 1000);
}

function cc() {
    jsonAdmins = $.parseJSON(admins);

    aindex = 0;
    ainterval = setInterval(function() {
        var item = jsonAdmins[aindex++];
        if (typeof item != 'undefined') {
            _publish(item, 'admin');
        } else {
            clearInterval(ainterval);
            markpublish();
        }
    }, 1000);
}

function _publish(item, user) {
    $.ajax({
        'url': '?publish=1',
        'type': 'post',
        'data': $('#announcement-form').serialize() + '&email=' + encodeURIComponent(item.email) + '&id=' + item
            .user_id,
        dataType: "json",
        success: function(response) {
            if (response.error == 0) {
                var data = response.data;
                $('#' + user + '-' + data.id).find('.sent').fadeIn();
            } else {
                $('#' + user + '-' + response.data.id).find('.error').fadeIn();
                errors++;
            }
        },
        error: function() {
            errors++;
            $('#' + user + '-' + item.user_id).find('.error').fadeIn();
        }
    });
}

function getVeried() {
    var grade = $('[name="grade[]"]:checked').serialize();
    var _grade = grade != '' ? '&' + grade : '';
    var midyear = $('[name="midyear"]').is(':checked') ? '&midyear=1' : '';

    var soe_grade = $('[name="SoeGrade[]"]:checked').serialize();
    var _soe_grade = soe_grade != '' ? '&' + soe_grade : '';

    $.ajax({
        'url': '?getverified=1' + _grade + midyear + _soe_grade,
        'type': 'get',
        dataType: 'JSON',
        success: function(response) {
            verified = response.data;
            $('.verified-container').html('');
            if (response.data.length > 0) {
                var ids = "";
                $.each(response.data, function(i, value) {
                    $('.verified-container').append('<div id="parent-' + value.user_id +
                        '"><i class="fa fa-check sent"  style="display:none;color:green;"></i><i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>' +
                        value.name + '(' + value.email + ')</div>');
                    if(ids === "") {
                        ids = value.user_id;
                    }else
                        ids = ids+", "+value.user_id;
                });
                $('#parent_user_ids').val(ids);
            }
            global_waiting_hide();
        },
        error: function() {
            alert('There is an error marking announcement as Published');
            global_waiting_hide();
        }
    });
}


function markpublish() {
    $.ajax({
        'url': '?markpublish=1',
        'type': 'post',
        'data': $('#announcement-form').serialize(),
        success: function() {
            $('.publish-btn').removeAttr('disabled').find('.publish-txt').text('Publish');

            if (errors == verified.length) {
                swal('', 'There\'s seem to be an issue in sending announcements.', 'error');
            } else if (errors > 0) {
                swal('', 'Anouncement sent to parents. Found ' + errors +
                    ' error(s) on sending announcement.', 'warning');
            } else {
                swal('', 'Done sending announcements to parents.', 'success');
            }

        },
        error: function() {
            alert('There is an error marking announcement as Published');
        }
    });
}

$(function() {
    $('#announcement-form').validate({
        ignore: [],
        rules: {
            content: {
                required: function() {
                    CKEDITOR.instances["announcement-content"].updateElement();
                }
            }
        },
    });

    $('.filter-grade').click(function() {
        global_waiting();
        getVeried();
    });
});
</script>