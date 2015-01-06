<?php

class SuperwikiPage extends SimpleORMap {

    static public function findByName($name, $seminar_id)
    {
        $pages = self::findBySQL("name = :name AND seminar_id = :seminar_id", array('name' => $name, 'seminar_id' => $seminar_id));
        return count($pages) ? $pages[0] : null;
    }

    static public function findAll($seminar_id)
    {
        return self::findBySQL("content != '' AND content IS NOT NULL AND seminar_id = ? ORDER BY name ASC", array($seminar_id));
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'superwiki_pages';
        $config['has_many']['versions'] = array(
            'class_name' => 'SuperwikiVersion',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['belongs_to']['settings'] = array(
            'class_name' => 'SuperwikiSettings',
            'foreign_key' => 'seminar_id'
        );
        parent::configure($config);
    }

    public function __construct($id = null)
    {
        $this->registerCallback('before_store', 'createVersion');
        parent::__construct($id);
    }

    protected function createVersion()
    {
        if (!$this->isNew() && ($this->content['content'] !== $this->content_db['data'])
                && (($this->content_db['last_author'] !== $this->content['last_author'])
                    || ($this['chdate'] < time() - 60 * 30))) {
            //Neue Version anlegen:
            $version = new SuperwikiVersion();
            $version->setData($this->content_db);
            $version->setId($version->getNewId());
            $version->store();
        }
        return true;
    }

    public function isReadable($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        switch ($this['read_permission']) {
            case "all":
                return true;
            case "tutor":
                return $GLOBALS['perm']->have_studip_perm("tutor", $this['seminar_id'], $user_id);
            case "dozent":
                return $GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'], $user_id);
        }
        return false;
    }

    public function isEditable($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        switch ($this['write_permission']) {
            case "all":
                return true;
            case "tutor":
                return $GLOBALS['perm']->have_studip_perm("tutor", $this['seminar_id'], $user_id);
            case "dozent":
                return $GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'], $user_id);
        }
        return false;
    }

    public function wikiFormat()
    {
        $text = formatReady($this['content']);
        $pages = self::findBySQL("seminar_id = ? AND content IS NOT NULL AND content != '' ORDER BY CHAR_LENGTH(name) DESC", array($this['seminar_id']));
        foreach ($pages as $page) {
            if ($page->getId() !== $this->getId()) {
                $text = preg_replace("/(\s)".$page['name']."/", '$1<a href="'.URLHelper::getLink("plugins.php/superwiki/page/view/".$page->getId(), array('cid' => $page['seminar_id'])).'">'.Assets::img("icons/16/blue/".$page->settings['icon'], array('class' => "text-bottom"))." ".htmlReady($page['name']).'</a>', $text);
            }
        }
        return $text;
    }

    public function merge($text1, $text2, $original)
    {
        $start1 = null;
        $start2 = null;
        $end1 = null;
        $end2 = null;
        for($i = 0; $i < strlen(original); $i++) {
            if ($original[$i] !== $text1[$i]) {
                $start1 = $i;
                break;
            }
        }
        for($i = 0; $i < strlen($original); $i++) {
            if ($original[$i] !== $text2[$i]) {
                $start2 = $i;
                break;
            }
        }
        for($i = strlen($original); $i > 0; $i--) {
            if ($original[$i] !== $text1[$i]) {
                $end1 = $i;
                break;
            }
        }
        for($i = strlen($original); $i > 0; $i--) {
            if ($original[$i] !== $text2[$i]) {
                $end2 = $i;
                break;
            }
        }
        if ($start1 === null) {
            $start1 = 0;
        }
        if ($start2 === null) {
            $start2 = 0;
        }
        if ($end1 === null) {
            $end1 = 0;
        }
        if ($end2 === null) {
            $end2 = 0;
        }

        //now we sort the carets, so we can begin with the first:
        if ($start1 <= $start2) {
            if ($end1 >= $end2) {
                //now we have a dominant version1
                return $text1;
            }
        } else {
            if ($end2 >= $end1) {
                //now we have a dominant version2
                return $text2;
            }
            $k = $start2;
            $start2 = $start1;
            $start1 = $k;
            $k = $end2;
            $end2 = $end1;
            $end1 = $k;
            $k = $text1;
            $text1 = $text2;
            $text2 = $k;
            //now we have switched carets and texts, so that text1 has earlier changes
        }
        if ($end1 <= $start2) {
            $text = substr($text1, 0, strlen($text1) - (strlen($original) - $end1));
            $text .= substr($text2, strlen($text1) - (strlen($original) - $end1));
            return $text;
        } else {
            //this is a conflict, take the more changed text as the result
            if ($end1 - $start1 > $end2 - $start2) {
                return $text1;
            } else {
                return $text2;
            }
        }
    }
}