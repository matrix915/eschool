<?php

($year = $_SESSION['mth_reports_school_year']) || die('No year set');

$file = 'eSchool Students - ' . $year;
$reportArr = array(array(
    'STUDENT_NUMBER',
    'LastFirst',
    'SCHOOLID',
    'GRADE_LEVEL',
    'First_Name',
    'Middle_Name',
    'Last_Name',
    'DOB',
    'Gender',
    'Home_Phone',
    'Street',
    'City',
    'State',
    'Zip',
    'Mailing_City',
    'Mailing_State',
    'Mailing_Street',
    'Mailing_Zip',
    'FedEthnicity',
    'FedRaceDecline',
    'race_african_american',
    'race_american_indian',
    'race_asian',
    'race_caucasian',
    'race_hawaiian',
    'tribe_goshute',
    'tribe_navajo',
    'tribe_other',
    'tribe_paiute',
    'tribe_shoshone_nwb',
    'tribe_ute',
    'SSN',
    'Alt_city',
    'Alt_name',
    'Alt_phone',
    'Alt_rel',
    'Alt_state',
    'Alt_street',
    'Alt_time',
    'Alt_zip',
    'birth_country',
    'birthplace',
    'c1_address',
    'c1_cell',
    'c1_cell_provider',
    'c1_city',
    'c1_employment',
    'c1_firstname',
    'c1_langauge',
    'c1_lastname',
    'c1_mailing',
    'c1_mailing_city',
    'c1_mailing_state',
    'c1_mailing_zip',
    'c1_relationship',
    'c1_state',
    'c1_work_email',
    'c1_work_phone',
    'c1_zip',
    'c2_address',
    'c2_cell',
    'c2_cell_provider',
    'c2_city',
    'c2_employment',
    'c2_firstname',
    'c2_langauge',
    'c2_lastname',
    'c2_mailing',
    'c2_mailing_city',
    'c2_mailing_state',
    'c2_mailing_zip',
    'c2_relationship',
    'c2_state',
    'c2_work_email',
    'c2_work_phone',
    'c2_zip',
    'c3_address',
    'c3_cell',
    'c3_cell_provider',
    'c3_city',
    'c3_employment',
    'c3_firstname',
    'c3_langauge',
    'c3_lastname',
    'c3_mailing',
    'c3_mailing_city',
    'c3_mailing_state',
    'c3_mailing_zip',
    'c3_relationship',
    'c3_state',
    'c3_work_email',
    'c3_work_phone',
    'c3_zip',
    'c4_address',
    'c4_cell',
    'c4_cell_provider',
    'c4_city',
    'c4_employment',
    'c4_firstname',
    'c4_langauge',
    'c4_lastname',
    'c4_mailing',
    'c4_mailing_city',
    'c4_mailing_state',
    'c4_mailing_zip',
    'c4_relationship',
    'c4_state',
    'c4_work_email',
    'c4_work_phone',
    'c4_zip',
    'Doctor_Name',
    'Doctor_Phone',
    'Emerg_Contact_1',
    'Emerg_Phone_1',
    'emerg_1_cell',
    'emerg_1_city',
    'emerg_1_lang',
    'Emerg_1_Rel',
    'emerg_1_state',
    'emerg_1_street',
    'emerg_1_work',
    'emerg_1_work_phone',
    'emerg_1_zip',
    'Emerg_Contact_2',
    'Emerg_Phone_2',
    'emerg_2_cell',
    'emerg_2_city',
    'emerg_2_lang',
    'Emerg_2_Rel',
    'emerg_2_state',
    'emerg_2_street',
    'emerg_2_work',
    'emerg_2_work_phone',
    'emerg_2_zip',
    'home_phone_carrier',
    'language_first',
    'language_first_enrolled',
    'language_home',
    'language_spoken',
    'permission_picture_display',
    'permission_picture_district',
    'permission_picture_newsletter',
    'permission_picture_newspaper',
    'permission_picture_sign',
    'prefer_name',
    'previous_school',
    'previous_school_address',
    'previous_school_district',
    'program_504_plan',
    'program_ELL',
    'program_gifted',
    'program_other',
    'program_special_ed_(IEP)',
    'program_speech',
    'reg_adhd',
    'reg_adhd_details',
    'reg_agriculture',
    'reg_allergies_epi',
    'reg_allergies_epi_details',
    'reg_allergies_food',
    'reg_allergies_food_details',
    'reg_allergies_lactose',
    'reg_allergies_lactose_details',
    'reg_allergies_meds',
    'reg_allergies_meds_details',
    'reg_allergies_other',
    'reg_allergies_other_details',
    'reg_allergies_seasonal',
    'reg_allergies_seasonal_details',
    'reg_asthma',
    'reg_asthma_details',
    'reg_birth_defect',
    'reg_birth_defect_details',
    'reg_blood_disorder',
    'reg_blood_disorder_details',
    'reg_bone_problems',
    'reg_bone_problems_details',
    'reg_counseling_consent',
    'reg_diabetes',
    'reg_diabetes_details',
    'reg_eschool_consent',
    'reg_eschool_contract',
    'reg_eschool_program',
    'reg_eye_problems',
    'reg_eye_problems_details',
    'reg_hearing_problems',
    'reg_hearing_problems_details',
    'reg_heartlung_problems',
    'reg_heartlung_problems_details',
    'reg_hospitalization',
    'reg_hospitalization_what',
    'reg_hospitalization_when',
    'reg_injury',
    'reg_injury_details',
    'reg_medical_consent',
    'reg_medical_consent_sign',
    'reg_medications',
    'reg_medications_what',
    'reg_medications_when',
    'reg_nosebleed',
    'reg_nosebleed_details',
    'reg_other_health',
    'reg_other_health_details',
    'reg_parenting_plan',
    'reg_previous_schoolid',
    'reg_restraining_order',
    'reg_seizures',
    'reg_seizures_details',
    'reg_stomach_problems',
    'reg_stomach_problems_details',
    'studentprefemail',
    'tech_parent',
    'tech_student'
));

function isRace(mth_packet $packet = NULL, $race = NULL)
{
    if (!$packet) {
        return '';
    }
    if (is_null($isRace = $packet->isRace($race))) {
        return '';
    }
    return $isRace ? 'Yes' : 'No';
}

$filter = new mth_person_filter();
$filter->setSchoolOfEnrollment(array(\mth\student\SchoolOfEnrollment::eSchool));
$filter->setStatus(array(mth_student::STATUS_ACTIVE, mth_student::STATUS_PENDING));
$filter->setStatusYear(array($year->getID()));
$missingPackets = 0;
foreach ($filter->getStudents() as $student) {
    /* @var $student mth_student */
    if (!($parent = $student->getParent())) {
        core_notify::addError('Parent Missing for ' . $student);
        break;
    }
    if (!($packet = mth_packet::getStudentPacket($student))) {
        $missingPackets++;
    }
    if (!($address = $parent->getAddress())) {
        core_notify::addError('Address Missing for ' . $parent);
        continue;
    }
    $reportArr[] = array(
        '',//STUDENT_NUMBER
        $student->getLastName() . ', ' . $student->getFirstName() . ' ' . $student->getMiddleName(), //LastFirst
        '',  //SCHOOLID
        $student->getGradeLevelValue($year->getID()),  //GRADE_LEVEL
        $student->getFirstName(),  //First_Name
        $student->getMiddleName(),  //Middle_Name
        $student->getLastName(),  //Last_Name
        $student->getDateOfBirth('Y-m-d'),  //DOB
        $student->getGender(),  //Gender
        $parent->getPhone('Home')->getNumber(),  //Home_Phone
        $address->getStreet() . ' ' . $address->getStreet2(),  //Street
        $address->getCity(),  //City
        $address->getState(),  //State
        $address->getZip(),  //Zip
        $address->getCity(),  //Mailing_City
        $address->getState(),  //Mailing_State
        $address->getStreet() . ' ' . $address->getStreet2(),  //Mailing_Street
        $address->getZip(),  //Mailing_Zip
        $packet ? ($packet->isHispanic() ? 'Yes' : 'No') : '',  //FedEthnicity
        '',  //FedRaceDecline
        isRace($packet, mth_packet::RACE_BLACK),  //race_african_american
        isRace($packet, mth_packet::RACE_NATIVE),  //race_american_indian
        isRace($packet, mth_packet::RACE_ASIAN),  //race_asian
        isRace($packet, mth_packet::RACE_WHITE),  //race_caucasian
        isRace($packet, mth_packet::RACE_ISLAND),  //race_hawaiian
        '',  //tribe_goshute
        '',  //tribe_navajo
        '',  //tribe_other
        '',  //tribe_paiute
        '',  //tribe_shoshone_nwb
        '',  //tribe_ute
        '',  //SSN
        '',  //Alt_city
        '',  //Alt_name
        '',  //Alt_phone
        '',  //Alt_rel
        '',  //Alt_state
        '',  //Alt_street
        '',  //Alt_time
        '',  //Alt_zip
        'United States of America',  //birth_country
        'Not Provided',  //birthplace
        $address->getStreet() . ' ' . $address->getStreet2(),  //c1_address
        $parent->getPhone('Cell')->getNumber(),  //c1_cell
        '',  //c1_cell_provider
        $address->getCity(),  //c1_city
        '',  //c1_employment
        $parent->getFirstName(),  //c1_firstname
        $packet ? $packet->getLanguageAtHome() : 'English',  //c1_langauge
        $parent->getLastName(),  //c1_lastname
        $address->getStreet() . ' ' . $address->getStreet2(),  //c1_mailing
        $address->getCity(),  //c1_mailing_city
        $address->getState(),  //c1_mailing_state
        $address->getZip(),  //c1_mailing_zip
        'Other',  //c1_relationship
        $address->getState(),  //c1_state
        $parent->getEmail(),  //c1_work_email
        '',  //c1_work_phone
        $address->getZip(),  //c1_zip
        '',  //c2_address
        $packet ? $packet->getSecondaryPhone() : '',  //c2_cell
        '',  //c2_cell_provider
        '',  //c2_city
        '',  //c2_employment
        $packet ? $packet->getSecondaryContactFirst() : '',  //c2_firstname
        '',  //c2_langauge
        $packet ? $packet->getSecondaryContactLast() : '',  //c2_lastname
        '',  //c2_mailing
        '',  //c2_mailing_city
        '',  //c2_mailing_state
        '',  //c2_mailing_zip
        '',  //c2_relationship
        '',  //c2_state
        $packet ? $packet->getSecondaryEmail() : '',  //c2_work_email
        '',  //c2_work_phone
        '',  //c2_zip
        '',  //c3_address
        '',  //c3_cell
        '',  //c3_cell_provider
        '',  //c3_city
        '',  //c3_employment
        '',  //c3_firstname
        '',  //c3_langauge
        '',  //c3_lastname
        '',  //c3_mailing
        '',  //c3_mailing_city
        '',  //c3_mailing_state
        '',  //c3_mailing_zip
        '',  //c3_relationship
        '',  //c3_state
        '',  //c3_work_email
        '',  //c3_work_phone
        '',  //c3_zip
        '',  //c4_address
        '',  //c4_cell
        '',  //c4_cell_provider
        '',  //c4_city
        '',  //c4_employment
        '',  //c4_firstname
        '',  //c4_langauge
        '',  //c4_lastname
        '',  //c4_mailing
        '',  //c4_mailing_city
        '',  //c4_mailing_state
        '',  //c4_mailing_zip
        '',  //c4_relationship
        '',  //c4_state
        '',  //c4_work_email
        '',  //c4_work_phone
        '',  //c4_zip
        '',  //Doctor_Name
        '',  //Doctor_Phone
        $parent->getFirstName(),  //Emerg_Contact_1
        $parent->getPhone('Home')->getNumber(),  //Emerg_Phone_1
        '',  //emerg_1_cell
        $address->getCity(),  //emerg_1_city
        'English',  //emerg_1_lang
        'Other',  //Emerg_1_Rel
        '',  //emerg_1_state
        '',  //emerg_1_street
        '',  //emerg_1_work
        '',  //emerg_1_work_phone
        '',  //emerg_1_zip
        $packet ? $packet->getSecondaryContactFirst() : '',  //Emerg_Contact_2
        $packet ? $packet->getSecondaryPhone() : '',  //Emerg_Phone_2
        '',  //emerg_2_cell
        $address->getCity(),  //emerg_2_city
        'English',  //emerg_2_lang
        'Other',  //Emerg_2_Rel
        '',  //emerg_2_state
        '',  //emerg_2_street
        '',  //emerg_2_work
        '',  //emerg_2_work_phone
        '',  //emerg_2_zip
        '',  //home_phone_carrier
        'English',  //language_first
        '',  //language_first_enrolled
        'No',  //language_home
        $packet ? $packet->getLanguage() : 'English',  //language_spoken
        'No',  //permission_picture_display
        'No',  //permission_picture_district
        'No',  //permission_picture_newsletter
        'No',  //permission_picture_newspaper
        'No',  //permission_picture_sign
        $student->getFirstName(),  //prefer_name
        '',  //previous_school
        '',  //previous_school_address
        '',  //previous_school_district
        '',  //program_504_plan
        '',  //program_ELL
        '',  //program_gifted
        '',  //program_other
        '',  //program_special_ed_(IEP)
        '',  //program_speech
        'No',  //reg_adhd
        '',  //reg_adhd_details
        'No',  //reg_agriculture
        'No',  //reg_allergies_epi
        '',  //reg_allergies_epi_details
        'No',  //reg_allergies_food
        '',  //reg_allergies_food_details
        'No',  //reg_allergies_lactose
        '',  //reg_allergies_lactose_details
        'No',  //reg_allergies_meds
        '',  //reg_allergies_meds_details
        'No',  //reg_allergies_other
        '',  //reg_allergies_other_details
        'No',  //reg_allergies_seasonal
        '',  //reg_allergies_seasonal_details
        'No',  //reg_asthma
        '',  //reg_asthma_details
        'No',  //reg_birth_defect
        '',  //reg_birth_defect_details
        'No',  //reg_blood_disorder
        '',  //reg_blood_disorder_details
        'No',  //reg_bone_problems
        '',  //reg_bone_problems_details
        'OF',  //reg_counseling_consent
        'No',  //reg_diabetes
        '',  //reg_diabetes_details
        'OF',  //reg_eschool_consent
        'OF',  //reg_eschool_contract
        'I enrolled through My Tech High',  //reg_eschool_program
        'No',  //reg_eye_problems
        '',  //reg_eye_problems_details
        'No',  //reg_hearing_problems
        '',  //reg_hearing_problems_details
        'No',  //reg_heartlung_problems
        '',  //reg_heartlung_problems_details
        'No',  //reg_hospitalization
        '',  //reg_hospitalization_what
        '',  //reg_hospitalization_when
        'No',  //reg_injury
        '',  //reg_injury_details
        'No',  //reg_medical_consent
        'OF',  //reg_medical_consent_sign
        'No',  //reg_medications
        '',  //reg_medications_what
        '',  //reg_medications_when
        'No',  //reg_nosebleed
        '',  //reg_nosebleed_details
        'No',  //reg_other_health
        '',  //reg_other_health_details
        '',  //reg_parenting_plan
        '',  //reg_previous_schoolid
        '',  //reg_restraining_order
        'No',  //reg_seizures
        '',  //reg_seizures_details
        'No',  //reg_stomach_problems
        '',  //reg_stomach_problems_details
        $parent->getEmail(),  //studentprefemail
        'Yes',  //tech_parent
        'Yes, I have informed my student of this policy',  //tech_student
    );
}
core_notify::addError('Missing packets for ' . $missingPackets . ' Students');

include ROOT . core_path::getPath('../report.php');