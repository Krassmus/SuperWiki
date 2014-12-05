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
        parent::configure($config);
    }

    public function __construct($id = null)
    {
        $this->registerCallback('before_store', 'createVersion');
        parent::__construct($id);
    }

    protected function createVersion()
    {
        if (($this->content['content'] !== $this->content_db['data'])
                && (($this->content_db['last_author'] !== $this->content['last_author']) || ($this['chdate'] < time() - 60 * 30))) {
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
        return true;
    }

    public function isEditable($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        return true;
    }
}