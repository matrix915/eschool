<?php
if (req_get::bool('reimbursement_type')) {
  $placeholder = req_post::int('placeholder');
  $status = req_post::int('status');
  $result = false;
  if ($type = mth_reimbursementtype::getByPlaceHolder($placeholder)) {
    $type->set('is_enable', $status);
    $result = $type->save();
  }
  echo $result ? 1 : 0;
  exit;
}

if (req_get::bool('set_advance')) {
  $name = req_post::txt('name');
  $value = req_post::txt('value');
  $type = req_post::txt('type');
  $category = req_post::txt('category');
  $result = false;
  if(core_setting::set($name,$value,$type,$category)){
    $result = true;
  }
  echo $result ? 1 : 0;
  exit;
}

cms_page::setPageTitle('Advance Settings');
core_loader::printHeader('admin');
?>
<div class="nav-tabs-horizontal nav-tabs-inverse">
  <?php
  $current_header = 'advance';
  include core_config::getSitePath() . "/_/admin/settings/header.php";
  ?>
  <div class="tab-content p-20 higlight-links">
    <div class="row">
      <div class="col-md-6">
        <h4>Reimbursement Types</h4>
        <div>Enable/Disable Reimbursement Type</div>
        <?php while ($reimbursement_type = mth_reimbursementtype::each()) : ?>
          <div class="checkbox-custom checkbox-primary">
            <input class="reimbursement_type" type="checkbox" name="settings[reimbursementtype][]" <?= $reimbursement_type->isEnabled() ? 'CHECKED' : '' ?> value="<?= $reimbursement_type->getPlaceHolder() ?>">
            <label><?= $reimbursement_type->getLabel() ?></label>
          </div>
        <?php endwhile; ?>
      </div>
      <div class="col-md-6">
        <?php foreach (core_setting::getCategorySettings('advance') as $setting) : ?>
          <?= $setting->getTypeHmtl() ?>
        <?php endforeach ?>
      </div>
    </div>
  </div>
</div>
<?php
core_loader::printFooter('admin');
?>
<script>
  $(function() {
    $('.reimbursement_type').change(function() {
      var ischeck = $(this).is(':checked') ? 1 : 0;
      var val = $(this).val();

      $.ajax({
        url: '?reimbursement_type=1',
        type: 'POST',
        data: {
          placeholder: val,
          status: ischeck
        },
        success: function(response) {
          if (response == 1) {
            toastr.success('Saved');
          } else {
            toastr.error('Unable to save changes.');
          }
        },
        error: function() {
          toastr.error('Unable to save changes.');
        }
      });
    });

    $('.advance_settings').change(function() {
      var ischeck = $(this).is(':checked') ? 1 : 0;
      var name = $(this).attr('name');
      var data = $(this).data();
      $.ajax({
        url: '?set_advance=1',
        type: 'POST',
        data: {
          name: name,
          value: ischeck,
          type: data.type,
          category: data.category
        },
        success: function(response) {
          if (response == 1) {
            toastr.success('Saved');
          } else {
            toastr.error('Unable to save changes.');
          }
        },
        error: function() {
          toastr.error('Unable to save changes.');
        }
      });
    });
  });
</script>