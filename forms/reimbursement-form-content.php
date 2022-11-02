<?php
($parent = mth_parent::getByUser()) || core_secure::loadLogin();
($year = mth_schoolYear::getCurrent()) || die('No year defined');

mth_views_reimbursements::handleFormAjax();

if (($submittedReimbusement = mth_views_reimbursements::handleFormSubmission()) !== NULL) {
    if($submittedReimbusement->isSaved()){
        core_notify::addMessage('Reimbursement has been saved as a DRAFT. Be sure to return later to finish it.');
        core_loader::reloadParent();
    }elseif ($submittedReimbusement->isSubmitted()) {
        core_notify::addMessage('Thank you for submitting a Request for Reimbursement.');
        core_loader::reloadParent();
    }  else {
        core_notify::addError('Unable to submit the form. Please make sure the form is complete and try again.');
        core_loader::redirect('?reimbursement=' . $submittedReimbusement->id());
    }
}

$reimbursement = req_get::txt('reimbursement') === 'NEW'
    ? new mth_reimbursement()
    : mth_reimbursement::get(req_get::int('reimbursement'));

$viewonly = req_get::txt('mode') == 1;

($reimbursement) || core_loader::reloadParent('Reimbursement request not found');

core_loader::addCssRef('iosstyle', core_config::getThemeURI() . '/assets/css/ios.css');
core_loader::isPopUp();
core_loader::printHeader();

cms_page::setPageContent('If a receipt includes items for multiple students and/or classes, each item is highlighted or labeled with the specific student\'s name and Period (<a href="/forms/Reimbursement_Receipt_Example.pdf" target="_blank">see sample</a>).
<ul><li><i>Remember that the same highlighted/labeled receipt can be uploaded to all applicable Requests for Reimbursement.</i></li>
</ul>', 'Reimbursement Confirm 1', cms_content::TYPE_HTML);
cms_page::setPageContent('All items unrelated to any Request for Reimbursement (ie personal items) have been crossed off or marked as n/a', 'Reimbursement Confirm 2', cms_content::TYPE_HTML);
cms_page::setPageContent('All receipts are dated within the approved window.', 'Reimbursement Confirm 3', cms_content::TYPE_HTML);
cms_page::setPageContent('All receipts are provided by the vendor and include the date, description of item(s) purchased, price paid, payment type, and vendor contact information.', 'Reimbursement Confirm 4', cms_content::TYPE_HTML);
cms_page::setPageContent('If the cost of an item is shared with multiple students and/or Periods (or Technology Allowance), the allocation is clearly indicated on the receipt (<a href="https://drive.google.com/file/d/1tHbUtYNPRN7_rU1dpE4t0iDTcNTU4XK2/view?usp=sharing" target="_blank">see sample</a>).', 'Reimbursement Confirm 5', cms_content::TYPE_HTML);
cms_page::setPageContent('I understand that if any of this information is missing or unclear, this Request for Reimbursement will be sent back to update.', 'Reimbursement Confirm 6', cms_content::TYPE_HTML);
?>

<button type="button" class="iframe-close btn btn-secondary btn-round" onclick="parent.global_popup_iframe_close('mth_reimbursement-popup-form')">
    Close
</button>
<h3>Request for Reimbursement Form</h3>
<?php if (!mth_reimbursement::open() && !$reimbursement->id() && empty($_SESSION['/forms/reimbursement?test=1'])): ?>
    <div class="alert bg-info"><b>The reimbursement form will be available <?= mth_schoolYear::getCurrent()->reimburse_open('F j') ?></b></div>
<?php else: ?>
    <?php mth_views_reimbursements::printReimbursement($reimbursement,$viewonly) ?>
<?php endif; ?>
<script>
    $('.iframe-close').click(function () {
        parent.location.hash = '';
    });
</script>
<?php
core_loader::printFooter();