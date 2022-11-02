<?php

/**
 *
 *
 * @author abe
 */
class cms_page
{

    /* @var core_path */
    protected $path;

    protected $content = array();
    protected $pageAreas = array();

    /* @var cms_page */
    private static $_default;

    const LOC_MAIN = 'MAIN';
    const LOC_TITLE = 'TITLE';
    const LOC_REDIRECT = 'REDIRECT';

    public static function getPrimaryLocations()
    {
        return array(self::LOC_MAIN, self::LOC_TITLE, self::LOC_REDIRECT);
    }

    public function __construct(core_path $path)
    {
        $this->path = $path;
        $this->content = cms_content::getPageContent($this);
        if (!isset($this->content[self::LOC_MAIN])) {
            $this->content[self::LOC_MAIN] = cms_content::defineLocationType(self::LOC_MAIN, cms_content::TYPE_HTML);
            $this->content[self::LOC_TITLE] = cms_content::defineLocationType(self::LOC_TITLE, cms_content::TYPE_TEXT);
            $this->content[self::LOC_MAIN] = $this->content[self::LOC_MAIN]->saveChanges('%', '<p>The requested page was not found.</p>', 0);
            $this->content[self::LOC_TITLE] = $this->content[self::LOC_TITLE]->saveChanges('%', 'Page Not Found', 0);
        }
    }

    /**
     *
     * @param core_path $path
     * @return \cms_page
     */
    public static function getPage(core_path $path)
    {
        return new cms_page($path);
    }

    public static function setDefaultPage(core_path $path = NULL)
    {
        if (!$path) {
            $path = core_path::getPath();
        }
        self::$_default = self::getPage($path);
    }

    public static function setDefaultTempTitle($title)
    {
        if (!self::$_default) {
            return false;
        }
        self::$_default->setTempTitle($title);
    }

    public function setTempTitle($title)
    {
        $this->content[self::LOC_TITLE] = cms_content::getTemp(self::LOC_TITLE, cms_content::TYPE_TEXT, $title, $this);
    }

    /**
     *
     * @param string $location
     * @param string $type
     * @return cms_content
     */
    public function getContent($location, $type)
    {
        $location = cms_content::sanitizeLocationString($location);

        if (in_array($location, self::getPrimaryLocations())) {
            error_log('Use the appropriate mthode to access ' . $location . ' content');
            return FALSE;
        }

        if (empty($location)) {
            return false;
        }

        $this->pageAreas[] = $location;

        if (isset($this->content[$location])) {
            return $this->content[$location];
        }

        $this->content[$location] = cms_content::defineLocationType($location, $type);

        return $this->content[$location];
    }

    /**
     *
     * @param string $location
     * @param string $type
     * @return cms_content
     */
    public static function getDefaultPageContent($location, $type)
    {
        if (!self::$_default)
            return false;
        return self::$_default->getContent($location, $type);
    }

    /**
     *
     * @return cms_content
     */
    public function getMainContent()
    {
        return $this->content[self::LOC_MAIN];
    }

    /**
     *
     * @return cms_content
     */
    public static function getDefaultPageMainContent()
    {
        if (!self::$_default)
            return false;
        return self::$_default->getMainContent();
    }

    /**
     *
     * @return cms_content
     */
    public function getTitleContent()
    {
        return $this->content[self::LOC_TITLE];
    }

    /**
     *
     * @return cms_content
     */
    public static function getDefaultPageTitleContent()
    {
        if (!self::$_default)
            return false;
        return self::$_default->getTitleContent();
    }

    public static function setPageTitle($title)
    {
        if (!self::$_default) {
            return false;
        }

        if (self::$_default->getPath() != self::$_default->getTitleContent()->getPath()) {
            self::$_default->getTitleContent()->saveChanges(self::$_default->getPath(), $title, 10);
            self::setDefaultPage(self::$_default->getPath());
        }
    }

    public static function setPageContent($content, $location = NULL, $type = NULL)
    {
        if (!self::$_default) {
            return false;
        }

        if (!$location) {
            $thisContent = self::getDefaultPageMainContent();
        } else {
            $thisContent = self::getDefaultPageContent($location, $type);
        }

        if (self::$_default->getPath() != $thisContent->getPath()) {
            $thisContent->saveChanges(self::$_default->getPath(), $content, 10);
            self::setDefaultPage(self::$_default->getPath());
        }
    }

    public function is404()
    {
        return !isset($this->content[self::LOC_TITLE])
        || $this->content[self::LOC_TITLE]->getPath() != $this->path->getString();
    }

    public static function isDefaultPage404()
    {
        if (!self::$_default)
            return false;
        return self::$_default->is404();
    }

    public function hasRedirect()
    {
        return isset($this->content[self::LOC_REDIRECT]);
    }

    /**
     * checks is a redirect for this page exsists and execute it if it does.
     * @return boolean
     */
    public function executeRedirect()
    {
        if (!$this->hasRedirect())
            return false;

        if (headers_sent()) {
            error_log('Attempted to redirect after headers were sent.');
            return false;
        }

        header('location: ' . $this->content[self::LOC_REDIRECT]);
        exit();
    }

    /**
     * checks is a redirect for the default page exsists and execute it if it does.
     * @return boolean
     */
    public static function executeDefaultPageRedirect()
    {
        if (!self::$_default)
            return false;
        return self::$_default->executeRedirect();
    }

    /**
     * call after all getContent references to included missing content
     * @param bool $excludeHTML
     * @return array of cms_content objects [location]=>cms_content
     */
    public function getAvailableContentAreas()
    {
        if (!core_user::isUserAdmin())
            return false;
        if ($this->content[self::LOC_MAIN]->getPath() == $this->path
            || $this->content[self::LOC_TITLE]->getPath() != $this->path
        ) {
            $arr['Main Content'] = $this->content[self::LOC_MAIN];
        } elseif ($this->content[self::LOC_TITLE]->getPath() == $this->path) {
            $arr['Page Title'] = $this->content[self::LOC_TITLE];
        }

        foreach ($this->content as $location => $content) {
            /* @var $content cms_content */
            if (!$content || !in_array($location, $this->pageAreas))
                continue;
            $arr[$location] = $content;
        }
        return $arr;
    }

    public static function getDefaultPageAvailableContentAreas()
    {
        if (!self::$_default)
            return false;
        return self::$_default->getAvailableContentAreas();
    }

    /**
     *
     * @return core_path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     *
     * @return core_path
     */
    public static function getDefaultPagePath()
    {
        if (!self::$_default)
            return false;
        return self::$_default->getPath();
    }
}
