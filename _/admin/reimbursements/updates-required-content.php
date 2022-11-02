<?php

($reimbursement = mth_reimbursement::get(req_get::int('reimbursement')))
|| core_loader::reloadParent('Reimbursement Request Not Found');

if (req_get::bool('form')) {
    core_loader::formSubmitable(req_get::txt('form')) || die();

    $reimbursement->set_status(mth_reimbursement::STATUS_UPDATE);
    if (req_post::bool('action_homeroom')) {
        $reimbursement->set_at_least_80(false);
    }
    if (req_post::bool('action_amount')) {
        //removing for MTH request.  May need again in the future.
        //$reimbursement->set_invalid_amount();
    }
    if (req_post::bool('action_date') || req_post::bool('action_receipt')) {
        //removing for MTH request.  May need again in the future.
        //$reimbursement->set_require_new_receipt();
    }

    $email = new core_emailservice();
    $ccs = array_unique($reimbursement->getCC());

    if ($reimbursement->save() && $email->send(
        [$reimbursement->student_parent()->getEmail()],
        req_post::txt('subject'),
        req_post::html('content'),
        null,
        $ccs
    )) {
        echo '<script>parent.global_popup_iframe_close("mth_reimbursement-popup-form");</script>';
        exit();
    } else {
        core_notify::addError('An error has occurred, please try again.');
        core_loader::redirect('?reimbursement=' . $reimbursement->id());
    }
}

$siteURL = (core_secure::usingSSL() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
//$link = $siteURL . '/forms/reimbursement#show' . $reimbursement->id();
$link = MUSTANG_URI;
$HelpfulVideoLinkUT = "https://www.mytechhigh.com/how-to-resubmit-a-request-for-reimbursement ";

$reimbursementMethodLabel = $reimbursement->is_direct_order() ? 'Direct Order' : 'Reimbursement';
$emailContent = '<p>Hi ' . $reimbursement->student_parent()->getPreferredFirstName() . ',</p>
<p>We are unable to process the following Request for ' . ($reimbursement->is_direct_order() ? 'a Direct Order' : 'Reimbursement') . ':</p>
<p>
  <b>Submitted:</b> ' . $reimbursement->date_submitted('m/d/Y') . '<br>
  <b>Amount:</b> $' . $reimbursement->amount(true) . '<br>
  <b>Student:</b> ' . $reimbursement->student() . '<br>
  ' . ($reimbursement->schedule_period()
    ? '<b>Class: </b>' . $reimbursement->schedule_period_description() : '') . '
</p>
<p><b>Action Required:</b></p>
<!--HOMEROOM--><!--/HOMEROOM-->
<!--DEVICE--><!--/DEVICE-->
<!--AMOUNT--><!--/AMOUNT-->
<!--AMOUNT2--><!--/AMOUNT2-->
<!--DATE--><!--/DATE-->
<!--RECEIPT--><!--/RECEIPT-->
<!--RECEIPT2--><!--/RECEIPT2-->
<!--TUITION--><!--/TUITION-->
<!--P7CUSTOM--><!--/P7CUSTOM-->
<!--P7TPP--><!--/P7TPP-->
<!--P7DIRECT--><!--/P7DIRECT-->
<!--SUMMERIN--><!--/SUMMERIN-->
<!--LEGO--><!--/LEGO-->
<!--APRILNOTE--><!--/APRILNOTE-->
<!--MAYNOTE--><!--/MAYNOTE-->
<!--NETFEES--><!--/NETFEES-->
<!--TPC--><!--/TPC-->
<!--MAXREIMBURSE--><!--/MAXREIMBURSE-->
<!--NONSECULAR--><!--/NONSECULAR-->
<!--MIDYEARCUSTOM--><!--/MIDYEARCUSTOM-->
<!--MIDYEARTP--><!--/MIDYEARTP-->
<!--PERIODSIX--><!--/PERIODSIX-->
<!--MAYNOTESEVEN--><!--/MAYNOTESEVEN-->
<!--KINDERGARTENTA--><!--/KINDERGARTENTA-->
<!--INVOICE--><!--/INVOICE-->
<!--ARTSUPPLIES--><!--/ARTSUPPLIES-->
<!--COMBINEDPERIODS--><!--/COMBINEDPERIODS-->
<!--INVALIDWISHLISTAMAZON--><!--/INVALIDWISHLISTAMAZON-->
<!--INVALIDWISHLISTRAINBOWRESOURCE--><!--/INVALIDWISHLISTRAINBOWRESOURCE-->
<!--ARTSUPPLIES_DO--><!--/ARTSUPPLIES_DO-->
<!--LEGO_DO--><!--/LEGO_DO-->
<!--KINDERGARTEN_TA_DO--><!--/KINDERGARTEN_TA_DO-->
<!--CUSTOM_BUILT_DO--><!--/CUSTOM_BUILT_DO-->
<!--3RDPARTYPROVIDERDO--><!--/3RDPARTYPROVIDERDO-->
<!--MTH_DIRECT_DO--><!--/MTH_DIRECT_DO-->
<!--BACKORDERUNAVAILABLE--><!--/BACKORDERUNAVAILABLE-->
<!--DIRECTORDERAR--><!--/DIRECTORDERAR-->
<!--PTINFORMATION--><!--/PTINFORMATION-->
<!--OTHER--><!--/OTHER-->
<p>Please go to <a href="' . $link . '">' . $link . '</a> to make the necessary updates.</p>
<p>Helpful video:  <a href="' . $HelpfulVideoLinkUT . '">How to Resubmit a Request for Reimbursement.</a></p>
<p>My Tech High</p>';

//core_loader::includeCKEditor();

core_loader::isPopUp();
core_loader::printHeader();
?>

<form id="mth_reimbursement_updates_required_form" method="post" action="?form=mth_reimbursement_updates_required_form-<?=uniqid()?>&reimbursement=<?=$reimbursement->id()?>">
  <div class="row">
    <div class="col-md-6">
      <fieldset>
        <legend>To</legend>
        <?=$reimbursement->student_parent()?> &lt;<?=$reimbursement->student_parent()->getEmail()?>&gt;
      </fieldset>
      <fieldset class="form-group">
        <legend>Subject</legend>
        <input type="text" name="subject" class="form-control" value="Action Required for <?=$reimbursementMethodLabel?>">
      </fieldset>
      <fieldset>
        <legend>Actions Required</legend>
        <div class="checkbox-custom checkbox-primary">
          <input type="checkbox" name="action_homeroom" id="action_homeroom" value="1">
          <label for="action_homeroom">
            Homeroom Grade
          </label>
        </div>
        <div class="checkbox-custom checkbox-primary">
          <input type="checkbox" name="action_amount" id="action_amount" value="1">
          <label for="action_amount">
            Exceeded Amount
          </label>
        </div>
          <?php if (!$reimbursement->is_direct_order()): ?>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_amount2" id="action_amount2" value="1">
                  <label for="action_amount2">
                      Amount Requested
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_receipt" id="action_receipt" value="1">
                  <label for="action_receipt">
                      Detailed Receipt
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_date" id="action_date" value="1">
                  <label for="action_date">
                      Receipt Date
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_receipt2" id="action_receipt2" value="1">
                  <label for="action_receipt2">
                      Receipt Information
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_tuition" id="action_tuition" value="1">
                  <label for="action_tuition">
                      3rd Party Tuition
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_p7custom" id="action_p7custom" value="1">
                  <label for="action_p7custom">
                      Custom-built Period 7
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_p7tpp" id="action_p7tpp" value="1">
                  <label for="action_p7tpp">
                      3rd Party Provider Period 7
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_p7direct" id="action_p7direct" value="1">
                  <label for="action_p7direct">
                      My Tech High Direct Period 7
                  </label>
                  <!-- </div><div class="checkbox-custom checkbox-primary"><label for="action_product" class="checkbox-block-label">
                                <input type="checkbox" name="action_product" id="action_product" value="1">
                                Missing Serial Number
                            </label> -->
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="summerin" id="summerin" value="1">
                  <label for="summerin">
                      Summer Instruction
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="art_supplies" id="art_supplies" value="1">
                  <label for="art_supplies">
                      Art Supplies
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="lego" id="lego" value="1">
                  <label for="lego">
                      LEGO
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="aprilnote" id="aprilnote" value="1">
                  <label for="aprilnote">
                      April Note
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="maynote" id="maynote" value="1">
                  <label for="maynote">
                      May Note 3 Days
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="netfees" id="netfees" value="1">
                  <label for="netfees">
                      Internet Fees
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="tpc" id="tpc" value="1">
                  <label for="tpc">
                      3rd Party Consecutive
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="maxreimburse" id="maxreimburse" value="1">
                  <label for="maxreimburse">
                      Max Reimbursed
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="midyearcustom" id="midyearcustom" value="1">
                  <label for="midyearcustom">
                      Mid-year: Custom
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="midyeartp" id="midyeartp" value="1">
                  <label for="midyeartp">
                      Mid-year: 3rd Party
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="periodsix" id="periodsix" value="1">
                  <label for="periodsix">
                      Period 6
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="maynoteseven" id="maynoteseven" value="1">
                  <label for="maynote">
                      May Note 7 Days
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="kindergarten_ta" id="kindergarten_ta" value="1">
                  <label for="kindergarten_ta">
                      Kindergarten TA
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="action_invoice" id="action_invoice" value="1">
                  <label for="action_invoice">
                      Invoice
                  </label>
              </div>

          <?php else: ?>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="invalid_wishlist_amazon" id="invalid_wishlist_amazon" value="1">
                  <label for="invalid_wishlist_amazon">
                      Invalid Wishlist Amazon
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="invalid_wishlist_rainbow_resource" id="invalid_wishlist_rainbow_resource" value="1">
                  <label for="invalid_wishlist_rainbow_resource">
                    Invalid Wishlist - Rainbow Resource
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="art_supplies_DO" id="art_supplies_DO" value="1">
                  <label for="art_supplies_DO">
                      Art Supplies
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="lego_DO" id="lego_DO" value="1">
                  <label for="lego_DO">
                      LEGO
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="kindergarten_ta_DO" id="kindergarten_ta_DO" value="1">
                  <label for="kindergarten_ta_DO">
                      Kindergarten TA
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="custom_built_period7_DO" id="custom_built_period7_DO" value="1">
                  <label for="custom_built_period7_DO">
                      Custom-built Period 7
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="3rd_party_provider_DO" id="3rd_party_provider_DO" value="1">
                  <label for="3rd_party_provider_DO">
                    3rd Party Provider Period 7
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="mth_direct_DO" id="mth_direct_DO" value="1">
                  <label for="mth_direct_DO">
                    My Tech High Direct Period 7
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="backorder_unavailable" id="backorder_unavailable" value="1">
                  <label for="backorder_unavailable">
                      Backorder/Unavailable
                  </label>
              </div>
              <div class="checkbox-custom checkbox-primary">
                  <input type="checkbox" name="direct_order_ar" id="direct_order_ar" value="1">
                  <label for="direct_order_ar">
                      Direct Order Amount Requested
                  </label>
              </div>

          <?php endif;?>
          <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="ptInformation" id="ptInformation" value="1">
              <label for="ptInformation">
                Per the information
              </label>
          </div>
          <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="nonsecular" id="nonsecular" value="1">
              <label for="nonsecular">
                  Non-secular
              </label>
          </div>
          <div class="checkbox-custom checkbox-primary">
              <input type="checkbox" name="new_device" id="new_device" value="1">
              <label for="new_device">
                  New Device
              </label>
          </div>
        <div class="checkbox-custom checkbox-primary">
          <input type="checkbox" name="combined_periods" id="combined_periods" value="1">
          <label for="combined_periods">
            Combined Periods
          </label>
        </div>
        <div class="checkbox-custom checkbox-primary">
          <input type="checkbox" name="action_other" id="action_other" value="1">
          <label for="action_other">
            Other
          </label>
        </div>
      </fieldset>
    </div>
    <div class="col-md-6">
      <textarea name="content" id="emailContent"><?=$emailContent?></textarea>
      <br>
      <p>
        <button type="submit" class="btn btn-round btn-primary">Send</button>
        <button type="button" onclick="location.href = 'edit?reimbursement=<?=$reimbursement->id()?>'" class="btn btn-round btn-secondary">Cancel</button>

      </p>
    </div>
  </div>


</form>
<script src="//cdn.ckeditor.com/4.10.0/basic/ckeditor.js"></script>
<script>
  CKEDITOR.config.removePlugins = "about,image,forms,youtube,iframe,print,stylescombo,table,tabletools,undo,specialchar,removeformat,pastefromword,pastetext,smiley,font,clipboard,selectall,format,blockquote,resize,elementspath,find,maximize,showblocks,sourcearea,scayt,about,wsc,justify,bidi,horizontalrule";
  CKEDITOR.config.removeButtons = "Subscript,Superscript,Anchor";
  CKEDITOR.config.disableNativeSpellChecker = false;
  CKEDITOR.config.height = '450px';

  var emailContent = CKEDITOR.replace('emailContent');

  var CBs = [{
      wasChecked: false,
      txt: 'Our records indicate that <?=$reimbursement->student()->getPreferredFirstName()?>\'s Homeroom is missing one or more Learning Logs and/or \
the grade is less than 80%.  Please have <?=$reimbursement->student()->getPreferredFirstName()?> submit all missing assignments and ensure \
the grade is over 80% so you can resubmit this <?=!$reimbursement->is_direct_order() ? 'Request for Reimbursement Form.' : 'Direct Order Form.'?>',
      hidden: 'HOMEROOM',
      cb: $('#action_homeroom')
    },
    {
      wasChecked: false,
      txt: 'The Amount Requested exceeds the amount available. Please review the information in Parent Link, adjust the <?=!$reimbursement->is_direct_order() ? 'Amount Requested' : 'wishlist'?>, and resubmit the form.',
      hidden: 'AMOUNT',
      cb: $('#action_amount')
    },
    {
      wasChecked: false,
      txt: 'The Amount Requested is greater than the amount shown on the receipts.',
      hidden: 'AMOUNT2',
      cb: $('#action_amount2')
    },
    {
      wasChecked: false,
      txt: 'One or more receipts does not include a date.  Please submit receipts dated after April 1, <?=$reimbursement->school_year()->getStartYear()?>.',
      hidden: 'DATE',
      cb: $('#action_date')
    },
    {
      wasChecked: false,
      txt: 'Per the information in Parent Link, if a receipt includes items for multiple children and/or classes, \
each item MUST be highlighted or labeled with the specific student\'s name and Period for which you are requesting reimbursement. \
If an item on a receipt is not for any student\'s class, simply write "n/a" next to it. \
See example <a href="<?=$siteURL?>/forms/Reimbursement_Receipt_Example.pdf">here</a>.',
      hidden: 'RECEIPT',
      cb: $('#action_receipt')
    },
    {
      wasChecked: false,
      txt: 'Per the information in Parent Link, all receipts must be provided by the vendor and include the date, \
description of item(s) purchased, price paid, payment type, and vendor contact information.',
      hidden: 'RECEIPT2',
      cb: $('#action_receipt2')
    },
    {
      wasChecked: false,
      txt: 'Per the information in Parent Link, under the 3rd Party Provider option we can provide reimbursement for tuition only, not fees or supplies. \
Please resubmit this for tuition only.',
      hidden: 'TUITION',
      cb: $('#action_tuition')
    },
    {
      wasChecked: false,
      txt: 'Per the information in Parent Link, a Custom-built class in optional Period 7 uses $225 of the student\'s Technology Allowance. \
Since <?=$reimbursement->student()->getPreferredFirstName()?> has a Custom-built class in optional Period 7, \
the available Technology Allowance is $275.',
      hidden: 'P7CUSTOM',
      cb: $('#action_p7custom')
    },
    {
      wasChecked: false,
      txt: 'Per the information in Parent Link, a 3rd Party Provider class in optional Period 7 uses $300 of the student’s Technology Allowance. \
Since <?=$reimbursement->student()->getPreferredFirstName()?> has a 3rd Party Provider class in optional Period 7, \
the available Technology Allowance is $200.',
      hidden: 'P7TPP',
      cb: $('#action_p7tpp')
    },
    {
      wasChecked: false,
      txt: 'Per the information in Parent Link, a My Tech High Direct class in optional Period 7 uses $300 of the student’s Technology Allowance. ' +
        ' Since <?=$reimbursement->student()->getPreferredFirstName()?> has a My Tech High Direct class in optional Period 7, ' +
        'the available Technology Allowance is $200.',
      hidden: 'P7DIRECT',
      cb: $('#action_p7direct')
    },
    {
      wasChecked: false,
      txt: 'Please only include the new computer, laptop, Chromebook, or iPad/tablet on this Request for Reimbursement form. Submit a separate form for additional Technology Allowance items.',
      hidden: 'DEVICE',
      cb: $('#new_device')
    },
    {
      wasChecked: false,
      txt: 'Per the information in Parent Link, we can provide reimbursement for instruction from August - May.  This includes instruction prior to that.',
      hidden: 'SUMMERIN',
      cb: $('#summerin')
    },
    {
      wasChecked: false,
      txt: 'Per the information in the Tech Catalog and in Parent Link, the LEGO class deducts $150 from the student\'s Technology Allowance.  Please adjust the Amount Requested.',
      hidden: 'LEGO',
      cb: $('#lego')
    },
    {
      wasChecked: false,
      txt: 'Receipts dated after March 31 can be considered for next school year.',
      hidden: 'APRILNOTE',
      cb: $('#aprilnote')
    },
    {
      wasChecked: false,
      txt: "<strong><span style='color:#ff0000'>NOTE: This MUST be re-submitted by <?=date("m/d/y", strtotime('+3 days'))?> to be considered for this school year's reimbursement.</span></strong>",
      hidden: 'MAYNOTE',
      cb: $('#maynote')
    },
    {
      wasChecked: false,
      txt: "We can provide reimbursement for internet fees that have been paid (note that this may be different than the billed amount). Please provide proof of payment.",
      hidden: 'NETFEES',
      cb: $('#netfees')
    },
    {
      wasChecked: false,
      txt: "Under the 3rd Party Provider option we can provide reimbursement for consecutive, not concurrent, classes. Please adjust the Amount Requested to include just consecutive classes OR change this to Custom-built so you can include concurrent activities.",
      hidden: 'TPC',
      cb: $('#tpc')
    },
    {
      wasChecked: false,
      txt: "$  of the maximum $  has already been reimbursed. Please adjust the Amount Requested.",
      hidden: 'MAXREIMBURSE',
      cb: $('#maxreimburse')
    },
    {
      wasChecked: false,
      txt: "Per the information in Parent Link, you are free to use, but we are not able <?=!$reimbursement->is_direct_order() ? 'to provide reimbursement for,' : 'to purchase'?> non-secular curriculum. Please just include secular materials here.",
      hidden: 'NONSECULAR',
      cb: $('#nonsecular')
    },
    {
      wasChecked: false,
      txt: "The available reimbursement for Custom-built Periods for students who join our program mid-year is $112.50. Please adjust the Amount Requested.",
      hidden: 'MIDYEARCUSTOM',
      cb: $('#midyearcustom')
    },
    {
      wasChecked: false,
      txt: "The available reimbursement for 3rd Party Provider Periods for students who join our program mid-year is $150. Please adjust the Amount Requested.",
      hidden: 'MIDYEARTP',
      cb: $('#midyeartp')
    },
    {
      wasChecked: false,
      txt: "Please provide verification of the dates of this semester-based class.",
      hidden: 'PERIODSIX',
      cb: $('#periodsix')
    },
    {
      wasChecked: false,
      txt: '',
      hidden: 'OTHER',
      cb: $('#action_other')
    },
    {
      wasChecked: false,
      txt: "<strong><span style='color:#ff0000'>NOTE: This MUST be re-submitted by <?=date("m/d/y", strtotime('+7 days'))?> to be considered for this school year's reimbursement.</span></strong>",
      hidden: 'MAYNOTESEVEN',
      cb: $('#maynoteseven')
    },
    {
      wasChecked: false,
      txt: "Per the information in Parent Link, the Technology Allowance for students in Kindergarten is $250.",
      hidden: 'KINDERGARTENTA',
      cb: $('#kindergarten_ta')
    },
    {
      wasChecked: false,
      txt: "An unpaid invoice is attached; please provide a receipt or a proof of payment.",
      hidden: 'INVOICE',
      cb: $('#action_invoice')
    },
    {
      wasChecked: false,
      txt: "We can provide reimbursement for up to $25 in art supplies per Custom-built Period. Additional arts and crafts items can be reimbursed with Technology Allowance funds.",
      hidden: 'ARTSUPPLIES',
      cb: $('#art_supplies')
    },
    {
      wasChecked: false,
      txt: "Custom-built Periods 2, 3, 4, and/or 6 are the only ones that can be combined. Please <?=!$reimbursement->is_direct_order() ? 'submit a separate reimbursement request' : 'create a separate wishlist'?> for Period 5 and only include items for Custom-built Periods 2, 3, 4, and/or 6 here.",
      hidden: 'COMBINEDPERIODS',
      cb: $('#combined_periods')
    },    {
      wasChecked: false,
      txt: "The wishlist link is not valid. Please follow the instructions  <a href='https://www.youtube.com/watch?v=JP1Lb1ZLA0c' target='_blank'>here</a> to create an individualized wishlist for each student. ",
      hidden: 'INVALIDWISHLISTAMAZON',
      cb: $('#invalid_wishlist_amazon')
    },
    {
      wasChecked: false,
      txt: "At least one item on your wishlist is no longer available, currently out of stock, or on backorder.  Please edit your wishlist.",
      hidden: 'BACKORDERUNAVAILABLE',
      cb: $('#backorder_unavailable')
    },
    {
      wasChecked: false,
      txt: "Including applicable shipping and taxes, the cost of the items on the wishlist is more than the amount available. Please remove some items from the list and resubmit the request.",
      hidden: 'DIRECTORDERAR',
      cb: $('#direct_order_ar')
    },
    {
      wasChecked: false,
      txt: "The wishlist link is not valid. Please follow the instructions  <a href='https://youtu.be/0ifXU7yI8M4' target='_blank'>here</a>  to create an individualized wishlist for each student.",
      hidden: 'INVALIDWISHLISTRAINBOWRESOURCE',
      cb: $('#invalid_wishlist_rainbow_resource')
    },
    {
      wasChecked: false,
      txt: "We can purchase up to $25 in art supplies per Custom-built Period. Additional arts and crafts items can be purchased with Technology Allowance funds.",
      hidden: 'ARTSUPPLIES_DO',
      cb: $('#art_supplies_DO')
    },
    {
      wasChecked: false,
      txt: "Per the information in the Tech Catalog and in Parent Link, the LEGO class deducts $150 from the student's Technology Allowance. Please adjust the Amount Requested.",
      hidden: 'LEGO_DO',
      cb: $('#lego_DO')
    },
    {
      wasChecked: false,
      txt: "Per the information in Parent Link, the Technology Allowance for students in Kindergarten is $250.",
      hidden: 'KINDERGARTEN_TA_DO',
      cb: $('#kindergarten_ta_DO')
    },
    {
      wasChecked: false,
      txt: "Per the information in Parent Link, a Custom-built class in optional Period 7 uses $225 of the student’s Technology Allowance. Since  <?=$reimbursement->student()->getPreferredFirstName()?> has a Custom-built class in optional Period 7, the available Technology Allowance is $275.",
      hidden: 'CUSTOM_BUILT_DO',
      cb: $('#custom_built_period7_DO')
    },
    {
      wasChecked: false,
      txt: "Per the information in Parent Link, a 3rd Party Provider class in optional Period 7 uses $300 of the student’s Technology Allowance. Since  <?=$reimbursement->student()->getPreferredFirstName()?> has a 3rd Party Provider class in optional Period 7, the available Technology Allowance is $200.",
      hidden: '3RDPARTYPROVIDERDO',
      cb: $('#3rd_party_provider_DO')
    },
    {
      wasChecked: false,
      txt: "Per the information in Parent Link, a My Tech High Direct class in optional Period 7 uses $300 of the student’s Technology Allowance. Since  <?=$reimbursement->student()->getPreferredFirstName()?> has a My Tech High Direct class in optional Period 7, the available Technology Allowance is $200.",
      hidden: 'MTH_DIRECT_DO',
      cb: $('#mth_direct_DO')
    },
    {
      wasChecked: false,
      txt: "Per the information found in the Reimbursement section of Parent Link,",
      hidden: 'PTINFORMATION',
      cb: $('#ptInformation')
    },
  ];
  setInterval(function() {
    for (var cb in CBs) {
      if (CBs[cb].cb.prop('checked') && !CBs[cb].wasChecked) {
        CBs[cb].wasChecked = true;
        emailContent.setData(
          emailContent.getData().replace(
            '<!--' + CBs[cb].hidden + '--><!--/' + CBs[cb].hidden + '-->',
            '<!--' + CBs[cb].hidden + '--><ul><li>' + CBs[cb].txt + '</li></ul><!--/' + CBs[cb].hidden + '-->'
          )
        );
      } else if (CBs[cb].wasChecked && !CBs[cb].cb.prop('checked')) {
        CBs[cb].wasChecked = false;
        emailContent.setData(
          emailContent.getData().replace(
            (new RegExp('<!--' + CBs[cb].hidden + '-->(\\n|\\r|.)*<!--/' + CBs[cb].hidden + '-->')),
            '<!--' + CBs[cb].hidden + '--><!--/' + CBs[cb].hidden + '-->'
          )
        );
      }
    }
  }, 500);
</script>
<?php
core_loader::printFooter();
