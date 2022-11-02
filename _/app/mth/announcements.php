<?php

/**
 * Announcements
 * 
 * @author  Rex
 */

use mth\aws\ses;

class mth_announcements
{
    protected $announcement_id;
    protected $content = null;
    protected $user_id;
    protected $date_created;
    protected $published = 0;
    protected $subject;
    protected $date_published;
    private static $_cache;

    public function getID()
    {
        return (int)$this->announcement_id;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getTime()
    {
        return strtotime($this->date_created);
    }

    public function getDatePublished()
    {
        return strtotime($this->date_published);
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getPostedBy()
    {
        $user = core_user::getUserById($this->user_id);
        return $user ? $user->getFirstName() : 'UNKNOWN';
    }

    public function isPublished()
    {
        return (bool)$this->published;
    }


    public function setPublished($set)
    {
        $this->published = (int)$set;
    }

    public function setDatePublished($set)
    {
        $this->date_published = $set;
    }

    public function setId($set)
    {
        $this->announcement_id = (int)$set;
    }

    public function save($content, $subject, $users = null)
    {
        $content = self::sanitizeAndFixHTML($content);
        $subject = self::sanitizeText($subject);

        $db = new core_db();

        $accouncementId = $this->getID();

        if ($this->getID()) {
            $result = $db->query("UPDATE mth_announcements 
                set content='{$db->escape_string($content)}'
                ,subject='{$db->escape_string($subject)}'
                ,published={$this->published}
                ,date_published='{$this->date_published}'
                ,user_id=" . core_user::getUserID()
                . " WHERE announcement_id={$this->getID()}");
            if ($users) {
                foreach (explode(",", $users) as $user_id) {
                    $sub_db = new core_db();
                    var_dump($sub_db->query("INSERT INTO mth_user_announcement(announcement_id, user_id) VALUES($accouncementId, $user_id)"));
                }
            }
            return $result;
        } else {
            $db->query("REPLACE INTO mth_announcements(content,subject,published,user_id" . ($this->published ? ',date_published' : '') . ") 
            VALUES('{$db->escape_string($content)}','{$db->escape_string($subject)}',{$this->published}," . core_user::getUserID() . ($this->published ? ",'" . $this->date_published . "'" : '') . ")");
            if ($users) {
                foreach (explode(",", $users) as $user_id) {
                    $sub_db = new core_db();
                    $sub_db->query("INSERT INTO mth_user_announcement(announcement_id, user_id) VALUES($db->insert_id, $user_id)");
                }
            }
            return self::getContentById($db->insert_id);
        }
    }

    public static function publish($content, $subject, array $to)
    {
        $subject = req_sanitize::txt_decode($subject);
        if ($user = core_user::getCurrentUser()) {
            $ses = new core_emailservice();
            $ses->enableTracking(true);

            if (!core_config::isProduction()) {
                $to = [$to[0]];
            }

            return $ses->send(
                $to,
                $subject,
                $content,
                [$user->getEmail(), $user->getName()]
            );
        }
        return false;
        //$announcement_admin = core_setting::get("announcementsbcc", 'Announcements')->getValue();     
    }

    public static function delete($id)
    {
        $db = new core_db();
        return $db->query("DELETE from mth_announcements WHERE announcement_id={$id}");
    }

    /**
     *
     * @param int $announcement_id
     * @return mth_announcements
     */
    public static function getContentById($announcement_id)
    {
        return core_db::runGetObject(
            'SELECT * FROM mth_announcements WHERE announcement_id=' . (int)$announcement_id,
            'mth_announcements'
        );
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

    public static function sanitizeText($text)
    {
        return req_sanitize::txt($text);
    }

    /**
     * Get All Announcements
     *
     * @param boolean $include_unpublished 
     * @param array $exclude_ids
     * @return mth_announcements
     */
    public static function getAllAnnouncements($include_unpublished = true, $exclude_ids = array())
    {
        $user = core_user::getCurrentUser();        
        $where = [];
        if (!$include_unpublished) {
            $where[] = 'published=1';
        }

        if (!empty($exclude_ids)) {
            $where[] = implode(',', $exclude_ids);
        }


        $where_stmt = count($where) > 0 ? ('WHERE ' . implode(' and ', $where) . ($user->canEmulate() ? '' : ' and')) : ($user->canEmulate() ? '' : 'where');
        $user_id = $user->getID();
        $sql = "SELECT mth_announcements.* from mth_announcements"
            . ($user->canEmulate() ? " " : " inner join mth_user_announcement on mth_user_announcement.announcement_id=mth_announcements.announcement_id ")
            . $where_stmt . ($user->canEmulate() ? "" : " mth_user_announcement.user_id = $user_id") . " group by mth_announcements.announcement_id order by date_created desc";
        
        return core_db::runGetObjects($sql, __class__);
    }

    /**
     * Get All Announcements IDs
     *
     * @param boolean $include_unpublished 
     * @param array $exclude_ids
     * @return mth_announcements
     */
    public static function getAnnouncementIds($include_unpublished = true, $exclude_ids = array())
    {
        $where = [];
        $ids = [];
        if (!$include_unpublished) {
            $where[] = 'published=1';
        }

        if (!empty($exclude_ids)) {
            $where[] = implode(',', $exclude_ids);
        }

        $where_stmt = count($where) > 0 ? ('WHERE ' . implode(' and ', $where)) : '';

        $results = core_db::runQuery('SELECT announcement_id FROM mth_announcements ' . $where_stmt);

        while ($row = $results->fetch_object()) {
            $ids[] = (int)$row->announcement_id;
        }

        $results->close();
        return $ids;
    }

    /**
     * Initialize Event BCC settings
     *
     * @param string $email
     * @return void
     */
    public static function initEmailBCC($email)
    {
        core_setting::init(
            'announcementsbcc',
            'Announcements',
            $email,
            core_setting::TYPE_TEXT,
            true,
            'Email BCC',
            '<p>Send an email copy to this email address.</p>'
        );
    }

    public function send(array $people, $subject, $content)
    {

        $announcement_admin = core_setting::get("announcementsbcc", 'Announcements')->getValue();
        $ses = new ses();

        $bccs = [];
        $first_person = '';

        foreach ($people as $key => $person) {
            $_email = $person->getEmail();
            if (core_user::validateEmail($_email)) {
                if ($key == 0) {
                    $first_person = $_email;
                } else {
                    $bccs[] = $_email;
                }
            }
        }

        if (empty($first_person) || empty($bccs)) {
            return false;
        }

        return $ses->send(
            [$first_person],
            $subject,
            $content,
            $bccs,
            [$announcement_admin]
        );
    }
}
