<?php

if (req_get::bool('set_settings')) {
  $name = req_post::txt('name');
  $value = req_post::txt('value');
  $type = req_post::txt('type');
  $category = req_post::txt('category');
  $result = false;
  if (core_setting::set($name, $value, $type, $category)) {
    $result = true;
  }
  echo $result ? 1 : 0;
  exit;
}

cms_page::setPageTitle('Re-enrollment Settings');
core_loader::printHeader('admin');

$settings = core_setting::get('unlock_packet', 'packet_settings');

?>
<div class="nav-tabs-horizontal nav-tabs-inverse">
  <?php
  $current_header = 're-enroll';
  include core_config::getSitePath() . "/_/admin/settings/header.php";
  ?>
  <div class="tab-content p-20 higlight-links">
    <div class="row">
      <div class="col-md-12">
        <h4>Re-enroll Settings</h4>
        <div class="checkbox-custom checkbox-primary">
          <input id="reenroll_settings" class="advance_settings" data-category="packet_settings" data-type="Bool" type="checkbox" name="unlock_packet" <?= $settings->getValue() ? 'CHECKED' : '' ?>>
          <label>Unlock Enrollment Packet</label>
        </div>
        <div class="col-md-6 packet-settings">
          <?php foreach (core_setting::getCategorySettings('packet_settings') as $setting) :
            if ($setting->getName() == 'unlock_packet') {
              continue;
            }
            ?>
            <?= $setting->getTypeHmtl(); ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
core_loader::printFooter('admin');
?>
<script>
  function isUnlocked() {
    if (!$('#reenroll_settings').is(':checked')) {
      $('.packet-settings').hide();
    } else {
      $('.packet-settings').show();
    }
    return true;
  }

  $(function() {
    $('#reenroll_settings').change(function(){
      if (!$('#reenroll_settings').is(':checked')) {
        $('.packet-settings .advance_settings').prop('checked',false).change();
      }
    });

    $('.advance_settings').change(function() {
      isUnlocked();
      var ischeck = $(this).is(':checked') ? 1 : 0;
      var name = $(this).attr('name');
      var data = $(this).data();
      $.ajax({
        url: '?set_settings=1',
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

    isUnlocked();

    $('input[name ="parent_id"]').attr('disabled', true);
  });
</script>