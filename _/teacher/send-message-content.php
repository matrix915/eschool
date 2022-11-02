<?php

use mth\yoda\homeroom\messages;
use mth\yoda\homeroom\messagesrecepient;
if (isset($_GET['publish'])) {
    if (empty(trim(req_post::txt('subject')))) {
        echo json_encode(['error' => 1, 'data' => ['id' => 0, 'msg' => 'Subject is required']]);
        exit();
    }

    if (empty(trim(req_post::html('content')))) {
        echo json_encode(['error' => 1, 'data' => ['id' => 0, 'msg' => 'Content is required']]);
        exit();
    }

    if (messages::publish(
        req_post::html('content'),
        req_post::txt('subject'),
        [req_post::txt('email')],
        isset($_GET['copy'])?[core_setting::get("homeroombcc", 'Homeroom')->getValue()]:null
    )) {
        $message = new messages();
        $message->setInsert('title', req_post::txt('subject'));
        $message->setInsert('content',req_post::html('content'));
        $message->setInsert('teacher_user_id',core_user::getCurrentUser()->getID());
        if($message->save()){

            foreach($_POST['student_id'] as $student){
                $recepient = new messagesrecepient();
                $recepient->setInsert('yoda_homeroom_messages_id',$message->getID());
                $recepient->setInsert('person_id',$student);
                $recepient->save();
            }
            
            echo json_encode(['error' => 0, 'data' => ['id' => req_post::int('parent_id'), 'msg' => 'Sent']]);
        }else{
            echo json_encode(['error' => 1, 'data' => ['id' => req_post::int('parent_id'), 'msg' => 'Unable to save']]);
        }
    } else {
        echo json_encode(['error' => 1, 'data' => ['id' => req_post::int('parent_id'), 'msg' => 'Error Sending']]);
    }
    
    exit();

}
//core_loader::includeCKEditor();
core_loader::isPopUp();
core_loader::printHeader();
?>

<div class="row">
    <div class="col-md-6">
        <form action="?form=<?= uniqid() ?>" method="post" id="announcement-form">
            <div class="form-group">
                <label>Subject</label>
                <input type="text" class="form-control" required name="subject" value="">
                <!-- <input type="hidden" name="id" value=""> -->
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" class="form-control" required id="announcement-content"></textarea>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Email Preview</h4>
                </div>
                <div id="email-preview" class="card-block cke-preview">
                    
                </div>
            </div>

            <p>
                <button  name="submit"type="button" onclick="publish()"  class="publish-btn btn btn-success btn-round" value="Save">Send</button>
                <button class="btn btn-secondary btn-round" type="button" onclick="top.global_popup_close('send_message_popup')">Close</button>
            </p>
        </form>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
               Parents Emails (<span class="parent-count"></span>)
            </div>
            <div class="card-block parent-list">
            </div>
        </div>
    </div>
</div>
<?php
core_loader::includejQueryValidate();
core_loader::printFooter();
?>
<script src="//cdn.ckeditor.com/4.10.1/full/ckeditor.js"></script>
<script>
tobesend = {};
vindex = 0;
errors = 0;
parentcount = 0;
function publish(){
    CKEDITOR.instances["announcement-content"].updateElement();
    
    var $publishbtn = $('.publish-btn');
    global_waiting();
    vinterval = setInterval(function(){
        var key = (Object.keys( tobesend ))[vindex++];
        var item =  tobesend[key];
        if(typeof item != 'undefined'){
            if(vindex == 1){
                _publish({
                    email: key,
                    student_id: item.students,
                    parent_id: item.parent_id
                },true);
            }else{
                _publish({
                    email: key,
                    student_id: item.students,
                    parent_id: item.parent_id
                });
            }
           
        }else{
            global_waiting_hide();
            clearInterval(vinterval);
            var message = "Done sending announcement to parents.";
            if(errors > 0){
                message += ' '+errors+' error(s) detected.';
            }

            if(errors == parentcount){
                message = 'There seems to be an issue sending the announcement.'
            }
            swal('',message,'');

        }
    },1000);
}

function _publish(item,copy){
    var _copy = copy != undefined?'&copy=1':'';
    $.ajax({
        'url': '?publish=1'+_copy,
        'type': 'post',
        'data': $('#announcement-form').serialize()+'&'+$.param(item),
        dataType: "json",
        success: function(response){
            
            if(response.error == 0){
                var data = response.data;
                $('#parent-'+data.id).find('.sent').fadeIn();
            }else{
                $('#parent-'+response.data.id).find('.error').fadeIn();
                errors++;
            }
        },
        error: function(){
            alert('there is an error occur when publishing');
        }
    });
}

$(function(){
    CKEDITOR.config.removePlugins = "iframe,print,format,pastefromword,pastetext,clipboard,about,image,forms,youtube,iframe,print,stylescombo,flash,newpage,save,preview,templates";
    CKEDITOR.config.disableNativeSpellChecker = false;
    CKEDITOR.config.removeButtons = "Subscript,Superscript";
     
    CKEDITOR.replace('announcement-content');
    CKEDITOR.instances["announcement-content"].on('change', function() { 
       $('#email-preview').html(this.getData());
    });
    
    CKEDITOR.on('dialogDefinition', function(ev) {
        try {
            var dialogName = ev.data.name;
            var dialogDefinition = ev.data.definition;

            if(dialogName == 'link') {
                var informationTab = dialogDefinition.getContents('target');
                var targetField = informationTab.get('linkTargetType');
                targetField['default'] = '_blank';
            }

        } catch(exception) {

            alert('Error ' + ev.message);

        }
    });
   
    var $parentlist = $('.parent-list');
    parent.$('.actionCB:checked').each(function(){
        var studentobj = ($(this).val()).split('::');
        var parent_email = studentobj[2];
        var student_id = studentobj[0];
        var parent_id = studentobj[1];
        var homeroom = studentobj[3];

        if(typeof tobesend[parent_email] == 'undefined'){
            tobesend[parent_email] = {
                parent_id: parent_id,
                students: []
            };
            
        }
        $('#parent-'+parent_id).length ==0 && $parentlist.append('<div id="parent-'+parent_id+'">'+
            '<i class="fa fa-check sent"  style="display:none;color:green;"></i>'+
            '<i class="fa fa-exclamation-circle error" style="display:none;color:red"></i>'+
            parent_email+
            '</div>');

        tobesend[parent_email].students.push(student_id);
    });
    parentcount = Object.keys(tobesend).length;
    $('.parent-count').text(parentcount);

    $('#announcement-form').validate({
        ignore: [],
        rules: { 
            content:{
                required: function(){
                    CKEDITOR.instances["announcement-content"].updateElement();
                }
            }
        },  
    });
});
</script>