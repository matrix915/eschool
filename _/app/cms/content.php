<?php

/**
 * cms page content items
 *
 * @author abe
 */
class cms_content
{
    protected $content_id;
    protected $path;
    protected $type;
    protected $location;
    protected $content;
    protected $time;
    protected $published;
    protected $priority;

    const TYPE_HTML = 'HTML';
    const TYPE_TEXT = 'Text';
    const TYPE_LIMITED_HTML = 'Limited';
    const TYPE_REDIRECT = 'Redirect';
    const TYPE_URL = 'URL';

    public static function getAvailableTypes()
    {
        return array(self::TYPE_HTML, self::TYPE_LIMITED_HTML, self::TYPE_TEXT, self::TYPE_REDIRECT, self::TYPE_URL);
    }

    /* @var cms_page */
    protected $page;

    public function __construct(cms_page $page = null)
    {
        $this->page = $page;
    }

    public static function getPageContent(cms_page $page)
    {
        $result = core_db::runQuery('SELECT * 
                                  FROM cms_content 
                                  WHERE "' . $page->getPath() . '" LIKE path 
                                    AND published=1');
        if(!$result){ return array(); }
        $arr = array();
        while ($r = $result->fetch_object('cms_content', array('page' => $page))) {
            /* @var $r cms_content */
            if (!isset($arr[$r->getLocation()])
                || $r->getPriority() > $arr[$r->getLocation()]->getPriority()
                || ($page && $r->getPriority() == $arr[$r->getLocation()]->getPriority()
                    && $r->getPath() == $page->getPath()->getString()
                    && $arr[$r->getLocation()]->getPath() != $page->getPath()->getString())
                || ($r->getPriority() == $arr[$r->getLocation()]->getPriority()
                    && $r->getTime() > $arr[$r->getLocation()]->getTime())
            )
                $arr[$r->getLocation()] = $r;
        }
        $result->free_result();
        return $arr;
    }

    public static function getTemp($location, $type, $content, cms_page $page)
    {
        $tempContent = new cms_content($page);
        $tempContent->type = $type;
        $tempContent->location = $location;
        $tempContent->content = $tempContent->sanitize($content);
        return $tempContent;
    }

    /**
     *
     * @param bool $publishedOnly
     * @return array
     */
    public static function getContentLocations($publishedOnly = true)
    {
        $arr = array();
        $result = core_db::runQuery('SELECT 
                                  DISTINCT location 
                                  FROM cms_content 
                                  WHERE ' . ($publishedOnly ? 'published = ' : '') . '1 
                                  ORDER BY location');
        while ($r = $result->fetch_row())
            $arr[] = $r[0];

        $result->free_result();
        return $arr;
    }

    public static function getAllContentByLocation($location, $exactPathsOnly = true, $excludeAdmin = true)
    {
        return core_db::runGetObjects(
            'SELECT * 
              FROM cms_content 
              WHERE `location`="' . self::sanitizeLocationString($location) . '"
                AND published = 1
                ' . ($exactPathsOnly ? 'AND `path` NOT LIKE "%\%%"' : '') . '
                ' . ($excludeAdmin ? 'AND `path` NOT LIKE "/_/admin/%"' : '') . '
              ORDER BY path',
            'cms_content');
    }

    public static function getAllContent(ARRAY $filters, $exactPathsOnly = true)
    {
        return core_db::runGetObjects(
            'SELECT * 
              FROM cms_content 
              WHERE published = 1
                ' . (isset($filters['ExcludeLocations'])
                ? 'AND location NOT IN ("' . implode('","', array_map(array('cms_content', 'sanitizeLocationString'), $filters['ExcludeLocations'])) . '")'
                : '') . ' 
                ' . ($exactPathsOnly ? 'AND `path` NOT LIKE "%\%%"' : '') . '
              ORDER BY path',
            'cms_content');
    }

    public static function getContentLocationType($location)
    {
        return core_db::runGetValue(
            'SELECT `type` 
                FROM cms_content 
                WHERE `path`="%"
                  AND location="' . self::sanitizeLocationString($location) . '"
                LIMIT 1');
    }

    public function saveChanges($path, $content, $priority)
    {
        if (!$this->published) {
            return false;
        }
        $path = self::sanitizePathString($path);
        $priority = ($priority < 0 ? 0 : (int)$priority);
        $content = $this->sanitize($content);

        $db = new core_db();
        $db->query('UPDATE cms_content 
                SET published=0 
                WHERE `path`="' . $path . '" 
                  AND location="' . $this->location . '"');
        $db->query('INSERT INTO cms_content 
                  (`path`, `type`, location, content, `time`, published, priority)
                  VALUES
                  ("' . $path . '",
                  "' . $this->type . '",
                  "' . $this->location . '",
                  "' . $db->escape_string($content) . '",
                  NOW(),
                  1,
                  ' . $priority . ')');
        return self::getContentById($db->insert_id);
    }

    public function sanitize($content)
    {
        switch ($this->type) {
            case self::TYPE_HTML:
                return self::sanitizeAndFixHTML($content);
            case self::TYPE_LIMITED_HTML:
                return self::sanitizeLimitedHTML($content);
            case self::TYPE_REDIRECT:
            case self::TYPE_URL:
                return cms_nav::sanitizePathString($content);
            default:
                return self::sanitizeText($content);
        }
    }

    public static function sanitizeText($text)
    {
        return req_sanitize::txt($text);
    }

    public static function checkForbiddenCharacters($text)
    {
        return req_sanitize::containsForbiddenCharacters($text);
    }

    public static function sanitizeAndFixHTML($HTML)
    {
        $HTML = trim($HTML);
        if (empty($HTML)) {
            return '';
        }
        include_once ROOT . '/_/includes/HTMLPurifier/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Trusted', true);
        $config->set('CSS.Trusted', true);
        $config->set('HTML.TargetBlank', true);
        $htmlFixer = new HTMLPurifier($config);
        return $htmlFixer->purify($HTML);
    }

    public static function sanitizeLimitedHTML($HTML)
    {
        return strip_tags(self::sanitizeAndFixHTML($HTML), '<a><span><strong><b><em><i><br><small>');
    }

    public static function defineLocationType($location, $type)
    {
        if (self::getContentLocationType($location)) {
            return false; //already defined
        }

        if (!in_array($type, self::getAvailableTypes())) {
            error_log('attempted to define location with invalid type');
            return false;
        }

        $db = new core_db();
        $db->query('INSERT INTO cms_content 
                (`path`,`type`,location,priority, published)
                VALUES
                ("%","' . $type . '","' . self::sanitizeLocationString($location) . '",0,' . ($type == self::TYPE_REDIRECT ? '0' : '1') . ')');

        return self::getContentById($db->insert_id);
    }

    /**
     *
     * @param int $content_id
     * @return cms_content
     */
    public static function getContentById($content_id)
    {
        return core_db::runGetObject(
            'SELECT * FROM cms_content WHERE content_id=' . (int)$content_id,
            'cms_content');
    }

    /**
     *
     * @param string $path
     * @param string $location
     * @return cms_content
     */
    public static function getContentByPathLocation($path, $location)
    {
        return core_db::runGetObject(
            'SELECT * FROM cms_content 
                WHERE `path`="' . self::sanitizePathString($path) . '"
                  AND location="' . self::sanitizeLocationString($location) . '"
                  AND published=1',
            'cms_content');
    }


    public function getID()
    {
        return (int)$this->content_id;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getPriority()
    {
        return (int)$this->priority;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getTime()
    {
        return strtotime($this->time);
    }

    public function getType()
    {
        return $this->type;
    }

    public function isHTML()
    {
        return $this->type == self::TYPE_HTML || $this->type == self::TYPE_LIMITED_HTML;
    }

    public function isTitleOrMain()
    {
        return $this->location === cms_page::LOC_MAIN || $this->location === cms_page::LOC_TITLE;
    }

    public function isPublished()
    {
        return (bool)$this->published;
    }

    public function __toString()
    {
        return (string)$this->getContent();
    }

    public function getContent()
    {
        return $this->content;
    }

    public static function sanitizeLocationString($location)
    {
        return preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $location);
    }

    public static function sanitizePathString($path)
    {
        return preg_replace('/[^a-zA-Z0-9\-_%\/]/', '', $path);
    }

    /**
     *
     * @param string $oldPath
     * @param string $newURL
     * @return cms_content
     */
    public static function makeRedirect($oldPath, $newURL)
    {
        if (($redirect = self::getContentByPathLocation($oldPath, cms_page::LOC_REDIRECT))) {
            $redirect->saveChanges($oldPath, $newURL, 10);
            return $redirect;
        } else {
            $db = new core_db();
            $db->query('INSERT INTO cms_content 
                    (`path`, `type`, location, content, `time`, published, priority)
                    VALUES
                    ("' . $db->escape_string($oldPath) . '",
                    "' . cms_content::TYPE_REDIRECT . '",
                    "' . cms_page::LOC_REDIRECT . '",
                    "' . $db->escape_string(cms_nav::sanitizePathString($newURL)) . '",
                    NOW(),
                    1,
                    10)');
            return self::getContentById($db->insert_id);
        }
    }

    public function delete($deleteSupportingContent = true)
    {
        $this->published = 0;
        if ($deleteSupportingContent && $this->isTitleOrMain()) {
            $secondary = $this->getLocation() == cms_page::LOC_MAIN
                ? self::getContentByPathLocation($this->getPath(), cms_page::LOC_TITLE)
                : self::getContentByPathLocation($this->getPath(), cms_page::LOC_MAIN);
            if ($secondary) {
                $secondary->delete(false);
            }
        }
        return core_db::runQuery('UPDATE cms_content SET published=0 WHERE content_id=' . $this->getID());
    }
}
