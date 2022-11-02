<?php
core_user::getUserLevel() || core_secure::loadLogin();

$user = core_user::getCurrentUser();

if(req_get::bool('archive')){
    if($user_pref = mth_userannouncement::get($user->getID(),req_get::int('archive'))){
        if($user_pref->archive()){
            echo json_encode(['error'=>0,'data'=> req_get::int('archive')]);
        }else{
            echo json_encode(['error'=>1,'data'=> 'Unable to archive announcement']);
        }
    }else{
        echo json_encode(['error'=>1,'data'=> 'Announcement not found']);
    }            
    exit();
}

cms_page::setPageTitle('Announcements');
cms_page::setPageContent('');
core_loader::printHeader('student');
$announcements = mth_announcements::getAllAnnouncements(false);
$user->setRedAnnouncements(count($announcements));
core_user::setCurrentUser($user);
?>
<style>
    .page{
        background: #1e88e5;
    }
    .archived-announcement{
        display:none;
    }
    .announcement-cont.collapsed .cke-preview{
        display:none;
    }
    .announcement-cont{
        cursor:pointer;
    }
</style>

<div class="page">
    <?= core_loader::printBreadCrumb('window');?>
    <div class="page-content container-fluid">
        <div class="card">
            <div class="card-block">
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="showarchive" id="showarchive"> 
                    <label>Show Archived Announcements</label>
                </div>
            </div>
        </div>
        <?php $acount = 0;?>
        <?php foreach($announcements as $key=>$announcement):?>
            <?php 
                if(!($user_pref = mth_userannouncement::get($user->getID(),$announcement->getID()))){
                    $user_pref = new mth_userannouncement();
                    $user_pref->user_id($user->getID());
                    $user_pref->announcement_id($announcement->getID());
                    $user_pref->save();
                }
                $is_archive = $user_pref->isArchived();
                $firstone = $acount==0;
                $content = $announcement->getContent();
            ?>
       
            <div class="announcement-cont card <?=$is_archive?'archived-announcement':''?> <?=!$firstone?'collapsed':''?>" id="announcement-<?=$announcement->getID()?>" data-id="<?=$announcement->getID()?>">
                <div class="card-header">
                    <a class="float-right acrhive-btn" data-id="<?=$announcement->getID()?>" title="Archive"><i class="fa fa-archive"></i></a>
                    <h4 class="card-title mb-0"><span style="color:#757575;margin-right: 30px;"><?=date('M j, Y', $announcement->getDatePublished())?></span> <?=$announcement->getSubject()?></h4>
                </div>
                <div class="card-block cke-preview">
                    <?=$content?>
                    <br>
                    <small>Posted by <?=$announcement->getPostedBy()?></small>
                </div>
            </div>
            <?php $acount++;?>
        <?php endforeach;?>
    </div>
</div>
<?php
core_loader::printFooter('student');
?>

<script>
    function more(id){
        $('#short-content-'+id).hide();
        $('#real-content-'+id).show();
    }

    $(function(){
        $('.announcement-cont').click(function(){
            var announcement = $(this).data();
            if($(this).hasClass('collapsed')){
                $(this).removeClass('collapsed');
            }else{
                $(this).addClass('collapsed');
            }
        });

        $('#showarchive').change(function(){
            if($(this).is(':checked')){
                $('.archived-announcement').fadeIn();
            }else{
                $('.archived-announcement').fadeOut();
            }
        });

        $('.acrhive-btn').click(function(){
            var id = $(this).data('id');
            $.ajax({
                url: '?archive='+id,
                dataType: 'JSON',
                success: function(response){
                    if(response.error == 0){
                        $('#announcement-'+response.data).fadeOut();
                    }else{
                        swal('',response.data,'error');
                    }
                },
                error: function(){
                    swal('','Unable to archive announcement','error');
                }
            });
        });
    });
</script>