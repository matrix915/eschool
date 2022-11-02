<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 10/6/16
 * Time: 2:10 PM
 */

/** @var $year mth_schoolYear */
($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'Product Serial Numbers - ' . $year;

$reportArr = array(array(
    'School Year',
    'School of Enrollment',
    'Student Last',
    'Student First',
    'Student Status',
    'Item Name',
    'Serial Number',
    'Amount Paid',
    'Amount Reimbursed',
    'Date Reimbursed',
    'Parent Last',
    'Parent First',
    'Street',
    'Street Line 2',
    'City',
    'State',
    'Zip'
));

$next_year = $year->getNextYear();
$asOfDate = date('n/j/Y',strtotime('tomorrow',$year->getDateEnd()));

while ($reimbursement = mth_reimbursement::each(null, null, $year, array(mth_reimbursement::STATUS_APPROVED, mth_reimbursement::STATUS_PAID))) {
    if (!$reimbursement->product_sn()
        || !($student = $reimbursement->student())
        || !($parent = $student->getParent())
        || !($address = $parent->getAddress())
    ) {
        continue;
    }

    $withdrawn = false;
    $status = $student->getStatusLabel($year);
    if($status==mth_student::STATUS_LABEL_WITHDRAW){
        $status .= ' as of '.$student->getStatusDate($year,'n/j/Y');
        $withdrawn = true;
    }

    $next_status = $student->getStatusLabel($next_year);
    if(!$withdrawn && $next_status===mth_student::STATUS_LABEL_WITHDRAW){
        $status = $next_status.' as of '.$asOfDate;
    }

    $reportArr[] = array(
        $year->getName(),
        $student->getSchoolOfEnrollment(false,$year),
        $student->getLastName(),
        $student->getFirstName(),
        $status,
        $reimbursement->product_name(),
        $reimbursement->product_sn(),
        '$' . $reimbursement->product_amount(true),
        '$' . $reimbursement->amount(true),
        $reimbursement->date_paid('n/j/Y'),
        $parent->getLastName(),
        $parent->getFirstName(),
        $address->getStreet(),
        $address->getStreet2(),
        $address->getCity(),
        $address->getState(),
        $address->getZip()
    );
}


include ROOT . core_path::getPath('../report.php');