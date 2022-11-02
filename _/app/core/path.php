<?php

/**
 * Description of path
 *
 * @author abe
 */
class core_path
{
    protected $uri;
    protected $pathStr;
    protected $pathArr;

    private static $_paths;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /**
     *
     * @param string $uri with leaing slash(e.g. /path/after/domain), can be relative to current location (no-leading slash), or only include the query string
     * @return \core_path
     */
    public static function getPath($uri = null)
    {
        if (!isset(self::$_paths[$uri])) {
            if ($uri && ($uri[0] == '?' || $uri[0] == '#')) {
                $uri = NULL;
            } elseif ($uri && $uri[0] != '/') {
                $uri = self::buildPathForRelative($uri);
            }
            $redirect_url = isset($_SERVER['REDIRECT_URL']) && $_SERVER['REDIRECT_URL'] == '/' ? $_SERVER['REQUEST_URI'] : $_SERVER['REDIRECT_URL'];
            self::$_paths[$uri] = new core_path($uri !== null ? $uri : $redirect_url);
        }
        return self::$_paths[$uri];
    }

    public static function buildPathForRelative($relativePath)
    {
        $pathArr = self::getPath()->getArray();
        do {
            $oldUri = $relativePath;
            $relativePath = preg_replace('/[^\.\/]+\/\.\.\//', '', $relativePath);
        } while ($oldUri != $relativePath);
        $matches = array();
        preg_match_all('/\.\.\//', $relativePath, $matches);
        if ($matches) {
            for ($i = 1; $i <= count($matches[0]); $i++) {
                array_pop($pathArr);
            }
        }
        $relativePath = str_replace('../', '', $relativePath);
        $pathArr[count($pathArr) - 1] = $relativePath;
        return '/' . implode('/', $pathArr);
    }

    public function __toString()
    {
        return $this->getString();
    }

    /**
     *
     * @return string full uri path without leading slash
     */
    public function getString()
    {
        if ($this->pathStr === NULL) {
            $this->pathStr = preg_replace('/[^a-zA-Z0-9\-_\.\/]/', '', $this->getRawString());
        }
        return $this->pathStr;
    }

    public function getRawString()
    {
        if (!$this->uri || $this->uri[0] != '/') {
            $this->uri = '/' . $this->uri;
        }
        return current(explode('?', $this->uri));
    }

    public function getArray()
    {
        if ($this->pathArr === NULL) {
            $this->pathArr = explode('/', trim($this->getString(), '/'));
        }
        return $this->pathArr;
    }

    public function getSegment($num)
    {
        $arr = $this->getArray();
        if (isset($arr[$num])) {
            return $arr[$num];
        }
    }

    public function getRawArray()
    {
        return explode('/', trim($this->getRawString(), '/'));
    }

    public function isAdmin()
    {
        $pathArr = $this->getArray();
        if (count($pathArr) < 2) {
            return false;
        }
        return $pathArr[0] . '/' . $pathArr[1] === '_/admin' || $pathArr[0] . '/' . $pathArr[1] === '_/teacher';
    }
}
