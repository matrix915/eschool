<?php

/**
 * Created by PhpStorm.
 * User: abe
 * Date: 5/17/17
 * Time: 3:31 PM
 */

namespace mth\student;

class SchoolOfEnrollment
{
    const
    Unassigned = 0,
    GPA = 1,
    ALA = 2,
    eSchool = 3,
    Tooele = 4,
    Demo = 5,
    Nebo = 6,
    ICSD = 7,
    Nyssa = 8;

    protected static $active = [
        self::Unassigned,
        self::Demo,
        self::GPA,
        // self::eSchool,
        self::Tooele,
        self::Nebo,
        self::ICSD,
        self::Nyssa,
    ],

    $visible = [
        self::GPA,
        // self::eSchool,
        self::Tooele,
        self::Nebo,
        self::ICSD,
        self::Nyssa,
    ],

    $short_names = [
        self::Unassigned => 'Unassigned',
        self::Demo => 'Demo',
        self::GPA => 'GPA',
        self::ALA => 'ALA',
        self::eSchool => 'eSchool',
        self::Tooele => 'Tooele',
        self::Nebo => 'Nebo',
        self::ICSD => 'ICSD/SEA',
        self::Nyssa => 'Nyssa',
    ],

    $long_names = [
        self::Unassigned => 'Unassigned',
        self::Demo => 'Demo',
        self::GPA => 'Gateway Preparatory Academy',
        self::ALA => 'American Leadership Academy',
        self::eSchool => 'Provo eSchool',
        self::Tooele => 'Digital Education Center - Tooele County School District',
        self::Nebo => 'Advanced Learning Center - Nebo School District',
        self::ICSD => 'Southwest Education Academy - Iron County School District',
        self::Nyssa => 'Nyssa School District 26 ',
    ],

    $addresses = [
        self::Unassigned => '',
        self::Demo => '',
        self::GPA => '201 East Thoroughbred Way
Enoch, UT 84721',
        self::ALA => '898 West 1100 South
Spanish Fork, UT 84660',
        self::eSchool => '280 West 940 North
            Provo, Utah 84604',
        self::Tooele => '211 S Tooele Blvd
Tooele, UT 84074',
        self::Nebo => '161 E 400 N
Salem, Utah 84660',
        self::ICSD => '',
        self::Nyssa => '',
    ],

    $phones = [
        self::Unassigned => '',
        self::Demo => '',
        self::GPA => '',
        self::ALA => '',
        self::eSchool => '',
        self::Tooele => 'Phone: (435) 833-8710
Fax: (435) 833-8788',
        self::Nebo => 'Phone: +1 801-489-2833',
        self::ICSD => '',
        self::Nyssa => '',
    ];

    protected $school_of_enrollment_id;

    protected function __construct($school_of_enrollment_id)
    {
        $this->school_of_enrollment_id = $school_of_enrollment_id;
    }

    /**
     * @param $school_of_enrollment_id
     * @return bool|SchoolOfEnrollment
     */
    public static function get($school_of_enrollment_id)
    {
        if (self::isValid($school_of_enrollment_id)) {
            return new self($school_of_enrollment_id);
        }
        return false;
    }

    /**
     * @param $school_of_enrollment_id
     * @return bool
     */
    public static function isValid($school_of_enrollment_id)
    {
        return isset(self::$short_names[$school_of_enrollment_id]);
    }

    /**
     * @return SchoolOfEnrollment[]
     */
    public static function getActive()
    {
        $schools = [];
        foreach (self::$active as $school_of_enrollment_id) {
            $schools[$school_of_enrollment_id] = new self($school_of_enrollment_id);
        }
        return $schools;
    }

    /**
     * @return SchoolOfEnrollment[]
     */
    public function getAll()
    {
        $schools = [];
        foreach (self::$short_names as $school_of_enrollment_id => $short_name) {
            $schools[$school_of_enrollment_id] = new self($school_of_enrollment_id);
        }
        return $schools;
    }

    /**
     * @return SchoolOfEnrollment[]
     */
    public static function getAllSOE()
    {
        $schools = [];
        foreach (self::$short_names as $school_of_enrollment_id => $short_name) {
            $schools[$school_of_enrollment_id] = new self($school_of_enrollment_id);
        }
        return $schools;
    }

    /**
     * @return SchoolOfEnrollment[]
     */
    public static function getVisible()
    {
        $schools = [];
        foreach (self::$visible as $school_of_enrollment_id) {
            $schools[$school_of_enrollment_id] = new self($school_of_enrollment_id);
        }
        return $schools;
    }

    public function getId()
    {
        return $this->school_of_enrollment_id;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return isset(self::$active[$this->school_of_enrollment_id]);
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return isset(self::$visible[$this->school_of_enrollment_id]);
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return self::$short_names[$this->school_of_enrollment_id];
    }

    /**
     * @return string
     */
    public function getLongName()
    {
        return self::$long_names[$this->school_of_enrollment_id];
    }

    /**
     * @param bool $html
     * @return string
     */
    public function getAddresses($html = true)
    {
        $address = self::$addresses[$this->school_of_enrollment_id];
        if (!$html) {
            return $address;
        }
        return nl2br($address);
    }

    /**
     * @param bool $html
     * @return string
     */
    public function getPhones($html = true)
    {
        $address = self::$phones[$this->school_of_enrollment_id];
        if (!$html) {
            return $address;
        }
        return nl2br($address);
    }

    public function __toString()
    {
        return $this->getShortName();
    }
}