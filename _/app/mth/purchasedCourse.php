<?php

/**
 * Description of purchasedCourse
 *
 * @author abe
 */
class mth_purchasedCourse extends core_model
{
    protected $purchasedCourse_id;
    protected $mth_parent_id;
    protected $wooCommerce_customer_id;
    protected $wooCommerce_order_id;            ///
    protected $wooCommerce_order_line_item_id;   // These field cobminations should be unique
    protected $quantity_item;                   ///
    protected $date_purchased;
    protected $canvas_course_id;
    protected $mth_school_year_id;
    protected $mth_student_id;
    protected $student_canvas_enrollment_id;
    protected $parent_canvas_enrollment_id;
    protected $date_registered;

    protected static $cache = array();

    public function id()
    {
        return (int)$this->purchasedCourse_id;
    }

    public function order_id()
    {
        return (int)$this->wooCommerce_order_id;
    }

    public function line_item_id()
    {
        return (int)$this->wooCommerce_order_line_item_id;
    }

    public function quantity_item()
    {
        return (int)$this->quantity_item;
    }

    public function date_purchased($format = NULL)
    {
        if (!$this->date_purchased && ($order = mth_wooCommerce_order::get($this->order_id()))) {
            $this->set('date_purchased', $order->created_at('Y-m-d H:i:s'));
            $this->save();
        }
        return self::getDate($this->date_purchased, $format);
    }

    public function course_title()
    {
        if (($mth_course = $this->mth_course())) {
            return $mth_course->title();
        }
    }

    /**
     *
     * @return mth_canvas_course
     */
    public function canvas_course()
    {
        return mth_canvas_course::getByID($this->canvas_course_id);
    }

    /**
     *
     * @return mth_course
     */
    public function mth_course()
    {
        if (($canvas_course = $this->canvas_course())) {
            return $canvas_course->mth_course();
        }
    }

    public function mth_student_id()
    {
        return (int)$this->mth_student_id;
    }

    /**
     *
     * @return mth_student
     */
    public function mth_student()
    {
        return mth_student::getByStudentID($this->mth_student_id());
    }

    public function mth_parent_id()
    {
        return (int)$this->mth_parent_id;
    }

    /**
     *
     * @return mth_parent
     */
    public function mth_parent()
    {
        return mth_parent::getByParentID($this->mth_parent_id());
    }

    public function student_canvas_enrollment_id()
    {
        return (int)$this->student_canvas_enrollment_id;
    }

    public function parent_canvas_enrollment_id()
    {
        return (int)$this->parent_canvas_enrollment_id;
    }

    public function date_registered($format)
    {
        return self::getDate($this->date_registered, $format);
    }

    public function set_student_id($mth_student_id)
    {
        if (($student = mth_student::getByStudentID($mth_student_id))) {
            $this->set_student($student);
        }
    }

    public function set_student(mth_student $student)
    {
        $this->set('mth_student_id', $student->getID());
    }

    public function createCanvasEnrollments()
    {
        return mth_canvas_user::createCanvasAccounts($this->mth_student(), true)
        && $this->createStudentCanvasEnrollment()
        && $this->createParentCanvasEnrollment();
    }

    public function createStudentCanvasEnrollment()
    {
        if (!($canvas_user = mth_canvas_user::get($this->mth_student()))
            || !($canvas_course = $this->canvas_course())
            || !($enrollment = mth_canvas_enrollment::get($canvas_user, $canvas_course))
            || !($enrollment->create(true))
        ) {
            return false;
        }
        $this->set('date_registered', date('Y-m-d H:i:s'));
        $this->set('student_canvas_enrollment_id', $enrollment->id());
        return $enrollment->id();
    }

    public function createParentCanvasEnrollment()
    {
        if (!($canvas_user = mth_canvas_user::get($this->mth_parent()))
            || !($canvas_course = $this->canvas_course())
            || !($enrollment = mth_canvas_enrollment::get($canvas_user, $canvas_course))
            || !($enrollment->create(true))
        ) {
            return false;
        }
        $this->set('parent_canvas_enrollment_id', $enrollment->id());
        return $enrollment->id();
    }

    public function save()
    {
        return parent::runUpdateQuery('mth_purchasedCourse', 'purchasedCourse_id=' . $this->id());
    }

    public function __destruct()
    {
        $this->save();
    }

    /**
     *
     * @param mth_parent $parent
     * @param mth_schoolYear $school_year
     * @param boolean $reset
     * @return mth_purchasedCourse
     */
    public static function eachOfParent(mth_parent $parent, mth_schoolYear $school_year = NULL, $reset = false)
    {
        $result = &self::cache(__CLASS__, 'eachOfParent-' . $parent->getID() . '-' . $school_year);
        if (!isset($result)) {
            if (is_null($school_year) && !($school_year = mth_schoolYear::getCurrent())) {
                return false;
            }
            $result = core_db::runQuery(sprintf('SELECT * FROM mth_purchasedcourse 
                                            WHERE mth_parent_id=%d 
                                              AND mth_school_year_id=%d',
                $parent->getID(),
                $school_year->getID()));
        }
        if (!$reset && $result !== false && ($purchasedCourse = $result->fetch_object('mth_purchasedCourse'))) {
            return $purchasedCourse;
        }
        $result->data_seek(0);
        return NULL;
    }

    /**
     *
     * @param mth_schoolYear $school_year
     * @param bool $reset
     * @return mth_purchasedCourse
     */
    public static function each(mth_schoolYear $school_year = NULL, $reset = false)
    {
        $result = &self::$cache['each'][$school_year ? $school_year->getID() : NULL];
        if (!$result) {
            if (is_null($school_year) && !($school_year = mth_schoolYear::getCurrent())) {
                return false;
            }
            $result = core_db::runQuery('SELECT * FROM mth_purchasedcourse 
                                    WHERE mth_school_year_id=' . $school_year->getID() . ' 
                                    ORDER BY purchasedCourse_id DESC');
        }
        if (!$reset && ($purchasedCourse = $result->fetch_object('mth_purchasedCourse'))) {
            return $purchasedCourse;
        }
        $result->data_seek(0);
        return NULL;
    }

    public static function populateOrderPurchases(mth_wooCommerce_order $order)
    {
        $order->eachLineItem(true);
        while ($line_item = $order->eachLineItem()) {
            for ($quanity_item = 1; $quanity_item <= $line_item->quantity(); $quanity_item++) {
                self::create($line_item, $quanity_item);
            }
        }
    }

    public static function create(mth_wooCommerce_order_lineItem $line_item, $quanity_item)
    {
        if (($purchasedCourse = self::getByLineItem($line_item, $quanity_item))) {
            return $purchasedCourse;
        }

        if ($quanity_item > $line_item->quantity()) {
            return false;
        }

        if (!($canvas_course = $line_item->canvas_course())) {
            return false;
        }

        if (!($parent = $line_item->order()->mth_parent())) {
            return false;
        }

        unset($_SESSION[core_config::getCoreSessionVar()][__CLASS__]['hasPurchasedCourse'][$parent->getID()]);

        $success = core_db::runQuery(
            sprintf('INSERT INTO mth_purchasedCourse 
                          (mth_parent_id, wooCommerce_customer_id, wooCommerce_order_id, 
                            wooCommerce_order_line_item_id, quantity_item, canvas_course_id, 
                            mth_school_year_id, date_purchased)
                          VALUES
                          (%d, %d, %d,
                            %d, %d, %d,
                            %d, "%s")',
                $parent->getID(), $line_item->order()->customer()->id(), $line_item->order()->id(),
                $line_item->id(), $quanity_item, $canvas_course->id(),
                $canvas_course->school_year_id(), $line_item->order()->created_at('Y-m-d H:i:s')));

        if ($success) {
            return self::get(core_db::getInsertID());
        }

        return false;
    }

    public static function getByLineItem(mth_wooCommerce_order_lineItem $line_item, $quanity_item)
    {
        $purchasedCourse = &self::cache(__CLASS__, 'getByLineItem-' . $line_item->id() . '-' . (int)$quanity_item);
        if (!isset($purchasedCourse)) {
            $purchasedCourse = core_db::runGetObject(sprintf('SELECT * FROM mth_purchasedcourse 
                                                        WHERE wooCommerce_order_id=%d 
                                                          AND wooCommerce_order_line_item_id=%d 
                                                          AND quantity_item=%d',
                $line_item->order()->id(),
                $line_item->id(),
                $quanity_item),
                'mth_purchasedCourse');
            if ($purchasedCourse) {
                self::cache(__CLASS__, 'get-' . $purchasedCourse->id(), $purchasedCourse);
            }
        }
        return $purchasedCourse;
    }

    /**
     *
     * @param int $purchasedCourse_id
     * @return mth_purchasedCourse
     */
    public static function get($purchasedCourse_id)
    {
        $purchasedCourse = &self::cache(__CLASS__, 'get-' . (int)$purchasedCourse_id);
        if (!isset($purchasedCourse)) {
            $purchasedCourse = core_db::runGetObject('SELECT * FROM mth_purchasedcourse 
                                                WHERE purchasedCourse_id=' . (int)$purchasedCourse_id,
                'mth_purchasedCourse');
            if ($purchasedCourse) {
                /* @var $purchasedCourse mth_purchasedCourse */
                self::cache(__CLASS__, 'getByLineItem-' . $purchasedCourse->line_item_id() . '-' . $purchasedCourse->quantity_item(), $purchasedCourse);
            }
        }
        if (!core_user::isUserAdmin() && mth_parent::getByUser() != $purchasedCourse->mth_parent()) {
            return NULL;
        }
        return $purchasedCourse;
    }


    protected function __construct()
    {
    }

    public static function getParentIDfromCustomerID($wooCommerce_customer_id)
    {
        if (!$wooCommerce_customer_id) {
            return NULL;
        }
        return core_db::runGetValue('SELECT mth_parent_id FROM mth_purchasedcourse 
                                  WHERE wooCommerce_customer_id=' . (int)$wooCommerce_customer_id);
    }

    public static function hasPurchasedCourse(mth_parent $parent)
    {
        $hasPurchased = &$_SESSION[core_config::getCoreSessionVar()][__CLASS__]['hasPurchasedCourse'][$parent->getID()];
        if (!isset($hasPurchased)) {
            if (self::eachOfParent($parent)) {
                self::eachOfParent($parent, NULL, true);
                $hasPurchased = true;
            } else {
                $hasPurchased = false;
            }
        }
        return $hasPurchased;
    }

    public static function removeStudent(mth_student $student)
    {
        return core_db::runQuery('UPDATE mth_purchasedCourse SET mth_student_id=NULL 
                              WHERE student_canvas_enrollment_id IS NULL 
                                AND mth_student_id=' . $student->getID());
    }
}
