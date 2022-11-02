<?php

/**
 * email sent
 *
 * @author cres <crestelitoc@codev.com>
 */
class mth_emaillogs extends core_model
{
    ################################################ DATABASE FIELDS ############################################

    protected $email_batch_id;
    protected $student_id;
    protected $parent_id;
    protected $school_year_id;
    protected $status;
    protected $type;
    protected $title;
    protected $email_address;
    protected $error_message;
    protected $date_created;

    ################################################ STATIC MEMBERS ############################################

    protected static $cache;

    const STATUS_PENDING = 1;
    const STATUS_SENT = 2;
    const STATUS_FAILED = 3;

    const STATUS_LABEL_PENDING = 'Pending';
    const STATUS_LABEL_SENT = 'SENT';
    const STATUS_LABEL_FAILED = 'FAILED';

    protected static $availableStatuses = array(
        self::STATUS_SENT => self::STATUS_LABEL_SENT,
        self::STATUS_PENDING => self::STATUS_LABEL_PENDING,
        self::STATUS_FAILED => self::STATUS_LABEL_FAILED,
    );

    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @return bool|mth_emaillogs[]
     */
    public function all()
    {
        $lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
        return core_db::runGetObjects(
            'SELECT * FROM mth_email_logs
                     WHERE date_created > "' . date('Y-m-d' , $lastmonth) . '"',
            'mth_emaillogs'
        );
    }

    public function allStudentIds()
    {
        $lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
        return core_db::runGetValues(
            'SELECT student_id FROM mth_email_logs
                     WHERE date_created > "' . date('Y-m-d' , $lastmonth) . '"');
    }

    public function allParentIds()
    {
        $lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
        return core_db::runGetValues(
            'SELECT parent_id FROM mth_email_logs
                     WHERE date_created > "' . date('Y-m-d' , $lastmonth) . '"');
    }

    /**
     *
     * @return $availableStatus
     */
    public function getStatusLabel()
    {
        return self::$availableStatuses[$this->status];
    }

    /**
     *
     * @return $date_created
     */
    public function getDateCreated()
    {
        return Date('Y-m-d', strtotime($this->date_created));
    }

    /**
     *
     * @return mth_parent
     */
    public function getParent()
    {
        return mth_parent::getByParentID($this->parent_id);
    }

    /**
     *
     * @return mth_student
     */
    public function getStudent()
    {
        return mth_student::getByStudentID($this->student_id);
    }

    /**
     *
     * @return $email_batch_id
     */
    public function emailBatchId($id = NULL)
    {
        if(!is_null($id))
        {
            $this->set('email_batch_id', $id);
        }
        return $this->email_batch_id;
    }

    /**
     *
     * @return $student_id
     */
    public function studentId($id = NULL)
    {
        if(!is_null($id))
        {
            $this->set('student_id', (int) $id);
        }
        return $this->student_id;
    }

    /**
     *
     * @return $parent_id
     */
    public function parentId($id = NULL)
    {
        if(!is_null($id))
        {
            $this->set('parent_id', (int) $id);
        }
        return $this->parent_id;
    }

    /**
     *
     * @return $school_year_id
     */
    public function schoolYearId($id = NULL)
    {
        if(!is_null($id))
        {
            $this->set('school_year_id', (int) $id);
        }
        return $this->school_year_id;
    }

    /**
     *
     * @return $status
     */
    public function status($status = NULL)
    {
        if(!is_null($status))
        {
            $this->set('status', (int) $status);
        }
        return $this->status;
    }

    /**
     *
     * @return $type
     */
    public function type($type = NULL)
    {
        if(!is_null($type))
        {
            $this->set('type', $type);
        }
        return $this->type;
    }

    /**
     *
     * @return $type
     */
    public function title($title = NULL)
    {
        if(!is_null($title))
        {
            $this->set('title', $title);
        }
        return $this->title;
    }

    /**
     *
     * @return $email_address
     */
    public function emailAddress($email_address = NULL)
    {
        if(!is_null($email_address))
        {
            $this->set('email_address', $email_address);
        }
        return $this->email_address;
    }

    /**
     *
     * @return $error_message
     */
    public function errorMessage($error_message = NULL)
    {
        if(!is_null($error_message))
        {
            $this->set('error_message', $error_message);
        }
        return $this->error_message;
    }

    /**
     *
     * @return mth_emaillogs
     */
    public function save()
    {
        $whereClause = 'email_batch_id = "' . $this->emailBatchId() . '"';
        return $this->runUpdateQuery('mth_email_logs', $whereClause);
    }

    /**
     *
     * @return mth_emaillogs
     */
    public function create()
    {
        return core_db::runQuery('INSERT INTO mth_email_logs (email_batch_id, student_id, parent_id, school_year_id, status, type, email_address, error_message) 
        VALUES ("' . $this->email_batch_id . '", ' . $this->student_id . ',' . $this->parent_id . ',' . $this->school_year_id . ',' . $this->status . ',"' . $this->type . '","' . $this->email_address . '","' . $this->error_message . '")');
    }
}
