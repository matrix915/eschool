<?php

/**
 * packet
 *
 * @author abe
 */
class mth_packet
{
    protected $packet_id;
    protected $student_id;
    protected $status;
    protected $school_district;
    protected $special_ed;
    protected $last_school;
    protected $permission_to_request_records;
    protected $hispanic;
    protected $race;
    protected $language;
    protected $language_home;
    protected $secondary_contact_first;
    protected $secondary_contact_last;
    protected $secondary_phone;
    protected $secondary_email;
    protected $household_size;
    protected $household_income;
    protected $agrees_to_policy;
    protected $approves_enrollment;
    protected $ferpa_agreement;
    protected $signature_name;
    protected $signature_file_id;
    protected $exemption_form_date;
    protected $reenroll_files;
    protected $admin_notes;
    protected $immunization_notes;
    protected $non_diploma_seeking;
    protected $relationship_parentinfo;
    protected $relationship_secondaryContact;

    const RELATION_FATHER = 'Father';
    const RELATION_MOTHER = 'Mother';
    const RELATION_OTHER = 'Other';

    //mth_4
    protected $special_ed_desc;
    protected $last_school_type; //last_school_type and last_school_address go with last_school (name)
    protected $last_school_address;
    protected $reupload_files;

    //mth_7
    protected $deadline;
    protected $understands_special_ed;
    protected $understands_sped_scheduling;

    //mth_8
    protected $photo_permission;
    protected $dir_permission;

    //mth_20
    protected $date_submitted;
    protected $date_last_submitted;
    protected $date_accepted;
    protected $date_assigned_to_soe;

    protected $birth_place;
    protected $birth_country;
    protected $worked_in_agriculture;
    protected $military;
    protected $medical_exemption;

    //tta0004
    protected $language_home_child,
    $language_friends,
    $language_home_preferred,
    $work_move,
    $living_location,
        $lives_with;

    //mth2018-02-05-mth_packet_add_column_deleted
    protected $deleted;
    //mth2019-05-23-mth_packet-add-column_military_branch
    protected $military_branch;
    //mth2019-05-30-mth_packet-add-column-exempt_immunization
    protected $exemp_immunization;

    private $_updateQuerys = array();

    const STATUS_NOT_STARTED = 'Not Started';
    const STATUS_STARTED = 'Started';
    const STATUS_SUBMITTED = 'Submitted';
    const STATUS_ACCEPTED = 'Accepted';
    const STATUS_MISSING = 'Missing Info';
    const STATUS_RESUBMITTED = 'Resubmitted';

    const AGE_ISSUE = 'Age Issue';

    const SPECIALED_NO = 0;
    const SPECIALED_IEP = 2;
    const SPECIALED_504 = 4;
    const SPECIALED_EXIT = 5;

    protected static $AGE_GRADELEVEL = array(
        'OR-K' => 5,
        'K' => 5,
        1 => 6,
        2 => 7,
        3 => 8,
        4 => 9,
        5 => 10,
        6 => 11,
        7 => 12,
        8 => 13,
        9 => 14,
        10 => 15,
        11 => 16,
        12 => 17,
    );

    protected static $cache = array();

    public static function getAgeGradeLevel($gradelevel)
    {
        if (!isset(self::$AGE_GRADELEVEL[$gradelevel])) {
            return null;
        }
        return self::$AGE_GRADELEVEL[$gradelevel];
    }

    public static function getAvailableStatuses()
    {
        return array(
            self::STATUS_NOT_STARTED,
            self::STATUS_STARTED,
            self::STATUS_ACCEPTED,
            self::STATUS_MISSING,
            self::STATUS_SUBMITTED,
            self::STATUS_RESUBMITTED,
        );
    }

    private static $_specialEdOpts = array(
        0 => 'No',
        //1=>'No - but I\'d like to request in-depth diagnostic assessments',
        2 => 'Yes - an IEP', //Yes - and the IEP has been within the past 2 years -- YES
        //3 => 'Yes - but the IEP was not within the past 2 years',//Yes - but the IEP was not within the past 2 years -- same as 0
        4 => 'Student has a 504 Plan (not an IEP)',
        5 => 'Exit',
    );

    private static $SPECIALED = array(
        self::SPECIALED_NO => 'No',
        self::SPECIALED_IEP => 'Yes - an IEP',
        self::SPECIALED_504 => 'Yes - a 504 Plan (not an IEP)',
    );

    const RACE_NATIVE = 1;
    const RACE_ASIAN = 2;
    const RACE_BLACK = 3;
    const RACE_ISLAND = 4;
    const RACE_WHITE = 5;
    const RACE_UNDECLARED = 6;

    private static $_raceOpts = array(
        self::RACE_NATIVE => 'American Indian or Alaska Native',
        self::RACE_ASIAN => 'Asian',
        self::RACE_BLACK => 'Black or African American',
        self::RACE_ISLAND => 'Native Hawaiian or Other Pacific Islander',
        self::RACE_WHITE => 'White',
        self::RACE_UNDECLARED => 'Undeclared',
    );

    private static $_ferpaOpts = array(
        1 => 'I give my permission for the school to share immunization information with USIIS.',
        2 => 'I do not give my permission for the school to share immunization information with USIIS',
    );

    private static $_photoPermOpts = array(
        1 => 'I give permission for the school take and post pictures of my student.',
        2 => 'I do not give permission for the school to take and post pictures of my student.',
    );

    private static $_dirPermOpts = array(
        1 => 'I give permission for the school to post my contact information in a student directory.',
        2 => 'I do not give permission for the school to post my contact information in a student directory.',
    );

    private static $_incomeOpts = array(
        0 => 'Not shared',
        1 => 'Less than $1,600',
        2 => '$1,600 - $3,000',
        3 => '$3,000 - $4,000',
        4 => '$4,000 - $5,500',
        5 => '$5,500 - $6,600',
        6 => 'Above $6,600',
    );

    private static $_schoolDistricts = array(
        'Alpine',
        'Beaver',
        'Box Elder',
        'Cache',
        'Canyons',
        'Carbon',
        'Daggett',
        'Davis',
        'Duchesne',
        'Emery',
        'Garfield',
        'Grand',
        'Granite',
        'Iron',
        'Jordan',
        'Juab',
        'Kane',
        'Logan',
        'Millard',
        'Morgan',
        'Murray',
        'Nebo',
        'North Sanpete',
        'North Summit',
        'Ogden',
        'Park City',
        'Piute',
        'Provo',
        'Rich',
        'Salt Lake City',
        'San Juan',
        'Sevier',
        'South Sanpete',
        'South Summit',
        'Tintic',
        'Tooele',
        'Uintah',
        'Wasatch',
        'Washington',
        'Wayne',
        'Weber'
    );

    private static $_orSchoolDistricts = array( 
        'Adel School District', 
        'Adrian School District', 
        'Alsea School District', 
        'Amity School District', 
        'Annex School District, Ontario', 
        'Arlington School District', 
        'Arock School District', 
        'Ashland School District', 
        'Ashwood School District', 
        'Astoria School District', 
        'Athena-Weston School District', 
        'Baker School District, Baker City', 
        'Bandon School District', 
        'Banks School District', 
        'Beaverton School District', 
        'Bend-La Pine School District', 
        'Bethel School District, Eugene', 
        'Blachly School District', 
        'Black Butte School District, Camp Sherman', 
        'Brookings-Harbor School District', 
        'Burnt River School District, Unity', 
        'Butte Falls School District', 
        'Camas Valley School District', 
        'Canby School District', 
        'Cascade School District, Turner', 
        'Centennial School District, Portland', 
        'Central Curry School District, Gold Beach', 
        'Central Linn School District, Brownsville', 
        'Central Point School District (formerly Jackson County School District)', 
        'Central School District, Independence', 
        'Clatskanie School District', 
        'Colton School District', 
        'Condon School District', 
        'Coos Bay School District', 
        'Coquille School District', 
        'Corbett School District', 
        'Corvallis School District', 
        'Cove School District', 
        'Creswell School District', 
        'Crook County School District, Prineville',
        'Crow-Applegate-Lorane School District', 
        'Culver School District', 
        'Dallas School District', 
        'David Douglas School District, Portland',
        'Days Creek School District (Douglas County School District 15)',
        'Dayton School District',
        'Dayville School District',
        'Diamond School District',
        'Double O School District, Hines',
        'Drewsey School District',
        'Dufur School District',
        'Eagle Point School District',
        'Echo School District',
        'Elgin School District',
        'Elkton School District',
        'Enterprise School District',
        'Estacada School District',
        'Eugene School District',
        'Falls City School District',
        'Fern Ridge School District, Elmira',
        'Forest Grove School District',
        'Fossil School District',
        'Frenchglen School District',
        'Gaston School District',
        'Gervais School District',
        'Gladstone School District',
        'Glendale School District',
        'Glide School District',
        'Grants Pass School District',
        'Greater Albany Public School District',
        'Gresham-Barlow School District',
        'Harney County School District 3, Burns',
        'Harney County School District 4 (Crane Elementary School District), Crane',
        'Harney County Union High School District (Crane Union High School District), Crane',
        'Harper School District',
        'Harrisburg School District',
        'Helix School District',
        'Hermiston School District',
        'Hillsboro School District',
        'Hood River County School District, Hood River',
        'Huntington School District',
        'Imbler School District',
        'Ione School District',
        'Jefferson County School District, Madras',
        'Jewell School District',
        'John Day School District (Grant County School District), Canyon City',
        'Jordan Valley School District',
        'Joseph School District',
        'Junction City School District',
        'Juntura School District',
        'Klamath County School District',
        'Klamath Falls City Schools',
        'Knappa School District',
        'La Grande School District',
        'Lake County School District (Lakeview School District)',
        'Lake Oswego School District',
        'Lebanon Community Schools',
        'Lincoln County School District, Newport',
        'Long Creek School District',
        'Lowell School District',
        'Mapleton School District',
        'Marcola School District',
        'McDermitt Elementary School District (Students attend school in McDermitt, Nevada)',
        'McKenzie School District, Finn Rock',
        'McMinnville School District',
        'Medford School District',
        'Milton-Freewater Unified School District',
        'Mitchell School District',
        'Molalla River School District',
        'Monroe School District',
        'Monument School District',
        'Morrow School District, Lexington',
        'Mt. Angel School District',
        'Myrtle Point School District',
        'Neah-Kah-Nie School District, Rockaway Beach',
        'Nestucca Valley School District, Hebo',
        'Newberg School District',
        'North Bend School District',
        'North Clackamas School District, Milwaukie',
        'North Douglas School District, Drain',
        'North Lake School District, Silver Lake',
        'North Marion School District, Aurora',
        'North Powder School District',
        'North Santiam School District, Stayton',
        'North Wasco County School District (formerly The Dalles and Chenowith school districts)',
        'Nyssa School District',
        'Oakland School District',
        'Oakridge School District',
        'Ontario School District',
        'Oregon City School District',
        'Oregon Trail School District, Sandy',
        'Paisley School District',
        'Parkrose School District, Portland',
        'Pendleton School District',
        'Perrydale School District',
        'Philomath School District',
        'Phoenix-Talent School District',
        'Pilot Rock School District',
        'Pine Creek School District, Hines',
        'Pine Eagle School District, Halfway',
        'Pinehurst School District, Ashland',
        'Pleasant Hill School District',
        'Plush School District',
        'Port Orford-Langlois School District',
        'Portland Public Schools',
        'Powers School District',
        'Prairie City School District',
        'Prospect School District',
        'Rainier School District',
        'Redmond School District',
        'Reedsport School District',
        'Reynolds School District, Fairview',
        'Riddle School District',
        'Riverdale School District, Portland',
        'Rogue River School District',
        'Roseburg School District (Douglas County School District 4)',
        'Salem-Keizer School District',
        'Santiam Canyon School District, Mill City',
        'Scappoose School District',
        'Scio School District',
        'Seaside School District',
        'Sheridan School District',
        'Sherman County School District, Wasco',
        'Sherwood School District',
        'Silver Falls School District, Silverton',
        'Sisters School District',
        'Siuslaw School District, Florence',
        'South Harney School District, Fields',
        'South Lane School District, Cottage Grove',
        'South Umpqua School District, Myrtle Creek',
        'South Wasco County School District, Maupin',
        'Spray School District',
        'Springfield School District',
        'St. Helens School District',
        'St. Paul School District',
        'Stanfield School District',
        'Suntex School District, Hines',
        'Sutherlin School District',
        'Sweet Home School District',
        'Three Rivers/Josephine County School District, Murphy',
        'Tigard-Tualatin School District',
        'Tillamook School District',
        'Troy School District',
        'Ukiah School District',
        'Umatilla School District',
        'Union School District',
        'Vale School District',
        'Vernonia School District',
        'Wallowa School District',
        'Warrenton-Hammond School District',
        'West Linn-Wilsonville School District',
        'Willamina School District',
        'Winston-Dillard School District',
        'Woodburn School District',
        'Yamhill-Carlton School District',
        'Yoncalla School District',
    );

    //Oregon School Districts
    private static $_schoolDistrictsOR = ["Adel School District", "Adrian School District", "Alsea School District", "Amity School District", "Annex School District, Ontario", "Arlington School District", "Arock School District", "Ashland School District", "Ashwood School District", "Astoria School District", "Athena-Weston School District", "Baker School District, Baker City", "Bandon School District", "Banks School District", "Beaverton School District", "Bend-La Pine School District", "Bethel School District, Eugene", "Blachly School District", "Black Butte School District, Camp Sherman", "Brookings-Harbor School District", "Burnt River School District, Unity", "Butte Falls School District", "Camas Valley School District", "Canby School District", "Cascade School District, Turner", "Centennial School District, Portland", "Central Curry School District, Gold Beach", "Central Linn School District, Brownsville", "Central Point School District (formerly Jackson County School District)", "Central School District, Independence", "Clatskanie School District", "Colton School District", "Condon School District", "Coos Bay School District", "Coquille School District", "Corbett School District", "Corvallis School District", "Cove School District", "Creswell School District", "Crook County School District, Prineville", "Crow-Applegate-Lorane School District", "Culver School District", "Dallas School District", "David Douglas School District, Portland", "Days Creek School District (Douglas County School District 15)", "Dayton School District", "Dayville School District", "Diamond School District", "Double O School District, Hines", "Drewsey School District", "Dufur School District", "Eagle Point School District", "Echo School District", "Elgin School District", "Elkton School District", "Enterprise School District", "Estacada School District", "Eugene School District", "Falls City School District", "Fern Ridge School District, Elmira", "Forest Grove School District", "Fossil School District", "Frenchglen School District", "Gaston School District", "Gervais School District", "Gladstone School District", "Glendale School District", "Glide School District", "Grants Pass School District", "Greater Albany Public School District", "Gresham-Barlow School District", "Harney County School District 3, Burns", "Harney County School District 4 (Crane Elementary School District), Crane", "Harney County Union High School District (Crane Union High School District), Crane", "Harper School District", "Harrisburg School District", "Helix School District", "Hermiston School District", "Hillsboro School District", "Hood River County School District, Hood River", "Huntington School District", "Imbler School District", "Ione School District", "Jefferson County School District, Madras", "Jewell School District", "John Day School District (Grant County School District), Canyon City", "Jordan Valley School District", "Joseph School District", "Junction City School District", "Juntura School District", "Klamath County School District", "Klamath Falls City Schools", "Knappa School District", "La Grande School District", "Lake County School District (Lakeview School District)", "Lake Oswego School District", "Lebanon Community Schools", "Lincoln County School District, Newport", "Long Creek School District", "Lowell School District", "Mapleton School District", "Marcola School District", "McDermitt Elementary School District (Students attend school in McDermitt, Nevada)", "McKenzie School District, Finn Rock", "McMinnville School District", "Medford School District", "Milton-Freewater Unified School District", "Mitchell School District", "Molalla River School District", "Monroe School District", "Monument School District", "Morrow School District, Lexington", "Mt. Angel School District", "Myrtle Point School District", "Neah-Kah-Nie School District, Rockaway Beach", "Nestucca Valley School District, Hebo", "Newberg School District", "North Bend School District", "North Clackamas School District, Milwaukie", "North Douglas School District, Drain", "North Lake School District, Silver Lake", "North Marion School District, Aurora", "North Powder School District", "North Santiam School District, Stayton", "North Wasco County School District (formerly The Dalles and Chenowith school districts)", "Nyssa School District", "Oakland School District", "Oakridge School District", "Ontario School District", "Oregon City School District", "Oregon Trail School District, Sandy", "Paisley School District", "Parkrose School District, Portland", "Pendleton School District", "Perrydale School District", "Philomath School District", "Phoenix-Talent School District", "Pilot Rock School District", "Pine Creek School District, Hines", "Pine Eagle School District, Halfway", "Pinehurst School District, Ashland", "Pleasant Hill School District", "Plush School District", "Port Orford-Langlois School District", "Portland Public Schools", "Powers School District", "Prairie City School District", "Prospect School District", "Rainier School District", "Redmond School District", "Reedsport School District", "Reynolds School District, Fairview", "Riddle School District", "Riverdale School District, Portland", "Rogue River School District", "Roseburg School District (Douglas County School District 4)", "Salem-Keizer School District", "Santiam Canyon School District, Mill City", "Scappoose School District", "Scio School District", "Seaside School District", "Sheridan School District", "Sherman County School District, Wasco", "Sherwood School District", "Silver Falls School District, Silverton", "Sisters School District", "Siuslaw School District, Florence", "South Harney School District, Fields", "South Lane School District, Cottage Grove", "South Umpqua School District, Myrtle Creek", "South Wasco County School District, Maupin", "Spray School District", "Springfield School District", "St. Helens School District", "St. Paul School District", "Stanfield School District", "Suntex School District, Hines", "Sutherlin School District", "Sweet Home School District", "Three Rivers/Josephine County School District, Murphy", "Tigard-Tualatin School District", "Tillamook School District", "Troy School District", "Ukiah School District", "Umatilla School District", "Union School District", "Vale School District", "Vernonia School District", "Wallowa School District", "Warrenton-Hammond School District", "West Linn-Wilsonville School District", "Willamina School District", "Winston-Dillard School District", "Woodburn School District", "Yamhill-Carlton School District", "Yoncalla School District"];
    //UT School Districts values Used on the dynamic School District
    private static $_schoolDistrictsUT = ['Alpine', 'Beaver', 'Box Elder', 'Cache', 'Canyons', 'Carbon', 'Daggett', 'Davis', 'Duchesne', 'Emery', 'Garfield', 'Grand', 'Granite', 'Iron', 'Jordan', 'Juab', 'Kane', 'Logan', 'Millard', 'Morgan', 'Murray', 'Nebo', 'North Sanpete', 'North Summit', 'Ogden', 'Park City', 'Piute', 'Provo', 'Rich', 'Salt Lake City', 'San Juan', 'Sevier', 'South Sanpete', 'South Summit', 'Tintic', 'Tooele', 'Uintah', 'Wasatch', 'Washington', 'Wayne', 'Weber'];

    private static $_schoolTypes = array(
        0 => 'None - Student has always been homeschooled',
        1 => 'Student was previously enrolled in the following school',
    );

    public static function spedMap($tostudent = true)
    {
        if ($tostudent) {
            return [
                self::SPECIALED_NO => mth_student::SPED_NO,
                self::SPECIALED_IEP => mth_student::SPED_IEP,
                self::SPECIALED_504 => mth_student::SPED_504,
                self::SPECIALED_EXIT => mth_student::SPED_EXIT,
            ];
        }

        return [
            mth_student::SPED_NO => self::SPECIALED_NO,
            mth_student::SPED_IEP => self::SPECIALED_IEP,
            mth_student::SPED_504 => self::SPECIALED_504,
            mth_student::SPED_EXIT => self::SPECIALED_EXIT,
        ];
    }

    public static function getAvailSpecialEd()
    {
        return self::$SPECIALED;
    }

    public static function getAvailableSecialEd()
    {
        return self::$_specialEdOpts;
    }

    public static function getAvailableRace()
    {
        return self::$_raceOpts;
    }

    public static function getAvailableFerpa()
    {
        return self::$_ferpaOpts;
    }

    public static function getAvailableIncome()
    {
        return self::$_incomeOpts;
    }

    public static function getAvailableSchoolDistricts()
    {
        return self::$_schoolDistricts;
    }

    public static function getORAvailableSchoolDistricts()
    {
        return self::$_orSchoolDistricts;
    }

    public static function getAvailableSchoolTypes()
    {
        return self::$_schoolTypes;
    }

    public static function getPhotoPermOpts()
    {
        return self::$_photoPermOpts;
    }

    public static function getDirPermOpts()
    {
        return self::$_dirPermOpts;
    }

    public static function getAvailableCountries()
    {
        $countriesArr = &self::$cache['getAvailableCountries'];
        if ($countriesArr === null) {
            $countriesArr = include ROOT . '/_/mth_includes/countriesArray.php';
        }
        return $countriesArr;
    }

    public static function countryName($countryCode)
    {
        $counties = self::getAvailableCountries();
        return $counties[$countryCode];
    }

    /**
     *
     * @param mth_student $student
     * @return mth_packet
     */
    public static function create(mth_student $student)
    {
        if (($packet = self::getStudentPacket($student))) {
            self::deleteStudentPackets($student);
            //return $packet;
        }

        if (($applicaiton = mth_application::getStudentApplication($student))
            && $applicaiton->isAccepted()
        ) {
            $deadline = strtotime(core_setting::get('packetDeadline', 'Packets') . ' days', $applicaiton->getDateAccepted());
        } else {
            $deadline = strtotime(core_setting::get('packetDeadline', 'Packets') . ' days');
        }
        core_db::runQuery('INSERT INTO mth_packet
                        (student_id, status, deadline)
                        VALUES (
                          ' . $student->getID() . ',
                          "' . self::STATUS_NOT_STARTED . '",
                          "' . date('Y-m-d', $deadline) . '")');
        return self::getByID(core_db::getInsertID());
    }

    public static function deleteStudentPackets(mth_student $student, $soft = true)
    {
        if ($soft) {
            return core_db::runQuery('UPDATE mth_packet set deleted=1 where student_id=' . $student->getID());
        }
        return core_db::runQuery('DELETE FROM mth_packet WHERE student_id=' . $student->getID());
    }

    public function restore()
    {
        return core_db::runQuery('UPDATE mth_packet set deleted=0 where packet_id=' . $this->getID());
    }

    public function resetDeadline()
    {
        if (($applicaiton = mth_application::getStudentApplication(mth_student::getByStudentID($this->student_id)))
            && $applicaiton->isAccepted()
        ) {
            $deadline = strtotime(core_setting::get('packetDeadline', 'Packets') . ' days', $applicaiton->getDateAccepted());
        } else {
            $deadline = strtotime(core_setting::get('packetDeadline', 'Packets') . ' days');
        }
        $this->deadline = date('Y-m-d', $deadline);
        $this->_updateQuerys['deadline'] = 'deadline="' . $this->deadline . '"';
    }

    /**
     * @param array $packet_ids
     * @param array $status
     * @param int $deadline
     * @return array Array of mth_packet objects
     */
    public static function get(array $packet_ids = null, array $status = null, $deadline = null)
    {
        if (!is_null($status)) {
            $status = array_intersect($status, self::getAvailableStatuses());
        }
        return core_db::runGetObjects(
            'SELECT * FROM mth_packet
                                    WHERE 1
                                      ' . (!is_null($packet_ids) ? ' AND packet_id IN (' . implode(',', array_map('intval', $packet_ids)) . ')' : '') . '
                                      ' . (!is_null($status) ? 'AND `status` IN ("' . implode('","', $status) . '")' : '') . '
                                      ' . (!is_null($deadline) ? 'AND deadline="' . date('Y-m-d', $deadline) . '"' : '') . '
                                    ORDER BY `status` DESC, deadline ASC, packet_id DESC',
            'mth_packet'
        );
    }

    protected static function exec_each($exec_id, array $packet_ids = null, $reset = false)
    {
        $result = &self::$cache['exec_each'][$exec_id];
        if (!isset($result)) {
            $result = core_db::runQuery('SELECT * FROM mth_packet
                                    WHERE 1
                                      ' . (!empty($packet_ids)
                ? ' AND packet_id IN (' . implode(',', array_map('intval', $packet_ids)) . ')' : '') . '
                                    ORDER BY `status` DESC, deadline ASC, packet_id DESC');
        }
        if (!$reset && ($packet = $result->fetch_object('mth_packet'))) {
            return $packet;
        }
        $result->data_seek(0);
        return null;
    }

    /**
     *
     * @param mth_schoolYear $year
     * @param array $status
     * @param bool $reset
     * @return mth_packet
     */
    public static function each(mth_schoolYear $year = null, array $status = null, $reset = false)
    {
        $exec_id = &self::$cache['each'][$year ? $year->getID() : 'ALL'][empty($status) ? implode(',', $status) : 'ALL'];
        if (!isset($exec_id)) {
            $exec_id = uniqid();
            if (!is_null($status)) {
                $status = array_intersect($status, self::getAvailableStatuses());
            }
            $IDs = core_db::runGetValues('SELECT p.packet_id FROM mth_packet as p
                                          JOIN mth_student as s ON s.student_id=p.student_id
                                          WHERE 1
                                          AND s.parent_id IS NOT NULL
                                  ' . ($year ? 'AND YEAR(p.date_submitted)="' . $year->getStartYear() . '"' : '') . '
                                  ' . (!is_null($status) ? 'AND p.`status` IN ("' . implode('","', $status) . '")' : ''));

            if (empty($IDs)) {
                $IDs = array(0);
            }
            self::exec_each($exec_id, $IDs, true);
        }
        return self::exec_each($exec_id, null, $reset);
    }

    /**
     *
     * @param int|null $deadline
     * @return array Array of mth_packet objects
     */
    public static function getUnSubmitted($deadline = null)
    {
        return self::get(null, array(
            self::STATUS_MISSING,
            self::STATUS_NOT_STARTED,
            self::STATUS_STARTED,
        ), $deadline);
    }

    /**
     *
     * @param int|null $deadline
     * @return array Array of mth_packet objects
     */
    public static function getUnAccepted($deadline = null)
    {
        return self::get(null, array(
            self::STATUS_MISSING,
            self::STATUS_NOT_STARTED,
            self::STATUS_STARTED,
            self::STATUS_SUBMITTED,
            self::STATUS_RESUBMITTED,
        ), $deadline);
    }

    /**
     *
     * @param int $packet_id
     * @return mth_packet|false
     */
    public static function getByID($packet_id)
    {
        return core_db::runGetObject('SELECT * FROM mth_packet WHERE packet_id=' . (int) $packet_id, 'mth_packet');
    }

    /**
     *
     * @param mth_student $student
     * @return mth_packet|false
     */
    public static function getStudentPacket(mth_student $student, $active_only = false)
    {
        $stmt = '';
        if ($active_only) {
            $stmt = ' AND deleted=0';
        }
        return core_db::runGetObject('SELECT * FROM mth_packet WHERE student_id=' . $student->getID() . $stmt . ' ORDER BY packet_id DESC', 'mth_packet');
    }

    /**
     *
     * @param mth_student $student
     * @return mth_packet|false
     */
    public static function getStudentPackets(mth_student $student, $active_only = false)
    {
        $activeClause = '';
        if ($active_only) {
            $activeClause = ' AND deleted=0';
        }
        return core_db::runGetObjects('SELECT * FROM mth_packet WHERE student_id='
            . $student->getID() . $activeClause . ' ORDER BY packet_id DESC', 'mth_packet');
    }

    /**
     *
     * @param mth_student $student
     * @return mth_packet|false
     */
    public static function getStudentsPackets(array $studentIds, $active_only = false)
    {
        if (is_array($studentIds) && empty($studentIds)) {
            return array();
        }

        $activeClause = '';
        if ($active_only) {
            $activeClause = ' AND deleted=0';
        }
        $packets = core_db::runGetObjects('SELECT * FROM mth_packet WHERE student_id IN ('
            . implode(',', $studentIds) . ')' . $activeClause . ' ORDER BY packet_id DESC', 'mth_packet');

        if (empty($packets)) {
            $packets = [];
        }
        return $packets;
    }

    public function getID()
    {
        return (int) $this->packet_id;
    }

    public function getMedicalExemption()
    {
        return (int) $this->medical_exemption;
    }

    public function getStudentID()
    {
        return (int) $this->student_id;
    }

    public function isDeleted()
    {
        return (bool) $this->deleted;
    }

    public function isExempImmunization()
    {
        return (bool) $this->exemp_immunization;
    }

    /**
     *
     * @return mth_student
     */
    public function getStudent()
    {
        return mth_student::getByStudentID($this->student_id);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMilitaryBranch()
    {
        return $this->military_branch;
    }

    public function __toString()
    {
        if ($this->isAccepted()) {
            return self::STATUS_ACCEPTED . ': ' . $this->getDateAccepted('M. j, Y');
        } elseif ($this->isResubmitted()) {
            return self::STATUS_RESUBMITTED . ': ' . $this->getDateLastSubmitted('M. j, Y');
        } elseif ($this->isSubmitted()) {
            return self::STATUS_SUBMITTED . ': ' . $this->getDateSubmitted('M. j, Y');
        }
        return $this->getStatus();
    }

    public function isRightAge($student)
    {
        if (!$this->isSubmitted()) {
            return true;
        }

        // if( $application == null){
        //     $application = mth_application::getStudentApplication($student);
        // }

        $submitted = $this->getDateSubmitted();
        $year = $this->getDateSubmitted('Y');
        $current_sy = mth_schoolYear::getCurrent();

        if (!$submitted) {
            $sy = $current_sy;
        } elseif ($packet_year = mth_schoolYear::getByStartYear($year)) {
            $sy = $packet_year;
        } else {
            $sy = $current_sy;
        }

        $packet_end_at = strtotime('9/1/' . $sy->getDateBegin('Y'));

        /** Commented out because of IN-525 */
        // if ($submitted && $submitted > $packet_end_at) {
        //   return false;
        // }

        $gradelevel = $student->getGradeLevelValue($sy->getID());
        $gradelevel_age = mth_packet::getAgeGradeLevel($gradelevel);

        $dob = $student->getDateOfBirth();
        //There are 31556926 seconds in a year.
        $age_before_end = floor(($packet_end_at - $dob) / 31556926);

        if ($gradelevel_age != $age_before_end) {
            return false;
        }

        return true;
    }

    public function setStatus($status)
    {
        if ($status == $this->status) {
            return true;
        }
        if (!in_array($status, self::getAvailableStatuses())) {
            return false;
        }
        $this->status = $status;
        $this->_updateQuerys['status'] = '`status`="' . core_db::escape($status) . '"';
        return true;
    }

    public function submit($signature_name)
    {
        if (!$this->agrees_to_policy || !$this->approves_enrollment || !$this->ferpa_agreement) {
            return false;
        }
        $this->signature_name = cms_content::sanitizeText($signature_name);
        $this->_updateQuerys['signature_name'] = 'signature_name="' . core_db::escape($signature_name) . '"';
        $this->setSubmitDateToToday();
        $student = $this->getStudent();
        if ($student->getReenrolled()) {
            $this->setLastSubmitDateToToday();
            $this->setStatus(self::STATUS_RESUBMITTED);
        } else {
            $this->setStatus(self::STATUS_SUBMITTED);
        }

        return $this->save();
    }

    public function setSubmitDateToToday()
    {
        $this->date_submitted = date('Y-m-d H:i:s');
        $this->_updateQuerys['date_submitted'] = '`date_submitted`="' . $this->date_submitted . '"';
    }

    public function setLastSubmitDateToToday()
    {
        $this->date_last_submitted = date('Y-m-d H:i:s');
        $this->_updateQuerys['date_last_submitted'] = '`date_last_submitted`="' . $this->date_last_submitted . '"';
    }

    public function setExemptionFormDate($value)
    {
        $this->exemption_form_date = core_model::getDate($value, 'Y-m-d H:i:s');
        if($this->exemption_form_date) {
            $this->_updateQuerys['exemption_form_date'] = '`exemption_form_date`="' . $this->exemption_form_date . '"';
        }
        else {
            $this->_updateQuerys['exemption_form_date'] = '`exemption_form_date`=NULL';
        }
    }

    public function setMedicalExemption($value)
    {
        $this->medical_exemption = (int) $value;
        $this->_updateQuerys['medical_exemption'] = '`medical_exemption`="' . $this->medical_exemption . '"';
    }

    public function resubmit()
    {
        if ($this->getReuploadFiles()) {
            return false;
        }
        $this->setLastSubmitDateToToday();
        $this->setStatus(self::STATUS_RESUBMITTED);
        return $this->save();
    }

    public function agreesToPolicy($set = null)
    {
        if (!is_null($set)) {
            $this->agrees_to_policy = $set ? 1 : 0;
            $this->_updateQuerys['agrees_to_policy'] = 'agrees_to_policy=' . $this->agrees_to_policy;
        }
        return (bool) $this->agrees_to_policy;
    }

    public function approveEnrollment($set = null)
    {
        if (!is_null($set)) {
            $this->approves_enrollment = $set ? 1 : 0;
            $this->_updateQuerys['approves_enrollment'] = 'approves_enrollment=' . $this->approves_enrollment;
        }
        return (bool) $this->approves_enrollment;
    }

    public function photoPerm($set = null, $returnStr = true)
    {
        if (!is_null($set) && isset(self::$_photoPermOpts[$set])) {
            $this->photo_permission = (int) $set;
            $this->_updateQuerys['photo_permission'] = 'photo_permission=' . $this->photo_permission;
        }
        if ($returnStr && isset(self::$_photoPermOpts[$this->photo_permission])) {
            return self::$_photoPermOpts[$this->photo_permission];
        }
        return $this->photo_permission;
    }

    public function dirPerm($set = null, $returnStr = true)
    {
        if (!is_null($set) && isset(self::$_dirPermOpts[$set])) {
            $this->dir_permission = (int) $set;
            $this->_updateQuerys['dir_permission'] = 'dir_permission=' . $this->dir_permission;
        }
        if ($returnStr && isset(self::$_dirPermOpts[$this->dir_permission])) {
            return self::$_dirPermOpts[$this->dir_permission];
        }
        return $this->dir_permission;
    }

    public function accept()
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->_updateQuerys['status'] = 'status="' . $this->status . '"';
        $this->date_accepted = date('Y-m-d H:i:s');
        $this->_updateQuerys['date_accepted'] = '`date_accepted`="' . $this->date_accepted . '"';
        if (($student = $this->getStudent())) {
            $application = mth_application::getStudentApplication($student);
            $schoolYear = self::getActivePacketYear($this);
            $parent = $student->getParent();
            $find = [
                '[PARENT]',
                '[STUDENT]',
                '[YEAR]',
            ];
            $replace = [
                $parent->getPreferredFirstName(),
                $student->getPreferredFirstName(),
                $schoolYear,
            ];
            if (!$student->getReenrolled()) {
                $student->setStatus(mth_student::STATUS_PENDING, $schoolYear);
                $emailContent = core_setting::get('packetAcceptedEmail', 'Packets');
                $emailSubject = core_setting::get('packetAcceptedEmailSubject', 'Packets');
            }

            if ($student->getReenrolled()) {
                $emailContent = core_setting::get('ReEnrollmentPacketAcceptanceContent', 'Re-enrollment');
                $emailSubject = core_setting::get('ReEnrollmentPacketAcceptanceSubject', 'Re-enrollment');
            }

            $email = new core_emailservice();
            $email_result = $email->send(
                array($parent->getEmail()),
                str_replace(
                    $find,
                    $replace,
                    $emailSubject->getValue()
                ),
                str_replace(
                    $find,
                    $replace,
                    $emailContent->getValue()
                )
            );
            if (!$email_result) {
                core_notify::addError('Unable to email parent!');
            }

        }
        $this->setReuploadFiles([]);
    }

    public function dateAssignedToSoe(bool $set = false, $format = null)
    {
        if ($set === true) {
            $this->date_assigned_to_soe = date('Y-m-d H:i:s');
            $this->_updateQuerys['date_assigned_to_soe'] = '`date_assigned_to_soe`="' . $this->date_assigned_to_soe . '"';
        }
        return core_model::getDate($this->date_assigned_to_soe, $format);
    }

    public function setReuploadFiles(array $reuploadFileTypes)
    {
        foreach ($reuploadFileTypes as &$value) {
            $value = cms_content::sanitizeText((string) $value);
        }
        $this->reupload_files = $reuploadFileTypes;
        $this->_updateQuerys['reupload_files'] = 'reupload_files="' . core_db::escape(serialize($this->reupload_files)) . '"';
    }

    public function getReuploadFiles()
    {
        if (empty($this->reupload_files)) {
            return array();
        }
        if (!is_array($this->reupload_files)) {
            $this->reupload_files = unserialize($this->reupload_files);
        }
        return $this->reupload_files;
    }

    public function getSchoolDistrict()
    {
        return $this->getStudent()->getParent() !== NULL ? $this->getStudent()->getParent()->getAddress()->getSchoolDistrictOfR() : '';
    }

    public function setSchoolDistrict($school_district)
    {
        if (!in_array($school_district, self::$_schoolDistricts)) {
            return false;
        }
        $this->school_district = cms_content::sanitizeText($school_district);
        $this->_updateQuerys['school_district'] = 'school_district="' . core_db::escape($this->school_district) . '"';
        return true;
    }

    public function getSpecialEd($number = false)
    {
        if ($this->special_ed == 1) {
            $this->special_ed = 0;
        }
        if (!isset(self::$_specialEdOpts[$this->special_ed])) {
            return null;
        }
        if ($number) {
            return (int) $this->special_ed;
        }
        return self::$_specialEdOpts[$this->special_ed];
    }

    public function requireIEP()
    {
        $require = [self::SPECIALED_IEP, self::SPECIALED_504];
        $student_not_require = [mth_student::SPED_EXIT, mth_student::SPED_NO];
        $SPED = $this->getSpecialEd(true);
        //return $SPED ? $SPED > 1 : $this->getStudent()->specialEd();
        return $SPED ? in_array($SPED, $require) : !in_array($this->getStudent()->specialEd(), $student_not_require);
    }

    public function setSpecialEd($special_ed)
    {
        if (!isset(self::$_specialEdOpts[$special_ed])) {
            return;
        }
        $this->special_ed = (int) $special_ed;
        $this->_updateQuerys['special_ed'] = 'special_ed="' . $this->special_ed . '"';

        $sped_map = [
            self::SPECIALED_NO => mth_student::SPED_NO,
            self::SPECIALED_IEP => mth_student::SPED_IEP,
            self::SPECIALED_504 => mth_student::SPED_504,
            self::SPECIALED_EXIT => mth_student::SPED_EXIT,
        ];

        $sped = isset($sped_map[$this->special_ed]) ? $sped_map[$this->special_ed] : $this->special_ed;
        $this->getStudent()->set_spacial_ed($sped);
    }

    public function setSpecialEdDesc($special_ed_desc)
    {
        $this->special_ed_desc = nl2br(htmlentities(strip_tags($special_ed_desc)));
        $this->_updateQuerys['special_ed_desc'] = 'special_ed_desc="' . core_db::escape($this->special_ed_desc) . '"';
    }

    public function getSpecialEdDesc($html = true)
    {
        if (!$html) {
            return strip_tags($this->special_ed_desc);
        }
        return $this->special_ed_desc;
    }

    public function getLastSchoolName()
    {
        return $this->last_school;
    }

    public function setLastSchoolName($last_school_name)
    {
        $this->last_school = cms_content::sanitizeText($last_school_name);
        $this->_updateQuerys['last_school'] = 'last_school="' . core_db::escape($this->last_school) . '"';
    }

    public function getLastSchoolType($number = false)
    {
        if (!$number && isset($this->last_school_type)) {
            return self::$_schoolTypes[$this->last_school_type];
        }
        return $this->last_school_type;
    }

    public function setLastSchoolType($last_school_type)
    {
        if (!isset(self::$_schoolTypes[$last_school_type])) {
            return false;
        }
        $this->last_school_type = (int) $last_school_type;
        $this->_updateQuerys['last_school_type'] = 'last_school_type=' . $this->last_school_type;
        return true;
    }

    public function getLastSchoolAddress($html = true)
    {
        if (!$html) {
            return strip_tags($this->last_school_address);
        }
        return $this->last_school_address;
    }

    public function setLastSchoolAddress($last_school_address)
    {
        $this->last_school_address = nl2br(htmlentities(strip_tags($last_school_address)));
        $this->_updateQuerys['last_school_address'] = 'last_school_address="' . core_db::escape($this->last_school_address) . '"';
    }

    public function getPermissionToRequestRecords()
    {
        if (is_null($this->permission_to_request_records)) {
            return null;
        }
        return (bool) $this->permission_to_request_records;
    }

    public function setPermissionToRequestRecords($permission_to_request_records)
    {
        $this->permission_to_request_records = (int) (bool) $permission_to_request_records;
        $this->_updateQuerys['permission_to_request_records'] = 'permission_to_request_records=' . $this->permission_to_request_records;
    }

    public function getUnderstandsSpecialEd()
    {
        return (bool) $this->understands_special_ed;
    }

    public function getUnderstandsSpedScheduling()
    {
        return (bool) $this->understands_sped_scheduling;
    }

    public function setUnderstandsSpecialEd($understands_special_ed)
    {
        $this->understands_special_ed = $understands_special_ed ? 1 : 0;
        $this->_updateQuerys['understands_special_ed'] = 'understands_special_ed=' . $this->understands_special_ed;
    }

    public function setUnderstandsSpedScheduling($understands_sped_scheduling)
    {
        $this->understands_sped_scheduling = $understands_sped_scheduling ? 1 : 0;
        $this->_updateQuerys['understands_sped_scheduling'] = 'understands_sped_scheduling=' . $this->understands_sped_scheduling;
    }

    public function getBirthPlace()
    {
        return $this->birth_place;
    }

    public function setBirthPlace($birthPlace)
    {
        $this->birth_place = req_sanitize::txt($birthPlace);
        $this->_updateQuerys['birth_place'] = 'birth_place="' . core_db::escape($this->birth_place) . '"';
    }

    public function getBirthCountry($return_name = false)
    {
        if ($return_name) {
            $countries = self::getAvailableCountries();
            if (isset($countries[$this->birth_country])) {
                return $countries[$this->birth_country];
            }
        }
        return $this->birth_country;
    }

    public function setBirthCountry($country_code)
    {
        if (!$country_code && core_user::isUserAdmin()) {
            $this->birth_country = null;
            $this->_updateQuerys['birth_country'] = 'birth_country=NULL';
        }
        $counties = self::getAvailableCountries();
        if (!isset($counties[$country_code])) {
            return false;
        }
        $this->birth_country = $country_code;
        $this->_updateQuerys['birth_country'] = 'birth_country="' . core_db::escape($this->birth_country) . '"';
        return true;
    }

    public function getWorkedInAgriculture()
    {
        if ($this->worked_in_agriculture === null) {
            return null;
        }
        return (bool) $this->worked_in_agriculture;
    }

    public function setWorkedInAgriculture($bool)
    {
        $this->worked_in_agriculture = $bool ? 1 : 0;
        $this->_updateQuerys['worked_in_agriculture'] = 'worked_in_agriculture=' . $this->worked_in_agriculture;
    }

    public function getMilitary()
    {
        if ($this->military === null) {
            return null;
        }
        return (bool) $this->military;
    }

    public function setMilitary($bool)
    {
        $this->military = $bool ? 1 : 0;
        $this->_updateQuerys['military'] = 'military=' . $this->military;
    }

    public function setExempImmunization($bool)
    {
        $this->exemp_immunization = $bool ? 1 : 0;
        $this->_updateQuerys['exemp_immunization'] = 'exemp_immunization=' . $this->exemp_immunization;
    }

    public function setMilitaryBranch($string)
    {
        $this->military_branch = req_sanitize::txt($string);
        $this->_updateQuerys['military_branch'] = 'military_branch="' . core_db::escape($this->military_branch) . '"';
    }

    public function isHispanic()
    {
        if (is_null($this->hispanic)) {
            return null;
        }
        return (bool) $this->hispanic;
    }

    public function setHispanic($hispanic)
    {
        $this->hispanic = (int) (bool) $hispanic;
        $this->_updateQuerys['hispanic'] = 'hispanic=' . $this->hispanic;
    }

    public function getRace($returnString = true, $returnArrayString = false)
    {
        if (!$this->race) {
            return $returnString ? '' : array();
        }
        if (!is_array($this->race)) {
            $this->race = unserialize($this->race);
        }
        if ($returnString) {
            $raceArr = $this->race;
            foreach ($raceArr as $num => $race) {
                if (isset(self::$_raceOpts[$race])) {
                    $raceArr[$num] = self::$_raceOpts[$race];
                }
            }
            return $returnArrayString ? $raceArr : implode(', ', $raceArr);
        }
        return $this->race;
    }

    public function checkRace($param)
    {        
            if (!is_array($this->race)) {
                $this->race = unserialize($this->race);
            }
           
            return in_array($param, $this->race)?'Y':'N';
    }

   

    public function isRace($race)
    {
        if (!$this->getRace()) {
            return null;
        }
        return in_array($race, $this->getRace(false));
    }

    public function setRace(array $race)
    {
        $raceArr = array();
        foreach ($race as $raceItem) {
            if (isset(self::$_raceOpts[$raceItem])) {
                $raceArr[] = $raceItem;
            } else {
                $raceArr[] = ucwords(trim(cms_content::sanitizeText($raceItem)));
            }
        }
        $this->race = serialize($raceArr);
        $this->_updateQuerys['race'] = 'race="' . core_db::escape($this->race) . '"';
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = cms_content::sanitizeText($language);
        $this->_updateQuerys['language'] = 'language="' . core_db::escape($this->language) . '"';
    }

    public function getLanguageAtHome()
    {
        return $this->language_home;
    }

    public function setLanguageAtHome($language)
    {
        $this->language_home = cms_content::sanitizeText($language);
        $this->_updateQuerys['language_home'] = 'language_home="' . core_db::escape($this->language_home) . '"';
    }

    /**
     * @return mixed
     */
    public function getLanguageHomeChild()
    {
        return $this->language_home_child;
    }

    /**
     * @param mixed $language_home_child
     */
    public function setLanguageHomeChild($language_home_child)
    {
        $this->language_home_child = cms_content::sanitizeText($language_home_child);
        $this->_updateQuerys['language_home_child'] = 'language_home_child="' . core_db::escape($this->language_home_child) . '"';
    }

    /**
     * @return mixed
     */
    public function getLanguageFriends()
    {
        return $this->language_friends;
    }

    /**
     * @param mixed $language_friends
     */
    public function setLanguageFriends($language_friends)
    {
        $this->language_friends = cms_content::sanitizeText($language_friends);
        $this->_updateQuerys['language_friends'] = 'language_friends="' . core_db::escape($this->language_friends) . '"';
    }

    /**
     * @return mixed
     */
    public function getLanguageHomePreferred()
    {
        return $this->language_home_preferred;
    }

    /**
     * @param mixed $language_home_preferred
     */
    public function setLanguageHomePreferred($language_home_preferred)
    {
        $this->language_home_preferred = cms_content::sanitizeText($language_home_preferred);
        $this->_updateQuerys['language_home_preferred'] = 'language_home_preferred="' . core_db::escape($this->language_home_preferred) . '"';
    }

    /**
     * @return bool|null
     */
    public function getWorkMove()
    {
        if (isset($this->work_move)) {
            return (bool) $this->work_move;
        }
        return null;
    }

    /**
     * @param bool $work_move
     * @return bool
     */
    public function setWorkMove($work_move)
    {
        $this->work_move = $work_move ? 1 : 0;
        $this->_updateQuerys['work_move'] = 'work_move=' . $this->work_move;
        return true;
    }

    /**
     * @param bool $return_label
     * @return mixed
     */
    public function getLivingLocation($return_label = true)
    {
        if (
            $return_label
            && $this->living_location
            && ($labels = \mth\packet\LivingLocationEnum::getLabels())
        ) {
            return $labels[$this->living_location];
        }
        return $this->living_location;
    }

    /**
     * @param mixed $living_location
     * @return bool
     */
    public function setLivingLocation($living_location)
    {
        if (!\mth\packet\LivingLocationEnum::isValidValue((int) $living_location)) {
            return false;
        }
        $this->living_location = (int) $living_location;
        $this->_updateQuerys['living_location'] = 'living_location=' . $this->living_location;
        return true;
    }

    /**
     * @param bool $return_label
     * @return mixed
     */
    public function getLivesWith($return_label = true)
    {
        if (
            $return_label
            && $this->lives_with
            && ($labels = \mth\packet\LivesWithEnum::getLabels())
        ) {
            return $labels[$this->lives_with];
        }
        return $this->lives_with;
    }

    /**
     * @param mixed $lives_with
     * @return bool
     */
    public function setLivesWith($lives_with)
    {
        if ($lives_with && !\mth\packet\LivesWithEnum::isValidValue((int) $lives_with)) {
            return false;
        }
        $this->lives_with = $lives_with ? (int) $lives_with : 'null';
        $this->_updateQuerys['lives_with'] = 'lives_with=' . ($this->lives_with ? $this->lives_with : 'NULL');
        return true;
    }

    public function getSecondaryContactFirst()
    {
        return $this->secondary_contact_first;
    }

    public function getSecondaryContactLast()
    {
        return $this->secondary_contact_last;
    }

    public function setSecondaryContact($first, $last)
    {
        $this->secondary_contact_first = ucwords(cms_content::sanitizeText($first));
        $this->_updateQuerys['secondary_contact_first'] = 'secondary_contact_first="' . core_db::escape($this->secondary_contact_first) . '"';
        $this->secondary_contact_last = ucwords(cms_content::sanitizeText($last));
        $this->_updateQuerys['secondary_contact_last'] = 'secondary_contact_last="' . core_db::escape($this->secondary_contact_last) . '"';
    }

    public function getRelationShipParentInfo()
    {
        return $this->relationship_parentinfo;
    }

    public function setRelationShipParentInfo($relationship)
    {
        $this->_updateQuerys['relationship_parentinfo'] = 'relationship_parentinfo="' . $relationship . '"';
    }

    public function getRelationShipSecondaryContact()
    {
        return $this->relationship_secondaryContact;
    }

    public function setRelationShipSecondaryContact($relationship)
    {
        $this->_updateQuerys['relationship_secondaryContact'] = 'relationship_secondaryContact="' . $relationship . '"';
    }

    public function getSecondaryPhone()
    {
        return $this->secondary_phone;
    }

    public function setSecondaryPhone($phone)
    {
        $this->secondary_phone = mth_phone::formatNumber(mth_phone::sanitizeNumber($phone));
        $this->_updateQuerys['secondary_phone'] = 'secondary_phone="' . $this->secondary_phone . '"';
    }

    public function getSecondaryEmail()
    {
        return $this->secondary_email;
    }

    public function setSecondaryEmail($email)
    {
        $email = strtolower(trim($email));
        if (!core_user::validateEmail($email)) {
            return false;
        }
        $this->secondary_email = $email;
        $this->_updateQuerys['secondary_email'] = 'secondary_email="' . core_db::escape($this->secondary_email) . '"';
        return true;
    }

    public function getHouseholdSize()
    {
        return (int) $this->household_size;
    }

    public function setHouseholdSize($household_size)
    {
        $this->household_size = (int) $household_size;
        $this->_updateQuerys['household_size'] = 'household_size="' . $this->household_size . '"';
    }

    public function setAdminNotes($admin_notes)
    {
        $db = new core_db();
        $this->admin_notes = $db->escape_string($admin_notes);
        $this->_updateQuerys['admin_notes'] = 'admin_notes="' . $this->admin_notes . '"';
    }

    public function getAdminNotes()
    {
        return $this->admin_notes;
    }

    public function setImmunizationNotes($immunization_notes)
    {
        $db = new core_db();
        $this->immunization_notes = $db->escape_string($immunization_notes);
        $this->_updateQuerys['immunization_notes'] = 'immunization_notes="' . $this->immunization_notes . '"';
    }

    public function getImmunizationNotes()
    {
        return $this->immunization_notes;
    }

    public function getHouseholdIncome($returnString = true)
    {
        if (isset(self::$_incomeOpts[$this->household_income]) && $returnString) {
            return self::$_incomeOpts[$this->household_income];
        }
        return $this->household_income;
    }

    public function setHouseholdIncome($income)
    {
        if (!isset(self::$_incomeOpts[$income])) {
            return false;
        }
        $this->household_income = (int) $income;
        $this->_updateQuerys['household_income'] = 'household_income=' . $this->household_income;
        return true;
    }

    public function getFERPAagreement()
    {
        if (isset(self::$_ferpaOpts[$this->ferpa_agreement])) {
            return self::$_ferpaOpts[$this->ferpa_agreement];
        }
        return null;
    }

    public function setFERPAagreement($value)
    {
        if (!isset(self::$_ferpaOpts[$value])) {
            return false;
        }
        $this->ferpa_agreement = (int) $value;
        $this->_updateQuerys['ferpa_agreement'] = 'ferpa_agreement=' . $this->ferpa_agreement;
        return true;
    }

    public function getSignatureName()
    {
        return $this->signature_name;
    }

    public function getSignatureFileID()
    {
        return (int) $this->signature_file_id;
    }

    public function getSignatureFileContent()
    {
        if (($sig = mth_packet_file::getByID($this->signature_file_id))) {
            return $sig->getContents();
        }
        return null;
    }

    public function saveSignatureFile($svgXMLbase64content)
    {
        $sigContent = base64_decode($svgXMLbase64content);
        mth_packet_file::saveFile(mth_packet_file::KIND_SIG . '.svg', $sigContent, 'image/svg+xml', mth_packet_file::KIND_SIG, $this);
        if (($file = mth_packet_file::getPacketFile($this, mth_packet_file::KIND_SIG))) {
            $this->signature_file_id = $file->getID();
            $this->_updateQuerys['signature_file_id'] = 'signature_file_id=' . $this->signature_file_id;
        }
    }

    public function save()
    {
        if (empty($this->_updateQuerys)) {
            return true;
        }
        if ($this->status == self::STATUS_NOT_STARTED) {
            $this->setStatus(self::STATUS_STARTED);
        }
        $query = 'UPDATE mth_packet SET ' . implode(',', $this->_updateQuerys) . ' WHERE packet_id=' . $this->getID();
        return core_db::runQuery($query);
    }

    public function __destruct()
    {
        $this->save();
    }

    public function isSubmitted()
    {
        return !in_array($this->status, array(self::STATUS_STARTED, self::STATUS_MISSING, self::STATUS_NOT_STARTED));
    }

    public function isResubmitted()
    {
        return in_array($this->status, array(self::STATUS_RESUBMITTED));
    }

    public function isMissingInfo()
    {
        return $this->status == self::STATUS_MISSING;
    }

    public function isAccepted()
    {
        return $this->status == self::STATUS_ACCEPTED;
    }

    public function getDeadline($format = null)
    {
        return core_model::getDate($this->deadline, $format);
    }

    public function getExemptionFormDate($format = null)
    {
        return core_model::getDate($this->exemption_form_date, $format);
    }

    public function getDateSubmitted($format = null)
    {
        return core_model::getDate($this->date_submitted, $format);
    }

    public function getDateLastSubmitted($format = null)
    {
        return core_model::getDate($this->date_last_submitted, $format);
    }

    public function getDateLastSubmittedOrSubmitted($format = null)
    {
        if ($this->date_last_submitted) {
            return $this->getDateLastSubmitted($format);
        }

        return $this->getDateSubmitted($format);
    }

    public function getDateAccepted($format = null)
    {
        return core_model::getDate($this->date_accepted, $format);
    }

    public function isPassedDue()
    {
        return $this->getDeadline('Ymd') <= date('Ymd');
    }
    /**
     * Delete Packet
     * @param boolean $packet_only true if delete packet object only otherwise delete everything that's linked on the student
     * @return boolean
     */
    public function delete($packet_only = false)
    {
        if (!($student = $this->getStudent())) {
            //persistent delete
            return $this->_delete();
        }

        if (!($app = mth_application::getStudentApplication($student))) {
            return false;
        }

        if ($packet_only) {
            //If Status is accepted reset the packet otherwise it will only fall to the delete packet
            //given that ther application is not Accepted for example Submitted the it needs an approval
            //before student will start packet submission
            if ($app->isAccepted()) {
                self::create($student);
            }
        } else {
            if (!$app->delete()) {
                return false;
            }

            //triggers to delete everything that is linked to student
            if (!$student->delete()) {
                $previous_status = $student->getPreviousWithdrawal();
                if ($previous_status) {
                    $school_year = mth_schoolYear::getByID($previous_status->school_year_id);
                    $student->setStatus($previous_status->status, $school_year, $previous_status->date_updated);
                }
            }
        }

        return $this->_delete();
    }

    public function _delete()
    {
        return core_db::runQuery('DELETE FROM mth_packet WHERE packet_id=' . $this->getID());
    }

    public static function getStatusCounts()
    {
        $result = core_db::runQuery('SELECT p.status, COUNT(p.packet_id)
                                  FROM mth_packet AS p
                                    INNER JOIN mth_student AS s ON s.student_id=p.student_id
                                  WHERE p.status!="' . self::STATUS_ACCEPTED . '"
                                  AND s.parent_id IS NOT NULL
                                  GROUP BY p.status');
        $statuses = self::getAvailableStatuses();
        $arr = array_combine($statuses, array_fill(0, count($statuses), 0));
        unset($arr[self::STATUS_ACCEPTED]);

        while ($r = $result->fetch_row()) {
            $arr[$r[0]] = $r[1];
        }
        $result->free_result();
        return $arr;
    }

    public static function getActivePacketYear($packet = null)
    {
        if ($packet && ($student = $packet->getStudent()) && ($application = mth_application::getStudentApplication($student))) {
            return $application->getSchoolYear(true);
        }

        $current_year = mth_schoolYear::getCurrent();

        /**
         * Switch Year on March
         */
        if (date('n') > 2 && ($current_year->getDateBegin() > time() && $current_year->getDateEnd() < time())) {
            return mth_schoolYear::getNext();
        }

        return $current_year;
    }

    public function getReenrollFiles()
    {
        if (empty($this->reenroll_files)) {
            return array();
        }
        if (!is_array($this->reenroll_files)) {
            $this->reenroll_files = unserialize($this->reenroll_files);
        }
        return $this->reenroll_files;
    }

    public function setReenrollFiles($fileArray = [], $clear = false)
    {
        $student = $this->getStudent();
        if ($clear) {
            core_db::runQuery('UPDATE mth_packet set reenroll_files="" where student_id=' . $student->getID());
            return $this;
        }
        foreach ($fileArray as &$value) {
            $value = cms_content::sanitizeText((string) $value);
        }
        if ($this->reenroll_files === null) {
            $this->reenroll_files = $fileArray;
        } else {
            $this->reenroll_files = array_merge((array) $this->getReenrollFiles(), $fileArray);
        }
        core_db::runQuery('UPDATE mth_packet set reenroll_files="' . core_db::escape(serialize($this->reenroll_files)) . '" where student_id=' . $student->getID());
    }

    public function requiresReenrollFiles($getArray = false)
    {
        $student = $this->getStudent();
        if ($getArray) {
            return array(
                'iep' => (core_setting::get('iep_documents', 'packet_settings')->getValue() && $this->requireIEP()),
                'im' => core_setting::get('immunizations', 'packet_settings')->getValue() && $student->getRequiredImmunizations(),
                'ur' => core_setting::get('proof_of_residency', 'packet_settings')->getValue(),
            );
        }
        return (core_setting::get('iep_documents', 'packet_settings')->getValue() && $this->requireIEP()) ||
        core_setting::get('immunizations', 'packet_settings')->getValue() && $student->getRequiredImmunizations() ||
        core_setting::get('proof_of_residency', 'packet_settings')->getValue();
    }

    public function nonDiplomaSeeking($set = null)
    {
        if (!is_null($set)) {
            $this->non_diploma_seeking = $set ? 1 : 0;
            $this->_updateQuerys['non_diploma_seeking'] = 'non_diploma_seeking=' . $this->non_diploma_seeking;
        }
        return (bool) $this->non_diploma_seeking;
    }

    public static function getSchoolDistrictbyState($state = null)
    {
        if (!is_null($state) && $state == 'OR') {
            return self::$_schoolDistrictsOR;
        } else if (!is_null($state) && $state == 'UT') {
            return self::$_schoolDistrictsUT;
        }
    }
}
