<?php
use mth\aws\ses;
$ses = new ses();
if(req_get::bool('form-control')){
     $valid = true;
     if(!req_get::bool('param')){
          core_notify::addError('Param is Missing');
          $valid = false;
     }

     if(!req_get::bool('tempname')){
          core_notify::addError('TempName is required');
          $valid = false;
     }

     if(!req_get::bool('value')){
          core_notify::addError('Value is required');
          $valid = false;
     }

     if($valid){
          if($ses->updateCustomVerification(req_get::txt('tempname'),[
               req_get::txt('param') =>  req_get::txt('value')
          ])){
               core_notify::addMessage('Settings Save');
          }else{
               core_notify::addError('Saving Error');
          }
     }
     
     
     echo '<script>top.location.reload();</script>';
     exit;
}

if(req_get::bool('change')){
     core_loader::isPopUp();
     core_loader::printHeader();
?>   
     <form id="form">
          <input type="hidden" name="form-control" value="sdfevser">
          <div class="form-group">
               <label>TemplateName</label>
               <input type="text" class="form-control" name="tempname" value="<?=req_get::txt('change')?>">
          </div>
          <div class="form-group">
               <label>Param</label>
               <select name="param" class="form-control">
                    <option>SuccessRedirectionURL</option>
                    <option>FailureRedirectionURL</option>
                    <option>FromEmailAddress</option>
               </select>
          </div>
          <div class="form-group">
          <label>Value</label>
               <input type="text" class="form-control" name="value">
          </div>
     
          <button  type="Save" class="btn btn-primary">
               Save
          </button>
          <button onclick="parent.global_popup_iframe_close('emailtmpmodal')" type="button" class="btn btn-secondary">
               Close
          </button>
     </form>
<?php     
     core_loader::printFooter();
     exit;
}

cms_page::setPageTitle('Custom Email');
cms_page::setPageContent('This page show SES custom Emails');
core_loader::includeBootstrapDataTables('css');
core_loader::printHeader('admin');
$template = $ses->getCustomVerifications();
?>
<table class="table" id="temp_tbl">
     <thead>
          <th>TemplateName</th>
          <th>FromEmailAddress</th>
          <th>SuccessRedirectionURL</th>
          <th>FailureRedirectionURL</th>
     </thead>
     <tbody>
          <?php foreach($template['CustomVerificationEmailTemplates'] as $tem):?>
               <tr>
                    <td><a onclick="global_popup_iframe('emailtmpmodal','/_/admin/email-temp?change=<?=$tem['TemplateName']?>',true);"><?=$tem['TemplateName']?></a></td>
                    <td><?=$tem['FromEmailAddress']?></td>
                    <td><?=$tem['SuccessRedirectionURL']?></td>
                    <td><?=$tem['FailureRedirectionURL']?></td>
               </tr>
          <?php endforeach?>
     </tbody>
</table>
<?php
core_loader::includeBootstrapDataTables('js');
core_loader::printFooter('admin');
?>
<script>
     $(function(){
               
          $('#temp_tbl').DataTable({
                    stateSave: true,
                    "paging": false,
                    "aaSorting": [[0, 'asc']],
          });
     });
</script>