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
        $this['last_author'] = $GLOBALS['user']->id;
        return true;
    }

    public function isReadable($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        if ($GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'], $user_id)) {
            return true;
        }
        switch ($this['read_permission']) {
            case "all":
                return true;
            case "tutor":
                return $GLOBALS['perm']->have_studip_perm("tutor", $this['seminar_id'], $user_id);
            case "dozent":
                return $GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'], $user_id);
            default:
                //statusgruppe_id
                return Statusgruppen::find($this['read_permission'])->isMember($user_id);
        }
        return false;
    }

    public function isEditable($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        if ($GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'], $user_id)) {
            return true;
        }
        switch ($this['write_permission']) {
            case "all":
                return true;
            case "tutor":
                return $GLOBALS['perm']->have_studip_perm("tutor", $this['seminar_id'], $user_id);
            case "dozent":
                return $GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'], $user_id);
            default:
                //statusgruppe_id
                $statusgruppe = Statusgruppen::find($this['read_permission']);
                return $statusgruppe && $statusgruppe->isMember($user_id);
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
}