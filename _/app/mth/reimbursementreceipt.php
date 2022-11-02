<?php

class mth_reimbursementreceipt extends core_model
{
    protected $file_id;
    protected $submission_id;
    protected $reimbursement_id;

    public static function get($reimbursement_id, $file_id) {
        return core_db::runGetObject('SELECT * FROM mth_reimbursement_reciept 
            WHERE reimbursement_id=' .
            (int) $reimbursement_id . ' AND
            file_id=' . (int) $file_id, 'mth_reimbursementreceipt');
    }

    public function submissionId() {
        return $this->submission_id;
    }

    public function fileId() {
        return (int) $this->file_id;
    }

    public static function create($reimbursement_id, $file_id) {
        $result = core_db::runQuery('REPLACE INTO mth_reimbursement_reciept (reimbursement_id, file_id) 
            VALUES (' . $reimbursement_id .
            ',' . $file_id . ')');
        return $result;
    }

    public function save($reimbursement_id) {
        $reimbursementId = $this->reimbursement_id ? $this->reimbursement_id : $reimbursement_id;
        core_db::runQuery('REPLACE INTO mth_reimbursement_reciept (reimbursement_id, file_id'. ( $this->submission_id ? ', submission_id' : '') . ')
            VALUES (' . $reimbursementId
            . ',' . $this->file_id
            . ( $this->submission_id ? ',' . $this->submission_id : '' )
            . ')');
    }

    public function SetSubmissionIdIfNull($submission_id) {
        if(!$submission_id){
            return false;
        }
        if(!isset($this->submission_id) || is_null($this->submission_id)){
            $this->submission_id = $submission_id;
        }
    }

    public static function getReceiptsByReimbursementId($reimbursement_id) {
        return core_db::runGetObjects('SELECT * FROM mth_reimbursement_reciept as mrr
        LEFT JOIN mth_reimbursement_submission as mrs 
        ON mrs.submission_id=mrr.submission_id 
        WHERE mrr.reimbursement_id=' .
        (int) $reimbursement_id, 'mth_reimbursementreceipt');
    }

    public static function getReceiptsBySubmissionIds($reimbursement){
        if(!$reimbursement->id()) {
            return [];
        }
        $submissionReceipts = [];
        $submissionIds = $reimbursement->sorted_submission_ids($reimbursement->id());
        if(!empty($submissionIds)){
            foreach ($submissionIds as $submissionId) {
                if(!isset($submissionReceipts[$submissionId])) { $submissionReceipts[$submissionId] = [];}
            }
        }

        $receipts = self::getReceiptsByReimbursementId($reimbursement->id());
        foreach($receipts as $receipt) {
            if(!$receipt->submissionId()){
                $submissionReceipts['new'][] = $receipt;
            } else {
                if(!isset($submissionReceipts[$receipt->submissionId()])) { $submissionReceipts[$receipt->submissionId()] = []; }
                $submissionReceipts[$receipt->submissionId()][] = $receipt;
            }
        }
        foreach ($submissionReceipts as $key => $submissionReceiptArray) {
            if(empty($submissionReceiptArray)) { unset($submissionReceipts[$key]); }
        }
        return $submissionReceipts;
    }
}