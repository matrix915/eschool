<?php

class mth_reimbursementsubmission extends core_model
{
    protected $date_created;
    protected $submission_id;
    protected $reimbursement_id;

    public static function get($submission_id) {
        return core_db::runGetObject('SELECT * FROM mth_reimbursement_submission 
            WHERE submission_id=' .
            (int) $submission_id, 'mth_reimbursementsubmission');
    }

    public static function create($reimbursement_id) {
        core_db::runQuery('INSERT INTO mth_reimbursement_submission (reimbursement_id) VALUES (' . $reimbursement_id . ')');
        return self::get(core_db::getInsertID());
    }

    public static function getSubmissionsByReimbursementId($reimbursement_id) {
        return core_db::runGetObjects('SELECT * FROM mth_reimbursement_submission
            WHERE reimbursement_id=' .
            (int) $reimbursement_id, 'mth_reimbursementsubmission');
    }

    public function reimbursementId($submission_id) {
        return !$submission_id ? null : self::reimbursement_id;
    }

    public function id() {
        return (int) $this->submission_id;
    }
}