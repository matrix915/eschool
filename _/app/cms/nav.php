<?php

/**
 * nav
 *
 * @author abe
 */
class cms_nav
{

    protected $nav;
    protected $nav_item_id;
    protected $order;
    protected $path;
    protected $title;
    protected $parent_nav_item_id;

    //icon only supports font awesome classes eg. 'fa-envelope'
    protected $icon;

    protected $child_count;

    protected static $cache = array();

    public $children = array();

    /**
     *
     * @param string $nav
     * @param int $parent_nav_item_id
     * @return cms_nav or array of cms_nav objects will return an array unless the $nav is not NULL and $parent_nav_item_id is NULL
     */
    public static function getNavObj($nav = NULL, $parent_nav_item_id = NULL)
    {
        $rtn = core_db::runGetObjects(
            'SELECT n.*, (SELECT COUNT(*) FROM cms_nav AS cn WHERE cn.parent_nav_item_id=n.nav_item_id) AS child_count
              FROM cms_nav AS n
              WHERE ' . ($nav ? 'n.nav="' . self::sanitizeNavString($nav) . '"' : '1') . '
                ' . ($parent_nav_item_id
                ? 'AND n.parent_nav_item_id=' . (int)$parent_nav_item_id
                : 'AND n.parent_nav_item_id IS NULL') . '
              ORDER BY n.`order`',
            'cms_nav');

        if (empty($rtn) && $nav) {
            $rtn = array(self::makeNav($nav));
        }
        foreach ($rtn as $navItem) {
            /* @var $navItem cms_nav_item */
            if ($navItem && !$navItem->getChildCount()) {
                continue;
            }
            $navItem->children = self::getNavObj($nav, $navItem->getID());
        }
        if (!$parent_nav_item_id && $nav) {
            $rtn = $rtn[0];
        }
        return $rtn;
    }

    /**
     *
     * @param int $nav_item_id
     * @return cms_nav
     */
    public static function getNavItemByID($nav_item_id)
    {
        if (!isset(self::$cache[$nav_item_id])) {
            self::$cache[$nav_item_id] = core_db::runGetObject(
                'SELECT n.*, 
                                      (SELECT COUNT(*) 
                                        FROM cms_nav AS cn 
                                        WHERE cn.parent_nav_item_id=n.nav_item_id) AS child_count
                                      FROM cms_nav AS n
                                      WHERE n.nav_item_id=' . (int)$nav_item_id,
                'cms_nav');
        }
        return self::$cache[$nav_item_id];
    }

    public static function getAllNavItemIDs($childrenOnly = true)
    {
        $arr = array();
        $result = core_db::runQuery('SELECT nav_item_id
                                  FROM cms_nav
                                  WHERE ' . ($childrenOnly ? 'parent_nav_item_id IS NOT NULL' : '1'));
        while ($r = $result->fetch_row())
            $arr[$r[0]] = $r[0];

        $result->free_result();
        return $arr;
    }

    /**
     *
     * @param int $parent_nav_item_id
     * @param string $path
     * @return cms_nav
     */
    public static function getChildByPath($parent_nav_item_id, $path)
    {
        return core_db::runGetObject('SELECT * FROM cms_nav 
                                  WHERE parent_nav_item_id=' . (int)$parent_nav_item_id . ' 
                                    AND `path`="' . core_db::escape(self::sanitizePathString($path)) . '"', 'cms_nav');
    }

      /**
     *
     * @param int $parent_nav_item_id
     * @param string $path
     * @return cms_nav
     */
    public static function getNavByPath($path)
    {
        return core_db::runGetObject('SELECT * FROM cms_nav 
                                  WHERE `path`="' . core_db::escape(self::sanitizePathString($path)) . '"', 'cms_nav');
    }

    /**
     *
     * @param string $nav
     * @return cms_nav
     */
    public static function makeNav($nav)
    {
        $db = new core_db;
        $db->query('INSERT INTO cms_nav (nav) VALUES ("' . self::sanitizeNavString($nav) . '")');
        return self::getNavItemByID($db->insert_id);
    }

    public static function sanitizeNavString($nav)
    {
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $nav);
    }

    public static function sanitizeTitleString($title)
    {
        return str_replace(array("\n", "\r", "\t"), '', htmlentities(strip_tags($title)));
    }

    public static function sanitizePathString($path)
    {
        if (empty($path)) {
            return '/';
        } elseif (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return str_replace(
                array("\n", "\r", "\t", '"', "'"),
                array('', '', '', '%22', '%27'),
                $path);
        } else {
            return core_path::getPath($path)->getString();
        }
    }

    /**
     *
     * @param string $path
     * @param string $title
     * @return cms_nav
     */
    public function addChild($path, $title, $order = NULL, $preventDuplicates = false, $icon = NULL)
    {
        if ($preventDuplicates && ($navItem = self::getNavByPath($path))) {
            return $navItem;
        }
        core_db::runQuery('INSERT INTO cms_nav 
                  (nav,`order`,`path`,title,parent_nav_item_id,icon)
                SELECT n.nav, 
                  ' . ($order === NULL
                ? 'IFNULL((SELECT MAX(n2.`order`) FROM cms_nav AS n2 WHERE n2.parent_nav_item_id=n.nav_item_id),0) AS `order`'
                : (int)$order) . ',
                  "' . core_db::escape(self::sanitizePathString($path)) . '",
                  "' . core_db::escape(self::sanitizeTitleString($title)) . '",
                  n.nav_item_id,
                  ' .($icon == NULL?'NULL': ("'".core_db::escape(self::sanitizeTitleString($icon))."'"))  . '
                FROM cms_nav AS n
                WHERE n.nav_item_id=' . $this->getID());
        return self::getNavItemByID(core_db::getInsertID());
    }

    public function getID()
    {
        return (int)$this->nav_item_id;
    }

    public function getChildCount()
    {
        return (int)$this->child_count;
    }

    public function getNav()
    {
        return $this->nav;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getIcon(){
        return $this->icon;
    }

    /**
     *
     * @return core_path
     */
    public function getPath()
    {
        return core_path::getPath($this->path);
    }

    public function getOrder()
    {
        return (int)$this->order;
    }

    public function getParentID()
    {
        return (int)$this->parent_nav_item_id;
    }

    public function update($title = NULL, $order = NULL, $path = NULL, $parentID = NULL,$icon = NULL)
    {
        $db = new core_db();
        $updateArr = array();
        if ($title !== NULL) {
            $this->title = self::sanitizeTitleString($title);
            $updateArr[] = '`title`="' . $this->title . '"';
        }
        if ($order !== NULL) {
            $this->order = (int)$order;
            $updateArr[] = '`order`=' . $this->order;
        }
        if ($path !== NULL) {
            $this->path = self::sanitizePathString($path);
            $updateArr[] = '`path`="' . $db->escape_string($this->path) . '"';
        }
        if ($parentID !== NULL) {
            $this->parent_nav_item_id = (int)$parentID;
            $updateArr[] = 'parent_nav_item_id=' . $this->parent_nav_item_id;
        }
        if ($icon !== NULL) {
            $this->icon = $icon;
            $updateArr[] = 'icon="' . $this->icon. '"';
        }
        return $db->query('UPDATE cms_nav 
                        SET ' . implode(',', $updateArr) . ' 
                        WHERE nav_item_id=' . $this->nav_item_id);
    }

    public function delete()
    {
        return core_db::runQuery('DELETE FROM cms_nav 
                              WHERE nav_item_id=' . (int)$this->nav_item_id . '
                                OR parent_nav_item_id=' . (int)$this->nav_item_id);
    }

    public function printThisNav($classAttribute = NULL, $idAttribute = NULL, $childrenOnly = TRUE,$hasIcon = FALSE,$sidenav = false)
    {
       
        if (!$childrenOnly){
            $hasChildClass = count($this->children)>0?'has-sub':''; 
            $icon = $hasIcon?' <i class="site-menu-icon fa '.$this->getIcon().'" aria-hidden="true"></i>':'';
            echo '<li id="cms_nav-' . $this->getID() . '" class="site-menu-item '.$hasChildClass.'">
            <a href="' . $this->getPath() . '">'. $icon.'<span class="site-menu-title">' . ($this->getTitle() ? $this->getTitle() : $this->getNav()) . '</span></a>';
        }
        if ($this->getChildCount()) {
            
            $children_menu = !$childrenOnly?'site-menu-sub ':'';
            $displayblock = $sidenav?'':'style="display:block"';
            echo '<ul data-plugin="menu" '.$displayblock.' class="'. $children_menu . $classAttribute . ' cms_nav-' . $this->getID() . ' cms_nav" id="' . ($idAttribute ? $idAttribute : 'cms_nav_sub-' . $this->getID()) . '">';
            foreach ($this->children as $childItem) {
                $childItem->printThisNav(NULL, NULL, FALSE,$hasIcon,$sidenav);
            }
            echo '</ul>';
        }
        if (!$childrenOnly)
            echo '</li>';
    }

    /**
     *
     * @param string $nav if NULL this will print parent items, it will not wrap them in a ul element
     * @param string $classAttribute
     * @param string $idAttribute
     * @param boolean $hasIcon
     */
    public static function printNav($nav = NULL, $classAttribute = NULL, $idAttribute = NULL,$hasIcon = FALSE, $sidenav = false)
    {
        $navObj = self::getNavObj($nav);
        if ($navObj && $nav) {
            $navObj->printThisNav($classAttribute, $idAttribute,TRUE,$hasIcon,$sidenav);
        } elseif (is_array($navObj)) {
            foreach ($navObj as $navObjItem) {
                $navObjItem->printThisNav($classAttribute, $idAttribute, FALSE,$hasIcon,$sidenav);
            }
        }
    }
}
