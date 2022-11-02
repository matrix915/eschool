<?php

use mth\student\SchoolOfEnrollment;
class mth_assistant{
    const TYPE_PROVIDER = 1;
    const TYPE_SCHOOL = 2;
    const TYPE_IEP = 3;

    const TYPES = [
        self::TYPE_PROVIDER => 'Provider TA',
        self::TYPE_SCHOOL => 'School of Enrollment TA',
        self::TYPE_IEP => 'SPED TA'
    ];

    protected $assistant_id;
    protected $user_id;
    protected $value;
    protected $type;
    
    protected static $cache = array();

    public function getUserID(){
        return $this->user_id;
    }
    
    public function getValue(){
        return $this->value;
    }

    public function getType(){
        return $this->type;
    }

    public static function getTypeLabel($type){
        return self::TYPES[$type];
    }

    public function isAssignToSchool(){
        return $this->type == self::TYPE_SCHOOL;
    }

    public function isAssignToProvider(){
        return $this->type == self::TYPE_PROVIDER;
    }

    public function isAssignToSped(){
        return $this->type == self::TYPE_IEP;
    }

    public static function getTypes($html = false){
        if(!$html){
            return self::TYPES;
        }
        
        $_html = '';
        foreach(self::TYPES as $id => $type){
            $_html .= '<option value='.$id.'>'.$type.'</option>';
        }
        return $_html;
    }
    
    public static function getArrayValues($type, array $values){
        if($type == self::TYPE_PROVIDER){
            return self::getProviderSelectedValues($values);
        }

        if($type == self::TYPE_IEP){
           return self::getSPEDSelectedValues($values);
        }

        if($type == self::TYPE_SCHOOL){
            return self::getSchoolSelectedValues($values);
        }
    }

    public static function getSchoolSelectedValues(array $values){
        $schools = [];
        foreach($values as $school_id){
            if($school = SchoolOfEnrollment::get($school_id)){
                $schools[] = $school->getShortName();
            }
        }
        return $schools;
    }

    public static function getProviderSelectedValues(array $values){
        $providers = [];
        foreach($values as $provider_id){
            if($provider = mth_provider::get($provider_id)){
                $providers[] = $provider->name();
            }
        }
        return $providers;
    }

    public static function getSPEDSelectedValues(array $values){
        $speds = [];
        foreach($values as $sped_id){
            if($sped = mth_student::getSped($sped_id)){
                $speds[] = $sped;
            }
        }
        return $speds;
    }
    

    public static function getTypeValues($type,$html = false){
        if($type == self::TYPE_PROVIDER){
            return self::getProviderValues($html);
        }

        if($type == self::TYPE_IEP){
           return self::getSPEDValues($html);
        }

        if($type == self::TYPE_SCHOOL){
            return self::getSchoolValues($html);
        }
    }

    public static function getProviderValues($html = false){
        $arr = [];
        $_html = '';

        mth_provider::each(NULL,NULL,false,true);
        while ($provider = mth_provider::each()) {
            if($html){
                $_html.= '<div class="checkbox-custom checkbox-primary"><input type="checkbox" value="'.$provider->id().'" name="assistant_value[]"><label>'.$provider->name().'</label></div>';
            }else{
                $arr[$provider->id()] = $provider;
            }
        }
        
        return $html?$_html:$arr;
    }

    public static function getSPEDValues($html = false){
        $sped = mth_student::getAvailableSpEd();
       
        if($html){
            $_html = '';
            foreach($sped as $id=>$sp){
                $_html.= '<div class="checkbox-custom checkbox-primary"><input type="checkbox" value="'.$id.'" name="assistant_value[]"><label>'.$sp.'</label></div>';
            }
            return $_html;
        }
        return $sped;
    }

    public static function getSchoolValues($html = false){
        $data = SchoolOfEnrollment::getActive();
        if($html){
            $_html = '';
            foreach($data as $id => $school){
                $_html.= '<div class="checkbox-custom checkbox-primary"><input type="checkbox" value="'.$id.'" name="assistant_value[]"><label>'.$school->getShortName().'</label></div>';
            }
            return $_html;
        }
        return $data;
    }

    public static function newAsssistant($user_id,$value,$type){
        $db = new core_db();
        $db->query(sprintf('REPLACE INTO mth_assistant_user (user_id, `value`, `type`)
                        VALUES (%d,%d,%d)',$user_id,$value,$type));
        return $db->insert_id;
    }

    public static function getByUserId($user_id){
        return core_db::runGetObjects('SELECT * FROM mth_assistant_user where user_id='.$user_id,
        __CLASS__);
    }
}