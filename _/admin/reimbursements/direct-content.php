<?php

use mth\yoda\courses;



if(req_get::bool('reimbursement')){
     $reimbursement = mth_reimbursement::get(req_get::int('reimbursement'));
}else{
     $reimbursement = new mth_reimbursement();
}

if (req_get::bool('delete')) {
     if ($reimbursement->delete()) {
          core_notify::addMessage('Reimbursement Deleted');
          echo "<script>
          top.global_popup_iframe_close('directdeduction');
          var familymodal = top.document.getElementById('mth_reimbursement-show').getElementsByTagName('iframe');
               if(familymodal != undefined){
                    familymodal[0].contentWindow.location.reload();
               }
          </script>";
          exit();
     } else {
         core_notify::addError('Unable to delete reimbursement request');
         core_loader::redirect('?reimbursement=' . req_get::int('reimbursement'));
     }
 }

$year = null;
$parent = null;

if(req_get::bool('sy')){
     $year = mth_schoolYear::getByID(req_get::int('sy'));
}else{
     $year = $reimbursement->school_year();
}

if(req_get::bool('parent')){
     $parent = mth_parent::getByParentID(req_get::int('parent'));
}else{
     $parent = $reimbursement->student_parent();
}

$year || die('Year not specified');
$parent || die('Parent not specified');





if(req_get::bool('form')){
    
     if(!($student = mth_student::getByStudentID(req_post::int('student')))){
          exit('Invalid Student');
     }
     $reimbursement->set_type(mth_reimbursement::TYPE_DIRECT);
     $reimbursement->set_status(mth_reimbursement::STATUS_PAID);
     $reimbursement->set_amount(req_post::float('amount'));
     $reimbursement->set_description(req_post::multi_txt('description'));
     $reimbursement->set_student_year($student, $year);
     $reimbursement->set_at_least_80(true);
     $reimbursement->set_tag_type(req_post::int('tag_type'));
     if($reimbursement->save()){
          core_notify::addMessage('Reimbursement Direct Deduction Saved');
     }else{
          core_notify::addError('Reimbursement Direct Deduction save error');
     }

     echo "<script>
          top.global_popup_iframe_close('directdeduction');
          var familymodal = top.document.getElementById('mth_reimbursement-show').getElementsByTagName('iframe');
          if(familymodal != undefined){
               familymodal[0].contentWindow.location.reload();
          }
     </script>";
     exit();
}

core_loader::isPopUp();
core_loader::printHeader();
?>

<div class="reimbursement-form">
     <h3>Direct Deduction</h3>
     <form action="?form=<?= uniqid('mth_reimbursement_form-') ?><?= $reimbursement->id()?("&reimbursement=".$reimbursement->id()):("&sy=".$year->getID()."&parent=".$parent->getID()) ?>" 
     method="post" id="mth_reimbursement_form">
       
          <div class="form-group">
               <label for="student">Select a Student</label>
               <select id="student" name="student" required class="form-control">
                    <option></option>
                    <?php foreach ($parent->getStudents() as $student) : /* @var $student mth_student */ ?>
                         <?php
                              if (!($schedule = mth_schedule::get($student, $year)) || !$student->isActive($year)) {
                                   continue;
                              }
                              $student_homeroom = courses::getStudentHomeroom($student->getID(), $year);
                              $grade =  $student_homeroom ? $student_homeroom->getGrade() : null;
                              $zeros = $student_homeroom ? $student_homeroom->getZeros() : 0;
                              ?>
                         <option data-grade="<?= $grade ?>" data-zeros="<?= $zeros ?>" value="<?= $student->getID() ?>" <?= $reimbursement->student() && $reimbursement->student()->getID() == $student->getID() ? 'selected' : '' ?>>
                              <?= $student ?> - <?= $grade === null ? 'N/A' : ($grade . '%') ?>, <?= $zeros ?> missing
                         </option>
                    <?php endforeach; ?>
               </select>
          </div>
          <div class="form-group">
               <label>Amount</label>
               <div class="input-group reimburse_link_content">
                    <span class="input-group-addon">$</span>
                    <input type="text" name="amount" class="form-control" id="amount" style="max-width: 100px" value="<?= $reimbursement->amount(true) ?>" required>
               </div>
          </div>
          <div class="form-group">
               <label>Tag Reimbursement</label>
               <select name="tag_type" class="form-control">
                    <option></option>
                    <?php foreach(mth_reimbursement::type_labels() as $key=>$tag):?>
                         <option value="<?=$key?>" <?=$reimbursement->type_tag(true)== $key?'SELECTED':''?>><?=$tag?></option>
                    <?php endforeach;?>
               </select>
          </div>
          <div class="form-group mb-10">
               <label for="description">Additional Information</label>
               <textarea class="form-control" name="description" rows="9" id="description"><?= $reimbursement->description(false) ?></textarea>
          </div>
          <button class="btn btn-round btn-primary">Save</button> 
          <?php if($reimbursement->id()):?>
               <button type="button" class="btn btn-round btn-danger" onclick="mth_reimbursement_delete()">Delete</button>
          <?php endif;?>
          <button type="button" class="btn btn-round btn-secondary" onclick="closeForm()">Close</button>
     </form>

</div>
<?php
core_loader::includejQueryValidate();
core_loader::printFooter();
?>
<script>
     function closeForm() {
          top.global_popup_iframe_close('directdeduction');
     }

     function mth_reimbursement_delete() {
            global_confirm('<p>Are you sure you want to delete this Reimbursement Request. This action cannot be undone.',
            function () {
                location.href = '?reimbursement=<?=$reimbursement->id()?>&delete=1';
            },
            'Yes');
        }  
     $(function() {
          $('#mth_reimbursement_form')
               .submit(function() {
                    if ($(this).valid()) {
                         global_waiting();
                    }
                    return true;
               })
               .validate({
                    rules: {
                         student: 'required',
                         amount:'required'
                    }
               });
     });
</script>