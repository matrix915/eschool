<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 3/1/17
 * Time: 4:46 PM
 */

namespace core;


class view
{
    const VIEWS_DIR = '/_/views/';

    protected $view = null;
    protected $vars = array();

    /**
     * view constructor.
     * @param string $view relative to the includes/views directory excluding the .php (e.g. "main" for the includes/views/main.php view)
     */
    public function __construct($view)
    {
        $this->view = $view.(substr($view,-4)=='.php'?'':'.php');
    }

    public function printView($vars = array())
    {
        extract($this->vars);
        extract($vars);
        /** @noinspection PhpIncludeInspection */
        include ROOT.self::VIEWS_DIR.$this->view;
    }

    public function getViewContent($vars = array()){
        ob_start();
        $this->printView($vars);
        return ob_get_clean();
    }

    public function set($field, $value)
    {
        $this->vars[$field] = $value;
    }

    public function setMultiple(array $assoc_array)
    {
        $this->vars = $assoc_array+$this->vars;
    }
}