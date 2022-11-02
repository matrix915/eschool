<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 10/21/16
 * Time: 12:42 PM
 */

/**
 * create individual traits which use this trait to inject application specific classes:
 *
 * trait nameAfterInterfaceOfObjectBeingInjected
 * {
 *
 *      // type hint for the interface of the object being returned
 *      protected function getNameOfClass()
 *      {
 *          return $this->_getInjected('name\of\default\class');
 *      }
 * }
 *
 * then use that trait in your class to get the needed objects injected.
 *
 * To inject an alternate object just use the inject method:
 *
 * $myObject->inject(new AlternateClass());
 *
 */

namespace core;

trait Injectable
{
    protected $_injectable_injected = array();

    /**
     * @param string $default_class
     * @return mixed
     */
    protected function _getInjected($default_class){
        if(isset($this->_injectable_injected[$default_class])){
            return $this->_injectable_injected[$default_class];
        }

        //check interface
        $interface_arr = class_implements($default_class);
        if(($parent_class = get_parent_class($default_class))
            && ($parent_implements = class_implements($parent_class))
        ){
            $interface_arr = array_diff($interface_arr, $parent_implements); //we don't want the parent class interface
        }
        $interface = current($interface_arr); //only get the interface this class directly implements
        if($interface && isset($this->_injectable_injected[$interface])){
            return $this->_injectable_injected[$default_class] = $this->_injectable_injected[$interface];
        }

        //check for class injected elsewhere
        if(isset(g::$_injected[$default_class])){
            return $this->_injectable_injected[$default_class] = g::$_injected[$default_class];
        }

        //check interface injected elsewhere
        if($interface && isset(g::$_injected[$interface])){
            g::$_injected[$default_class] = g::$_injected[$interface];
            $this->_injectable_injected[$interface] = g::$_injected[$interface];
            return $this->_injectable_injected[$default_class] = g::$_injected[$interface];
        }

        $obj = new $default_class;

        g::$_injected[$default_class] = $obj;
        if($interface){ g::$_injected[$interface] = $obj; }
        return $this->_injectable_injected[$default_class] = $obj;
    }


    public function inject($object){
        $class = get_class($object);
        $this->_injectable_injected[$class] = $object;

        if(!isset(g::$_injected[$class])){
            g::$_injected[$class] = $object;
        }

        if(($parent_class = get_parent_class($object))){
            if(!isset($this->_injectable_injected[$parent_class])){
                $this->_injectable_injected[$parent_class] = $object;
            }
            if(!isset(g::$_injected[$parent_class])){
                g::$_injected[$parent_class] = $object;
            }
        }

        if( ($interface = current(class_implements($object))) ){
            if(!isset($this->_injectable_injected[$interface])){
                $this->_injectable_injected[$interface] = $object;
            }
            if(!isset(g::$_injected[$interface])){
                g::$_injected[$interface] = $object;
            }

        }
    }

    /**
     * @inheritDoc
     */
    function __wakeup()
    {
        $this->_injectable_injected = []; //Is this the best way to handle it? Probably shouldn't serialize objects that are injectable.
    }


}